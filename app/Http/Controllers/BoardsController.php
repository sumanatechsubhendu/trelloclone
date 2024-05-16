<?php

namespace App\Http\Controllers;

use App\Http\Requests\BoardRequest;
use App\Http\Resources\BoardResource;
use App\Http\Resources\TeamResource;
use App\Models\Board;
use App\Models\BoardSection;
use App\Models\Section;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Boards",
 *     description="API endpoints for managing boards"
 * )
 *
 * @OA\Schema(
 *     schema="Board",
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         description="The ID of the board"
 *     ),
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         description="The name of the board"
 *     ),
 *     @OA\Property(
 *         property="workspace_id",
 *         type="integer",
 *         format="int64",
 *         description="The ID of the workspace the board belongs to"
 *     ),
 *     @OA\Property(
 *         property="admin_id",
 *         type="integer",
 *         format="int64",
 *         description="The ID of the admin of the board"
 *     ),
 *     @OA\Property(
 *         property="created_by",
 *         type="integer",
 *         format="int64",
 *         description="The ID of the user who created the board"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="The date and time when the board was created"
 *     ),
 * )
 */

class BoardsController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/boards",
     *     summary="Get a list of boards",
     *     tags={"Boards"},
     *     description="Retrieves a list of boards with pagination support.",
     *     operationId="getBoardList",
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
     *         description="Number of boards per page (default is 10)",
     *         required=false,
     *         @OA\Schema(
     *             type="integer",
     *             format="int32",
     *         ),
     *     ),
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="A list of boards",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Board"),
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
        $users = Board::paginate($pageSize, ['*'], 'page', $pageNumber);

        return BoardResource::collection($users);
    }

    /**
     * Store a newly created board in storage.
     *
     * @param  \Illuminate\Http\BoardRequest  $request
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Post(
     *     path="/api/boards",
     *     summary="Create a new board",
     *     tags={"Boards"},
     *     description="Create a new board with the provided data",
     *     operationId="createBoard",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Board data",
     *         @OA\JsonContent(
     *             required={"name", "description", "workspace_id", "admin_id", "created_by"},
     *             @OA\Property(property="name", type="string", example="Board Name"),
     *             @OA\Property(property="description", type="string", example="Board Description"),
     *             @OA\Property(property="workspace_id", type="integer", example=1),
     *             @OA\Property(property="admin_id", type="integer", example=1),
     *             @OA\Property(property="created_by", type="integer", example=1),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Board created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Board"),
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
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer token",
     *         @OA\Schema(
     *             type="string",
     *             example="Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIxIiwianRpIjoiMjFjMDNlMWI5ODdkOGQzNjE4NGUzMDVmYzI4NTgyZTNhMDBkOGM4NzliMGZjYjZjNTJiYzQwMGFkYWY2ZmNmMjYwYTRhOTk4NTU0OGFmOGMiLCJpYXQiOjE3MTM2OTM1NzYuNzQ0OTM3LCJuYmYiOjE3MTM2OTM1NzYuNzQ0OTM5LCJleHAiOjE3NDUyMjk1NzYuNzI3MTEzLCJzdWIiOiIxMiIsInNjb3BlcyI6W119.Q6KmBLxMGm3Jc8f_eeW1VPNMPfgdD4R5KqEfp_0xjfW16YAOnDtrCpzq5C4eeKNdH8gk9vLEpmY8Op5pXc903mtIcXfAUOfcsv0XdsIVM1ymh1bP5t-Iur_SwY37vUI_sS2zYKY0f9R9MjGq3XdK9XJIGDY6G3F__ztLDkwtfnwtOxittHgmSEAc2IshLM3yOm-jkyGKz6HPbFrSBxwwWtIXGV4EiUhmZDEkZce2v5v5ux3xRYqFRzeGdEM6Rgj2dYCqGC7egCzyb3LC2ei2lAqk_ofKDJ6TXWLBktsBGRlcJnVJyWh1nV7a9S8CV3KA8l6p9PW4I5rvZkBO6AzX5wLI264grzh9dHlr7qJmgRXHPfA6_OJJ1REpvkfnWoiJWqqVQ92IJpVVYznf6fk-ZWY-uXEqaP63tjq8pyQKwijNKRTMUA8VKtpd0ahqKnjaNaFhuS77T5zMZyLdsKF2hZ4tztlaQFRGqk37zFn5NzoP6N4dViRw9C_6rY0AZ63MT0wHigij7lORahfPN2MrjF-gklqhB61N9d_GCMLCTaf7E3rK3JlMdwLHVi0JCF453GkC5mhU2xOphPo2y6HElBIulPwdM2CpmARGNFUrjTyjkFOkxT-PRYFJgFYUKos_eBLFG64f_mbbVOmaw7CY9lr0KbVeuamBDhTjUDR4A44"
     *         ),
     *     ),
     * )
     */

    public function store(BoardRequest $request)
    {
        // Set the created_by attribute
        $data = $request->validated();
        $data['created_by'] = Auth::user()->id;
        // Create the Team
        $board = Board::create($data);
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

        // Return the newly created team resource
        return new BoardResource($board);
    }


    /**
     * @OA\Get(
     *     path="/api/boards/{id}",
     *     operationId="getBoardById",
     *     tags={"Boards"},
     *     summary="Get board details by ID",
     *     description="Returns the details of a board identified by the provided ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the board to fetch",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Board")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Board not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Board not found")
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function show($id)
    {
        try {
            $team = Board::findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => new TeamResource($team)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Board not found'
            ], JsonResponse::HTTP_NOT_FOUND);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/boards/{id}",
     *     operationId="updateBoard",
     *     tags={"Boards"},
     *     summary="Update board details",
     *     description="Updates the details of a board identified by the provided ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the board to update",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Board")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Board updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Board")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Board not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Board not found")
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */

     public function update(BoardRequest $request, Board $board)
      {
          try {
              // Update the team
              $board->update($request->validated());

              return response()->json([
                  'success' => true,
                  'message' => 'Board updated successfully',
                  'data' => [
                      'board' => new BoardResource($board)
                  ]
              ]);
          } catch (ModelNotFoundException $e) {
              // Handle the 404 Not Found exception
              return response()->json([
                  'success' => false,
                  'message' => 'Board not found',
              ], JsonResponse::HTTP_NOT_FOUND);
          }
      }

    /**
     * @OA\Delete(
     *     path="/api/boards/{id}",
     *     operationId="deleteBoard",
     *     tags={"Boards"},
     *     summary="Delete a board",
     *     description="Deletes a board identified by the provided ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the board to delete",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Board deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Board not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Board not found")
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function destroy(Board $board)
    {
        $board->delete();
        return response()->json(null, 204);
    }
}
