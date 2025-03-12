class SpravcaTlace {
    constructor() {
        this.inicializuj();
    }

    inicializuj() {
        // Sledovanie udalostí pre tlač
        document.addEventListener('click', (e) => {
            if (e.target.matches('.tlacit-listok')) {
                this.tlacListok(e.target.dataset.cislo);
            }
        });
    }

    async tlacListok(cisloListka) {
        try {
            const response = await fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'cl_tlac_listok',
                    nonce: cl_admin.nonce,
                    cislo_listka: cisloListka
                })
            });

            const data = await response.json();
            
            if (data.success) {
                this.otvorTlacoveOkno(data.data);
            } else {
                alert('Chyba pri tlači: ' + data.data);
            }
        } catch (error) {
            console.error('Chyba pri tlači:', error);
            alert('Nastala chyba pri tlači');
        }
    }

    otvorTlacoveOkno(html) {
        const w = window.open('', '', 'width=400,height=600');
        w.document.write(html);
        w.document.close();
        
        // Ak je nastavená automatická tlač
        if (cl_nastavenia?.auto_tlac) {
            w.print();
            setTimeout(() => w.close(), 1000);
        }
    }
}

// Inicializácia po načítaní dokumentu
document.addEventListener('DOMContentLoaded', () => {
    window.spravcaTlace = new SpravcaTlace();
});
