<?php
// --- CONFIGURACIÓN DE CONEXIÓN --- //
require __DIR__ . '/../load_env.php';
$env_path = __DIR__ . '/../.env';

if (file_exists($env_path)) {
    load_dotenv_simple($env_path);
    $host = getenv('DB_HOST');
    $user = getenv('DB_USER');
    $pass = getenv('DB_PASS');
    $dbname = getenv('DB_NAME');

    $conn = new mysqli($host, $user, $pass, $dbname);
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }

    $query = "
        SELECT fecha, jugador, lp, tier, division
        FROM rank_history
        ORDER BY fecha ASC
    ";
    $result = $conn->query($query);

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[$row['jugador']][] = [
            'fecha' => $row['fecha'],
            'lp' => (int)$row['lp'],
            'tier' => $row['tier'],
            'division' => $row['division']
        ];
    }
    $conn->close();
} else {
    $data_path = __DIR__ . '/data.json';
    $data = file_exists($data_path)
        ? json_decode(file_get_contents($data_path), true)
        : [];
}
?>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>League Rank Tracker + Predicción</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: system-ui, sans-serif;
            background: #0f172a;
            color: #f1f5f9;
            margin: 0;
            padding: 20px;
            text-align: center;
        }
        h1 { color: #38bdf8; font-size: 1.8rem; margin-bottom: 8px; }
        h2, h3 { color: #94a3b8; margin-top: 20px; }

        /* === Canvas === */
        canvas {
            width: 100%;
            height: 400px; /* altura fija */
            background: #1e293b;
            border-radius: 12px;
            padding: 16px;
            margin: 20px auto;
            display: block;
        }

        /* === Contenedor para scroll horizontal en móviles === */
        .table-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        /* === Tablas === */
        table {
            margin: 20px auto;
            border-collapse: collapse;
            width: 100%;
            max-width: 900px;
            background: #1e293b;
            border-radius: 8px;
            overflow: hidden;
            text-align: center;
            text-transform: capitalize;
            font-size: 0.9rem;
        }
        th, td {
            padding: 8px 12px;
            border-bottom: 1px solid #334155;
            word-break: break-word;
        }
        th { background: #334155; color: #38bdf8; }

        #tablaPosiciones table tr:nth-child(2) { background-color: rgba(255, 215, 0, 0.1); }
        #tablaPosiciones table tr:nth-child(3) { background-color: rgba(192, 192, 192, 0.1); }
        #tablaPosiciones table tr:nth-child(4) { background-color: rgba(205, 127, 50, 0.1); }

        #predicciones {
            background: #1e293b;
            padding: 16px;
            border-radius: 12px;
            max-width: 700px;
            margin: 20px auto;
            text-align: left;
        }
        #predicciones h3 { color: #38bdf8; }

        .footer-riot {
            margin-top: 40px;
            padding: 20px;
            text-align: center;
            background: #0f172a;
            color: #94a3b8;
            font-size: 0.85rem;
            border-top: 1px solid #334155;
            line-height: 1.5;
        }
        .footer-riot strong { color: #f1f5f9; }

        /* === RESPONSIVO === */
        @media (max-width: 900px) {
            body { padding: 12px; }
            h1 { font-size: 1.6rem; }
            h2, h3 { font-size: 1rem; }
            table { font-size: 0.8rem; }
            th, td { padding: 6px; }
            #predicciones, .footer-riot { padding: 12px; }
            canvas { height: 350px; }
        }

        @media (max-width: 600px) {
            h1 { font-size: 1.3rem; }
            h2, h3 { font-size: 0.9rem; }

            table, thead, tbody, th, td, tr { display: block; width: 100%; }
            thead tr { display: none; }
            tr {
                margin-bottom: 12px;
                border: 1px solid #334155;
                border-radius: 8px;
                background: #1e293b;
                padding: 8px;
            }
            td {
                border: none;
                display: flex;
                justify-content: space-between;
                font-size: 0.85rem;
                padding: 6px 4px;
            }
            td::before {
                content: attr(data-label);
                color: #38bdf8;
                font-weight: bold;
                flex-basis: 45%;
                text-align: left;
            }
            canvas { height: 300px; }
        }
    </style>
</head>
<body>
    <h1>League Rank Tracker</h1>
    <p>Comparativo diario de progreso + predicción de tendencia</p>

    <canvas id="rankChart"></canvas>

    <div id="tablaPosiciones" class="table-wrapper"></div>
    <div id="predicciones"></div>

    <script>
        const data = <?php echo json_encode($data); ?>;

        const tierValues = { "IRON":0,"BRONZE":1,"SILVER":2,"GOLD":3,"PLATINUM":4,"DIAMOND":5,"MASTER":6,"GRANDMASTER":7,"CHALLENGER":8 };
        const divisionValues = { "I":4,"II":3,"III":2,"IV":1 };
        const colors = Array.from({length: 100}, (_, i) => `hsl(${(i*137.5)%360},70%,50%)`);

        const jugadores = Object.keys(data);

        const datasets = jugadores.map((jugador,index)=>{
            const color = colors[index % colors.length];
            return {
                label: jugador,
                data: data[jugador].map(item=>{
                    const tierBase = tierValues[item.tier] || 0;
                    const divisionBase = divisionValues[item.division] || 0;
                    const avance = (tierBase*400) + ((divisionBase-1)*100) + item.lp;
                    return { x:item.fecha, y:avance, lp:item.lp, tier:item.tier, division:item.division };
                }),
                borderColor: color,
                backgroundColor: color,
                fill:false,
                tension:0.2
            };
        });

        const posiciones = jugadores.map(jugador=>{
            const registros = data[jugador];
            const ultimo = registros[registros.length-1];
            const tierBase = tierValues[ultimo.tier] || 0;
            const divisionBase = divisionValues[ultimo.division] || 0;
            const puntaje = (tierBase*400) + ((divisionBase-1)*100) + ultimo.lp;
            return { jugador, tier: ultimo.tier, division: ultimo.division, lp: ultimo.lp, puntaje };
        }).sort((a,b)=>b.puntaje-a.puntaje);

        document.getElementById("tablaPosiciones").innerHTML = `
            <h2>🏆 Tabla de posiciones actual</h2>
            <table>
                <tr><th>#</th><th>Jugador</th><th>Tier</th><th>División</th><th>LP</th><th>Puntaje total</th></tr>
                ${posiciones.map((p,i)=>`
                    <tr ${i===0?'style="background:#33415588;"':''}>
                        <td data-label="#">${i+1}</td>
                        <td data-label="Jugador"><strong>${p.jugador}</strong></td>
                        <td data-label="Tier">${p.tier}</td>
                        <td data-label="División">${p.division}</td>
                        <td data-label="LP">${p.lp}</td>
                        <td data-label="Puntaje total">${p.puntaje}</td>
                    </tr>
                `).join("")}
            </table>
        `;

        function linearRegression(points){
            const n = points.length;
            const sumX = points.reduce((acc,_,i)=>acc+i,0);
            const sumY = points.reduce((acc,p)=>acc+p.y,0);
            const sumXY = points.reduce((acc,p,i)=>acc+i*p.y,0);
            const sumXX = points.reduce((acc,_,i)=>acc+i*i,0);
            const slope = (n*sumXY - sumX*sumY)/(n*sumXX - sumX*sumX);
            const intercept = (sumY - slope*sumX)/n;
            return {slope, intercept};
        }

        const predictionDatasets = jugadores.map((jugador,index)=>{
            const playerData = datasets[index].data;
            const {slope, intercept} = linearRegression(playerData);
            const nextIndex = playerData.length;
            const predictedValue = slope*nextIndex+intercept;
            const lastDate = playerData[playerData.length-1].x;
            const nextDate = new Date(lastDate);
            nextDate.setDate(nextDate.getDate()+1);
            const nextDateStr = nextDate.toISOString().split('T')[0];
            return {
                label: jugador+" (Predicción)",
                data:[{x:playerData[0].x, y:intercept},{x:nextDateStr, y:predictedValue}],
                borderColor: colors[index % colors.length],
                borderDash:[6,6],
                fill:false,
                tension:0.2,
                pointRadius:0
            };
        });

        new Chart(document.getElementById('rankChart'),{
            type:'line',
            data:{datasets:[...datasets,...predictionDatasets]},
            options:{
                responsive:true,
                maintainAspectRatio:false,
                parsing:{xAxisKey:'x', yAxisKey:'y'},
                plugins:{
                    legend:{labels:{color:'#f1f5f9'}},
                    title:{display:true,text:'Evolución y predicción de progreso (LP + Tier)',color:'#38bdf8'},
                    tooltip:{
                        callbacks:{
                            label:function(ctx){
                                if(ctx.dataset.label.includes("Predicción"))
                                    return `Predicción: ${ctx.raw.y.toFixed(2)} (tendencia)`;
                                return `LP: ${ctx.raw.lp} | ${ctx.raw.tier} ${ctx.raw.division} | Avance: ${ctx.raw.y}`;
                            }
                        }
                    }
                },
                scales:{
                    x:{type:'category', ticks:{color:'#f1f5f9'}},
                    y:{ticks:{color:'#f1f5f9'}}
                }
            }
        });

        const predDiv = document.getElementById("predicciones");
        predDiv.innerHTML = "<h3>📈 Predicción próxima (según tendencia actual):</h3>";
        const predicciones = jugadores.map((jugador,i)=>{
            const {slope,intercept}=linearRegression(datasets[i].data);
            const pred=slope*datasets[i].data.length+intercept;
            const delta=slope>=0?"⬆️ subiendo":"⬇️ bajando";
            return {jugador,pred,delta};
        }).sort((a,b)=>b.pred-a.pred);
        predicciones.forEach(({jugador,pred,delta})=>{
            predDiv.innerHTML+=`<p><strong>${jugador}</strong>: ${pred.toFixed(2)} puntos estimados (${delta})</p>`;
        });
    </script>

    <h2>Historial detallado</h2>
    <div class="table-wrapper">
        <table>
            <tr>
                <th>Fecha</th>
                <th>Jugador</th>
                <th>Tier</th>
                <th>División</th>
                <th>LP</th>
            </tr>
            <?php foreach ($data as $jugador => $rows): ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td data-label="Fecha"><?= htmlspecialchars($row['fecha']) ?></td>
                        <td data-label="Jugador"><?= htmlspecialchars($jugador) ?></td>
                        <td data-label="Tier"><?= htmlspecialchars($row['tier']) ?></td>
                        <td data-label="División"><?= htmlspecialchars($row['division']) ?></td>
                        <td data-label="LP"><?= htmlspecialchars($row['lp']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </table>
    </div>

    <footer class="footer-riot">
        <p><strong>League Rank Tracker</strong> © <?= date('Y') ?> — No está avalado por Riot Games ni refleja las opiniones de Riot Games o sus afiliados.</p>
        <p>Los datos son estimaciones visuales basadas en información pública o manual. Este sitio no sustituye estadísticas oficiales.</p>
        <p>Este proyecto cumple con las <a href="https://developer.riotgames.com/" target="_blank" rel="noopener noreferrer">Políticas de terceros de Riot Games</a>.</p>
    </footer>
</body>
</html>
