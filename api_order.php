<?php
/**
 * API pro vytvoření objednávky
 *
 * Metoda: POST
 * Content-Type: application/json
 *
 * Vstup (JSON):
 * {
 *   "polozky": [
 *     {"id": 101, "nazev": "Pizza", "cena": 150},
 *     ...
 *   ]
 * }
 *
 * Výstup (JSON):
 * {"status": "success", "message": "Objednáno."}
 * nebo
 * {"status": "error", "message": "Chybová zpráva"}
 *
 * Bezpečnost:
 * - Vyžaduje přihlášení ($_SESSION['user_id'])
 * - Ověřuje existenci produktů v menu
 * - Ceny se berou ze serveru (není možné manipulovat)
 * - Všechna data jsou validována
 *
 * @package GastroV2
 * @requires session Uživatel musí být přihlášen
 * @method POST
 */

session_start();
header('Content-Type: application/json');

// 1. Ochrana: Musí být přihlášen
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Nejste přihlášen"]);
    exit;
}

// 2. Načtení dat od klienta
/**
 * Raw JSON payload obdržený z klienta
 * @var string $inputJSON
 */
$inputJSON = file_get_contents('php://input');
/**
 * Dekódovaná data z klienta (neověřená)
 * @var array|null $dataOdKlienta
 */
$dataOdKlienta = json_decode($inputJSON, true);

if (!$dataOdKlienta || empty($dataOdKlienta['polozky'])) {
    echo json_encode(["status" => "error", "message" => "Košík je prázdný"]);
    exit;
}

// 3. Načtení menu ze serveru
/**
 * Kompletní menu načtené ze souboru
 * @var array $menu
 */
$menu = json_decode(file_get_contents('data/menu.json'), true);
if (!$menu) {
    echo json_encode(["status" => "error", "message" => "Chyba serveru"]);
    exit;
}

// --- 4. SESTAVENÍ OBJEDNÁVKY ---
/**
 * Položky objednávky ověřené vůči menu
 * @var array<int, array{id:int,nazev:string,cena:int}>
 */
$finalniPolozky = [];
/**
 * Součet cen všech položek
 * @var int $celkovaCena
 */
$celkovaCena = 0;

foreach ($dataOdKlienta['polozky'] as $polozkaZKosiku) {
    $hledaneID = (int)$polozkaZKosiku['id'];

    // Hledáme produkt v menu
    foreach ($menu['kategorie'] as $kat) {
        foreach ($kat['produkty'] as $prod) {
            
            if ($prod['id'] === $hledaneID) {
                // Našli jsme produkt
                $finalniPolozky[] = [
                    'id' => $prod['id'],
                    'nazev' => $prod['nazev'],
                    'cena' => $prod['cena']
                ];
                $celkovaCena += $prod['cena'];
                break 2; // Vyskočíme z cyklů, máme hotovo
            }
        }
    }
}

if (empty($finalniPolozky)) {
    echo json_encode(["status" => "error", "message" => "Žádné platné produkty"]);
    exit;
}

// 5. Uložení
/**
 * Nově vytvořená objednávka připravená k uložení
 * @var array{
 *   id:int,
 *   user_id:int,
 *   username:string,
 *   datum:string,
 *   stav:string,
 *   polozky:array,
 *   cena:int
 * } $novaObjednavka
 */
$novaObjednavka = [
    'id' => time(),
    'user_id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'datum' => date("d.m.Y H:i:s"),
    'stav' => 'Nová',
    'polozky' => $finalniPolozky,
    'cena' => $celkovaCena
];

$souborOrders = 'data/orders.json';
$aktualniObjednavky = json_decode(file_get_contents($souborOrders), true);
if (!$aktualniObjednavky) $aktualniObjednavky = [];

$aktualniObjednavky[] = $novaObjednavka;

if (file_put_contents($souborOrders, json_encode($aktualniObjednavky, JSON_PRETTY_PRINT))) {
    echo json_encode(["status" => "success", "message" => "Objednáno."]);
} else {
    echo json_encode(["status" => "error", "message" => "Chyba zápisu"]);
}
?>