<?php
declare(strict_types=1);

namespace CL\Admin;

/**
 * Správa nastavení pluginu
 * 
 * Zabezpečuje:
 * - Registráciu nastavení vo WordPress admin
 * - Ukladanie nastavení do vlastnej DB tabuľky
 * - Zobrazenie formulárov pre nastavenia
 * - Live náhľad nastavení
 */

class Nastavenia {
    private \CL\jadro\SpravcaNastaveni $spravca;
    private \CL\jadro\SpravcaPrekladov $preklady;

    public function __construct() {
        $this->spravca = \CL\jadro\SpravcaNastaveni::ziskajInstanciu();
        $this->preklady = \CL\jadro\SpravcaPrekladov::ziskajInstanciu();
        add_action('admin_init', [$this, 'registrujNastavenia']);
        add_action('admin_menu', [$this, 'pridajStrankyNastaveni']);
    }

    public function pridajStrankyNastaveni(): void {
        // Pridáme podstránku pre systémové nastavenia
        add_submenu_page(
            'cl-nastavenia', // Parent slug
            'Systémové nastavenia', // Page title
            'Systémové nastavenia', // Menu title
            'manage_options', // Capability
            'cl-system-settings', // Menu slug
            [$this, 'zobrazSystemoveNastavenia'] // Callback function
        );
    }

    public function zobrazSystemoveNastavenia(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Nedostatočné oprávnenia');
        }
        ?>
        <div class="wrap">
            <h1>Systémové nastavenia</h1>
            <form method="post" action="options.php">
                <?php 
                settings_fields('cl_system_settings');
                do_settings_sections('cl_system_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function pridajMedia($hook): void {
        if ($hook !== 'toplevel_page_cl-nastavenia') {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_editor();
        
        // Pridáme vlastný plugin pre TinyMCE a vlastné štýly
        wp_enqueue_script(
            'cl-tinymce-plugin',
            CL_ASSETS_URL . 'js/tinymce-plugin.js',
            ['jquery'],
            CL_VERSION,
            true
        );

        // Pridáme vlastné nastavenia pre TinyMCE
        add_filter('tiny_mce_before_init', function($settings) {
            // Povolíme všetky CSS vlastnosti
            $settings['valid_styles'] = '*[*]';
            
            // Zachováme formátovanie
            $settings['verify_html'] = false;
            $settings['cleanup'] = false;
            $settings['forced_root_block'] = false;
            
            // Povolíme všetky HTML atribúty
            $settings['extended_valid_elements'] = '*[*]';
            
            // Zabránime automatickému pridávaniu tagov
            $settings['remove_linebreaks'] = false;
            
            return $settings;
        });
    }

    public function zobrazStranku(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Nedostatočné oprávnenia');
        }

        $spravca = $this->spravca; // Sprístupníme $spravca pre template
        include CL_INCLUDES_DIR . 'admin/pohlady/nastavenia-formular.php';
    }

