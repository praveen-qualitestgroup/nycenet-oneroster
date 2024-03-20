<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserClass extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'user_classrooms';

    /**
     * Indicates if all mass assignment is enabled.
     *
     * @var bool
     */
    protected static $unguarded = true;

    /*
     * The attributes that are mass assignable
     */
    protected $fillable = [
        'id',
        'user_id',
        'classroom_id',
        'deleted_at'
    ];
}
