<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Workspace extends Model
{
    protected $fillable = ['name', 'admin_id', 'created_by'];

    /**
     * Get the user who is the admin of the workspace.
     */
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Get the user who created the workspace.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
