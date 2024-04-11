<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Districts extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'districts';

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
        'name',
        'ext_district_id',
        'identifier',
        'integration_id',
        'addr_street',
        'addr_unit',
        'addr_postal_code',
        'addr_city',
        'addr_state',
        'addr_country',
        'phone',
        'time_zone',
        'properties',
        'ext_updated_at',
        'deleted_at'
    ];
}