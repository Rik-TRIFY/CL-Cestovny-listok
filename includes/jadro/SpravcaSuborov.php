<?php
declare(strict_types=1);

namespace CL\jadro;

class SpravcaSuborov {
    public function ulozPredaj(array $data): bool {
        $cislo_predaja = $data['cislo_predaja'];
        $datum = date('Y-m-d');
        
        // Uloženie predaja do súboru
        $predaj_subor = sprintf(
            CL_PREDAJ_DIR . 'predaj-%s.json',
            $cislo_predaja
        );
        
        // Uloženie do denného logu
        $log_subor = sprintf(
            CL_LOGS_DIR . 'predaje-%s.log',
            $datum
        );
        
        // Vytvoríme JSON s kompletnými dátami
        $json_data = json_encode($data, JSON_PRETTY_PRINT);
        
        // Vytvoríme log záznam
        $log_zaznam = sprintf(
            "[%s] Predaj č.%s - Suma: %.2f€ - Predajca: %s\n",
            date('H:i:s'),
            $cislo_predaja,
            $data['celkova_suma'],
            $data['predajca']
        );
        
        // Uložíme oba súbory
        $ulozene_predaj = file_put_contents($predaj_subor, $json_data);
        $ulozene_log = file_put_contents($log_subor, $log_zaznam, FILE_APPEND);
        
        return ($ulozene_predaj !== false && $ulozene_log !== false);
    }
    
    public function nacitajPredaj(string $cislo_predaja): ?array {
        $subor = sprintf(
            CL_PREDAJ_DIR . 'predaj-%s.json',
            $cislo_predaja
        );
        
        if (!file_exists($subor)) {
            return null;
        }
        
        $obsah = file_get_contents($subor);
        return json_decode($obsah, true);
    }
    
    public function ulozListok(string $cislo_listka, string $html): bool {
        $subor = sprintf(
            CL_PREDAJ_DIR . 'listok-%s.html',
            $cislo_listka
        );
        
        return (bool)file_put_contents($subor, $html);
    }
    
    public function zapisDoLogu(string $typ_operacie, array $data): bool {
        $log_subor = sprintf(
            CL_LOGS_DIR . 'system-%s.log',
            date('Y-m-d')
        );
        
        $log_zaznam = sprintf(
            "[%s] %s: %s\n",
            date('H:i:s'),
            $typ_operacie,
            json_encode($data, JSON_PRETTY_PRINT)
        );
        
        return (bool)file_put_contents($log_subor, $log_zaznam, FILE_APPEND);
    }

    public function kontrolujIntegritu(): array {
        $chyby = [];
        $db = new Databaza();
        
        // Kontrola integrity dát medzi databázami
        $vysledok = $db->nacitaj("SELECT COUNT(*) as pocet FROM `CL-predaj`");
        if (!$vysledok) {
            $chyby[] = 'Chyba pri kontrole integrity databáz';
        }
        
        // Kontrola existencie všetkých súborov lístkov
        $predaje = $db->nacitaj("SELECT cislo_predaja FROM `CL-predaj`");
        foreach ($predaje as $predaj) {
            $subor = CL_PREDAJ_DIR . 'listok-' . $predaj['cislo_predaja'] . '.html';
            if (!file_exists($subor)) {
                $chyby[] = 'Chýba súbor lístka: ' . $predaj['cislo_predaja'];
            }
        }
        
        return $chyby;
    }
}
