<?php
session_start();
require_once 'db.php';
/** @var PDO $pdo */

// Controllo validita sessione
if (!isset($_SESSION['utente_id'])) {
    header("Location: login.php");
    exit;
}

$ruolo = $_SESSION['ruolo_id'];
$utente_id = $_SESSION['utente_id'];

// Estrazione dati semafori includendo latitudine e longitudine
if ($ruolo == 1) {
    $stmt = $pdo->query("SELECT id, codice_seriale, nome_incrocio, latitudine, longitudine FROM semafori ORDER BY id ASC");
} else {
    $stmt = $pdo->prepare("
        SELECT s.id, s.codice_seriale, s.nome_incrocio, s.latitudine, s.longitudine 
        FROM semafori s 
        JOIN semafori_manutentori sm ON s.id = sm.semaforo_id 
        WHERE sm.utente_id = ? 
        ORDER BY s.id ASC
    ");
    $stmt->execute([$utente_id]);
}
$semafori = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Impostazione del nodo di default per il caricamento iniziale
$primo_semaforo = $semafori[0] ?? null;
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Pannello di Controllo - Semafori IoT</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php
// Iniezione componente navbar
require_once 'navbar.php';
?>

<div class="app-container">

    <aside class="sidebar">
        <div class="sidebar-title">Dispositivi Assegnati</div>
        <div id="lista-semafori">
            <?php foreach ($semafori as $s): ?>
                <div class="semaforo-item <?php echo ($primo_semaforo && $s['id'] == $primo_semaforo['id']) ? 'active' : ''; ?>"
                     data-id="<?php echo $s['id']; ?>"
                     data-nome="<?php echo htmlspecialchars($s['nome_incrocio']); ?>"
                     data-lat="<?php echo htmlspecialchars($s['latitudine']); ?>"
                     data-lng="<?php echo htmlspecialchars($s['longitudine']); ?>">
                    <?php echo htmlspecialchars($s['nome_incrocio']); ?><br>
                    <small style="color: #95a5a6;">C. seriale: <?php echo htmlspecialchars($s['codice_seriale']); ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    </aside>

    <main class="main-content">
        <?php if ($primo_semaforo): ?>

            <div class="header-dati">
                <div>
                    <h1 id="titolo-incrocio"><?php echo htmlspecialchars($primo_semaforo['nome_incrocio']); ?></h1>
                    <p style="color: #7f8c8d; margin-top: 5px;">Ultimo aggiornamento: <span id="timestamp">In attesa di dati...</span></p>
                </div>
                <div>
                    <span class="status-badge" id="stato-connessione">Connesso</span>
                </div>
            </div>

            <div class="sensor-grid">
                <div class="card">
                    <h3>Temperatura Interna</h3>
                    <span class="valore" id="val_temp">--</span><span class="unita">°C</span>
                </div>
                <div class="card">
                    <h3>Umidità Relativa</h3>
                    <span class="valore" id="val_umid">--</span><span class="unita">%</span>
                </div>
                <div class="card">
                    <h3>Densità Traffico</h3>
                    <span class="valore" id="val_traffico">--</span><span class="unita">veicoli/m</span>
                </div>
            </div>

            <div class="map-container" style="margin-top: 30px;">
                <h3 style="margin-bottom: 15px; color: #7f8c8d; font-size: 1rem; font-weight: 500;">Posizione Geografica</h3>
                <iframe id="mappa-google"
                        width="100%"
                        height="350"
                        style="border: 1px solid #e0e6ed; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);"
                        loading="lazy"
                        allowfullscreen
                        src="https://maps.google.com/maps?q=<?php echo $primo_semaforo['latitudine']; ?>,<?php echo $primo_semaforo['longitudine']; ?>&z=16&output=embed">
                </iframe>
            </div>

        <?php else: ?>
            <h2>Nessun dispositivo assegnato</h2>
            <p>Contatta l'amministratore di sistema per richiedere l'assegnazione dei semafori.</p>
        <?php endif; ?>
    </main>
</div>

<script>
    // Configurazione polling dati
    let semaforoCorrente = <?php echo $primo_semaforo ? $primo_semaforo['id'] : 'null'; ?>;
    let pollingInterval = null;

    async function fetchDatiSensori() {
        if (!semaforoCorrente) return;

        try {
            // Esecuzione chiamata REST endpoint
            const response = await fetch(`api_sensori.php?id=${semaforoCorrente}`);
            const data = await response.json();

            if (data.payload) {
                const payload = JSON.parse(data.payload);
                document.getElementById('timestamp').innerText = data.ricevuto_at;
                document.getElementById('val_temp').innerText = payload.temperatura ?? '--';
                document.getElementById('val_umid').innerText = payload.umidita ?? '--';
                document.getElementById('val_traffico').innerText = payload.traffico ?? '--';

                document.getElementById('stato-connessione').innerText = 'Online';
                document.getElementById('stato-connessione').style.backgroundColor = '#2ecc71';
            } else {
                document.getElementById('stato-connessione').innerText = 'Nessun Dato';
                document.getElementById('stato-connessione').style.backgroundColor = '#e74c3c';
            }
        } catch (error) {
            console.error("Errore Fetch API:", error);
            document.getElementById('stato-connessione').innerText = 'Errore Rete';
            document.getElementById('stato-connessione').style.backgroundColor = '#e74c3c';
        }
    }

    // Gestione switch di contesto da sidebar
    document.querySelectorAll('.semaforo-item').forEach(item => {
        item.addEventListener('click', function() {
            // Aggiornamento classe attiva
            document.querySelectorAll('.semaforo-item').forEach(el => el.classList.remove('active'));
            this.classList.add('active');

            // Aggiornamento titolo e reset UI
            document.getElementById('titolo-incrocio').innerText = this.getAttribute('data-nome');
            document.getElementById('val_temp').innerText = '--';
            document.getElementById('val_umid').innerText = '--';
            document.getElementById('val_traffico').innerText = '--';
            document.getElementById('timestamp').innerText = 'Caricamento in corso...';

            // Aggiornamento coordinate mappa
            const lat = this.getAttribute('data-lat');
            const lng = this.getAttribute('data-lng');
            if (lat && lng) {
                document.getElementById('mappa-google').src = `https://maps.google.com/maps?q=${lat},${lng}&z=16&output=embed`;
            }

            // Riavvvio fetch dati
            semaforoCorrente = this.getAttribute('data-id');
            fetchDatiSensori();
        });
    });

    // Avvio ciclo di polling iniziale
    if (semaforoCorrente) {
        fetchDatiSensori();
        pollingInterval = setInterval(fetchDatiSensori, 2000);
    }
</script>
</body>
</html>