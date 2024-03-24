<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Schools extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'schools';

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
        'sourcedId',
        'status',
        'name',
        'dateLastModified',
        'identifier',
        'type',
        'ext_updated_at',
        'deleted_at'
    ];
}
