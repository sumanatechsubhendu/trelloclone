<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkspaceRequest;
use App\Http\Resources\BoardResource;
use App\Http\Resources\WorkspaceResource;
use App\Http\Resources\TeamResource;
use App\Http\Resources\WorkspaceResourceWithBoards;
use App\Models\Board;
use App\Models\BoardSection;
use App\Models\Section;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Workspaces",
 *     description="API endpoints for managing workspaces"
 * )
 *
 * @OA\Schema(
 *     schema="Workspace",
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         description="The ID of the workspace"
 *     ),
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         description="The name of the workspace"
 *     ),
 *     @OA\Property(
 *         property="admin_id",
 *         type="integer",
 *         format="int64",
 *         description="The ID of the admin user of the workspace"
 *     ),
 *     @OA\Property(
 *         property="created_by",
 *         type="integer",
 *         format="int64",
 *         description="The ID of the user who created the workspace"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="The date and time when the workspace was created"
 *     ),
 * )
 */

class WorkspaceController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/workspaces",
     *     summary="Get a list of workspaces",
     *     tags={"Workspaces"},
     *     description="Retrieves a list of workspaces.",
     *     operationId="getWorkspaceList",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="A list of workspaces",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Workspace"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized: Authentication failed or user lacks necessary permissions.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No workspace found.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No workspace found"),
     *         ),
     *     ),
     * )
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // If the user is an admin, retrieve all workspaces
        if ($user->role == 'admin') {
            $workspaces = Workspace::get();
        } else {
            // Retrieve the logged-in user's workspace IDs using WorkspaceMember model
            $workspaceIds = WorkspaceMember::where('user_id', $user->id)->pluck('workspace_id')->all();

            if (empty($workspaceIds)) {
                return response()->json([
                    'success' => false,
                    'message' => "You don't have access to any of the workspaces."
                ], 404);
            }

            // Retrieve workspaces for the user
            $workspaces = Workspace::whereIn('id', $workspaceIds)->get();
        }
        return response()->json([
            'success' => true,
            'message' => "Workspace list retrieved successfully",
            'data' => WorkspaceResource::collection($workspaces)
        ]);
    }

     /**
     * @OA\Post(
     *     path="/api/workspaces",
     *     summary="Create a new workspace",
     *     tags={"Workspaces"},
     *     description="Create a new workspace with the provided data",
     *     operationId="createWorkspace",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Workspace data",
     *         @OA\JsonContent(
     *             required={"name", "description", "bg_color"},
     *             @OA\Property(property="name", type="string", example="Webwizard"),
     *             @OA\Property(property="description", type="string", example="Webwizard is a IT firm situated in USA"),
     *             @OA\Property(property="bg_color", type="string", example="#EEEFFF"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Workspace created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Workspace"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object", example={"name": {"The name field is required."}, "description": {"The description field is required."}}),
     *         ),
     *     ),
     *     security={{"bearerAuth": {}}},
     * )
     */


     public function store(WorkspaceRequest $request, Workspace $workspace)
     {
        $request->setTeam($workspace);
        // Set the created_by attribute
        $data = $request->validated();
        $data['created_by'] = Auth::user()->id;
        // Create the Team
        $workspaceObj = Workspace::create($data);

        $boardData['name'] = 'General Tasks';
        $boardData['bg_color'] = $request->bg_color;
        $boardData['workspace_id'] = $workspaceObj->id;
        $boardData['created_by'] = Auth::user()->id;
        // Create the Team
        $board = Board::create($boardData);

        $sections = Section::where('type', 0)->orderBy('position', 'asc')->get();
        $boardSections = [];
        foreach ($sections as $key => $val) {
            $boardSections[] = [
                'board_id' => $board->id,
                'section_id' => $val->id,
                'position' => $key + 1,
                'created_by' => Auth::user()->id,
                'created_at' => now(), // Add created_at timestamp
                'updated_at' => now(), // Add updated_at timestamp
            ];
        }
        // Now, you can insert the board sections
        BoardSection::insert($boardSections);        
        return response()->json([
            'success' => true,
            'message' => 'Workspace created successfully.',
            'data' => new WorkspaceResource($workspaceObj)
        ], JsonResponse::HTTP_OK);
    }

    /**
     * @OA\Get(
     *     path="/api/workspaces/{slug}",
     *     operationId="getWorkspaceBySlug",
     *     tags={"Workspaces"},
     *     summary="Get workspace details by slug",
     *     description="Returns the details of a workspace identified by the provided slug",
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         description="Slug of the workspace to fetch",
     *         required=true,
     *         @OA\Schema(type="string")
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

     public function show($slug)
     {
         $user = Auth::user();
 
         // If the user is an admin, retrieve the workspace by slug
         if ($user->role == 'admin') {
             try {
                 $workspace = Workspace::where('slug', $slug)->firstOrFail();
             } catch (ModelNotFoundException $e) {
                 return response()->json([
                     'success' => false,
                     'message' => 'Workspace not found'
                 ], JsonResponse::HTTP_NOT_FOUND);
             }
         } else {
             // Get workspace ID by slug
             $workspace = Workspace::where('slug', $slug)->first();
             if (!$workspace) {
                 return response()->json([
                     'success' => false,
                     'message' => 'Workspace not found'
                 ], JsonResponse::HTTP_NOT_FOUND);
             }
 
             // Check if the user is a member of the workspace
             $isMember = WorkspaceMember::where('user_id', $user->id)
                 ->where('workspace_id', $workspace->id)
                 ->exists();
 
             if (!$isMember) {
                 return response()->json([
                     'success' => false,
                     'message' => 'No access to this workspace.'
                 ], JsonResponse::HTTP_FORBIDDEN);
             }
         }
         
         return response()->json([
             'success' => true,
             'message' => 'Workspace details retrieved successfully.',
             'data' => new WorkspaceResourceWithBoards($workspace)
         ]);
     }

     /**
     * @OA\Put(
     *     path="/api/workspaces/{id}",
     *     operationId="updateWorkspace",
     *     tags={"Workspaces"},
     *     summary="Update workspace details",
     *     description="Updates the details of a workspace identified by the provided ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the workspace to update",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Workspace")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Workspace updated successfully",
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

      public function update(WorkspaceRequest $request, Workspace $workspace)
      {
          try {
              // Update the team
              $workspace->update($request->validated());

              return response()->json([
                  'success' => true,
                  'message' => 'Workspace updated successfully',
                  'data' => new WorkspaceResource($workspace)
              ]);
          } catch (ModelNotFoundException $e) {
              // Handle the 404 Not Found exception
              return response()->json([
                  'success' => false,
                  'message' => 'Team not found',
              ], JsonResponse::HTTP_NOT_FOUND);
          }
      }

     /**
     * @OA\Delete(
     *     path="/api/workspaces/{id}",
     *     operationId="deleteWorkspace",
     *     tags={"Workspaces"},
     *     summary="Delete a workspace",
     *     description="Deletes a workspace identified by the provided ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the workspace to delete",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Workspace deleted successfully"
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

     public function destroy(Workspace $workspace)
     {
        try {
            $workspace->delete();
            return response()->json(['success' => true, 'message' => 'User deleted successfully.'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete User.'], 500);
        }
     }
}
