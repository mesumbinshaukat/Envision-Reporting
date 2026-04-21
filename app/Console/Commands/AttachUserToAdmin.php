<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class AttachUserToAdmin extends Command
{
    protected $signature = 'user:attach-admin {userEmail} {adminEmail}';

    protected $description = 'Attach an existing moderator/supervisor to an admin tenant (sets users.admin_id).';

    public function handle(): int
    {
        $userEmail = (string) $this->argument('userEmail');
        $adminEmail = (string) $this->argument('adminEmail');

        $user = User::where('email', $userEmail)->first();
        $admin = User::where('email', $adminEmail)->first();

        if (!$user) {
            $this->error("User not found: {$userEmail}");
            return self::FAILURE;
        }

        if (!$admin) {
            $this->error("Admin not found: {$adminEmail}");
            return self::FAILURE;
        }

        if (!$admin->isAdmin()) {
            $this->error("Target adminEmail is not role=admin: {$adminEmail}");
            return self::FAILURE;
        }

        if ($user->isAdmin()) {
            $this->error("Refusing: {$userEmail} is an admin.");
            return self::FAILURE;
        }

        $user->admin_id = $admin->id;
        $user->save();

        $this->info("Attached {$userEmail} to admin {$adminEmail} (admin_id={$admin->id}).");

        return self::SUCCESS;
    }
}

