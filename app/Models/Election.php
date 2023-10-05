<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use function PHPUnit\Framework\containsIdentical;

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

            $electoralCoefficient = round ($totalElectionVotes/$slotsToAssign,2);


            //TODO: Obtenemos el número mágico, la parte entera y la parte decimal de cada elección.
        foreach ($boardsTotal as $boardTotal){

                $n = $boardTotal->total_votes/$electoralCoefficient;
                $whole = floor($n);      // 1
                $fraction = $n - $whole; // .25

                $boardTotal->magicNumber = round ($n,2);
                $boardTotal->wholeTotal = $whole;
                $boardTotal->fractionTotal = round ($fraction, 2);

        }

        //TODO: Evaluamos, LA ELECCIÓN TUVO ALGÚN EMPATE Ó NO?
        //TODO: Para eso ejecutamos el primer forEach para saberlo.




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


        //En dado caso de que la elección tenga más de una curul...
        else{

            //Si hubo empate:
            //En primer lugar, asignamos las curules para las votaciones que no hayan tenido empates
            $boards = $boardsTotal->sortByDesc('total_votes');

//            dd($boards);

            foreach ($boards as $boardTotal) {
                if(!property_exists($boardTotal, 'has_tie')){

                    //Aqui hacemos las asignaciones por la parte entera de cada curul..
                if ($boardTotal->wholeTotal > 0 && $slotsToAssign > 0){

                    $members = DB::table('board_members as bm')->where('board_id', '=', $boardTotal->board_id)
                        ->leftJoin('users as a', 'a.id', '=', 'bm.head_id')
                        ->leftJoin('users as b', 'b.id', '=', 'bm.substitute_id')
                        ->select('bm.*', 'a.name as head_name','b.name as substitute_name')
                        ->orderBy('priority', 'ASC')->take($boardTotal->wholeTotal)->get();

                    if(count($members) === 0){
                        continue;
                    }

                    $boardTotal->wholeMembers = $members;

                    $slotsToAssign -= count($members);
                }
                }
            }


            $shuffledBoards = $boards->shuffle()->all();

            //Luego de eso, hacemos un shuffle de las votaciones con empate y les asignamos sus curules ganas por parte entera
            foreach ($shuffledBoards as $boardTotal) {
                if(property_exists($boardTotal, 'has_tie')){
                    //Aqui hacemos las asignaciones por la parte entera de cada curul..
                    if ($boardTotal->wholeTotal > 0 && $slotsToAssign > 0){

                        $members = DB::table('board_members as bm')->where('board_id', '=', $boardTotal->board_id)
                            ->leftJoin('users as a', 'a.id', '=', 'bm.head_id')
                            ->leftJoin('users as b', 'b.id', '=', 'bm.substitute_id')
                            ->select('bm.*', 'a.name as head_name','b.name as substitute_name')
                            ->orderBy('priority', 'ASC')->take($boardTotal->wholeTotal)->get();

                        if(count($members) === 0){
                            continue;
                        }

                        $umbralMayorDelEmpate = $boardTotal->fractionTotal;
                        $boardTotal->wholeMembers = $members;
                        $boardTotal->hadRandomAssignment = true;
                        $slotsToAssign -= count($members);
                    }
                }
            }


            //EN ESTE PUNTO, YA SE REALIZARON TODAS LAS ASIGNACIONES POR PARTE ENTERA DE LAS VOTACIONES QUE NO PRESENTARON EMPATE
            // Y DE LAS QUE SÍ TAMBIÉN.

            //AHORA HAREMOS LAS ASIGNACIONES DE LAS CURÚLES RESTANTES POR PARTE DECIMAL
            //EN PRIMER LUGAR LE ASIGNAMOS LAS CURULES A LAS PLANCHAS QUE NO TUVIERON EMPATE.

            $boards = $boardsTotal->sortByDesc('fractionTotal');

            foreach ($boards as $board) {

                if (!property_exists($board, 'has_tie')) {
                    $arrayOfIdsSelectedMembers = [];
                    if (property_exists($board, 'wholeMembers')) {
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

                            //Si ya no tiene más renglones disponibles, se le asigna a la siguiente con la parte decimal más alta.
                            if ($membersToAssign) {
                                $board->wholeMembers->push($membersToAssign);
                                $slotsToAssign--;
                            }
                        }
                    } else {
                        if ($slotsToAssign > 0 && $board->fractionTotal > $umbralMayorDelEmpate) {
                            $members = DB::table('board_members as bm')->where('board_id', '=', $board->board_id)
                                ->leftJoin('users as a', 'a.id', '=', 'bm.head_id')
                                ->leftJoin('users as b', 'b.id', '=', 'bm.substitute_id')
                                ->select('bm.*', 'a.name as head_name', 'b.name as substitute_name')
                                ->orderBy('priority', 'ASC')->take(1)->get();

                            if (count($members) > 0) {
                                $board->wholeMembers = $members;
                                $slotsToAssign--;
                            }
                        }
                    }
                }
            }




            //AHORA HACEMOS LA ASGINACIÓN POR PARTE DECIMAL PARA LAS PLANCHAS QUE PRESENTARON ALGUN EMPATE.
            // PARA ESTO, LO QUE SE DEBE HACER ES... ORGANIZAR LAS CURULES QUE TENGAN has_tie de manera aleatoria, e ir asignando y
            // comparando en todo momento con el $slotsToAssign (cuantas curules quedan disponibles)

            $shuffledBoardsAgain = $boards->shuffle()->all();

            foreach ($shuffledBoardsAgain as $board) {
                $arrayOfIdsSelectedMembers = [];
                if (property_exists($board, 'has_tie')) {
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

                            //Si ya no tiene más renglones disponibles para meter la curul que se ganó, se le cede a la siguiente en la lista aleatoria
                            if($membersToAssign){
                                $board->wholeMembers->push($membersToAssign);
                                $slotsToAssign--;
                            }
                        }
                    }

                    else{
                        if ($slotsToAssign > 0) {
                            $members = DB::table('board_members as bm')->where('board_id', '=', $board->board_id)
                                ->leftJoin('users as a', 'a.id', '=', 'bm.head_id')
                                ->leftJoin('users as b', 'b.id', '=', 'bm.substitute_id')
                                ->select('bm.*', 'a.name as head_name', 'b.name as substitute_name')
                                ->orderBy('priority', 'ASC')->take(1)->get();

                            if (count($members) > 0){
                                $board->wholeMembers = $members;
                                $slotsToAssign--;
                            }
                        }
                    }
                }
            }
        }
