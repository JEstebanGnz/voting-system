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
        'max_lines',
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

        $boards = $activeElection->boards;


        foreach ($boards as $board){

            $lines = DB::table('board_members as bm')->where('board_id', '=', $board->id)
                ->leftJoin('users as a', 'a.id', '=', 'bm.head_id')
                ->leftJoin('users as b', 'b.id', '=', 'bm.substitute_id')
                ->select('bm.*', 'a.name as head_name','b.name as substitute_name')
                ->orderBy('priority', 'ASC')->get();

            if(count($lines) === 0){
                continue;
            }

            $board['lines'] = $lines;

        }

        if ($activeElection){
            return response()->json($activeElection);
        }

        return null;
    }

    public function getVotingReport($election)
    {
             $boardsTotal = DB::table('votes as v')->select(['v.board_id', 'b.description', DB::raw('COUNT(*) AS total_votes')])
            ->where('v.election_id', '=', $this->id)->join('users as u', 'v.user_id', '=', 'u.id')
            ->join('boards as b', 'v.board_id', '=','b.id')
            ->where('u.has_payment','=',true)
            ->groupBy('b.description', 'v.board_id')
            ->orderByRaw('(total_votes) desc')->get();

             $totalElectionVotes = 0;
             $originalSlotsToAssign =$election->max_lines;
             $slotsToAssign = $election->max_lines;

             foreach ($boardsTotal as $boardTotal){
                  $totalElectionVotes += $boardTotal->total_votes;
             }

            $electoralCoefficient = $totalElectionVotes/$slotsToAssign;

            foreach ($boardsTotal as $boardTotal){

                $n = $boardTotal->total_votes/$electoralCoefficient;
                $whole = floor($n);      // 1
                $fraction = $n - $whole; // .25

                $boardTotal->magicNumber = round ($n,2);
                $boardTotal->wholeTotal = $whole;
                $boardTotal->fractionTotal = round ($fraction, 2);

                if ($boardTotal->wholeTotal > 0){

                    $members = DB::table('board_members as bm')->where('board_id', '=', $boardTotal->board_id)
                        ->leftJoin('users as a', 'a.id', '=', 'bm.head_id')
                        ->leftJoin('users as b', 'b.id', '=', 'bm.substitute_id')
                        ->select('bm.*', 'a.name as head_name','b.name as substitute_name')
                        ->orderBy('priority', 'ASC')->take($boardTotal->wholeTotal)->get();

                    $boardTotal->wholeMembers = $members;

                    $slotsToAssign -= count($members);
                }
            }

            //Si hubo curules por asignar luego de haber asignado con las partes enteras, procedemos a hacer las asignaciones con los residuos.

            $boards = $boardsTotal->sortByDesc('fractionTotal');

            if ($slotsToAssign > 0) {
                $counter = 0;

                foreach ($boards as $board) {

                    $arrayOfIdsSelectedMembers = [];

                    if(property_exists($board, 'wholeMembers')){

                        $alreadySelectedMembers = $board->wholeMembers;

                        foreach ($alreadySelectedMembers as $alreadySelectedMember) {
                            $arrayOfIdsSelectedMembers [] = $alreadySelectedMember->id;
                        }
                    }

                    if ($counter <= $slotsToAssign) {

                        $membersToAssign = DB::table('board_members as bm')->where('bm.board_id', '=', $board->board_id)
                            ->whereNotIn('bm.id', $arrayOfIdsSelectedMembers)
                            ->leftJoin('users as a', 'a.id', '=', 'bm.head_id')
                            ->leftJoin('users as b', 'b.id', '=', 'bm.substitute_id')
                            ->select('bm.*', 'a.name as head_name', 'b.name as substitute_name')
                            ->orderBy('priority', 'ASC')->take(1)->first();

                        $board->wholeMembers->push($membersToAssign);

                        $slotsToAssign--;
                        $counter++;

                    }

                }
            }

        foreach ($boards as $board) {
            if (property_exists($board, 'wholeMembers')) {
                $board->totalWonPositions = count($board->wholeMembers);
            }
            else{
                $board->totalWonPositions = 0;
            }

        }

        $finalBoards = $boards->sortByDesc('wholeTotal');

        $electionFinalData = (object)[];
        $electionFinalData->electionSlots = $originalSlotsToAssign;
        $electionFinalData->electionVotes = $totalElectionVotes;
        $electionFinalData->electionCoefficient = $electoralCoefficient;
        $electionFinalData->electionFinalBoards = $finalBoards;

        return $electionFinalData;

    }


}
