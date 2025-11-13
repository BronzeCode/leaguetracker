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

// --- CONSULTA DE DATOS --- //
$query = "
    SELECT r.fecha, r.jugador, r.lp, r.tier, r.division, g.nombre AS grupo
    FROM rank_history r
    LEFT JOIN grupos g ON r.grupo_id = g.id
    ORDER BY r.fecha ASC
";
$result = $conn->query($query);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[$row['jugador']][] = [
        'fecha' => $row['fecha'],
        'lp' => (int)$row['lp'],
        'tier' => $row['tier'],
        'division' => $row['division'],
        'grupo' => $row['grupo']
    ];
}
$conn->close();
} else {
	// Sin .env → usar data.json local
    $data_path = __DIR__ . '/data.json';
    if (file_exists($data_path)) {
        $data = json_decode(file_get_contents($data_path), true);
    } else {
        $data = [];
    }
}
?>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>League Rank Tracker + Predicción</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>

    <style>
        #tablaPosiciones table tr:nth-child(2) { background-color: rgba(255, 215, 0, 0.1); } /* 🥇 */
        #tablaPosiciones table tr:nth-child(3) { background-color: rgba(192, 192, 192, 0.1); } /* 🥈 */
        #tablaPosiciones table tr:nth-child(4) { background-color: rgba(205, 127, 50, 0.1); } /* 🥉 */
   
        body {
            font-family: system-ui, sans-serif;
            background: #0f172a;
            color: #f1f5f9;
            margin: 0;
            padding: 20px;
            text-align: center;
        }
        h1 { color: #38bdf8; }
        h2, h3 { color: #94a3b8; }
        canvas {
            background: #1e293b;
            border-radius: 12px;
            padding: 20px;
            max-width: 800px;
            margin: 20px auto;
            display: block;
        }
        table {
            margin: 30px auto;
            border-collapse: collapse;
            width: 80%;
            background: #1e293b;
            border-radius: 8px;
            overflow: hidden;
            text-align: center;
            text-transform: capitalize;
        }
        th, td {
            padding: 10px 15px;
            border-bottom: 1px solid #334155;
        }
        th { background: #334155; color: #38bdf8; }
        #predicciones {
            background: #1e293b;
            padding: 20px;
            border-radius: 12px;
            max-width: 600px;
            margin: 20px auto;
            text-align: left;
        }
        #predicciones h3 { color: #38bdf8; }
        #predicciones p {
            margin: 6px 0;
            font-size: 14px;
            color: #f1f5f9;
        }
        canvas#rankChart {
    width: 800px !important;
}
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
.footer-riot strong {
  color: #f1f5f9;
}
        #grupoFiltro { padding: 10px; border-radius: 8px; background: #1e293b; color: #f1f5f9; border: 1px solid #334155; margin-top: 10px; }

    </style>
</head>
<body>
    <h1>League Rank Tracker</h1>
    <p>Comparativo diario de progreso + predicción de tendencia</p>
    <select id="grupoFiltro">
  <option value="todos">Todos los grupos</option>
</select>
    <canvas id="rankChart"></canvas>

    <div id="tablaPosiciones"></div>


    <div id="predicciones"></div>

    <script>
const data = <?php echo json_encode($data); ?>;

// Mapeo de Tiers a valores base
const tierValues = {
    "IRON": 0, "BRONZE": 1, "SILVER": 2, "GOLD": 3,
    "PLATINUM": 4, "DIAMOND": 5, "MASTER": 6,
    "GRANDMASTER": 7, "CHALLENGER": 8
};

// Peso por División (I es más alto)
const divisionValues = { "I": 4, "II": 3, "III": 2, "IV": 1 };

// Generar 100 colores distintos
const colors = Array.from({length: 100}, (_, i) => {
    const hue = (i * 137.5) % 360;
    return `hsl(${hue}, 70%, 50%)`;
});

const jugadores = Object.keys(data);
const fechas = [...new Set(data[jugadores[0]].map(item => item.fecha))];

/*Ajuste de pendientes y normal */ 
const datasets = jugadores.map((jugador, index) => {
    const color = colors[index % colors.length];
    return {
        label: jugador,
        data: data[jugador].map(item => {
            const tierBase = tierValues[item.tier] || 0;
            const divisionBase = divisionValues[item.division] || 0;

            // 🎯 Escala uniforme: cada Tier = 400 puntos totales (4 divisiones × 100 LP)
            const avance = (tierBase * 400) + ((divisionBase - 1) * 100) + item.lp;

            return {
                x: item.fecha,
                y: avance,
                lp: item.lp,
                tier: item.tier,
                division: item.division
            };
        }),
        borderColor: color,
        backgroundColor: color,
        fill: false,
        tension: 0.2
    };
});

/*AJuste de pendientes y normal*/
// === Generar tabla de posiciones actual ===
const tablaDiv = document.getElementById("tablaPosiciones");