//                    if(property_exists($board, 'wholeMembers')){
//                        $alreadySelectedMembers = $board->wholeMembers;
//
//                        foreach ($alreadySelectedMembers as $alreadySelectedMember) {
//                            $arrayOfIdsSelectedMembers [] = $alreadySelectedMember->id;
//                        }
//
//                        if ($slotsToAssign > 0) {
//                            $membersToAssign = DB::table('board_members as bm')->where('bm.board_id', '=', $board->board_id)
//                                ->whereNotIn('bm.id', $arrayOfIdsSelectedMembers)
//                                ->leftJoin('users as a', 'a.id', '=', 'bm.head_id')
//                                ->leftJoin('users as b', 'b.id', '=', 'bm.substitute_id')
//                                ->select('bm.*', 'a.name as head_name', 'b.name as substitute_name')
//                                ->orderBy('priority', 'ASC')->take(1)->first();
//
//                            //Si ya no tiene más renglones disponibles para meter la curul que se ganó, se le cede esa curul a la anterior plancha a esta (la que tenía la parte decimal mayor)
//                            if (!$membersToAssign){
//
//                                $boardFractionReference = $board->fractionTotal;
//                                $boardsToKnowPreviousBoard = [];
//
//                                $boardsDelMasBajoAlMasAltoFraction = $boardsTotal->sortBy('fractionTotal');
//
//                                foreach ($boardsDelMasBajoAlMasAltoFraction as $board2ndLoop) {
//                                    if($board2ndLoop->fractionTotal > $boardFractionReference){
//                                        $boardsToKnowPreviousBoard [] = $board2ndLoop;
//                                        break;
//                                    }
//                                }
//
//                                $boardQueSeVaAQuedarConLaCurulSobrante = $boardsToKnowPreviousBoard[0];
//
//                                $arrayOfIdsSelectedMembers = [];
//                                $alreadySelectedMembers = $boardQueSeVaAQuedarConLaCurulSobrante->wholeMembers;
//
//                                foreach ($alreadySelectedMembers as $alreadySelectedMember) {
//                                    $arrayOfIdsSelectedMembers [] = $alreadySelectedMember->id;
//                                }
//
//                                $membersToAssign = DB::table('board_members as bm')->where('bm.board_id', '=', $boardQueSeVaAQuedarConLaCurulSobrante->board_id)
//                                    ->whereNotIn('bm.id', $arrayOfIdsSelectedMembers)
//                                    ->leftJoin('users as a', 'a.id', '=', 'bm.head_id')
//                                    ->leftJoin('users as b', 'b.id', '=', 'bm.substitute_id')
//                                    ->select('bm.*', 'a.name as head_name', 'b.name as substitute_name')
//                                    ->orderBy('priority', 'ASC')->take(1)->first();
//
//                                if ($membersToAssign){
//
////                                    dd($boardQueSeVaAQuedarConLaCurulSobrante);
//
//                                    $correctBoard = $boards->where('board_id', '=', $boardQueSeVaAQuedarConLaCurulSobrante->board_id)->first();
//
//                                    $correctBoard->wholeMembers->push($membersToAssign);
//                                    $slotsToAssign--;
//                                    continue;
//                                }
//                            }
//
//                            $board->wholeMembers->push($membersToAssign);
//                            $slotsToAssign--;
//                        }
//                    }
//
//                    else{
//                        if ($slotsToAssign > 0) {
//                            $members = DB::table('board_members as bm')->where('board_id', '=', $board->board_id)
//                                ->leftJoin('users as a', 'a.id', '=', 'bm.head_id')
//                                ->leftJoin('users as b', 'b.id', '=', 'bm.substitute_id')
//                                ->select('bm.*', 'a.name as head_name', 'b.name as substitute_name')
//                                ->orderBy('priority', 'ASC')->take(1)->get();
//
//                            if (count($members) === 0){
//                                continue;
//                            }
//
//                            $board->wholeMembers = $members;
//                            $slotsToAssign--;
//                        }
//                    }


