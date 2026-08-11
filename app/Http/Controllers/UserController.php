<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $query = User::query();

        if ($user->isSuperAdmin()) {
            $companies = Company::orderBy('name')->get();
        } else {
            $companies = collect();
            $query->where('company_id', $user->company_id);
        }

        $users = $query->with('company')->get()->filter(function ($item) use ($user) {
            return $item->id != $user->id;
        });

        return view('users.index', compact('users', 'companies'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->isSuperAdmin()
            ? $request->input('company_id')
            : $user->company_id;

        if ($user->isSuperAdmin()) {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email',
                'password' => 'required|string|min:4',
                'role' => 'required|in:company_admin',
                'company_id' => 'required|integer|exists:companies,id',
            ]);
        } else {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email',
                'password' => 'required|string|min:4',
                'role' => 'required|in:company_admin,viewer',
            ]);
        }

        $isAdmin = $request->role === User::ROLE_SUPER_ADMIN;

        try {
            User::create([
                'name' => $request->name,
                'email' => $request->email,
                'force_to_change_password' => true,
                'password' => Hash::make($request->password),
                'is_admin' => $isAdmin,
                'role' => $request->role,
                'company_id' => $request->role === User::ROLE_SUPER_ADMIN ? null : $companyId,
            ]);

            return redirect()->route('users.index')->with('success', 'User created successfully');
        } catch (\Exception $e) {
            return redirect()->route('users.index')->with('failed', 'Failed to create user');
        }
    }

    public function toggleActive($id)
    {
        $user = User::find($id);

        if (!$user) {
            return redirect()->route('users.index')->with('failed', 'User not found');
        }

        if ($user->id === Auth::user()->id) {
            return redirect()->route('users.index')->with('failed', 'You cannot disable your own account');
        }

        if (!Auth::user()->isSuperAdmin() && $user->company_id !== Auth::user()->company_id) {
            return redirect()->route('users.index')->with('failed', 'Failed to update user');
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $action = $user->is_active ? 'enabled' : 'disabled';

        return redirect()->route('users.index')->with('success', "User {$action} successfully");
    }

    public function resetPassword(Request $request, $id)
    {
        $request->validate([
            'password' => 'required|string|min:4|confirmed',
        ]);

        $user = User::find($id);

        if (!$user) {
            return redirect()->route('users.index')->with('failed', 'User not found');
        }

        if (!Auth::user()->isSuperAdmin() && $user->company_id !== Auth::user()->company_id) {
            return redirect()->route('users.index')->with('failed', 'Failed to reset password');
        }

        try {
            $user->password = Hash::make($request->password);
            $user->force_to_change_password = true;
            $user->save();

            return redirect()->route('users.index')->with('success', 'Password reset successfully. The user must change it on next login.');
        } catch (\Exception $e) {
            return redirect()->route('users.index')->with('failed', 'Failed to reset password');
        }
    }

    public function showChangePasswordForm()
    {
        return view('auth.passwords.change');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:4|confirmed',
        ]);

        try {
            $user = Auth::user();
            $user->password = Hash::make($request->password);
            $user->force_to_change_password = false;
            $user->save();
            $user = auth()->user();
            auth()->logout();
            return redirect()->route('login')->with('success', 'Password changed successfully.');
        } catch (\Exception $e) {
            dd($e);
            return redirect()->route('password.change')->with('failed', 'Failed to change password');
        }
    }
}
