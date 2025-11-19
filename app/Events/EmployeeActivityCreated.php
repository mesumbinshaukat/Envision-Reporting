<?php

namespace App\Events;

use App\Models\EmployeeActivityLog;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmployeeActivityCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public EmployeeActivityLog $log)
    {
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.activity.logs'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->log->id,
            'occurred_at' => optional($this->log->occurred_at)->toIso8601String(),
            'action' => $this->log->action,
            'category' => $this->log->category,
            'summary' => $this->log->summary,
            'description' => $this->log->description,
            'employee_user' => $this->log->employeeUser ? [
                'id' => $this->log->employeeUser->id,
                'name' => optional($this->log->employeeUser->employee)->name ?? $this->log->employeeUser->name,
            ] : null,
            'ip_address_v4' => $this->log->ip_address_v4,
            'ip_address_v6' => $this->log->ip_address_v6,
            'device_type' => $this->log->device_type,
            'browser' => $this->log->browser,
            'os' => $this->log->os,
        ];
    }
}
