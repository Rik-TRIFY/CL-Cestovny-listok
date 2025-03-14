document.addEventListener('DOMContentLoaded', function() {
    // Hlavný objekt košíka
    const kosik = {
        polozky: {},
        poslednePolozky: [],
        
        /**
         * Pridá položku do košíka
         */
        pridajPolozku(id, nazov, cena) {
            if (!this.polozky[id]) {
                this.polozky[id] = { id, nazov, cena: parseFloat(cena), pocet: 0 };
            }
            this.polozky[id].pocet++;
            
            // Pridanie do posledných položiek
            this.pridajDoPoslednych(id, nazov, parseFloat(cena));
            
            this.aktualizujZobrazenie();
            
            // Uložíme do localStorage
            this.ulozDoStorage();
        },
        
        /**
         * Odstráni položku z košíka
         */
        odstranPolozku(id) {
            if (this.polozky[id] && this.polozky[id].pocet > 0) {
                this.polozky[id].pocet--;
                if (this.polozky[id].pocet === 0) {
                    delete this.polozky[id];
                }
                this.aktualizujZobrazenie();
                this.ulozDoStorage();
            }
        },
        
        /**
         * Pridá položku do zoznamu posledných položiek
         */
        pridajDoPoslednych(id, nazov, cena) {
            // Vložíme novú položku na začiatok poľa
            this.poslednePolozky.unshift({
                id: id,
                nazov: nazov,
                cena: cena,
                cas: Date.now()
            });
            
            // Obmedzíme len na posledné 2 položky bez akéhokoľvek filtrovania
            if (this.poslednePolozky.length > 2) {
                this.poslednePolozky.pop(); // Odstránime najstaršiu položku
            }
            
            // Aktualizácia zobrazenia
            this.aktualizujPoslednePolozky();
        },
        
        // Pridanie do posledných položiek - upravená logika
        addToRecent(id, name, price) {
            // Vždy pridáme novú položku na začiatok poľa
            this.recentItems.unshift({ id, name, price });
            
            // Ponechanie len posledných 2 položiek
            this.recentItems = this.recentItems.slice(0, 2);
            
            // Aktualizácia zobrazenia posledných položiek
            this.updateRecentDisplay();
        },

        /**
         * Aktualizuje zobrazenie posledných položiek
         */
        aktualizujPoslednePolozky() {
            const kontajner = document.getElementById('recent-items-container');
            kontajner.innerHTML = '';
            
            if (this.poslednePolozky.length === 0) {
                kontajner.innerHTML = '<div class="pos-recent-item">Zatiaľ neboli pridané žiadne položky</div>';
                return;
            }
            
            this.poslednePolozky.forEach(polozka => {
                const element = document.createElement('div');
                element.className = 'pos-recent-item';
                element.innerHTML = `
                    <div>${polozka.nazov}</div>
                    <div>${polozka.cena.toFixed(2)} €</div>
                `;
                kontajner.appendChild(element);
            });
        },
        
        /**
         * Renderuje položky v košíku
         */
        renderujPolozkyKosika() {
            const kontajner = document.getElementById('cart-items-container');
            kontajner.innerHTML = '';
            
            if (Object.keys(this.polozky).length === 0) {
                kontajner.innerHTML = '<div style="padding:20px;text-align:center;">Košík je prázdny</div>';
                document.getElementById('checkout-btn').disabled = true;
                return;
            }
            
            document.getElementById('checkout-btn').disabled = false;
            
            // Renderovanie každej položky
            Object.values(this.polozky).forEach(polozka => {
                const element = document.createElement('div');
                element.className = 'pos-cart-item';
                element.innerHTML = `
                    <div class="pos-item-info">
                        <div class="pos-item-name">${polozka.nazov}</div>
                        <div class="pos-item-price">${(polozka.cena * polozka.pocet).toFixed(2)} €</div>
                    </div>
                    <div class="pos-item-controls">
                        <button class="pos-qty-btn minus" data-id="${polozka.id}">-</button>
                        <span class="pos-item-qty">${polozka.pocet}</span>
                        <button class="pos-qty-btn plus" data-id="${polozka.id}">+</button>
                    </div>
                `;
                kontajner.appendChild(element);
            });
            
            // Pridanie event listenerov pre tlačidlá
            document.querySelectorAll('.pos-qty-btn.minus').forEach(btn => {
                btn.addEventListener('click', () => this.odstranPolozku(btn.dataset.id));
            });
            
            document.querySelectorAll('.pos-qty-btn.plus').forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = btn.dataset.id;
                    const polozka = this.polozky[id];
                    this.pridajPolozku(id, polozka.nazov, polozka.cena);
                });
            });
            
            this.aktualizujCelkovuSumu();
        },
        
        /**
         * Aktualizuje celkovú sumu v košíku
         */
        aktualizujCelkovuSumu() {
            const total = Object.values(this.polozky).reduce(
                (sum, item) => sum + (item.cena * item.pocet), 0
            );
            document.getElementById('cart-total-amount').textContent = total.toFixed(2) + ' €';
        },
        
        /**
         * Aktualizuje zobrazenie počtu položiek a uloženie dát
         */
        aktualizujZobrazenie() {
            const celkovyPocet = Object.values(this.polozky).reduce((sum, item) => sum + item.pocet, 0);
            document.getElementById('cart-item-count').textContent = celkovyPocet;
            this.renderujPolozkyKosika();
        },
        
        /**
         * Uloží košík do localStorage
         */
        ulozDoStorage() {
            localStorage.setItem('posCart', JSON.stringify(this.polozky));
            // Pridáme uloženie posledných položiek
            localStorage.setItem('posRecentItems', JSON.stringify(this.poslednePolozky));
        },
        
        /**
         * Načíta košík z localStorage
         */
        nacitajZoStorage() {
            try {
                const ulozenyKosik = localStorage.getItem('posCart');
                const ulozenePosledne = localStorage.getItem('posRecentItems');
                
                if (ulozenyKosik) {
                    this.polozky = JSON.parse(ulozenyKosik);
                    this.aktualizujZobrazenie();
                }
                
                // Načítame aj posledné položky
                if (ulozenePosledne) {
                    this.poslednePolozky = JSON.parse(ulozenePosledne);
                    this.aktualizujPoslednePolozky();
                }
            } catch (e) {
                console.error('Chyba pri načítaní košíka:', e);
            }
        },
        
        /**
         * Dokončí predaj a vytlačí lístok
         */
        dokonciPredaj() {
            if (Object.keys(this.polozky).length === 0) {
                alert('Košík je prázdny');
                return;
            }
            
            // Transformujeme položky do formátu, ktorý očakáva backend
            const polozkyPreBackend = Object.values(this.polozky).map(polozka => ({
                id: polozka.id,
                nazov: polozka.nazov,
                cena: polozka.cena,
                pocet: polozka.pocet
            }));
            
            // AJAX volanie na backend
            fetch(cl_pos.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'cl_dokoncit_predaj',
                    nonce: cl_pos.nonce,
                    polozky: JSON.stringify(polozkyPreBackend)
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Zobrazíme notifikáciu s poznámkou
                    alert(data.data.poznamka || 'Predaj bol úspešne dokončený');
                    
                    // Vyčistíme košík
                    this.polozky = {};
                    this.aktualizujZobrazenie();
                    this.ulozDoStorage();
                    
                    // Vrátime sa na hlavnú obrazovku
                    document.getElementById('cart-screen').classList.remove('active');
                    document.getElementById('terminal-screen').classList.add('active');
                    
                    // Otvoríme lístok v novom okne pre náhľad
                    if (data.data.url_listka) {
                        window.open(data.data.url_listka, '_blank');
                    }
                } else {
                    alert('Chyba pri dokončení predaja: ' + data.data);
                }
            })
            .catch(error => {
                console.error('Chyba pri dokončení predaja:', error);
                alert('Došlo k chybe pri dokončení predaja. Skúste to znova.');
            });
        },
        
        /**
         * Otvorí okno pre tlač lístka
         */
        tlacListok(cisloListka, url) {
            const w = window.open(url, 'PRINT_TICKET', 'width=400,height=600');
            if (w) {
                setTimeout(() => {
                    w.print();
                    setTimeout(() => w.close(), 1000);
                }, 500);
            } else {
                alert('Povoľte vyskakovacie okná pre tlač lístka');
                // Otvoríme lístok v novej karte, ak je blokovač vyskakovacích okien aktívny
                window.open(url, '_blank');
            }
        }
    };
    
    // Inicializácia košíka
    kosik.nacitajZoStorage();
    kosik.aktualizujPoslednePolozky();
    
    // Event listener pre dokončenie predaja
    document.getElementById('checkout-btn')?.addEventListener('click', function() {
        kosik.dokonciPredaj();
    });
    
    // Event listenery pre tlačidlá lístkov
    document.querySelectorAll('.pos-product, .cl-ticket-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const nazov = this.dataset.nazov || this.querySelector('.pos-product-name').textContent;
            const cena = parseFloat(this.dataset.cena || this.querySelector('.pos-product-price').textContent);
            
            console.log('Kliknuté na tlačidlo:', id, nazov, cena);
            kosik.pridajPolozku(id, nazov, cena);
            
            // Vizuálna spätná väzba pri kliknutí
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = '';
            }, 100);
        });
    });

    // Event listenery pre produktové tlačidlá
    document.querySelectorAll('.pos-product').forEach(produkt => {
        produkt.addEventListener('click', function() {
            const id = this.dataset.id;
            const nazov = this.querySelector('.pos-product-name').textContent;
            const cena = parseFloat(this.querySelector('.pos-product-price').textContent);
            
            kosik.pridajPolozku(id, nazov, cena);
            
            // Vizuálna spätná väzba pri kliknutí
            this.style.transform = 'scale(0.98)';
            setTimeout(() => {
                this.style.transform = '';
            }, 100);
        });
    });
    
    // Event listenery pre prepínanie obrazoviek (už implementované v HTML)

    // Zobrazenie predchádzajúcich lístkov
    document.getElementById('show-previous').addEventListener('click', function() {
        fetch(cl_pos.ajaxurl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'cl_get_previous_tickets',
                nonce: cl_pos.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const container = document.getElementById('previous-tickets-list');
                container.innerHTML = data.data.tickets.map(ticket => `
                    <div class="previous-ticket-item">
                        <div class="previous-ticket-info">
                            <strong>${ticket.cislo_listka}</strong><br>
                            ${ticket.datum}
                        </div>
                        <div class="previous-ticket-actions">
                            <button onclick="openTicket('${ticket.cislo_listka}')" class="view-button">
                                Zobraziť
                            </button>
                            <button onclick="reprintTicket('${ticket.cislo_listka}')" class="reprint-button">
                                Tlačiť znova
                            </button>
                        </div>
                    </div>
                `).join('');
                document.getElementById('previous-tickets-modal').style.display = 'block';
            }
        });
    });

    // Menu akcie
    document.getElementById('show-menu').addEventListener('click', function() {
        document.getElementById('menu-modal').style.display = 'block';
    });

    // Uzatvorenie zmeny
    document.getElementById('close-shift').addEventListener('click', function() {
        if (!confirm(cl_preklady.confirm_reprint)) return;
        
        fetch(cl_pos.ajaxurl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'cl_close_shift',
                nonce: cl_pos.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const printWindow = window.open('', 'PRINT', 'height=600,width=800');
                printWindow.document.write(data.data.report);
                printWindow.document.close();
                printWindow.focus();
                setTimeout(() => {
                    printWindow.print();
                    printWindow.close();
                }, 250);
            }
        });
    });

    // Zatváranie modálnych okien
    document.querySelectorAll('.pos-modal-close').forEach(button => {
        button.addEventListener('click', function() {
            this.closest('.pos-modal').style.display = 'none';
        });
    });

    // Pomocné funkcie
    window.openTicket = function(cisloListka) {
        fetch(cl_pos.ajaxurl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'cl_get_ticket_html',
                nonce: cl_pos.nonce,
                cislo_listka: cisloListka
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const printWindow = window.open('', '_blank');
                printWindow.document.write(data.data.html);
                printWindow.document.close();
            }
        });
    };

    window.reprintTicket = function(cisloListka) {
        if (!confirm(cl_preklady.confirm_reprint)) return;
        
        fetch(cl_pos.ajaxurl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'cl_reprint_ticket',
                nonce: cl_pos.nonce,
                cislo_listka: cisloListka
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const printWindow = window.open('', 'PRINT', 'height=600,width=800');
                printWindow.document.write(data.data.html);
                printWindow.document.close();
                printWindow.focus();
                setTimeout(() => {
                    printWindow.print();
                    printWindow.close();
                }, 250);
            }
        });
    };

    // Event listener pre tlačidlo Predchádzajúce lístky
    document.getElementById('show-previous').addEventListener('click', function() {
        console.log('Kliknuté na Predchádzajúce lístky');
        
        // Zobrazíme modálne okno okamžite s načítavacou správou
        document.getElementById('previous-tickets-modal').style.display = 'block';
        document.getElementById('previous-tickets-list').innerHTML = 'Načítavam predchádzajúce lístky...';
        
        // AJAX volanie na načítanie lístkov
        fetch(cl_pos.ajaxurl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'cl_get_previous_tickets',
                nonce: cl_pos.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('previous-tickets-list');
            
            if (data.success && data.data.tickets && data.data.tickets.length > 0) {
                container.innerHTML = data.data.tickets.map(ticket => `
                    <div class="previous-ticket-item">
                        <div class="previous-ticket-info">
                            <strong>Lístok č. ${ticket.cislo_listka}</strong>
                            <div>${ticket.datum}</div>
                        </div>
                        <div class="previous-ticket-actions">
                            <button onclick="openTicket('${ticket.cislo_listka}')" class="view-button">
                                ${cl_preklady.view_ticket}
                            </button>
                            <button onclick="reprintTicket('${ticket.cislo_listka}')" class="reprint-button">
                                ${cl_preklady.print_ticket}
                            </button>
                        </div>
                    </div>
                `).join('');
            } else {
                container.innerHTML = '<div class="empty-state">Neboli nájdené žiadne predchádzajúce lístky.</div>';
            }
        })
        .catch(error => {
            console.error('Chyba pri načítaní lístkov:', error);
            document.getElementById('previous-tickets-list').innerHTML = 
                '<div class="error-state">Chyba pri načítaní predchádzajúcich lístkov.</div>';
        });
    });

    // Zatváranie modálneho okna
    document.querySelectorAll('.pos-modal-close').forEach(button => {
        button.addEventListener('click', function() {
            this.closest('.pos-modal').style.display = 'none';
        });
    });
});
