<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Rules\RealEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /register/check-email?email=...
 * The register page asks this while the person types, so a fake email gets its red message
 * under the field right away. The same check runs again when the form is sent.
 * It does not say if an email is already registered (so nobody can use it to find accounts).
 */
class EmailCheckController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $email = mb_substr((string) $request->query('email', ''), 0, 255);
        $problem = RealEmail::problem($email);

        return response()->json([
            'valid' => $problem === null,
            'message' => $problem ?? 'Looks good. We will send a 6-digit code to this email.',
        ]);
    }
}
