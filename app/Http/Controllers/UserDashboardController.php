<?php

namespace App\Http\Controllers;

use App\Models\Board;
use Illuminate\Http\Request;
use App\Models\User; // Assuming your User model is in the App\Models namespace
use App\Models\Workspace; // Assuming your Workspace model is in the App\Models namespace
use App\Models\WorkspaceMember;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class UserDashboardController extends Controller
{
    /**
     * List all workspaces accessible to the logged-in user.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\Response
     */
    public function listWorkspaces(Request $request)
    {
        // Retrieve the logged-in user's workspaces using WorkspaceMember model
        $workspaces = WorkspaceMember::where("user_id", Auth::user()->id)
        ->with('workspace:id,name')
        ->paginate();

        // Transform the workspaces data to match the desired JSON response structure
        $workspacesData = $workspaces->map(function ($workspace) {
            return [
                'id' => $workspace->id,
                'workspace_id' => $workspace->workspace->id,
                'user_id' => $workspace->user_id,
                'name' => $workspace->workspace->name,
                'created_at' => $workspace->created_at,
            ];
        });

        // Create a paginator manually to include pagination metadata in the response
        $paginator = new LengthAwarePaginator(
            $workspacesData,
            $workspaces->total(),
            $workspaces->perPage(),
            $workspaces->currentPage(),
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Return the paginated list of workspaces as JSON response
        return response()->json(['workspaces' => $paginator], 200);
    }

    /**
     * List all workspaces accessible to the logged-in user.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\Response
     */
    public function listBoards(Request $request)
    {
        // Retrieve the logged-in user's workspaces using WorkspaceMember model
        $workspaces = WorkspaceMember::where("user_id", Auth::user()->id)
        ->get();

        $allowed_workspace = [];
        foreach ($workspaces as $key=>$val) {
            $allowed_workspace[] = $val->workspace_id;
        }

        if($allowed_workspace) {
            // Retrieve boards where workspace_id is 4 and paginate the results
            $boards = Board::whereIn('workspace_id', [4])->paginate();

            // Return the paginated list of boards as JSON response
            return response()->json([
                'success' => false,
                'boards' => $boards
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No boards found'
            ], 422);
        }
    }

    /**
     * List all workspaces accessible to the logged-in user.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\Response
     */
    public function workspaceDetails($id)
    {
        try {
            // Retrieve the logged-in user's workspaces using WorkspaceMember model
            $workspaces = WorkspaceMember::where("user_id", Auth::user()->id)
                ->where("workspace_id", $id)
                ->with('workspace:id,name')
                ->paginate();
            return response()->json([
                'success' => true,
                'data' => $workspaces
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Workspace not found'
            ], JsonResponse::HTTP_NOT_FOUND);
        }
    }
}
