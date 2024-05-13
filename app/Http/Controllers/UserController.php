<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

    /**
     * @OA\Tag(
     *     name="Users",
     *     description="API endpoints for managing users"
     * )
     *
     * @OA\Schema(
     *     schema="User",
     *     @OA\Property(
     *         property="id",
     *         type="integer",
     *         format="int64",
     *         description="The ID of the user"
     *     ),
     *     @OA\Property(
     *         property="name",
     *         type="string",
     *         description="The name of the user"
     *     ),
     *     @OA\Property(
     *         property="email",
     *         type="string",
     *         format="email",
     *         description="The email address of the user"
     *     ),
     *     @OA\Property(
     *         property="created_at",
     *         type="string",
     *         format="date-time",
     *         description="The date and time when the user was created"
     *     ),
     *     @OA\Property(
     *         property="updated_at",
     *         type="string",
     *         format="date-time",
     *         description="The date and time when the user was last updated"
     *     )
     * )
     */
class UserController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/users",
     *     summary="Get a list of users",
     *     tags={"Users"},
     *     description="Retrieves a list of users with pagination support.",
     *     operationId="getUserList",
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number (default is 1)",
     *         required=false,
     *         @OA\Schema(
     *             type="integer",
     *             format="int32",
     *         ),
     *     ),
     *     @OA\Parameter(
     *         name="pageSize",
     *         in="query",
     *         description="Number of users per page (default is 10)",
     *         required=false,
     *         @OA\Schema(
     *             type="integer",
     *             format="int32",
     *         ),
     *     ),
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="A list of users",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/User"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request, invalid parameters",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object", example={"name": {"The name field is required."}, "email": {"The email field is required."}}),
     *         ),
     *     ),
     * )
     */

    public function index(Request $request)
    {
        // Set default page size if not provided in the request
        $pageSize = $request->input('pageSize', 10);

        // Get page number from the request
        $pageNumber = $request->input('page', 1);

        // Fetch users with pagination
        $users = User::paginate($pageSize, ['*'], 'page', $pageNumber);

        return UserResource::collection($users);
    }

     /**
     * @OA\Post(
     *     path="/api/users",
     *     summary="Create a new user",
     *     tags={"Users"},
     *     description="Create a new user with the provided data",
     *     operationId="createUser",
     *     @OA\RequestBody(
     *         required=true,
     *         description="User data",
     *         @OA\JsonContent(
     *             required={"name", "email", "password"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/User"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object", example={"name": {"The name field is required."}, "email": {"The email field is required."}}),
     *         ),
     *     ),
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer token",
     *         @OA\Schema(
     *             type="string",
     *             example="Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIxIiwianRpIjoiMjFjMDNlMWI5ODdkOGQzNjE4NGUzMDVmYzI4NTgyZTNhMDBkOGM4NzliMGZjYjZjNTJiYzQwMGFkYWY2ZmNmMjYwYTRhOTk4NTU0OGFmOGMiLCJpYXQiOjE3MTM2OTM1NzYuNzQ0OTM3LCJuYmYiOjE3MTM2OTM1NzYuNzQ0OTM5LCJleHAiOjE3NDUyMjk1NzYuNzI3MTEzLCJzdWIiOiIxMiIsInNjb3BlcyI6W119.Q6KmBLxMGm3Jc8f_eeW1VPNMPfgdD4R5KqEfp_0xjfW16YAOnDtrCpzq5C4eeKNdH8gk9vLEpmY8Op5pXc903mtIcXfAUOfcsv0XdsIVM1ymh1bP5t-Iur_SwY37vUI_sS2zYKY0f9R9MjGq3XdK9XJIGDY6G3F__ztLDkwtfnwtOxittHgmSEAc2IshLM3yOm-jkyGKz6HPbFrSBxwwWtIXGV4EiUhmZDEkZce2v5v5ux3xRYqFRzeGdEM6Rgj2dYCqGC7egCzyb3LC2ei2lAqk_ofKDJ6TXWLBktsBGRlcJnVJyWh1nV7a9S8CV3KA8l6p9PW4I5rvZkBO6AzX5wLI264grzh9dHlr7qJmgRXHPfA6_OJJ1REpvkfnWoiJWqqVQ92IJpVVYznf6fk-ZWY-uXEqaP63tjq8pyQKwijNKRTMUA8VKtpd0ahqKnjaNaFhuS77T5zMZyLdsKF2hZ4tztlaQFRGqk37zFn5NzoP6N4dViRw9C_6rY0AZ63MT0wHigij7lORahfPN2MrjF-gklqhB61N9d_GCMLCTaf7E3rK3JlMdwLHVi0JCF453GkC5mhU2xOphPo2y6HElBIulPwdM2CpmARGNFUrjTyjkFOkxT-PRYFJgFYUKos_eBLFG64f_mbbVOmaw7CY9lr0KbVeuamBDhTjUDR4A44"
     *         ),
     *     ),
     * )
     */

     public function store(StoreUserRequest $request)
     {
         // Set the created_by attribute
         $data = $request->validated();
         $data['created_by'] = Auth::user()->id;
         $data['password'] = Hash::make($request->password);

         // Create the user
         $user = User::create($data);

         // Return the newly created user resource
         return new UserResource($user);
     }

     /**
      * @OA\Get(
      *     path="/api/users/{id}",
      *     operationId="getUserById",
      *     tags={"Users"},
      *     summary="Get user details by ID",
      *     description="Returns the details of a user identified by the provided ID",
      *     @OA\Parameter(
      *         name="id",
      *         in="path",
      *         description="ID of the user to fetch",
      *         required=true,
      *         @OA\Schema(type="integer")
      *     ),
      *     @OA\Response(
      *         response=200,
      *         description="Successful operation",
      *         @OA\JsonContent(ref="#/components/schemas/User")
      *     ),
      *     @OA\Response(
      *         response=404,
      *         description="User not found",
      *         @OA\JsonContent(
      *             @OA\Property(property="success", type="boolean", example=false),
      *             @OA\Property(property="message", type="string", example="User not found")
      *         )
      *     ),
      *     security={{"bearerAuth": {}}}
      * )
      */
     public function show($id)
     {
         try {
             $user = User::findOrFail($id);
             return response()->json([
                 'success' => true,
                 'data' => new UserResource($user)
             ]);
         } catch (ModelNotFoundException $e) {
             return response()->json([
                 'success' => false,
                 'message' => 'User not found'
             ], JsonResponse::HTTP_NOT_FOUND);
         }
     }

     /**
      * @OA\Put(
      *     path="/api/users/{id}",
      *     operationId="updateUser",
      *     tags={"Users"},
      *     summary="Update user details",
      *     description="Updates the details of a user identified by the provided ID",
      *     @OA\Parameter(
      *         name="id",
      *         in="path",
      *         description="ID of the user to update",
      *         required=true,
      *         @OA\Schema(type="integer")
      *     ),
      *     @OA\RequestBody(
      *         required=true,
      *         @OA\JsonContent(ref="#/components/schemas/User")
      *     ),
      *     @OA\Response(
      *         response=200,
      *         description="User updated successfully",
      *         @OA\JsonContent(ref="#/components/schemas/User")
      *     ),
      *     @OA\Response(
      *         response=404,
      *         description="User not found",
      *         @OA\JsonContent(
      *             @OA\Property(property="success", type="boolean", example=false),
      *             @OA\Property(property="message", type="string", example="User not found")
      *         )
      *     ),
      *     security={{"bearerAuth": {}}}
      * )
      */
     public function update(UpdateUserRequest $request, User $user)
     {
         // Update the user
         $user->update($request->validated());

         return response()->json([
             'success' => true,
             'message' => 'User updated successfully',
             'data' => [
                 'user' => new UserResource($user)
             ]
         ]);
     }

     /**
      * @OA\Delete(
      *     path="/api/users/{id}",
      *     operationId="deleteUser",
      *     tags={"Users"},
      *     summary="Delete a user",
      *     description="Deletes a user identified by the provided ID",
      *     @OA\Parameter(
      *         name="id",
      *         in="path",
      *         description="ID of the user to delete",
      *         required=true,
      *         @OA\Schema(type="integer")
      *     ),
      *     @OA\Response(
      *         response=204,
      *         description="User deleted successfully"
      *     ),
      *     @OA\Response(
      *         response=404,
      *         description="User not found",
      *         @OA\JsonContent(
      *             @OA\Property(property="success", type="boolean", example=false),
      *             @OA\Property(property="message", type="string", example="User not found")
      *         )
      *     ),
      *     security={{"bearerAuth": {}}}
      * )
      */
     public function destroy(User $user)
     {
         $user->delete();
         return response()->json(null, 204);
     }
}
