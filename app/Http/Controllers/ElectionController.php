<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Election;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class ElectionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json(DB::table('elections')->get());
    }

    public function setActive(Request $request, Election $election): JsonResponse
    {
        //Detect previous active election

        try {
            $active = Election::getActiveElection();
            if($active){
                $active->is_active = false;
                $active->save();
            }
        } catch (\Exception $e) {
        } finally {
            $election->is_active = true;
            $election->save();
        }
        return response()->json(['message' => 'Se ha seleccionado la elección como la nueva elección activa']);
    }

    public function getBoards(int $electionId): JsonResponse
    {
        $boards = DB::table('boards as b')->select(['b.id','b.description', 'e.name as election_name'])
            ->join('elections as e', 'b.election_id', '=', 'e.id')->where('election_id', '=', $electionId)->get();

        return response()->json($boards);
    }

    public function getActive()
    {
        return Election::getActiveElection();
    }

    public function deactivate (Request $request, Election $election): JsonResponse
    {
        try {
            $election->is_active = false;
            $election->save();
        } catch (\Exception $e) {

        }

        return response()->json(['message' => 'Se ha desactivado la elección, ya no hay elecciones activas']);
    }


    public function generateReport(Request $request, Election $election)
    {
        $electionData = $election->getVotingReport($election);
        $electionName = $election->name;

        /*dd($electionData);*/

        return Pdf::loadView('election-report-single', compact('electionData', 'electionName'))
            ->stream("Reporte para la elección $electionName.pdf");
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
        Election::create($request->all());
        return response()->json(['message' => 'La elección se ha creado exitosamente']);
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
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Election $election): JsonResponse
    {
        $election->update($request->all());
        return response()->json(['message' => 'Elección actualizada exitosamente']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Election $election): JsonResponse
    {
        if ($election->active === 1) {
            return response()->json(['message' => 'No se puede eliminar una elección activa'], 400);
        }
        try {
            $election->delete();
        } catch (QueryException $e) {
            if ($e->getCode() === "23000") {
                return response()->json(['message' => 'No puedes eliminar una elección si tiene planchas asociadas.'], 400);
            }
        }
        return response()->json(['message' => 'Elección eliminada exitosamente']);
    }
}
