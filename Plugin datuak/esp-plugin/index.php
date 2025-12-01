<?php
/*
Plugin Name: ESP Plugin - Datuak erakutsi
Author: Puneta
Version: 1.1
Description: Datu-basean gordetako irakurketak erakusten ditu + grafikoa.

*/

add_shortcode("esp-plugin", "esp_formulario");

function esp_formulario()
{
    // wordpressen jQuery kargatu
    wp_enqueue_script('jquery');

    // JS scripta kargatu
    wp_enqueue_script(
        'esp-plugin-js',
        plugin_dir_url(__FILE__) . 'js/app.js',
        array('jquery'),
        '1.1',
        true
    );

    // Filtratu 
    $html = '<div id="esp-plugin-container" style="font-family: Arial, sans-serif;">';
    $html .= '<h3>Ingurumen datuak</h3>';

    $html .= '<div id="esp-controls" style="margin-bottom:10px;">';
    $html .= 'Noiztik: <input type="date" id="esp-date-from"> ';
    $html .= 'Noiz arte: <input type="date" id="esp-date-to"> ';
    $html .= '<button id="esp-refresh">Berritu</button> ';
    $html .= '<button id="esp-reset">Reset</button> ';
    $html .= '</div>';

    // Taula eta paginación
    $html .= '<div id="esp-plugin-content">Kargatzen...</div>';
    $html .= '<div id="esp-pagination" style="margin-top:10px;"></div>';

    // Grafikoa
    $html .= '<h4>Gráfico: Tenperatura / Hezetasuna</h4>';
    $html .= '<canvas id="esp-chart" width="800" height="300"></canvas>';

    $html .= '</div>';

    return $html;
}
