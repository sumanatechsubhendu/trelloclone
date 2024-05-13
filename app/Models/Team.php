<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $fillable = ['name', 'description', 'team_head_id', 'created_by'];

    /**
     * Get the user who is the head of the team.
     */
    public function teamHead()
    {
        return $this->belongsTo(User::class, 'team_head_id');
    }

    /**
     * Get the user who created the team.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
