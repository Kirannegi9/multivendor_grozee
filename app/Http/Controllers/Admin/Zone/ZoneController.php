<?php

namespace App\Http\Controllers\Admin\Zone;

use App\Models\Order;
use Illuminate\View\View;
use App\Exports\ZoneExport;
use Illuminate\Http\Request;
use App\Services\ZoneService;
use Illuminate\Http\JsonResponse;
use Brian2694\Toastr\Facades\Toastr;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\BaseController;
use App\Enums\ExportFileNames\Admin\Zone;
use App\Http\Requests\Admin\ZoneAddRequest;
use Illuminate\Database\Eloquent\Collection;
use App\Http\Requests\Admin\ZoneUpdateRequest;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Enums\ViewPaths\Admin\Zone as ZoneViewPath;
use App\Http\Requests\Admin\ZoneModuleUpdateRequest;
use App\Contracts\Repositories\ZoneRepositoryInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Contracts\Repositories\TranslationRepositoryInterface;
use MatanYadaev\EloquentSpatial\Objects\Polygon;
use Illuminate\Support\Facades\Log;
use MatanYadaev\EloquentSpatial\Objects\LineString;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Illuminate\Support\Facades\DB;

class ZoneController extends BaseController
{
    public function __construct(
        protected ZoneRepositoryInterface $zoneRepo,
        protected ZoneService $zoneService,
        protected TranslationRepositoryInterface $translationRepo
    )
    {
    }

    public function index(?Request $request): View|Collection|LengthAwarePaginator|null
    {
        return $this->getAddView($request);
    }

    private function getAddView(Request $request): View
    {
        $zones = $this->zoneRepo->getListWhere(
            searchValue: $request['search'],
            dataLimit: config('default_pagination')
        );
        $language = getWebConfig('language');
        $defaultLang = str_replace('_', '-', app()->getLocale());
        return view(ZoneViewPath::INDEX[VIEW], compact('zones','language','defaultLang'));
    }

     public function add(ZoneAddRequest $request): JsonResponse
    {
        $data = $this->zoneService->getAddData($request); // ✅ No need to pass zoneId
    
        if (empty($data)) {
            return response()->json(['error' => 'Failed to add zone'], 500);
        }
    
        // $zone = $this->zoneRepo->add($data);
        // $this->translationRepo->addByModel($request, $zone, 'App\Models\Zone', 'name');
    
        $zones = $this->zoneRepo->getListWhere(
            relations: ['stores', 'deliverymen'],
            dataLimit: config('default_pagination')
        );
    
        return response()->json([
            'view' => view('admin-views.zone.partials._table', compact('zones'))->render(),
            'total' => $zones->count()
        ]);
    }
    public function update(ZoneUpdateRequest $request, $id): RedirectResponse
    {
        try {
            // Fetch existing zone
            $zone = $this->zoneRepo->getById($id); // ✅ Try this instead of `find($id)`
            if (!$zone) {
                Toastr::error(translate('messages.zone_not_found'));
                return back();
            }
    
            // Process polygon coordinates
            $polygonPoints = [];
            $value = $request->coordinates;
    
            foreach (explode('),(', trim($value, '()')) as $single_array) {
                $coords = explode(',', $single_array);
                $lat = (float)trim($coords[0]); // Latitude
                $lng = (float)trim($coords[1]); // Longitude
                $polygonPoints[] = new Point($lat, $lng);
            }
    
            if ($polygonPoints[0] !== end($polygonPoints)) {
                $polygonPoints[] = $polygonPoints[0]; // Close the polygon
            }
    
            $polygonWKT = "POLYGON((" . implode(", ", array_map(fn($p) => "{$p->longitude} {$p->latitude}", $polygonPoints)) . "))";
    
            // Update zone data
            $this->zoneRepo->update($id, [
                'name'                  => $request->name[array_search('default', $request->lang)] ?? 'Unnamed Zone',
                'coordinates'           => DB::raw("ST_GeomFromText('{$polygonWKT}', 4326)"),
                'cash_on_delivery'      => $request->cash_on_delivery ? 1 : 0,
                'digital_payment'       => $request->digital_payment ? 1 : 0,
                'updated_at'            => now(),
            ]);
    
            // Update translations
            $this->translationRepo->updateByModel($request, $zone, 'App\Models\Zone', 'name');
    
            Toastr::success(translate('messages.zone_updated_successfully'));
            return back();
        } catch (\Exception $e) {
            Toastr::error(translate('messages.zone_update_failed'));
            return back();
        }
    }

    public function getUpdateView(string|int $id): View|RedirectResponse
    {
        if(env('APP_MODE')=='demo' && $id == 1)
        {
            Toastr::warning(translate('messages.you_can_not_edit_this_zone_please_add_a_new_zone_to_edit'));
            return back();
        }

        $zone = $this->zoneRepo->getWithCoordinateWhere(
            params: ['id'=> $id]
        );

        $area = json_decode($zone['coordinates'][0]->toJson(),true);
        $language = getWebConfig('language');
        $defaultLang = str_replace('_', '-', app()->getLocale());

        return view(ZoneViewPath::UPDATE[VIEW], compact(['zone','area','language','defaultLang']));
    }


