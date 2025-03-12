<?php
declare(strict_types=1);

namespace CL\Admin;

class SpravcaExportu {
    private \CL\Jadro\Databaza $databaza;
    
    public function __construct() {
        $this->databaza = new \CL\Jadro\Databaza();
        add_action('wp_ajax_cl_export_data', [$this, 'ajaxExportData']);
    }
    
    public function ajaxExportData(): void {
        check_ajax_referer('cl_admin_nonce', 'nonce');
        
        $typ = sanitize_text_field($_POST['typ'] ?? '');
        $od = sanitize_text_field($_POST['od'] ?? '');
        $do = sanitize_text_field($_POST['do'] ?? '');
        
        switch ($typ) {
            case 'predaje':
                $data = $this->exportujPredaje($od, $do);
                break;
            case 'statistiky':
                $data = $this->exportujStatistiky($od, $do);
                break;
            default:
                wp_send_json_error('Neplatný typ exportu');
                return;
        }
        
        wp_send_json_success(['data' => $data]);
    }
    
    private function exportujPredaje(string $od, string $do): array {
        return $this->databaza->nacitaj(
            "SELECT p.*, 
                    GROUP_CONCAT(CONCAT(pp.pocet, 'x ', tl.nazov) SEPARATOR ', ') as polozky
             FROM `CL-predaj` p
             LEFT JOIN `CL-polozky_predaja` pp ON p.id = pp.predaj_id
             LEFT JOIN `CL-typy_listkov` tl ON pp.typ_listka_id = tl.id
             WHERE DATE(p.datum_predaja) BETWEEN %s AND %s
             GROUP BY p.id
             ORDER BY p.datum_predaja DESC",
            [$od, $do]
        );
    }
}
