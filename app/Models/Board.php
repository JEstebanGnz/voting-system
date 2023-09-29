<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Board extends Model
{
    use HasFactory;

    protected $fillable = [
        'description',
        'election_id',
    ];

    //Relación uno a muchos (inversa)
    public function election(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function votes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Vote::class);
    }


    public static function getBoardMembers ($boardId){

        return self::where('id', $boardId)->with('board')->first();

    }


}