    public function registrujNastavenia(): void {
        register_setting('cl_nastavenia', 'cl_nastavenia', [
            'sanitize_callback' => [$this, 'sanitizeNastavenia']
        ]);
        
        // 1. Nastavenia lístka
        add_settings_section('cl_sekcia_listok', 'Nastavenia vzhľadu lístka', [$this, 'zobrazSekciuListok'], 'cl-nastavenia');
        
        $nastavenia_listka = [
            'logo' => ['Logo lístka', 'zobrazInputLogo'],
            'hlavicka' => ['Hlavička lístka', 'zobrazInputHlavicka'],
            'paticka' => ['Pätička lístka', 'zobrazInputPaticka'],
            'font_velkost' => ['Veľkosť písma', 'zobrazInputFontVelkost'],
            'logo_velkost' => ['Veľkosť loga', 'zobrazInputLogoVelkost'],
            'pismo' => ['Typ písma', 'zobrazInputPismo'],
            'zarovnanie' => ['Zarovnanie textu', 'zobrazInputZarovnanie'],
            'zalamovanie' => ['Zalamovanie textu', 'zobrazInputZalamovanie']
        ];

        // 2. Nastavenia predaja
        $nastavenia_predaja = [
            'sirka_tlace' => ['Šírka tlačiarne', 'zobrazInputSirkaTlace'],
            'predvolena_tlaciaren' => ['Predvolená tlačiareň', 'zobrazInputPredvolenaTlaciaren'],
            'pocet_kopii' => ['Počet kópií', 'zobrazInputPocetKopii'],
            'format_cisla' => ['Formát čísla lístka', 'zobrazInputFormatCisla'],
            'auto_tlac' => ['Automatická tlač', 'zobrazInputAutoTlac'],
            'cas_stornovania' => ['Časový limit na stornovanie', 'zobrazInputCasStornovania'],
            'zobrazit_historiu' => ['Zobraziť históriu predaja', 'zobrazInputZobrazitHistoriu'],
            'pocet_v_historii' => ['Počet lístkov v histórii', 'zobrazInputPocetVHistorii']
        ];

        // 3. Nastavenia databáz
        $nastavenia_databaz = [
            'db_backup_host' => ['Adresa záložnej DB', 'zobrazInputDbHost'],
            'db_backup_name' => ['Názov záložnej DB', 'zobrazInputDbName'],
            'db_backup_user' => ['Používateľ záložnej DB', 'zobrazInputDbUser'],
            'db_backup_pass' => ['Heslo záložnej DB', 'zobrazInputDbPass'],
            'db_sync_interval' => ['Interval synchronizácie', 'zobrazInputDbSyncInterval'],
            'db_auto_sync' => ['Automatická synchronizácia', 'zobrazInputDbAutoSync']
        ];

        // 4. Systémové nastavenia
        $nastavenia_system = [
            'debug_mode' => ['Debug mód', 'zobrazInputDebugMode'],
            'log_level' => ['Úroveň logovania', 'zobrazInputLogLevel'],
            'cache_lifetime' => ['Životnosť cache', 'zobrazInputCacheLifetime'],
            'session_timeout' => ['Timeout sedenia', 'zobrazInputSessionTimeout'],
            'max_pokusov' => ['Max. počet pokusov', 'zobrazInputMaxPokusov'],
            // Pridáme nové polia pre texty tlačidiel
            'button_back' => ['Text tlačidla "Späť"', 'zobrazInputButtonBack'],
            'button_add' => ['Text tlačidla "Pridať do košíka"', 'zobrazInputButtonAdd'],
            'button_cart' => ['Text tlačidla "Košík"', 'zobrazInputButtonCart'],
            'button_checkout' => ['Text tlačidla "Dokončiť"', 'zobrazInputButtonCheckout'],
            'cart_empty' => ['Text prázdneho košíka', 'zobrazInputCartEmpty'],
            'cart_title' => ['Nadpis košíka', 'zobrazInputCartTitle']
        ];

        // POS Nastavenia
        $nastavenia_pos = [
            'pos_layout' => ['Rozloženie tlačidiel', 'zobrazInputPosLayout'],
            'pos_columns' => ['Počet stĺpcov', 'zobrazInputPosColumns'],
            'pos_width' => ['Šírka obrazovky', 'zobrazInputPosWidth'],     // Pridané
            'pos_height' => ['Výška obrazovky', 'zobrazInputPosHeight'],   // Pridané
            'pos_button_size' => ['Veľkosť tlačidiel', 'zobrazInputPosButtonSize'],
            'pos_button_style' => ['Štýl tlačidiel', 'zobrazInputPosButtonStyle'],
            'pos_colors' => ['Farebná schéma', 'zobrazInputPosColors'],
            'pos_font_size' => ['Veľkosť písma', 'zobrazInputPosFontSize'],
            'pos_show_history' => ['Zobraziť históriu', 'zobrazInputPosShowHistory'],
            'pos_history_count' => ['Počet záznamov v histórii', 'zobrazInputPosHistoryCount']
        ];

        // Registrácia sekcií a polí
        $sekcie = [
            'listok' => [$nastavenia_listka, 'Nastavenia vzhľadu a obsahu lístka'],
            'predaj' => [$nastavenia_predaja, 'Nastavenia predaja a tlače'],
            'databazy' => [$nastavenia_databaz, 'Nastavenia záložnej databázy'],
            'system' => [$nastavenia_system, 'Systémové nastavenia']
        ];

        foreach ($sekcie as $id => $config) {
            add_settings_section(
                "cl_sekcia_$id",
                $config[1],
                [$this, "zobrazSekciu$id"],
                'cl-nastavenia'
            );

            foreach ($config[0] as $pole_id => $pole_config) {
                add_settings_field(
                    "cl_$pole_id",
                    $pole_config[0],
                    [$this, $pole_config[1]],
                    'cl-nastavenia',
                    "cl_sekcia_$id"
                );
            }
        }

        // Registrácia sekcií POS
        add_settings_section(
            'cl_sekcia_pos',
            'Nastavenia POS terminálu',
            [$this, 'zobrazSekciuPos'],
            'cl-nastavenia'
        );

        foreach ($nastavenia_pos as $id => $config) {
            add_settings_field(
                "cl_$id",
                $config[0],
                [$this, $config[1]],
                'cl-nastavenia',
                'cl_sekcia_pos'
            );
        }

        // Texty tlačidiel
        register_setting('cl_system_settings', 'cl_button_back');
        register_setting('cl_system_settings', 'cl_button_add_to_cart');
        register_setting('cl_system_settings', 'cl_button_checkout');

        // Registrácia systémových nastavení

        add_settings_section(
            'cl_system_buttons_section',
            'Texty tlačidiel',
            function() {
                echo '<p>Tu môžete upraviť texty tlačidiel používané v aplikácii.</p>';
            },
            'cl-nastavenia'  // Zmeníme z 'cl_system_settings' na 'cl-nastavenia'
        );

        // Registrujeme polia pre texty tlačidiel v sekcii system
        add_settings_field(
            'cl_button_back',
            'Tlačidlo "Späť"',
            [$this, 'zobrazInputButtonBack'],
            'cl-nastavenia',
            'cl_sekcia_system'
        );

        add_settings_field(
            'cl_button_add_to_cart',
            'Tlačidlo "Do košíka"',
            [$this, 'zobrazInputButtonAddToCart'],
            'cl-nastavenia',
            'cl_sekcia_system'
        );

        add_settings_field(
            'cl_button_checkout',
            'Tlačidlo "Dokončiť"',
            [$this, 'zobrazInputButtonCheckout'],
            'cl-nastavenia',
            'cl_sekcia_system'
        );

        // Registrujeme nastavenia
        register_setting('cl_nastavenia', 'cl_button_back');
        register_setting('cl_nastavenia', 'cl_button_add_to_cart');
        register_setting('cl_nastavenia', 'cl_button_checkout');

        // Systémové nastavenia
        add_settings_section(
            'cl_sekcia_system',
            'Systémové nastavenia',
            [$this, 'zobrazSekciuSystem'],
            'cl-nastavenia'
        );

        // Registrácia polí pre preklady
        $preklady = [
            'button_back' => 'Text tlačidla "Späť"',
            'button_add' => 'Text tlačidla "Pridať do košíka"',
            'button_cart' => 'Text tlačidla "Košík"',
            'button_checkout' => 'Text tlačidla "Dokončiť"',
            'cart_empty' => 'Text prázdneho košíka',
            'cart_title' => 'Nadpis košíka'
        ];

        foreach ($preklady as $kluc => $popis) {
            add_settings_field(
                'cl_' . $kluc,
                $popis,
                [$this, 'zobrazInputPreklad'],
                'cl-nastavenia',
                'cl_sekcia_system',
                ['kluc' => $kluc]
            );
        }
    }

