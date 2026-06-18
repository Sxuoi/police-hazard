<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HeatmapController extends Controller
{
    /**
     * Render the main Heatmap Index page.
     */
    public function index(): View
    {
        return view('heatmap.index');
    }

    /**
     * Fetch the multi-layer spatial data for the Heatmap.
     */
    public function data(Request $request): JsonResponse
    {
        // 1. Validation
        $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'saker_level' => ['nullable', 'string', 'in:MABES,POLDA,POLRESTABES,POLSEK'],
            'operation_type' => ['nullable', 'string', 'in:PH,PATROL'],
        ]);

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $sakerLevel = $request->input('saker_level');
        $operationType = $request->input('operation_type');

        // Enforce 90-day range limit
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        if ($start->diffInDays($end) > 90) {
            return response()->json([
                'message' => 'Rentang tanggal tidak boleh melebihi 90 hari.',
                'errors' => [
                    'end_date' => ['Rentang tanggal tidak boleh melebihi 90 hari.']
                ]
            ], 422);
        }

        // 2. Query Layer 1: Coverage (Choropleth by Zone)
        $coverageQuery = DB::table('zones as z')
            ->join('locations as l', 'l.zone_id', '=', 'z.id')
            ->join('sakers as s', 's.id', '=', 'z.saker_id')
            ->join('operations as o', 'o.id', '=', 'z.operation_id')
            ->leftJoin('daily_attendance_summary as das', function ($join) use ($startDate, $endDate) {
                $join->on('das.location_id', '=', 'l.id')
                    ->whereBetween('das.summary_date', [$startDate, $endDate]);
            });

        if ($sakerLevel) {
            $coverageQuery->where('s.type', $sakerLevel);
        }
        if ($operationType) {
            $coverageQuery->where('o.operation_type', $operationType);
        }

        $coverageData = $coverageQuery
            ->select(
                'z.id as zone_id',
                'z.name as zone_name',
                's.name as saker_name',
                DB::raw("COUNT(CASE WHEN das.day_status = 'attended' THEN 1 END) as attended_days"),
                DB::raw("COUNT(das.location_id) as total_days"),
                DB::raw("ST_AsGeoJSON(ST_Union(ST_Buffer(l.coordinates::geography, 300)::geometry)) as geojson")
            )
            ->groupBy('z.id', 'z.name', 's.name')
            ->get();

        $features = [];
        foreach ($coverageData as $zone) {
            if (!$zone->geojson) {
                continue;
            }

            $rate = $zone->total_days > 0 
                ? round(($zone->attended_days / $zone->total_days) * 100, 1) 
                : 100.0;

            $features[] = [
                'type' => 'Feature',
                'geometry' => json_decode($zone->geojson),
                'properties' => [
                    'zone_id' => $zone->zone_id,
                    'zone_name' => $zone->zone_name,
                    'saker_name' => $zone->saker_name,
                    'attendance_rate' => $rate,
                    'attended_days' => (int) $zone->attended_days,
                    'total_days' => (int) $zone->total_days,
                ]
            ];
        }

        $coverage = [
            'type' => 'FeatureCollection',
            'features' => $features
        ];

        // 3. Query Layer 2: Absences (Clustering for Leaflet.heat)
        $absencesQuery = DB::table('daily_attendance_summary as das')
            ->join('locations as l', 'l.id', '=', 'das.location_id')
            ->join('zones as z', 'z.id', '=', 'l.zone_id')
            ->join('sakers as s', 's.id', '=', 'l.saker_id')
            ->join('operations as o', 'o.id', '=', 'z.operation_id')
            ->where('das.day_status', 'not_attended')
            ->whereBetween('das.summary_date', [$startDate, $endDate]);

        if ($sakerLevel) {
            $absencesQuery->where('s.type', $sakerLevel);
        }
        if ($operationType) {
            $absencesQuery->where('o.operation_type', $operationType);
        }

        $absences = $absencesQuery
            ->select(
                DB::raw("ST_Y(l.coordinates::geometry) as lat"),
                DB::raw("ST_X(l.coordinates::geometry) as lng"),
                DB::raw("COUNT(*) as weight")
            )
            ->groupBy('l.id', 'l.coordinates')
            ->get()
            ->map(fn($item) => [
                (float) $item->lat,
                (float) $item->lng,
                (int) $item->weight
            ]);

        // 4. Query Layer 3: Spoofing (Point Markers)
        $spoofingQuery = DB::table('attendances as a')
            ->join('users as u', 'u.id', '=', 'a.officer_id')
            ->join('locations as l', 'l.id', '=', 'a.location_id')
            ->join('zones as z', 'z.id', '=', 'l.zone_id')
            ->join('sakers as s', 's.id', '=', 'a.saker_id')
            ->join('assignments as asn', 'asn.id', '=', 'a.assignment_id')
            ->join('operations as o', 'o.id', '=', 'asn.operation_id')
            ->where('a.spoofing_score', '>=', 1)
            ->whereBetween('a.checked_in_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        if ($sakerLevel) {
            $spoofingQuery->where('s.type', $sakerLevel);
        }
        if ($operationType) {
            $spoofingQuery->where('o.operation_type', $operationType);
        }

        $spoofing = $spoofingQuery
            ->select(
                'a.id',
                DB::raw("ST_Y(a.checkin_coordinates::geometry) as lat"),
                DB::raw("ST_X(a.checkin_coordinates::geometry) as lng"),
                'a.spoofing_score',
                'a.spoofing_signals',
                'a.checked_in_at',
                'u.name as officer_name',
                'u.nrp as officer_nrp',
                'l.name as location_name'
            )
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'lat' => (float) $item->lat,
                'lng' => (float) $item->lng,
                'spoofing_score' => (int) $item->spoofing_score,
                'spoofing_signals' => json_decode($item->spoofing_signals, true) ?: [],
                'checked_in_at' => Carbon::parse($item->checked_in_at)->toIso8601String(),
                'officer_name' => $item->officer_name,
                'officer_nrp' => $item->officer_nrp,
                'location_name' => $item->location_name,
            ]);

        // 5. Query Layer 4: Density (Officer Density count per location)
        $densityQuery = DB::table('assignments as asn')
            ->join('locations as l', 'l.id', '=', 'asn.location_id')
            ->join('zones as z', 'z.id', '=', 'l.zone_id')
            ->join('sakers as s', 's.id', '=', 'asn.saker_id')
            ->join('operations as o', 'o.id', '=', 'asn.operation_id')
            ->where('asn.start_date', '<=', $endDate)
            ->where(function ($query) use ($startDate) {
                $query->whereNull('asn.end_date')
                      ->orWhere('asn.end_date', '>=', $startDate);
            });

        if ($sakerLevel) {
            $densityQuery->where('s.type', $sakerLevel);
        }
        if ($operationType) {
            $densityQuery->where('o.operation_type', $operationType);
        }

        $density = $densityQuery
            ->select(
                'l.id as location_id',
                'l.name as location_name',
                DB::raw("ST_Y(l.coordinates::geometry) as lat"),
                DB::raw("ST_X(l.coordinates::geometry) as lng"),
                DB::raw("COUNT(asn.id) as assignment_count")
            )
            ->groupBy('l.id', 'l.name', 'l.coordinates')
            ->get()
            ->map(fn($item) => [
                'location_id' => $item->location_id,
                'location_name' => $item->location_name,
                'lat' => (float) $item->lat,
                'lng' => (float) $item->lng,
                'assignment_count' => (int) $item->assignment_count,
            ]);

        return response()->json([
            'coverage' => $coverage,
            'absences' => $absences,
            'spoofing' => $spoofing,
            'density' => $density,
        ]);
    }
}
