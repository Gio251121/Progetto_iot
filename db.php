<?php
// Connessione al database tramite PDO
$host = '127.0.0.1';
$db   = 'semaforo_iot';
$user = 'root'; // Inserire le credenziali di produzione
$pass = '';     // Inserire le credenziali di produzione
$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die("Errore di connessione al database: " . $e->getMessage());
}
?>
