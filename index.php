<?php
/**
 * Hlavní stránka aplikace
 *
 * Funkce:
 * - Zobrazení kategorií a produktů z data/menu.json
 * - Řazení produktů (cena, název)
 * - Server-side stránkování produktů
 * - Zobrazení navigace podle přihlášení/role
 *
 * @package GastroV2
 */
session_start();
$menu = json_decode(file_get_contents('data/menu.json'), true);
if (!$menu) $menu = ["kategorie" => []];

// Parametry z URL
$katId = isset($_GET['kategorie']) ? (int)$_GET['kategorie'] : null;
$stranka = isset($_GET['stranka']) ? (int)$_GET['stranka'] : 1;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'default'; // sort parametr
$limit = 3; 

// Výběr produktů
$zobrazeneProdukty = [];
$nazevKategorie = "Všechny kategorie";

if ($katId) {
    foreach ($menu['kategorie'] as $kat) {
        if ($kat['id'] === $katId) {
            $zobrazeneProdukty = $kat['produkty'];
            $nazevKategorie = $kat['nazev'];
            break;
        }
    }
}

// --- LOGIKA ŘAZENÍ (před stránkováním) ---
if ($sort === 'cena_asc') {
    usort($zobrazeneProdukty, function($a, $b) { return $a['cena'] - $b['cena']; });
} elseif ($sort === 'cena_desc') {
    usort($zobrazeneProdukty, function($a, $b) { return $b['cena'] - $a['cena']; });
} elseif ($sort === 'nazev_asc') {
    usort($zobrazeneProdukty, function($a, $b) { return strcmp($a['nazev'], $b['nazev']); });
}

// Stránkování
$celkemPolozek = count($zobrazeneProdukty);
$celkemStran = ceil($celkemPolozek / $limit);
$offset = ($stranka - 1) * $limit;
$produktyNaStrance = array_slice($zobrazeneProdukty, $offset, $limit);
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Restaurace</title>
    <link rel="stylesheet" href="style.css">
</head>
<body data-prihlasen="<?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>">

<header>
    <h1>Restaurace U FELáka</h1>
    <nav>
        <a href="index.php">Menu</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="orders.php">Objednávky</a>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="admin.php" class="link-admin">Administrace</a> <!-- pozn můžu použít style? -->
            <?php endif; ?> 
            <button id="btn-kosik" type="button">Košík (<span id="pocet-v-kosiku">0</span>)</button>
            <a href="profile.php">Profil</a>
            <a href="logout.php">Odhlásit (<?= htmlspecialchars($_SESSION['username']) ?>)</a>
        <?php else: ?>
            <a href="login.php">Přihlásit</a>
            <a href="register.php">Registrovat</a>
        <?php endif; ?>
    </nav>
</header>

<main>
    <?php if (!$katId): ?>
        <h2>Kategorie</h2>
        <div class="produkt-list">
            <?php foreach ($menu['kategorie'] as $k): ?>
                <div class="produkt">
                    <h3><?= htmlspecialchars($k['nazev']) ?></h3>
                    <p><?= htmlspecialchars(count($k['produkty']) . ' položek') ?></p>
                    <a href="?kategorie=<?= $k['id'] ?>"><button>Vybrat</button></a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="top-bar">
            <a href="index.php" class="btn-small">← Zpět</a>
            <h2><?= htmlspecialchars($nazevKategorie) ?></h2>
            
            <div class="sort-container">
                <label for="sort-select" class="label-inline">Seřadit:</label>
                <select id="sort-select">
                    <option value="default" <?= $sort == 'default' ? 'selected' : '' ?>>Výchozí</option>
                    <option value="cena_asc" <?= $sort == 'cena_asc' ? 'selected' : '' ?>>Od nejlevnějšího</option>
                    <option value="cena_desc" <?= $sort == 'cena_desc' ? 'selected' : '' ?>>Od nejdražšího</option>
                    <option value="nazev_asc" <?= $sort == 'nazev_asc' ? 'selected' : '' ?>>Abecedně (A-Z)</option>
                </select>
            </div>
        </div>

        <div class="produkt-list">
            <?php foreach ($produktyNaStrance as $p): ?>
                <div class="produkt">
                    <img src="<?= htmlspecialchars($p['obrazek']) ?>" alt="Foto" class="obrazek-produktu">
                    <h4><?= htmlspecialchars($p['nazev']) ?></h4>
                    <p class="cena"><?= htmlspecialchars($p['cena'] . ' Kč') ?></p>
                    <button class="btn-do-kosiku" 
                            data-id="<?= $p['id'] ?>" 
                            data-nazev="<?= htmlspecialchars($p['nazev']) ?>" 
                            data-cena="<?= $p['cena'] ?>">Do košíku</button>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($celkemStran > 1): ?>
            <div class="strankovani">
                <?php for ($i = 1; $i <= $celkemStran; $i++): ?>
                    <?php if ($i == $stranka): ?>
                        <strong><?= $i ?></strong>
                    <?php else: ?>
                        <a href="?kategorie=<?= $katId ?>&stranka=<?= $i ?>&sort=<?= $sort ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</main>

<div id="kosik-modal"><div class="modal-content">
    <h2>Košík</h2><ul id="seznam-kosiku"></ul>
    <p>Celkem: <strong id="celkova-cena">0</strong> Kč</p>
    <button id="btn-objednat">Objednat</button>
    <button id="btn-zavrit-kosik" class="btn-close">Zavřít</button>
</div></div>

<script src="script.js"></script>
</body>
</html>