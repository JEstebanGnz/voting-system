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

        if ($activeElection){

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
             $votesChecker = 0;


            foreach ($boardsTotal as $boardTotal){

                $counter = 0;
                $totalElectionVotes += $boardTotal->total_votes;
                $votesChecker = $boardTotal->total_votes;

                foreach ($boardsTotal as $boardTotalFindTie) {

                    if ($boardTotalFindTie->total_votes === $votesChecker){
                        $counter++;
                    }

                    if ($boardTotalFindTie->total_votes === $votesChecker && $counter > 1){
                        $boardTotal->has_tie=1;
                    }
                }
            }

            $electoralCoefficient = $totalElectionVotes/$slotsToAssign;


        foreach ($boardsTotal as $boardTotal){

                $n = $boardTotal->total_votes/$electoralCoefficient;
                $whole = floor($n);      // 1
                $fraction = $n - $whole; // .25

                $boardTotal->magicNumber = round ($n,2);
                $boardTotal->wholeTotal = $whole;
                $boardTotal->fractionTotal = round ($fraction, 2);

                //Aqui hacemos las asignaciones por la parte entera de cada curul..

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


        //Si la elección es para una sola curul, incluímos primero la lógica de asignar las curules por la parte decimal y luego si llega a haber empate
        if($election->max_lines === 1){

            $boards = $boardsTotal->sortByDesc('fractionTotal');

            $counter = 0;
            //Si hubo curules por asignar luego de haber asignado con las partes enteras, procedemos a hacer las asignaciones con los residuos.
            foreach ($boards as $board) {

                $arrayOfIdsSelectedMembers = [];
                if(property_exists($board, 'wholeMembers')){
                    $alreadySelectedMembers = $board->wholeMembers;

                    foreach ($alreadySelectedMembers as $alreadySelectedMember) {
                        $arrayOfIdsSelectedMembers [] = $alreadySelectedMember->id;
                    }

                    if ($slotsToAssign > 0) {
                        $membersToAssign = DB::table('board_members as bm')->where('bm.board_id', '=', $board->board_id)
                            ->whereNotIn('bm.id', $arrayOfIdsSelectedMembers)
                            ->leftJoin('users as a', 'a.id', '=', 'bm.head_id')
                            ->leftJoin('users as b', 'b.id', '=', 'bm.substitute_id')
                            ->select('bm.*', 'a.name as head_name', 'b.name as substitute_name')
                            ->orderBy('priority', 'ASC')->take(1)->first();

                        $board->wholeMembers->push($membersToAssign);

                        $slotsToAssign--;
                    }
                }

                else{
                    if ($slotsToAssign > 0) {
                        $members = DB::table('board_members as bm')->where('board_id', '=', $board->board_id)
                            ->leftJoin('users as a', 'a.id', '=', 'bm.head_id')
                            ->leftJoin('users as b', 'b.id', '=', 'bm.substitute_id')
                            ->select('bm.*', 'a.name as head_name', 'b.name as substitute_name')
                            ->orderBy('priority', 'ASC')->take(1)->get();

                        $board->wholeMembers = $members;
                        $slotsToAssign--;
                    }
                }
            }

            //Si hubo empate, entonces seleccionamos mediante la suerte cuál será la elección ganadora.
            $boards = $boardsTotal->sortByDesc('total_votes');

            if ($slotsToAssign > 0) {
                $boardsWithTie = [];
                foreach ($boards as $board) {
                    if(property_exists($board, 'has_tie')) {
                        $boardsWithTie [] = $board;
                    }
                }

                if (count($boardsWithTie) > 0){

                    $randomBoard = $boardsWithTie[array_rand($boardsWithTie)];
                    $arrayOfIdsSelectedMembers = [];
                    $randomBoard->tie_winner = 1;

                    if(property_exists($randomBoard, 'wholeMembers')){
                        $alreadySelectedMembers = $randomBoard->wholeMembers;

                        foreach ($alreadySelectedMembers as $alreadySelectedMember) {
                            $arrayOfIdsSelectedMembers [] = $alreadySelectedMember->id;
                        }

                        if ($slotsToAssign > 0) {

                            $membersToAssign = DB::table('board_members as bm')->where('bm.board_id', '=', $randomBoard->board_id)
                                ->whereNotIn('bm.id', $arrayOfIdsSelectedMembers)
                                ->leftJoin('users as a', 'a.id', '=', 'bm.head_id')
                                ->leftJoin('users as b', 'b.id', '=', 'bm.substitute_id')
                                ->select('bm.*', 'a.name as head_name', 'b.name as substitute_name')
                                ->orderBy('priority', 'ASC')->take(1)->first();

                            $randomBoard->wholeMembers->push($membersToAssign);
                            $slotsToAssign--;
                        }
                    }

                    else{
                        if ($slotsToAssign > 0){
                        $membersToAssign = DB::table('board_members as bm')->where('bm.board_id', '=', $randomBoard->board_id)
                            ->leftJoin('users as a', 'a.id', '=', 'bm.head_id')
                            ->leftJoin('users as b', 'b.id', '=', 'bm.substitute_id')
                            ->select('bm.*', 'a.name as head_name', 'b.name as substitute_name')
                            ->orderBy('priority', 'ASC')->take(1)->first();

                        $randomBoard->wholeMembers = $membersToAssign;
                        $slotsToAssign--;
                        }
                    }
                }
            }

        }

        //En cambio, si la elección tiene más de una curúl, entonces implementamos primero la lógica de calcular si hubo empate y luego asignar los decimales.
        else{
            //Si hubo empate, entonces seleccionamos mediante la suerte cuál será la elección ganadora.
            $boards = $boardsTotal->sortByDesc('total_votes');

            if ($slotsToAssign > 0) {
                $boardsWithTie = [];
                foreach ($boards as $board) {
                    if(property_exists($board, 'has_tie')) {
                        $boardsWithTie [] = $board;
                    }
                }

                if (count($boardsWithTie) > 0){

                    $randomBoard = $boardsWithTie[array_rand($boardsWithTie)];
                    $arrayOfIdsSelectedMembers = [];

                    $randomBoard->tie_winner = 1;
                    //Si ya había algun elegido, entonces se agrega a esa lista y se traen los que ya estaban para no volver a seleccionarlos
                    if(property_exists($randomBoard, 'wholeMembers')){
                        $alreadySelectedMembers = $randomBoard->wholeMembers;

                        foreach ($alreadySelectedMembers as $alreadySelectedMember) {
                            $arrayOfIdsSelectedMembers [] = $alreadySelectedMember->id;
                        }

                        if ($slotsToAssign > 0) {

                            $membersToAssign = DB::table('board_members as bm')->where('bm.board_id', '=', $randomBoard->board_id)
                                ->whereNotIn('bm.id', $arrayOfIdsSelectedMembers)
                                ->leftJoin('users as a', 'a.id', '=', 'bm.head_id')
                                ->leftJoin('users as b', 'b.id', '=', 'bm.substitute_id')
                                ->select('bm.*', 'a.name as head_name', 'b.name as substitute_name')
                                ->orderBy('priority', 'ASC')->take(1)->first();

                            $randomBoard->wholeMembers->push($membersToAssign);
                            $slotsToAssign--;
                        }
                    }


                    else {

                        if ($slotsToAssign > 0){
                            //Si no, simplemente se agrega el primero.
                            $membersToAssign = DB::table('board_members as bm')->where('bm.board_id', '=', $randomBoard->board_id)
                                ->leftJoin('users as a', 'a.id', '=', 'bm.head_id')
                                ->leftJoin('users as b', 'b.id', '=', 'bm.substitute_id')
                                ->select('bm.*', 'a.name as head_name', 'b.name as substitute_name')
                                ->orderBy('priority', 'ASC')->take(1)->first();

                            $randomBoard->wholeMembers = $membersToAssign;
                            $slotsToAssign--;
                        }
                    }
                }
            }

            $boards = $boardsTotal->sortByDesc('fractionTotal');

            //Si hubo curules por asignar luego de haber asignado con las partes enteras, procedemos a hacer las asignaciones con los residuos.
            foreach ($boards as $board) {

                $arrayOfIdsSelectedMembers = [];
                if(property_exists($board, 'wholeMembers')){
                    $alreadySelectedMembers = $board->wholeMembers;

                    foreach ($alreadySelectedMembers as $alreadySelectedMember) {
                        $arrayOfIdsSelectedMembers [] = $alreadySelectedMember->id;
                    }

                    if ($slotsToAssign > 0) {
                        $membersToAssign = DB::table('board_members as bm')->where('bm.board_id', '=', $board->board_id)
                            ->whereNotIn('bm.id', $arrayOfIdsSelectedMembers)
                            ->leftJoin('users as a', 'a.id', '=', 'bm.head_id')
                            ->leftJoin('users as b', 'b.id', '=', 'bm.substitute_id')
                            ->select('bm.*', 'a.name as head_name', 'b.name as substitute_name')
                            ->orderBy('priority', 'ASC')->take(1)->first();

                        $board->wholeMembers->push($membersToAssign);
                        $slotsToAssign--;
                    }
                }

                else{
                    if ($slotsToAssign > 0) {
                        $members = DB::table('board_members as bm')->where('board_id', '=', $board->board_id)
                            ->leftJoin('users as a', 'a.id', '=', 'bm.head_id')
                            ->leftJoin('users as b', 'b.id', '=', 'bm.substitute_id')
                            ->select('bm.*', 'a.name as head_name', 'b.name as substitute_name')
                            ->orderBy('priority', 'ASC')->take(1)->get();

                        $board->wholeMembers = $members;
                        $slotsToAssign--;
                    }
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

        $finalBoards = $boards->sortBy([['wholeTotal','desc'],['totalWonPositions','desc']]);

        $electionFinalData = (object)[];
        $electionFinalData->electionSlots = $originalSlotsToAssign;
        $electionFinalData->electionVotes = $totalElectionVotes;
        $electionFinalData->electionCoefficient = $electoralCoefficient;
        $electionFinalData->electionFinalBoards = $finalBoards;


        return $electionFinalData;

    }


}
