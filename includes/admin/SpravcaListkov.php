<?php
declare(strict_types=1);

namespace CL\Admin;

class SpravcaListkov {
    private \CL\jadro\Databaza $databaza;
    
    public function __construct() {
        $this->databaza = new \CL\jadro\Databaza();
        
        // AJAX handlery
        add_action('wp_ajax_cl_pridaj_listok', [$this, 'pridajListok']);
        add_action('wp_ajax_cl_uprav_listok', [$this, 'upravListok']);
        add_action('wp_ajax_cl_prepni_aktivny', [$this, 'prepniAktivny']);
    }

    public function zobrazSpravuListkov(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Nedostatočné oprávnenia');
        }
        require_once CL_INCLUDES_DIR . 'admin/pohlady/sprava-listkov.php';
    }

    public function pridajListok(): void {
        check_ajax_referer('cl_listky_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Nedostatočné oprávnenia');
            return;
        }
        
        global $wpdb;
        $nazov = sanitize_text_field($_POST['nazov']);
        $cena = (float)$_POST['cena'];
        
        if (empty($nazov) || $cena <= 0) {
            wp_send_json_error('Neplatné údaje');
            return;
        }
        
        $success = $wpdb->insert(
            $wpdb->prefix . 'cl_typy_listkov',
            [
                'nazov' => $nazov,
                'cena' => $cena,
                'aktivny' => true
            ],
            ['%s', '%f', '%d']
        );
        
        if ($success) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Chyba pri ukladaní: ' . $wpdb->last_error);
        }
    }

    public function upravListok(): void {
        check_ajax_referer('cl_listky_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Nedostatočné oprávnenia');
            return;
        }
        
        $id = (int)$_POST['id'];
        $nazov = sanitize_text_field($_POST['nazov']);
        $cena = (float)$_POST['cena'];
        
        if (empty($nazov) || $cena <= 0) {
            wp_send_json_error('Neplatné údaje');
            return;
        }
        
        $success = $this->databaza->aktualizuj(
            "UPDATE `{$wpdb->prefix}cl_typy_listkov` SET nazov = %s, cena = %f WHERE id = %d",
            [$nazov, $cena, $id]
        );
        
        if ($success) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Chyba pri ukladaní');
        }
    }

    public function prepniAktivny(): void {
        check_ajax_referer('cl_listky_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Nedostatočné oprávnenia');
            return;
        }
        
        $id = (int)$_POST['id'];
        $aktivny = (bool)$_POST['aktivny'];
        
        $success = $this->databaza->aktualizuj(
            "UPDATE `{$wpdb->prefix}cl_typy_listkov` SET aktivny = %d WHERE id = %d",
            [$aktivny, $id]
        );
        
        if ($success) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Chyba pri ukladaní');
        }
    }
}
