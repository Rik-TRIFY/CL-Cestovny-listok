document.addEventListener('DOMContentLoaded', function() {
    const kosik = {
        polozky: [],
        
        pridajPolozku(id, nazov, cena) {
            // Namiesto zvyšovania počtu existujúcej položky zlúčime položky pri zobrazení
            this.polozky.push({ id, nazov, cena, pocet: 1 });
            this.aktualizujZobrazenie();
        },
        
        odstranPolozku(id) {
            this.polozky = this.polozky.filter(p => p.id !== id);
            this.aktualizujZobrazenie();
        },
        
        zmenPocet(id, novyPocet) {
            const polozka = this.polozky.find(p => p.id === id);
            if (polozka) {
                polozka.pocet = Math.max(1, novyPocet);
                this.aktualizujZobrazenie();
            }
        },
        
        aktualizujZobrazenie() {
            const kontajner = document.getElementById('polozky-kosika');
            let html = '';
            
            // Zlúčime rovnaké položky
            const zlucenePolozky = this.polozky.reduce((acc, polozka) => {
                const existujuca = acc.find(p => p.id === polozka.id);
                if (existujuca) {
                    existujuca.pocet += polozka.pocet;
                } else {
                    acc.push({ ...polozka });
                }
                return acc;
            }, []);
            
            zlucenePolozky.forEach(polozka => {
                const sumaCelkom = polozka.cena * polozka.pocet;
                html += `
                    <div class="polozka" data-id="${polozka.id}">
                        <span class="nazov">${polozka.nazov}</span>
                        <div class="mnozstvo">
                            <span class="pocet">${polozka.pocet}x</span>
                        </div>
                        <span class="cena">${sumaCelkom.toFixed(2)} €</span>
                        <button class="odstranit">&times;</button>
                    </div>
                `;
            });
            
            kontajner.innerHTML = html;
            
            // Celková suma zo zlúčených položiek
            const celkovaSuma = zlucenePolozky.reduce((sum, item) => 
                sum + (item.cena * item.pocet), 0);
            document.getElementById('celkova-suma').textContent = celkovaSuma.toFixed(2);
        },
        
        dokonciPredaj() {
            if (this.polozky.length === 0) {
                alert('Košík je prázdny');
                return;
            }
            
            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'cl_dokonci_predaj',
                    nonce: cl_pos.nonce,
                    polozky: JSON.stringify(this.polozky)
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.tlacListok(data.data.html_listok);
                    this.polozky = [];
                    this.aktualizujZobrazenie();
                } else {
                    alert('Chyba: ' + data.data);
                }
            });
        },
        
        tlacListok(html) {
            const w = window.open('', '', 'width=400,height=600');
            w.document.write(html);
            w.document.close();
            w.print();
            setTimeout(() => w.close(), 1000);
        }
    };
    
    // Event listeners pre tlačidlá lístkov
    document.querySelectorAll('.cl-listok').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const nazov = this.dataset.nazov;
            const cena = parseFloat(this.dataset.cena);
            
            kosik.pridajPolozku(id, nazov, cena);
            
            // Vizuálna spätná väzba pri kliknutí
            this.classList.add('kliknute');
            setTimeout(() => this.classList.remove('kliknute'), 200);
        });
    });
    
    // Event listeners...
});
