document.addEventListener('DOMContentLoaded', function() {
    // Vytvorenie zálohy
    document.getElementById('vytvor-zalohu')?.addEventListener('click', function() {
        if (!confirm('Chcete vytvoriť novú zálohu systému?')) return;
        
        this.disabled = true;
        this.textContent = 'Vytvára sa záloha...';
        
        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'cl_vytvor_zalohu',
                nonce: cl_admin.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Chyba pri vytváraní zálohy: ' + data.data);
                this.disabled = false;
                this.textContent = 'Vytvoriť zálohu';
            }
        });
    });

    // Obnova zo zálohy
    document.querySelectorAll('.cl-obnov-zalohu').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!confirm('POZOR! Obnova zo zálohy prepíše aktuálne dáta! Pokračovať?')) return;
            
            const id = this.dataset.id;
            
            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'cl_obnov_zalohu',
                    nonce: cl_admin.nonce,
                    id: id
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Chyba pri obnove: ' + data.data);
                }
            });
        });
    });

    // Zmazanie zálohy
    document.querySelectorAll('.cl-zmaz-zalohu').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!confirm('Naozaj chcete zmazať túto zálohu?')) return;
            
            const id = this.dataset.id;
            
            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'cl_zmaz_zalohu',
                    nonce: cl_admin.nonce,
                    id: id
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Chyba pri mazaní: ' + data.data);
                }
            });
        });
    });
});