    /**
     * Upravíme sanitizeNastavenia aby ukladal nastavenia s prefixom podľa záložky
     */
    public function sanitizeNastavenia($input) {
        if (empty($input) || !is_array($input)) {
            return false;
        }

        $active_tab = $_GET['tab'] ?? 'listok';
        
        try {
            // Špeciálna logika pre uloženie šablóny lístka
            if (isset($input['sablona_listka'])) {
                $this->spravca->uloz('listok_sablona', $input['sablona_listka']);
                unset($input['sablona_listka']);
            }

            // Uložíme každé nastavenie samostatne, už bez prefixu
            foreach ($input as $key => $value) {
                // Špeciálna validácia pre POS nastavenia
                if (strpos($key, 'pos_') === 0) {
                    if (in_array($key, ['pos_width', 'pos_height', 'pos_columns'])) {
                        $value = absint($value);
                    }
                }
                
                // Uložíme hodnotu bez prefixu záložky
                $this->spravca->uloz($key, $value);
            }

            // Uložíme preklady do novej tabuľky
            if (isset($input['button_back'])) {
                $this->preklady->uloz('button_back', $input['button_back']);
            }
            if (isset($input['button_add'])) {
                $this->preklady->uloz('button_add', $input['button_add']);
            }
            if (isset($input['button_cart'])) {
                $this->preklady->uloz('button_cart', $input['button_cart']);
            }
            if (isset($input['button_checkout'])) {
                $this->preklady->uloz('button_checkout', $input['button_checkout']);
            }
            if (isset($input['cart_empty'])) {
                $this->preklady->uloz('cart_empty', $input['cart_empty']);
            }
            if (isset($input['cart_title'])) {
                $this->preklady->uloz('cart_title', $input['cart_title']);
            }
            
            // Spracovanie prekladov
            if (isset($input['preklady']) && is_array($input['preklady'])) {
                foreach ($input['preklady'] as $kluc => $hodnota) {
                    $this->preklady->uloz($kluc, $hodnota);
                }
                unset($input['preklady']);
            }

            return true;
        } catch (\Exception $e) {
            error_log('CL Plugin - Chyba pri ukladaní nastavení: ' . $e->getMessage());
            return false;
        }
    }

    // Callback metódy pre sekcie
    public function zobrazSekciuListok(): void {
        echo '<p>Nastavenia pre vzhľad a obsah tlačených lístkov.</p>';
    }

    public function zobrazSekciuPredaj(): void {
        echo '<p>Nastavenia pre proces predaja a tlače lístkov.</p>';
    }

    public function zobrazSekciuZalohy(): void {
        echo '<p>Nastavenia automatického zálohovania databázy a súborov.</p>';
    }

    public function zobrazSekciuNotifikacie(): void {
        echo '<p>Nastavenia pre systémové notifikácie a upozornenia.</p>';
    }

    public function zobrazSekciuStatistiky(): void {
        echo '<p>Nastavenia pre štatistiky.</p>';
    }