// Tomar el último registro de cada jugador
const posiciones = jugadores.map(jugador => {
    const registros = data[jugador];
    const ultimo = registros[registros.length - 1];
    const tierBase = tierValues[ultimo.tier] || 0;
    const divisionBase = divisionValues[ultimo.division] || 0;
    const puntaje = (tierBase * 400) + ((divisionBase - 1) * 100) + ultimo.lp;

    return {
        jugador,
        tier: ultimo.tier,
        division: ultimo.division,
        lp: ultimo.lp,
        puntaje
    };
});

// Ordenar de mayor a menor
posiciones.sort((a, b) => b.puntaje - a.puntaje);

// Crear tabla HTML
let tablaHTML = `
    <h2>🏆 Tabla de posiciones actual</h2>
    <table>
        <tr>
            <th>#</th>
            <th>Jugador</th>
            <th>Tier</th>
            <th>División</th>
            <th>LP</th>
            <th>Puntaje total</th>
        </tr>
        ${posiciones.map((p, i) => `
            <tr ${i === 0 ? 'style="background:#33415588;"' : ''}>
                <td>${i + 1}</td>
                <td><strong>${p.jugador}</strong></td>
                <td>${p.tier}</td>
                <td>${p.division}</td>
                <td>${p.lp}</td>
                <td>${p.puntaje}</td>
            </tr>
        `).join("")}
    </table>
`;

tablaDiv.innerHTML = tablaHTML;

// === Calcular regresión lineal ===
function linearRegression(points) {
    const n = points.length;
    const sumX = points.reduce((acc, _, i) => acc + i, 0);
    const sumY = points.reduce((acc, p) => acc + p.y, 0);
    const sumXY = points.reduce((acc, p, i) => acc + i * p.y, 0);
    const sumXX = points.reduce((acc, _, i) => acc + i * i, 0);

    const slope = (n * sumXY - sumX * sumY) / (n * sumXX - sumX * sumX);
    const intercept = (sumY - slope * sumX) / n;

    return { slope, intercept };
}

// === Generar datasets de predicción ===
const predictionDatasets = jugadores.map((jugador, index) => {
    const playerData = datasets[index].data;
    const { slope, intercept } = linearRegression(playerData);

    const nextIndex = playerData.length;
    const predictedValue = slope * nextIndex + intercept;

    const lastDate = playerData[playerData.length - 1].x;
    const nextDate = new Date(lastDate);
    nextDate.setDate(nextDate.getDate() + 1);
    const nextDateStr = nextDate.toISOString().split('T')[0];

    return {
        label: jugador + " (Predicción)",
        data: [
            { x: playerData[0].x, y: intercept },
            { x: nextDateStr, y: predictedValue }
        ],
        borderColor: colors[index % colors.length],
        borderDash: [6, 6],
        fill: false,
        tension: 0.2,
        pointRadius: 0
    };
});
let chartInstance = null;

// === Crear Chart ===
const allDatasets = [...datasets, ...predictionDatasets];

chartInstance = new Chart(document.getElementById('rankChart'), {
    type: 'line',
    data: { datasets: allDatasets },
    options: {
        responsive: true,
        parsing: { xAxisKey: 'x', yAxisKey: 'y' },
        plugins: {
            legend: { labels: { color: '#f1f5f9' } },
            title: {
                display: true,
                text: 'Evolución y predicción de progreso (LP + Tier)',
                color: '#38bdf8'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        if (context.dataset.label.includes("Predicción")) {
                            return `Predicción: ${context.raw.y.toFixed(2)} (tendencia)`;
                        }
                        const lp = context.raw.lp;
                        const tier = context.raw.tier;
                        const division = context.raw.division;
                        const avance = context.raw.y;
                        return `LP: ${lp} | ${tier} ${division} | Avance: ${avance}`;
                    }
                }
            }
        },
            scales: {
                x: { type: 'time', ticks: { color: '#f1f5f9' } },
                y: { ticks: { color: '#f1f5f9' } }
            }
        }
});

// === Mostrar predicciones en texto ===
const predDiv = document.getElementById("predicciones");
predDiv.innerHTML = "<h3>📈 Predicción próxima (según tendencia actual):</h3>";

// Paso 1: calcular todas las predicciones
const predicciones = jugadores.map((jugador, i) => {
  const { slope, intercept } = linearRegression(datasets[i].data);
  const pred = slope * datasets[i].data.length + intercept;
  const delta = slope >= 0 ? "⬆️ subiendo" : "⬇️ bajando";
  return { jugador, pred, delta };
});

// Paso 2: ordenar de mayor a menor por pred
predicciones.sort((a, b) => b.pred - a.pred);

