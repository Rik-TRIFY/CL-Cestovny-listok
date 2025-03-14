<?php
declare(strict_types=1);

namespace CL\Admin;

class SpravcaArchivu {
    private \CL\Jadro\Databaza $databaza;
    
    public function __construct() {
        $this->databaza = new \CL\Jadro\Databaza();
        add_action('wp_ajax_cl_nacitaj_archiv', [$this, 'ajaxNacitajArchiv']);
        add_action('wp_ajax_cl_detail_dna', [$this, 'ajaxDetailDna']);
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
    
    public function ajaxDetailDna(): void {
        if (!wp_verify_nonce($_POST['nonce'], 'cl_admin')) {
            wp_send_json_error('Neplatný bezpečnostný token');
            return;
        }

        if (!isset($_POST['den'])) {
            wp_send_json_error('Chýba parameter den');
            return;
        }

        $den = sanitize_text_field($_POST['den']);
        
        // Získanie detailných údajov o predaji za daný deň
        $predaje = $this->databaza->nacitaj(
            "SELECT 
                p.cislo_predaja,
                p.datum_predaja,
                u.display_name as predajca,
                p.celkova_suma,
                GROUP_CONCAT(
                    CONCAT(t.nazov, ': ', pp.pocet, 'x (', pp.cena_za_kus, '€)')
                    ORDER BY t.nazov
                    SEPARATOR '\n'
                ) as polozky
            FROM `{$this->wpdb->prefix}cl_predaj` p
            LEFT JOIN `{$this->wpdb->prefix}cl_polozky_predaja` pp ON p.id = pp.predaj_id
            LEFT JOIN `{$this->wpdb->prefix}cl_typy_listkov` t ON pp.typ_listka_id = t.id
            LEFT JOIN `{$this->wpdb->users}` u ON p.predajca_id = u.ID
            WHERE DATE(p.datum_predaja) = %s
            GROUP BY p.id
            ORDER BY p.datum_predaja",
            [$den]
        );

        // Sumarizácia pre daný deň
        $sumar = $this->databaza->nacitaj(
            "SELECT 
                t.nazov,
                SUM(pp.pocet) as celkovy_pocet,
                SUM(pp.pocet * pp.cena_za_kus) as celkova_suma
            FROM `{$this->wpdb->prefix}cl_predaj` p
            JOIN `{$this->wpdb->prefix}cl_polozky_predaja` pp ON p.id = pp.predaj_id
            JOIN `{$this->wpdb->prefix}cl_typy_listkov` t ON pp.typ_listka_id = t.id
            WHERE DATE(p.datum_predaja) = %s
            GROUP BY t.id
            ORDER BY t.nazov",
            [$den]
        );

        wp_send_json_success([
            'predaje' => $predaje,
            'sumar' => $sumar,
            'den' => $den
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
