<?php
// Endpoint REST per interrogazioni AJAX
require 'db.php';

$semaforo_id = $_GET['id'] ?? null;

if ($semaforo_id) {
    // Estrazione del record più recente per il semaforo specificato
    $stmt = $pdo->prepare("SELECT payload, ricevuto_at FROM dati_sensori WHERE semaforo_id = ? ORDER BY ricevuto_at DESC LIMIT 1");
    $stmt->execute([$semaforo_id]);
    $dati = $stmt->fetch(PDO::FETCH_ASSOC);

    // Formattazione risposta HTTP in JSON
    header('Content-Type: application/json');
    echo $dati ? json_encode($dati) : json_encode(['errore' => 'Nessun dato']);
}
?>
