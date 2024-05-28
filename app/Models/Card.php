<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Card extends Model
{
    protected $fillable = [
        'title', 'description', 'board_section_id', 'position_id', 'created_by'
    ];

    public function boardSection()
    {
        return $this->belongsTo(BoardSection::class, 'position_id');
    }

    public function board()
    {
        return $this->hasOneThrough(Board::class, BoardSection::class, 'id', 'id', 'board_section_id', 'board_id');
    }

    public function members()
    {
        return $this->belongsToMany(WorkspaceMember::class, 'card_members', 'card_id', 'workspace_member_id');
    }

    // Define relationships if any
}
