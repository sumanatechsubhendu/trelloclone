<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Card extends Model
{
    protected $fillable = [
        'title', 'description', 'board_section_id', 'position_id', 'created_by'
    ];

    // Define relationships if any
}
