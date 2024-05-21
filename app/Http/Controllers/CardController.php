<?php

namespace App\Http\Controllers;

use App\Http\Requests\CardRequest;
use Illuminate\Http\Request;
use App\Models\Card;
use App\Http\Resources\CardResource;
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
        return CardResource::collection($cards);
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
            'data' => new CardResource($card)
        ], HttpResponse::HTTP_CREATED);
    }

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
            'data' => $data
        ]);
    }

    // Implement update, show, and delete methods similar to store method above...
}
