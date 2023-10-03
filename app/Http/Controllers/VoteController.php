<?php

namespace App\Http\Controllers;

use App\Models\Election;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VoteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

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
    public function store(Request $request): JsonResponse
    {

       $userVote = $request->input('userVote');
        //verify if the user already voted

       $voter = DB::table('votes')->where('user_id', '=', $userVote['user_id'])
           ->where('election_id', '=', $userVote['election_id'])->first();

       if ($voter) {
            return response()->json(['message' => 'No puedes votar más de una vez, tu voto ya está registrado'], 403);
        }

        //If not, well then proceed and register the vote
            Vote::create([
                'election_id' => $userVote['election_id'],
                'board_id' => $userVote['board_id'],
                'user_id' => $userVote['user_id']
            ]);

        return response()->json(['message' => 'Voto registrado exitosamente']);
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
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }


    public function isRepresentadedAbleToVote(Election $election, User $user)
    {

        $alreadyVoted = DB::table('votes')->where('election_id', '=', $election->id)
            ->where('user_id', '=', $user->id)->first();

        if (!$alreadyVoted){
            return true;
        }
        return false;
    }


    public function isAbleToVote(Request $request)
    {

        $user = $request->input('user');
        $election = $request->input('election');

        $alreadyVoted = DB::table('votes')->where('election_id', '=', $election['id'])
            ->where('user_id', '=', $user['id'])->first();

        if (!$alreadyVoted){
            return true;
        }
        return false;
    }

}
