<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('admin.activity.logs', function ($user) {
    if (!$user) {
        return false;
    }

    if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
        return ['id' => $user->id, 'name' => $user->name];
    }

    // Support employee guard users resolved via Broadcast::routes()
    if (method_exists($user, 'admin_id') && $user->admin_id) {
        // Allow employee to listen only if needed (mostly for debugging)
        return [
            'id' => $user->id,
            'name' => $user->name,
            'admin_id' => $user->admin_id,
        ];
    }

    return false;
});
