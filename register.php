<?php
/**
 * Registrační formulář pro nové uživatele
 *
 * Zpracování:
 * - Kontrola uživatelského jména (3+ znaků, jedinečné)
 * - Kontrola hesla (5+ znaků, musí se shodovat)
 * - Vytvoření nového uživatele v data/users.json
 * - Prvi registrovaný uživatel je automaticky admin
 *
 * @package GastroV2
 * @var string $chyby Pole chybových zpráv
 * @var string $uspech Zpráva o úspěšné registraci
 * @var string $userValue Předvyplněné jméno uživatele
 */

session_start();

/** @var array<string,string> $chyby Pole chyb (např. $chyby['username']) */
$chyby = [];
/** @var string $uspech Zpráva o úspěchu po PRG */
$uspech = isset($_GET['uspech']) ? "Registrace byla úspěšná! Můžete se přihlásit." : "";

// Hodnoty pro předvyplnění (aby nezmizely po chybě)
$userValue = ""; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Sanitace a uložení vstupu do proměnné pro "sticky form"
    /** @var string $userValue Předvyplněné jméno (sanitované) */
    $userValue = trim($_POST['username']);
    /** @var string $pass1 První zadání hesla */
    $pass1 = $_POST['pass1'];
    /** @var string $pass2 Potvrzení hesla */
    $pass2 = $_POST['pass2'];

    // 2. VALIDACE JEDNOTLIVÝCH POLÍ

    // -- Uživatelské jméno --
    if (empty($userValue)) {
        $chyby['username'] = "Uživatelské jméno je povinné.";
    } elseif (strlen($userValue) < 3) {
        $chyby['username'] = "Jméno musí mít alespoň 3 znaky.";
    } else {
        // Kontrola duplicity v databázi
        $uzivatele = json_decode(file_get_contents('data/users.json'), true) ?: [];
        foreach ($uzivatele as $u) {
            if ($u['username'] === $userValue) {
                $chyby['username'] = "Toto jméno je již obsazené.";
                break;
            }
        }
    }

    // -- Heslo --
    if (empty($pass1)) {
        $chyby['pass1'] = "Heslo je povinné.";
    } elseif (strlen($pass1) < 5) {
        $chyby['pass1'] = "Heslo musí mít alespoň 5 znaků.";
    }

    // -- Kontrola hesla znovu --
    if ($pass1 !== $pass2) {
        $chyby['pass2'] = "Zadaná hesla se neshodují."; // pozn. je toto lepší než alert v JS?
    }

    // 3. POKUD NEJSOU ŽÁDNÉ CHYBY -> ULOŽIT
    if (empty($chyby)) {
        $uzivatele = json_decode(file_get_contents('data/users.json'), true) ?: [];
        
        $novyUzivatel = [
            'id' => time(),
            'username' => $userValue,
            'password' => password_hash($pass1, PASSWORD_DEFAULT),
            'role' => (count($uzivatele) === 0) ? 'admin' : 'user'
        ];

        $uzivatele[] = $novyUzivatel;
        file_put_contents('data/users.json', json_encode($uzivatele, JSON_PRETTY_PRINT));
        
        header('Location: register.php?uspech=1');
        exit; 
    }
}
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Registrace</title>
    <link rel="stylesheet" href="style.css">
</head>
<body data-prihlasen="false">

    <header>
        <h1>Registrace</h1>
        <nav><a href="index.php">Zpět na menu</a></nav>
    </header>

    <main>
        <?php if ($uspech): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($uspech) ?> <a href="login.php"><strong>Přihlásit se</strong></a>
            </div>
        <?php endif; ?>

        <form method="post" id="registracni-formular" novalidate> <label for="username" class="povinne">Uživatelské jméno:</label>
            <input type="text" id="username" name="username" 
                   value="<?= htmlspecialchars($userValue) ?>" 
                   class="<?= isset($chyby['username']) ? 'input-chyba' : '' ?>"
                   required>
            
            <?php if (isset($chyby['username'])): ?>
                <span class="zprava-chyba-text"><?= htmlspecialchars($chyby['username']) ?></span>
            <?php endif; ?>


            <label for="heslo1" class="povinne">Heslo:</label>
            <input type="password" id="heslo1" name="pass1" 
                   class="<?= isset($chyby['pass1']) ? 'input-chyba' : '' ?>"
                   required>
            
            <?php if (isset($chyby['pass1'])): ?>
                <span class="zprava-chyba-text"><?= htmlspecialchars($chyby['pass1']) ?></span>
            <?php endif; ?>


            <label for="heslo2" class="povinne">Potvrzení hesla:</label>
            <input type="password" id="heslo2" name="pass2" 
                   class="<?= isset($chyby['pass2']) ? 'input-chyba' : '' ?>"
                   required>
            
            <?php if (isset($chyby['pass2'])): ?>
                <span class="zprava-chyba-text"><?= htmlspecialchars($chyby['pass2']) ?></span>
            <?php endif; ?>

            <button type="submit">Zaregistrovat</button>
        </form>
    </main>
    <script src="script.js"></script>
</body>
</html>