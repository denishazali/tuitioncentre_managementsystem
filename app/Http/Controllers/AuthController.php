<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            return back()
                ->withErrors([
                    'email' => 'The provided email or password is incorrect.',
                ])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        $user = $request->user();

        if($user->account_status === 'pending') {
            $this->logoutRejectedAccount($request);

            return to_route('login')
                ->withErrors([
                    'email' => 'Your account is pending approval.',
                ])
                ->withInput($request->only('email'));
        }

        if($user->account_status === 'inactive') {
                $this->logoutRejectedAccount($request);

                return to_route('login')
                    ->withErrors([
                        'email' => 'Your account is inactive. Please contact an administrator.',
                    ])
                    ->withInput($request->only('email'));
            }

            return $this->redirectToDashboard($user);
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request):RedirectResponse
        {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required','confirmed', 'min:8'],
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'parent',
                'account_status' => 'active',
            ]);

            Auth::login($user);
            $request->session()->regenerate();

            return to_route('parent.dashboard')
                ->with('success', 'Your parent account has been created.');
        }

        public function logout(Request $request):RedirectResponse
        {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return to_route('login')
                ->with('success', 'You have been logged out.');
        }

        private function logoutRejectedAccount(Request $request): void
        {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        private function redirectToDashboard(User $user):RedirectResponse
        {
            return match ($user->role) {
                'admin' => to_route('admin.dashboard'),
                'parent' => to_route('parent.dashboard'),
                'student' => to_route('student.dashboard'),
                default => abort(403, 'Your account does not have a valid role.'),
            };
        }
}