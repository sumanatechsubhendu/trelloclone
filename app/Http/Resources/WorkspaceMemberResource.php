<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkspaceMemberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'workspace_id' => $this->workspace_id,
            'user_id' => $this->user_id,
            'created_at' => $this->created_at,
            'user' => new UserResource($this->user), // Assuming UserResource is another resource class
            'workspace' => new WorkspaceResource($this->workspace), // Assuming WorkspaceResource is another resource class
        ];
    }
}
