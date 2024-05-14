<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class PersonalAccessToken extends Model
{
    //use SoftDeletes, HasApiTokens;

    protected $table = 'personal_access_tokens';

    protected $dates = ['deleted_at'];

}
