<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Traits\ApiPagination;
use App\Models\OfficeClosure;
use App\Models\EmployeeUser;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OfficeClosureApiController extends BaseApiController
{
    use ApiPagination;

    /**
     * Display a listing of office closures
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Get admin user based on token type
        if ($user->tokenCan('admin')) {
            $admin = $user;
        } elseif ($user->tokenCan('employee')) {
            // Employee user - get their admin
            $employeeUser = EmployeeUser::with('employee.user')->find($user->id);
            
            if (!$employeeUser || !$employeeUser->employee || !$employeeUser->employee->user) {
                return $this->error('Unable to retrieve admin settings', 404);
            }
            
            $admin = $employeeUser->employee->user;
        } else {
            return $this->forbidden('Invalid token permissions');
        }

        $query = $admin->officeClosures()->latest('start_date');

        // Filter by upcoming/active closures only
        if ($request->has('active_only') && $request->active_only) {
            $today = Carbon::today();
            $query->where(function ($q) use ($today) {
                $q->where('start_date', '>=', $today)
                    ->orWhere(function ($inner) use ($today) {
                        $inner->whereNotNull('end_date')
                            ->where('end_date', '>=', $today);
                    });
            });
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('start_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where(function ($q) use ($request) {
                $q->whereDate('start_date', '<=', $request->date_to)
                    ->orWhere(function ($inner) use ($request) {
                        $inner->whereNotNull('end_date')
                            ->whereDate('end_date', '<=', $request->date_to);
                    });
            });
        }

        $closures = $this->applyPagination($query);

        return $this->paginated($closures, function ($closure) {
            return [
                'id' => $closure->id,
                'start_date' => $closure->start_date?->toDateString(),
                'end_date' => $closure->end_date?->toDateString(),
                'reason' => $closure->reason,
                'is_single_day' => is_null($closure->end_date) || $closure->start_date->eq($closure->end_date),
                'is_active' => $this->isClosureActive($closure),
                'created_at' => $closure->created_at?->toISOString(),
                'updated_at' => $closure->updated_at?->toISOString(),
            ];
        });
    }

    /**
     * Store a newly created office closure
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        if (!$request->user()->tokenCan('admin')) {
            return $this->forbidden('Only admin users can create office closures');
        }

        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $closure = $user->officeClosures()->create([
            'start_date' => Carbon::parse($validated['start_date'])->toDateString(),
            'end_date' => isset($validated['end_date']) ? Carbon::parse($validated['end_date'])->toDateString() : null,
            'reason' => $validated['reason'] ?? null,
        ]);

        return $this->created([
            'id' => $closure->id,
            'start_date' => $closure->start_date?->toDateString(),
            'end_date' => $closure->end_date?->toDateString(),
            'reason' => $closure->reason,
            'is_single_day' => is_null($closure->end_date),
            'is_active' => $this->isClosureActive($closure),
            'created_at' => $closure->created_at?->toISOString(),
            'updated_at' => $closure->updated_at?->toISOString(),
        ], 'Office closure created successfully');
    }

    /**
     * Display the specified office closure
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        
        // Get admin user based on token type
        if ($user->tokenCan('admin')) {
            $admin = $user;
        } elseif ($user->tokenCan('employee')) {
            $employeeUser = EmployeeUser::with('employee.user')->find($user->id);
            
            if (!$employeeUser || !$employeeUser->employee || !$employeeUser->employee->user) {
                return $this->error('Unable to retrieve admin settings', 404);
            }
            
            $admin = $employeeUser->employee->user;
        } else {
            return $this->forbidden('Invalid token permissions');
        }

        $closure = $admin->officeClosures()->find($id);

        if (!$closure) {
            return $this->notFound('Office closure not found');
        }

        return $this->success([
            'id' => $closure->id,
            'start_date' => $closure->start_date?->toDateString(),
            'end_date' => $closure->end_date?->toDateString(),
            'reason' => $closure->reason,
            'is_single_day' => is_null($closure->end_date) || $closure->start_date->eq($closure->end_date),
            'is_active' => $this->isClosureActive($closure),
            'created_at' => $closure->created_at?->toISOString(),
            'updated_at' => $closure->updated_at?->toISOString(),
        ], 'Office closure retrieved successfully');
    }

    /**
     * Remove the specified office closure
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        if (!$request->user()->tokenCan('admin')) {
            return $this->forbidden('Only admin users can delete office closures');
        }

        $user = $request->user();
        $closure = $user->officeClosures()->find($id);

        if (!$closure) {
            return $this->notFound('Office closure not found');
        }

        $closure->delete();

        return $this->success(null, 'Office closure deleted successfully');
    }

    /**
     * Check if closure is currently active
     *
     * @param OfficeClosure $closure
     * @return bool
     */
    protected function isClosureActive(OfficeClosure $closure): bool
    {
        $today = Carbon::today();
        
        if ($closure->end_date) {
            return $today->between($closure->start_date, $closure->end_date);
        }
        
        return $today->eq($closure->start_date);
    }
}
