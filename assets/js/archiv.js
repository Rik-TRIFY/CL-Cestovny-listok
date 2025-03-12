document.addEventListener('DOMContentLoaded', function() {
    const formFilter = document.getElementById('filter-archiv');
    let aktualnaPoziadavka = null;
    
    formFilter.addEventListener('submit', function(e) {
        e.preventDefault();
        nacitajArchiv(1);
    });
    
    function nacitajArchiv(strana = 1) {
        if (aktualnaPoziadavka) {
            aktualnaPoziadavka.abort();
        }
        
        const data = new FormData(formFilter);
        data.append('action', 'cl_nacitaj_archiv');
        data.append('nonce', cl_admin.nonce);
        data.append('strana', strana);
        
        aktualnaPoziadavka = new AbortController();
        
        fetch(ajaxurl, {
            method: 'POST',
            signal: aktualnaPoziadavka.signal,
            body: new URLSearchParams(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('archiv-obsah').innerHTML = data.data.html;
                document.getElementById('archiv-pagination').innerHTML = data.data.pagination;
                inicializujAkcie();
            }
        })
        .finally(() => {
            aktualnaPoziadavka = null;
        });
    }
    
    // Načítame prvýkrát
    nacitajArchiv();
});
