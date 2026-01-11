document.addEventListener('DOMContentLoaded', function() {
    
    // --- 1. KOŠÍK ---
    let kosik = JSON.parse(localStorage.getItem('kosik')) || [];
    const jePrihlasen = document.body.getAttribute('data-prihlasen') === 'true';
    aktualizovatPocitadlo();

    // "Do košíku"
    document.body.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-do-kosiku')) {
            if (!jePrihlasen) {
                alert("Pro objednání se musíte přihlásit.");
                window.location.href = 'login.php';
                return;
            }
            var id = e.target.getAttribute('data-id');
            var nazev = e.target.getAttribute('data-nazev');
            var cena = parseInt(e.target.getAttribute('data-cena'));
            kosik.push({ id, nazev, cena });
            uložitKosik();
            aktualizovatPocitadlo();
            alert(nazev + " přidáno.");
        }
    });

    const btnKosik = document.getElementById('btn-kosik');
    if (btnKosik) btnKosik.addEventListener('click', zobrazitKosik);

    const btnZavrit = document.getElementById('btn-zavrit-kosik');
    if (btnZavrit) btnZavrit.addEventListener('click', () => document.getElementById('kosik-modal').style.display = 'none');

    const btnObjednat = document.getElementById('btn-objednat');
    if (btnObjednat) btnObjednat.addEventListener('click', odeslatObjednavku);

    function uložitKosik() { localStorage.setItem('kosik', JSON.stringify(kosik)); }
    function aktualizovatPocitadlo() { 
        const el = document.getElementById('pocet-v-kosiku'); 
        if(el) el.innerText = kosik.length; 
    }

    function zobrazitKosik() {
        const modal = document.getElementById('kosik-modal');
        const seznam = document.getElementById('seznam-kosiku');
        const cenaEl = document.getElementById('celkova-cena');
        modal.style.display = 'flex';
        seznam.innerHTML = '';
        let celkem = 0;
        kosik.forEach((polozka, index) => {
            celkem += polozka.cena;
            const li = document.createElement('li');
            li.innerText = `${polozka.nazev} (${polozka.cena} Kč) `;
            const btn = document.createElement('button');
            btn.innerText = "X";
            btn.style.marginLeft = "10px";
            btn.style.background = "red";
            btn.style.padding = "2px 5px";
            btn.addEventListener('click', () => {
                kosik.splice(index, 1);
                uložitKosik();
                zobrazitKosik();
                aktualizovatPocitadlo();
            });
            li.appendChild(btn);
            seznam.appendChild(li);
        });
        cenaEl.innerText = celkem;
    }

    function odeslatObjednavku() {
        if (kosik.length === 0) return alert("Košík je prázdný!");
        const data = {
            datum: new Date().toLocaleString(),
            polozky: kosik,
            cena: kosik.reduce((a, b) => a + b.cena, 0),
            stav: 'Nová'
        };
        fetch('api_order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(d => {
            if (d.status === 'success') {
                alert("Objednáno!");
                kosik = [];
                uložitKosik();
                aktualizovatPocitadlo();
                document.getElementById('kosik-modal').style.display = 'none';
                window.location.href = 'orders.php';
            } else { alert("Chyba: " + d.message); }
        });
    }

    // --- 2. ZMĚNA ŘAZENÍ (SORTING) ---
    const sortSelect = document.getElementById('sort-select');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            const vybranaHodnota = this.value;
            const validOptions = ['default', 'cena_asc', 'cena_desc', 'nazev_asc'];
            if (!validOptions.includes(vybranaHodnota)) {
                this.value = 'default';
                alert('Neplatná volba řazení!');
                return;
            }
            // Získáme aktuální URL
            const url = new URL(window.location.href);
            // Nastavíme parametr 'sort'
            url.searchParams.set('sort', vybranaHodnota);
            // Resetujeme stránkování na 1 při změně řazení
            url.searchParams.set('stranka', 1);
            // Přenačteme stránku
            window.location.href = url.toString();
        });
    }

    // --- 3. POTVRZOVÁNÍ AKCÍ
    // Najdeme všechny formuláře, které mají třídu 'form-confirm'
    const confirmForms = document.querySelectorAll('.form-confirm');
    confirmForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            // Získáme text zprávy z data atributu, nebo použijeme výchozí
            const msg = this.getAttribute('data-confirm-msg') || "Opravdu provést tuto akci?";
            if (!confirm(msg)) {
                e.preventDefault(); // Zruší odeslání, pokud uživatel klikne na Storno
            }
        });
    });

    // --- 4. VALIDACE HESEL (Registrace) ---
    const regForm = document.getElementById('registracni-formular');
    if (regForm) {
        regForm.addEventListener('submit', function(e) {
            const h1 = document.getElementById('heslo1').value;
            const h2 = document.getElementById('heslo2').value;
            if (h1 !== h2) {
                e.preventDefault();
                alert("Hesla se neshodují!"); // pozn. podmínky hodnocení projektu - můžu nechat, co je v register.php?
            }
        });
    }

    // --- 5. VALIDACE PROFILU (Změna údajů) ---
    const profileForm = document.querySelector('.profile-form');
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            const noveHeslo1 = document.getElementById('nove_heslo1').value;
            const noveHeslo2 = document.getElementById('nove_heslo2').value;
            const noveJmeno = document.getElementById('nove_jmeno').value;
            
            // Pokud jsou hesla vyplněna, musí se shodovat
            if (noveHeslo1 || noveHeslo2) {
                if (noveHeslo1 !== noveHeslo2) {
                    e.preventDefault();
                    alert("Nová hesla se neshodují!");
                    return false;
                }
                if (noveHeslo1.length < 5) {
                    e.preventDefault();
                    alert("Nové heslo musí mít alespoň 5 znaků!");
                    return false;
                }
            }
            
            // Pokud je vyplněno nové jméno, musí mít alespoň 3 znaky
            if (noveJmeno && noveJmeno.length < 3) {
                e.preventDefault();
                alert("Nové jméno musí mít alespoň 3 znaky!");
                return false;
            }
        });
    }

    // --- 6. VALIDACE REGISTRACE ---
    const usernameField = document.getElementById('username');
    if (usernameField) {
        usernameField.addEventListener('blur', function() {
            if (this.value.length > 0 && this.value.length < 3) {
                this.classList.add('input-chyba');
            } else {
                this.classList.remove('input-chyba');
            }
        });
    }
});