    public function zobrazSekciuSystem(): void {
        echo '<p>Systémové nastavenia.</p>';
    }

    public function zobrazSekciuDatabazy(): void {
        echo '<p>Nastavenia záložnej databázy a synchronizácie. Pri zmene nastavení sa automaticky otestuje pripojenie.</p>';
    }

    public function zobrazSekciuPos(): void {
        echo '<p>Nastavenia vzhľadu a funkcionality POS terminálu.</p>';
    }

    // Callback metódy pre polia
    public function zobrazInputLogo(): void {
        $spravca = \CL\jadro\SpravcaNastaveni::ziskajInstanciu();
        $logo_url = $spravca->nacitaj('listok_logo_url', '');
        ?>
        <input type="text" id="cl_logo_url" name="cl_nastavenia[logo_url]" value="<?php echo esc_attr($logo_url); ?>" class="regular-text">
        <button type="button" class="button" id="cl_upload_logo">Vybrať obrázok</button>
        <p class="description">
            Odporúčané rozmery: 180 x 60 pixelov (maximálna šírka 50mm pre 54mm tlačiareň).<br>
            Podporované formáty: PNG, JPEG (preferované PNG s priehľadnosťou).<br>
            Pre najlepšie výsledky použite čiernobiele logo.
        </p>
        <?php
    }

    public function zobrazInputHlavicka(): void {
        $spravca = \CL\jadro\SpravcaNastaveni::ziskajInstanciu();
        $hlavicka = $spravca->nacitaj('listok_hlavicka', '');
        ?>
        <textarea name="cl_nastavenia[hlavicka]" rows="3" class="large-text"><?php echo esc_textarea($hlavicka); ?></textarea>
        <p class="description">
            Text v hlavičke lístka. Môžete použiť HTML tagy &lt;b&gt;, &lt;i&gt;.<br>
            Dostupné premenné: {datum}, {cas}, {predajca}<br>
            Max. 4 riadky pre zachovanie čitateľnosti lístka.
        </p>
        <?php
    }

    public function zobrazInputPaticka(): void {
        $spravca = \CL\jadro\SpravcaNastaveni::ziskajInstanciu();
        $paticka = $spravca->nacitaj('listok_paticka', '');
        ?>
        <textarea name="cl_nastavenia[paticka]" rows="3" class="large-text"><?php echo esc_textarea($paticka); ?></textarea>
        <p class="description">
            Text v pätičke lístka. Môžete použiť HTML tagy &lt;b&gt;, &lt;i&gt;.<br>
            Dostupné premenné: {datum}, {cas}, {predajca}<br>
            Max. 4 riadky pre zachovanie čitateľnosti lístka.
        </p>
        <?php
    }

    public function zobrazInputFormatCisla(): void {
        $spravca = \CL\jadro\SpravcaNastaveni::ziskajInstanciu();
        $format = $spravca->nacitaj('predaj_format_cisla', 'RRRRMMDD-XXXX');
        ?>
        <input type="text" name="cl_nastavenia[format_cisla]" value="<?php echo esc_attr($format); ?>" class="regular-text">
        <p class="description">
            Formát čísla lístka. Predvolený formát: RRRRMMDD-XXXX<br>
            R = rok, M = mesiac, D = deň, X = poradové číslo
        </p>
        <?php
    }

    public function zobrazInputAutoTlac(): void {
        $spravca = \CL\jadro\SpravcaNastaveni::ziskajInstanciu();
        $auto_tlac = $spravca->nacitaj('predaj_auto_tlac', '1');
        ?>
        <label>
            <input type="checkbox" name="cl_nastavenia[auto_tlac]" value="1" <?php checked('1', $auto_tlac); ?>>
            Automaticky otvoriť okno tlače po dokončení predaja
        </label>
        <p class="description">
            Pri vypnutí sa zobrazí len náhľad lístka s možnosťou manuálnej tlače.
        </p>
        <?php
    }

    public function zobrazInputStatistikyCas(): void {
        $spravca = \CL\jadro\SpravcaNastaveni::ziskajInstanciu();
        $cas = $spravca->nacitaj('statistiky_cas', '23:00');
        echo '<input type="time" name="cl_nastavenia[statistiky_cas]" value="' . esc_attr($cas) . '" />';
        echo '<p class="description">Čas kedy sa majú generovať denné štatistiky</p>';
    }

    public function zobrazInputStatistikyPriecinok(): void {
        $spravca = \CL\jadro\SpravcaNastaveni::ziskajInstanciu();
        $priecinok = $spravca->nacitaj('statistiky_priecinok', WP_CONTENT_DIR . '/statistiky');
        echo '<input type="text" name="cl_nastavenia[statistiky_priecinok]" value="' . esc_attr($priecinok) . '" class="regular-text" />';
        echo '<p class="description">Absolútna cesta k priečinku kde sa majú ukladať štatistiky</p>';
    }

