<?php

namespace App\Services;

use MatanYadaev\EloquentSpatial\Objects\LineString;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Objects\Polygon;
use Illuminate\Support\Facades\DB;

class ZoneService
{

     public function getAddData(Object $request): array
{
    try {
        // ✅ Validate name before using
        if (!isset($request->name) || !is_array($request->name)) {
            throw new \Exception("Invalid name input. Expected an array.");
        }

        $langIndex = array_search('default', $request->lang);
        $zoneName = isset($request->name[$langIndex]) && !empty($request->name[$langIndex])
            ? $request->name[$langIndex]
            : 'Unnamed Zone';

        // ✅ Process polygon coordinates
        $value = $request['coordinates'];
        $polygonPoints = [];

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

        // ✅ Ensure `id` is not manually set
        $zoneId = DB::table('zones')->insertGetId([
            'name'                  => $zoneName,
            'coordinates'           => DB::raw("ST_GeomFromText('{$polygonWKT}', 4326)"),
            'store_wise_topic'      => '',
            'customer_wise_topic'   => '',
            'deliveryman_wise_topic'=> '',
            'cash_on_delivery'      => $request->cash_on_delivery ? 1 : 0,
            'digital_payment'       => $request->digital_payment ? 1 : 0,
            'updated_at'            => now(),
            'created_at'            => now(),
        ]);

        // ✅ Now update topics using correct `id`
        DB::table('zones')->where('id', $zoneId)->update([
            'store_wise_topic'      => "zone_{$zoneId}_store",
            'customer_wise_topic'   => "zone_{$zoneId}_customer",
            'deliveryman_wise_topic'=> "zone_{$zoneId}_delivery_man",
        ]);

        return ['id' => $zoneId];  // ✅ Only return `id` but don't pass `id` manually later
    } catch (\Exception $e) {
        return [];
    }
}
    public function getZoneModuleSetupData(Object $request): array
    {
        return [
            'cash_on_delivery' => $request->cash_on_delivery?1:0,
            'digital_payment' => $request->digital_payment?1:0,
            'offline_payment' => $request->offline_payment?1:0,
            'increased_delivery_fee' => $request->increased_delivery_fee ?? 0,
            'increased_delivery_fee_status' => $request->increased_delivery_fee_status ?? 0,
            'increase_delivery_charge_message' => $request->increase_delivery_charge_message ?? null,
        ];
    }

    public function formatCoordinates(array $coordinates): array
    {
        $data = [];
        foreach ($coordinates as $coordinate) {
            $data[] = (object)['lat' => $coordinate[1], 'lng' => $coordinate[0]];
        }
        return $data;
    }

    public function formatZoneCoordinates(object $zones): array
    {
        $data = [];
        foreach($zones as $zone)
        {
            $area = json_decode($zone->coordinates[0]->toJson(),true);
            $data[] = self::formatCoordinates(coordinates: $area['coordinates']);
        }
        return $data;
    }

    public function checkModuleDeliveryCharge(array $moduleData): array
    {
        foreach($moduleData as $data){
            if(isset($data['maximum_shipping_charge']) && ((int)$data['maximum_shipping_charge'] < (int)$data['minimum_shipping_charge'])){
                return ['flag' => 'max_delivery_charge'];
            }
        }
        return [];
    }

}
