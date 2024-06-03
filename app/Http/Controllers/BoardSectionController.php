<?php

namespace App\Http\Controllers;

use App\Http\Requests\BoardSectionRequest;
use App\Http\Resources\BoardSectionResource;
use App\Models\BoardSection;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Board Sections",
 *     description="API endpoints for managing board sections"
 * )
 *
 * @OA\Schema(
 *     schema="BoardSection",
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         description="The ID of the board section"
 *     ),
 *     @OA\Property(
 *         property="board_id",
 *         type="integer",
 *         format="int64",
 *         description="The ID of the board associated with the section"
 *     ),
 *     @OA\Property(
 *         property="section_id",
 *         type="integer",
 *         format="int64",
 *         description="The ID of the section associated with the board"
 *     ),
 *     @OA\Property(
 *         property="position",
 *         type="integer",
 *         description="The position ID of the board section"
 *     ),
 *     @OA\Property(
 *         property="created_by",
 *         type="integer",
 *         format="int64",
 *         description="The ID of the user who created the board section"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="The date and time when the board section was created"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="The date and time when the board section was last updated"
 *     ),
 * )
 */

class BoardSectionController extends Controller
{
    /**
     * Get a list of workspace member.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * @OA\Get(
     *     path="/api/boardSections",
     *     summary="Get a list of board sections",
     *     tags={"Board Sections"},
     *     description="Retrieves a list of board sections with pagination support.",
     *     operationId="getBoardSectionList",
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
     *         description="Number of board sections per page (default is 10)",
     *         required=false,
     *         @OA\Schema(
     *             type="integer",
     *             format="int32",
     *         ),
     *     ),
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="A list of board sections",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/BoardSection"),
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
        $workspaces = BoardSection::paginate($pageSize, ['*'], 'page', $pageNumber);

        if (empty($workspaces)) {
            return response()->json([
                'success' => false,
                'message' => "You don't have access to any of the boardsection."
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => "Board Section list retrieved successfully",
            'data' => BoardSectionResource::collection($workspaces)
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/boardSections",
     *     summary="Create a new board section",
     *     tags={"Board Sections"},
     *     description="Create a new board section with the provided data",
     *     operationId="createBoardSection",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Board section data",
     *         @OA\JsonContent(
     *             required={"board_id", "section_id", "position", "created_by"},
     *             @OA\Property(property="board_id", type="integer", example="1"),
     *             @OA\Property(property="section_id", type="integer", example="1"),
     *             @OA\Property(property="position", type="integer", example="1"),
     *             @OA\Property(property="created_by", type="integer", example="1"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Board section created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/BoardSection"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object", example={"board_id": {"The board_id field is required."}, "section_name": {"The section_name field is required."}, "position": {"The position field is required."}, "created_by": {"The created_by field is required."}}),
     *         ),
     *     ),
     *     security={{"bearerAuth": {}}},
     * )
     */

     public function store(BoardSectionRequest $request)
     {
         // Extract validated data
         $data = $request->validated();

         // Check if the workspace member already exists
         $existingMember = BoardSection::where('board_id', $data['board_id'])
                                          ->where('section_id', $data['section_id'])
                                          ->where('position', $data['position'])
                                          ->first();

         if ($existingMember) {
             return response()->json([
                 'success' => false,
                 'message' => 'Board Section already exists'
             ], 422);
         }

         // Set the created_by attribute
         $data['created_by'] = Auth::user()->id;

         // Create the WorkspaceMember
         $workspaceMember = BoardSection::create($data);

         // Return the newly created workspace member resource
         return response()->json([
            'success' => true,
            'message' => 'Board section created successfully.',
            'data' => new BoardSectionResource($workspaceMember)
        ], JsonResponse::HTTP_OK);
     }

    /**
     * @OA\Get(
     *     path="/api/boardSections/{id}",
     *     operationId="getBoardSectionById",
     *     tags={"Board Sections"},
     *     summary="Get board section details by ID",
     *     description="Returns the details of a board section identified by the provided ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the board section to fetch",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/BoardSection")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Board section not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Board section not found")
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */

    public function show($id)
    {
        try {
            $team = BoardSection::findOrFail($id);
            return response()->json([
                'success' => true,
                'message' => 'Board section details retrieved successfully.',
                'data' => new BoardSectionResource($team)
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
     *     path="/api/boardSections/{id}",
     *     operationId="updateBoardSection",
     *     tags={"Board Sections"},
     *     summary="Update board section details",
     *     description="Updates the details of a board section identified by the provided ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the board section to update",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/BoardSection")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Board section updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/BoardSection")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Board section not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Board section not found")
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */


    public function update(BoardSectionRequest $request, BoardSection $boardSection)
    {
        try {

            // Extract validated data
            $data = $request->validated();
            // Check if the workspace member already exists
            $existingMember = BoardSection::where('board_id', $data['board_id'])
            ->where('section_id', $data['section_id'])
            ->where('position', $data['position'])
            ->first();

            if ($existingMember) {
                return response()->json([
                'success' => false,
                'message' => 'boardSection already exists'
                ], 422);
            }
            // Update the team
            $boardSection->update($data);

            return response()->json([
                'success' => true,
                'message' => 'boardSection updated successfully',
                'data' => [
                    'board_sections' => new BoardSectionResource($boardSection)
                ]
            ]);
        } catch (ModelNotFoundException $e) {
            // Handle the 404 Not Found exception
            return response()->json([
                'success' => false,
                'message' => 'boardSection not found',
            ], JsonResponse::HTTP_NOT_FOUND);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/boardSections/{id}",
     *     operationId="deleteBoardSection",
     *     tags={"Board Sections"},
     *     summary="Delete a board section",
     *     description="Deletes a board section identified by the provided ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the board section to delete",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Board section deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Board section not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Board section not found")
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */


    public function destroy(BoardSection $boardSection)
    {
        try {
            $boardSection->delete();
            return response()->json(['success' => true, 'message' => 'Board sction deleted successfully.'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete board section.'], 500);
        }
    }
}
