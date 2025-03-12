<?php
declare(strict_types=1);

namespace CL\jadro;

class SpravcaVerzie {
    public function __construct() {
        add_action('admin_init', [$this, 'kontrolaVerzie']);
    }

    public function kontrolaVerzie(): void {
        $aktualna_verzia = get_option('cl_db_verzia', '0');
        
        if (version_compare($aktualna_verzia, CL_VERSION, '<')) {
            $this->aktualizacia($aktualna_verzia);
            update_option('cl_db_verzia', CL_VERSION);
        }
    }

    public function inicializuj(): void {
        $this->nastavZakladneNastavenia();
    }

    private function aktualizacia(string $stara_verzia): void {
        global $wpdb;
        
        if (version_compare($stara_verzia, '1.0.0', '<')) {
            // Inicializačné aktualizácie pre verziu 1.0.0
            $this->vytvorPriecinky();
            $this->nastavZakladneNastavenia();
        }
    }

    private function vytvorPriecinky(): void {
        $priecinky = [
            CL_PLUGIN_DIR . 'zalohy',
            CL_PLUGIN_DIR . 'assets/images',
            CL_PREDAJ_DIR,
            CL_LOGS_DIR
        ];

        foreach ($priecinky as $priecinok) {
            if (!file_exists($priecinok)) {
                wp_mkdir_p($priecinok);
                file_put_contents($priecinok . '/index.php', '<?php // Silence is golden');
            }
        }
    }

    private function nastavZakladneNastavenia(): void {
        $nastavenia = [
            'sirka_tlace' => '54',
            'auto_tlac' => true,
            'interval_kontroly' => 'hourly',
            'notifikacia_rozdielov' => true,
            'zachovat_zalohy_dni' => 30,  // Pridané
            'auto_zaloha' => true         // Pridané
        ];

        foreach ($nastavenia as $kluc => $hodnota) {
            if (get_option('cl_' . $kluc) === false) {
                update_option('cl_' . $kluc, $hodnota);
            }
        }
    }
}
