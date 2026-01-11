<?php
/**
 * Správa profilu uživatele - změna jména a hesla
 *
 * Funkcionality:
 * - Změna uživatelského jména (3+ znaků, kontrola na duplicity)
 * - Změna hesla (5+ znaků, ověření stávajícího hesla)
 * - Okamžitá aktualizace session po změně jména
 * - Server-side validace všech polí
 *
 * Bezpečnost:
 * - Vyžaduje ověření stávajícího hesla
 * - Hesla jsou hashována pomocí password_hash()
 * - Všechny vstupy jsou validovány a escapovány
 *
 * @package GastroV2
 * @requires session Uživatel musí být přihlášen
 */

session_start();

// Ověření, zda je uživatel přihlášen
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

/** @var array<string,string> $chyby Pole chybových zpráv podle klíče pole */
$chyby = [];
/** @var string $uspech Zpráva o úspěchu z parametrů URL */
$uspech = isset($_GET['uspech']) ? "Údaje byly úspěšně aktualizovány." : "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Načtení uživatelů
    /** @var array $uzivatele Plný seznam uživatelů ze souboru */
    $uzivatele = json_decode(file_get_contents('data/users.json'), true) ?: [];
    
    // Hledání aktuálního uživatele
    /** @var int|null $uzivatelIndex Index aktuálního uživatele v poli */
    $uzivatelIndex = null;
    /** @var array|null $aktualniUzivatel Data aktuálního uživatele */
    $aktualniUzivatel = null;
    
    foreach ($uzivatele as $index => $u) {
        if ($u['id'] === $_SESSION['user_id']) {
            $uzivatelIndex = $index;
            $aktualniUzivatel = $u;
            break;
        }
    }
    
    if ($aktualniUzivatel === null) {
        $chyby['obecna'] = "Uživatel nebyl nalezen.";
    } else {
        // Ověření stávajícího hesla
        $staveHeslo = trim($_POST['stare_heslo']);
        
        if (empty($staveHeslo)) {
            $chyby['stare_heslo'] = "Stávající heslo je povinné.";
        } elseif (!password_verify($staveHeslo, $aktualniUzivatel['password'])) {
            $chyby['stare_heslo'] = "Stávající heslo je nesprávné.";
        }
        
        // Validace nového jména (pokud chce změnit)
        $noveJmeno = trim($_POST['nove_jmeno']);
        
        if (!empty($noveJmeno)) {
            if (strlen($noveJmeno) < 3) {
                $chyby['nove_jmeno'] = "Nové jméno musí mít alespoň 3 znaky.";
            } else {
                // Kontrola, zda je jméno již obsazené
                foreach ($uzivatele as $index => $u) {
                    if ($u['id'] !== $_SESSION['user_id'] && $u['username'] === $noveJmeno) {
                        $chyby['nove_jmeno'] = "Toto jméno je již obsazené.";
                        break;
                    }
                }
            }
        }
        
        // Validace nového hesla (pokud chce změnit)
        $noveHeslo1 = $_POST['nove_heslo1'];
        $noveHeslo2 = $_POST['nove_heslo2'];
        
        if (!empty($noveHeslo1) || !empty($noveHeslo2)) {
            if (empty($noveHeslo1)) {
                $chyby['nove_heslo1'] = "Nové heslo je povinné.";
            } elseif (strlen($noveHeslo1) < 5) {
                $chyby['nove_heslo1'] = "Nové heslo musí mít alespoň 5 znaků.";
            }
            
            if ($noveHeslo1 !== $noveHeslo2) {
                $chyby['nove_heslo2'] = "Zadaná hesla se neshodují.";
            }
        }
        
        // Pokud nejsou chyby, uložit změny
        if (empty($chyby)) {
            // Pokud byl zadán nový username
            if (!empty($noveJmeno)) {
                $uzivatele[$uzivatelIndex]['username'] = $noveJmeno;
                $_SESSION['username'] = $noveJmeno;
            }
            
            // Pokud bylo zadáno nové heslo
            if (!empty($noveHeslo1)) {
                $uzivatele[$uzivatelIndex]['password'] = password_hash($noveHeslo1, PASSWORD_BCRYPT);
            }
            
            // Uložení zpět do JSON
            file_put_contents('data/users.json', json_encode($uzivatele, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            header('Location: profile.php?uspech=1');
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Můj profil</title>
    <link rel="stylesheet" href="style.css">
</head>
<body data-prihlasen="true">
    <header>
        <h1>Restaurace U FELáka</h1>
        <nav>
            <a href="index.php">Menu</a>
            <a href="orders.php">Objednávky</a>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="admin.php" class="link-admin">Administrace</a>
            <?php endif; ?>
            <button id="btn-kosik" type="button">Košík (<span id="pocet-v-kosiku">0</span>)</button>
            <a href="profile.php" class="nav-active">Profil</a>
            <a href="logout.php">Odhlásit (<?= htmlspecialchars($_SESSION['username']) ?>)</a>
        </nav>
    </header>

    <main>
        <h2>Můj profil</h2>
        
        <?php if ($uspech): ?>
            <div class="alert alert-success"><?= htmlspecialchars($uspech) ?></div>
        <?php endif; ?>
        
        <?php if (isset($chyby['obecna'])): ?>
            <div class="alert alert-danger"><?= $chyby['obecna'] ?></div>
        <?php endif; ?>
        
        <form method="post" class="profile-form" novalidate>
            <fieldset>
                <legend>Změna údajů</legend>
                
                <div class="form-group">
                    <label for="stare_heslo" class="povinne">Stávající heslo:</label>
                    <input 
                        type="password" 
                        id="stare_heslo" 
                        name="stare_heslo" 
                        required 
                        class="<?= isset($chyby['stare_heslo']) ? 'input-error' : '' ?>"
                    />
                    <?php if (isset($chyby['stare_heslo'])): ?>
                        <span class="chyba"><?= htmlspecialchars($chyby['stare_heslo']) ?></span>
                    <?php endif; ?>
                </div>
                
                <hr class="form-divider">
                
                <div class="form-group">
                    <label for="nove_jmeno">Nové uživatelské jméno (ponechte prázdné, chcete-li zachovat):</label>
                    <input 
                        type="text" 
                        id="nove_jmeno" 
                        name="nove_jmeno"
                        placeholder="<?= htmlspecialchars($_SESSION['username']) ?>"
                        class="<?= isset($chyby['nove_jmeno']) ? 'input-error' : '' ?>"
                    />
                    <?php if (isset($chyby['nove_jmeno'])): ?>
                        <span class="chyba"><?= $chyby['nove_jmeno'] ?></span>
                    <?php endif; ?>
                </div>
                
                <hr class="form-divider">
                
                <div class="form-group">
                    <label for="nove_heslo1">Nové heslo (ponechte prázdné, chcete-li zachovat):</label>
                    <input 
                        type="password" 
                        id="nove_heslo1" 
                        name="nove_heslo1"
                        class="<?= isset($chyby['nove_heslo1']) ? 'input-error' : '' ?>"
                    />
                    <?php if (isset($chyby['nove_heslo1'])): ?>
                        <span class="chyba"><?= $chyby['nove_heslo1'] ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="nove_heslo2">Potvrzení nového hesla:</label>
                    <input 
                        type="password" 
                        id="nove_heslo2" 
                        name="nove_heslo2"
                        class="<?= isset($chyby['nove_heslo2']) ? 'input-error' : '' ?>"
                    />
                    <?php if (isset($chyby['nove_heslo2'])): ?>
                        <span class="chyba"><?= $chyby['nove_heslo2'] ?></span>
                    <?php endif; ?>
                </div>
                
                <button type="submit" class="btn-primary">Uložit změny</button>
            </fieldset>
        </form>
    </main>

    <script src="script.js"></script>
</body>
</html>
