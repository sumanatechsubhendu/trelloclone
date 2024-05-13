<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    /**
    * @OA\Post(
    * path="/api/login",
    * operationId="authLogin",
    * tags={"Login"},
    * summary="User Login",
    * description="Login User Here",
    *     @OA\RequestBody(
    *         @OA\JsonContent(),
    *         @OA\MediaType(
    *            mediaType="multipart/form-data",
    *            @OA\Schema(
    *               type="object",
    *               required={"email", "password"},
    *               @OA\Property(property="email", type="string", pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$", format="email", example="amlan@email.com"),
    *               @OA\Property(property="password", type="string", format="password", example="password")
    *            ),
    *        ),
    *    ),
    *      @OA\Response(
    *          response=201,
    *          description="Login Successfully",
    *          @OA\JsonContent()
    *       ),
    *      @OA\Response(
    *          response=200,
    *          description="Login Successfully",
    *          @OA\JsonContent()
    *       ),
    *      @OA\Response(
    *          response=422,
    *          description="Unprocessable Entity",
    *          @OA\JsonContent()
    *       ),
    *      @OA\Response(response=400, description="Bad request"),
    *      @OA\Response(response=404, description="Resource Not Found"),
    * )
    */
    public function login(Request $request)
    {

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            $token = $user->createToken('YourAppName')->accessToken;

            return response()->json(['token' => $token], 200);
        }

        return response()->json(['message' => 'Unauthorized'], 401);
    }


    /**
     * @OA\Post(
     *     path="/api/logout",
     *     operationId="authLogout",
     *     tags={"Logout"},
     *     summary="User Logout",
     *     description="Logout User Here",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logout Successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Successfully logged out")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return response()->json(['message' => 'Successfully logged out'], 200);
    }

    public function verifyToken(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
        ]);

        // If validation fails, return validation error response
        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }
        // Check if the request has a valid token
        if (Auth::guard('api')->check()) {
            // Retrieve the authenticated user
            $user = Auth::user();

            // Retrieve the authenticated user's access token
            $accessToken = $user->token();

            // Prepare the response data
            $responseData = [
                'user_id' => $user->id,
                'email' => $user->email,
                'token_type' => 'Bearer',
                'expires_at' => $accessToken->expires_at,
                'created_at' => $accessToken->created_at,
            ];

            // Token is valid, return success response
            return response()->json([
                'valid' => true,
                'message' => 'Token is valid',
                'responseData' => $responseData
        ], 200);
        } else {
            // Token is invalid or missing, return error response
            return response()->json(['valid' => false, 'message' => 'Unauthorized'], 401);
        }
    }
}