    public function zobrazInputIntervalZalohy(): void {
        $spravca = \CL\jadro\SpravcaNastaveni::ziskajInstanciu();
        $interval = $spravca->nacitaj('interval_zalohy', 'daily');
        ?>
        <select name="cl_nastavenia[interval_zalohy]">
            <option value="hourly" <?php selected($interval, 'hourly'); ?>>Hodinovo</option>
            <option value="daily" <?php selected($interval, 'daily'); ?>>Denne</option>
            <option value="weekly" <?php selected($interval, 'weekly'); ?>>Týždenne</option>
            <option value="monthly" <?php selected($interval, 'monthly'); ?>>Mesačne</option>
        </select>
        <p class="description">
            Interval automatického zálohovania.
        </p>
        <?php
    }

    public function zobrazInputPocetZaloh(): void {
        $spravca = \CL\jadro\SpravcaNastaveni::ziskajInstanciu();
        $pocet = $spravca->nacitaj('pocet_zaloh', '5');
        ?>
        <input type="number" name="cl_nastavenia[pocet_zaloh]" value="<?php echo esc_attr($pocet); ?>" class="small-text">
        <p class="description">
            Počet uchovávaných záloh. Staršie zálohy budú automaticky odstránené.
        </p>
        <?php
    }

    public function zobrazInputEmailNotifikacie(): void {
        $spravca = \CL\jadro\SpravcaNastaveni::ziskajInstanciu();
        $email = $spravca->nacitaj('email_notifikacie', '');
        ?>
        <input type="email" name="cl_nastavenia[email_notifikacie]" value="<?php echo esc_attr($email); ?>" class="regular-text">
        <p class="description">
            E-mailová adresa pre zasielanie notifikácií.
        </p>
        <?php
    }

    public function zobrazInputSirkaTlace(): void {
        $spravca = \CL\jadro\SpravcaNastaveni::ziskajInstanciu();
        $sirka = $spravca->nacitaj('predaj_sirka_tlace', '54');
        ?>
        <select name="cl_nastavenia[sirka_tlace]">
            <option value="54" <?php selected($sirka, '54'); ?>>54mm (štandardná)</option>
            <option value="80" <?php selected($sirka, '80'); ?>>80mm</option>
        </select>
        <p class="description">
            Vyberte šírku vašej tlačiarne. Toto nastavenie ovplyvní formátovanie lístka.
        </p>
        <?php
    }

    public function zobrazInputDebugMode(): void {
        $spravca = \CL\jadro\SpravcaNastaveni::ziskajInstanciu();
        $debug = $spravca->nacitaj('system_debug_mode', '0');
        ?>
        <label>
            <input type="checkbox" name="cl_nastavenia[debug_mode]" value="1" <?php checked($debug, '1'); ?>>
            Povoliť rozšírené logovanie
        </label>
        <p class="description">
            V debug móde sa budú zapisovať detailné informácie o operáciách do logov.
            Používajte len pri riešení problémov.
        </p>
        <?php
    }

    public function zobrazInputCacheLifetime(): void {
        $spravca = \CL\jadro\SpravcaNastaveni::ziskajInstanciu();
        $lifetime = $spravca->nacitaj('system_cache_lifetime', '3600');
        ?>
        <input type="number" name="cl_nastavenia[cache_lifetime]" value="<?php echo esc_attr($lifetime); ?>" min="300" step="300">
        <p class="description">
            Doba v sekundách, po ktorú sa majú uchovávať dočasné dáta v cache.
            Minimálne 300 sekúnd (5 minút).
        </p>
        <?php
    }

    public function zobrazInputDbHost(): void {
        $spravca = \CL\jadro\SpravcaNastaveni::ziskajInstanciu();
        $hodnota = $spravca->nacitaj('databazy_db_backup_host', DB_HOST);
        ?>
        <input type="text" name="cl_nastavenia[db_backup_host]" value="<?php echo esc_attr($hodnota); ?>" class="regular-text">
        <p class="description">Adresa servera záložnej databázy (napr. localhost alebo IP adresa)</p>
        <?php
    }

    public function zobrazInputDbName(): void {
        $spravca = \CL\jadro\SpravcaNastaveni::ziskajInstanciu();
        $hodnota = $spravca->nacitaj('databazy_db_backup_name', DB_NAME . '_backup');
        ?>
        <input type="text" name="cl_nastavenia[db_backup_name]" value="<?php echo esc_attr($hodnota); ?>" class="regular-text">
        <p class="description">Názov záložnej databázy</p>
        <?php
    }

    public function zobrazInputDbUser(): void {
        $spravca = \CL\jadro\SpravcaNastaveni::ziskajInstanciu();
        $hodnota = $spravca->nacitaj('databazy_db_backup_user', DB_USER);
        ?>
        <input type="text" name="cl_nastavenia[db_backup_user]" value="<?php echo esc_attr($hodnota); ?>" class="regular-text">
        <p class="description">Používateľské meno pre prístup k záložnej databáze</p>
        <?php
    }

