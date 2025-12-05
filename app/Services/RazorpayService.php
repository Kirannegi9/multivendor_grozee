<?php
namespace App\Services;

use App\Traits\Processor;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;

class RazorpayService
{
    use Processor;

    public function setRazorpayConfig(): ?array
    {
        return Cache::remember('razorpay_config', 300, function () {
            $config = $this->payment_config('razor_pay', 'payment_config');

            if (!$config) return null;

            $razor = $config->mode === 'live'
                ? json_decode($config->live_values)
                : json_decode($config->test_values ?? '{}');

            if ($razor?->api_key && $razor?->api_secret) {
                Config::set('razor_config', [
                    'api_key' => $razor->api_key,
                    'api_secret' => $razor->api_secret,
                ]);
                return [
                    'api_key' => $razor->api_key,
                    'api_secret' => $razor->api_secret,
                ];
            }

            return null;
        });
    }
}
