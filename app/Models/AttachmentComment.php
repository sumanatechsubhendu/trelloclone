<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttachmentComment extends Model
{
    use HasFactory;

    protected $fillable = ['attachment_id', 'user_id', 'comment'];

    public function attachment()
    {
        return $this->belongsTo(Attachment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