// Paso 3: mostrar ordenado
predicciones.forEach(({ jugador, pred, delta }) => {
  predDiv.innerHTML += `<p><strong>${jugador}</strong>: ${pred.toFixed(2)} puntos estimados (${delta})</p>`;
});

/** Filtros **/
//const data = <?php echo json_encode($data); ?>;
// === DIBUJAR GRÁFICO ===
function renderChart(jugadoresFiltrados){
    const datasets=[]; const predDatasets=[];
    jugadoresFiltrados.forEach((jugador,i)=>{
        const color=colors[i%colors.length];
        const points=data[jugador].map(item=>{
            const tierBase=tierValues[item.tier]||0;
            const divisionBase=divisionValues[item.division]||0;
            const avance=(tierBase*400)+((divisionBase-1)*100)+item.lp;
            return {x:item.fecha,y:avance,lp:item.lp,tier:item.tier,division:item.division};
        });
        datasets.push({label:jugador,data:points,borderColor:color,backgroundColor:color,fill:false,tension:0.2});
        const {slope,intercept}=linearRegression(points);
        const nextIndex=points.length;
        const predictedValue=slope*nextIndex+intercept;
        const lastDate=new Date(points[points.length-1].x);
        lastDate.setDate(lastDate.getDate()+1);
        const nextDateStr=lastDate.toISOString().split('T')[0];
        predDatasets.push({
            label:jugador+" (Predicción)",
            data:[{x:points[0].x,y:intercept},{x:nextDateStr,y:predictedValue}],
            borderColor:color,borderDash:[6,6],fill:false,tension:0.2,pointRadius:0
        });
    });

    const ctx=document.getElementById('rankChart').getContext('2d');
    if(chartInstance) chartInstance.destroy();
    chartInstance=new Chart(ctx,{
        type:'line',
        data:{datasets:[...datasets,...predDatasets]},
        options:{
            responsive:true,
            parsing:{xAxisKey:'x',yAxisKey:'y'},
            plugins:{
                legend:{labels:{color:'#f1f5f9'}},
                title:{display:true,text:'Evolución y predicción de progreso (LP + Tier)',color:'#38bdf8'}
            },
 scales: {
                x: { type: 'time', ticks: { color: '#f1f5f9' } },
                y: { ticks: { color: '#f1f5f9' } }
            }        }
    });
}
// Extraer los grupos únicos
const grupos = [...new Set(Object.values(data)
  .flatMap(rows => rows.map(r => r.grupo)))];

// Insertar en el selector
const select = document.getElementById("grupoFiltro");
grupos.forEach(grupo => {
  const opt = document.createElement("option");
  opt.value = grupo;
  opt.textContent = grupo;
  select.appendChild(opt);
});

select.addEventListener("change", () => {
  const grupoSeleccionado = select.value;
  
  // Filtrar jugadores por grupo
  const jugadoresFiltrados = Object.keys(data).filter(jugador =>
    grupoSeleccionado === "todos" ||
    data[jugador][0].grupo === grupoSeleccionado
  );

  // Aquí vuelves a dibujar la gráfica y la tabla usando solo `jugadoresFiltrados`
  renderChart(jugadoresFiltrados);
  //renderTabla(jugadoresFiltrados);
});


/** Filtros **/

    </script>

     <h2>Historial detallado</h2>
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
                    <td><?= htmlspecialchars($row['fecha']) ?></td>
                    <td><?= htmlspecialchars($jugador) ?></td>
                    <td><?= htmlspecialchars($row['tier']) ?></td>
                    <td><?= htmlspecialchars($row['division']) ?></td>
                    <td><?= htmlspecialchars($row['lp']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </table>
    <?php
$year = date('Y');
$siteName = "League Rank Tracker";
?>
<footer class="footer-riot">
  <p>
    <strong>League Rank Tracker</strong> © <?= date('Y') ?> —
    No está avalado por Riot Games y no refleja las opiniones o puntos de vista de Riot Games ni de nadie oficialmente involucrado en la producción o gestión de sus propiedades.
    Riot Games y todas las propiedades asociadas son marcas comerciales o registradas de Riot Games, Inc.
  </p>
  <p>
    Los datos mostrados son estimaciones visuales basadas en información pública o registrada manualmente.
    Este sitio no ofrece un sistema alternativo de clasificación ni sustituye las estadísticas oficiales de Riot Games.
  </p>
  <p>
    Este proyecto cumple con las políticas de terceros de Riot Games y utiliza la información de acuerdo con sus 
    <a href="https://developer.riotgames.com/" target="_blank" rel="noopener noreferrer">Términos de Uso para Desarrolladores</a>.
  </p>
  <p>© 2025 League Tracker. League Tracker is not endorsed by Riot Games and does not reflect the views or opinions of Riot Games or anyone officially involved in producing or managing League of Legends. League of Legends and Riot Games are trademarks or registered trademarks of Riot Games, Inc.
</p>
</footer>

</body>
</html>