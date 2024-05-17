<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateTeamRequest;
use App\Http\Resources\TeamResource;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;

    /**
     * @OA\Tag(
     *     name="Teams",
     *     description="API endpoints for managing teams"
     * )
     *
     * @OA\Schema(
     *     schema="Team",
     *     @OA\Property(
     *         property="id",
     *         type="integer",
     *         format="int64",
     *         description="The ID of the team"
     *     ),
     *     @OA\Property(
     *         property="name",
     *         type="string",
     *         description="The name of the team"
     *     ),
     *     @OA\Property(
     *         property="description",
     *         type="string",
     *         description="The description of the team"
     *     ),
     *     @OA\Property(
     *         property="team_head_id",
     *         type="integer",
     *         format="int64",
     *         description="team head id"
     *     ),
     *     @OA\Property(
     *         property="created_at",
     *         type="string",
     *         format="date-time",
     *         description="The date and time when the user was created"
     *     ),
     * )
     */
class TeamController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/teams",
     *     summary="Get a list of teams",
     *     tags={"Teams"},
     *     description="Retrieves a list of teams with pagination support.",
     *     operationId="getTeamList",
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
     *         description="Number of teams per page (default is 10)",
     *         required=false,
     *         @OA\Schema(
     *             type="integer",
     *             format="int32",
     *         ),
     *     ),
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="A list of teams",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Team"),
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
        $users = Team::paginate($pageSize, ['*'], 'page', $pageNumber);

        return TeamResource::collection($users);
    }

    /**
     * @OA\Post(
     *     path="/api/teams",
     *     summary="Create a new team",
     *     tags={"Teams"},
     *     description="Create a new team with the provided data",
     *     operationId="createTeam",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Team data",
     *         @OA\JsonContent(
     *             required={"name", "description"},
     *             @OA\Property(property="name", type="string", example="Webwizard"),
     *             @OA\Property(property="description", type="string", example="Webwizard is a IT firm situated in USA"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Team created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Team"),
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

     public function store(UpdateTeamRequest $request)
     {
         // Set the created_by attribute
         $data = $request->validated();
         $data['created_by'] = Auth::user()->id;
         // Create the Team
         $team = Team::create($data);

         // Return the newly created team resource
         return new TeamResource($team);
     }


     /**
      * @OA\Get(
      *     path="/api/teams/{id}",
      *     operationId="getTeamById",
      *     tags={"Teams"},
      *     summary="Get team details by ID",
      *     description="Returns the details of a team identified by the provided ID",
      *     @OA\Parameter(
      *         name="id",
      *         in="path",
      *         description="ID of the team to fetch",
      *         required=true,
      *         @OA\Schema(type="integer")
      *     ),
      *     @OA\Response(
      *         response=200,
      *         description="Successful operation",
      *         @OA\JsonContent(ref="#/components/schemas/Team")
      *     ),
      *     @OA\Response(
      *         response=404,
      *         description="Team not found",
      *         @OA\JsonContent(
      *             @OA\Property(property="success", type="boolean", example=false),
      *             @OA\Property(property="message", type="string", example="Team not found")
      *         )
      *     ),
      *     security={{"bearerAuth": {}}}
      * )
      */
     public function show($id)
     {
         try {
             $team = Team::findOrFail($id);
             return response()->json([
                 'success' => true,
                 'data' => new TeamResource($team)
             ]);
         } catch (ModelNotFoundException $e) {
             return response()->json([
                 'success' => false,
                 'message' => 'Team not found'
             ], JsonResponse::HTTP_NOT_FOUND);
         }
     }

     /**
      * @OA\Put(
      *     path="/api/teams/{id}",
      *     operationId="updateTeam",
      *     tags={"Teams"},
      *     summary="Update team details",
      *     description="Updates the details of a team identified by the provided ID",
      *     @OA\Parameter(
      *         name="id",
      *         in="path",
      *         description="ID of the team to update",
      *         required=true,
      *         @OA\Schema(type="integer")
      *     ),
      *     @OA\RequestBody(
      *         required=true,
      *         @OA\JsonContent(ref="#/components/schemas/Team")
      *     ),
      *     @OA\Response(
      *         response=200,
      *         description="Team updated successfully",
      *         @OA\JsonContent(ref="#/components/schemas/Team")
      *     ),
      *     @OA\Response(
      *         response=404,
      *         description="Team not found",
      *         @OA\JsonContent(
      *             @OA\Property(property="success", type="boolean", example=false),
      *             @OA\Property(property="message", type="string", example="User not found")
      *         )
      *     ),
      *     security={{"bearerAuth": {}}}
      * )
      */
      public function update(UpdateTeamRequest $request, Team $team)
      {
          try {
              // Update the team
              $team->update($request->validated());

              return response()->json([
                  'success' => true,
                  'message' => 'Team updated successfully',
                  'data' => [
                      'team' => new TeamResource($team)
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
      *     path="/api/teams/{id}",
      *     operationId="deleteTeam",
      *     tags={"Teams"},
      *     summary="Delete a team",
      *     description="Deletes a team identified by the provided ID",
      *     @OA\Parameter(
      *         name="id",
      *         in="path",
      *         description="ID of the team to delete",
      *         required=true,
      *         @OA\Schema(type="integer")
      *     ),
      *     @OA\Response(
      *         response=204,
      *         description="Team deleted successfully"
      *     ),
      *     @OA\Response(
      *         response=404,
      *         description="Team not found",
      *         @OA\JsonContent(
      *             @OA\Property(property="success", type="boolean", example=false),
      *             @OA\Property(property="message", type="string", example="User not found")
      *         )
      *     ),
      *     security={{"bearerAuth": {}}}
      * )
      */
     public function destroy(Team $team)
     {
         $team->delete();
         return response()->json(null, 204);
     }
}
