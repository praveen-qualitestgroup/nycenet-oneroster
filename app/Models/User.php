<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $table = 'users';

    /**
     * Indicates if all mass assignment is enabled.
     *
     * @var bool
     */
    protected static $unguarded = true;

    /**
     * The attributes that are mass assignable
     */
    protected $fillable = [
        'ext_user_id',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'phone',
        'birthday',
        'gender',
        'picture_url',
        'graduation_year',
        "addr_street",
        "addr_unit",
        "addr_postal_code",
        "addr_city",
        "addr_state",
        "addr_country",
        'properties',
        'time_zone',
        'ext_updated_at',
        'deleted_at'
    ];
}
