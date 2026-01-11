<?php
/**
 * Administrační panel - správa uživatelů, menu a objednávek
 *
 * Sekce:
 * - Správa uživatelů: povýšení/ponížení na admina, smazání, stránkování
 * - Správa menu: vytvoření kategorií, přejmenování, přidání/smazání produktů
 * - Nahrávání obrázků: do assets/ složky
 *
 * POST akce:
 * - smazat_uzivatele: Smazání uživatele (ne sebe)
 * - zmenit_roli: Změna role (admin ↔ user)
 * - nova_kategorie: Vytvoření kategorie
 * - smazat_kategorie: Smazání kategorie s produkty
 * - prejmenovat_kategorie: Přejmenování kategorie
 * - pridat_produkt: Přidání produktu s obrázkem
 * - smazat_produkt: Smazání produktu
 *
 * Bezpečnost:
 * - Přístup jen pro adminy
 * - Admin si nemůže smazat sám sebe
 * - Validace upload souborů
 *
 * @package GastroV2
 * @requires admin role
 */

session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') die("Přístup zamítnut.");

/** @var string $fileMenu Cesta k souboru s menu */
$fileMenu = 'data/menu.json';
/** @var string $fileUsers Cesta k souboru s uživateli */
$fileUsers = 'data/users.json';
/** @var array $menu Načtené menu ze souboru */
$menu = json_decode(file_get_contents($fileMenu), true);
/** @var array $users Načtení uživatelé ze souboru */
$users = json_decode(file_get_contents($fileUsers), true);
if (!$users) $users = [];

// --- ZPRACOVÁNÍ FORMULÁŘŮ ---

/**
 * 1. UŽIVATELÉ: Smazání
 * Smaže uživatele podle ID (admin nesmí smazat sám sebe)
 */
if (isset($_POST['akce']) && $_POST['akce'] === 'smazat_uzivatele') {
    $id = (int)$_POST['user_id'];
    if ($id !== $_SESSION['user_id']) { // Nemazat sebe
        $users = array_filter($users, fn($u) => $u['id'] !== $id);
        file_put_contents($fileUsers, json_encode(array_values($users), JSON_PRETTY_PRINT));
    }
    header("Location: admin.php?tab=users"); exit;
}

/**
 * 2. UŽIVATELÉ: Změna role (Povýšit/Ponížit)
 * Přepíná roli vybraného uživatele mezi 'admin' a 'user'
 */
if (isset($_POST['akce']) && $_POST['akce'] === 'zmenit_roli') {
    $id = (int)$_POST['user_id'];
    if ($id !== $_SESSION['user_id']) { // Neměnit roli sobě
        foreach ($users as &$u) {
            if ($u['id'] === $id) {
                // Přepínač: pokud je admin, bude user, a naopak
                $u['role'] = ($u['role'] === 'admin') ? 'user' : 'admin';
                break;
            }
        }
        file_put_contents($fileUsers, json_encode($users, JSON_PRETTY_PRINT));
    }
    header("Location: admin.php?tab=users"); exit;
}

/**
 * 3. MENU: Nová kategorie
 * Vytvoří novou kategorii s unikátním ID a prázdným seznamem produktů
 */
if (isset($_POST['akce']) && $_POST['akce'] === 'nova_kategorie') {
    $nazev = trim($_POST['nazev_kategorie']);
    if ($nazev) {
        $menu['kategorie'][] = ['id' => time(), 'nazev' => $nazev, 'produkty' => []];
        file_put_contents($fileMenu, json_encode($menu, JSON_PRETTY_PRINT));
        header("Location: admin.php?uspech=kategorie_pridana"); exit;
    }
    header("Location: admin.php"); exit;
}

/**
 * 4. MENU: Smazat kategorii
 * Odstraní kategorii včetně všech jejích produktů
 */
if (isset($_POST['akce']) && $_POST['akce'] === 'smazat_kategorie') {
    $id = (int)$_POST['cat_id'];
    $menu['kategorie'] = array_filter($menu['kategorie'], fn($k) => $k['id'] !== $id);
    $menu['kategorie'] = array_values($menu['kategorie']);
    file_put_contents($fileMenu, json_encode($menu, JSON_PRETTY_PRINT));
    header("Location: admin.php"); exit;
}

/**
 * 5. MENU: Přejmenovat kategorii
 * Aktualizuje název vybrané kategorie
 */
if (isset($_POST['akce']) && $_POST['akce'] === 'prejmenovat_kategorie') {
    $id = (int)$_POST['cat_id'];
    $novyNazev = $_POST['novy_nazev'];
    foreach ($menu['kategorie'] as &$k) {
        if ($k['id'] === $id) { $k['nazev'] = $novyNazev; break; }
    }
    file_put_contents($fileMenu, json_encode($menu, JSON_PRETTY_PRINT));
    header("Location: admin.php"); exit;
}

/**
 * 6. PRODUKTY: Přidat/Smazat
 * Přidává nový produkt do kategorie nebo maže existující produkt
 */