    public function zobrazInputDbPass(): void {
        $spravca = \CL\jadro\SpravcaNastaveni::ziskajInstanciu();
        $hodnota = $spravca->nacitaj('databazy_db_backup_pass', '');
        ?>
        <input type="password" name="cl_nastavenia[db_backup_pass]" value="<?php echo esc_attr($hodnota); ?>" class="regular-text">
        <p class="description">Heslo pre prístup k záložnej databáze</p>
        <?php
    }

    public function zobrazInputDbSyncInterval(): void {
        $spravca = \CL\jadro\SpravcaNastaveni::ziskajInstanciu();
        $interval = $spravca->nacitaj('databazy_db_sync_interval', '300');
        ?>
        <select name="cl_nastavenia[db_sync_interval]">
            <option value="60" <?php selected($interval, '60'); ?>>Každú minútu</option>
            <option value="300" <?php selected($interval, '300'); ?>>Každých 5 minút</option>
            <option value="900" <?php selected($interval, '900'); ?>>Každých 15 minút</option>
            <option value="1800" <?php selected($interval, '1800'); ?>>Každých 30 minút</option>
            <option value="3600" <?php selected($interval, '3600'); ?>>Každú hodinu</option>
        </select>
        <p class="description">Ako často sa má kontrolovať synchronizácia databáz</p>
        <?php
    }

    // Nové callback metódy pre rozšírené nastavenia
    public function zobrazInputFontVelkost(): void {
        $spravca = \CL\jadro\SpravcaNastaveni::ziskajInstanciu();
        $velkost = $spravca->nacitaj('listok_font_velkost', '12');
        ?>
        <select name="cl_nastavenia[font_velkost]">
            <option value="10" <?php selected($velkost, '10'); ?>>10px - Malé</option>
            <option value="12" <?php selected($velkost, '12'); ?>>12px - Štandardné</option>
            <option value="14" <?php selected($velkost, '14'); ?>>14px - Väčšie</option>
        </select>
        <p class="description">Základná veľkosť písma na lístku. Nadpisy budú primerane väčšie.</p>
        <?php
    }

    public function zobrazInputLogoVelkost(): void {
        $spravca = \CL\jadro\SpravcaNastaveni::ziskajInstanciu();
        $velkost = $spravca->nacitaj('listok_logo_velkost', '50');
        ?>
        <input type="number" name="cl_nastavenia[logo_velkost]" value="<?php echo esc_attr($velkost); ?>" class="small-text">
        <p class="description">Maximálna šírka loga v mm. Odporúčaná hodnota: 50mm pre 54mm tlačiareň.</p>
        <?php
    }

    public function zobrazInputPismo(): void {
        $spravca = \CL\jadro\SpravcaNastaveni::ziskajInstanciu();
        $pismo = $spravca->nacitaj('listok_pismo', 'Arial');
        ?>
        <select name="cl_nastavenia[pismo]">
            <option value="Arial" <?php selected($pismo, 'Arial'); ?>>Arial</option>
            <option value="Courier" <?php selected($pismo, 'Courier'); ?>>Courier</option>
            <option value="Times" <?php selected($pismo, 'Times'); ?>>Times New Roman</option>
        </select>
        <p class="description">Vyberte typ písma pre tlačené lístky.</p>
        <?php
    }

    public function zobrazInputZarovnanie(): void {
        $spravca = \CL\jadro\SpravcaNastaveni::ziskajInstanciu();
        $zarovnanie = $spravca->nacitaj('listok_zarovnanie', 'left');
        ?>
        <select name="cl_nastavenia[zarovnanie]">
            <option value="left" <?php selected($zarovnanie, 'left'); ?>>Vľavo</option>
            <option value="center" <?php selected($zarovnanie, 'center'); ?>>V strede</option>
            <option value="right" <?php selected($zarovnanie, 'right'); ?>>Vpravo</option>
        </select>
        <p class="description">Zarovnanie textu na lístku.</p>
        <?php
    }

    public function zobrazInputZalamovanie(): void {
        $spravca = \CL\jadro\SpravcaNastaveni::ziskajInstanciu();
        $zalamovanie = $spravca->nacitaj('listok_zalamovanie', '1');
        ?>
        <label>
            <input type="checkbox" name="cl_nastavenia[zalamovanie]" value="1" <?php checked($zalamovanie, '1'); ?>>
            Povoliť zalamovanie textu
        </label>
        <p class="description">Pri povolení sa text automaticky zalomí na ďalší riadok, ak presiahne šírku tlačiarne.</p>
        <?php
    }

    public function zobrazInputPredvolenaTlaciaren(): void {
        $spravca = \CL\jadro\SpravcaNastaveni::ziskajInstanciu();
        $tlaciaren = $spravca->nacitaj('predaj_predvolena_tlaciaren', '');
        ?>
        <input type="text" name="cl_nastavenia[predvolena_tlaciaren]" value="<?php echo esc_attr($tlaciaren); ?>" class="regular-text">
        <p class="description">Názov predvolenej tlačiarne pre tlač lístkov.</p>
        <?php
    }

