document.querySelectorAll('.opravit-zaznam').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        const zdroj = this.dataset.zdroj;
        
        if (!confirm('Skutočne chcete použiť dáta z ' + 
            (zdroj === 'primary' ? 'hlavnej' : 'záložnej') + 
            ' databázy?')) {
            return;
        }
        
        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'cl_oprav_zaznam',
                nonce: cl_admin.nonce,
                id: id,
                zdroj: zdroj
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Chyba: ' + data.data);
            }
        });
    });
});

// Kontrola rozdielov v databázach
document.getElementById('kontrola-databaz')?.addEventListener('click', function() {
    fetch(ajaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'cl_skontroluj_databazy',
            nonce: cl_admin.nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('vysledky-kontroly').innerHTML = data.data.html;
        }
    });
});

// Synchronizácia databáz
document.addEventListener('click', function(e) {
    if (!e.target.matches('.sync-db')) return;
    
    const tabulka = e.target.dataset.tabulka;
    const zdroj = e.target.dataset.zdroj;
    
    if (!confirm(`Skutočne chcete synchronizovať tabuľku ${tabulka} použitím dát z ${zdroj} databázy?`)) {
        return;
    }
    
    fetch(ajaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'cl_synchronizuj_databazu',
            nonce: cl_admin.nonce,
            tabulka: tabulka,
            zdroj: zdroj
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Chyba: ' + data.data);
        }
    });
});