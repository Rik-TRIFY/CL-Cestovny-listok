jQuery(document).ready(function($) {
    // Kontrola a nastavenie AJAX URL
    if (typeof ajaxurl === 'undefined') {
        window.ajaxurl = '/wp-admin/admin-ajax.php';
        console.warn('ajaxurl nebol definovaný, použitá predvolená hodnota');
    }
    
    // Kontrola cl_admin objektu
    if (typeof cl_admin === 'undefined') {
        window.cl_admin = {
            nonce: '',
            version: '1.0.0'
        };
        console.error('cl_admin nie je definovaný! AJAX požiadavky budú pravdepodobne zlyhávať.');
    }
    
    console.log('Listky.js načítaný');
    console.log('AJAX URL:', ajaxurl);
    console.log('cl_admin objekt:', cl_admin);
    
    console.log('Listky.js načítaný - verzia:', cl_admin.version);
    console.log('jQuery verzia:', $.fn.jquery);
    
    // Kontrola dostupnosti cl_admin
    if (!cl_admin) {
        console.error('CHYBA: cl_admin nie je definovaný!');
        alert('Kritická chyba: Chýbajúca konfigurácia. Kontaktujte administrátora.');
        return;
    }
    
    // Základné nastavenie AJAX URL - dôležité prvé overenie s debug výpisom
    const ajax_url = cl_admin.ajaxurl;
    console.log('AJAX URL:', ajax_url);
    console.log('Nonce hodnota:', cl_admin.nonce);

    // Otvoriť modálne okno pre pridanie nového lístka
    $(document).on('click', '#pridat-listok', function(e) {
        console.log('Kliknutie na pridať lístok');
        $('#modal-title').text('Pridať nový lístok');
        $('#listok-id').val('');
        $('#listok-nazov').val('');
        $('#listok-text').val('');
        $('#listok-cena').val('');
        $('#listok-poradie').val('0');
        $('#listok-modal').show();
    });

    // Zatvoriť modálne okno - použitie delegovaných eventov
    $(document).on('click', '.cl-modal-close, .cl-modal-zrusit', function() {
        console.log('Zatváranie modálneho okna');
        $('#listok-modal').hide();
    });
    
    // Zavrieť modal aj pri kliknutí mimo neho - použitie delegovaného eventu
    $(document).on('click', '#listok-modal', function(e) {
        if ($(e.target).is('#listok-modal')) {
            console.log('Klik mimo modálneho okna - zatváranie');
            $('#listok-modal').hide();
        }
    });

    // Odoslanie formulára - použitie delegovaného eventu
    $(document).on('submit', '#listok-formular', function(e) {
        e.preventDefault();
        console.log('Odosielanie formulára');
        
        // Určenie, či ide o pridanie alebo úpravu lístka
        const idVal = $('#listok-id').val();
        const action = idVal ? 'cl_uprav_listok' : 'cl_pridaj_listok';
        console.log('Typ akcie:', action);
        
        // Zbierka údajov s validáciou
        const data = {
            action: action,
            nonce: cl_admin.nonce,
            id: idVal,
            nazov: $('#listok-nazov').val(),
            text_listok: $('#listok-text').val(),
            cena: $('#listok-cena').val(),
            poradie: $('#listok-poradie').val() || 0
        };
        
        console.log('Odosielané dáta:', data);
        
        // Odoslanie AJAX požiadavky s rozšírenou diagnostikou
        $.ajax({
            url: ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                console.log('Odpoveď servera:', response);
                if (response.success) {
                    location.reload();
                } else {
                    alert('Chyba: ' + (response.data || 'Neznáma chyba'));
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX chyba:', error);
                console.error('Status:', status);
                console.error('Odpoveď:', xhr.responseText);
                alert('Chyba pri komunikácii so serverom: ' + error);
            }
        });
    });

    // Prepínanie aktivity lístka - použitie delegovaného eventu
    $(document).on('click', '.toggle-aktivny', function() {
        const id = $(this).data('id');
        const aktivny = $(this).data('aktivny') == 1 ? 0 : 1;
        
        console.log('Prepínanie aktivity pre ID:', id, 'na hodnotu:', aktivny);
        
        // AJAX požiadavka s rozšírenou diagnostikou
        $.ajax({
            url: ajax_url,
            type: 'POST',
            data: {
                action: 'cl_prepni_aktivny',
                nonce: cl_admin.nonce,
                id: id,
                aktivny: aktivny
            },
            success: function(response) {
                console.log('Odpoveď servera:', response);
                if (response.success) {
                    location.reload(); // Po úspešnej zmene obnovíme stránku
                } else {
                    alert('Chyba: ' + (response.data || 'Neznáma chyba'));
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX chyba:', error);
                console.error('Status:', status);
                console.error('Odpoveď:', xhr.responseText);
                alert('Chyba pri komunikácii so serverom: ' + error);
            }
        });
    });

    // Načítanie údajov pre úpravu - použitie delegovaného eventu
    $(document).on('click', '.upravit-listok', function() {
        const id = $(this).data('id');
        console.log('Načítavanie údajov pre ID:', id);
        
        // AJAX požiadavka s dodatočným logovaním
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'cl_nacitaj_listok',
                nonce: cl_admin.nonce,
                id: id
            },
            beforeSend: function() {
                console.log('Odosielam požiadavku pre ID:', id);
                console.log('Nonce:', cl_admin.nonce);
            },
            success: function(response) {
                console.log('Server response:', response);
                if (response.success && response.data) {
                    // Naplnenie modálneho okna údajmi o lístku
                    $('#modal-title').text('Upraviť lístok');
                    $('#listok-id').val(response.data.id);
                    $('#listok-nazov').val(response.data.nazov);
                    $('#listok-text').val(response.data.text_listok);
                    $('#listok-cena').val(parseFloat(response.data.cena).toFixed(2));
                    $('#listok-poradie').val(parseInt(response.data.poradie) || 0);
                    
                    // Zobrazenie modálneho okna
                    $('#listok-modal').show();
                    
                    console.log('Modal naplnený dátami:', {
                        id: response.data.id,
                        nazov: response.data.nazov,
                        text: response.data.text_listok,
                        cena: response.data.cena,
                        poradie: response.data.poradie
                    });
                } else {
                    alert('Chyba pri načítaní údajov o lístku');
                    console.error('Neplatná odpoveď:', response);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                alert('Chyba pri načítaní údajov: ' + error);
            }
        });
    });

    // Mazanie lístka - použitie delegovaného eventu
    $(document).on('click', '.zmazat-listok', function() {
        if (!confirm('Naozaj chcete zmazať tento lístok?')) {
            return;
        }
        
        const id = $(this).data('id');
        console.log('Mazanie lístka s ID:', id);
        
        // AJAX požiadavka s rozšírenou diagnostikou
        $.ajax({
            url: ajax_url,
            type: 'POST',
            data: {
                action: 'cl_zmaz_listok',
                nonce: cl_admin.nonce,
                id: id
            },
            success: function(response) {
                console.log('Odpoveď servera:', response);
                if (response.success) {
                    location.reload();
                } else {
                    alert('Chyba: ' + (response.data || 'Neznáma chyba'));
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX chyba:', error);
                console.error('Status:', status);
                console.error('Odpoveď:', xhr.responseText);
                alert('Chyba pri komunikácii so serverom: ' + error);
            }
        });
    });

    // Inicializačné kontroly
    console.log('Kontrola modálneho okna:', $('#listok-modal').length ? 'OK' : 'Chýba!');
    console.log('Kontrola formulára:', $('#listok-formular').length ? 'OK' : 'Chýba!');
    console.log('Kontrola tlačidla Pridať:', $('#pridat-listok').length ? 'OK' : 'Chýba!');
    console.log('Kontrola tlačidiel Upraviť:', $('.upravit-listok').length);
    console.log('Kontrola tlačidiel Aktivovať/Deaktivovať:', $('.toggle-aktivny').length);
});