    public function zobrazInputPocetKopii(): void {
        $spravca = \CL\jadro\SpravcaNastaveni::ziskajInstanciu();
        $kopie = $spravca->nacitaj('predaj_pocet_kopii', '1');
        ?>
        <input type="number" name="cl_nastavenia[pocet_kopii]" value="<?php echo esc_attr($kopie); ?>" class="small-text">
        <p class="description">Počet kópií lístka, ktoré sa majú vytlačiť.</p>
        <?php
    }

    public function zobrazInputCasStornovania(): void {
        $spravca = \CL\jadro\SpravcaNastaveni::ziskajInstanciu();
        $cas = $spravca->nacitaj('predaj_cas_stornovania', '300');
        ?>
        <input type="number" name="cl_nastavenia[cas_stornovania]" value="<?php echo esc_attr($cas); ?>" class="small-text">
        <p class="description">Časový limit na stornovanie lístka v sekundách. Predvolená hodnota: 300 sekúnd (5 minút).</p>
        <?php
    }

    public function zobrazInputDbAutoSync(): void {
        $spravca = \CL\jadro\SpravcaNastaveni::ziskajInstanciu();
        $auto_sync = $spravca->nacitaj('databazy_db_auto_sync', '1');
        ?>
        <label>
            <input type="checkbox" name="cl_nastavenia[db_auto_sync]" value="1" <?php checked($auto_sync, '1'); ?>>
            Povoliť automatickú synchronizáciu databáz
        </label>
        <p class="description">Pri povolení sa databázy budú automaticky synchronizovať podľa nastaveného intervalu.</p>
        <?php
    }

    public function zobrazInputPosLayout(): void {
        $spravca = \CL\jadro\SpravcaNastaveni::ziskajInstanciu();
        $layout = $spravca->nacitaj('pos_pos_layout', 'grid');
        ?>
        <select name="cl_nastavenia[layout]" id="pos_layout">
            <option value="grid" <?php selected($layout, 'grid'); ?>>Mriežka</option>
            <option value="list" <?php selected($layout, 'list'); ?>>Zoznam</option>
            <option value="compact" <?php selected($layout, 'compact'); ?>>Kompaktný</option>
        </select>
        <p class="description">Rozloženie tlačidiel v POS termináli</p>
        <?php
    }

    public function zobrazInputPosColumns(): void {
        $spravca = \CL\jadro\SpravcaNastaveni::ziskajInstanciu();
        $columns = $spravca->nacitaj('pos_pos_columns', 4);
        ?>
        <input type="number" name="cl_nastavenia[pos_columns]" value="<?php echo esc_attr($columns); ?>" min="2" max="6" step="1">
        <p class="description">Počet stĺpcov v mriežke (2-6)</p>
        <?php
    }

    public function zobrazInputPosButtonSize(): void {
        $spravca = \CL\jadro\SpravcaNastaveni::ziskajInstanciu();
        $size = $spravca->nacitaj('pos_pos_button_size', 'medium');
        ?>
        <select name="cl_nastavenia[pos_button_size]">
            <option value="small" <?php selected($size, 'small'); ?>>Malé</option>
            <option value="medium" <?php selected($size, 'medium'); ?>>Stredné</option>
            <option value="large" <?php selected($size, 'large'); ?>>Veľké</option>
        </select>
        <p class="description">Veľkosť tlačidiel v POS termináli</p>
        <?php
    }

    public function zobrazInputPosWidth(): void {
        $spravca = \CL\jadro\SpravcaNastaveni::ziskajInstanciu();
        $width = $spravca->nacitaj('pos_pos_width', '375');
        ?>
        <input type="hidden" name="cl_nastavenia[pos_width]" id="pos_width" value="<?php echo esc_attr($width); ?>">
        <?php
    }

    public function zobrazInputPosHeight(): void {
        $spravca = \CL\jadro\SpravcaNastaveni::ziskajInstanciu();
        $height = $spravca->nacitaj('pos_pos_height', '667');
        ?>
        <input type="hidden" name="cl_nastavenia[pos_height]" id="pos_height" value="<?php echo esc_attr($height); ?>">
        <?php
    }

    public function registrujTlacitka($buttons) {
        array_push($buttons, 'cl_variables');
        return $buttons;
    }

    public function registrujPlugin($plugins) {
        $plugins['cl_variables'] = CL_ASSETS_URL . 'js/tinymce-plugin.js';
        return $plugins;
    }

    public function zobrazInputButtonBack(): void {
        $text = $this->preklady->nacitaj('button_back', 'NASPÄŤ');
        ?>
        <input type="text" 
               name="cl_nastavenia[button_back]" 
               value="<?php echo esc_attr($text); ?>" 
               class="regular-text">
        <p class="description">Text tlačidla pre návrat späť</p>
        <?php
    }

