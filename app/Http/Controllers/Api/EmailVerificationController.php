<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\EmailVerificationCode;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EmailVerificationController extends Controller
{
    /**
     * Verify the email with the provided code.
     */
    public function verify(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'exists:users,email'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation échouée',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        // Check if the user is already verified
        if ($user->email_verified_at !== null) {
            return response()->json([
                'message' => 'Email déjà vérifié',
            ], 400);
        }

        // Check if code matches
        if ($user->email_verification_code !== $request->code) {
            return response()->json([
                'message' => 'Code de vérification invalide',
            ], 400);
        }

        // Check if code is expired
        if ($user->email_verification_code_expires_at < now()) {
            return response()->json([
                'message' => 'Code de vérification expiré',
            ], 400);
        }

        // Mark email as verified
        $user->email_verified_at = now();
        $user->email_verification_code = null;
        $user->email_verification_code_expires_at = null;
        $user->save();

        return response()->json([
            'message' => 'Email vérifié avec succès',
            'user' => new UserResource($user),
        ], 200);
    }

    /**
     * Resend the verification code.
     */
    public function resend(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation échouée',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        // Check if the user is already verified
        if ($user->email_verified_at !== null) {
            return response()->json([
                'message' => 'Email déjà vérifié',
            ], 400);
        }

        // Generate a new verification code
        $verificationCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->email_verification_code = $verificationCode;
        $user->email_verification_code_expires_at = now()->addMinutes(15);
        $user->save();

        // Send verification code via email
        $user->notify(new EmailVerificationCode($verificationCode));

        return response()->json([
            'message' => 'Code de vérification renvoyé avec succès',
        ], 200);
    }
}
