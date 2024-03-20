<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Classes extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'classrooms';

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
        'ext_class_id',
        'school_id',
        'name',
        'ext_updated_at',
        'deleted_at'
    ];
}
