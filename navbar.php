<?php
// Determinazione dell'etichetta del ruolo in base alla sessione attiva
$etichetta_ruolo = (isset($_SESSION['ruolo_id']) && $_SESSION['ruolo_id'] == 1) ? 'Admin' : 'Tecnico';
?>
<nav class="navbar">
    <div class="brand">SemaNet IoT Control</div>
    <?php if (isset($_SESSION['ruolo_id']) && $_SESSION['ruolo_id'] == 1): ?>
        <a href="gestione_manutentori.php" style="background-color: #34495e; color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-weight: 600; font-size: 0.9rem; margin-left: 15px; transition: background-color 0.2s;">
             Gestione Manutentori
        </a>

        <a href="gestione_semafori.php" style="background-color: #34495e; color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-weight: 600; font-size: 0.9rem; margin-left: 15px; transition: background-color 0.2s;">
             Gestione Semafori
        </a>
    <?php endif; ?>
    <div class="user-info">
        <span>Operatore: <?php echo $etichetta_ruolo; ?></span>
        <a href="logout.php" class="btn-logout">Disconnetti</a>
    </div>

</nav>
