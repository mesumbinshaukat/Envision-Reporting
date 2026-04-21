<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserFeaturePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserManagementController extends Controller
{
    public function index()
    {
        $tenantId = auth()->user()->tenantId();

        $users = User::query()
            ->where('admin_id', $tenantId)
            ->whereIn('role', ['moderator', 'supervisor'])
            ->orderBy('role')
            ->orderBy('name')
            ->paginate(10);

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $features = config('features.features', []);

        $grouped = collect($features)
            ->map(function ($meta, $key) {
                return [
                    'key' => $key,
                    'label' => $meta['label'] ?? $key,
                    'group' => $meta['group'] ?? 'Other',
                ];
            })
            ->groupBy('group')
            ->sortKeys();

        $roles = [
            'moderator' => 'Moderator',
            'supervisor' => 'Supervisor',
        ];

        return view('admin.users.create', [
            'groupedFeatures' => $grouped,
            'roles' => $roles,
        ]);
    }

    public function show(User $user)
    {
        $this->assertManageableUser($user);

        $user->load('featurePermissions');

        $features = config('features.features', []);
        $permissions = $user->featurePermissions
            ->keyBy('feature_key')
            ->map(fn (UserFeaturePermission $p) => ['read' => (bool) $p->can_read, 'write' => (bool) $p->can_write])
            ->toArray();

        $grouped = collect($features)
            ->map(function ($meta, $key) use ($permissions) {
                return [
                    'key' => $key,
                    'label' => $meta['label'] ?? $key,
                    'group' => $meta['group'] ?? 'Other',
                    'can_read' => (bool) (($permissions[$key]['read'] ?? false) || ($permissions[$key]['write'] ?? false)),
                    'can_write' => (bool) ($permissions[$key]['write'] ?? false),
                ];
            })
            ->groupBy('group')
            ->sortKeys();

        return view('admin.users.show', [
            'managedUser' => $user,
            'groupedFeatures' => $grouped,
        ]);
    }

    public function edit(User $user)
    {
        $this->assertManageableUser($user);

        $features = config('features.features', []);

        $permissions = $user->featurePermissions()
            ->get()
            ->keyBy('feature_key');

        $grouped = collect($features)
            ->map(function ($meta, $key) use ($permissions) {
                $perm = $permissions->get($key);

                return [
                    'key' => $key,
                    'label' => $meta['label'] ?? $key,
                    'group' => $meta['group'] ?? 'Other',
                    'can_read' => (bool) optional($perm)->can_read,
                    'can_write' => (bool) optional($perm)->can_write,
                ];
            })
            ->groupBy('group')
            ->sortKeys();

        $roles = [
            'moderator' => 'Moderator',
            'supervisor' => 'Supervisor',
        ];

        return view('admin.users.edit', [
            'managedUser' => $user,
            'groupedFeatures' => $grouped,
            'roles' => $roles,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(['moderator', 'supervisor'])],
            'permissions' => ['nullable', 'array'],
            'permissions.*.read' => ['nullable', 'boolean'],
            'permissions.*.write' => ['nullable', 'boolean'],
        ]);

        $featureRegistry = array_keys(config('features.features', []));
        $requested = $request->input('permissions', []);

        $rows = $this->normalizePermissions($requested, $featureRegistry);

        if (empty($rows)) {
            $defaults = config('features.role_defaults.' . $data['role'], []);
            $rows = $this->normalizePermissions($defaults, $featureRegistry);
        }

        if (empty($rows)) {
            throw ValidationException::withMessages([
                'permissions' => 'Please select at least one feature permission.',
            ]);
        }

        return DB::transaction(function () use ($data, $rows) {
            $user = User::create([
                'admin_id' => auth()->user()->tenantId(),
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => $data['role'],
            ]);

            foreach ($rows as $row) {
                UserFeaturePermission::create([
                    'user_id' => $user->id,
                    'feature_key' => $row['feature_key'],
                    'can_read' => $row['can_read'],
                    'can_write' => $row['can_write'],
                ]);
            }

            return redirect()
                ->route('admin.users.index')
                ->with('success', 'User created successfully.');
        });
    }

    public function update(Request $request, User $user)
    {
        $this->assertManageableUser($user);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class, 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(['moderator', 'supervisor'])],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'permissions' => ['nullable', 'array'],
            'permissions.*.read' => ['nullable', 'boolean'],
            'permissions.*.write' => ['nullable', 'boolean'],
        ]);

        $featureRegistry = array_keys(config('features.features', []));
        $requested = $request->input('permissions', []);
        $rows = $this->normalizePermissions($requested, $featureRegistry);

        if (empty($rows)) {
            $defaults = config('features.role_defaults.' . $data['role'], []);
            $rows = $this->normalizePermissions($defaults, $featureRegistry);
        }

        if (empty($rows)) {
            throw ValidationException::withMessages([
                'permissions' => 'Please select at least one feature permission.',
            ]);
        }

        return DB::transaction(function () use ($user, $data, $rows) {
            $update = [
                'name' => $data['name'],
                'email' => $data['email'],
                'role' => $data['role'],
            ];

            if (!empty($data['password'])) {
                $update['password'] = Hash::make($data['password']);
            }

            $user->update($update);

            UserFeaturePermission::where('user_id', $user->id)->delete();
            foreach ($rows as $row) {
                UserFeaturePermission::create([
                    'user_id' => $user->id,
                    'feature_key' => $row['feature_key'],
                    'can_read' => $row['can_read'],
                    'can_write' => $row['can_write'],
                ]);
            }

            return redirect()
                ->route('admin.users.index')
                ->with('success', 'User updated successfully.');
        });
    }

    public function destroy(User $user)
    {
        $this->assertManageableUser($user);

        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }

    protected function assertManageableUser(User $user): void
    {
        $tenantId = auth()->user()->tenantId();

        if ($user->role === 'admin') {
            abort(403);
        }

        if ((int) $user->admin_id !== (int) $tenantId) {
            abort(404);
        }
    }

    /**
     * @param array<string, mixed> $requested
     * @param array<int, string> $registryKeys
     * @return array<int, array{feature_key:string, can_read:bool, can_write:bool}>
     */
    protected function normalizePermissions(array $requested, array $registryKeys): array
    {
        $rows = [];

        foreach ($registryKeys as $key) {
            $payload = $requested[$key] ?? null;
            if (!is_array($payload)) {
                continue;
            }

            $canRead = filter_var($payload['read'] ?? false, FILTER_VALIDATE_BOOL);
            $canWrite = filter_var($payload['write'] ?? false, FILTER_VALIDATE_BOOL);

            if (!$canRead && !$canWrite) {
                continue;
            }

            $rows[] = [
                'feature_key' => $key,
                'can_read' => $canRead || $canWrite, // write implies read
                'can_write' => $canWrite,
            ];
        }

        return $rows;
    }
}

