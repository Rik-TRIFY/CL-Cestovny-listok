<?php
declare(strict_types=1);

/**
 * Hlavná trieda pre POS terminál
 * 
 * Spracováva logiku pre:
 * - Predaj lístkov
 * - Správu košíka
 * - Generovanie čísiel lístkov
 * - Ukladanie predajov do DB
 * - Tlač lístkov
 */

namespace CL\POS;

class Terminal {
    private \CL\jadro\Databaza $databaza;
    private \CL\jadro\SpravcaNastaveni $spravca;

    public function __construct() {
        $this->databaza = new \CL\jadro\Databaza();
        $this->spravca = \CL\jadro\SpravcaNastaveni::ziskajInstanciu();
        add_action('wp_ajax_cl_pridaj_do_kosika', [$this, 'pridajDoKosika']);
        add_action('wp_ajax_cl_odstran_z_kosika', [$this, 'odstranZKosika']);
        add_action('wp_ajax_cl_dokonci_predaj', [$this, 'dokonciPredaj']);
        add_action('wp_enqueue_scripts', [$this, 'pridajAssets']);
        add_action('wp_ajax_cl_dokoncit_predaj', [$this, 'dokoncitPredaj']);
    }

    public function pridajAssets(): void {
        wp_enqueue_style('cl-pos', CL_ASSETS_URL . 'css/pos.css', [], CL_VERSION);
        wp_enqueue_script('cl-pos', CL_ASSETS_URL . 'js/pos.js', ['jquery'], CL_VERSION, true);
        
        wp_localize_script('cl-pos', 'cl_pos', [
            'nonce' => wp_create_nonce('cl_pos_nonce'),
            'ajaxurl' => admin_url('admin-ajax.php')
        ]);
    }

    public function zobrazTerminal(): void {
        if (!is_user_logged_in()) {
            wp_die('Nemáte dostatočné oprávnenia na zobrazenie terminálu.');
        }
        
        $predajca = wp_get_current_user();
        
        // Načítame nastavenia z našej DB
        $layout = $this->spravca->nacitaj('pos_layout', 'grid');
        $columns = $this->spravca->nacitaj('pos_columns', 4);
        
        require CL_INCLUDES_DIR . 'pos/pohlady/terminal.php';
    }

    public function pridajDoKosika(): void {
        check_ajax_referer('cl_pos_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Nedostatočné oprávnenia');
            return;
        }
        
        $typ_listka_id = (int)$_POST['typ_listka_id'];
        $pocet = max(1, (int)$_POST['pocet']);
        
        $listok = $this->databaza->nacitaj(
            "SELECT * FROM `CL-typy_listkov` WHERE id = %d AND aktivny = TRUE",
            [$typ_listka_id]
        );
        
        if (empty($listok)) {
            wp_send_json_error('Neplatný lístok');
            return;
        }
        
