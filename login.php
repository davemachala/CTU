<?php
/**
 * Přihlášení uživatele
 *
 * Zpracování:
 * - Ověření uživatelského jména a hesla vůči data/users.json
 * - Nastavení session proměnných při úspěšném přihlášení
 * - Přesměrování na index.php (POST-Redirect-GET)
 *
 * @package GastroV2
 */
session_start();

$chybaLogin = "";
$userValue = ""; // Pro předvyplnění

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userValue = trim($_POST['username']); // Uložíme si, co uživatel zadal
    $pass = $_POST['password'];
    
    $uzivatele = json_decode(file_get_contents('data/users.json'), true) ?: [];
    
    $uspech = false;
    foreach ($uzivatele as $u) {
        if ($u['username'] === $userValue) {
            if (password_verify($pass, $u['password'])) { //
                $_SESSION['user_id'] = $u['id'];
                $_SESSION['username'] = $u['username'];
                $_SESSION['role'] = $u['role'];
                header('Location: index.php');
                exit;
            }
        }
    }
    
    // Pokud jsme prošli cyklus a nenašli shodu -> chyba
    $chybaLogin = "Zadali jste nesprávné jméno nebo heslo.";
}
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Přihlášení</title>
    <link rel="stylesheet" href="style.css">
</head>
<body data-prihlasen="false">
    <header><h1>Přihlášení</h1><nav><a href="index.php">Zpět</a></nav></header>
    <main>
        
        <?php if ($chybaLogin): ?>
            <div class="alert alert-danger"><?= $chybaLogin ?></div>
        <?php endif; ?>
        
        <form method="post" novalidate>
            
            <label for="jmeno" class="povinne">Jméno:</label>
            <input type="text" id="jmeno" name="username" 
                   value="<?= htmlspecialchars($userValue) ?>" 
                   class="<?= $chybaLogin ? 'input-chyba' : '' ?>"
                   required>
            
            <label for="heslo" class="povinne">Heslo:</label>
            <input type="password" id="heslo" name="password" 
                   class="<?= $chybaLogin ? 'input-chyba' : '' ?>"
                   required>
            
            <button type="submit">Přihlásit se</button>
        </form>
    </main>
    <script src="script.js"></script>
</body>
</html>