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
    private \CL\jadro\SpravcaPrekladov $preklady; // Pridáme property

    public function __construct() {
        $this->databaza = new \CL\jadro\Databaza();
        $this->spravca = \CL\jadro\SpravcaNastaveni::ziskajInstanciu();
        $this->preklady = \CL\jadro\SpravcaPrekladov::ziskajInstanciu(); // Inicializujeme v konštruktore
        add_action('wp_ajax_cl_pridaj_do_kosika', [$this, 'ajaxPridajDoKosika']);
        add_action('wp_ajax_cl_dokoncit_predaj', [$this, 'ajaxDokoncitPredaj']);
        add_action('wp_ajax_cl_nacitaj_predaje', [$this, 'ajaxNacitajPosledne']);
        add_action('wp_ajax_cl_tlacit_listok', [$this, 'ajaxTlacitListok']);
        add_action('wp_enqueue_scripts', [$this, 'pridajAssets']);
        add_action('wp_ajax_cl_get_previous_tickets', [$this, 'ajaxGetPreviousTickets']);
        add_action('wp_ajax_cl_get_ticket_html', [$this, 'ajaxGetTicketHtml']);
        add_action('wp_ajax_cl_reprint_ticket', [$this, 'ajaxReprintTicket']);
        add_action('wp_ajax_cl_close_shift', [$this, 'ajaxCloseShift']);
        add_action('wp_ajax_cl_tlac_denny_report', [$this, 'ajaxTlacDennyReport']);
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
        
        // Nahradzovanie placeholderov - používame len SpravcaPrekladov
        $app_name = $this->spravca->nacitaj('pos_app_name', 'POSka - Cestovné lístky');
        
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
                esc_html($this->preklady->nacitaj('recently_added', 'Naposledy pridané')),
                esc_html($this->preklady->nacitaj('button_cart', 'KOŠÍK')),
                esc_html($this->preklady->nacitaj('cart_title', 'Košík')),
                esc_html($this->preklady->nacitaj('total_sum', 'SPOLU:')),
                esc_html($this->preklady->nacitaj('button_back', 'bbNASPÄŤ')),
                esc_html($this->preklady->nacitaj('button_checkout', 'DOKONČIŤ A TLAČIŤ'))
            ],
            $sablona
        );
        
        wp_localize_script('cl-pos', 'cl_preklady', [
            'button_back' => $this->preklady->nacitaj('button_back', 'aaNASPÄŤ'),
            'button_add' => $this->preklady->nacitaj('button_add', 'PRIDAŤ DO KOŠÍKA'),
            'button_cart' => $this->preklady->nacitaj('button_cart', 'KOŠÍK'),
            'button_checkout' => $this->preklady->nacitaj('button_checkout', 'DOKONČIŤ A TLAČIŤ'),
            'cart_empty' => $this->preklady->nacitaj('cart_empty', 'Košík je prázdny'),
            'cart_title' => $this->preklady->nacitaj('cart_title', 'Košík'),
            'total_sum' => $this->preklady->nacitaj('total_sum', 'SPOLU:'),
            'previous_tickets' => $this->preklady->nacitaj('previous_tickets', 'Predchádzajúce lístky'),
            'menu' => $this->preklady->nacitaj('menu', 'Menu'),
            'view_ticket' => $this->preklady->nacitaj('view_ticket', 'Zobraziť'),
            'print_ticket' => $this->preklady->nacitaj('print_ticket', 'Tlačiť znova'),
            'no_previous_tickets' => $this->preklady->nacitaj('no_previous_tickets', 'Zatiaľ neboli pridané žiadne lístky'),
            'close_shift' => $this->preklady->nacitaj('close_shift', 'Uzatvoriť zmenu'),
            'confirm_close_shift' => $this->preklady->nacitaj('confirm_close_shift', 'Naozaj chcete uzatvoriť zmenu?'),
            'confirm_reprint' => $this->preklady->nacitaj('confirm_reprint', 'Naozaj chcete znovu vytlačiť tento lístok?')
        ]);
        
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
            // Určenie typu lístka
            $cssClass = 'ticket-standard';
            if (floatval($listok->cena) === 0.00) {
                $cssClass = 'ticket-free';
            } elseif (stripos($listok->nazov, 'QR') !== false) {
                $cssClass = 'ticket-qr';
            }
            
            $html .= sprintf(
                '<div class="pos-product cl-ticket-btn %s" data-id="%d" data-nazov="%s" data-cena="%.2f">
                    <div class="pos-product-name">%s</div>
                    <div class="pos-product-price">%.2f €</div>
                </div>',
                $cssClass,
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

    private function generujCisloPredaja(): string {
        // Získanie aktuálneho dátumu vo formáte RRMMDD
        $datum = date('ymd');
        
        // Získanie prvých 3 písmen užívateľského mena
        $current_user = wp_get_current_user();
        $username = substr(strtoupper($current_user->user_login), 0, 3);
        
        // Formát: RRMMDDXXX-0001
        $zaklad = $datum . $username;
        $poradie = 1;
        
        // Nájdeme posledný predaj pre daného používateľa v daný deň
        $posledny = $this->databaza->nacitajPole(
            "SELECT cislo_predaja 
             FROM `{$wpdb->prefix}cl_predaj` 
             WHERE cislo_predaja LIKE %s 
             AND DATE(datum_predaja) = CURDATE()
             ORDER BY cislo_predaja DESC 
             LIMIT 1",
            [$zaklad . '-%']
        );
        
        if (!empty($posledny)) {
            // Extrahujeme posledné poradové číslo
            $casti = explode('-', $posledny[0]['cislo_predaja']);
            $poradie = intval(end($casti)) + 1;
        }
        
        // Vrátime nové číslo predaja vo formáte RRMMDDXXX-0001
        return sprintf('%s-%04d', $zaklad, $poradie);
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
        try {
            error_log("[POS] Začiatok spracovania predaja");
            
            // Kontrola košíka
            $this->kontrolaPredaja($polozky);
            error_log("[POS] Košík skontrolovaný: " . count($polozky) . " položiek");
            
            // Vygenerovanie čísla predaja
            $cislo_predaja = $this->generujCisloPredaja();
            error_log("[POS] Vygenerované číslo predaja: " . $cislo_predaja);
            
            // Zápis do DB
            $predaj_id = $this->vlozHlavickuPredaja($cislo_predaja, $polozky);
            error_log("[POS] Hlavička predaja uložená, ID: " . $predaj_id);
            
            // Vloženie položiek
            $this->vlozPolozkyPredaja($predaj_id, $polozky);
            error_log("[POS] Položky predaja uložené do DB");
            
            // Generovanie HTML lístka
            $html = $this->generujHtmlListok($cislo_predaja, $polozky);
            $html_path = CL_PREDAJ_DIR . "listok-{$cislo_predaja}.html";
            file_put_contents($html_path, $html);
            error_log("[POS] HTML lístok vygenerovaný: " . $html_path);
            
            // Generovanie PDF
            $pdf_path = CL_PREDAJ_DIR . "listok-{$cislo_predaja}.pdf";
            file_put_contents($pdf_path, "Simulovaný PDF lístok pre číslo: $cislo_predaja");
            error_log("[POS] PDF lístok vygenerovaný: " . $pdf_path);
            
            // Odpoveď pre frontend
            wp_send_json_success([
                'cislo_predaja' => $cislo_predaja,
                'url_listka' => site_url("/predaj/listok-{$cislo_predaja}.html"),
                'debug_info' => [
                    'predaj_id' => $predaj_id,
                    'html_path' => $html_path,
                    'pdf_path' => $pdf_path,
                    'timestamp' => current_time('mysql')
                ]
            ]);
            
            error_log("[POS] Predaj úspešne dokončený: " . $cislo_predaja);
            
        } catch (\Exception $e) {
            error_log("[POS] CHYBA: " . $e->getMessage());
            error_log("[POS] Stack trace: " . $e->getTraceAsString());
            wp_send_json_error([
                'message' => $e->getMessage(),
                'debug_info' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]
            ]);
        }
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
        try {
            check_ajax_referer('cl_pos_nonce', 'nonce');
            
            if (!is_user_logged_in()) {
                throw new \Exception('Nie ste prihlásený');
            }

            // Debug log pre prichádzajúce dáta
            error_log('POST data: ' . print_r($_POST, true));

            // Získame a dekódujeme dáta z POST
            $raw_polozky = isset($_POST['polozky']) ? stripslashes($_POST['polozky']) : '';
            error_log('Raw polozky: ' . $raw_polozky);
            
            $polozky = json_decode($raw_polozky, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Chyba pri dekódovaní JSON: ' . json_last_error_msg());
            }

            if (empty($polozky)) {
                throw new \Exception('Košík je prázdny');
            }

            // Generovanie čísla lístka
            $cislo_listka = $this->generujCisloPredaja();
            
            // Výpočet celkovej sumy
            $celkova_suma = 0;
            foreach ($polozky as $polozka) {
                $celkova_suma += floatval($polozka['cena']) * intval($polozka['pocet']);
            }

            global $wpdb;
            $wpdb->query('START TRANSACTION');

            try {
                // Vloženie hlavičky predaja - opravený názov stĺpca z cislo_predaja na cislo_listka
                $success = $wpdb->insert(
                    $wpdb->prefix . 'cl_predaj',
                    [
                        'cislo_listka' => $cislo_listka,  // ZMENENÉ z cislo_predaja na cislo_listka
                        'predajca_id' => get_current_user_id(),
                        'celkova_suma' => $celkova_suma,
                        'datum_predaja' => current_time('mysql'),
                        'data_listka' => json_encode($polozky)
                    ]
                );

                if (!$success) {
                    throw new \Exception('Nepodarilo sa uložiť predaj: ' . $wpdb->last_error);
                }

                $predaj_id = $wpdb->insert_id;

                // Vloženie položiek predaja
                foreach ($polozky as $polozka) {
                    $success = $wpdb->insert(
                        $wpdb->prefix . 'cl_polozky_predaja',
                        [
                            'predaj_id' => $predaj_id,
                            'typ_listka_id' => intval($polozka['id']),
                            'pocet' => intval($polozka['pocet']),
                            'cena_za_kus' => floatval($polozka['cena'])
                        ]
                    );
                    
                    if (!$success) {
                        throw new \Exception('Nepodarilo sa uložiť položku predaja');
                    }
                }

                // Generovanie HTML pomocou šablóny
                $data = [
                    'cislo_listka' => $cislo_listka,
                    'datum' => current_time('d.m.Y'),
                    'cas' => current_time('H:i'),
                    'predajca' => wp_get_current_user()->display_name,
                    'polozky' => $polozky,
                    'celkova_suma' => $celkova_suma
                ];

                $html = $this->generujHtmlListok($data);

                // Uloženie HTML verzie
                $html_path = CL_PREDAJ_HTML_DIR . "listok-{$cislo_listka}.html";
                if (!file_put_contents($html_path, $html)) {
                    throw new \Exception('Nepodarilo sa uložiť HTML lístok');
                }

                // Simulované uloženie PDF
                $pdf_path = CL_PREDAJ_PDF_DIR . "listok-{$cislo_listka}.pdf";
                if (!file_put_contents($pdf_path, "PDF verzia lístka {$cislo_listka}")) {
                    throw new \Exception('Nepodarilo sa uložiť PDF lístok');
                }

                $wpdb->query('COMMIT');

                // Vrátime úspešnú odpoveď s HTML pre tlač
                wp_send_json_success([
                    'cislo_listka' => $cislo_listka,
                    'html' => $html,
                    'predaj_id' => $predaj_id,
                    'celkova_suma' => $celkova_suma
                ]);

            } catch (\Exception $e) {
                $wpdb->query('ROLLBACK');
                throw $e;
            }

        } catch (\Exception $e) {
            error_log("[POS] CHYBA: " . $e->getMessage());
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    private function generujHtmlListok(array $data): string {
        // Načítame šablónu
        $sablona = file_get_contents(CL_PLUGIN_DIR . 'sablony/listok.html');
        if (!$sablona) {
            throw new \Exception('Nepodarilo sa načítať šablónu lístka');
        }

        // Vytvoríme HTML pre položky
        $polozky_html = '';
        foreach ($data['polozky'] as $polozka) {
            if (isset($polozka['pocet']) && $polozka['pocet'] > 0) {
                $suma = number_format($polozka['cena'] * $polozka['pocet'], 2);
                $polozky_html .= sprintf(
                    '<div class="polozka">
                        <div class="polozka-riadok">
                            <span>%s</span>
                            <span>%dx %s€ = %s€</span>
                        </div>
                    </div>',
                    esc_html($polozka['nazov']),
                    intval($polozka['pocet']),
                    number_format($polozka['cena'], 2),
                    $suma
                );
            }
        }

        // Dôležitá zmena - používame všetky premenné zo šablóny
        return str_replace(
            [
                '{cislo_listka}',
                '{datum}',
                '{cas}',
                '{predajca}',
                '{suma}',  // Toto je kľúčové - používame {suma} ako v šablóne
                '{polozky}'
            ],
            [
                esc_html($data['cislo_listka']),
                esc_html($data['datum']),
                esc_html($data['cas']),
                esc_html($data['predajca']),
                number_format($data['celkova_suma'], 2) . ' €',
                $polozky_html
            ],
            $sablona
        );
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

    public function ajaxGetPreviousTickets(): void {
        check_ajax_referer('cl_pos_nonce', 'nonce');
        
        global $wpdb;
        $tickets = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, cislo_predaja as cislo_listka, DATE_FORMAT(datum_predaja, '%d.%m.%Y %H:%i') as datum
                 FROM {$wpdb->prefix}cl_predaj
                 WHERE DATE(datum_predaja) = CURDATE()
                 AND predajca_id = %d
                 ORDER BY datum_predaja DESC
                 LIMIT 10",
                get_current_user_id()
            )
        );
        
        wp_send_json_success(['tickets' => $tickets]);
    }

    public function ajaxGetTicketHtml(): void {
        check_ajax_referer('cl_pos_nonce', 'nonce');
        
        $cislo_listka = sanitize_text_field($_POST['cislo_listka']);
        $html = $this->nacitajHtmlListka($cislo_listka);
        
        wp_send_json_success(['html' => $html]);
    }

    public function ajaxReprintTicket(): void {
        check_ajax_referer('cl_pos_nonce', 'nonce');
        
        $cislo_listka = sanitize_text_field($_POST['cislo_listka']);
        $html = $this->nacitajHtmlListka($cislo_listka);
        
        // Zalogujeme opätovnú tlač
        $spravca = new \CL\jadro\SpravcaSuborov();
        $spravca->zapisDoLogu('REPRINT', [
            'cislo_listka' => $cislo_listka,
            'predajca' => wp_get_current_user()->display_name,
            'datum' => current_time('mysql')
        ]);
        
        wp_send_json_success(['html' => $html]);
    }

    public function ajaxCloseShift(): void {
        check_ajax_referer('cl_pos_nonce', 'nonce');
        
        global $wpdb;
        $report = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT tl.nazov, COUNT(*) as pocet
                 FROM {$wpdb->prefix}cl_polozky_predaja pp
                 JOIN {$wpdb->prefix}cl_predaj p ON pp.predaj_id = p.id
                 JOIN {$wpdb->prefix}cl_typy_listkov tl ON pp.typ_listka_id = tl.id
                 WHERE DATE(p.datum_predaja) = CURDATE()
                 AND p.predajca_id = %d
                 AND p.storno = 0
                 GROUP BY tl.id, tl.nazov
                 ORDER BY tl.nazov",
                get_current_user_id()
            )
        );
        
        $celkova_suma = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(celkova_suma)
                 FROM {$wpdb->prefix}cl_predaj
                 WHERE DATE(datum_predaja) = CURDATE()
                 AND predajca_id = %d
                 AND storno = 0",
                get_current_user_id()
            )
        );
        
        // Generovanie HTML pre tlač
        $html = $this->generujReportHtml($report, $celkova_suma);
        
        wp_send_json_success(['report' => $html]);
    }

    public function ajaxTlacDennyReport(): void {
        if (!wp_verify_nonce($_POST['nonce'], 'cl_pos_nonce')) {
            wp_send_json_error('Neplatný bezpečnostný token');
            return;
        }

        if (!isset($_POST['den'])) {
            wp_send_json_error('Chýba parameter den');
            return;
        }

        $den = sanitize_text_field($_POST['den']);

        // Získanie súhrnných údajov o predaji za daný deň
        $sumar = $this->databaza->nacitaj(
            "SELECT 
                t.nazov,
                SUM(pp.pocet) as pocet,
                SUM(pp.pocet * pp.cena_za_kus) as suma
            FROM `{$wpdb->prefix}cl_predaj` p
            JOIN `{$wpdb->prefix}cl_polozky_predaja` pp ON p.id = pp.predaj_id
            JOIN `{$wpdb->prefix}cl_typy_listkov` t ON pp.typ_listka_id = t.id
            WHERE DATE(p.datum_predaja) = %s
            AND p.storno = 0
            GROUP BY t.id
            ORDER BY t.nazov",
            [$den]
        );

        $celkova_suma = array_sum(array_column($sumar, 'suma'));
        
        // Generovanie HTML pre tlač
        $html = $this->generujReportHtml($sumar, $celkova_suma);

        wp_send_json_success(['html' => $html]);
    }

    private function nacitajHtmlListka(string $cislo_listka): string {
        $subor = CL_PREDAJ_HTML_DIR . 'listok-' . $cislo_listka . '.html';
        if (!file_exists($subor)) {
            throw new \Exception('Lístok sa nenašiel');
        }
        return file_get_contents($subor);
    }

    private function generujReportHtml(array $report, float $celkova_suma): string {
        $html = '<!DOCTYPE html><html><head>';
        $html .= '<style>
            body { font-family: Arial; margin: 20px; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
            .total { font-weight: bold; margin-top: 20px; text-align: right; }
        </style>';
        $html .= '</head><body>';
        $html .= '<h2>Denný report predaja - ' . date('d.m.Y') . '</h2>';
        $html .= '<table><tr><th>Typ lístka</th><th>Počet</th></tr>';
        
        foreach ($report as $item) {
            $html .= "<tr><td>{$item->nazov}</td><td>{$item->pocet}x</td></tr>";
        }
        
        $html .= '</table>';
        $html .= '<div class="total">Celková suma: ' . number_format($celkova_suma, 2) . ' €</div>';
        $html .= '<div>Dátum: ' . date('d.m.Y H:i') . '</div>';
        $html .= '<div>Predajca: ' . wp_get_current_user()->display_name . '</div>';
        $html .= '</body></html>';
        
        return $html;
    }

    private function dokoncitPredaj(array $polozky): array {
        try {
            // Kontrola košíka
            $this->kontrolaPredaja($polozky);

            // Vygenerovanie čísla predaja
            $cislo_predaja = $this->generujCisloPredaja();

            // Vloženie hlavičky predaja
            $predaj_id = $this->vlozHlavickuPredaja($cislo_predaja, $polozky);

            // Vloženie položiek predaja
            $this->vlozPolozkyPredaja($predaj_id, $polozky);

            // Vygenerovanie HTML lístka
            $html = $this->generujHtmlListok($cislo_predaja, $polozky);

            // Generovanie PDF
            $pdf_path = CL_PREDAJ_PDF_DIR . "listok-{$cislo_predaja}.pdf";
            file_put_contents($pdf_path, "Simulovaný PDF lístok pre číslo: $cislo_predaja");

            // Zalogujeme operáciu
            error_log("Vytvorené súbory pre lístok $cislo_predaja:");
            error_log("HTML: $html_path");
            error_log("PDF: $pdf_path");

            // Vrátime údaje o predaji
            return [
                'cislo_predaja' => $cislo_predaja,
                'url_listka' => CL_PLUGIN_URL . "includes/predaj/listok-{$cislo_predaja}.html",
                'poznamka' => "TESTOVACÍ REŽIM: Lístok bol uložený ako HTML a PDF (bez skutočnej tlače)",
                'success' => true
            ];
        } catch (\Exception $e) {
            error_log("Chyba pri dokončení predaja: " . $e->getMessage());
            throw $e;
        }
    }
}
