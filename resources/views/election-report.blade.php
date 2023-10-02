<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>

    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }


        td, th {
            border: 1px solid black;
            text-align: left;
            padding: 5px;
        }

        #votes{
            text-align: center;
        }

        th {
            text-align: center;
            background-color: #0f1f39;
            color: #ffffff;
        }

        p{
            font-size: 20px;

        }
    </style>
</head>
<body>
<div>
    <h1 style="text-align: center">Resultados de votación para la elección: {{$electionName}}</h1>
    @if($electionData->electionVotes > 0)

        <p> {{$electionData->electionVotes}} (número de votos) dividido {{$electionData->electionSlots}} (número de curules):
        <strong>{{$electionData->electionVotes}} / {{$electionData->electionSlots}} = {{$electionData->electionCoefficient}}</strong> (cociente electoral)</p>


        @foreach($electionData->electionFinalBoards as $election)

            @if($election->totalWonPositions > 0)
            <p style="line-height: 70%"> <strong> {{$election->description}}: </strong>
                {{$election->total_votes}} / {{$electionData->electionCoefficient}} =  <strong> {{$election->magicNumber}} </strong>
            (cociente: {{$election->wholeTotal}} ;  residuo: {{$election->fractionTotal}}) <strong> Tendrá {{$election->totalWonPositions}} miembro(s)</strong>
            </p>

            @else
                <p style="line-height: 70%"><strong> {{$election->description}}: </strong>
                    {{$election->total_votes}} / {{$electionData->electionCoefficient}} =
                    <strong> {{$election->magicNumber}} </strong>
                    (cociente: {{$election->wholeTotal}} ; residuo: {{$election->fractionTotal}}) <strong>
                        No tendrá miembros </strong>
                </p>
            @endif

        @endforeach

        @foreach($electionData->electionFinalBoards as $election)

            @if(property_exists($election, 'has_tie') && property_exists($election, 'tie_winner'))

                <span> Debido a que se presentó un empate, de manera aleatoria se determinó que a la <strong>{{$election->description}}</strong>
                    se le asignará la curul restante. </span>

            @endif

        @endforeach




        <h2 style="margin-top: 50px"> Los {{$electionData->electionSlots}} miembros elegidos serán:</h2>

{{--        @foreach($electionData->electionFinalBoards as $election)--}}
{{--            @if($election->totalWonPositions > 0)--}}
{{--                @foreach($election->wholeMembers as $electionBoardLine)--}}
{{--                    <p style="text-align: center"><strong>Titular: </strong> {{$electionBoardLine->head_name}}  ----- <strong>Suplente:  </strong>{{$electionBoardLine->substitute_name}} </p>--}}
{{--                @endforeach--}}
{{--            @endif--}}
{{--        @endforeach--}}


        <table class="table" style="max-width: 85%; margin: auto" >
            <thead>
            <tr>
                <th scope="col">Plancha</th>
                <th scope="col">Titular</th>
                <th scope="col">Suplente</th>
            </tr>
            </thead>

            <tbody>
            @foreach($electionData->electionFinalBoards as $election)
                @if($election->totalWonPositions > 0)
                    @foreach($election->wholeMembers as $electionBoardLine)
                        <tr>
                            <td>{{$election->description}}</td>
                            <td style="text-transform: capitalize">{{$electionBoardLine->head_name}}</td>
                            <td>{{$electionBoardLine->substitute_name}}</td>
                        </tr>
                    @endforeach
                @endif
            @endforeach
            </tbody>

        </table>







    @else
        <h2 style="text-align: center">
            No hay votos registrados en esta elección
        </h2>

    @endif

    <p>
        Este reporte ha sido generado por el Sistema de Votaciones el {{new \Carbon\Carbon()}}.
    </p>
</div>


</body>
</html>
