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

$primo_semaforo = $semafori[0] ?? null;

// Codifica dell'intero array dei semafori in JSON per il modulo JavaScript della mappa
$semafori_json = json_encode($semafori);
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
                    <h3>Temperatura</h3>
                    <span class="valore" id="val_temp">--</span><span class="unita">°C</span>
                </div>
                <div class="card">
                    <h3>Umidità</h3>
                    <span class="valore" id="val_umid">--</span><span class="unita">%</span>
                </div>
                <div class="card">
                    <h3>Densità Traffico</h3>
                    <span class="valore" id="val_traffico">--</span><span class="unita"></span>
                </div>
            </div>

            <div class="sensor-grid">
                <div class="card">
                    <h3>Visibilità</h3>
                    <span class="valore" id="val_visibilita" >--</span>
                    <div id="label_visibilita" style="font-size: 0.9rem; color: #7f8c8d; margin-top: 5px; font-weight: 500;"></div>
                </div>
                <div class="card">
                    <h3>Stato Asfalto (Pioggia)</h3>
                    <span class="valore" id="val_pioggia">--</span>
                </div>
            </div>

            <div class="map-container" style="margin-top: 30px;">
                <h3 style="margin-bottom: 15px; color: #7f8c8d; font-size: 1rem; font-weight: 500;">Posizione Geografica</h3>
                <?php if ($primo_semaforo): ?>
                    <iframe id="mappa-google"
                            width="100%"
                            height="350"
                            style="border: 1px solid #e0e6ed; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);"
                            loading="lazy"
                            allowfullscreen
                            src="https://maps.google.com/maps?q=<?php echo $primo_semaforo['latitudine']; ?>,<?php echo $primo_semaforo['longitudine']; ?>+(Semaforo:+<?php echo urlencode($primo_semaforo['codice_seriale']); ?>)&z=18&output=embed">
                    </iframe>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <h2>Nessun dispositivo assegnato</h2>
            <p>Contatta l'amministratore di sistema per richiedere l'assegnazione dei semafori.</p>
        <?php endif; ?>
    </main>

    <div class="card" style="text-align: left;">
        <h3>Stato Luci In Tempo Reale</h3>
        <div class="semaforo-grafico">
            <div class="luce rossa" id="luce-rosso"></div>
            <div class="luce gialla" id="luce-giallo"></div>
            <div class="luce verde" id="luce-verde"></div>
        </div>

        <div class="pulsantiera-semaforo">
            <button class="btn-power btn-accendi" onclick="impostaStatoSemaforo('attivo')">
                Accendi
            </button>
            <button class="btn-power btn-inattivo" onclick="impostaStatoSemaforo('inattivo')">
                Inattivo
            </button>
            <button class="btn-power btn-spegni" onclick="impostaStatoSemaforo('spento')">
                Spegni
            </button>
        </div>

        <div style="margin-top: 25px; border-top: 1px solid #e0e6ed; padding-top: 15px;">
            <h4 style="color: #7f8c8d; margin-bottom: 10px; font-size: 1rem; text-align: left;">Indicatori Diagnostici</h4>

            <div id="pannello-allerte" style="display: flex; flex-direction: column;">
                <div id="alert-ghiaccio" class="alert-dynamic">Temperatura molto bassa</div>
                <div id="alert-calore" class="alert-dynamic">Surriscaldamento asfalto o hardware</div>
                <div id="alert-traffico" class="alert-dynamic">Congestione traffico rilevata</div>
                <div id="alert-visibilita" class="alert-dynamic">Poca visibilità</div>
                <div id="alert-pioggia" class="alert-dynamic">Strada bagnata</div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/mqtt/dist/mqtt.min.js"></script>

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

                // Aggiornamento campi base
                document.getElementById('timestamp').innerText = data.ricevuto_at;
                document.getElementById('val_temp').innerText = payload.temperatura ?? '--';
                document.getElementById('val_umid').innerText = payload.umidita ?? '--';
                document.getElementById('val_traffico').innerText = payload.traffico ?? '--';

                // --- CALCOLO LUMINOSITA' E VISIBILITA' ---
                if (payload.luminosita !== undefined && payload.luminosita !== null) {
                    const cE_luce = payload.luminosita === true || payload.luminosita === "true";

                    if (cE_luce) {
                        document.getElementById('val_visibilita').innerText = 'Buona';
                        document.getElementById('label_visibilita').innerText = 'Illuminazione sufficiente';
                    } else {
                        document.getElementById('val_visibilita').innerText = 'Scarsa';
                        document.getElementById('label_visibilita').innerText = 'Poca illuminazione';
                    }
                } else {
                    document.getElementById('val_visibilita').innerText = '--';
                    document.getElementById('label_visibilita').innerText = '';
                }

                // --- VALUTAZIONE SENSORE PIOGGIA (Booleano) ---
                if (payload.acqua !== undefined && payload.acqua !== null) {
                    const elementoPioggia = document.getElementById('val_pioggia');
                    // Se true rileva acqua, se false è asciutto
                    if (payload.acqua === true || payload.acqua === "true") {
                        elementoPioggia.innerText = 'Bagnato ';
                    } else {
                        elementoPioggia.innerText = 'Asciutto ';
                        elementoPioggia.style.color = '#2c3e50';
                    }
                } else {
                    document.getElementById('val_pioggia').innerText = '--';
                    document.getElementById('val_pioggia').style.color = '#2c3e50';
                }


                document.getElementById('stato-connessione').innerText = 'Online';
                document.getElementById('stato-connessione').style.backgroundColor = '#2ecc71';

                // --- LOGICA ALLERTE FISSE ---
                const temp = parseFloat(payload.temperatura);
                const traffico = payload.traffico ? payload.traffico.toString().trim().toLowerCase() : '';
                const lum = parseInt(payload.luminosita);
                const acqua = payload.acqua === true || payload.acqua === "true";

                // 1. Reset globale dello stato visivo degli allarmi
                document.getElementById('alert-ghiaccio').classList.remove('active');
                document.getElementById('alert-calore').classList.remove('active');
                document.getElementById('alert-traffico').classList.remove('active');
                document.getElementById('alert-visibilita').classList.remove('active');
                document.getElementById('alert-pioggia').classList.remove('active');

                // 2. Valutazione e attivazione condizionale

                // Controllo termico per ghiaccio o surriscaldamento
                if (!isNaN(temp)) {
                    if (temp <= 3) document.getElementById('alert-ghiaccio').classList.add('active');
                    else if (temp >= 28) document.getElementById('alert-calore').classList.add('active');
                }

                // Controllo densità traffico
                if (traffico === 'alto') {
                    document.getElementById('alert-traffico').classList.add('active');
                }

                // Controllo luminosità ambientale (soglia critica impostata sotto il 20%)
                if (!isNaN(lum) && lum < 20) {
                    document.getElementById('alert-visibilita').classList.add('active');
                }

                // Controllo stato asfalto tramite sensore pioggia
                if (acqua) {
                    document.getElementById('alert-pioggia').classList.add('active');
                }
            }else {
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
    const listaSemaforiCompleta = <?php echo $semafori_json; ?>;

    document.querySelectorAll('.semaforo-item').forEach(item => {
        item.addEventListener('click', function() {
            document.querySelectorAll('.semaforo-item').forEach(el => el.classList.remove('active'));
            this.classList.add('active');

            const idSemaforo = this.getAttribute('data-id');
            const nomeIncrocio = this.getAttribute('data-nome');
            const lat = this.getAttribute('data-lat');
            const lng = this.getAttribute('data-lng');

            const datiDispositivo = listaSemaforiCompleta.find(s => s.id == idSemaforo);
            const seriale = datiDispositivo ? datiDispositivo.codice_seriale : 'Sconosciuto';

            // Reset visivo di TUTTI i campi sensori in attesa del nuovo caricamento
            document.getElementById('titolo-incrocio').innerText = nomeIncrocio;
            document.getElementById('val_temp').innerText = '--';
            document.getElementById('val_umid').innerText = '--';
            document.getElementById('val_traffico').innerText = '--';
            document.getElementById('val_visibilita').innerText = '--';
            document.getElementById('label_visibilita').innerText = '';
            document.getElementById('val_pioggia').innerText = '--';
            document.getElementById('val_pioggia').style.color = '#2c3e50';
            document.getElementById('timestamp').innerText = 'Caricamento in corso...';

            if (lat && lng) {
                const testoMarker = encodeURIComponent(`Seriale: ${seriale}`);
                document.getElementById('mappa-google').src = `https://maps.google.com/maps?q=${lat},${lng}+(${testoMarker})&z=18&output=embed`;
            }

            // Riavvvio del ciclo sul nuovo ID
            semaforoCorrente = idSemaforo;
            fetchDatiSensori();
        });
    });

    // Avvio ciclo di polling iniziale
    if (semaforoCorrente) {
        fetchDatiSensori();
        pollingInterval = setInterval(fetchDatiSensori, 2000);
    }



    // Callback quando arriva un messaggio dall'ESP32
    if (typeof mqtt === 'undefined') {
        alert("Errore critico: il browser blocca ancora internet. Usa Google Chrome!");
    } else {
        console.log("Libreria MQTT.js caricata correttamente!");

        // Configurazione per HiveMQ Cloud (Nota l'uso di 'wss://' per la sicurezza TLS)
        const brokerUrl = "wss://bf89bd34a48e407abd232255e6194d47.s1.eu.hivemq.cloud:8884/mqtt";
        const opzioniMqtt = {
            clientId: "web_client_" + Math.random().toString(16).substring(2, 8),
            username: "DevWebsite", // <-- Sostituisci se hai cambiato utente
            password: "DevWebsite123" // <-- Sostituisci se hai cambiato password
        };

        // Avvio connessione
        const client = mqtt.connect(brokerUrl, opzioniMqtt);

        function impostaStatoSemaforo(statoRichiesto) {
            // Verifica lo stato della connessione WebSocket prima dell'invio
            if (!client || !client.connected) {
                console.error("Errore: Client MQTT non connesso al broker.");
                return;
            }

            // Pubblica il payload di testo sul topic di comando
            client.publish('esp/comandi', statoRichiesto, function(err) {
                if (err) {
                    console.error("Fallimento pubblicazione MQTT:", err);
                } else {
                    console.log("Comando hardware inviato: " + statoRichiesto);
                }
            });
        }

        // Evento: Connessione Riuscita
        client.on('connect', function () {
            console.log("✅ Connesso al broker HiveMQ via WebSocket!");

            // Iscrizione al topic del semaforo
            client.subscribe('esp/luce', function (err) {
                if (!err) {
                    console.log("In ascolto dei comandi sul topic esp32/luci...");
                }
            });
        });

        // Evento: Messaggio Ricevuto
        client.on('message', function (topic, message) {
            // message arriva come buffer, lo trasformiamo in stringa testuale
            const comando = message.toString().trim().toLowerCase();
            console.log("Ricevuto comando: " + comando);

            aggiornaStatoGrafico(comando);
        });

        // Evento: Errore
        client.on('error', function (error) {
            console.error("Errore di connessione:", error);
            document.getElementById('stato-connessione').innerText = 'Errore Broker';
            document.getElementById('stato-connessione').style.backgroundColor = '#e74c3c';
        });
    }

    // Funzione per aggiornare i colori a schermo
    function aggiornaStatoGrafico(stato) {
        // Spegniamo prima tutte le luci
        document.getElementById('luce-rosso').classList.remove('attiva');
        document.getElementById('luce-giallo').classList.remove('attiva');
        document.getElementById('luce-verde').classList.remove('attiva');

        // Accendiamo quella ricevuta dal messaggio
        if (stato === 'rosso') {
            document.getElementById('luce-rosso').classList.add('attiva');
        } else if (stato === 'giallo') {
            document.getElementById('luce-giallo').classList.add('attiva');
        } else if (stato === 'verde') {
            document.getElementById('luce-verde').classList.add('attiva');
        } else if (stato === 'spento') {
            document.getElementById('luce-rosso').classList.remove('attiva');
            document.getElementById('luce-giallo').classList.remove('attiva');
            document.getElementById('luce-verde').classList.remove('attiva');
        }
    }


</script>



</body>
</html>