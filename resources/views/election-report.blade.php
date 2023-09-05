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
    </style>
</head>
<body>
<div>
    <h1 style="text-align: center">Resultados de votación para la elección: {{$electionName}}</h1>
    @if(count($electionVotes) !==0)

        <table>
            <thead>
            <tr>
                <th>
                    Plancha
                </th>

                <th>
                    Cantidad votos
                </th>
            </tr>
            </thead>

            <tbody>
            @foreach($electionVotes as $election)
                <tr>

                    <td>
                        {!!html_entity_decode($election->description)!!}
                    </td>

                    <td id="votes">
                        {{$election->total_votes}}
                    </td>

                </tr>

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
