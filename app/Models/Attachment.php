<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = ['attachable_id', 'attachable_name', 'attachable_type', 'attachment_path', 'file_type', 'user_id'];

    // Attachment belongs to a polymorphic model
    public function attachable()
    {
        return $this->morphTo();
    }

    public function comments()
    {
        return $this->hasMany(AttachmentComment::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