if (isset($_POST['akce']) && $_POST['akce'] === 'pridat_produkt') {
    $catId = (int)$_POST['kategorie_id'];
    $path = "assets/default.jpg";
    if (isset($_FILES['obrazek']) && $_FILES['obrazek']['error'] === 0) {
        $path = "assets/" . time() . "_" . basename($_FILES['obrazek']['name']);
        move_uploaded_file($_FILES['obrazek']['tmp_name'], $path);
    }
    $new = ["id" => time(), "nazev" => trim($_POST['nazev']), "cena" => (int)$_POST['cena'], "obrazek" => $path];
    foreach ($menu['kategorie'] as &$k) { if ($k['id'] === $catId) { $k['produkty'][] = $new; break; }}
    file_put_contents($fileMenu, json_encode($menu, JSON_PRETTY_PRINT));
    header("Location: admin.php?uspech=produkt_pridan"); exit;
}
if (isset($_POST['akce']) && $_POST['akce'] === 'smazat_produkt') {
    $pid = (int)$_POST['id_produktu'];
    foreach ($menu['kategorie'] as &$k) {
        $k['produkty'] = array_values(array_filter($k['produkty'], fn($p) => $p['id'] !== $pid));
    }
    file_put_contents($fileMenu, json_encode($menu, JSON_PRETTY_PRINT));
    header("Location: admin.php"); exit;
}

// --- STRÁNKOVÁNÍ UŽIVATELŮ ---
$pageUsers = isset($_GET['page_users']) ? (int)$_GET['page_users'] : 1;
$limitUsers = 5;
$totalUsers = count($users);
$pagesUsersTotal = ceil($totalUsers / $limitUsers);
$usersSlice = array_slice($users, ($pageUsers - 1) * $limitUsers, $limitUsers);
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Administrace</title>
    <link rel="stylesheet" href="style.css">
</head>
<body data-prihlasen="true">
    <header>
        <h1>Administrace</h1>
        <nav>
            <a href="index.php">Web</a>
            <a href="orders.php">Objednávky</a>
        </nav>
    </header>

    <main>
        <div class="admin-section">
            <h2>Správa Uživatelů</h2>
            <table>
                <tr><th>Jméno</th><th>Role</th><th>Akce</th></tr>
                <?php foreach ($usersSlice as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['username']) ?></td>
                    <td>
                        <?php if($u['role'] === 'admin'): ?>
                            <strong class="text-admin">ADMIN</strong>
                        <?php else: ?>
                            User
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                            <form method="post" class="form-inline form-confirm" data-confirm-msg="Změnit roli uživatele?">
                                <input type="hidden" name="akce" value="zmenit_roli">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <button type="submit" class="btn-warning btn-small">
                                    <?= $u['role'] === 'admin' ? 'Ponížit na User' : 'Povýšit na Admin' ?>
                                </button>
                            </form>
                            
                            <form method="post" class="form-inline form-confirm" data-confirm-msg="Opravdu smazat uživatele?">
                                <input type="hidden" name="akce" value="smazat_uzivatele">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <button type="submit" class="btn-danger btn-small">Smazat</button>
                            </form>
                        <?php else: ?>
                            <small>Nelze upravit sebe</small>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            
            <?php if ($pagesUsersTotal > 1): ?>
                <div class="strankovani">
                    <?php for($i=1; $i<=$pagesUsersTotal; $i++): ?>
                        <a href="?page_users=<?= $i ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="admin-section">
            <h2>Správa Menu</h2>
            
            <form method="post" class="form-highlight">
                <input type="hidden" name="akce" value="nova_kategorie">
                <label>Nová kategorie:</label>
                <input type="text" name="nazev_kategorie" required placeholder="Např. Dezerty">
                <button type="submit">Vytvořit kategorii</button>
            </form>

            <?php foreach ($menu['kategorie'] as $kat): ?>
                <div class="cat-box">
                    <div class="cat-header">
                        <form method="post" class="cat-rename-form">
                            <input type="hidden" name="akce" value="prejmenovat_kategorie">
                            <input type="hidden" name="cat_id" value="<?= $kat['id'] ?>">
                            <input type="text" name="novy_nazev" value="<?= htmlspecialchars($kat['nazev']) ?>">
                            <button type="submit" class="btn-small">Přejmenovat</button>
                        </form>

                        <form method="post" class="form-confirm form-minimal" data-confirm-msg="Smazat celou kategorii včetně produktů?">
                            <input type="hidden" name="akce" value="smazat_kategorie">
                            <input type="hidden" name="cat_id" value="<?= $kat['id'] ?>">
                            <button type="submit" class="btn-danger btn-small">Smazat kat.</button>
                        </form>
                    </div>

                    <table> 
                        <?php foreach ($kat['produkty'] as $prod): ?>
                        <tr> 
                            <td width="50"><img src="<?= htmlspecialchars($prod['obrazek']) ?>" height="30"></td>
                            <td><?= htmlspecialchars($prod['nazev']) // pozn. je třeba obr. zmenšovat? ?></td>
                            <td><?= $prod['cena'] ?> Kč</td>
                            <td>
                                <form method="post" class="form-confirm form-minimal" data-confirm-msg="Smazat produkt?">
                                    <input type="hidden" name="akce" value="smazat_produkt">
                                    <input type="hidden" name="id_produktu" value="<?= $prod['id'] ?>">
                                    <button type="submit" class="btn-danger btn-small">X</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>

                    <form method="post" enctype="multipart/form-data" class="form-add-prod">
                        <input type="hidden" name="akce" value="pridat_produkt">
                        <input type="hidden" name="kategorie_id" value="<?= $kat['id'] ?>">
                        <div class="flex-gap">
                            <input type="text" name="nazev" placeholder="Název" required>
                            <input type="number" name="cena" placeholder="Cena" required>
                        </div>
                        <input type="file" name="obrazek" required>
                        <button type="submit" class="btn-small">Přidat produkt</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
    <script src="script.js"></script>
</body>
</html>