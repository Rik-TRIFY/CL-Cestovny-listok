<?php
declare(strict_types=1);

namespace CL\Jadro;

class AutoZaloha {
    private SpravcaSuborov $spravca;
    private Databaza $databaza;
    
    public function __construct() {
        $this->spravca = new SpravcaSuborov();
        $this->databaza = new Databaza();
        
        add_action('cl_denne_zalohy', [$this, 'vytvorDennuZalohu']);
        
        if (!wp_next_scheduled('cl_denne_zalohy')) {
            wp_schedule_event(strtotime('today 23:00'), 'daily', 'cl_denne_zalohy');
        }
    }
    
    public function vytvorDennuZalohu(): void {
        $datum = date('Y-m-d');
        $nazov_suboru = sprintf('zaloha-%s.json', $datum);
        
        $data = [
            'datum' => $datum,
            'cas' => date('H:i:s'),
            'verzia_db' => '1.0',
            'tabulky' => []
        ];
        
        // Záloha všetkých tabuliek
        $tabulky = ['CL-typy_listkov', 'CL-predaj', 'CL-polozky_predaja'];
        
        foreach ($tabulky as $tabulka) {
            $zaznamy = $this->databaza->nacitaj("SELECT * FROM `$tabulka`");
            $data['tabulky'][$tabulka] = $zaznamy;
        }
        
        // Uložíme zálohu
        $vysledok = file_put_contents(
            CL_PLUGIN_DIR . 'zalohy/' . $nazov_suboru,
            json_encode($data, JSON_PRETTY_PRINT)
        );
        
        if ($vysledok === false) {
            $this->spravca->zapisDoLogu('ZALOHA_ERROR', [
                'datum' => $datum,
                'chyba' => 'Nepodarilo sa vytvoriť záložný súbor'
            ]);
        } else {
            $this->spravca->zapisDoLogu('ZALOHA_OK', [
                'datum' => $datum,
                'velkost' => $vysledok
            ]);
            
            // Zmažeme staré zálohy (staršie ako 30 dní)
            $this->zmazStareZalohy();
        }
    }
    
    private function zmazStareZalohy(): void {
        $adresar = CL_PLUGIN_DIR . 'zalohy/';
        $subory = glob($adresar . 'zaloha-*.json');
        $limit = strtotime('-30 days');
        
        foreach ($subory as $subor) {
            if (filemtime($subor) < $limit) {
                unlink($subor);
            }
        }
    }
}
