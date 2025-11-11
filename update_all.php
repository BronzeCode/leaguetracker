<?php
// --- CONFIGURACIÓN --- //
require __DIR__ . '/load_env.php';
load_dotenv_simple(__DIR__ . '/.env');
$host = getenv('DB_HOST');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');
$dbname = getenv('DB_NAME');
$api_key =  getenv('API_RIOT');;


if (empty($api_key)) {
    die("❌ Falta API key.");
}

// === LISTA DE SUMMONERS === //
// puedes agregar cuantos quieras:
$summoners = [
    ['nombre' => 'Summoner', 'tag' => 'tag', 'region' => 'Na'],
    ['nombre' => 'Summoner', 'tag' => 'tag', 'region' => 'Na'],
    ['nombre' => 'Summoner', 'tag' => 'tag', 'region' => 'Na'],
    ['nombre' => 'Summoner', 'tag' => 'tag', 'region' => 'Na'],
    ['nombre' => 'Summoner', 'tag' => 'tag', 'region' => 'Na'],
];

// --- CONEXIÓN --- //
$mysqli = new mysqli($host, $user, $pass, $dbname);
if ($mysqli->connect_error) {
    die("Error DB: " . $mysqli->connect_error);
}

// --- FUNCIÓN API --- //
function riot_api_request($url, $api_key) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-Riot-Token: $api_key"]);
    $result = curl_exec($ch);
    curl_close($ch);
    return json_decode($result, true);
}

$fecha = date('Y-m-d');

echo "<h2>Actualizando Summoners...</h2>";

foreach ($summoners as $s) {
    $jugador = $s['nombre'];
    $tagname = $s['tag'];

    echo "<h3>🔍 Consultando $jugador#$tagname...</h3>";

    // 1️⃣ Obtener PUUID
    $summoner_url = "https://americas.api.riotgames.com/riot/account/v1/accounts/by-riot-id/" . urlencode($jugador)."/".urlencode($tagname);
    $summoner_data = riot_api_request($summoner_url, $api_key);

    if (!isset($summoner_data['puuid'])) {
        echo "❌ No se pudo obtener PUUID para $jugador<br>";
        continue;
    }

    $puuid = $summoner_data['puuid'];
    $region_jugador = $s['region'];
    // 2️⃣ Obtener Ranked SoloQ
    $rank_url = "https://$region_jugador.api.riotgames.com/lol/league/v4/entries/by-puuid/$puuid";
    $rank_data = riot_api_request($rank_url, $api_key);

    $solo = null;
    foreach ($rank_data as $entry) {
        if ($entry['queueType'] === 'RANKED_SOLO_5x5') {
            $solo = $entry;
            break;
        }
    }

    // 3️⃣ Guardar en BD
    if ($solo) {
        $stmt = $mysqli->prepare("
            INSERT INTO rank_history (fecha, jugador, tier, division, lp, wins, losses)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "ssssiii",
            $fecha,
            $jugador,
            $solo['tier'],
            $solo['rank'],
            $solo['leaguePoints'],
            $solo['wins'],
            $solo['losses']
        );
        $stmt->execute();
        $stmt->close();

        echo "✅ Guardado: <strong>$jugador</strong> - {$solo['tier']} {$solo['rank']} ({$solo['leaguePoints']} LP)<br>";
    } else {
        echo "⚠️ $jugador no tiene SoloQ rankeado.<br>";
    }

    // Pequeña pausa para evitar límite de rate
    sleep(1);
}

$mysqli->close();
?>

