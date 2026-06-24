<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Location;
use App\Models\Operation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $date = $request->query('date', now()->format('Y-m-d'));
        $operationId = $request->query('operation_id');
        $zoneId = $request->query('zone_id');
        $officer = $request->query('officer');

        $query = Location::where('is_active', true);

        // Filter by zone
        if ($zoneId) {
            $query->where('zone_id', $zoneId);
        }

        // Filter by operation (locations whose zone belongs to that operation)
        if ($operationId && ! $zoneId) {
            $query->whereHas('zone', fn ($q) => $q->where('operation_id', $operationId));
        }

        // Filter by officer – only return locations that have an active assignment
        // for the searched officer, rather than returning all locations.
        if ($officer) {
            $query->whereHas('assignments', function ($q) use ($date, $officer) {
                $q->where('start_date', '<=', $date)
                    ->where(fn ($sq) => $sq->whereNull('end_date')->orWhere('end_date', '>=', $date))
                    ->where('status', 'active')
                    ->whereHas('officer', function ($oq) use ($officer) {
                        $oq->withoutGlobalScopes()
                            ->where('name', 'ilike', "%{$officer}%")
                            ->orWhere('nrp', 'ilike', "%{$officer}%");
                    });
            });
        }

        // Build assignment constraint closure
        $assignmentFilter = function ($q) use ($date, $officer) {
            $q->where('start_date', '<=', $date)
                ->where(fn ($sq) => $sq->whereNull('end_date')->orWhere('end_date', '>=', $date))
                ->where('status', 'active');
            if ($officer) {
                $q->whereHas('officer', function ($oq) use ($officer) {
                    $oq->withoutGlobalScopes()
                        ->where('name', 'ilike', "%{$officer}%")
                        ->orWhere('nrp', 'ilike', "%{$officer}%");
                });
            }
        };

        $locations = $query
            ->withCount(['assignments as total_assignments' => $assignmentFilter])
            ->withCount(['assignments as present_assignments' => function ($q) use ($date, $officer) {
                $q->where('start_date', '<=', $date)
                    ->where(fn ($sq) => $sq->whereNull('end_date')->orWhere('end_date', '>=', $date))
                    ->where('status', 'active')
                    ->whereHas('attendances', fn ($aq) => $aq->whereDate('checked_in_at', $date));
                if ($officer) {
                    $q->whereHas('officer', function ($oq) use ($officer) {
                        $oq->withoutGlobalScopes()
                            ->where('name', 'ilike', "%{$officer}%")
                            ->orWhere('nrp', 'ilike', "%{$officer}%");
                    });
                }
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
            }
        }

        // Fetch operations for the dropdown
        $operations = Operation::select('id', 'name')->orderBy('name')->get();

        return view('dashboard.index', compact('metrics', 'date', 'operations'));
    }

    public function mapData(Request $request): JsonResponse
    {
        $date = $request->query('date', now()->format('Y-m-d'));
        $operationId = $request->query('operation_id');
        $zoneId = $request->query('zone_id');
        $officer = $request->query('officer');

        // Build a unique cache key incorporating all filters
        $cacheKey = 'dashboard:map:' . md5(json_encode(compact('date', 'operationId', 'zoneId', 'officer')));
        $ttl = (int) config('policehazard.cache.map_points_ttl_seconds', 30);

        $loader = function () use ($date, $operationId, $zoneId, $officer) {
            $query = Location::select(
                'id', 'name', 'address', 'minimum_officer', 'zone_id',
                DB::raw('ST_Y(coordinates::geometry) as lat'),
                DB::raw('ST_X(coordinates::geometry) as lng')
            )
                ->where('is_active', true);

            // Filter by zone
            if ($zoneId) {
                $query->where('zone_id', $zoneId);
            }

            // Filter by operation (locations whose zone belongs to that operation)
            if ($operationId && ! $zoneId) {
                $query->whereHas('zone', fn ($q) => $q->where('operation_id', $operationId));
            }

            // Filter by officer – only return locations that have an active assignment
            // for the searched officer, rather than returning all locations.
            if ($officer) {
                $query->whereHas('assignments', function ($q) use ($date, $officer) {
                    $q->where('start_date', '<=', $date)
                        ->where(fn ($sq) => $sq->whereNull('end_date')->orWhere('end_date', '>=', $date))
                        ->where('status', 'active')
                        ->whereHas('officer', function ($oq) use ($officer) {
                            $oq->withoutGlobalScopes()
                                ->where('name', 'ilike', "%{$officer}%")
                                ->orWhere('nrp', 'ilike', "%{$officer}%");
                        });
                });
            }

            // Build assignment constraint
            $assignmentFilter = function ($q) use ($date, $officer) {
                $q->where('start_date', '<=', $date)
                    ->where(fn ($sq) => $sq->whereNull('end_date')->orWhere('end_date', '>=', $date))
                    ->where('status', 'active');
                if ($officer) {
                    $q->whereHas('officer', function ($oq) use ($officer) {
                        $oq->withoutGlobalScopes()
                            ->where('name', 'ilike', "%{$officer}%")
                            ->orWhere('nrp', 'ilike', "%{$officer}%");
                    });
                }
            };

            return $query
                ->withCount(['assignments as total_assignments' => $assignmentFilter])
                ->withCount(['assignments as present_assignments' => function ($q) use ($date, $officer) {
                    $q->where('start_date', '<=', $date)
                        ->where(fn ($sq) => $sq->whereNull('end_date')->orWhere('end_date', '>=', $date))
                        ->where('status', 'active')
                        ->whereHas('attendances', fn ($aq) => $aq->whereDate('checked_in_at', $date));
                    if ($officer) {
                        $q->whereHas('officer', function ($oq) use ($officer) {
                            $oq->withoutGlobalScopes()
                                ->where('name', 'ilike', "%{$officer}%")
                                ->orWhere('nrp', 'ilike', "%{$officer}%");
                        });
                    }
                }])
                ->with(['assignments' => function ($q) use ($date, $officer) {
                    $q->where('start_date', '<=', $date)
                        ->where(fn ($sq) => $sq->whereNull('end_date')->orWhere('end_date', '>=', $date))
                        ->where('status', 'active')
                        ->with(['officer', 'attendances' => function ($aq) use ($date) {
                            $aq->whereDate('checked_in_at', $date);
                        }]);
                    if ($officer) {
                        $q->whereHas('officer', function ($oq) use ($officer) {
                            $oq->withoutGlobalScopes()
                                ->where('name', 'ilike', "%{$officer}%")
                                ->orWhere('nrp', 'ilike', "%{$officer}%");
                        });
                    }
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

                    $officersList = $loc->assignments->map(function($a) {
                        $att = $a->attendances->first();
                        return [
                            'name' => $a->officer->name,
                            'attendance_id' => $att?->id,
                            'photo_status' => $att?->photo_status,
                            'time' => $att?->checked_in_at?->format('H:i')
                        ];
                    })->values();

                    return [
                        'id' => $loc->id,
                        'name' => $loc->name,
                        'lat' => $loc->lat,
                        'lng' => $loc->lng,
                        'status' => $status,
                        'total' => $loc->total_assignments,
                        'present' => $loc->present_assignments,
                        'min' => $loc->minimum_officer,
                        'officers' => $officersList,
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

    public function photo(Attendance $attendance)
    {
        if (! $attendance->photo_path) {
            abort(404, 'Photo not found');
        }

        $disk = config('policehazard.photo.s3_disk', 's3');
        try {
            $exists = Storage::disk($disk)->exists($attendance->photo_path);
        } catch (\League\Flysystem\UnableToCheckExistence $e) {
            $exists = false;
        }

        if (! $exists) {
            // Fallback for local testing (maybe it's still raw)
            if ($attendance->photo_raw_path && Storage::disk(config('policehazard.photo.private_disk', 'local'))->exists($attendance->photo_raw_path)) {
                return Storage::disk(config('policehazard.photo.private_disk', 'local'))->response($attendance->photo_raw_path);
            }
            abort(404, 'Photo not found on disk');
        }

        return Storage::disk($disk)->response($attendance->photo_path);
    }
}
