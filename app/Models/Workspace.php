<?php

namespace App\Models;
use Illuminate\Support\Str;

use Illuminate\Database\Eloquent\Model;

class Workspace extends Model
{
    protected $fillable = ['name', 'bg_color', 'admin_id', 'created_by','slug'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($workspace) {
            $workspace->slug = static::generateUniqueSlug($workspace->name);
        });

        static::updating(function ($workspace) {
            if ($workspace->isDirty('name')) {
                $workspace->slug = static::generateUniqueSlug($workspace->name);
            }
        });
    }

    protected static function generateUniqueSlug($name)
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;
        
        while (static::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }
        
        return $slug;
    }

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

    public function members()
    {
        return $this->hasMany(WorkspaceMember::class, 'workspace_id');
    }
}
