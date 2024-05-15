<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Workspace;

class ImageController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/users/{user}/profile-image",
     *     summary="Upload a profile image",
     *     tags={"uploadImage"},
     *     description="Upload a profile image",
     *     operationId="uploadProfileImage",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Profile image data",
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"image"},
     *                 @OA\Property(
     *                     property="image",
     *                     description="Image file to upload",
     *                     type="string",
     *                     format="binary",
     *                 ),
     *             ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile image uploaded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Profile image uploaded successfully"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object", example={"image": {"The image field is required."}}),
     *         ),
     *     ),
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(
     *             type="integer",
     *             example=1,
     *         ),
     *     ),
     * )
     */
    public function storeProfileImage(Request $request, User $user)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // Example validation rules
        ]);

        // Store the uploaded image
        $imagePath = $request->file('image')->store('profile_images');

        // Associate the image with the user
        $user->image()->create(['image_path' => $imagePath]);

        return response()->json(['message' => 'Profile image uploaded successfully'], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/workspaces/{workspace}/workspace-image",
     *     summary="Upload a workspace image",
     *     tags={"uploadImage"},
     *     description="Upload a workspace image",
     *     operationId="uploadWorkspaceImage",
     *     @OA\RequestBody(
     *         required=true,
     *         description="workspace image data",
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"image"},
     *                 @OA\Property(
     *                     property="image",
     *                     description="Image file to upload",
     *                     type="string",
     *                     format="binary",
     *                 ),
     *             ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile image uploaded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Profile image uploaded successfully"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object", example={"image": {"The image field is required."}}),
     *         ),
     *     ),
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="workspace",
     *         in="path",
     *         required=true,
     *         description="Workspace ID",
     *         @OA\Schema(
     *             type="integer",
     *             example=1,
     *         ),
     *     ),
     * )
     */
    public function storeWorkspaceImage(Request $request, Workspace $workspace)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // Example validation rules
        ]);

        // Store the uploaded image
        $imagePath = $request->file('image')->store('workspace_images');

        // Associate the image with the user
        $workspace->image()->create(['image_path' => $imagePath]);

        return response()->json(['message' => 'Workspace image uploaded successfully'], 200);
    }
}
