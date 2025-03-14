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
        add_action('wp_ajax_cl_pridaj_do_kosika', [$this, 'ajaxPridajDoKosika']);
        add_action('wp_ajax_cl_dokoncit_predaj', [$this, 'ajaxDokoncitPredaj']);
        add_action('wp_ajax_cl_nacitaj_predaje', [$this, 'ajaxNacitajPosledne']);
        add_action('wp_ajax_cl_tlacit_listok', [$this, 'ajaxTlacitListok']);
        add_action('wp_enqueue_scripts', [$this, 'pridajAssets']);
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
            echo '<p class="cl-error">Pre prístup k POS terminálu sa musíte prihlásiť.</p>';
            return;
        }
        
        // Získanie prihlaseného používateľa
        $current_user = wp_get_current_user();
        $user_initials = mb_substr($current_user->display_name, 0, 1);
        
        // Priame načítanie lístkov z databázy s opravenými názvami tabuliek
        global $wpdb;
        
        // Kontrola, či existuje stĺpec 'poradie'
        $check_column = $wpdb->get_results("SHOW COLUMNS FROM `{$wpdb->prefix}cl_typy_listkov` LIKE 'poradie'");
        
        // Príprava SQL dotazu
        $table_name = $wpdb->prefix . 'cl_typy_listkov';
        
        // Ak stĺpec poradie existuje, použijeme ho na zoradenie
        if (!empty($check_column)) {
            $sql = "SELECT * FROM `{$table_name}` WHERE aktivny = '1' ORDER BY poradie ASC, nazov ASC";
        } else {
            $sql = "SELECT * FROM `{$table_name}` WHERE aktivny = '1' ORDER BY nazov ASC";
            
            // Pridáme stĺpec poradie do tabuľky
            $wpdb->query("ALTER TABLE `{$table_name}` ADD COLUMN `poradie` int(11) DEFAULT 0 AFTER `aktivny`");
            
            // Po pridaní stĺpca aktualizujeme SQL dotaz
            $sql = "SELECT * FROM `{$table_name}` WHERE aktivny = '1' ORDER BY poradie ASC, nazov ASC";
        }
        
        // Získanie záznamov s explicitnou kontrolou hodnoty aktivny ako string '1'
        $listky = $wpdb->get_results($sql);
        
        // Debug: Kontrola počtu vrátených záznamov
        if ($wpdb->last_error) {
            echo '<div class="cl-debug-info">SQL Error: ' . esc_html($wpdb->last_error) . '</div>';
        }
        
        // Zobrazenie všetkých lístkov aj neaktívnych pre diagnostiku
        if (empty($listky)) {
            echo '<div class="cl-debug-info">Neboli nájdené žiadne aktívne lístky v tabuľke ' . 
                esc_html($table_name) . '.</div>';
            
            // Skúsime načítať všetky lístky bez filtrovania
            $all_tickets = $wpdb->get_results("SELECT id, nazov, cena, aktivny FROM `{$table_name}`");
            
            if (!empty($all_tickets)) {
                echo '<div class="cl-debug-info">Našli sme ' . count($all_tickets) . 
                     ' lístkov bez filtrovania aktivny. Tu sú:</div>';
                echo '<ul class="cl-debug-list">';
                foreach ($all_tickets as $ticket) {
                    echo '<li>ID: ' . $ticket->id . ', Názov: ' . $ticket->nazov . 
                         ', Cena: ' . $ticket->cena . ', Aktivny: [' . var_export($ticket->aktivny, true) . ']' . 
                         ' (Typ: ' . gettype($ticket->aktivny) . ')</li>';
                }
                echo '</ul>';
                
                // Skúsime použiť tieto lístky namiesto filtrovania
                $listky = $all_tickets;
            }
        }
        
        // Načítanie a spracovanie šablóny
        $sablona = $this->nacitajSablonuTerminalu();
        
        // Nahradzovanie placeholderov
        $app_name = $this->spravca->nacitaj('pos_app_name', 'POSka - Cestovné lístky');
        $recently_added_label = $this->spravca->nacitaj('pos_recently_added_label', 'Naposledy pridané');
        $goto_cart_button = $this->spravca->nacitaj('pos_goto_cart_button', 'Prejsť do košíka');
        $cart_title = $this->spravca->nacitaj('pos_cart_title', 'Košík');
        $total_sum_label = $this->spravca->nacitaj('pos_total_sum_label', 'SUMA CELKOM');
        $back_button = $this->spravca->nacitaj('pos_back_button', 'Vrátiť späť');
        $checkout_button = $this->spravca->nacitaj('pos_checkout_button', 'ULOŽIŤ a TLAČ');
        
        // Nahradenie placeholderov v šablóne skutočnými údajmi
        $sablona = str_replace(
            [
                '{user_name}',
                '{user_initials}',
                '{ticket_buttons}',
                '{app_name}',
                '{recently_added_label}',
                '{goto_cart_button}',
                '{cart_title}',
                '{total_sum_label}',
                '{back_button}',
                '{checkout_button}'
            ],
            [
                esc_html($current_user->display_name),
                esc_html($user_initials),
                $this->generujTlacidlaListkov($listky),
                esc_html($app_name),
                esc_html($recently_added_label),
                esc_html($goto_cart_button),
                esc_html($cart_title),
                esc_html($total_sum_label),
                esc_html($back_button),
                esc_html($checkout_button)
            ],
            $sablona
        );
        
        echo $sablona;
    }

    private function nacitajSablonuTerminalu(): string {
        $cestaKSablonam = CL_PLUGIN_DIR . 'sablony/terminal.html';
        
        if (file_exists($cestaKSablonam)) {
            return file_get_contents($cestaKSablonam);
        }
        
        // Fallback ak súbor neexistuje
        return '<p class="cl-error">Šablóna terminálu sa nenašla.</p>';
    }

    private function generujTlacidlaListkov(array $listky): string {
        $html = '';
        
        foreach ($listky as $listok) {
            $html .= sprintf(
                '<div class="pos-product cl-ticket-btn" data-id="%d" data-nazov="%s" data-cena="%.2f">
                    <div class="pos-product-name">%s</div>
                    <div class="pos-product-price">%.2f €</div>
                </div>',
                $listok->id,
                esc_attr($listok->nazov),
                (float)$listok->cena,
                esc_html($listok->nazov),
                (float)$listok->cena
            );
        }
        
        return $html;
    }

    public function pridajDoKosika(): void {
        check_ajax_referer('cl_pos_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Nedostatočné oprávnenia');
            return;
        }
        
        $typ_listka_id = (int)$_POST['typ_listka_id'];
        $pocet = max(1, (int)$_POST['pocet']);
        
        $listok = $this->databaza->nacitajPole(
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
            $listok = $this->databaza->nacitajPole(
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
        $spravca = new \CL\jadro\SpravcaSuborov();
        
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

    public function ajaxPridajDoKosika(): void {
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

    public function ajaxDokoncitPredaj(): void {
        check_ajax_referer('cl_pos_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Nie ste prihlásený');
            return;
        }
        
        // Získanie dát z POST
        $polozky = isset($_POST['polozky']) ? json_decode(stripslashes($_POST['polozky']), true) : [];
        
        if (empty($polozky)) {
            wp_send_json_error('Košík je prázdny');
            return;
        }
        
        global $wpdb;
        
        // Generovanie unikátneho čísla lístka (RRRRMMDD-XXXX)
        $prefix = date('Ymd');
        $suffix = 1;
        
        // Nájdenie posledného čísla lístka s aktuálnym dátumom
        $last_number = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(CAST(SUBSTRING_INDEX(cislo_predaja, '-', -1) AS UNSIGNED)) 
             FROM `{$wpdb->prefix}cl_predaj` 
             WHERE cislo_predaja LIKE %s",
            $prefix . '-%'
        ));
        
        if ($last_number) {
            $suffix = intval($last_number) + 1;
        }
        
        $cislo_listka = sprintf('%s-%04d', $prefix, $suffix);
        
        // Výpočet celkovej sumy
        $celkova_suma = 0;
        foreach ($polozky as $polozka) {
            $celkova_suma += floatval($polozka['cena']) * intval($polozka['pocet']);
        }
        
        try {
            // Začiatok transakcie
            $wpdb->query('START TRANSACTION');
            
            // Vloženie hlavičky predaja
            $wpdb->insert(
                $wpdb->prefix . 'cl_predaj',
                [
                    'cislo_predaja' => $cislo_listka,
                    'predajca_id' => get_current_user_id(),
                    'celkova_suma' => $celkova_suma,
                    'datum_predaja' => current_time('mysql'),
                    'data_listka' => json_encode([
                        'polozky' => $polozky,
                        'hlavicka' => get_option('cl_hlavicka'),
                        'paticka' => get_option('cl_paticka'),
                        'logo_url' => get_option('cl_logo_url')
                    ])
                ]
            );
            
            $predaj_id = $wpdb->insert_id;
            
            // Vloženie položiek predaja
            foreach ($polozky as $polozka) {
                $wpdb->insert(
                    $wpdb->prefix . 'cl_polozky_predaja',
                    [
                        'predaj_id' => $predaj_id,
                        'typ_listka_id' => intval($polozka['id']),
                        'pocet' => intval($polozka['pocet']),
                        'cena_za_kus' => floatval($polozka['cena'])
                    ]
                );
            }
            
            // Vygenerovanie HTML lístka
            $html_listok = $this->generujHtmlListok([
                'cislo_predaja' => $cislo_listka,
                'datum_predaja' => current_time('mysql'),
                'predajca' => wp_get_current_user()->display_name,
                'polozky' => $polozky,
                'celkova_suma' => $celkova_suma
            ]);
            
            // Uloženie HTML lístka
            $subor_cesta = CL_PREDAJ_DIR . 'listok-' . $cislo_listka . '.html';
            file_put_contents($subor_cesta, $html_listok);
            
            // Commit transakcie
            $wpdb->query('COMMIT');
            
            // Logger
            $spravca_log = new \CL\jadro\SpravcaSuborov();
            $spravca_log->zapisDoLogu('PREDAJ', [
                'cislo_predaja' => $cislo_listka,
                'predajca_id' => get_current_user_id(),
                'celkova_suma' => $celkova_suma,
                'pocet_poloziek' => count($polozky)
            ]);
            
            wp_send_json_success([
                'cislo_listka' => $cislo_listka,
                'url_listka' => site_url('/predaj/listok-' . $cislo_listka . '.html')
            ]);
            
        } catch (\Exception $e) {
            // Rollback v prípade chyby
            $wpdb->query('ROLLBACK');
            
            // Logger
            $spravca_log = new \CL\jadro\SpravcaSuborov();
            $spravca_log->zapisDoLogu('PREDAJ_ERROR', [
                'error' => $e->getMessage(),
                'data' => [
                    'polozky' => $polozky,
                    'celkova_suma' => $celkova_suma
                ]
            ]);
            
            wp_send_json_error('Chyba pri ukladaní predaja: ' . $e->getMessage());
        }
    }

    private function generujHtmlListok(array $data): string {
        $generator = new \CL\Listky\Generator();
        return $generator->generujListok($data);
    }

    public function ajaxNacitajPosledne(): void {
        check_ajax_referer('cl_pos_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Nie ste prihlásený');
            return;
        }
        
        global $wpdb;
        
        // Načítanie posledných 3 predajov aktuálneho používateľa
        $predaje = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT p.*, COUNT(pp.id) as pocet_poloziek 
                 FROM `{$wpdb->prefix}cl_predaj` p
                 LEFT JOIN `{$wpdb->prefix}cl_polozky_predaja` pp ON p.id = pp.predaj_id
                 WHERE p.predajca_id = %d AND p.storno = 0
                 GROUP BY p.id
                 ORDER BY p.datum_predaja DESC
                 LIMIT 3",
                get_current_user_id()
            ),
            ARRAY_A
        );
        
        if (!$predaje) {
            wp_send_json_success(['predaje' => []]);
            return;
        }
        
        // Získanie detailov pre každý predaj
        foreach ($predaje as &$predaj) {
            $predaj['polozky'] = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT pp.*, tl.nazov 
                     FROM `{$wpdb->prefix}cl_polozky_predaja` pp
                     LEFT JOIN `{$wpdb->prefix}cl_typy_listkov` tl ON pp.typ_listka_id = tl.id
                     WHERE pp.predaj_id = %d",
                    $predaj['id']
                ),
                ARRAY_A
            );
            
            // Formátovanie dátumu
            $predaj['datum_format'] = wp_date('d.m.Y H:i', strtotime($predaj['datum_predaja']));
        }
        
        wp_send_json_success(['predaje' => $predaje]);
    }

    public function ajaxTlacitListok(): void {
        check_ajax_referer('cl_pos_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Nie ste prihlásený');
            return;
        }
        
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if (!$id) {
            wp_send_json_error('Neplatný identifikátor predaja');
            return;
        }
        
        global $wpdb;
        
        // Získanie predaja
        $predaj = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM `{$wpdb->prefix}cl_predaj` WHERE id = %d",
                $id
            ),
            ARRAY_A
        );
        
        if (!$predaj) {
            wp_send_json_error('Predaj sa nenašiel');
            return;
        }
        
        // URL na HTML lístok
        $url_listka = site_url('/predaj/listok-' . $predaj['cislo_predaja'] . '.html');
        
        wp_send_json_success([
            'cislo_listka' => $predaj['cislo_predaja'],
            'url_listka' => $url_listka
        ]);
    }
}
