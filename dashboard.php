<?php
session_start();
require_once 'db.php';
/** @var PDO $pdo */

// Controllo validità sessione
if (!isset($_SESSION['utente_id'])) {
    header("Location: login.php");
    exit;
}

$ruolo = $_SESSION['ruolo_id'];
$utente_id = $_SESSION['utente_id'];

// Estrazione semafori basata sui permessi dell'utente
if ($ruolo == 1) {
    $stmt = $pdo->query("SELECT id, codice_seriale, nome_incrocio FROM semafori ORDER BY id ASC");
} else {
    $stmt = $pdo->prepare("
        SELECT s.id, s.codice_seriale, s.nome_incrocio 
        FROM semafori s 
        JOIN semafori_manutentori sm ON s.id = sm.semaforo_id 
        WHERE sm.utente_id = ? 
        ORDER BY s.id ASC
    ");
    $stmt->execute([$utente_id]);
}
$semafori = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Selezione del nodo di default per il caricamento iniziale
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
// Iniezione dinamica del componente navbar
require_once 'navbar.php';
?>

<div class="app-container">

    <aside class="sidebar">
        <div class="sidebar-title">Dispositivi Assegnati</div>
        <div id="lista-semafori">
            <?php foreach ($semafori as $s): ?>
                <div class="semaforo-item <?php echo ($primo_semaforo && $s['id'] == $primo_semaforo['id']) ? 'active' : ''; ?>"
                     data-id="<?php echo $s['id']; ?>"
                     data-nome="<?php echo htmlspecialchars($s['nome_incrocio']); ?>">
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

        <?php else: ?>
            <h2>Nessun dispositivo assegnato</h2>
            <p>Contatta l'amministratore di sistema per richiedere l'assegnazione dei semafori.</p>
        <?php endif; ?>
    </main>
</div>

<script>
    // Gestione asincrona del fetching dati
    let semaforoCorrente = <?php echo $primo_semaforo ? $primo_semaforo['id'] : 'null'; ?>;
    let pollingInterval = null;

    async function fetchDatiSensori() {
        if (!semaforoCorrente) return;

        try {
            // Richiesta HTTP GET verso l'endpoint locale
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

    // Binding degli eventi di click sulla sidebar per switch di contesto
    document.querySelectorAll('.semaforo-item').forEach(item => {
        item.addEventListener('click', function() {
            document.querySelectorAll('.semaforo-item').forEach(el => el.classList.remove('active'));
            this.classList.add('active');

            document.getElementById('titolo-incrocio').innerText = this.getAttribute('data-nome');

            document.getElementById('val_temp').innerText = '--';
            document.getElementById('val_umid').innerText = '--';
            document.getElementById('val_traffico').innerText = '--';
            document.getElementById('timestamp').innerText = 'Caricamento in corso...';

            semaforoCorrente = this.getAttribute('data-id');
            fetchDatiSensori();
        });
    });

    // Trigger del polling a ciclo continuo
    if (semaforoCorrente) {
        fetchDatiSensori();
        pollingInterval = setInterval(fetchDatiSensori, 2000);
    }
</script>
</body>
</html>