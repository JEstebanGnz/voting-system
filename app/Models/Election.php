<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Election extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];


    //Relación uno a muchos (una elección puede tenre varias planchas)
    public function boards(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Board::class);
    }

    public function votes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public static function getActiveElection()
    {
        $activeElection = self::where('is_active', '=', 1)->with('boards')->first();

        if ($activeElection){
            return response()->json($activeElection);
        }

        return null;
    }

    public function getVotingReport()
    {
        return DB::table('votes as v')->select(['v.board_id', 'b.description', DB::raw('COUNT(*) AS total_votes')])
            ->where('v.election_id', '=', $this->id)->join('users as u', 'v.user_id', '=', 'u.id')
            ->join('boards as b', 'v.board_id', '=','b.id')
            ->where('u.has_payment','=',true)
            ->groupBy('b.description', 'v.board_id')
            ->orderByRaw('(total_votes) desc')->get();
    }


}
