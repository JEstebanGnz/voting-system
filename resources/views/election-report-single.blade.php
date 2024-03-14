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
    @if($electionData)

        <table class="table" style="max-width: 85%; margin: auto" >
            <thead>
            <tr>
                <th scope="col">Plancha</th>
                <th scope="col">Presidente</th>
                <th scope="col">Secretario</th>
                <th scope="col">Votos obtenidos</th>
            </tr>
            </thead>

            <tbody>
            @foreach($electionData as $board)
                        <tr>
                            <td>{{$board->description}}</td>
                            <td style="text-transform: capitalize">{{$board->members->head_name}}</td>
                            <td>{{$board->members->substitute_name}}</td>
                            <td style="text-align: center">{{$board->total_votes}}</td>
                        </tr>

            @endforeach
            </tbody>
        </table>
    @endif

        @if (!$isTie && count($electionData) > 0)
        <h2 style="text-align: center">
            El ganador de la elección es la {{$electionData[0]->description}} con {{$electionData[0]->total_votes}} votos en total.
        </h2>
          @endif

        @if ($isTie)
        <h2 style="text-align: center">
            Se ha presentado un empate en la votación.
        </h2>
        @endif

        @if (count($electionData) === 0)
        <h2 style="text-align: center">
            No hay votos registrados en esta elección.
        </h2>
        @endif

    <p>
        Este reporte ha sido generado por el Sistema de Votaciones el {{\Carbon\Carbon::now()->toDateTimeString()}}.
    </p>
</div>


</body>
</html>
