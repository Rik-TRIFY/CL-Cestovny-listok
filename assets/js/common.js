const CLUtils = {
    formatujCislo(cislo, desatinne = 2) {
        return Number(cislo).toFixed(desatinne);
    },

    formatujDatum(datum) {
        return new Date(datum).toLocaleDateString('sk-SK');
    },

    zobrazNotifikaciu(sprava, typ = 'success') {
        const notif = document.createElement('div');
        notif.className = `cl-notifikacia ${typ}`;
        notif.textContent = sprava;
        document.body.appendChild(notif);
        
        setTimeout(() => notif.remove(), 3000);
    },

    async posliAjax(action, data = {}) {
        try {
            const response = await fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action,
                    nonce: cl_admin.nonce,
                    ...data
                })
            });
            return await response.json();
        } catch (e) {
            console.error('AJAX Error:', e);
            this.zobrazNotifikaciu('Nastala chyba pri komunikácii so serverom', 'error');
            return null;
        }
    }
};
