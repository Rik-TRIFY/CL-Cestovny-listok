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
});
