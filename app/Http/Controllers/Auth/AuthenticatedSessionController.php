<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    public static function redirectToLogin(): RedirectResponse {
        $loginServer = env('LOGIN_SERVER');
        $server = env('CURRENT_SERVER_NAME');

        return response()->redirectTo("$loginServer/login?server=$server");
    }

    public static function redirectToHome(): RedirectResponse {
        return response()->redirectToRoute('dashboard');
    }

    public function login(Request $request): RedirectResponse
    {
        $token = $request->get('token');

        if (!$token) {
            return self::redirectToLogin();
        }

        $loginServer = env('LOGIN_SERVER');
        $server = env('CURRENT_SERVER_NAME');

        try {
            $response = json_decode(
                file_get_contents("$loginServer/validate-token?server=$server&token=$token"),
                true
            );

            if (!$response['valid']) {
                return self::redirectToLogin();
            }

            $userData = $response['user'];

            $user = User::find($userData['id']);

            if (!$user) {
                return self::redirectToLogin();
            }

            Auth::login($user);

            return self::redirectToHome();
        } catch (Exception $e) {
            return self::redirectToLogin();
        }
    }


    /**
     * Destroy an authenticated session.
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
