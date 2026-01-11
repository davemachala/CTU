<?php
/**
 * Stránka objednávek
 *
 * Funkce:
 * - Zobrazení aktivních a hotových objednávek
 * - Admin akce: označit jako hotové, smazat objednávku
 * - Filtrování: admin vidí vše, běžný uživatel jen své
 * - Server-side řazení (nejnovější nahoře)
 *
 * @package GastroV2
 */
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$fileOrders = 'data/orders.json';
$orders = json_decode(file_get_contents($fileOrders), true);
if (!$orders) $orders = [];

// Admin akce
if ($_SESSION['role'] === 'admin') {
    if (isset($_POST['akce']) && $_POST['akce'] === 'hotovo') {
        $id = (int)$_POST['order_id'];
        foreach ($orders as &$o) { if ($o['id'] === $id) $o['stav'] = 'Hotovo'; }
        file_put_contents($fileOrders, json_encode($orders, JSON_PRETTY_PRINT));
        header("Location: orders.php"); exit;
    }
    if (isset($_POST['akce']) && $_POST['akce'] === 'smazat') {
        $id = (int)$_POST['order_id'];
        $orders = array_values(array_filter($orders, fn($o) => $o['id'] !== $id));
        file_put_contents($fileOrders, json_encode($orders, JSON_PRETTY_PRINT));
        header("Location: orders.php"); exit;
    }
}

// Filtrování
$moje = [];
foreach ($orders as $o) {
    if ($_SESSION['role'] === 'admin' || $o['user_id'] === $_SESSION['user_id']) {
        $moje[] = $o;
    }
}

// Rozdělení Aktivní / Historie
$aktivni = [];
$historie = [];
foreach ($moje as $o) {
    if ($o['stav'] === 'Hotovo') $historie[] = $o;
    else $aktivni[] = $o;
}
// Seřadit od nejnovějších
$aktivni = array_reverse($aktivni);
$historie = array_reverse($historie);
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Objednávky</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .order-card { padding: 15px; margin-bottom: 10px; border-left: 5px solid #ccc; background: white; border: 1px solid #ddd; }
        .new { border-left-color: orange; }
        .done { border-left-color: green; background: #f9f9f9; }
    </style>
</head>
<body data-prihlasen="true">
    <header>
        <h1>Objednávky</h1>
        <nav><a href="index.php">Web</a></nav>
    </header>
    <main>
        <h2>Nevyřízené (Aktivní)</h2>
        <?php if(empty($aktivni)) echo "<p>Žádné aktivní objednávky.</p>"; ?>
        <?php foreach ($aktivni as $o): ?>
            <div class="order-card new">
                <strong>#<?= $o['id'] ?></strong> - <?= htmlspecialchars($o['username']) ?> - <?= $o['cena'] ?> Kč<br>
                <small><?= $o['datum'] ?></small>
                <ul><?php foreach($o['polozky'] as $p) echo "<li>".htmlspecialchars($p['nazev'])."</li>"; ?></ul>
                
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <form method="post" class="form-inline form-confirm" data-confirm-msg="Označit jako hotové?">
                        <input type="hidden" name="akce" value="hotovo">
                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                        <button type="submit" class="btn-small">✔ Hotovo</button>
                    </form>
                    <form method="post" class="form-inline form-confirm" data-confirm-msg="Smazat?">
                        <input type="hidden" name="akce" value="smazat">
                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                        <button type="submit" class="btn-danger btn-small">Smazat</button>
                    </form>
                <?php else: ?>
                    <span class="text-warning">Čeká na vyřízení</span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <h2>Historie (Hotové)</h2>
        <?php foreach ($historie as $o): ?>
            <div class="order-card done">
                <strong>#<?= $o['id'] ?></strong> - <?= htmlspecialchars($o['username']) ?> - <?= $o['cena'] ?> Kč<br>
                <small><?= $o['datum'] ?></small>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <form method="post" class="form-confirm form-right" data-confirm-msg="Smazat z historie?">
                        <input type="hidden" name="akce" value="smazat">
                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                        <button type="submit" class="btn-danger btn-small">X</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </main>
    <script src="script.js"></script>
</body>
</html>