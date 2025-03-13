<?php
declare(strict_types=1);

/**
 * Generátor štatistík predaja
 * 
 * Funkcionalita:
 * - Generovanie denných štatistík predaja
 * - Zoskupovanie dát podľa typov lístkov
 * - Export do JSON formátu
 * - Ukladanie do mesačných súborov
 */

namespace CL\Statistiky;

class GeneratorStatistik {
    private \CL\jadro\Databaza $databaza;
    
    public function __construct() {
        $this->databaza = new \CL\jadro\Databaza();
    }
    
    public function generujDenneStatistiky(string $datum = null): bool {
        try {
            $datum = $datum ?? date('Y-m-d');
            $nastavenia = get_option('cl_nastavenia');
            
            // Získame štatistiky predaja podľa typu lístka
            $statistiky = $this->databaza->nacitaj(
                "SELECT tl.nazov, COUNT(*) as pocet, u.display_name as predajca
                 FROM `{$wpdb->prefix}cl_predaj` p
                 JOIN `{$wpdb->prefix}cl_typy_listkov` tl ON p.typ_listka_id = tl.id
                 LEFT JOIN `{$wpdb->users}` u ON p.predajca_id = u.ID
                 WHERE DATE(p.datum_predaja) = %s AND p.storno = FALSE
                 GROUP BY tl.nazov, p.predajca_id",
                [$datum]
            );

            // Pripravíme dáta vo formáte JSON
            $data = [
                $datum => [
                    'sprievodca' => ''
                ]
            ];

            // Naplníme dáta podľa názvov lístkov
            foreach ($statistiky as $stat) {
                $data[$datum][$stat['nazov']] = (int)$stat['pocet'];
                $data[$datum]['sprievodca'] = $stat['predajca'];
            }

            // Vytvoríme súbor
            $mesiac = date('m-Y', strtotime($datum));
            $cielovy_priecinok = $nastavenia['statistiky_priecinok'] ?? WP_CONTENT_DIR . '/statistiky';
            
            if (!file_exists($cielovy_priecinok)) {
                wp_mkdir_p($cielovy_priecinok);
            }

            $subor = $cielovy_priecinok . '/' . $mesiac . '.log';
            
            // Ak súbor existuje, načítame existujúce dáta
            $existujuce_data = [];
            if (file_exists($subor)) {
                $existujuce_data = json_decode(file_get_contents($subor), true) ?: [];
            }
            
            // Zlúčime dáta
            $vysledne_data = array_merge($existujuce_data, $data);
            
            // Uložíme súbor
            $success = file_put_contents($subor, json_encode($vysledne_data, JSON_PRETTY_PRINT)) !== false;
            
            if ($success) {
                update_option('cl_posledne_generovanie_statistik', current_time('mysql'));
            }
            
            return $success;
            
        } catch (\Exception $e) {
            error_log('Chyba pri generovaní štatistík: ' . $e->getMessage());
            return false;
        }
    }
}
