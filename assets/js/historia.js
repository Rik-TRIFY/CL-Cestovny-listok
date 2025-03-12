document.addEventListener('DOMContentLoaded', function() {
    // Filter histórie
    document.getElementById('historia-filter')?.addEventListener('click', function() {
        const datumOd = document.getElementById('historia-datum-od').value;
        const datumDo = document.getElementById('historia-datum-do').value;
        
        // Reload s parametrami
        window.location.href = `?page=cl-historia&datum_od=${datumOd}&datum_do=${datumDo}`;
    });

    // Detail predaja
    document.querySelectorAll('.cl-detail-predaja').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            
            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'cl_detail_predaja',
                    nonce: cl_admin.nonce,
                    id: id
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Zobraz modal s detailom
                    alert('TODO: Implementovať zobrazenie detailu');
                }
            });
        });
    });

    // Stornovanie predaja
    document.querySelectorAll('.cl-storno-predaja').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!confirm('Naozaj chcete stornovať tento predaj?')) return;
            
            const id = this.dataset.id;
            
            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'cl_storno_predaja',
                    nonce: cl_admin.nonce,
                    id: id
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Chyba pri stornovaní: ' + data.data);
                }
            });
        });
    });
});
