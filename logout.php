<?php
/**
 * Odhlášení uživatele
 *
 * Postup:
 * - Zničení session proměnných na serveru
 * - Vymazání košíku z localStorage na klientu
 * - Přesměrování na index.php
 *
 * @package GastroV2
 */
session_start();

// 1. Zničení session na straně serveru (odhlášení uživatele)
$_SESSION = array(); // Vyprázdnění proměnných
session_destroy();   // Zničení session
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Odhlašování...</title>
</head>
<body>
    <p>Probíhá odhlašování...</p>

    <script>
        // 2. Vymazání košíku na straně klienta (prohlížeče)
        localStorage.removeItem('kosik');

        // 3. Přesměrování na hlavní stránku
        window.location.href = 'index.php';
    </script>
</body>
</html>