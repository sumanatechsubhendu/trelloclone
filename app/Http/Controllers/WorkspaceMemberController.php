<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkspaceMemberRequest;
use App\Http\Resources\WorkspaceMemberResource;
use Illuminate\Http\Request;
use App\Models\WorkspaceMember;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Workspace Members",
 *     description="API endpoints for managing workspace members"
 * )
 *
 * @OA\Schema(
 *     schema="WorkspaceMember",
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         description="The ID of the workspace member"
 *     ),
 *     @OA\Property(
 *         property="workspace_id",
 *         type="integer",
 *         format="int64",
 *         description="The ID of the workspace associated with the member"
 *     ),
 *     @OA\Property(
 *         property="user_id",
 *         type="integer",
 *         format="int64",
 *         description="The ID of the user associated with the member"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="The date and time when the workspace member was created"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="The date and time when the workspace member was last updated"
 *     ),
 * )
 */

class WorkspaceMemberController extends Controller
{
     /**
     * Get a list of workspace member.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * @OA\Get(
     *     path="/api/workspaceMembers",
     *     summary="Get a list of workspace members",
     *     tags={"Workspace Members"},
     *     description="Retrieves a list of workspace members with pagination support.",
     *     operationId="getWorkspaceMemberList",
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
     *         description="Number of workspace members per page (default is 10)",
     *         required=false,
     *         @OA\Schema(
     *             type="integer",
     *             format="int32",
     *         ),
     *     ),
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="A list of workspace members",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/WorkspaceMember"),
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

        // Fetch workspaces with pagination
        $workspaces = WorkspaceMember::paginate($pageSize, ['*'], 'page', $pageNumber);

        return WorkspaceMemberResource::collection($workspaces);
    }

    /**
     * @OA\Post(
     *     path="/api/workspaceMembers",
     *     summary="Create a new workspace member",
     *     tags={"Workspace Members"},
     *     description="Create a new workspace member with the provided data",
     *     operationId="createWorkspaceMember",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Workspace member data",
     *         @OA\JsonContent(
     *             required={"workspace_id", "user_id"},
     *             @OA\Property(property="workspace_id", type="integer", example="1"),
     *             @OA\Property(property="user_id", type="integer", example="1"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Workspace member created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/WorkspaceMember"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object", example={"workspace_id": {"The workspace_id field is required."}, "user_id": {"The user_id field is required."}}),
     *         ),
     *     ),
     *     security={{"bearerAuth": {}}},
     * )
     */

     public function store(WorkspaceMemberRequest $request)
     {
         // Extract validated data
         $data = $request->validated();

         // Check if the workspace member already exists
         $existingMember = WorkspaceMember::where('workspace_id', $data['workspace_id'])
                                          ->where('user_id', $data['user_id'])
                                          ->first();

         if ($existingMember) {
             return response()->json([
                 'success' => false,
                 'message' => 'Workspace member already exists'
             ], 422);
         }

         // Set the created_by attribute
         $data['created_by'] = Auth::user()->id;

         // Create the WorkspaceMember
         $workspaceMember = WorkspaceMember::create($data);

         // Return the newly created workspace member resource
         return new WorkspaceMemberResource($workspaceMember);
     }

    /**
     * @OA\Get(
     *     path="/api/workspaceMembers/{id}",
     *     operationId="getWorkspaceMemberById",
     *     tags={"Workspace Members"},
     *     summary="Get workspace member details by ID",
     *     description="Returns the details of a workspace member identified by the provided ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the workspace member to fetch",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/WorkspaceMember")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Workspace member not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Workspace member not found")
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */

    public function show($id)
    {
        try {
            $team = WorkspaceMember::findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => new WorkspaceMemberResource($team)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Workspace Member not found'
            ], JsonResponse::HTTP_NOT_FOUND);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/workspaceMembers/{id}",
     *     operationId="updateWorkspaceMember",
     *     tags={"Workspace Members"},
     *     summary="Update workspace member details",
     *     description="Updates the details of a workspace member identified by the provided ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the workspace member to update",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/WorkspaceMember")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Workspace member updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/WorkspaceMember")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Workspace member not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Workspace member not found")
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */

    public function update(WorkspaceMemberRequest $request, WorkspaceMember $workspaceMember)
    {
        try {

            // Extract validated data
            $data = $request->validated();
            // Check if the workspace member already exists
            $existingMember = WorkspaceMember::where('workspace_id', $data['workspace_id'])
            ->where('user_id', $data['user_id'])
            ->first();

            if ($existingMember) {
                return response()->json([
                'success' => false,
                'message' => 'Workspace member already exists'
                ], 422);
            }
            // Update the team
            $workspaceMember->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Team updated successfully',
                'data' => [
                    'team' => new WorkspaceMemberResource($workspaceMember)
                ]
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
     *     path="/api/workspaceMembers/{id}",
     *     operationId="deleteWorkspaceMember",
     *     tags={"Workspace Members"},
     *     summary="Delete a workspace member",
     *     description="Deletes a workspace member identified by the provided ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the workspace member to delete",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Workspace member deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Workspace member not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Workspace member not found")
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */

    public function destroy(WorkspaceMember $workspaceMember)
    {
        $workspaceMember->delete();
        return response()->json(null, 204);
    }

    /**
     * @OA\Get(
     *     path="/api/list-members-by-workspace-id/{workspaceId}",
     *     operationId="getMembersByWorkspaceId",
     *     tags={"Workspace Members"},
     *     summary="Get member list by workspace ID",
     *     description="Returns the list of members by workspace ID.",
     *     @OA\Parameter(
     *         name="workspaceId",
     *         in="path",
     *         description="ID of the workspace to fetch members",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/WorkspaceMember")
     *         )
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
    public function listMembersByWorkspaceId($workspaceId)
    {
        try {
            $members = WorkspaceMember::with('user')->where('workspace_id', $workspaceId)->get();

            // Check if any members found
            if ($members->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No workspace members found for the given workspace ID'
                ], JsonResponse::HTTP_NOT_FOUND);
            }

            $list_members = $members->map(function ($member) {
                return [
                    'workspace_members_id' => $member->id,
                    'workspace_id' => $member->workspace_id,
                    'user_id' => $member->user_id,
                    'name' => $member->user->name,
                    'email' => $member->user->email,
                    'profile_image' => $member->user->profile_image,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $list_members
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Workspace not found'
            ], JsonResponse::HTTP_NOT_FOUND);
        }
    }
}
