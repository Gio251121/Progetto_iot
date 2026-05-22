<?php
// Determinazione dell'etichetta del ruolo in base alla sessione attiva
$etichetta_ruolo = (isset($_SESSION['ruolo_id']) && $_SESSION['ruolo_id'] == 1) ? 'Admin' : 'Tecnico';
?>
<nav class="navbar">
    <div class="brand">SemaNet IoT Control</div>
    <div class="user-info">
        <span>Operatore: <?php echo $etichetta_ruolo; ?></span>
        <a href="logout.php" class="btn-logout">Disconnetti</a>
    </div>
</nav>
