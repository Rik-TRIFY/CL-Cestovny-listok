document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('generuj-statistiky')?.addEventListener('click', function() {
        this.disabled = true;
        this.textContent = 'Generujem...';
        
        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'cl_generuj_statistiky',
                nonce: cl_admin.nonce,
                datum: new Date().toISOString().split('T')[0]
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Chyba pri generovaní štatistík: ' + data.data);
            }
        })
        .finally(() => {
            this.disabled = false;
            this.textContent = 'Generovať štatistiky';
        });
    });
});
