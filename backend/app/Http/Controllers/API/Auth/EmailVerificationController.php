<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\UserResource;
use App\Services\EmailVerificationService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    use ApiResponse;

    public function __construct(private EmailVerificationService $verificationService) {}

    /**
     * POST /api/email/send-otp
     * Send or resend the 6-digit OTP to the authenticated user's email.
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $this->verificationService->send($request->user());

        return $this->success(null, 'Verification code sent to ' . $request->user()->email);
    }

    /**
     * POST /api/email/verify
     * Verify the submitted OTP.
     */
    public function verify(VerifyOtpRequest $request): JsonResponse
    {
        $this->verificationService->verify($request->user(), $request->otp);

        return $this->success(
            new UserResource($request->user()->fresh()),
            'Email verified successfully.'
        );
    }

    /**
     * GET /api/email/status
     * Check if the current user's email is verified.
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->success([
            'is_verified' => (bool) $user->email_verified_at,
            'verified_at' => $user->email_verified_at?->toDateTimeString(),
        ]);
    }
}
