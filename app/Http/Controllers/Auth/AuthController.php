<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Mail\ForgotPasswordMail;
use App\Mail\ResetPasswordMail;
use Illuminate\Support\Facades\Mail;
use DB;
use Carbon\Carbon;

class AuthController extends Controller
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
        $data   =   array();

        $validator = Validator::make($request->all(), [
            'email'     =>  'required',
            'password'  =>  'required',
        ]);

        if ($validator->fails())
        {
            $messages=$validator->messages();
            $data=$messages->all();
            return response()->json(['data' => $data, 'message' => 'Validation Error'], 400);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
             return response()->json(['status' => false, 'message' => 'incorrect Email or password'], 400);
        }
        if($user) {
            $exist_token = PersonalAccessToken::Where('tokenable_id', $user->id);
            if($exist_token)
            {
                $exist_token->update(['expires_at' => "".date('Y-m-d H:i:s').""]);
            }
        }
        $token =  "Bearer ".$user->createToken('TrelloClone')->accessToken;
        return response()->json(['user' => $user, 'token' => $token], 200);
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

    /**
     * @OA\Post(
     *     path="/api/verify-token",
     *     operationId="verifyToken",
     *     tags={"verifyToken"},
     *     summary="Verify Token",
     *     description="Verify the validity of the provided authentication token",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Token is valid",
     *         @OA\JsonContent(
     *             @OA\Property(property="valid", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Token is valid"),
     *             @OA\Property(property="responseData", type="object",
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="email", type="string", example="user@example.com"),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(property="expires_at", type="string", format="date-time", example="2024-05-10 12:00:00"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-05-10 10:00:00")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="valid", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */
    public function verifyToken(Request $request)
    {
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

    /**
     * @OA\Post(
     *     path="/api/forget-password",
     *     operationId="forgetPassword",
     *     tags={"forgetPassword"},
     *     summary="Forget Password",
     *     description="Initiate password reset for the user",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="User email",
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password reset email sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Password reset email sent successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User email not found or password reset mail already sent")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */
    public function forgetPassword(Request $request)
    {
        try {
            $checkExist = User::where('email', $request->email)->first();
            if(!$checkExist) {
                return response()->json(['status' => false, 'message' => 'User email not found or password reset mail already sent'], 400);
            }

            $checkPasswordResetToken = \DB::table('password_reset_tokens')->where('email', $request->email)->first();
            if($checkPasswordResetToken) {
                return response()->json(['status' => false, 'message' => 'User email not found or password reset mail already sent'], 400);
            }

            $token = Str::random(64);
            \DB::table('password_reset_tokens')->insert([
                'email' => $request->email,
                'token' => $token,
                'created_at' => Carbon::now()
            ]);

            Mail::to($request->email)->send(new ForgotPasswordMail($request->email, $token));
            return response()->json(['status' => true, 'message' => 'Password reset email sent successfully'], 200);
        } catch (\Exception $err) {
            return response()->json(['status' => false, 'message' => $err->getMessage()], 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/reset-password",
     *     operationId="resetPassword",
     *     tags={"resetPassword"},
     *     summary="Reset Password",
     *     description="Reset the user's password using the provided token and new password",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="User email and new password",
     *         @OA\JsonContent(
     *             required={"password", "password_confirmation", "token"},
     *             @OA\Property(property="password", type="string", format="password", minLength=8, example="newPassword123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", minLength=8, example="newPassword123"),
     *             @OA\Property(property="token", type="string", example="reset_token_here")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Password updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid URL for password reset or something went wrong")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */
    public function resetPassword(Request $request)
    {
        try {
            $this->validate($request, [
                'password' => 'required|string|min:8|confirmed',
                'password_confirmation' => 'required',
                'token' => 'required'
            ]);

            $checkPasswordResetToken = \DB::table('password_reset_tokens')->where('token', $request->token)->first();
            if(!$checkPasswordResetToken) {
                return response()->json(['status' => false, 'message' => 'Invalid url for password reset'], 400);
            } else {
                $user = User::where('email', $checkPasswordResetToken->email)->first();
                if($user) {
                    Mail::to($checkPasswordResetToken->email)->send(new ResetPasswordMail($checkPasswordResetToken->email));
                    $user->password = Hash::make($request->password);
                    $user->save();
                    \DB::table('password_reset_tokens')->where('token', $request->token)->delete();
                    return response()->json(['status' => true, 'message' => 'Password updated successfully'], 200);
                }
            }
            return response()->json(['status' => false, 'message' => 'Something went wrong'], 400);

        } catch (\Exception $err) {
            return response()->json(['status' => false, 'message' => $err->getMessage()], 400);
        }
    }
}
