<?php
declare(strict_types=1);

namespace CL\Admin;

class SpravcaArchivu {
    private \CL\Jadro\Databaza $databaza;
    
    public function __construct() {
        $this->databaza = new \CL\Jadro\Databaza();
        add_action('wp_ajax_cl_nacitaj_archiv', [$this, 'ajaxNacitajArchiv']);
    }
    
    public function zobrazArchiv(): void {
        require_once CL_INCLUDES_DIR . 'admin/pohlady/archiv-listkov.php';
    }
    
    public function ajaxNacitajArchiv(): void {
        check_ajax_referer('cl_admin_nonce', 'nonce');
        
        $od = sanitize_text_field($_POST['od'] ?? '');
        $do = sanitize_text_field($_POST['do'] ?? '');
        $strana = (int)($_POST['strana'] ?? 1);
        $limit = 50;
        
        $predaje = $this->nacitajPredaje($od, $do, $strana, $limit);
        $celkom = $this->ziskajPocetPredajov($od, $do);
        
        wp_send_json_success([
            'html' => $this->generujHtmlTabulky($predaje),
            'pagination' => $this->generujPaginaciu($strana, $celkom, $limit)
        ]);
    }
    
    private function nacitajPredaje(string $od, string $do, int $strana, int $limit): array {
        $offset = ($strana - 1) * $limit;
        $where = [];
        $params = [];
        
        if ($od) {
            $where[] = "DATE(datum_predaja) >= %s";
            $params[] = $od;
        }
        if ($do) {
            $where[] = "DATE(datum_predaja) <= %s";
            $params[] = $do;
        }
        
        $where_sql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        return $this->databaza->nacitaj(
            "SELECT * FROM `CL-predaj` 
             $where_sql 
             ORDER BY datum_predaja DESC 
             LIMIT %d OFFSET %d",
            array_merge($params, [$limit, $offset])
        );
    }
}
