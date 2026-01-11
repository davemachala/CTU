# Restaurace U FELáka - Webová aplikace

Jednoduché webové řešení pro správu restauračního menu a objednávek s podporou uživatelských účtů a administrativního rozhraní.

## Obsah

- [Uživatelská dokumentace](#uživatelská-dokumentace)
- [Programátorská dokumentace](#programátorská-dokumentace)
- [Funkcionality](#funkcionality)

## Uživatelská dokumentace

### Přihlášení a registrace

#### Registrace
1. Klikněte na odkaz **"Registrovat"** v horní navigaci
2. Vyplňte formulář:
   - **Uživatelské jméno**: minimálně 3 znaky, jedinečné
   - **Heslo**: minimálně 5 znaků
   - **Potvrzení hesla**: musí se shodovat
3. Klikněte **"Zaregistrovat"**
4. Po úspěšné registraci se můžete přihlásit

#### Přihlášení
1. Klikněte na odkaz **"Přihlásit"** v horní navigaci
2. Vyplňte:
   - Vaše uživatelské jméno
   - Vaše heslo
3. Klikněte **"Přihlásit se"**

**Testovací účty:**
- Administrátor: `admin` / heslo: `admin1`
- Běžný uživatel: `dave` / heslo: `dave1`

### Procházení menu a objednávky

1. **Zobrazení menu**
   - Na hlavní stránce vidíte kategorie
   - Klikněte na kategorii pro zobrazení produktů

2. **Řazení produktů**
   - Vyberte řazení v rozbalovacím seznamu:
     - Výchozí (dle pořadí)
     - Od nejlevnějšího
     - Od nejdražšího
     - Abecedně (A-Z)

3. **Přidání do košíku**
   - Klikněte na tlačítko **"Do košíku"** u produktu
   - Obsah košíku se aktualizuje

4. **Košík a objednávka**
   - Klikněte na tlačítko **"Košík"** v horní navigaci
   - Vidíte seznam položek a celkovou cenu
   - Tlačítko **"Objednat"** vytvoří objednávku
   - Tlačítko **"X"** odstraní položku

5. **Zobrazení objednávek**
   - Klikněte na **"Objednávky"** v navigaci
   - Vidíte aktivní a hotové objednávky

### Správa profilu

1. Klikněte na **"Profil"** v horní navigaci
2. Vyplňte:
   - **Stávající heslo** (povinné pro ověření)
   - **Nové uživatelské jméno** (volitelné, min. 3 znaky)
   - **Nové heslo** (volitelné, min. 5 znaků)
   - **Potvrzení hesla**
3. Klikněte **"Uložit změny"**

### Administrace

**Přístup:** Klikněte na **"Administrace"** (viditelné jen pro adminy)

#### Správa uživatelů
- **Povýšit/Ponížit**: Změní roli uživatele (admin ↔ user)
- **Smazat**: Odstraní uživatelský účet
- **Stránkování**: 5 uživatelů na stránku

#### Správa menu
1. **Nová kategorie**: Zadejte název a klikněte "Vytvořit kategorii"
2. **Přejmenování kategorie**: Editujte název v poli a klikněte "Přejmenovat"
3. **Smazání kategorie**: Klikněte "Smazat kat." (smaže všechny produkty)
4. **Přidání produktu**:
   - Zadejte název a cenu
   - Nahrajte obrázek
   - Klikněte "Přidat produkt"
5. **Smazání produktu**: Klikněte "X" u produktu

#### Správa objednávek
- Vidíte všechny objednávky (admin vidí všechny, uživatelé jen své)
- **Označ jako hotové**: Přesune objednávku do historie
- **Smazat**: Odstraní objednávku

---

## Programátorská dokumentace

### Struktura projektu

```
gastroV2/
├── admin.php           # Administrační panel
├── api_order.php       # API pro vytvoření objednávky (JSON)
├── index.php           # Hlavní stránka s menu
├── login.php           # Přihlášení
├── logout.php          # Odhlášení
├── orders.php          # Zobrazení objednávek
├── profile.php         # Správa profilu uživatele
├── register.php        # Registrace nového uživatele
├── script.js           # Klientské skripty (validace, AJAX)
├── style.css           # Styly aplikace
├── assets/             # Obrázky produktů
└── data/
    ├── users.json      # Uživatelské účty
    ├── menu.json       # Menu a produkty
    └── orders.json     # Objednávky
```

### Architektura

#### Uživatelské role
- **Nepřihlášený**: Může pouze prohlížet menu
- **Přihlášený (User)**: Objednávky, změna profilu
- **Administrátor**: Správa všeho (uživatelé, menu, objednávky)

#### Datové modely

**users.json**
```json
{
  "id": 1234567890,
  "username": "david",
  "password": "$2y$12$...",  // password_hash(PASSWORD_BCRYPT)
  "role": "user"             // "user" nebo "admin"
}
```

**menu.json**
```json
{
  "kategorie": [
    {
      "id": 1,
      "nazev": "Pizza",
      "produkty": [
        {
          "id": 101,
          "nazev": "Margherita",
          "cena": 150,
          "obrazek": "assets/pizza.jpg"
        }
      ]
    }
  ]
}
```

**orders.json**
```json
{
  "id": 1234567890,
  "user_id": 1234567890,
  "username": "david",
  "datum": "11.01.2026 14:30:00",
  "stav": "Nová",             // "Nová" nebo "Hotovo"
  "polozky": [
    {
      "id": 101,
      "nazev": "Margherita",
      "cena": 150
    }
  ],
  "cena": 150
}
```

### Bezpečnost

#### Hasování hesel
Všechna hesla jsou hashována pomocí `password_hash(PASSWORD_BCRYPT)`:
```php
$hash = password_hash($_POST['password'], PASSWORD_BCRYPT);
// Ověření: password_verify($input, $hash)
```

#### XSS ochrana
Všechny uživatelské vstupy jsou escapovány pomocí `htmlspecialchars()`:
```php
<?= htmlspecialchars($userValue, ENT_QUOTES, 'UTF-8') ?>
```

#### POST-Redirect-GET
Po zpracování formuláře jsou data uložena a stránka se přesměruje:
```php
file_put_contents('data/users.json', json_encode($data));
header('Location: page.php?success=1');
exit;
```

#### Validace na serveru
Všechny formuláře jsou validovány na serveru (klientská validace je jen podpora):
- Username: 3+ znaků, jedinečný
- Heslo: 5+ znaků, ověřeno
- Cena: musí být číslo

### Klíčové soubory

#### login.php
- Ověřuje přihlašovací údaje
- Vytvoří session
- Validace na straně serveru

#### register.php
- Kontroluje uživatelské jméno a heslo
- Vytváří nový účet
- První registrovaný uživatel je admin

#### profile.php
- Umožňuje změnu jména a hesla
- Ověřuje stávající heslo
- Aktualizuje session po změně jména

#### admin.php
- Správa uživatelů (role, smazání)
- Správa kategorií a produktů
- Stránkování (5 uživatelů na stránku)
- Nahrávání obrázků

#### index.php
- Zobrazení kategorií a produktů
- Řazení (cena, název)
- Stránkování produktů
- Integrace s košíkem

#### api_order.php
- Přijímá JSON data z `fetch()` call
- Ověřuje přihlášení
- Vytváří objednávku

#### script.js
- Správa košíku (localStorage)
- AJAX objednávky (fetch API)
- Klientská validace formulářů
- Potvrzování akcí (confirm dialog)

### Funkcionalita řazení

```
GET ?sort=cena_asc    // Cena vzestupně
GET ?sort=cena_desc   // Cena sestupně
GET ?sort=nazev_asc   // Abecedně
```

Server-side řazení pomocí `usort()`:
```php
if ($sort === 'cena_asc') {
    usort($produkty, fn($a, $b) => $a['cena'] - $b['cena']);
}
```

### Stránkování

**Server-side** (`limit` = 3 produkty, 5 uživatelů):
```php
$offset = ($page - 1) * $limit;
$items = array_slice($allItems, $offset, $limit);
```

**Klientská navigace**: Generuje se v HTML, nikoli v JS

### Běžné operace

**Čtení dat:**
```php
$data = json_decode(file_get_contents('data/file.json'), true);
```

**Zápis dat:**
```php
file_put_contents('data/file.json', json_encode($data, JSON_PRETTY_PRINT));
```

**Ověření přihlášení:**
```php
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
```

**Ověření admin roli:**
```php
if ($_SESSION['role'] !== 'admin') {
    die("Přístup zamítnut.");
}
```

---

## Funkcionality

### ✅ Implementované
- ✅ Registrace a přihlášení uživatelů
- ✅ 3 uživatelské role (nepřihlášený, user, admin)
- ✅ Správa profilu (změna jména a hesla)
- ✅ Menu s kategoriemi a produkty
- ✅ Řazení produktů (cena, název)
- ✅ Stránkování (server-side)
- ✅ Košík s localStorage
- ✅ Objednávky s statusem
- ✅ Administrační panel
- ✅ Nahrávání obrázků
- ✅ Bezpečné hashování hesel
- ✅ XSS ochrana
- ✅ POST-Redirect-GET
- ✅ Klientská a serverová validace
- ✅ AJAX (fetch API)

---

## Technologie

- **Backend**: PHP 7.4+
- **Frontend**: HTML5, CSS3, JavaScript
- **Data**: JSON soubory
- **Bezpečnost**: password_hash, htmlspecialchars, prepared data

---

## Poznámky k vývojářům

- Bez externích knihoven (Bootstrap, jQuery atd.)
- Všechna hesla jsou bcrypt hashována
- Všechny uživatelské vstupy jsou validovány na serveru
- Všechny HTML výstupy jsou escapovány
- Stránkování probíhá na serveru, ne v JavaScriptu
- Obrázky jsou uloženy v `assets/` složce

---

**Autor**: David Machala - Projekt pro předmět ZWA - SIT FEL ČVUT
**Rok**: 2025/2026
