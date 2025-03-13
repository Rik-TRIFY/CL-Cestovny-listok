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
    
    // Editor lístka
    const editor = document.getElementById('sablona-listka');
    const preview = document.getElementById('cl-listok-preview');
    const autoRefresh = document.getElementById('preview-auto-refresh');

    // Toolbar tlačidlá
    document.querySelectorAll('.cl-editor-toolbar button').forEach(btn => {
        btn.addEventListener('click', function() {
            const tag = this.dataset.tag;
            const variable = this.dataset.var;
            
            if (editor.selectionStart || editor.selectionStart === 0) {
                const startPos = editor.selectionStart;
                const endPos = editor.selectionEnd;
                const selected = editor.value.substring(startPos, endPos);
                
                if (tag) {
                    const replacement = `<${tag}>${selected}</${tag}>`;
                    editor.value = editor.value.substring(0, startPos) + replacement + editor.value.substring(endPos);
                } else if (variable) {
                    editor.value = editor.value.substring(0, startPos) + variable + editor.value.substring(endPos);
                }
                
                aktualizujNahlad();
            }
        });
    });

    // WordPress Media Uploader pre obrázky
    document.querySelectorAll('.cl-editor-toolbar button[data-tag="img"]').forEach(btn => {
        btn.addEventListener('click', function() {
            const mediaFrame = wp.media({
                title: 'Vybrať obrázok',
                multiple: false,
                library: {
                    type: 'image'
                }
            });

            mediaFrame.on('select', function() {
                const attachment = mediaFrame.state().get('selection').first().toJSON();
                const imgTag = `<img src="${attachment.url}" alt="" style="max-width:100%;height:auto;">`;
                
                // Vložíme tag na pozíciu kurzora
                if (editor.selectionStart || editor.selectionStart === 0) {
                    const startPos = editor.selectionStart;
                    editor.value = editor.value.substring(0, startPos) + imgTag + editor.value.substring(editor.selectionEnd);
                    aktualizujNahlad();
                }
            });

            mediaFrame.open();
        });
    });

    function aktualizujNahlad() {
        let html = editor.value;
        
        // Nahradenie premenných testovacími dátami
        const testData = {
            logo: '<img src="/test-logo.png" style="max-width:100%">',
            datum: '01.03.2024',
            cas: '14:30',
            cislo_listka: '20240301-0001',
            predajca: 'Test Predajca',
            polozky: `
                <div class="polozka">
                    <div>Cestovný lístok základný</div>
                    <div>2x 1.20€ = 2.40€</div>
                </div>
                <div class="polozka">
                    <div>Cestovný lístok zľavnený</div>
                    <div>1x 0.60€ = 0.60€</div>
                </div>
            `,
            suma: '3.00€'
        };
        
        Object.entries(testData).forEach(([key, value]) => {
            html = html.replace(new RegExp(`{${key}}`, 'g'), value);
        });
        
        preview.innerHTML = html;
    }

    // Automatický náhľad pri písaní
    editor?.addEventListener('input', () => {
        if (autoRefresh.checked) {
            aktualizujNahlad();
        }
    });

    // Manuálne obnovenie náhľadu
    document.getElementById('preview-refresh')?.addEventListener('click', aktualizujNahlad);

    // Inicializácia náhľadu
    aktualizujNahlad();
    
    // Spustiť náhľad pri načítaní
    aktualizujNahlad();
});
