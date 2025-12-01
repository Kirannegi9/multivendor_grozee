<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Zone;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ZoneController extends Controller
{
    public function get_zones()
    {
        $zones= Zone::where('status',1)->get();
        foreach($zones as $zone){
            $area = json_decode($zone->coordinates[0]->toJson(),true);
            $zone['formated_coordinates']=Helpers::format_coordiantes($area['coordinates']);
        }
        return response()->json($zones, 200);
    }

    // public function zonesCheck(Request $request){
    //     $validator = Validator::make($request->all(), [
    //         'lat' => 'required',
    //         'lng' => 'required',
    //         'zone_id' => 'required',
    //     ]);

    //     if ($validator->errors()->count() > 0) {
    //         return response()->json(['errors' => Helpers::error_processor($validator)], 403);
    //     }
    //     $zone = Zone::where('id',$request->zone_id)->whereContains('coordinates', new Point($request->lat, $request->lng, POINT_SRID))->exists();

    //     return response()->json($zone, 200);

    // }


public function zonesCheck(Request $request)
{
    $validator = Validator::make($request->all(), [
        'lat' => 'required|numeric',
        'lng' => 'required|numeric',
        'zone_id' => 'required|integer',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => Helpers::error_processor($validator)], 403);
    }

    try {
        $latitude = $request->lat;
        $longitude = $request->lng;
        $zoneId = $request->zone_id;

        // Check if the zone contains the given point
        $zoneExists = DB::select("
            SELECT EXISTS(
                SELECT * FROM zones
                WHERE id = ? AND ST_CONTAINS(coordinates, ST_GeomFromText(?, 4326))
            ) AS zoneExists
        ", [$zoneId, "POINT($longitude $latitude)"]);

        return response()->json(['exists' => (bool) $zoneExists[0]->zoneExists], 200);
    } catch (\Exception $e) {
        return response()->json([
            'errors' => [['code' => 'server_error', 'message' => translate('messages.something_went_wrong')]]
        ], 500);
    }
}

}
