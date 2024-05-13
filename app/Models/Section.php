<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $table = 'sections';

    protected $fillable = [
        'title', 'position', 'type', 'created_by'
    ];

    // Define relationships or additional methods here if needed
}
