document.addEventListener('DOMContentLoaded', function() {
    // Filter histórie
    document.getElementById('historia-filter')?.addEventListener('click', function() {
        const datumOd = document.getElementById('historia-datum-od').value;
        const datumDo = document.getElementById('historia-datum-do').value;
        
        // Validácia dátumov
        if (datumOd && datumDo && new Date(datumOd) > new Date(datumDo)) {
            alert('Dátum "od" nemôže byť neskorší ako dátum "do"');
            return;
        }
        
        // Reload s parametrami
        window.location.href = `?page=cl-historia&datum_od=${datumOd}&datum_do=${datumDo}`;
    });

    // Reset filtra
    document.getElementById('historia-reset')?.addEventListener('click', function() {
        window.location.href = '?page=cl-historia';
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

    // Detail predaja - upravené pre detail dňa
    document.querySelectorAll('.cl-detail-predaja').forEach(btn => {
        btn.addEventListener('click', function() {
            const den = this.dataset.den;
            
            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'cl_detail_dna',
                    nonce: cl_admin.nonce,
                    den: den
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Tu implementujeme zobrazenie detailu dňa
                    // Môžeme použiť modálne okno s detailným rozpisom
                    alert('TODO: Implementovať zobrazenie detailu dňa');
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

    // Tlač lístka
    document.querySelectorAll('.cl-tlac-listok').forEach(btn => {
        btn.addEventListener('click', function() {
            const cislo = this.dataset.cislo;
            
            fetch(ajaxurl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'cl_print_ticket',
                    nonce: cl_admin.nonce,
                    cislo_listka: cislo
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const printWindow = window.open('', 'PRINT', 'height=600,width=800');
                    printWindow.document.write(data.data.html);
                    printWindow.document.close();
                    printWindow.focus();
                    
                    setTimeout(() => {
                        printWindow.print();
                        printWindow.close();
                    }, 250);
                } else {
                    alert('Chyba pri tlači: ' + data.data);
                }
            });
        });
    });

    // Tlač reportu za deň
    document.querySelectorAll('.cl-tlac-report').forEach(btn => {
        btn.addEventListener('click', function() {
            const den = this.dataset.den;
            
            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'cl_tlac_denny_report',
                    nonce: cl_admin.nonce,
                    den: den
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const printWindow = window.open('', 'PRINT', 'height=600,width=800');
                    printWindow.document.write(data.data.html);
                    printWindow.document.close();
                    printWindow.focus();
                    
                    setTimeout(() => {
                        printWindow.print();
                        printWindow.close();
                    }, 250);
                }
            });
        });
    });
});
