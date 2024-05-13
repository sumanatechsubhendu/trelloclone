<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoardSection extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'board_id', 'section_id', 'position', 'created_by'
    ];

    /**
     * Get the board that owns the board section.
     */
    public function board()
    {
        return $this->belongsTo(Board::class);
    }

    /**
     * Get the section that owns the board section.
     */
    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * Get the user who created the board section.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

