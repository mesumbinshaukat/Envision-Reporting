<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Traits\HandlesAuthGuards;


class ProfileController extends Controller
{
    use HandlesAuthGuards;
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $this->getCurrentUser();

        return view('profile.edit', [
            'user' => $user,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $this->getCurrentUser();
        $validated = $request->validated();

        $removePhoto = (bool) ($validated['remove_profile_photo'] ?? false);
        unset($validated['remove_profile_photo']);

        if ($request->hasFile('profile_photo')) {
            $newPath = $request->file('profile_photo')->store('profile-photos', 'public');

            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }

            $user->profile_photo_path = $newPath;
        } elseif ($removePhoto && $user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
            $user->profile_photo_path = null;
        }

        if ($user->profile_photo_path && Str::startsWith($user->profile_photo_path, 'media/profile-photos/')) {
            $normalizedPath = Str::replaceFirst('media/profile-photos/', 'profile-photos/', $user->profile_photo_path);

            if (Storage::disk('public')->exists($user->profile_photo_path) && !Storage::disk('public')->exists($normalizedPath)) {
                Storage::disk('public')->move($user->profile_photo_path, $normalizedPath);
            }

            $user->profile_photo_path = $normalizedPath;
        }

        unset($validated['profile_photo']);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if ($this->isAdmin() && $user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($this->isEmployee()) {
            $employee = $user->employee;
            if ($employee) {
                $employee->name = $validated['name'];
                $employee->email = $validated['email'];
                $employee->save();
            }
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $guard = $this->isEmployee() ? 'employee' : 'web';

        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password:' . $guard],
        ]);

        $user = $this->getCurrentUser();

        if ($user && $user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        Auth::guard('web')->logout();
        Auth::guard('employee')->logout();

        if ($user) {
            $user->delete();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
