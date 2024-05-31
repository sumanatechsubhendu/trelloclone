<?php

namespace App\Http\Controllers;

use App\Http\Requests\CardRequest;
use Illuminate\Http\Request;
use App\Models\Card;
use App\Models\User;
use App\Models\CardMember;
use App\Models\WorkspaceMember;
use App\Http\Resources\CardResource;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * @OA\Tag(
 *     name="Cards",
 *     description="API endpoints for managing cards"
 * )
 *
 * @OA\Schema(
 *     schema="Card",
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         description="The ID of the card"
 *     ),
 *     @OA\Property(
 *         property="title",
 *         type="string",
 *         description="The title of the card"
 *     ),
 *     @OA\Property(
 *         property="description",
 *         type="string",
 *         description="The description of the card"
 *     ),
 *     @OA\Property(
 *         property="board_section_id",
 *         type="integer",
 *         format="int64",
 *         description="The ID of the board section the card belongs to"
 *     ),
 *     @OA\Property(
 *         property="position_id",
 *         type="integer",
 *         format="int64",
 *         description="The position ID of the card within its board section"
 *     ),
 *     @OA\Property(
 *         property="created_by",
 *         type="integer",
 *         format="int64",
 *         description="The ID of the user who created the card"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="The date and time when the card was created"
 *     ),
 * )
 */
class CardController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/cards",
     *     summary="Get a list of cards",
     *     tags={"Cards"},
     *     description="Retrieves a list of cards.",
     *     operationId="getCardList",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="A list of cards",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Card"),
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
     *         description="No cards found.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No cards found"),
     *         ),
     *     ),
     * )
     */
    public function index()
    {
        $cards = Card::all();
        
        if (empty($cards)) {
            return response()->json([
                'success' => false,
                'message' => "You don't have access to any of the card."
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => "Card list retrieved successfully",
            'data' => CardResource::collection($cards)
        ]);
    }

    /**
     * Store a newly created card in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     *
     * @OA\Post(
     *     path="/api/cards",
     *     summary="Create a new card",
     *     tags={"Cards"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Card")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Card created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Card")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity"
     *     )
     * )
     */
    public function store(CardRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = Auth::user()->id;
        $card = Card::create($data);

        return response()->json([
            'success' => true,
            'message' => "Card created successfully",
            'data' => new CardResource($card)
        ], HttpResponse::HTTP_CREATED);
    }

    /**
    * @OA\Get(
    *     path="/api/cards/search?title={title}",
    *     summary="Get cards by search",
    *     description="Retrieve cards based on the search criteria",
    *     operationId="getCardsBySearch",
    *     tags={"Cards"},
    *     @OA\Parameter(
    *         name="title",
    *         in="query",
    *         description="The title to search cards",
    *         required=true,
    *         @OA\Schema(type="string")
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="A list of cards based on the search criteria",
    *         @OA\JsonContent(
    *             type="array",
    *             @OA\Items(ref="#/components/schemas/Card")
    *         )
    *     ),
    *     @OA\Response(
    *         response=404,
    *         description="No cards found",
    *         @OA\JsonContent(
    *             @OA\Property(
    *                 property="success",
    *                 type="boolean",
    *                 example=false
    *             ),
    *             @OA\Property(
    *                 property="message",
    *                 type="string",
    *                 example="No cards found"
    *             )
    *         )
    *      ),
    *     @OA\Response(
    *         response=401,
    *         description="Unauthorized: Authentication failed or user lacks necessary permissions.",
    *         @OA\JsonContent(
    *             @OA\Property(
    *                 property="message",
    *                 type="string",
    *                 example="Unauthorized"
    *             )
    *         )
    *     ),
    *     security={
    *         {"bearerAuth": {}}
    *     }
    * )
    */

    public function search(Request $request)
    { 
        $query = Card::query()->with('board');

        if ($request->has('title')) {
            $title = $request->input('title');
            $query->where(function($q) use ($title) {
                $q->where('title', 'like', '%' . $title . '%')
                  ->orWhere('description', 'like', '%' . $title . '%');
            });
        }
    
        $cards = $query->get();
    
        if ($cards->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No cards found matching the criteria'
            ], HttpResponse::HTTP_NOT_FOUND);
        }
    
        $data = $cards->map(function ($card) {
            return [
                'id' => $card->id,
                'title' => $card->title,
                'description' => $card->description,
                'board_name' => $card->board->name,
                'section_name' => $card->boardSection->section->title,
                'created_at' => $card->created_at,
                'updated_at' => $card->updated_at,
            ];
        });
    
        return response()->json([
            'success' => true,
            'message' => "Card list retrieved successfully",
            'data' => $data
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/cards/{card}/members",
     *     summary="Add a member to a card",
     *     tags={"Cards"},
     *     description="Add a member to a specific card",
     *     operationId="addCardMember",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="card",
     *         in="path",
     *         description="ID of the card",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"workspace_member_id"},
     *             @OA\Property(property="workspace_member_id", type="integer", description="ID of the user to add to the card")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Member added to card successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Member added to card successfully."),
     *             @OA\Property(
     *                 property="card",
     *                 type="object",
     *                 ref="#/components/schemas/Card"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Card or User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Card or User not found.")
     *         )
     *     )
     * )
     */
    public function addMember(Request $request, $cardId)
    {
        $request->validate([
            'workspace_member_id' => 'required|exists:workspace_members,id',
        ]);

        try {
            $card = Card::findOrFail($cardId);
            $member = WorkspaceMember::findOrFail($request->workspace_member_id);

            // Check if the member is already in the card
            if ($card->members()->where('workspace_member_id', $member->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Member is already part of the card.'
                ], HttpResponse::HTTP_CONFLICT);
            }

            $card->members()->attach($member->id);

            return response()->json([
                'success' => true,
                'message' => 'Member added to card successfully.',
            ], HttpResponse::HTTP_OK);
        
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Card or User not found.'
            ], HttpResponse::HTTP_NOT_FOUND);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/cards/{cardId}/non-members",
     *     summary="Get users who are not members of a specific card",
     *     description="Retrieves users who are not members of the specified card.",
     *     operationId="getNonCardMembers",
     *     tags={"Cards"},
     *     @OA\Parameter(
     *         name="cardId",
     *         in="path",
     *         description="ID of the card",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success: Users who are not members of the card",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/User")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Error: Card not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Card not found")
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */

    public function getNonCardMembers($cardId)
    {
        try {
            $card = Card::findOrFail($cardId);
            $workspace = $card->board->workspace;
            $memberIds = $workspace->members->pluck('id');
            $cardMemberIds = CardMember::where('card_id', $cardId)->pluck('workspace_member_id');
            $filteredMemberIds = $memberIds->diff($cardMemberIds);

            // Check if there are any non-card members found
            if ($filteredMemberIds->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => "No non-members found for this workspace.",
                ], HttpResponse::HTTP_NOT_FOUND);
            }

            // Fetch non-card members
            $nonCardMembers = User::whereIn('id', WorkspaceMember::whereIn('id', $filteredMemberIds)->pluck('user_id'))->get();

            // Return non-card members
            return response()->json([
                'success' => true,
                'message' => "Users who are not members of the workspace retrieved successfully",
                'data' => UserResource::collection($nonCardMembers),
            ], HttpResponse::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => "No non-members found for this workspace.",
            ], HttpResponse::HTTP_NOT_FOUND);
        }
    }

    // Implement update, show, and delete methods similar to store method above...
}
