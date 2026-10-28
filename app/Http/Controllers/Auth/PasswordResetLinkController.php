<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            Password::sendResetLink($request->only('email') + ['is_active' => true]);
        } catch (TransportExceptionInterface $e) {
            // Do not log the reset URL, token, SMTP credentials or recipient.
            Log::error('Password reset email could not be delivered.', ['exception' => get_class($e)]);

            return back()->withInput($request->only('email'))
                ->withErrors(['email' => __('passwords.unavailable')]);
        }

        // Identical response for unknown, disabled and recently requested accounts.
        return back()->with('status', __(Password::RESET_LINK_SENT));
    }
}
