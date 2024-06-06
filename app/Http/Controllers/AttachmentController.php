<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Attachment;
use App\Models\AttachmentComment;
use App\Http\Requests\StoreAttachmentRequest;
use App\Http\Requests\UpdateAttachmentNameRequest;
use App\Http\Requests\StoreAttachmentCommentRequest;
use App\Http\Requests\UpdateAttachmentCommentRequest;
use App\Http\Resources\AttachmentResource;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as HttpResponse;


/**
 * @OA\Schema(
 *     schema="Attachment",
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         description="The ID of the board"
 *     ),
 *     @OA\Property(
 *         property="attachable_id",
 *         type="integer",
 *         format="int64",
 *         description="The ID of the entity this attachment is associated with"
 *     ),
 *     @OA\Property(
 *         property="attachable_name",
 *         type="string",
 *         description="The original name of the attachment file"
 *     ),
 *     @OA\Property(
 *         property="attachable_type",
 *         type="string",
 *         description="The type of the entity this attachment is associated with"
 *     ),
 *     @OA\Property(
 *         property="attachment_path",
 *         type="string",
 *         description="The file path where the attachment is stored"
 *     ),
 *     @OA\Property(
 *         property="file_type",
 *         type="string",
 *         description="The file type/extension of the attachment"
 *     ),
 *     @OA\Property(
 *         property="user_id",
 *         type="integer",
 *         format="int64",
 *         description="The ID of the user who uploaded the attachment"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="The date and time when the board was created"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="The date and time when the board was updated"
 *     ),
 * )
 * 
 *  * @OA\Schema(
 *     schema="Attachment_Comment",
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         description="The ID of the board"
 *     ),
 *     @OA\Property(
 *         property="attachment_id",
 *         type="integer",
 *         format="int64",
 *         description="The ID of the attachment this comment is associated with"
 *     ),
 *     @OA\Property(
 *         property="user_id",
 *         type="integer",
 *         format="int64",
 *         description="The ID of the user who uploaded the comment"
 *     ),
 *     @OA\Property(
 *         property="comment",
 *         type="string",
 *         description="The text content of the comment"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="The date and time when the board was created"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="The date and time when the board was updated"
 *     ),
 * )
 */

class AttachmentController extends Controller
{

    /**
     * Store a newly created attachment in storage.
     *
     * @param  \App\Http\Requests\StoreAttachmentRequest  $request
     * @param  int  $cardId
     * @return \Illuminate\Http\Response
     *
     * @OA\Post(
     *     path="/api/{cardId}/attachments",
     *     summary="Upload a new attachment for a card",
     *     tags={"Attachments"},
     *     @OA\Parameter(
     *         name="cardId",
     *         in="path",
     *         description="ID of the card to attach the file to",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"attachment"},
     *                 @OA\Property(
     *                     property="attachment",
     *                     description="The file to upload",
     *                     type="string",
     *                     format="binary"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Attachment uploaded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Attachment uploaded successfully."),
     *             @OA\Property(property="data", ref="#/components/schemas/Attachment")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation error message"),
     *             @OA\Property(property="errors", type="object", example={"attachment": {"The attachment field is required."}})
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
    public function store(StoreAttachmentRequest $request, $cardId)
    {
        try {
            $card = Card::findOrFail($cardId);
            $attachment = $request->file('attachment');
            $path = $attachment->store('attachments', 'public');
            $fileType = $attachment->getClientOriginalExtension();
            $originalFileName = $attachment->getClientOriginalName();

            $attachmentRecord = new Attachment([
                'attachable_id' => $cardId,
                'attachable_name' => $originalFileName,
                'attachable_type' => Card::class,
                'attachment_path' => $path,
                'file_type' => $fileType,
                'user_id' => Auth::id(),
            ]);

            $attachmentRecord->save();

            return response()->json([
                'success' => true,
                'message' => 'Attachment uploaded successfully.',
                'data' => $attachmentRecord,
            ], HttpResponse::HTTP_CREATED);
        } catch (ModelNotFoundException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Card not found'
            ], HttpResponse::HTTP_NOT_FOUND);
        }
    }

    /**
     * Update the name of an attachment.
     *
     * @param  \App\Http\Requests\UpdateAttachmentNameRequest  $request
     * @param  int  $attachmentId
     * @return \Illuminate\Http\Response
     *
     * @OA\Patch(
     *     path="/api/attachments/{attachmentId}",
     *     summary="Update the name of an attachment",
     *     tags={"Attachments"},
     *     @OA\Parameter(
     *         name="attachmentId",
     *         in="path",
     *         description="ID of the attachment to update",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"attachable_name"},
     *             @OA\Property(property="attachable_name", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Attachment name updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Attachment name updated successfully."),
     *             @OA\Property(property="data", ref="#/components/schemas/Attachment")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Error: Attachment not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Attachment not found")
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function update(UpdateAttachmentNameRequest $request, $attachmentId)
    {
        try {
            $attachment = Attachment::findOrFail($attachmentId);

            $data = $request->validated();

            $attachment->attachable_name = $data['attachable_name'];
            $attachment->save();

            return response()->json([
                'success' => true,
                'message' => 'Attachment name updated successfully.',
                'data' => new AttachmentResource($attachment),
            ], HttpResponse::HTTP_OK);
        } catch (ModelNotFoundException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Attachment not found'
            ], HttpResponse::HTTP_NOT_FOUND);
        }
    }

    /**
     * Remove the specified attachment from storage.
     *
     * @param  int  $attachmentId
     * @return \Illuminate\Http\Response
     *
     * @OA\Delete(
     *     path="/api/attachments/{attachmentId}",
     *     summary="Delete an attachment",
     *     tags={"Attachments"},
     *     @OA\Parameter(
     *         name="attachmentId",
     *         in="path",
     *         description="ID of the attachment to delete",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Attachment deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Error: Attachment not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Attachment not found")
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function destroy($attachmentId)
    {
        try {
            // Find the attachment or fail
            $attachment = Attachment::findOrFail($attachmentId);

            // Delete the attachment from storage
            Storage::delete($attachment->attachment_path);

            // Delete the attachment record from the database
            $attachment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Attachment deleted successfully.'
            ], HttpResponse::HTTP_NO_CONTENT);
        } catch (ModelNotFoundException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Attachment not found'
            ], HttpResponse::HTTP_NOT_FOUND);
        }
    }

    /**
     * Store a newly created comment for a specific attachment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $attachmentId
     * @return \Illuminate\Http\Response
     *
     * @OA\Post(
     *     path="/api/attachments/{attachmentId}/comments",
     *     summary="Add a new comment to an attachment",
     *     tags={"Attachments"},
     *     @OA\Parameter(
     *         name="attachmentId",
     *         in="path",
     *         description="ID of the attachment",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"comment"},
     *             @OA\Property(
     *                 property="comment",
     *                 description="The comment text",
     *                 type="string"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Comment added successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Comment added successfully."),
     *              @OA\Property(property="data", ref="#/components/schemas/Attachment_Comment")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Error: Attachment not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Attachment not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation error message"),
     *             @OA\Property(property="errors", type="object", example={"comment": {"The comment field is required."}})
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */

    public function storeComment(StoreAttachmentCommentRequest $request, $attachmentId)
    {
        $request->validate([
            'comment' => 'required|string'
        ]);

        try {
            $attachment = Attachment::findOrFail($attachmentId);

            $comment = new AttachmentComment([
                'attachment_id' => $attachmentId,
                'user_id' => Auth::id(),
                'comment' => $request->comment,
            ]);

            $attachment->comments()->save($comment);

            return response()->json([
                'success' => true,
                'message' => 'Comment added successfully.',
                'data' => $comment,
            ], HttpResponse::HTTP_CREATED);
        } catch (ModelNotFoundException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Attachment not found'
            ], HttpResponse::HTTP_NOT_FOUND);
        }
    }

    /**
     * Update an existing comment for a specific attachment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $attachmentId
     * @param  int  $commentId
     * @return \Illuminate\Http\Response
     *
     * @OA\Patch(
     *     path="/api/attachments/{attachmentId}/comments/{commentId}",
     *     summary="Update an existing comment on an attachment",
     *     tags={"Attachments"},
     *     @OA\Parameter(
     *         name="attachmentId",
     *         in="path",
     *         description="ID of the attachment",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="commentId",
     *         in="path",
     *         description="ID of the comment to update",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"comment"},
     *             @OA\Property(
     *                 property="comment",
     *                 description="The updated comment text",
     *                 type="string"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Comment updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Comment updated successfully."),
     *              @OA\Property(property="data", ref="#/components/schemas/Attachment_Comment")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Error: Attachment or Comment not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Attachment or Comment not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation error message"),
     *             @OA\Property(property="errors", type="object", example={"comment": {"The comment field is required."}})
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function updateComment(UpdateAttachmentCommentRequest $request, $attachmentId, $commentId)
    {
        $request->validate([
            'comment' => 'required|string'
        ]);

        try {
            // Find the attachment or fail
            $attachment = Attachment::findOrFail($attachmentId);

            // Find the comment or fail
            $comment = $attachment->comments()->findOrFail($commentId);

            // Update the comment text
            $comment->comment = $request->comment;
            $comment->save();

            return response()->json([
                'success' => true,
                'message' => 'Comment updated successfully.',
                'data' => $comment,
            ], HttpResponse::HTTP_OK);
        } catch (ModelNotFoundException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Attachment or Comment not found'
            ], HttpResponse::HTTP_NOT_FOUND);
        }
    }
    /**
     * Delete a comment from an attachment.
     *
     * @param  int  $attachmentId
     * @param  int  $commentId
     * @return \Illuminate\Http\Response
     *
     * @OA\Delete(
     *     path="/api/attachments/{attachmentId}/comments/{commentId}",
     *     summary="Delete a comment from an attachment",
     *     tags={"Attachments"},
     *     @OA\Parameter(
     *         name="attachmentId",
     *         in="path",
     *         description="ID of the attachment",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="commentId",
     *         in="path",
     *         description="ID of the comment to delete",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Comment deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Error: Attachment or Comment not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Attachment or Comment not found")
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function destroyComment($attachmentId, $commentId)
    {
        try {
            // Find the attachment or fail
            $attachment = Attachment::findOrFail($attachmentId);

            // Find the comment or fail
            $comment = $attachment->comments()->findOrFail($commentId);

            // Delete the comment
            $comment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Comment deleted successfully.'
            ], HttpResponse::HTTP_NO_CONTENT);
        } catch (ModelNotFoundException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Attachment or Comment not found'
            ], HttpResponse::HTTP_NOT_FOUND);
        }
    }
}
