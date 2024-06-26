<?php

namespace App\Http\Controllers;

use App\Http\Requests\CardRequest;
use App\Http\Requests\StoreCardCommentRequest;
use App\Http\Requests\StoreReplayRequest;
use App\Http\Resources\CardCommentResource;
use App\Http\Resources\CardReplayResource;
use Illuminate\Http\Request;
use App\Models\Card;
use App\Models\Replay;
use App\Models\Comment;
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
     *     security={{"bearerAuth": {}}},
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
     *     ),
     *     security={{"bearerAuth": {}}},
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
     *     path="/api/cards/{id}",
     *     summary="Get a specific card by ID",
     *     description="Retrieves a specific card based on its ID.",
     *     operationId="getCardById",
     *     tags={"Cards"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the card to retrieve",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success: Card found",
     *         @OA\JsonContent(ref="#/components/schemas/Card")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Error: Card not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Card not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized: Authentication failed or user lacks necessary permissions.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function show($id)
    {
        try {
            $card = Card::findOrFail($id);
            $comments = $card->comments()->with('user', 'replays.user')->get();

            return response()->json([
                'success' => true,
                'message' => 'Card details retrieved successfully.',
                'card' => new CardResource($card),
                'comments' => $comments
            ]);
        } catch (ModelNotFoundException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Card not found'
            ], HttpResponse::HTTP_NOT_FOUND);
        }
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
     * Store a newly created comment in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $cardId
     * @return \Illuminate\Http\Response
     *
     * @OA\Post(
     *     path="/api/cards/{cardId}/comments",
     *     summary="Create a new comment for a card",
     *     tags={"Cards"},
     *     @OA\Parameter(
     *         name="cardId",
     *         in="path",
     *         description="ID of the card to add comment to",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"content"},
     *             @OA\Property(property="content", type="string", example="This is a comment")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Comment created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", format="int64", description="The ID of the comment"),
     *                 @OA\Property(property="content", type="string", description="The content of the comment"),
     *                 @OA\Property(property="card_id", type="integer", format="int64", description="The ID of the card associated with the comment"),
     *                 @OA\Property(property="user_id", type="integer", format="int64", description="The ID of the user who created the comment"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", description="The date and time when the comment was created")
     *             )
     *         )
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

    public function storeComment(StoreCardCommentRequest $request, $cardId)
    {
        try {
            // Fetch the card or fail
            $card = Card::findOrFail($cardId);
        } catch (ModelNotFoundException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Card not found'
            ], HttpResponse::HTTP_NOT_FOUND);
        }

        // Set the comment attributes
        $data = $request->validated();
        $data['card_id'] = $cardId;
        $data['user_id'] = Auth::id();

        // Create the comment
        $comment = new Comment($data);
        $comment->save();

        // Return a JSON response with the newly created comment
        return response()->json([
            'success' => true,
            'message' => 'Comment stored successfully.',
            'data' => new CardCommentResource($comment)
        ], HttpResponse::HTTP_CREATED);
    }

    /**
     * Store a newly created replay for a comment in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Comment  $comment
     * @return \Illuminate\Http\Response
     *
     * @OA\Post(
     *     path="/api/comments/{comment}/replays",
     *     summary="Create a new replay for a comment",
     *     tags={"Comments"},
     *     @OA\Parameter(
     *         name="comment",
     *         in="path",
     *         description="The ID of the comment to add a replay to",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"replay_content"},
     *             @OA\Property(property="replay_content", type="string", example="This is a replay")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Replay stored successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Replay stored successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", format="int64", description="The ID of the replay"),
     *                 @OA\Property(property="replay_content", type="string", description="The content of the replay"),
     *                 @OA\Property(property="comment_id", type="integer", format="int64", description="The ID of the comment associated with the replay"),
     *                 @OA\Property(property="user_id", type="integer", format="int64", description="The ID of the user who created the replay"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", description="The date and time when the replay was created"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", description="The date and time when the replay was last updated")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Error: Comment not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Comment not found")
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */

    public function storeCommentReplay(StoreReplayRequest $request, Comment $comment)
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();

        // Create the replay
        $replay = new Replay($data);
        $comment->replays()->save($replay);

        // Return a JSON response
        return response()->json([
            'success' => true,
            'message' => 'Replay stored successfully.',
            'data' => new CardReplayResource($replay)
        ], 201);
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