    public function zobrazInputButtonAddToCart(): void {
        $text = $this->preklady->nacitaj('button_add_to_cart', 'DO KOŠÍKA');
        ?>
        <input type="text" name="cl_nastavenia[button_add_to_cart]" value="<?php echo esc_attr($text); ?>" class="regular-text">
        <?php
    }

    public function zobrazInputButtonCheckout(): void {
        $text = $this->preklady->nacitaj('button_checkout', 'DOKONČIŤ');
        ?>
        <input type="text" name="cl_nastavenia[button_checkout]" value="<?php echo esc_attr($text); ?>" class="regular-text">
        <?php
    }

    public function zobrazInputButtonAdd(): void {
        $text = $this->preklady->nacitaj('button_add', 'PRIDAŤ DO KOŠÍKA');
        ?>
        <input type="text" 
               name="cl_nastavenia[button_add]" 
               value="<?php echo esc_attr($text); ?>" 
               class="regular-text">
        <p class="description">Text tlačidla pre pridanie položky do košíka</p>
        <?php
    }

    public function zobrazInputButtonCart(): void {
        $text = $this->preklady->nacitaj('button_cart', 'KOŠÍK');
        ?>
        <input type="text" 
               name="cl_nastavenia[button_cart]" 
               value="<?php echo esc_attr($text); ?>" 
               class="regular-text">
        <p class="description">Text tlačidla pre zobrazenie košíka</p>
        <?php
    }

    public function zobrazInputCartEmpty(): void {
        $text = $this->preklady->nacitaj('cart_empty', 'Košík je prázdny');
        ?>
        <input type="text" 
               name="cl_nastavenia[cart_empty]" 
               value="<?php echo esc_attr($text); ?>" 
               class="regular-text">
        <p class="description">Text zobrazený pri prázdnom košíku</p>
        <?php
    }

    public function zobrazInputCartTitle(): void {
        $text = $this->preklady->nacitaj('cart_title', 'Košík');
        ?>
        <input type="text" 
               name="cl_nastavenia[cart_title]" 
               value="<?php echo esc_attr($text); ?>" 
               class="regular-text">
        <p class="description">Nadpis sekcie košíka</p>
        <?php
    }

    // Nová metóda pre zobrazenie input poľa prekladu
    public function zobrazInputPreklad($args): void {
        $kluc = $args['kluc'];
        $hodnota = $this->preklady->nacitaj($kluc, '');
        ?>
        <input type="text" 
               name="cl_nastavenia[preklady][<?php echo esc_attr($kluc); ?>]" 
               value="<?php echo esc_attr($hodnota); ?>" 
               class="regular-text">
        <?php
    }

    public function zobrazInputListokSablona(): void {
        $spravca = \CL\jadro\SpravcaNastaveni::ziskajInstanciu();
        
        // Načítame predvolenú šablónu zo súboru
        $predvolena_sablona = file_get_contents(CL_PLUGIN_DIR . 'sablony/listok.html');
        
        // Načítame uloženú šablónu z DB, ak existuje
        $ulozena_sablona = $spravca->nacitaj('listok_sablona');
        
        // Použijeme uloženú šablónu ak existuje, inak predvolenú
        $sablona = !empty($ulozena_sablona) ? $ulozena_sablona : $predvolena_sablona;
        ?>
        <textarea id="sablona-listka" name="cl_nastavenia[listok_sablona]" rows="20" class="large-text code"><?php 
            echo esc_textarea($sablona); 
        ?></textarea>
        <p class="description">
            Ak necháte prázdne, použije sa predvolená šablóna zo súboru sablony/listok.html<br>
            Dostupné premenné: {datum}, {cas}, {predajca}, {logo}, {polozky}, {celkova_suma}, {cislo_listka}, {hlavicka}, {paticka}
        </p>
        <button type="button" class="button" id="reset-template">Obnoviť predvolenú šablónu</button>
        <?php
    }

    private function getDefaultTemplate(): string {
        return <<<HTML
<div class="listok">
    <!-- Hlavička lístka -->
    <div class="hlavicka" style="text-align:center;margin-bottom:10px;">
        {logo}
        <div style="margin:5px 0;">
            <b>Cestovný lístok</b><br>
            {predajca}<br>
            {datum} {cas}
        </div>
    </div>

    <!-- Položky -->
    <div class="polozky" style="margin:10px 0;border-top:1px solid #000;border-bottom:1px solid #000;padding:5px 0;">
        {polozky}
    </div>

    <!-- Sumár -->
    <div class="sumar" style="text-align:right;margin:10px 0;">
        <b>SPOLU: {suma}</b>
    </div>

    <!-- Pätička -->
    <div class="paticka" style="text-align:center;font-size:90%;margin-top:10px;">
        Číslo lístka: {cislo_listka}<br>
        <div style="margin-top:5px;">
            Ďakujeme za Vašu návštevu!
        </div>
    </div>
</div>
HTML;
    }
}
