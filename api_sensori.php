<?php
require_once 'db.php';
/** @var PDO $pdo */

header('Content-Type: application/json');

$id_semaforo = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$id_semaforo) {
    echo json_encode(['payload' => null]);
    exit;
}

// Estrazione dell'ultimo messaggio assoluto registrato per il dispositivo
// NOTA: Sostituisci 'storico_sensori' con il nome reale della tua tabella
$stmt = $pdo->prepare("
    SELECT payload, ricevuto_at 
    FROM dati_sensori 
    WHERE semaforo_id = ? 
    ORDER BY ricevuto_at DESC 
    LIMIT 1
");
$stmt->execute([$id_semaforo]);
$ultimo_dato = $stmt->fetch(PDO::FETCH_ASSOC);

if ($ultimo_dato) {
    // Restituzione dell'ultimo payload salvato a prescindere da quando è arrivato
    echo json_encode([
        'payload' => $ultimo_dato['payload'],
        'ricevuto_at' => $ultimo_dato['ricevuto_at']
    ]);
} else {
    // Ritorna null solo se il semaforo è appena stato installato e non ha mai comunicato
    echo json_encode(['payload' => null]);
}
?>