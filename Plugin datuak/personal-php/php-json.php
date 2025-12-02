<?php
// WP ingurunea kargatu
require_once('../wp-load.php');

// Kanpoko DB konexioa
$external_db = new wpdb("admin", "Admin123", "ingurumen_datuak", "localhost");

// GET parametroak --- parametroak badaude, balioa hartzen da; ez badaude, balio lehenetsia erabiltzen da
$limit     = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$offset    = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to   = isset($_GET['date_to'])   ? trim($_GET['date_to'])   : '';

// WHERE (filtroetarako)
$where = "1=1"; //1=1 jartzen da SQL-ko WHERE klausula beti balioduna izan dadin eta horrela baldintzak erraz gehitu ahal izateko AND bidez.
$args  = []; // prepare() funtzioan erabiliko diren argumentuak

// date_from parametroa badago, SQL WHERE-era gehitzen da eta balioa args-en sartzen da
if ($date_from !== '') {
    $where .= " AND ts >= %s";
    $args[] = $date_from;
}
// date_to parametroa badago, SQL WHERE-era gehitzen da eta balioa args-en sartzen da
if ($date_to !== '') {
    $where .= " AND ts <= %s";
    $args[] = $date_to;
}

// SQL kontsultak sortu (filtroarekin edo gabe)
// Filtroak badaude ($args ez badago hutsik)
if ($args) {
    // Guztizko erregistro kopurua kontatzeko kontsulta (prepare erabilita)
    $count_sql = $external_db->prepare("SELECT COUNT(*) FROM lecturas WHERE $where", $args);
    
    // Datuak eskuratzeko kontsulta: id, denbora, sentsore balioak...
    // Filtroak aplikatuta, ts eremua behetik gora ordenatuta
    // Limit eta offset ere array merge bidez gehitzen dira
    $rows_sql  = $external_db->prepare(
        "SELECT id, ts, temperatura, humedad, sonido, detect, pir
         FROM lecturas
         WHERE $where
         ORDER BY ts DESC
         LIMIT %d OFFSET %d",
        array_merge($args, [$limit, $offset])
    );

// Filtroak ez badaude ($args hutsik badago)
} else {
    // Kontsulta sinplea guztizko erregistro kopurua jasotzeko
    $count_sql = "SELECT COUNT(*) FROM lecturas";

    // Datuak eskuratzeko kontsulta, ordezko parametroekin (limit eta offset)
    $rows_sql  = $external_db->prepare(
        "SELECT id, ts, temperatura, humedad, sonido, detect, pir
         FROM lecturas
         ORDER BY ts DESC
         LIMIT %d OFFSET %d",
        $limit, $offset
    );
}
// Datu-baseari kontsultak bidaltzen zaizkio
$total = $external_db->get_var($count_sql); // Guztizko kopurua jasotzen da zenbaki bakar gisa
$rows  = $external_db->get_results($rows_sql, ARRAY_A); // Lerroak jasotzen dira

// JSON bezala itzuli
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok'=>true,'total'=>(int)$total,'rows'=>$rows]);
exit;
