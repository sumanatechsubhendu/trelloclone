<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Workspace extends Model
{
    protected $fillable = ['name', 'admin_id', 'created_by'];
    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['workspace_image'];

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

    public function boards()
    {
        return $this->hasMany(Board::class, 'workspace_id'); // Specify the foreign key column name
    }

    public function image()
    {
        return $this->morphOne(Image::class, 'imageable');
    }

    public function getWorkspaceImageAttribute()
    {
        if ($this->image) {
            return asset('storage/' . $this->image->image_path);
        }

        // If there is no associated image, return a default placeholder image or null
        // Example: return asset('storage/default-placeholder.jpg');
        return null;
    }
}
