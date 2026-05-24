<?php
// Inizializzazione sessione
session_start();

// Inclusione connessione PDO
require 'db.php';
/** @var PDO $pdo */

// Elaborazione della richiesta POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Estrazione parametri utente
    $stmt = $pdo->prepare("SELECT id, username, password, ruolo_id FROM utenti WHERE username = ?");
    $stmt->execute([$username]);
    $utente = $stmt->fetch(PDO::FETCH_ASSOC);

    // Validazione crittografica della password
    if ($utente && password_verify($password, $utente['password'])) {
        // Popolamento variabili di sessione e reindirizzamento
        $_SESSION['utente_id'] = $utente['id'];
        $_SESSION['ruolo_id'] = $utente['ruolo_id'];
        header("Location: dashboard.php");
        exit;
    } else {
        $errore = "Username o password errati.";
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Accesso - SemaNet IoT</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-body">

<div class="login-card">
    <h2>SemaNet Control</h2>

    <?php if (isset($errore)): ?>
        <div class="alert"><?php echo $errore; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required autocomplete="username">
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">
        </div>

        <button type="submit">Accedi</button>
    </form>
</div>

</body>
</html>