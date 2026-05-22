<?php
// Terminazione sicura della sessione utente
session_start();
session_unset();
session_destroy();

// Reindirizzamento al gateway di accesso
header("Location: login.php");
exit;
?>
