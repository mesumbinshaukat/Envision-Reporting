<?php

namespace App\Http\Controllers;

use App\Models\OfficeClosure;
use App\Services\OfficeScheduleService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OfficeClosureController extends Controller
{
    public function index(Request $request, OfficeScheduleService $scheduleService)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $closures = $user->officeClosures()
            ->latest('start_date')
            ->paginate(12);

        $schedule = $scheduleService->getSchedule($user);

        return view('admin.attendance.closures.index', [
            'closures' => $closures,
            'schedule' => $schedule,
        ]);
    }

    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $user->officeClosures()->create([
            'start_date' => Carbon::parse($validated['start_date'])->toDateString(),
            'end_date' => isset($validated['end_date']) ? Carbon::parse($validated['end_date'])->toDateString() : null,
            'reason' => $validated['reason'] ?? null,
        ]);

        return redirect()
            ->route('admin.attendance.closures.index')
            ->with('success', 'Office closure added successfully.');
    }

    public function destroy(OfficeClosure $closure)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        abort_unless($closure->user_id === $user->id, 403);

        $closure->delete();

        return redirect()
            ->route('admin.attendance.closures.index')
            ->with('success', 'Office closure deleted successfully.');
    }
}
