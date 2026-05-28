<?php
// Caricamento dipendenze Composer
require __DIR__ . '/vendor/autoload.php';

// Inclusione connessione PDO centralizzata
require_once 'db.php';

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

// Parametri broker MQTT
$mqtt_host = 'bf89bd34a48e407abd232255e6194d47.s1.eu.hivemq.cloud';
$mqtt_port = 8883;
$mqtt_user = 'DevWebsite';
$mqtt_pass = 'DevWebsite123';

// Inizializzazione client MQTT
$client_id = uniqid('php_listener_', true);
$mqtt = new MqttClient($mqtt_host, $mqtt_port, $client_id);

// Configurazione TLS
$settings = (new ConnectionSettings)
    ->setUseTls(true)
    ->setUsername($mqtt_user)
    ->setPassword($mqtt_pass);

try {
    // Connessione al broker
    $mqtt->connect($settings, true);
    echo "🟢 Connesso al broker HiveMQ!\n";

    // Sottoscrizione per login ESP32
    $mqtt->subscribe('esp32/login_request', function (string $topic, string $message) use ($mqtt, $pdo) {
        $dati = json_decode($message, true);
        $codice_seriale = $dati['codice_seriale'] ?? '';
        $password_ricevuta = $dati['password'] ?? '';
        $replyTo = $dati['reply_to'] ?? 'esp32/risposta';

        echo $replyTo;

        if (empty($replyTo)) return;

        // Recuperiamo il semaforo dal DB
        $stmt = $pdo->prepare("SELECT id, nome_incrocio, password, latitudine, longitudine FROM semafori WHERE codice_seriale = :codice_seriale LIMIT 1");
        $stmt->execute(['codice_seriale' => $codice_seriale]);
        $semaforo = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($semaforo) {
            if (empty($password_ricevuta)) {
                echo "✅ Login UTENTE: " . $codice_seriale . "\n";
                $risposta = json_encode([
                    'stato' => 1,
                    'nome_incrocio' => $semaforo['nome_incrocio'],
                    'ruolo' => 'utente',
                    'latitudine' => $semaforo['latitudine'] !== null ? (float)$semaforo['latitudine'] : 0.0,
                    'longitudine' => $semaforo['longitudine'] !== null ? (float)$semaforo['longitudine'] : 0.0
                ]);
            } else {
                if (password_verify($password_ricevuta, $semaforo['password'])) {
                    echo "🛠️ Login MANUTENTORE: " . $codice_seriale . "\n";
                    $risposta = json_encode([
                        'stato' => 1,
                        'nome_incrocio' => $semaforo['nome_incrocio'],
                        'ruolo' => 'manutentore',
                        'latitudine' => $semaforo['latitudine'] !== null ? (float)$semaforo['latitudine'] : 0.0,
                        'longitudine' => $semaforo['longitudine'] !== null ? (float)$semaforo['longitudine'] : 0.0
                    ]);
                } else {
                    echo "❌ Login FALLITO (Password errata): " . $codice_seriale . "\n";
                    $risposta = json_encode(['stato' => 0]);
                }
            }
        } else {
            echo "❌ Login FALLITO (Seriale inesistente): " . $codice_seriale . "\n";
            $risposta = json_encode(['stato' => 0]);
        }

        $mqtt->publish($replyTo, $risposta, 1);
    }, 1);

    $mqtt->subscribe('esp/semafori/request', function (string $topic, string $message) use ($mqtt, $pdo) {
        echo "🗺️ Richiesta elenco semafori per mappa ricevuta.\n";

        try {
            // Estrazione di tutti i semafori censiti nel sistema
            $stmt = $pdo->prepare("SELECT codice_seriale, nome_incrocio, latitudine, longitudine FROM semafori");
            $stmt->execute();
            $listaSemafori = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Cast esplicito dei dati numerici decimali per evitare l'invio di stringhe nel JSON
            foreach ($listaSemafori as &$semaforo) {
                $semaforo['latitudine'] = $semaforo['latitudine'] !== null ? (float)$semaforo['latitudine'] : null;
                $semaforo['longitudine'] = $semaforo['longitudine'] !== null ? (float)$semaforo['longitudine'] : null;
            }

            // Serializzazione in formato Array JSON strutturato
            $payloadRisposta = json_encode($listaSemafori);

            // Pubblicazione sul canale fisso di risposta monitorato dalle applicazioni
            $mqtt->publish('esp/semafori', $payloadRisposta, 1);
            echo "✅ Elenco inviato correttamente su topic 'esp/semafori'\n";

        } catch (\Exception $e) {
            echo "❌ Errore estrazione dati geografici: " . $e->getMessage() . "\n";
        }
    }, 1);

    $mqtt->subscribe('esp/rfid', function (string $topic, string $message) use ($mqtt, $pdo) {
        echo " scheda rfid ricevuta.\n";

        try {

            $codice_seriale = trim($message);

            $stmt = $pdo->prepare("SELECT rfid, livello FROM disabili WHERE rfid = ?");
            $stmt->execute([$codice_seriale]);

            $record = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($record) {

                $json = [
                    'rfid' => $record['rfid'],
                    'livello' => $record['livello']
                ];


                $payload = json_encode($json);
                echo $payload;
                $mqtt->publish('esp/rfid/risposta', $payload, 1);
            }else{
                $mqtt->publish('esp/rfid/risposta', "non valido", 1);
            }


        } catch (\Exception $e) {
            echo "❌ Errore estrazione dati geografici: " . $e->getMessage() . "\n";
        }
    }, 1);

    $mqtt->subscribe('esp/rfid/risposta', function (string $topic, string $message) use ($mqtt, $pdo) {

        try {

            echo " \n scheda valida \n";


        } catch (\Exception $e) {
            echo "❌ Errore estrazione dati geografici: " . $e->getMessage() . "\n";
        }
    }, 1);

    // Sottoscrizione per ricezione dati sensori su topic statico
    $mqtt->subscribe('esp/dati', function (string $topic, string $message) use ($pdo) {
        // Assegnazione statica dell'ID semaforo
        $semaforo_id = 1;
        echo $message . "\n";

        // Validazione sintassi JSON compatibile con PHP 8.2
        json_decode($message);
        if (json_last_error() === JSON_ERROR_NONE) {
            // Inserimento a database del payload grezzo
            $stmt = $pdo->prepare("INSERT INTO dati_sensori (semaforo_id, topic, payload) VALUES (?, ?, ?)");
            $stmt->execute([$semaforo_id, $topic, $message]);
            echo "📊 Dati salvati per semaforo ID: $semaforo_id\n";
        } else {
            echo "⚠️ Payload JSON non valido ricevuto su $topic\n";
        }
    }, 1);


    $mqtt->subscribe('esp/luce', function (string $topic, string $message) use ($pdo) {
        // Assegnazione statica dell'ID semaforo

        if($message == "verde"){
            echo "verde \n";
        } elseif ($message == "giallo"){
            echo "giallo \n";

        } elseif ($message == "rosso"){
            echo "rosso \n";

        } elseif ($message == "spento"){
            echo "spento \n";
        }else {
            echo "⚠️ Payload JSON non valido ricevuto su $topic\n";
        }
    }, 1);

    echo "👂 In ascolto su tutti i topic configurati...\n";

    // Avvio ciclo di ascolto continuo
    $mqtt->loop(true);

} catch (\Exception $e) {
    echo "⚠️ Errore MQTT: " . $e->getMessage() . "\n";
}
?>
