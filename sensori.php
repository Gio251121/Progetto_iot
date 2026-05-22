<?php
// Validazione parametri e sessione
session_start();
require 'db.php';

if (!isset($_SESSION['utente_id']) || !isset($_GET['id'])) {
    header("Location: login.php");
    exit;
}

$semaforo_id = (int)$_GET['id'];

// Recupero nome incrocio per intestazione pagina
$stmt = $pdo->prepare("SELECT nome_incrocio FROM semafori WHERE id = ?");
$stmt->execute([$semaforo_id]);
$semaforo = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Dettaglio Sensori</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <a href="dashboard.php">← Torna alla Dashboard</a>
    <h2 style="margin-top: 20px;">Stato Dispositivo: <?php echo htmlspecialchars($semaforo['nome_incrocio'] ?? 'Sconosciuto'); ?></h2>
    <p>Ultimo aggiornamento: <span id="timestamp" style="font-weight: bold;">Caricamento...</span></p>

    <div class="sensor-grid">
        <div class="sensor-card">
            <h3>Temperatura</h3>
            <span class="valore" id="val_temp">--</span> °C
        </div>
        <div class="sensor-card">
            <h3>Umidità</h3>
            <span class="valore" id="val_umid">--</span> %
        </div>
        <div class="sensor-card">
            <h3>Traffico</h3>
            <span class="valore" id="val_traffico">--</span>
        </div>
    </div>
</div>

<script>
    // ID del semaforo per la richiesta API asincrona
    const semaforoId = <?php echo $semaforo_id; ?>;

    // Funzione per il polling dei dati
    async function aggiornaDati() {
        try {
            // Esecuzione richiesta HTTP GET
            const response = await fetch(`api_sensori.php?id=${semaforoId}`);
            const data = await response.json();

            if (data.payload) {
                // Decodifica del JSON salvato nel campo payload
                const payload = JSON.parse(data.payload);

                // Aggiornamento DOM con i dati ricevuti
                document.getElementById('timestamp').innerText = data.ricevuto_at;
                document.getElementById('val_temp').innerText = payload.temperatura ?? '--';
                document.getElementById('val_umid').innerText = payload.umidita ?? '--';
                document.getElementById('val_traffico').innerText = payload.traffico ?? '--';
            }
        } catch (error) {
            console.error("Errore di rete o API:", error);
        }
    }

    // Avvio immediato e ripetizione ogni 2 secondi
    aggiornaDati();
    setInterval(aggiornaDati, 2000);
</script>
</body>
</html>