        wp_send_json_success([
            'id' => $listok[0]['id'],
            'nazov' => $listok[0]['nazov'],
            'pocet' => $pocet,
            'cena' => (float)$listok[0]['cena']
        ]);
    }

    private function generujCisloListka(): string {
        $datum = date('Ymd');
        $poradie = 1;
        
        global $wpdb;
        $posledny = $wpdb->get_var($wpdb->prepare(
            "SELECT cislo_predaja FROM `CL-predaj` 
             WHERE cislo_predaja LIKE %s 
             ORDER BY cislo_predaja DESC LIMIT 1",
            "$datum-%"
        ));
        
        if ($posledny) {
            list(, $cislo) = explode('-', $posledny);
            $poradie = intval($cislo) + 1;
        }
        
        return sprintf('%s-%04d', $datum, $poradie);
    }

    private function kontrolaPredaja(array $polozky): bool {
        if (empty($polozky)) {
            throw new \Exception('Košík je prázdny');
        }

        foreach ($polozky as $polozka) {
            $listok = $this->databaza->nacitaj(
                "SELECT * FROM `CL-typy_listkov` WHERE id = %d AND aktivny = TRUE",
                [$polozka['id']]
            );
            
            if (empty($listok)) {
                throw new \Exception('Neplatný lístok v košíku');
            }
        }
        
        return true;
    }

    public function dokonciPredaj(): void {
        check_ajax_referer('cl_pos_nonce', 'nonce');
        
        $polozky = json_decode(stripslashes($_POST['polozky']), true);
        $celkova_suma = 0;
        $spravca = new \CL\Jadro\SpravcaSuborov();
        
        global $wpdb;
        $wpdb->query('START TRANSACTION');
        
        try {
            $cislo_listka = $this->generujCisloListka();
            $predajca = wp_get_current_user();
            
            // Najprv vytvoríme záznam v DB
            $cislo_predaja = date('Ymd') . sprintf('%04d', rand(0, 9999));
            $predajca = wp_get_current_user();
            
            // Pripravíme kompletné dáta predaja
            $data_predaja = [
                'cislo_predaja' => $cislo_listka,
                'predajca_id' => $predajca->ID,
                'predajca' => $predajca->display_name,
                'polozky' => $polozky,
                'celkova_suma' => 0,
                'datum_predaja' => current_time('mysql'),
                'hlavicka' => get_option('cl_hlavicka'),
                'paticka' => get_option('cl_paticka'),
                'logo_url' => get_option('cl_logo_url')
            ];

            // Uložíme do DB
            $wpdb->insert('CL-predaj', [
                'cislo_predaja' => $cislo_listka,
                'predajca_id' => get_current_user_id(),
                'celkova_suma' => 0,
                'datum_predaja' => current_time('mysql'),
                'data_listka' => json_encode([  // Pridáme JSON dáta priamo do DB
                    'polozky' => $polozky,
                    'hlavicka' => get_option('cl_hlavicka'),
                    'paticka' => get_option('cl_paticka'),
                    'logo_url' => get_option('cl_logo_url')
                ])
            ]);
            
            $predaj_id = $wpdb->insert_id;
                
            foreach ($polozky as $polozka) {
                $listok = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM `CL-typy_listkov` WHERE id = %d AND aktivny = TRUE",
                    $polozka['typ_listka_id']
                ));
                
                if (!$listok) {
                    throw new \Exception('Neplatný lístok');
                }
                
                $cena_za_kus = (float)$listok->cena;
                $suma_polozky = $cena_za_kus * $polozka['pocet'];
                $celkova_suma += $suma_polozky;
                
                $wpdb->insert('CL-polozky_predaja', [
                    'predaj_id' => $predaj_id,
                    'typ_listka_id' => $listok->id,
                    'pocet' => $polozka['pocet'],
                    'cena_za_kus' => $cena_za_kus
                ]);
            }
            
            // Aktualizácia celkovej sumy
            $wpdb->update('CL-predaj', 
                ['celkova_suma' => $celkova_suma],
                ['id' => $predaj_id]
            );
            
            // Uložíme do súborov
            if (!$spravca->ulozPredaj($data_predaja)) {
                throw new \Exception('Chyba pri ukladaní súborov');
            }
            
            $wpdb->query('COMMIT');
            
            // Generovanie a uloženie lístka
            $generator = new \CL\Listky\Generator();
            $data = [
                'cislo_predaja' => $cislo_listka,
                'polozky' => $polozky,
                'celkova_suma' => $celkova_suma,
                'predajca' => wp_get_current_user()->display_name,
                'datum' => current_time('mysql')
            ];
            
            $html_listok = $generator->generujListok([
                'cislo_listka' => $cislo_listka,
                'polozky' => $polozky,
                'celkova_suma' => $celkova_suma,
                'predajca' => $predajca->display_name,
                'datum' => current_time('mysql')
            ]);
            
            // Uložíme HTML lístok
            if (!$spravca->ulozListok($cislo_listka, $html_listok)) {
                throw new \Exception('Chyba pri ukladaní lístka');
            }
            
            // Zalogujeme operáciu
            $spravca->zapisDoLogu('PREDAJ', [
                'cislo_listka' => $cislo_listka,
                'predajca' => $predajca->display_name,
                'suma' => $celkova_suma
            ]);
            
            wp_send_json_success([
                'cislo_listka' => $cislo_listka,
                'html_listok' => $html_listok
            ]);
            
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            wp_send_json_error($e->getMessage());
        }
    }

    public function dokoncitPredaj(): void {
        check_ajax_referer('cl_pos_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Nie ste prihlásený');
            return;
        }

        $items = json_decode(stripslashes($_POST['items']), true);
        if (empty($items)) {
            wp_send_json_error('Prázdny košík');
            return;
        }

        // TODO: Logika pre uloženie predaja do DB
        
        wp_send_json_success([
            'html' => $this->generujHTML($items)
        ]);
    }

    private function generujHTML(array $items): string {
        // TODO: Generovanie HTML pre tlač
        return '<h1>Test tlače</h1>';
    }
}
