<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    /**
     * Disabled accounts (is_active = false) and users whose company is not
     * active (disabled or under review) must not be able to log in.
     */
    protected function attemptLogin(Request $request)
    {
        $username = $this->username();

        $user = User::where($username, $request->input($username))
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('company_id')
                    ->orWhereHas('company', function ($company) {
                        $company->where('status', Company::STATUS_ACTIVE);
                    });
            })
            ->first();

        if ($user && Hash::check($request->password, $user->password)) {
            $this->guard()->login($user, $request->boolean('remember'));

            return true;
        }

        return false;
    }
}
