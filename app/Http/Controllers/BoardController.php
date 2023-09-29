<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Election;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BoardController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        DB::table('boards as b')->select(['b.description', 'e.name as election_name', 'e.id as election_id'])
            ->join('elections as e', 'b.election_id', '=', 'e.id')->get();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Board::create($request->all());
        return response()->json(['message' => 'La plancha se ha creado exitosamente']);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {

    }

    public function editBoardView(Election $election, Board $board)
    {
        return Inertia::render('Boards/ManageBoard', ['election' => $election,'board' => $board]);
    }


    public function saveBoardMembers(Request $request, Board $board)
    {
        $data = $request->input('data');
        try {
            DB::table('board_members')->updateOrInsert(['board_id' => $board->id, 'priority' => $data['priority'] ],
                ['head_id' => $data['head_id'], 'substitute_id' => $data['substitute_id']]);
        } catch (\Exception $e){
            return response()->json(['message' => 'Ha ocurrido el siguiente error: ' . $e->getMessage()],400);
        }
        if( array_key_exists('editing', $data)){
            return response()->json(['message' => 'Renglón actualizado correctamente']);
        }
        return response()->json(['message' => 'Renglón agregado correctamente']);
    }

    public function deleteBoardLine(Request $request, Board $board)
    {
        $data = $request->input('data');

        try {
            DB::table('board_members')->where('board_id', '=', $board->id)
                ->where('head_id', '=', $data['head_id'])->where('substitute_id', '=', $data['substitute_id'])->delete();
            //Now let's reassign the board priorities.
            $boardLines = DB::table('board_members')->where('board_id', '=', $board->id)->orderBy('priority', 'ASC')->get();
            if(count($boardLines) > 0){
                $counter = 1;
                foreach ($boardLines as $line){
                    DB::table('board_members')->where('id', '=', $line->id)->update(['priority' => $counter]);
                    $counter++;
                }
            }
        }catch (\Exception $e){
            return response()->json(['message' => 'Ha ocurrido el siguiente error: ' . $e->getMessage()],400);
        }
        return response()->json(['message' => 'Renglón borrado correctamente']);

    }

    public function updateBoardPriorities(Request $request, Board $board)
    {

        $oldPosition = $request->input('data')['old_position'];
        $newPosition = $request->input('data')['new_position'];
        $lineId = $request->input('data')['line_id'];
        $headId =   $request->input('data')['head_id'];
        $substituteId=  $request->input('data')['substitute_id'];

        if($newPosition > $oldPosition){

            $toChangeLines = DB::table('board_members')->where('board_id', '=', $board->id)
                ->where('priority', '>=', $oldPosition)->where('priority', '<=', $newPosition)
                ->where('id', '!=', $lineId)->orderBy('priority', 'ASC')->get();

            //Entonces le están bajando la prioridad (la estamos pasando del renglón 2 al 4 por ejemplo)
            //Por ello debemos restarle en uno la prioridad a todos

            foreach ($toChangeLines as $line){
                DB::table('board_members')->where('id', '=', $line->id)->update(['priority' => $line->priority-1]);
            }

            DB::table('board_members')->updateOrInsert(['head_id' => $headId , 'substitute_id' => $substituteId],['priority' => $newPosition]);

            return response()->json(['message' => 'Posición actualizada correctamente']);
        }

        if($newPosition < $oldPosition){

            $toChangeLines = DB::table('board_members')->where('board_id', '=', $board->id)
                ->where('priority', '<=', $oldPosition)->where('priority', '>=', $newPosition)
                ->where('id', '!=', $lineId)->orderBy('priority', 'ASC')->get();

            //Entonces le están subiendo la prioridad (la estamos pasando del renglón 4 al 2 por ejemplo)
            //Por ello debemos aumentarle en uno la prioridad a todos los que estén entre la posición antigua y la nueva

            foreach ($toChangeLines as $line){
                DB::table('board_members')->where('id', '=', $line->id)->update(['priority' => $line->priority+1]);
            }

            DB::table('board_members')->updateOrInsert(['head_id' => $headId , 'substitute_id' => $substituteId],['priority' => $newPosition]);

            return response()->json(['message' => 'Posición actualizada correctamente']);
        }

        return response()->json(['message' => 'La posición nueva debe ser diferente a la actual'], 500);

    }

    public function getBoardMembers(Election $election, Board $board)
    {
       $members = DB::table('board_members as bm')->where('board_id', '=', $board->id)
           ->leftJoin('users as a', 'a.id', '=', 'bm.head_id')
           ->leftJoin('users as b', 'b.id', '=', 'bm.substitute_id')
           ->select('bm.*', 'a.name as head_name','b.name as substitute_name')
           ->orderBy('priority', 'ASC')->get();

       return response()->json($members);
    }




    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Board $board): JsonResponse
    {
        $board->update($request->all());
        return response()->json(['message' => 'Plancha actualizada exitosamente']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Board $board):JsonResponse
    {
        $board->delete();
        return response()->json(['message' => 'Plancha eliminada exitosamente']);
    }
}
