document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.cl-export-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const data = new FormData(this);
            data.append('action', 'cl_export_data');
            data.append('nonce', cl_admin.nonce);
            
            fetch(ajaxurl, {
                method: 'POST',
                body: new URLSearchParams(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    stiahniSubor(data.data, form.querySelector('[name="format"]').value);
                }
            });
        });
    });
    
    function stiahniSubor(data, format) {
        const blob = new Blob([data], { type: getMimeType(format) });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `export-${Date.now()}.${format}`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        a.remove();
    }
});
