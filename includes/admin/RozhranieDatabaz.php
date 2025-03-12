<?php
declare(strict_types=1);

namespace CL\Admin;

class RozhranieDatabaz {
    private \CL\Jadro\Databaza $databaza;
    
    public function __construct() {
        $this->databaza = new \CL\Jadro\Databaza();
        
        add_action('wp_ajax_cl_skontroluj_databazy', [$this, 'ajaxKontrolaDatabaz']);
        add_action('wp_ajax_cl_synchronizuj_databazu', [$this, 'ajaxSynchronizacia']);
        add_action('admin_notices', [$this, 'zobrazUpozornenia']);
    }
    
    public function ajaxKontrolaDatabaz(): void {
        check_ajax_referer('cl_admin_nonce', 'nonce');
        
        $rozdiely = $this->databaza->skontrolujRozdiely();
        wp_send_json_success([
            'rozdiely' => $rozdiely,
            'html' => $this->generujHtmlRozdielov($rozdiely)
        ]);
    }
    
    public function ajaxSynchronizacia(): void {
        check_ajax_referer('cl_admin_nonce', 'nonce');
        
        try {
            $zdroj = $_POST['zdroj'] ?? 'primary';
            $this->databaza->synchronizujPodlaVyberu($zdroj);
            wp_send_json_success('Databázy boli úspešne synchronizované');
        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    private function generujHtmlRozdielov(array $rozdiely): string {
        ob_start();
        require CL_INCLUDES_DIR . 'admin/pohlady/rozdiely-databaz.php';
        return ob_get_clean();
    }
}
