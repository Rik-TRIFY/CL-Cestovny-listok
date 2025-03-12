<?php
declare(strict_types=1);

namespace CL\Jadro;

class SpravcaTlace {
    public function __construct() {
        add_action('wp_ajax_cl_tlac_listok', [$this, 'ajaxTlacListok']);
        add_action('wp_ajax_cl_generuj_listok', [$this, 'ajaxGenerujListok']);
        add_action('wp_ajax_cl_uloz_listok', [$this, 'ajaxUlozListok']);
    }

    public function ajaxTlacListok(): void {
        check_ajax_referer('cl_admin_nonce', 'nonce');
        
        $cislo_listka = sanitize_text_field($_POST['cislo_listka']);
        $html = $this->nacitajListok($cislo_listka);
        
        if ($html) {
            $this->zapisDoLogu('TLAC', [
                'cislo_listka' => $cislo_listka,
                'pouzivatel' => wp_get_current_user()->display_name
            ]);
            wp_send_json_success($html);
        } else {
            wp_send_json_error('Lístok sa nenašiel');
        }
    }

    private function nacitajListok(string $cislo_listka): ?string {
        $subor = CL_PREDAJ_DIR . 'listok-' . $cislo_listka . '.html';
        return file_exists($subor) ? file_get_contents($subor) : null;
    }

    private function zapisDoLogu(string $typ, array $data): void {
        $spravca = new SpravcaSuborov();
        $spravca->zapisDoLogu('TLAC_' . $typ, $data);
    }
}
