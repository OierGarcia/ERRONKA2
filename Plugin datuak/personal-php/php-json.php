<?php
// WP ingurunea kargatu
require_once('../wp-load.php');

// Kanpoko DB konexioa
$external_db = new wpdb("admin", "Admin123", "ingurumen_datuak", "localhost");

// GET parametroak
$limit     = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$offset    = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to   = isset($_GET['date_to'])   ? trim($_GET['date_to'])   : '';

// WHERE (filtroetarako)
$where = "1=1";
$args  = [];

if ($date_from !== '') {
    $where .= " AND ts >= %s";
    $args[] = $date_from;
}
if ($date_to !== '') {
    $where .= " AND ts <= %s";
    $args[] = $date_to;
}

// SQL kontsultak sortu (filtroarekin edo gabe)
if ($args) {
    $count_sql = $external_db->prepare("SELECT COUNT(*) FROM lecturas WHERE $where", $args);
    $rows_sql  = $external_db->prepare(
        "SELECT id, ts, temperatura, humedad, sonido, detect, pir
         FROM lecturas
         WHERE $where
         ORDER BY ts DESC
         LIMIT %d OFFSET %d",
        array_merge($args, [$limit, $offset])
    );
} else {
    $count_sql = "SELECT COUNT(*) FROM lecturas";
    $rows_sql  = $external_db->prepare(
        "SELECT id, ts, temperatura, humedad, sonido, detect, pir
         FROM lecturas
         ORDER BY ts DESC
         LIMIT %d OFFSET %d",
        $limit, $offset
    );
}

// DB emaitzak jaso
$total = $external_db->get_var($count_sql);
$rows  = $external_db->get_results($rows_sql, ARRAY_A);

// JSON bezala itzuli
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok'=>true,'total'=>(int)$total,'rows'=>$rows]);
exit;
