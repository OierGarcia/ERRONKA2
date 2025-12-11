<?php

// ====== 1) Conectar BD LOCAL (XAMPP) ======
try {
    $pdoLocal = new PDO("mysql:host=localhost;dbname=ingurumen_local;charset=utf8mb4", 
                        "root", "");
    $pdoLocal->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    http_response_code(500);
    echo "Error BD local: " . $e->getMessage();
    exit;
}

// Comprobar parámetros
$requeridos = ["temperatura", "humedad", "sonido", "detect", "pir"];
foreach ($requeridos as $r) {
    if (!isset($_POST[$r])) {
        http_response_code(400);
        echo "Faltan parámetros";
        exit;
    }
}

// Capturar valores
$T = floatval($_POST["temperatura"]);
$H = floatval($_POST["humedad"]);
$S = intval($_POST["sonido"]);
$D = intval($_POST["detect"]);
$P = intval($_POST["pir"]);

// ====== 2) Insertar en la BD LOCAL ======
try {
    $stmt = $pdoLocal->prepare(
        "INSERT INTO lecturas (temperatura, humedad, sonido, detect, pir)
         VALUES (:t, :h, :s, :d, :p)"
    );
    $stmt->execute([":t"=>$T, ":h"=>$H, ":s"=>$S, ":d"=>$D, ":p"=>$P]);
} catch (Exception $e) {
    http_response_code(500);
    echo "Error insert local: " . $e->getMessage();
    exit;
}

// ====== 3) Conectar con la BD en UBUNTU ======
try {
    $pdoUbuntu = new PDO("mysql:host=192.168.71.64;dbname=ingurumen_datuak;charset=utf8mb4", 
                         "admin", "Admin123");
    $pdoUbuntu->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt2 = $pdoUbuntu->prepare(
        "INSERT INTO lecturas (temperatura, humedad, sonido, detect, pir)
         VALUES (:t, :h, :s, :d, :p)"
    );
    $stmt2->execute([":t"=>$T, ":h"=>$H, ":s"=>$S, ":d"=>$D, ":p"=>$P]);
} catch (Exception $e) {
    // El fallo aquí no rompe el sistema entero
    echo "Guardado local OK, pero fallo Ubuntu: " . $e->getMessage();
    exit;
}

echo "OK - local + Ubuntu";