    public function delete(Request $request): RedirectResponse
    {
        if(env('APP_MODE')=='demo' && $request['id'] == 1)
        {
            Toastr::warning(translate('messages.you_can_not_delete_this_zone_please_add_a_new_zone_to_delete'));
            return back();
        }
        if(Order::where('zone_id',$request['id'])->whereIn('order_status', ['pending','accepted','confirmed','processing','handover','picked_up'])->exists())
        {
            Toastr::warning(translate('messages.you_can_not_delete_this_zone_Please_complete_the_ongoing_orders_of_this_zone'));
            return back();
        }
        $this->zoneRepo->delete(id: $request['id']);
        Toastr::success(translate('messages.zone_deleted_successfully'));
        return back();
    }

    public function exportList(Request $request, string $type): BinaryFileResponse
    {
        $collection = $this->zoneRepo->getExportList($request);
        $data=[
            'data' =>$collection,
            'search' =>$request['search'] ?? null,
        ];
        if($type == 'csv'){
            return Excel::download(new ZoneExport($data), Zone::EXPORT_CSV);
        }
        return Excel::download(new ZoneExport($data), Zone::EXPORT_XLSX);
    }

    public function zoneFilter($id): RedirectResponse
    {
        if($id == 'all')
        {
            if(session()->has('zone_id')){
                session()->forget('zone_id');
            }
        }
        else{
            session()->put('zone_id', $id);
        }

        return back();
    }

    public function getModuleSetupView($id): View
    {
        $zone=$this->zoneRepo->getFirstWhere(
            params: ['id'=>$id],
            relations: ['modules']
        );
        return view(ZoneViewPath::MODULE_SETUP[VIEW], compact('zone'));
    }

    public function getLatestModuleSetupView(): View
    {
        $zone=$this->zoneRepo->getLatest(
            relations: ['modules']
        );
        return view(ZoneViewPath::MODULE_SETUP[VIEW], compact('zone'));
    }

    public function updateModuleSetup(ZoneModuleUpdateRequest $request, $id): RedirectResponse
    {
        $data = $this->zoneService->checkModuleDeliveryCharge(moduleData: $request->module_data);
        if (array_key_exists('flag', $data) && $data['flag'] == 'max_delivery_charge') {
            Toastr::error(translate('Maximum delivery charge must be greater than minimum delivery charge.'));
            return back();
        }
        $this->zoneRepo->zoneModuleSetupUpdate(id: $id ,data: $this->zoneService->getZoneModuleSetupData(request: $request),moduleData: $request->module_data);

        Toastr::success(translate('messages.zone_module_updated_successfully'));
        return redirect()->route('admin.business-settings.zone.home');
    }

    public function getInstruction(): View
    {
        session()->put('zone-instruction', 1);
        $zones = $this->zoneRepo->getWithCountLatest(
            relations: ['stores','deliverymen'],
            dataLimit: config('default_pagination')
        );
        return view(ZoneViewPath::INDEX[VIEW], compact('zones'));
    }

    public function updateStatus(Request $request): RedirectResponse
    {
        if(env('APP_MODE')=='demo' && $request['id'] == 1)
        {
            Toastr::warning('Sorry!You can not inactive this zone!');
            return back();
        }
        $this->zoneRepo->update(id: $request['id'], data: ['status' => $request['status']]);
        Toastr::success(translate('messages.zone_status_updated'));
        return back();
    }

    public function updateDigitalPayment(Request $request): RedirectResponse
    {
        $this->zoneRepo->update(id: $request['id'] ,data: ['digital_payment' => $request['digital_payment']]);
        Toastr::success(translate('messages.zone_digital_payment_status_updated'));
        return back();
    }

    public function updateCashOnDelivery(Request $request): RedirectResponse
    {
        $this->zoneRepo->update(id: $request['id'] ,data: ['cash_on_delivery' => $request['cash_on_delivery']]);
        Toastr::success(translate('messages.zone_cash_on_delivery_status_updated'));
        return back();
    }

    public function updateOfflinePayment(Request $request): RedirectResponse
    {
        $this->zoneRepo->update(id: $request['id'] ,data: ['offline_payment' => $request['offline_payment']]);
        Toastr::success(translate('messages.zone_offline_payment_status_updated'));
        return back();
    }

    public function getCoordinates($id): JsonResponse
    {
        $zone = $this->zoneRepo->getWithCoordinateWhere(
            params: ['id'=> $id]
        );
        $area = json_decode($zone['coordinates'][0]->toJson(),true);
        $data = $this->zoneService->formatCoordinates(coordinates: $area['coordinates']);
        $center = (object)['lat'=>(float)trim(explode(' ',$zone['center'])[1], 'POINT()'), 'lng'=>(float)trim(explode(' ',$zone['center'])[0], 'POINT()')];
        return response()->json(['coordinates'=>$data, 'center'=>$center]);
    }

    public function getAllZoneCoordinates($id = 0): JsonResponse
    {
        $zones = $this->zoneRepo->getActiveListExcept(
            params: ['id'=> $id]
        );

        $data = $this->zoneService->formatZoneCoordinates(zones: $zones);

        return response()->json($data);
    }

}
