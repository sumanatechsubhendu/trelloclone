<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkspaceMember extends Model
{
    use HasFactory;

    protected $table = 'workspace_members';

    protected $fillable = [
        'workspace_id',
        'user_id',
    ];

    // Define relationships if necessary
    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function boards()
    {
        return $this->hasMany(Board::class, 'workspace_id', 'workspace_id'); // Specify the foreign key column name
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
