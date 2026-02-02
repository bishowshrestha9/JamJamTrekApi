<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Laravel\Sanctum\PersonalAccessToken;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    #[OA\Post(
        path: "/api/login",
        summary: "User login",
        description: "Login the user and return a bearer token",
        operationId: "login",
        tags: ["Authentication"]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["email", "password"],
            properties: [
                new OA\Property(property: "email", type: "string", format: "email", example: "user@example.com"),
                new OA\Property(property: "password", type: "string", format: "password", example: "password123")
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Login successful",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "boolean", example: true),
                new OA\Property(property: "message", type: "string", example: "Login successful"),
                new OA\Property(property: "token", type: "string", example: "1|abcdefghijklmnopqrstuvwxyz")
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: "Invalid credentials",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "boolean", example: false),
                new OA\Property(property: "message", type: "string", example: "Invalid login details")
            ]
        )
    )]
    #[OA\Response(
        response: 403,
        description: "Invalid credentials",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "boolean", example: false),
                new OA\Property(property: "message", type: "string", example: "Invalid login details")
            ]
        )
    )]
    #[OA\Response(
        response: 423,
        description: "Invalid credentials",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "boolean", example: false),
                new OA\Property(property: "message", type: "string", example: "Invalid login details")
            ]
        )
    )]


    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid login details'
                ], 401);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            // Detect environment for cookie settings
            // Check multiple sources: Origin header, Referer header, and request host
        $origin = $request->header('Origin') ?? $request->header('Referer') ?? '';
        $requestHost = $request->getHost();
        $requestScheme = $request->getScheme();

        // Check if this is localhost (from any source)
        $isLocalhost = (
            str_contains($origin, 'localhost') ||
            str_contains($origin, '127.0.0.1') ||
            str_contains($requestHost, 'localhost') ||
            str_contains($requestHost, '127.0.0.1') ||
            $requestHost === '127.0.0.1' ||
            $requestHost === 'localhost'
        );

        $isHttps = $request->isSecure() || $requestScheme === 'https' || str_starts_with(config('app.url'), 'https://');
        $isProduction = config('app.env') === 'production';

        // Determine if this is a cross-origin request
        $origin = $request->header('Origin');
        $isCrossOrigin = $origin && $origin !== $request->getSchemeAndHttpHost();

        // Cookie settings based on context
        if ($isCrossOrigin) {
            // Cross-origin: MUST use SameSite=None with Secure=true
            // Note: This requires HTTPS even on localhost
            $sameSite = 'none';
            $secure = true;
        } else {
            // Same-origin: Can use SameSite=Lax without Secure requirement
            $sameSite = 'lax';
            $secure = $isHttps; // Only set secure if using HTTPS
        }
        $domain = null; // null for host-only cookie

        $response = response()->json([
            'status' => true,
            'message' => 'Login successful',
            'token' => $token, // Include token as fallback
        ], 200)->cookie(
                'auth_token',           // Cookie name
                $token,                 // Token value
                60 * 24 * 7,            // 7 days expiration (in minutes)
                '/',                     // Path (available to all paths)
                $domain,                // Domain: null for localhost
                $secure,                // Secure flag
                true,                    // HttpOnly (not accessible via JavaScript)
                false,                   // Raw (false = URL encode)
                $sameSite               // SameSite setting
            );

        return $response;
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $th) {
            \Log::error('Login error: ' . $th->getMessage(), ['file' => $th->getFile(), 'line' => $th->getLine()]);
            return response()->json([
                'status' => false,
                'message' => 'Login failed'
            ], 500);
        }
    }

    #[OA\Post(
        path: "/api/logout",
        summary: "User logout",
        description: "Logout the authenticated user by deleting their current access token",
        operationId: "logout",
        tags: ["Authentication"],
        security: [["sanctum" => []]]
    )]
    #[OA\Response(
        response: 200,
        description: "Logout successful",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "boolean", example: true),
                new OA\Property(property: "message", type: "string", example: "Logged out")
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: "Unauthenticated",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "message", type: "string", example: "Unauthenticated.")
            ]
        )
    )]
    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'status' => true,
                'message' => 'Logged out'
            ]);
        } catch (\Throwable $th) {
            \Log::error('Logout error: ' . $th->getMessage(), ['file' => $th->getFile(), 'line' => $th->getLine()]);
            return response()->json([
                'status' => false,
                'message' => 'Logout failed'
            ], 500);
        }
    }

    #[OA\Get(
        path: "/api/me",
        summary: "Get authenticated user",
        description: "Get the authenticated user's information",
        operationId: "me",
        tags: ["Authentication"],
        security: [["sanctum" => []]]
    )]
    #[OA\Response(
        response: 200,
        description: "User information retrieved successfully",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "boolean", example: true),
                new OA\Property(property: "role", type: "string", example: "admin")
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: "Unauthenticated",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "message", type: "string", example: "Unauthenticated.")
            ]
        )
    )]
    public function me(Request $request)
    {
        try {
            return response()->json([
                'status' => true,
                'role' => $request->user()->role
            ]);
        } catch (\Throwable $th) {
            \Log::error('Get user error: ' . $th->getMessage(), ['file' => $th->getFile(), 'line' => $th->getLine()]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve user information'
            ], 500);
        }
    }
}