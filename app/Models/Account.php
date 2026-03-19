<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Account extends Model
{
    /**
     * Eloquent model for game accounts
     * Maps to external SA:MP accounts table
     */
    protected $table = 'accounts';
    protected $primaryKey = 'Id';
    protected $fillable = ['playerName'];
    public $timestamps = false;

    /**
     * Scope: Find account by player name (case-insensitive)
     * 
     * SECURITY: Uses database-level LOWER() function to ensure
     * case-insensitive comparison and allow proper indexing
     *
     * @param Builder $query
     * @param string $playerName Player name to search for
     * @return Builder
     */
    public function scopeByPlayerName(Builder $query, string $playerName): Builder
    {
        return $query->whereRaw(
            'LOWER(playerName) = ?',
            [strtolower($playerName)]
        );
    }
}

