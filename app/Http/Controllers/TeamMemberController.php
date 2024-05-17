<?php

namespace App\Http\Controllers;

use App\Http\Requests\TeamMemberRequest;
use App\Http\Resources\TeamMemberResource;
use Illuminate\Http\Request;
use App\Models\TeamMember;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Team Members",
 *     description="API endpoints for managing team members"
 * )
 *
 * @OA\Schema(
 *     schema="TeamMember",
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         description="The ID of the team member"
 *     ),
 *     @OA\Property(
 *         property="team_id",
 *         type="integer",
 *         format="int64",
 *         description="The ID of the team associated with the member"
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
 *         description="The date and time when the team member was created"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="The date and time when the team member was last updated"
 *     ),
 * )
 */

class TeamMemberController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/team-members",
     *     summary="Get a list of team members",
     *     tags={"Team Members"},
     *     description="Retrieves a list of team members with pagination support.",
     *     operationId="getTeamMemberList",
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
     *         description="Number of team members per page (default is 10)",
     *         required=false,
     *         @OA\Schema(
     *             type="integer",
     *             format="int32",
     *         ),
     *     ),
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="A list of team members",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/TeamMember"),
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
        $workspaces = TeamMember::paginate($pageSize, ['*'], 'page', $pageNumber);

        return TeamMemberResource::collection($workspaces);
    }

    /**
     * @OA\Post(
     *     path="/api/team-members",
     *     summary="Create a new team member",
     *     tags={"Team Members"},
     *     description="Create a new team member with the provided data",
     *     operationId="createTeamMember",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Team member data",
     *         @OA\JsonContent(
     *             required={"team_id", "user_id"},
     *             @OA\Property(property="team_id", type="integer", example="1"),
     *             @OA\Property(property="user_id", type="integer", example="1"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Team member created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/TeamMember"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object", example={"team_id": {"The team_id field is required."}, "user_id": {"The user_id field is required."}}),
     *         ),
     *     ),
     *     security={{"bearerAuth": {}}},
     * )
     */

    public function store(TeamMemberRequest $request)
     {
         // Extract validated data
         $data = $request->validated();

         // Check if the workspace member already exists
         $existingMember = TeamMember::where('team_id', $data['team_id'])
                                          ->where('user_id', $data['user_id'])
                                          ->first();

         if ($existingMember) {
             return response()->json([
                 'success' => false,
                 'message' => 'Team member already exists'
             ], 422);
         }

         $data['created_by'] = Auth::user()->id;
         // Create the Team
         $team = TeamMember::create($data);

         // Return the newly created team resource
         return new TeamMemberResource($team);
     }

    /**
     * @OA\Get(
     *     path="/api/team-members/{id}",
     *     operationId="getTeamMemberById",
     *     tags={"Team Members"},
     *     summary="Get team member details by ID",
     *     description="Returns the details of a team member identified by the provided ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the team member to fetch",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/TeamMember")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Team member not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Team member not found")
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */

      public function show($id)
      {
          try {
              $team = TeamMember::findOrFail($id);
              return response()->json([
                  'success' => true,
                  'data' => new TeamMemberResource($team)
              ]);
          } catch (ModelNotFoundException $e) {
              return response()->json([
                  'success' => false,
                  'message' => 'TeamMember not found'
              ], JsonResponse::HTTP_NOT_FOUND);
          }
      }

    /**
     * @OA\Put(
     *     path="/api/team-members/{id}",
     *     operationId="updateTeamMember",
     *     tags={"Team Members"},
     *     summary="Update team member details",
     *     description="Updates the details of a team member identified by the provided ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the team member to update",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/TeamMember")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Team member updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/TeamMember")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Team member not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Team member not found")
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */

       public function update(TeamMemberRequest $request, TeamMember $teamMember)
       {
           try {
                // Extract validated data
                $data = $request->validated();

                // Check if the workspace member already exists
                $existingMember = TeamMember::where('team_id', $data['team_id'])
                                                ->where('user_id', $data['user_id'])
                                                ->first();

                if ($existingMember) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Team member already exists'
                    ], 422);
                }
               // Update the teamMember
               $teamMember->update($data);

               return response()->json([
                   'success' => true,
                   'message' => 'TeamMember updated successfully',
                   'data' => [
                       'team' => new TeamMemberResource($teamMember)
                   ]
               ]);
           } catch (ModelNotFoundException $e) {
               // Handle the 404 Not Found exception
               return response()->json([
                   'success' => false,
                   'message' => 'TeamMember not found',
               ], JsonResponse::HTTP_NOT_FOUND);
           }
       }

    /**
     * @OA\Delete(
     *     path="/api/team-members/{id}",
     *     operationId="deleteTeamMember",
     *     tags={"Team Members"},
     *     summary="Delete a team member",
     *     description="Deletes a team member identified by the provided ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the team member to delete",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Team member deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Team member not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Team member not found")
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */

      public function destroy(TeamMember $teamMember)
      {
          $teamMember->delete();
          return response()->json(null, 204);
      }
}
