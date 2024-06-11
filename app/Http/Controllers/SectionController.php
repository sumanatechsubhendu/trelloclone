<?php

namespace App\Http\Controllers;

use App\Http\Requests\SectionRequest;
use App\Http\Resources\SectionResource;
use Illuminate\Http\Request;
use App\Models\Section;
use App\Models\Card;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Sections",
 *     description="API endpoints for managing sections"
 * )
 *
 * @OA\Schema(
 *     schema="Section",
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         description="The ID of the section"
 *     ),
 *     @OA\Property(
 *         property="title",
 *         type="string",
 *         description="The title of the section"
 *     ),
 *     @OA\Property(
 *         property="position",
 *         type="integer",
 *         description="The position of the section"
 *     ),
 *     @OA\Property(
 *         property="type",
 *         type="integer",
 *         format="int32",
 *         description="The type of the section. 0 for Default, 1 for Custom."
 *     ),
 *     @OA\Property(
 *         property="created_by",
 *         type="integer",
 *         format="int64",
 *         description="The ID of the user who created the section"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="The date and time when the section was created"
 *     ),
 * )
 */

class SectionController extends Controller
{
    /**
     * Display a listing of the sections.
     *
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Get(
     *     path="/api/sections",
     *     summary="Get a list of sections",
     *     tags={"Sections"},
     *     description="Retrieves a list of sections with pagination support.",
     *     operationId="getSectionList",
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
     *         description="Number of sections per page (default is 10)",
     *         required=false,
     *         @OA\Schema(
     *             type="integer",
     *             format="int32",
     *         ),
     *     ),
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="A list of sections",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Section"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request, invalid parameters",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object", example={"title": {"The title field is required."}, "position": {"The position field is required."}}),
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
        $users = Section::paginate($pageSize, ['*'], 'page', $pageNumber);

        if (empty($users)) {
            return response()->json([
                'success' => false,
                'message' => "You don't have access to any of the Section."
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => "Section list retrieved successfully",
            'data' => SectionResource::collection($users)
        ]);
    }

    /**
     * Store a newly created Section in storage.
     *
     * @param  \Illuminate\Http\SectionRequest  $request
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Post(
     *     path="/api/sections",
     *     summary="Create a new section",
     *     tags={"Sections"},
     *     description="Create a new section with the provided data",
     *     operationId="createSection",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Section data",
     *         @OA\JsonContent(
     *             required={"title", "position", "type", "created_by"},
     *             @OA\Property(property="title", type="string", example="Section Title"),
     *             @OA\Property(property="position", type="integer", example=1),
     *             @OA\Property(property="type", type="integer", example=0, description="0 – Default, 1 – Custom"),
     *             @OA\Property(property="created_by", type="integer", example=1),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Section created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Section"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object", example={"title": {"The title field is required."}, "position": {"The position field is required."}}),
     *         ),
     *     ),
     *     security={{"bearerAuth": {}}},
     * )
     */

    public function store(SectionRequest $request)
    {
        // Set the created_by attribute
        $data = $request->validated();
        $data['created_by'] = Auth::user()->id;
        // Create the Team
        $team = Section::create($data);

        // Return the newly created team resource
        return response()->json([
            'success' => true,
            'message' => 'Section created successfully.',
            'data' => new SectionResource($team)
        ], JsonResponse::HTTP_OK);
    }

     /**
     * Display the specified section.
     *
     * @param  \App\Models\Section  $section
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Get(
     *     path="/api/sections/{id}",
     *     operationId="getSectionById",
     *     tags={"Sections"},
     *     summary="Get section details by ID",
     *     description="Returns the details of a section identified by the provided ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the section to fetch",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Section")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Section not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Section not found")
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */

    public function show($id)
    {
        try {
            $team = Section::findOrFail($id);
            return response()->json([
                'success' => true,
                'message' => 'Section details retrieved successfully.',
                'data' => new SectionResource($team)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Section not found'
            ], JsonResponse::HTTP_NOT_FOUND);
        }
    }

     /**
     * Show the form for editing the specified section.
     *
     * @param  \App\Models\Section  $section
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Put(
     *     path="/api/sections/{id}",
     *     operationId="updateSection",
     *     tags={"Sections"},
     *     summary="Update section details",
     *     description="Updates the details of a section identified by the provided ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the section to update",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Section data",
     *         @OA\JsonContent(ref="#/components/schemas/Section")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Section updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Section")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Section not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Section not found")
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */

    public function update(SectionRequest $request, Section $section)
    {
        try {
            // Update the team
            $section->update($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Section updated successfully',
                'data' => [
                    'board' => new SectionResource($section)
                ]
            ]);
        } catch (ModelNotFoundException $e) {
            // Handle the 404 Not Found exception
            return response()->json([
                'success' => false,
                'message' => 'Section not found',
            ], JsonResponse::HTTP_NOT_FOUND);
        }
    }

    /**
     * Remove the specified section from storage.
     *
     * @param  \App\Models\Section  $section
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Delete(
     *     path="/api/sections/{id}",
     *     operationId="deleteSection",
     *     tags={"Sections"},
     *     summary="Delete a section",
     *     description="Deletes a section identified by the provided ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the section to delete",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Section deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Section not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Section not found")
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */

    public function destroy(Section $section)
    {
        try {
            $section->delete();
            return response()->json(['success' => true, 'message' => 'Section deleted successfully.'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete Section.'], 500);
        }
        
    }

    /**
     * @OA\Get(
     *     path="/api/sections/{id}/positions",
     *     operationId="getSectionPositionsById",
     *     tags={"Sections"},
     *     summary="Get positions of a section by ID",
     *     description="Returns the positions of a section identified by the provided ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the section to fetch positions for",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Section positions retrieved successfully."),
     *             @OA\Property(property="data", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Section not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Section not found")
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function getPositionListBySectionId($id)
    {
        try {
            // Check if the section exists
            $section = Section::findOrFail($id);

            // Fetch the position values for all sections
            $positions = Card::where('board_section_id', $id)
            ->orderBy('position_id', 'asc')
            ->pluck('position_id');

            // Check if positions are empty
            if ($positions->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No positions found for the given section ID.',
                ], JsonResponse::HTTP_NO_CONTENT);
            }

            return response()->json([
                'success' => true,
                'message' => 'Section positions retrieved successfully.',
                'data' => $positions
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Section not found'
            ], JsonResponse::HTTP_NOT_FOUND);
        }
    }
}