//            $randomAssigned = false;
//
//                    while($randomAssigned){
//
//                        $randomBoard = $boardsWithTie[array_rand($boardsWithTie)];
//                        $arrayOfIdsSelectedMembers = [];
//
//
//                        //Si ya había algun elegido, entonces se agrega a esa lista y se traen los que ya estaban para no volver a seleccionarlos
//                        if(property_exists($randomBoard, 'wholeMembers')){
//                            $alreadySelectedMembers = $randomBoard->wholeMembers;
//
//                            foreach ($alreadySelectedMembers as $alreadySelectedMember) {
//                                $arrayOfIdsSelectedMembers [] = $alreadySelectedMember->id;
//                            }
//
//                            $membersToAssign = DB::table('board_members as bm')->where('bm.board_id', '=', $randomBoard->board_id)
//                                ->whereNotIn('bm.id', $arrayOfIdsSelectedMembers)
//                                ->leftJoin('users as a', 'a.id', '=', 'bm.head_id')
//                                ->leftJoin('users as b', 'b.id', '=', 'bm.substitute_id')
//                                ->select('bm.*', 'a.name as head_name', 'b.name as substitute_name')
//                                ->orderBy('priority', 'ASC')->take(1)->first();
//
//                            if($membersToAssign){
//                                $randomBoard->wholeMembers->push($membersToAssign);
//                                $slotsToAssign--;
//                                $randomAssigned = true;
//                                $randomBoard->tie_winner = 1;
//                            }
//
//                            //Si la plancha que se ganó la curul debido al empate ya no tiene más renglones, se vuelva a sortear
//                        }
//
//
//                        else {
//
//                            if ($slotsToAssign > 0){
//                                //Si no, simplemente se agrega el primero.
//                                $membersToAssign = DB::table('board_members as bm')->where('bm.board_id', '=', $randomBoard->board_id)
//                                    ->leftJoin('users as a', 'a.id', '=', 'bm.head_id')
//                                    ->leftJoin('users as b', 'b.id', '=', 'bm.substitute_id')
//                                    ->select('bm.*', 'a.name as head_name', 'b.name as substitute_name')
//                                    ->orderBy('priority', 'ASC')->take(1)->first();
//
//                                if($membersToAssign){
//                                    $randomBoard->wholeMembers->push($membersToAssign);
//                                    $slotsToAssign--;
//                                    $randomAssigned = true;
//                                    $randomBoard->tie_winner = 1;
//                                }
//                            }
//                        }
//
//                    }
                  //Fin de la lógica por si hubo empate.


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

        foreach ($finalBoards as $board){
            if (property_exists($board, 'has_tie')) {
                $electionFinalData->thereWasRandomAssignment = true;
            }
        }

//        dd($electionFinalData);

        return $electionFinalData;

    }


}
