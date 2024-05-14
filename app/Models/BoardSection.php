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
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['section_title'];


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

    public function getSectionTitleAttribute()
    {
        return $this->section->title;
    }
}

