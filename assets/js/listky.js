document.addEventListener('DOMContentLoaded', function() {
    // Filtre pre lístky
    const vyhladavanie = document.getElementById('vyhladavanie');
    const filterTrieda = document.getElementById('filter-trieda');
    const filterSkupina = document.getElementById('filter-skupina');
    
    function nacitajListky(filter = '') {
        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'cl_nacitaj_listky',
                nonce: cl_admin.nonce,
                filter: filter
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('listky-zoznam').innerHTML = data.data;
                aktualizujFiltreOptions(data.triedy, data.skupiny);
            }
        });
    }

    // Správa modal okna
    const modal = document.getElementById('listok-modal');
    document.getElementById('pridat-listok').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('listok-formular').reset();
        modal.style.display = 'block';
    });

    // Uloženie lístka
    document.getElementById('listok-formular').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'cl_uloz_listok',
                nonce: cl_admin.nonce,
                ...Object.fromEntries(formData)
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                modal.style.display = 'none';
                nacitajListky();
            } else {
                alert('Chyba: ' + data.data);
            }
        });
    });

    function aktualizujFiltreOptions(triedy, skupiny) {
        const triedaSelect = document.getElementById('filter-trieda');
        const skupinaSelect = document.getElementById('filter-skupina');
        
        // Zachováme aktuálne vybrané hodnoty
        const selectedTrieda = triedaSelect.value;
        const selectedSkupina = skupinaSelect.value;
        
        // Vyčistíme a naplníme filtre
        triedaSelect.innerHTML = '<option value="">Všetky triedy</option>';
        skupinaSelect.innerHTML = '<option value="">Všetky skupiny</option>';
        
        triedy.forEach(trieda => {
            if (trieda) {
                const option = new Option(trieda, trieda);
                if (trieda === selectedTrieda) option.selected = true;
                triedaSelect.add(option);
            }
        });
        
        skupiny.forEach(skupina => {
            if (skupina) {
                const option = new Option(skupina, skupina);
                if (skupina === selectedSkupina) option.selected = true;
                skupinaSelect.add(option);
            }
        });
    }

    // Event listeners pre filtrovanie
    let timeoutId;
    document.getElementById('vyhladavanie').addEventListener('input', function() {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => {
            nacitajListky(this.value);
        }, 300);
    });

    ['filter-trieda', 'filter-skupina'].forEach(id => {
        document.getElementById(id).addEventListener('change', function() {
            nacitajListky(document.getElementById('vyhladavanie').value);
        });
    });

    // Spustíme prvé načítanie
    nacitajListky();

    // Pridanie nového lístka
    document.getElementById('novy-listok-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'cl_pridaj_listok');
        
        fetch(ajaxurl, {
            method: 'POST',
            body: new URLSearchParams(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Chyba: ' + data.data);
            }
        })
        .catch(error => {
            console.error('Chyba:', error);
            alert('Nastala chyba pri komunikácii so serverom');
        });
    });

    // Prepínač aktívny/neaktívny
    document.querySelectorAll('.aktivny-switch').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const tr = this.closest('tr');
            const id = tr.dataset.id;
            
            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'cl_prepni_aktivny',
                    nonce: cl_admin.nonce,
                    id: id,
                    aktivny: this.checked ? 1 : 0
                })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    this.checked = !this.checked;
                    alert('Chyba: ' + data.data);
                }
            });
        });
    });

    // Úprava lístka
    document.querySelectorAll('.edit-listok').forEach(btn => {
        btn.addEventListener('click', function() {
            const tr = this.closest('tr');
            tr.querySelector('.listok-nazov').style.display = 'none';
            tr.querySelector('.listok-nazov-edit').style.display = 'inline';
            tr.querySelector('.listok-cena').style.display = 'none';
            tr.querySelector('.listok-cena-edit').style.display = 'inline';
            this.style.display = 'none';
            tr.querySelector('.save-listok').style.display = 'inline-block';
            tr.querySelector('.cancel-edit').style.display = 'inline-block';
        });
    });

    // Zrušenie úprav
    document.querySelectorAll('.cancel-edit').forEach(btn => {
        btn.addEventListener('click', function() {
            const tr = this.closest('tr');
            tr.querySelector('.listok-nazov').style.display = 'inline';
            tr.querySelector('.listok-nazov-edit').style.display = 'none';
            tr.querySelector('.listok-cena').style.display = 'inline';
            tr.querySelector('.listok-cena-edit').style.display = 'none';
            this.style.display = 'none';
            tr.querySelector('.save-listok').style.display = 'none';
            tr.querySelector('.edit-listok').style.display = 'inline-block';
        });
    });

    // Uloženie úprav
    document.querySelectorAll('.save-listok').forEach(btn => {
        btn.addEventListener('click', function() {
            const tr = this.closest('tr');
            const id = tr.dataset.id;
            const nazov = tr.querySelector('.listok-nazov-edit').value;
            const cena = tr.querySelector('.listok-cena-edit').value;

            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'cl_uprav_listok',
                    nonce: cl_admin.nonce,
                    id: id,
                    nazov: nazov,
                    cena: cena
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Chyba: ' + data.data);
                }
            });
        });
    });
});
