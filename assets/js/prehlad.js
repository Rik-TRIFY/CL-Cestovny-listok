document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('listok-preview');
    const obsah = document.getElementById('listok-obsah');
    
    // Zobrazenie lístka
    document.querySelectorAll('.zobraz-listok').forEach(btn => {
        btn.addEventListener('click', function() {
            fetch(this.dataset.url)
                .then(response => response.text())
                .then(html => {
                    obsah.innerHTML = html;
                    modal.style.display = 'block';
                });
        });
    });
    
    // Tlač lístka
    document.querySelectorAll('.tlacit-listok').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'cl_nacitaj_listok',
                    nonce: cl_admin.nonce,
                    id: id
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const w = window.open('', '', 'width=400,height=600');
                    w.document.write(data.data);
                    w.document.close();
                    w.print();
                    setTimeout(() => w.close(), 1000);
                }
            });
        });
    });
    
    // Zatvorenie modalu
    document.querySelectorAll('.cl-modal-close').forEach(btn => {
        btn.addEventListener('click', () => {
            modal.style.display = 'none';
        });
    });
});
