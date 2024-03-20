<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Integrations extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'integrations';
    /**
     * Indicates if all mass assignment is enabled.
     *
     * @var bool
     */
    protected static $unguarded = true;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'id',
        'ext_integration_id',
        'access_token',
        'ext_source_id',
        'ext_source_name',
        'deleted_at'
    ];
}
