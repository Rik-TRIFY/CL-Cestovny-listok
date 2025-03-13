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
                // Zachováme pôvodnú šírku obrázka ak je menšia ako 54mm (cca 204px pri 96dpi)
                const width = attachment.width > 204 ? 204 : attachment.width;
                const imgTag = `<img src="${attachment.url}" alt="" width="${width}" style="max-width:100%;height:auto;">`;
                
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

    // Test tlače
    document.getElementById('preview-print')?.addEventListener('click', function() {
        // Získame obsah z TinyMCE editora
        const content = tinymce.get('sablona-listka').getContent();
        let html = content;

        // Nahradíme premenné testovacími dátami
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
        
        // Nahradíme premenné v HTML
        Object.entries(testData).forEach(([key, value]) => {
            html = html.replace(new RegExp(`{${key}}`, 'g'), value);
        });

        // Otvoríme nové okno pre tlač
        const printWindow = window.open('', 'PRINT', 'height=600,width=800');
        
        printWindow.document.write(`
            <html>
            <head>
                <title>Test tlače lístka</title>
                <style>
                    @media print {
                        body {
                            width: 54mm;
                            margin: 0;
                            padding: 0;
                        }
                        @page {
                            size: 54mm auto;
                            margin: 0;
                        }
                    }
                    body {
                        font-family: ${document.getElementById('cl_nastavenia[pismo]')?.value || 'Arial'};
                        font-size: ${document.getElementById('cl_nastavenia[font_velkost]')?.value || '12'}px;
                    }
                    /* Štýly pre tlač */
                    img { max-width: 100%; height: auto; }
                    .polozka { margin: 5px 0; }
                    .suma { font-weight: bold; margin-top: 10px; }
                </style>
            </head>
            <body>
                ${html}
            </body>
            </html>
        `);
        
        printWindow.document.close();
        printWindow.focus();
        
        // Spustíme tlač po načítaní obsahu
        setTimeout(function() {
            printWindow.print();
            printWindow.close();
        }, 250);
    });

    // POS Preview Updates
    function aktualizujPosNahlad() {
        const layout = document.getElementById('pos_layout')?.value || 'grid';
        const colors = {
            primary: document.getElementById('pos_color_primary')?.value,
            secondary: document.getElementById('pos_color_secondary')?.value,
            background: document.getElementById('pos_color_background')?.value
        };

        const preview = document.getElementById('cl-pos-preview');
        if (preview) {
            preview.className = `cl-preview-window pos-layout-${layout}`;
            
            // Apply custom styles
            const customStyles = document.createElement('style');
            customStyles.textContent = `
                #cl-pos-preview .cl-listok {
                    background-color: ${colors.primary};
                    color: ${colors.secondary};
                }
                #cl-pos-preview .cl-terminal-container {
                    background-color: ${colors.background};
                }
            `;
            preview.appendChild(customStyles);
        }
    }

    // Live preview for POS settings
    ['pos_layout', 'pos_color_primary', 'pos_color_secondary', 'pos_color_background'].forEach(id => {
        document.getElementById(id)?.addEventListener('change', aktualizujPosNahlad);
    });

    // Manual refresh for POS preview
    document.getElementById('pos-preview-refresh')?.addEventListener('click', aktualizujPosNahlad);

    // Device Preview Selector
    const deviceSelect = document.getElementById('cl-device-select');
    const customResolution = document.getElementById('cl-custom-resolution');
    const deviceFrame = document.getElementById('cl-device-frame');
    const posPreview = document.getElementById('cl-pos-preview');
    const widthInput = document.getElementById('cl-width');
    const heightInput = document.getElementById('cl-height');

    const deviceSizes = {
        'custom': null,
        'iphone-se': { width: 375, height: 667 },
        'iphone-xr': { width: 414, height: 896 },
        'pixel-5': { width: 393, height: 851 },
        'samsung-s20': { width: 360, height: 800 },
        'samsung-s8': { width: 360, height: 740 }
    };

    function updatePreviewSize(width, height) {
        deviceFrame.style.width = width + 'px';
        deviceFrame.style.height = height + 'px';
        posPreview.style.width = width + 'px';
        posPreview.style.height = height + 'px';
    }

    deviceSelect?.addEventListener('change', function() {
        const selected = this.value;
        customResolution.style.display = selected === 'custom' ? 'flex' : 'none';
        
        if (selected !== 'custom') {
            const size = deviceSizes[selected];
            updatePreviewSize(size.width, size.height);
            widthInput.value = size.width;
            heightInput.value = size.height;
        }
    });

    [widthInput, heightInput].forEach(input => {
        input?.addEventListener('change', function() {
            updatePreviewSize(widthInput.value, heightInput.value);
        });
    });

    // Aktualizácia hidden inputov pri zmene rozlíšenia
    [widthInput, heightInput].forEach(input => {
        input?.addEventListener('change', function() {
            const posWidth = document.getElementById('pos_width');
            const posHeight = document.getElementById('pos_height');
            
            if (posWidth && posHeight) {
                posWidth.value = widthInput.value;
                posHeight.value = heightInput.value;
            }
            
            updatePreviewSize(widthInput.value, heightInput.value);
        });
    });

    // Aktualizácia rozlíšenia
    [widthInput, heightInput].forEach(input => {
        input?.addEventListener('input', function() {
            const posWidth = document.getElementById('pos_width');
            const posHeight = document.getElementById('pos_height');
            
            if (posWidth && posHeight) {
                // Aktualizujeme hidden inputy
                posWidth.value = widthInput.value;
                posHeight.value = heightInput.value;
            }
            
            // Aktualizujeme náhľad
            updatePreviewSize(widthInput.value, heightInput.value);
        });
    });

    // Načítanie uložených hodnôt pri načítaní stránky
    const savedWidth = document.getElementById('pos_width')?.value || '375';
    const savedHeight = document.getElementById('pos_height')?.value || '667';
    
    if (widthInput && heightInput) {
        widthInput.value = savedWidth;
        heightInput.value = savedHeight;
        updatePreviewSize(savedWidth, savedHeight);
        if (deviceSelect) {
            deviceSelect.value = 'custom';
            customResolution.style.display = 'flex';
        }
    }

    // Načítanie uložených hodnôt pri inicializácii
    const savedWidth = document.getElementById('pos_width')?.value;
    const savedHeight = document.getElementById('pos_height')?.value;
    
    if (savedWidth && savedHeight) {
        widthInput.value = savedWidth;
        heightInput.value = savedHeight;
        updatePreviewSize(savedWidth, savedHeight);
        deviceSelect.value = 'custom';
        customResolution.style.display = 'flex';
    }

    // Inicializácia náhľadu
    aktualizujNahlad();
    
    // Spustiť náhľad pri načítaní
    aktualizujNahlad();
});
