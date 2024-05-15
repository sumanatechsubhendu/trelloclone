<?php

namespace App\Http\Controllers;

use App\Http\Resources\BoardResource;
use App\Http\Resources\WorkspaceMemberResource;
use App\Http\Resources\WorkspaceResource;
use App\Models\Board;
use App\Models\Workspace;
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
        $workspaces = WorkspaceMember::when(Auth::user()->role !== 'admin', function ($query) {
            $query->where('user_id', Auth::user()->id);
        })
        ->with('workspace:id,name')
        ->paginate();
        // Transform the workspaces data to match the desired JSON response structure
        $workspacesData = $workspaces->map(function ($workspace) {
            return new WorkspaceMemberResource($workspace);
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
        $workspaces = WorkspaceMember::when(Auth::user()->role !== 'admin', function ($query) {
            $query->where('user_id', Auth::user()->id);
        })
        ->pluck('workspace_id') // Pluck workspace IDs directly
        ->all(); // Convert the collection to an array

        if (empty($workspaces)) {
            return response()->json([
                'success' => false,
                'message' => 'No boards found'
            ], 404);
        }

        if($workspaces) {
            // Retrieve boards where workspace_id is in the allowed workspaces and paginate the results
            if (Auth::user()->role == 'admin') {
                $boards = Board::paginate();
            } else {
                $boards = Board::whereIn('workspace_id', $workspaces)->paginate();
            }
            $boardData = $boards->map(function ($board) {
                return new BoardResource($board);
            });

            // Create a paginator manually to include pagination metadata in the response
            $paginator = new LengthAwarePaginator(
                $boardData,
                $boards->total(),
                $boards->perPage(),
                $boards->currentPage(),
                ['path' => $request->url(), 'query' => $request->query()]
            );

            // Return the paginated list of workspaces as JSON response
            return response()->json(['workspaces' => $paginator], 200);
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
            if (Auth::user()->role !== 'admin') {
                // Retrieve the logged-in user's workspaces using WorkspaceMember model
                $workspaces = WorkspaceMember::when(Auth::user()->role !== 'admin', function ($query) {
                    $query->where('user_id', Auth::user()->id);
                })
                ->where("workspace_id", $id)
                ->with('workspace:id,name', 'boards')
                ->firstOrFail(); // Use firstOrFail() to automatically throw ModelNotFoundException if not found
            } else {
                try {
                    $team = Workspace::findOrFail($id);
                    return response()->json([
                        'success' => true,
                        'data' => new WorkspaceResource($team)
                    ]);
                } catch (ModelNotFoundException $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Workspace not found'
                    ], JsonResponse::HTTP_NOT_FOUND);
                }
            }

            $team = Workspace::findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => new WorkspaceResource($workspaces->team)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Workspace not found or you have no access'
            ], JsonResponse::HTTP_NOT_FOUND);
        }
    }
}
