<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CardMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'card_id',
        'workspace_member_id',
    ];

    public function card()
    {
        return $this->belongsTo(Card::class);
    }

    public function workspaceMember()
    {
        return $this->belongsTo(WorkspaceMember::class, 'workspace_member_id');
    }
}
