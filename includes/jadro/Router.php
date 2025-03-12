<?php
declare(strict_types=1);

namespace CL\Jadro;

class Router {
    public function __construct() {
        add_action('admin_post_cl_stiahnut_zalohu', [$this, 'stiahnutZalohy']);
        add_action('admin_post_cl_generuj_pdf', [$this, 'generujPDF']);
    }
    
    public function stiahnutZalohy(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Nedostatočné oprávnenia');
        }
        
        check_admin_referer('cl_stiahnut_zalohu');
        
        $subor = sanitize_file_name($_GET['subor'] ?? '');
        $cesta = CL_PLUGIN_DIR . 'zalohy/' . $subor;
        
        if (!file_exists($cesta)) {
            wp_die('Súbor neexistuje');
        }
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $subor . '"');
        header('Content-Length: ' . filesize($cesta));
        readfile($cesta);
        exit;
    }
    
    public function generujPDF(): void {
        check_admin_referer('cl_generuj_pdf');
        
        // Logika pre generovanie PDF
        $spravca = new SpravcaExportu();
        $pdf = $spravca->exportujDoPDF(/* params */);
        
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="export.pdf"');
        echo $pdf;
        exit;
    }
}
