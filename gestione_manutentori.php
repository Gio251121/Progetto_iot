<?php
session_start();
require_once 'db.php';
/** @var PDO $pdo */

// Controllo validita sessione e permessi di amministrazione (ruolo = 1)
if (!isset($_SESSION['utente_id']) || $_SESSION['ruolo_id'] != 1) {
    header("Location: dashboard.php");
    exit;
}

$messaggio = '';

// Elaborazione dei form inviati tramite metodo POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Inserimento nuovo utente manutentore
    if (isset($_POST['azione']) && $_POST['azione'] === 'nuovo_manutentore') {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        // Generazione hash crittografico della password
        $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);

        try {
            // Esecuzione query preparata per prevenire SQL injection
            $stmt = $pdo->prepare("INSERT INTO utenti (username, email, password, ruolo_id) VALUES (?, ?, ?, 2)");
            $stmt->execute([$username, $email, $password]);
            $messaggio = "<div class='alert success'>Registrazione completata con successo.</div>";
        } catch (PDOException $e) {
            $messaggio = "<div class='alert error'>Errore: Username o Email già presenti a sistema.</div>";
        }
    }

    // Associazione semaforo-manutentore
    if (isset($_POST['azione']) && $_POST['azione'] === 'assegna_semaforo') {
        $manutentore_id = (int)$_POST['manutentore_id'];
        $semaforo_id = (int)$_POST['semaforo_id'];

        try {
            // Inserimento record nella tabella di giunzione
            $stmt = $pdo->prepare("INSERT INTO semafori_manutentori (utente_id, semaforo_id) VALUES (?, ?)");
            $stmt->execute([$manutentore_id, $semaforo_id]);
            $messaggio = "<div class='alert success'>Autorizzazione hardware assegnata correttamente.</div>";
        } catch (PDOException $e) {
            $messaggio = "<div class='alert error'>Errore: Il dispositivo è già assegnato a questo tecnico.</div>";
        }
    }
}

// Estrazione dizionari per i menu a tendina
$stmt_manutentori = $pdo->query("SELECT id, username FROM utenti WHERE ruolo_id = 2 ORDER BY username ASC");
$manutentori = $stmt_manutentori->fetchAll(PDO::FETCH_ASSOC);

$stmt_semafori = $pdo->query("SELECT id, nome_incrocio, codice_seriale FROM semafori ORDER BY id ASC");
$semafori = $stmt_semafori->fetchAll(PDO::FETCH_ASSOC);

// Estrazione report aggregato per la tabella riassuntiva
// GROUP_CONCAT unisce in una singola stringa tutti i semafori associati a un utente
$query_report = "
    SELECT u.username, u.email, GROUP_CONCAT(s.nome_incrocio SEPARATOR ', ') as impianti 
    FROM utenti u 
    LEFT JOIN semafori_manutentori sm ON u.id = sm.utente_id 
    LEFT JOIN semafori s ON sm.semaforo_id = s.id 
    WHERE u.ruolo_id = 2 
    GROUP BY u.id
    ORDER BY u.username ASC
";
$report = $pdo->query($query_report)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestione Tecnici - Admin Console</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Layout strutturale admin console */
        .admin-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .admin-card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border: 1px solid #e0e6ed; }

        /* Stili tipografici e componenti form */
        .admin-card h3 { margin-bottom: 20px; font-size: 1.2rem; color: #2c3e50; border-bottom: 2px solid #ecf0f1; padding-bottom: 10px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; color: #7f8c8d; font-size: 0.9rem; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #bdc3c7; border-radius: 5px; font-size: 0.95rem; transition: border-color 0.3s; }
        .form-control:focus { border-color: #3498db; outline: none; }

        /* Stili bottoni interazione */
        .btn-submit { width: 100%; background: #2c3e50; color: white; border: none; padding: 12px; font-size: 1rem; border-radius: 5px; cursor: pointer; font-weight: 600; margin-top: 10px; }
        .btn-submit:hover { background: #1a252f; }
        .btn-action { background: #2ecc71; }
        .btn-action:hover { background: #27ae60; }

        /* Stili feedback visivo */
        .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; font-weight: 500; }
        .alert.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        /* Stili Data Table riassuntiva */
        .data-table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 0.95rem; }
        .data-table th, .data-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e0e6ed; }
        .data-table th { background-color: #f8f9fa; color: #2c3e50; font-weight: 600; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.5px; }
        .data-table tr:hover { background-color: #fcfcfc; }
        .empty-cell { color: #a4b0be; font-style: italic; }
    </style>
</head>
<body>

<?php require_once 'navbar.php'; ?>

<div class="app-container">
    <main class="main-content" style="max-width: 1100px; margin: 0 auto; display: block;">

        <a href="dashboard.php" class="btn-back">Torna alla Dashboard</a>

        <h1 style="margin-bottom: 25px; color: #2c3e50;">Access Control Management</h1>

        <?php if ($messaggio) echo $messaggio; ?>

        <div class="admin-grid">

            <div class="admin-card">
                <h3>Creazione Profilo</h3>
                <form method="POST" action="gestione_manutentori.php">
                    <input type="hidden" name="azione" value="nuovo_manutentore">

                    <div class="form-group">
                        <label for="username">Username di Sistema</label>
                        <input type="text" id="username" name="username" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Indirizzo Email Aziendale</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>

                    <button type="submit" class="btn-submit">Registra Account</button>
                </form>
            </div>

            <div class="admin-card">
                <h3>Assegnazione Semaforo IoT</h3>
                <form method="POST" action="gestione_manutentori.php">
                    <input type="hidden" name="azione" value="assegna_semaforo">

                    <div class="form-group">
                        <label for="manutentore_id">Seleziona Operatore</label>
                        <select id="manutentore_id" name="manutentore_id" class="form-control" required>
                            <option value="">Nessuna selezione </option>
                            <?php foreach ($manutentori as $m): ?>
                                <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['username']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="semaforo_id">Seleziona Dispositivo Hardware</label>
                        <select id="semaforo_id" name="semaforo_id" class="form-control" required>
                            <option value="">Nessuna selezione</option>
                            <?php foreach ($semafori as $s): ?>
                                <option value="<?php echo $s['id']; ?>">
                                    <?php echo htmlspecialchars($s['codice_seriale']); ?> - <?php echo htmlspecialchars($s['nome_incrocio']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn-submit btn-action">Genera Autorizzazione</button>
                </form>
            </div>

        </div>

        <div class="admin-card" style="margin-bottom: 40px;">
            <h3>Report Assegnazioni Correnti</h3>
            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email Contatto</th>
                        <th>Impianti in Gestione</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($report as $row): ?>
                        <tr>
                            <td style="font-weight: 600;"><?php echo htmlspecialchars($row['username']); ?></td>
                            <td><?php echo htmlspecialchars($row['email'] ?: '-'); ?></td>
                            <td>
                                <?php if ($row['impianti']): ?>
                                    <?php echo htmlspecialchars($row['impianti']); ?>
                                <?php else: ?>
                                    <span class="empty-cell">Nessun impianto assegnato</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

</body>
</html>