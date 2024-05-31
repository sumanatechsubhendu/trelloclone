<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Board extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'bg_color', 'workspace_id', 'admin_id', 'created_by'];

    // Define relationships if needed
    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function sections()
    {
        return $this->hasMany(BoardSection::class, 'board_id', 'id'); // Specify the foreign key column name
    }

    public function cards()
    {
        return $this->hasManyThrough(Card::class, BoardSection::class, 'board_id', 'board_section_id', 'id', 'id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
