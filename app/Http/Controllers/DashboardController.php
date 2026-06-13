<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $date = $request->query('date', now()->format('Y-m-d'));

        // Basic metrics calculation
        $locations = Location::where('is_active', true)
            ->withCount(['assignments as total_assignments' => function ($q) use ($date) {
                $q->where('assignment_date', $date)->where('status', 'active');
            }])
            ->withCount(['assignments as present_assignments' => function ($q) use ($date) {
                $q->where('assignment_date', $date)->where('status', 'active')
                    ->whereHas('attendances');
            }])
            ->get();

        $metrics = [
            'total_locations' => $locations->count(),
            'full_attendance' => 0,
            'partial_attendance' => 0,
            'missing_attendance' => 0,
        ];

        foreach ($locations as $loc) {
            if ($loc->total_assignments > 0) {
                if ($loc->present_assignments >= $loc->minimum_officer) {
                    $metrics['full_attendance']++;
                } elseif ($loc->present_assignments > 0) {
                    $metrics['partial_attendance']++;
                } else {
                    $metrics['missing_attendance']++;
                }
            } else {
                // If no assignments, we might just not count it in missing or partial.
                // It just sits there. Or we could count it as missing. Let's not count.
            }
        }

        return view('dashboard.index', compact('metrics', 'date'));
    }

    public function mapData(Request $request): JsonResponse
    {
        $date = $request->query('date', now()->format('Y-m-d'));

        $cacheKey = "dashboard:map:{$date}";
        $ttl = (int) config('policehazard.cache.map_points_ttl_seconds', 30);

        $loader = function () use ($date) {
            return Location::select(
                'id', 'name', 'address', 'minimum_officer',
                DB::raw('ST_Y(coordinates::geometry) as lat'),
                DB::raw('ST_X(coordinates::geometry) as lng')
            )
                ->where('is_active', true)
                ->withCount(['assignments as total_assignments' => function ($q) use ($date) {
                    $q->where('assignment_date', $date)->where('status', 'active');
                }])
                ->withCount(['assignments as present_assignments' => function ($q) use ($date) {
                    $q->where('assignment_date', $date)->where('status', 'active')
                        ->whereHas('attendances');
                }])
                ->get()
                ->map(function ($loc) {
                    $status = 'no_assignment';
                    if ($loc->total_assignments > 0) {
                        if ($loc->present_assignments >= $loc->minimum_officer) {
                            $status = 'full';
                        } elseif ($loc->present_assignments > 0) {
                            $status = 'partial';
                        } else {
                            $status = 'missing';
                        }
                    }

                    return [
                        'id' => $loc->id,
                        'name' => $loc->name,
                        'lat' => $loc->lat,
                        'lng' => $loc->lng,
                        'status' => $status,
                        'total' => $loc->total_assignments,
                        'present' => $loc->present_assignments,
                        'min' => $loc->minimum_officer,
                    ];
                });
        };

        // Use tagged cache when the active store supports it (Redis); otherwise
        // fall back to the plain remember() call. Either path satisfies R9.1.
        try {
            $locations = Cache::tags(['dashboard'])->remember($cacheKey, $ttl, $loader);
        } catch (\BadMethodCallException) {
            $locations = Cache::remember($cacheKey, $ttl, $loader);
        }

        return response()->json($locations);
    }
}
