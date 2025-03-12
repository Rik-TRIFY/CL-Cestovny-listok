<?php
declare(strict_types=1);

namespace CL\Admin;

class SpravcaListkov {
    private \CL\Jadro\Databaza $databaza;
    
    public function __construct() {
        $this->databaza = new \CL\Jadro\Databaza();
        
        add_action('wp_ajax_cl_uloz_listok', [$this, 'ajaxUlozListok']);
        add_action('wp_ajax_cl_zmaz_listok', [$this, 'ajaxZmazListok']);
        add_action('wp_ajax_cl_nacitaj_listky', [$this, 'ajaxNacitajListky']);
    }
    
    public function zobrazSpravuListkov(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Nedostatočné oprávnenia');
        }
        
        require_once CL_INCLUDES_DIR . 'admin/pohlady/sprava-listkov.php';
    }
    
    public function ajaxUlozListok(): void {
        check_ajax_referer('cl_admin_nonce', 'nonce');
        
        $data = [
            'nazov' => sanitize_text_field($_POST['nazov']),
            'cena' => (float)$_POST['cena'],
            'trieda' => sanitize_text_field($_POST['trieda']),
            'skupina' => sanitize_text_field($_POST['skupina']),
            'aktivny' => (bool)$_POST['aktivny'],
            'poradie' => (int)$_POST['poradie']
        ];
        
        $id = $_POST['id'] ?? null;
        
        try {
            if ($id) {
                $this->databaza->aktualizuj('CL-typy_listkov', $data, ['id' => $id]);
            } else {
                $id = $this->databaza->vloz('CL-typy_listkov', $data);
            }
            
            wp_send_json_success(['id' => $id]);
        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    public function ajaxNacitajListky(): void {
        check_ajax_referer('cl_admin_nonce', 'nonce');
        
        $filter = sanitize_text_field($_POST['filter'] ?? '');
        $where = '';
        $params = [];
        
        if ($filter) {
            $where = "WHERE nazov LIKE %s OR trieda LIKE %s OR skupina LIKE %s";
            $filter_param = '%' . $this->databaza->esc_like($filter) . '%';
            $params = [$filter_param, $filter_param, $filter_param];
        }
        
        $listky = $this->databaza->nacitaj(
            "SELECT * FROM `CL-typy_listkov` $where ORDER BY poradie ASC",
            $params
        );
        
        // Získame unikátne triedy a skupiny pre filtre
        $triedy = array_unique(array_column($listky, 'trieda'));
        $skupiny = array_unique(array_column($listky, 'skupina'));
        
        ob_start();
        require CL_INCLUDES_DIR . 'admin/pohlady/listky-tabulka.php';
        $html = ob_get_clean();
        
        wp_send_json_success([
            'html' => $html,
            'triedy' => $triedy,
            'skupiny' => $skupiny
        ]);
    }
    
    public function ajaxZmazListok(): void {
        check_ajax_referer('cl_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Nedostatočné oprávnenia');
            return;
        }
        
        $id = (int)$_POST['id'];
        
        try {
            // Kontrola či lístok nie je použitý v predaji
            $pouzity = $this->databaza->nacitaj(
                "SELECT COUNT(*) as pocet FROM `CL-polozky_predaja` WHERE typ_listka_id = %d",
                [$id]
            );
            
            if ($pouzity[0]['pocet'] > 0) {
                wp_send_json_error('Lístok nie je možné zmazať, pretože je použitý v predaji');
                return;
            }

            $this->databaza->zmaz('CL-typy_listkov', ['id' => $id]);
            wp_send_json_success();
        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    public function aktualizuj($id, $data): bool {
        try {
            return $this->databaza->aktualizuj('CL-typy_listkov', $data, ['id' => $id]);
        } catch (\Exception $e) {
            $this->spravca->zapisDoLogu('LISTKY_AKTUALIZACIA_ERROR', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
