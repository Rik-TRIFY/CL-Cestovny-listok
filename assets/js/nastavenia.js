document.addEventListener('DOMContentLoaded', function() {
    // Výber loga
    document.getElementById('cl_upload_logo')?.addEventListener('click', function(e) {
        e.preventDefault();
        
        const frame = wp.media({
            title: 'Vyberte logo lístka',
            multiple: false,
            library: {
                type: 'image'
            }
        });
        
        frame.on('select', function() {
            const attachment = frame.state().get('selection').first().toJSON();
            document.getElementById('cl_logo_url').value = attachment.url;
            aktualizujNahlad();
        });
        
        frame.open();
    });
    
    // Live náhľad
    ['cl_logo_url', 'cl_hlavicka', 'cl_paticka'].forEach(id => {
        document.getElementById(id)?.addEventListener('input', aktualizujNahlad);
    });
    
    function aktualizujNahlad() {
        const preview = document.getElementById('cl-listok-preview');
        const logo = document.getElementById('cl_logo_url').value;
        const hlavicka = document.getElementById('cl_hlavicka').value;
        const paticka = document.getElementById('cl_paticka').value;
        
        let html = '';
        if (logo) {
            html += `<img src="${logo}" style="max-width:100%;height:auto;display:block;margin:0 auto;">`;
        }
        
        html += `<div style="margin:10px 0;font-size:12px;">${hlavicka}</div>`;
        html += `<div style="margin-top:20px;font-size:12px;border-top:1px solid #ddd;padding-top:10px;">${paticka}</div>`;
        
        preview.innerHTML = html;
    }
    
    // Spustiť náhľad pri načítaní
    aktualizujNahlad();
});
