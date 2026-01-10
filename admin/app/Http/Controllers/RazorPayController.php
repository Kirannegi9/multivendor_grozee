<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use App\Models\PaymentRequest;
use App\Traits\Processor;
use Razorpay\Api\Api;

class RazorPayController extends Controller
{
    use Processor;

    private PaymentRequest $payment;
    private $user;

    public function __construct(PaymentRequest $payment, User $user)
    {
        $config = $this->payment_config('razor_pay', 'payment_config');
        $razor = false;
        if (!is_null($config) && $config->mode == 'live') {
            $razor = json_decode($config->live_values);
        } elseif (!is_null($config) && $config->mode == 'test') {
            $razor = json_decode($config->test_values);
        }

        if ($razor) {
            $config = array(
                'api_key' => $razor->api_key,
                'api_secret' => $razor->api_secret
            );
            Config::set('razor_config', $config);
        }

        $this->payment = $payment;
        $this->user = $user;
    }

    public function index(Request $request): View|Factory|JsonResponse|Application
    {
        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|uuid'
        ]);

        if ($validator->fails()) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_400, null, $this->error_processor($validator)), 400);
        }

        $data = $this->payment::where(['id' => $request['payment_id']])->where(['is_paid' => 0])->first();
        if (!isset($data)) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_204), 200);
        }
        $payer = json_decode($data['payer_information']);

        if ($data['additional_data'] != null) {
            $business = json_decode($data['additional_data']);
            $business_name = $business->business_name ?? "my_business";
            $business_logo = $business->business_logo ?? url('/');
        } else {
            $business_name = "my_business";
            $business_logo = url('/');
        }

        // Get saved UPI ID from user if exists
        $saved_upi_id = null;
        if (isset($data->payer_id)) {
            $user = $this->user::find($data->payer_id);
            if ($user && Schema::hasColumn('users', 'upi_id') && !empty($user->upi_id)) {
                $saved_upi_id = $user->upi_id;
            }
        }

        return view('payment-views.razor-pay', compact('data', 'payer', 'business_logo', 'business_name', 'saved_upi_id'));
    }

    public function payment(Request $request): JsonResponse|Redirector|RedirectResponse|Application
    {
        $input = $request->all();
        
        // Validate required parameters
        if (empty($input['razorpay_payment_id']) || empty($request['payment_id'])) {
            Log::error('Razorpay Payment Callback - Missing required parameters', [
                'has_razorpay_payment_id' => !empty($input['razorpay_payment_id']),
                'has_payment_id' => !empty($request['payment_id'])
            ]);
            $payment_data = $this->payment::where(['id' => $request['payment_id']])->first();
            if (isset($payment_data) && function_exists($payment_data->failure_hook)) {
                call_user_func($payment_data->failure_hook, $payment_data);
            }
            return $this->payment_response($payment_data, 'fail');
        }

        try {
            $api = new Api(config('razor_config.api_key'), config('razor_config.api_secret'));
            $payment = $api->payment->fetch($input['razorpay_payment_id']);

            if (count($input) && !empty($input['razorpay_payment_id'])) {
                $response = $api->payment->fetch($input['razorpay_payment_id'])->capture(array('amount' => $payment['amount'] - $payment['fee']));
                $this->payment::where(['id' => $request['payment_id']])->update([
                    'payment_method' => 'razor_pay',
                    'is_paid' => 1,
                    'transaction_id' => $input['razorpay_payment_id'],
                ]);
                $data = $this->payment::where(['id' => $request['payment_id']])->first();
                
                // Save UPI ID if payment was made via UPI
                if (isset($data->payer_id)) {
                    try {
                        // Fetch payment details again to get complete information including VPA
                        $payment_details = $api->payment->fetch($input['razorpay_payment_id']);
                        $upi_id = null;
                        
                        // Try to get UPI ID from payment details
                        if (method_exists($payment_details, 'toArray')) {
                            $payment_array = $payment_details->toArray();
                            if (isset($payment_array['method']) && $payment_array['method'] == 'upi' && isset($payment_array['vpa']) && !empty($payment_array['vpa'])) {
                                $upi_id = $payment_array['vpa'];
                            }
                        } elseif (isset($payment_details->method) && $payment_details->method == 'upi' && isset($payment_details->vpa) && !empty($payment_details->vpa)) {
                            $upi_id = $payment_details->vpa;
                        } elseif (is_array($payment_details) && isset($payment_details['method']) && $payment_details['method'] == 'upi' && isset($payment_details['vpa']) && !empty($payment_details['vpa'])) {
                            $upi_id = $payment_details['vpa'];
                        }
                        
                        // Save UPI ID to user if found
                        if (!empty($upi_id)) {
                            $user = $this->user::find($data->payer_id);
                            if ($user && Schema::hasColumn('users', 'upi_id')) {
                                $user->upi_id = $upi_id;
                                $user->save();
                                Log::info('UPI ID saved to user', [
                                    'user_id' => $user->id,
                                    'upi_id' => $upi_id,
                                    'payment_id' => $input['razorpay_payment_id']
                                ]);
                            }
                        }
                    } catch (\Exception $e) {
                        // Log error but don't break payment flow
                        Log::error('Failed to save UPI ID', [
                            'error' => $e->getMessage(),
                            'payment_id' => $input['razorpay_payment_id'],
                            'payer_id' => $data->payer_id ?? null
                        ]);
                    }
                }
                
                if (isset($data) && function_exists($data->success_hook)) {
                    call_user_func($data->success_hook, $data);
                }
                return $this->payment_response($data, 'success');
            }
        } catch (\Exception $e) {
            Log::error('Razorpay Payment Processing Error', [
                'error' => $e->getMessage(),
                'payment_id' => $input['razorpay_payment_id'] ?? null,
                'payment_request_id' => $request['payment_id'] ?? null
            ]);
        }
        
        $payment_data = $this->payment::where(['id' => $request['payment_id']])->first();
        if (isset($payment_data) && function_exists($payment_data->failure_hook)) {
            call_user_func($payment_data->failure_hook, $payment_data);
        }
        return $this->payment_response($payment_data, 'fail');
    }
}
