<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserFeaturePermission;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\Bonus;
use App\Models\SalaryRelease;
use App\Models\Currency;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class DiagnoseUserAccess extends Command
{
    protected $signature = 'user:diagnose-access {email : User email} {--password= : Optional plaintext password to verify}';

    protected $description = 'Diagnose moderator/supervisor tenancy + feature permissions + data visibility.';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $user = User::where('email', $email)->with('featurePermissions')->first();

        if (!$user) {
            $this->error("No web user found for {$email}");
            return self::FAILURE;
        }

        $this->line('=== User ===');
        $this->line("id: {$user->id}");
        $this->line("name: {$user->name}");
        $this->line("email: {$user->email}");
        $this->line("role: " . ($user->role ?? 'admin'));
        $this->line("admin_id: " . ($user->admin_id ?? 'null'));
        $this->line("tenantId(): {$user->tenantId()}");

        $plain = (string) $this->option('password');
        if ($plain !== '') {
            $this->line('password_ok: ' . (Hash::check($plain, $user->password) ? 'true' : 'false'));
        }

        $this->newLine();
        $this->line('=== Feature permissions (stored rows) ===');
        if ($user->featurePermissions->isEmpty()) {
            $this->line('(none)');
        } else {
            foreach ($user->featurePermissions->sortBy('feature_key') as $perm) {
                $this->line(sprintf(
                    '- %s: read=%s write=%s',
                    $perm->feature_key,
                    $perm->can_read ? '1' : '0',
                    $perm->can_write ? '1' : '0'
                ));
            }
        }

        $this->newLine();
        $this->line('=== Effective access (registry) ===');
        $registry = array_keys(config('features.features', []));
        foreach ($registry as $featureKey) {
            $this->line(sprintf(
                '- %s: canRead=%s canWrite=%s',
                $featureKey,
                $user->canReadFeature($featureKey) ? '1' : '0',
                $user->canWriteFeature($featureKey) ? '1' : '0'
            ));
        }

        $tenantId = $user->tenantId();

        $this->newLine();
        $this->line('=== Data counts for tenant ===');
        $this->line('clients: ' . Client::where('user_id', $tenantId)->count());
        $this->line('invoices: ' . Invoice::where('user_id', $tenantId)->count());
        $this->line('employees: ' . Employee::where('user_id', $tenantId)->count());
        $this->line('expenses: ' . Expense::where('user_id', $tenantId)->count());
        $this->line('bonuses: ' . Bonus::where('user_id', $tenantId)->count());
        $this->line('salary_releases: ' . SalaryRelease::where('user_id', $tenantId)->count());
        $this->line('currencies: ' . Currency::where('user_id', $tenantId)->count());

        $this->newLine();
        $this->line('Done.');

        return self::SUCCESS;
    }
}

