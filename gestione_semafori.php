<?php
session_start();
require_once 'db.php';
/** @var PDO $pdo */

// Controllo permessi amministrativi
if (!isset($_SESSION['utente_id']) || $_SESSION['ruolo_id'] != 1) {
    header("Location: dashboard.php");
    exit;
}

$messaggio = '';

// Elaborazione dei dati ricevuti via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitizzazione degli input stringa
    $codice_seriale = trim($_POST['codice_seriale']);
    $nome_incrocio = trim($_POST['nome_incrocio']);

    // Cast a float per garantire il tipo numerico
    $latitudine = (float)$_POST['latitudine'];
    $longitudine = (float)$_POST['longitudine'];

    // Hash della password in Bcrypt
    $password_hash = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);

    try {
        // Validazione Backend: Controllo assoluto sui limiti geografici terrestri
        if ($latitudine < -90 || $latitudine > 90) {
            throw new Exception("La latitudine deve essere compresa tra -90 e 90.");
        }
        if ($longitudine < -180 || $longitudine > 180) {
            throw new Exception("La longitudine deve essere compresa tra -180 e 180.");
        }

        // Inserimento sicuro tramite prepared statement
        $stmt = $pdo->prepare("
            INSERT INTO semafori (codice_seriale, nome_incrocio, password, latitudine, longitudine) 
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([$codice_seriale, $nome_incrocio, $password_hash, $latitudine, $longitudine]);

        $messaggio = "<div class='alert success'>✅ Dispositivo registrato correttamente nel sistema.</div>";
    } catch (Exception $e) {
        $messaggio = "<div class='alert error'>❌ Errore durante il salvataggio: " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Creazione Semaforo - Admin Console</title>
    <link rel="stylesheet" href="style.css">

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        .admin-card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border: 1px solid #e0e6ed; max-width: 800px; margin: 0 auto; text-align: left; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; color: #7f8c8d; font-size: 0.9rem; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #bdc3c7; border-radius: 5px; font-size: 0.95rem; box-sizing: border-box; }
        .form-control:focus { border-color: #3498db; outline: none; }
        .btn-submit { width: 100%; background: #2980b9; color: white; border: none; padding: 12px; font-size: 1rem; border-radius: 5px; cursor: pointer; font-weight: 600; margin-top: 10px; }
        .btn-submit:hover { background: #2471a3; }
        .btn-back { display: inline-block; margin-bottom: 20px; padding: 10px 15px; background-color: #95a5a6; color: white; text-decoration: none; border-radius: 5px; font-weight: 600; font-size: 0.9rem; }
        .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; font-weight: 500; }
        .alert.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .coord-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;}

        /* Contenitore per il selezionatore mappa */
        #map-picker { height: 350px; border-radius: 8px; border: 1px solid #bdc3c7; margin-bottom: 15px; z-index: 1;}
    </style>
</head>
<body>

<?php require_once 'navbar.php'; ?>

<div class="app-container">
    <main class="main-content" style="max-width: 1100px; margin: 0 auto; display: block; text-align: center;">

        <div style="text-align: left; max-width: 800px; margin: 0 auto;">
            <a href="dashboard.php" class="btn-back">⬅ Torna alla Dashboard</a>
            <h1 style="margin-bottom: 25px; color: #2c3e50;">Registrazione Nuovo Impianto</h1>
        </div>

        <?php if ($messaggio) echo "<div style='max-width: 800px; margin: 0 auto; text-align: left;'>" . $messaggio . "</div>"; ?>

        <div class="admin-card">
            <form method="POST" action="gestione_semafori.php">

                <div class="form-group">
                    <label for="codice_seriale">Codice Seriale (Univoco)</label>
                    <input type="text" id="codice_seriale" name="codice_seriale" class="form-control" required placeholder="es. SR-9999-VI">
                </div>

                <div class="form-group">
                    <label for="nome_incrocio">Nome Toponomastico Incrocio</label>
                    <input type="text" id="nome_incrocio" name="nome_incrocio" class="form-control" required placeholder="es. Incrocio Viale Roma / Via Milano">
                </div>

                <div class="form-group">
                    <label for="password">Password Hardware (Per connessione ESP32)</label>
                    <input type="password" id="password" name="password" class="form-control" required placeholder="Inserisci la password in chiaro">
                </div>

                <div class="form-group">
                    <label>Seleziona la posizione esatta cliccando sulla mappa</label>
                    <div id="map-picker"></div>
                </div>

                <div class="coord-grid">
                    <div class="form-group">
                        <label for="latitudine">Latitudine</label>
                        <input type="number" step="0.00000001" min="-90" max="90" id="latitudine" name="latitudine" class="form-control" required placeholder="Latitudine">
                    </div>

                    <div class="form-group">
                        <label for="longitudine">Longitudine</label>
                        <input type="number" step="0.00000001" min="-180" max="180" id="longitudine" name="longitudine" class="form-control" required placeholder="Longitudine">
                    </div>
                </div>

                <button type="submit" class="btn-submit">Salva Dispositivo</button>
            </form>
        </div>

    </main>
</div>

<script>
    // Inizializzazione mappa centrata su Vicenza di default
    const map = L.map('map-picker').setView([45.5488, 11.5557], 13);
    let markerCorrente = null;

    // Caricamento dei layer stradali OpenStreetMap gratuiti
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap'
    }).addTo(map);

    // Gestione dell'evento click sulla mappa
    map.on('click', function(e) {
        // Estrazione precisa delle coordinate al click
        const lat = e.latlng.lat.toFixed(8);
        const lng = e.latlng.lng.toFixed(8);

        // Compilazione automatica dei campi del modulo HTML
        document.getElementById('latitudine').value = lat;
        document.getElementById('longitudine').value = lng;

        // Posizionamento o spostamento del pin visivo sulla mappa
        if (markerCorrente) {
            markerCorrente.setLatLng(e.latlng);
        } else {
            markerCorrente = L.marker(e.latlng).addTo(map);
        }
    });
</script>

</body>
</html>