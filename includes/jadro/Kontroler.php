<?php
declare(strict_types=1);

namespace CL\jadro;

class Kontroler {
    private Databaza $databaza;
    private SpravcaSuborov $spravca;
    
    public function __construct() {
        $this->databaza = new Databaza();
        $this->spravca = new SpravcaSuborov();
        
        add_action('cl_kontrola_integrity', [$this, 'kontrolujSystem']);
    }
    
    public function aktivujKontroly(): void {
        if (!wp_next_scheduled('cl_kontrola_integrity')) {
            wp_schedule_event(time(), 'hourly', 'cl_kontrola_integrity');
        }
    }
    
    public function deaktivujKontroly(): void {
        wp_clear_scheduled_hook('cl_kontrola_integrity');
    }
    
    public function kontrolujSystem(): void {
        // Kontrola integrity databáz
        $rozdiely = $this->databaza->kontrolujIntegrituDatabaz();
        if (!empty($rozdiely)) {
            $this->spravca->zapisDoLogu('KONTROLA_DB', $rozdiely);
            
            // Notifikácia administrátora
            if (get_option('cl_nastavenia')['notifikacia_rozdielov']) {
                $this->notifikujAdmina('Zistené rozdiely v databázach', $rozdiely);
            }

            try {
                $this->databaza->opravIntegritu();
                $this->spravca->zapisDoLogu('OPRAVA_DB', [
                    'status' => 'success',
                    'timestamp' => current_time('mysql')
                ]);
            } catch (\Exception $e) {
                $this->spravca->zapisDoLogu('OPRAVA_DB_ERROR', [
                    'message' => $e->getMessage(),
                    'timestamp' => current_time('mysql')
                ]);
            }
        }
        
        // Kontrola súborov
        $chyby = $this->spravca->kontrolujIntegritu();
        if (!empty($chyby)) {
            $this->spravca->zapisDoLogu('KONTROLA_SUBORY', $chyby);
            $this->notifikujAdmina('Zistené problémy so súbormi', $chyby);
        }
    }

    private function notifikujAdmina(string $titul, array $data): void {
        $spravca = new SpravcaNotifikacii();
        $spravca->pridajNotifikaciu(
            $titul . ' - <a href="' . admin_url('admin.php?page=cl-nastavenia&tab=system') . 
            '">Zobraziť detaily</a>',
            'warning'
        );

        // Poslanie emailu administrátorovi
        $admin_email = get_option('admin_email');
        if ($admin_email) {
            wp_mail(
                $admin_email,
                '[CL] ' . $titul,
                print_r($data, true)
            );
        }
    }
}
