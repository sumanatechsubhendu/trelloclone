<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Workspace;

class ImageController extends Controller
{
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
