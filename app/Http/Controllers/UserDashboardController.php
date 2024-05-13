<?php

namespace App\Http\Controllers;

use App\Models\Board;
use Illuminate\Http\Request;
use App\Models\WorkspaceMember;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class UserDashboardController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/list-workspaces",
     *     operationId="listWorkspaces",
     *     tags={"UserDashboard"},
     *     summary="List workspaces",
     *     description="Returns the list of workspaces for the authenticated user.",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="workspaces", type="object"),
     *         ),
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
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
     * @OA\Get(
     *     path="/api/list-boards",
     *     operationId="listBoards",
     *     tags={"UserDashboard"},
     *     summary="List boards",
     *     description="Returns the list of boards for the authenticated user's workspaces.",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="boards", type="array", @OA\Items(ref="#/components/schemas/Board")),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No boards found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No boards found")
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
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
            // Retrieve boards where workspace_id is in the allowed workspaces and paginate the results
            $boards = Board::whereIn('workspace_id', $allowed_workspace)->paginate();

            // Return the paginated list of boards as JSON response
            return response()->json([
                'success' => true,
                'boards' => $boards
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No boards found'
            ], 404);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/workspace-details/{id}",
     *     operationId="getWorkspaceDetailsById",
     *     tags={"UserDashboard"},
     *     summary="Get workspace details by ID",
     *     description="Returns the details of a workspace identified by the provided ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the workspace to fetch",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Workspace")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Workspace not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Workspace not found")
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function workspaceDetails($id)
    {
        try {
            // Retrieve the logged-in user's workspaces using WorkspaceMember model
            $workspaces = WorkspaceMember::where("user_id", Auth::user()->id)
                ->where("workspace_id", $id)
                ->with('workspace:id,name', 'boards')
                ->firstOrFail(); // Use firstOrFail() to automatically throw ModelNotFoundException if not found

            // Transform the workspaces data to match the desired JSON response structure
            $workspacesData = [
                'id' => $workspaces->id,
                'workspace_id' => $workspaces->workspace_id,
                'user_id' => $workspaces->user_id,
                'name' => $workspaces->workspace->name,
                'created_at' => $workspaces->created_at,
                'boards' => $workspaces->boards,
            ];
            return response()->json([
                'success' => true,
                'data' => $workspacesData
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Workspace not found'
            ], JsonResponse::HTTP_NOT_FOUND);
        }
    }
}
