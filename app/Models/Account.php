<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    /**
     * Eloquent model for game accounts
     */
    protected $table = 'accounts';
    protected $primaryKey = 'Id';
    protected $fillable = ['playerName'];
    public $timestamps = false;
}
