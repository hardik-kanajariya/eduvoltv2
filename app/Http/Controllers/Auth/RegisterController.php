<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RegisterController extends Controller
{
    /**
     * Display the registration view.
     */
    public function showRegistrationForm(): View
    {
        // Get available roles for registration (excluding super admin roles)
        $roles = Role::whereNotIn('name', ['super_admin', 'admin'])
                    ->orderBy('name')
                    ->get();

        return view('auth.register', [
            'roles' => $roles
        ]);
    }

    /**
     * Handle an incoming registration request.
     */
    public function register(RegisterRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Assign the selected role
        $role = Role::find($request->role_id);
        if ($role && !in_array($role->name, ['super_admin', 'admin'])) {
            $user->assignRole($role);
        } else {
            // Default to student role if no valid role selected
            $studentRole = Role::where('name', 'student')->first();
            if ($studentRole) {
                $user->assignRole($studentRole);
            }
        }

        event(new Registered($user));

        Auth::login($user);

        return redirect('/dashboard');
    }
}