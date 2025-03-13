<?php
declare(strict_types=1);

namespace CL\Admin;

class Nastavenia {
    public function __construct() {
        add_action('admin_init', [$this, 'registrujNastavenia']);
    }

    public function zobrazStranku(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Nedostatočné oprávnenia');
        }
        include CL_INCLUDES_DIR . 'admin/pohlady/nastavenia-formular.php';
    }

    public function registrujNastavenia(): void {
        register_setting('cl_nastavenia', 'cl_nastavenia');
        
        // Vzhľad lístka
        add_settings_section(
            'cl_sekcia_listok',
            'Nastavenia vzhľadu lístka',
            [$this, 'zobrazSekciuListok'],
            'cl-nastavenia'
        );
        
        add_settings_field(
            'cl_logo',
            'Logo na lístku',
            [$this, 'zobrazInputLogo'],
            'cl-nastavenia',
            'cl_sekcia_listok'
        );

        add_settings_field(
            'cl_hlavicka',
            'Hlavička lístka',
            [$this, 'zobrazInputHlavicka'],
            'cl-nastavenia',
            'cl_sekcia_listok'
        );

        add_settings_field(
            'cl_paticka',
            'Pätička lístka',
            [$this, 'zobrazInputPaticka'],
            'cl-nastavenia',
            'cl_sekcia_listok'
        );

        // Nastavenia predaja
        add_settings_section(
            'cl_sekcia_predaj',
            'Nastavenia predaja',
            [$this, 'zobrazSekciuPredaj'],
            'cl-nastavenia'
        );

        add_settings_field(
            'cl_format_cisla',
            'Formát čísla lístka',
            [$this, 'zobrazInputFormatCisla'],
            'cl-nastavenia',
            'cl_sekcia_predaj'
        );

        add_settings_field(
            'cl_auto_tlac',
            'Automatická tlač',
            [$this, 'zobrazInputAutoTlac'],
            'cl-nastavenia',
            'cl_sekcia_predaj'
        );

        add_settings_field(
            'cl_sirka_tlace',
            'Šírka tlačiarne',
            [$this, 'zobrazInputSirkaTlace'],
            'cl-nastavenia',
            'cl_sekcia_predaj'
        );

        // Nastavenia štatistík
        add_settings_section(
            'cl_sekcia_statistiky',
            'Nastavenia štatistík',
            [$this, 'zobrazSekciuStatistiky'],
            'cl-nastavenia'
        );
        
        add_settings_field(
            'cl_statistiky_cas',
            'Čas aktualizácie štatistík',
            [$this, 'zobrazInputStatistikyCas'],
            'cl-nastavenia',
            'cl_sekcia_statistiky'
        );
        
        add_settings_field(
            'cl_statistiky_priecinok',
            'Cieľový priečinok pre štatistiky',
            [$this, 'zobrazInputStatistikyPriecinok'],
            'cl-nastavenia',
            'cl_sekcia_statistiky'
        );

        // Nastavenia zálohovania
        add_settings_section(
            'cl_sekcia_zalohy',
            'Nastavenia zálohovania',
            [$this, 'zobrazSekciuZalohy'],
            'cl-nastavenia'
        );

        add_settings_field(
            'cl_interval_zalohy',
            'Interval zálohovania',
            [$this, 'zobrazInputIntervalZalohy'],
            'cl-nastavenia',
            'cl_sekcia_zalohy'
        );

        add_settings_field(
            'cl_pocet_zaloh',
            'Počet uchovávaných záloh',
            [$this, 'zobrazInputPocetZaloh'],
            'cl-nastavenia',
            'cl_sekcia_zalohy'
        );

        // Nastavenia notifikácií
        add_settings_section(
            'cl_sekcia_notifikacie',
            'Nastavenia notifikácií',
            [$this, 'zobrazSekciuNotifikacie'],
            'cl-nastavenia'
        );

        add_settings_field(
            'cl_email_notifikacie',
            'E-mailové notifikácie',
            [$this, 'zobrazInputEmailNotifikacie'],
            'cl-nastavenia',
            'cl_sekcia_notifikacie'
        );

        // Systémové nastavenia
        add_settings_section(
            'cl_sekcia_system',
            'Systémové nastavenia',
            [$this, 'zobrazSekciuSystem'],
            'cl-nastavenia'
        );

        add_settings_field(
            'cl_debug_mode',
            'Debug mód',
            [$this, 'zobrazInputDebugMode'],
            'cl-nastavenia',
            'cl_sekcia_system'
        );

        add_settings_field(
            'cl_cache_lifetime',
            'Životnosť cache',
            [$this, 'zobrazInputCacheLifetime'],
            'cl-nastavenia',
            'cl_sekcia_system'
        );

        // Nastavenia databáz
        add_settings_section(
            'cl_sekcia_databazy',
            'Nastavenia databáz',
            [$this, 'zobrazSekciuDatabazy'],
            'cl-nastavenia'
        );

        add_settings_field(
            'cl_db_backup_host',
            'Adresa záložnej DB',
            [$this, 'zobrazInputDbHost'],
            'cl-nastavenia',
            'cl_sekcia_databazy'
        );

        add_settings_field(
            'cl_db_backup_name',
            'Názov záložnej DB',
            [$this, 'zobrazInputDbName'],
            'cl-nastavenia',
            'cl_sekcia_databazy'
        );

        add_settings_field(
            'cl_db_backup_user',
            'Používateľ záložnej DB',
            [$this, 'zobrazInputDbUser'],
            'cl-nastavenia',
            'cl_sekcia_databazy'
        );

        add_settings_field(
            'cl_db_backup_pass',
            'Heslo záložnej DB',
            [$this, 'zobrazInputDbPass'],
            'cl-nastavenia',
            'cl_sekcia_databazy'
        );

        add_settings_field(
            'cl_db_sync_interval',
            'Interval kontroly synchronizácie',
            [$this, 'zobrazInputDbSyncInterval'],
            'cl-nastavenia',
            'cl_sekcia_databazy'
        );
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

    // Callback metódy pre polia
    public function zobrazInputLogo(): void {
        $nastavenia = get_option('cl_nastavenia');
        $logo_url = $nastavenia['logo_url'] ?? '';
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
        $nastavenia = get_option('cl_nastavenia');
        $hlavicka = $nastavenia['hlavicka'] ?? '';
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
        $nastavenia = get_option('cl_nastavenia');
        $paticka = $nastavenia['paticka'] ?? '';
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
        $nastavenia = get_option('cl_nastavenia');
        $format = $nastavenia['format_cisla'] ?? 'RRRRMMDD-XXXX';
        ?>
        <input type="text" name="cl_nastavenia[format_cisla]" value="<?php echo esc_attr($format); ?>" class="regular-text">
        <p class="description">
            Formát čísla lístka. Predvolený formát: RRRRMMDD-XXXX<br>
            R = rok, M = mesiac, D = deň, X = poradové číslo
        </p>
        <?php
    }

    public function zobrazInputAutoTlac(): void {
        $nastavenia = get_option('cl_nastavenia');
        $auto_tlac = $nastavenia['auto_tlac'] ?? '1';
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
        $nastavenia = get_option('cl_nastavenia');
        $cas = $nastavenia['statistiky_cas'] ?? '23:00';
        echo '<input type="time" name="cl_nastavenia[statistiky_cas]" value="' . esc_attr($cas) . '" />';
        echo '<p class="description">Čas kedy sa majú generovať denné štatistiky</p>';
    }

    public function zobrazInputStatistikyPriecinok(): void {
        $nastavenia = get_option('cl_nastavenia');
        $priecinok = $nastavenia['statistiky_priecinok'] ?? WP_CONTENT_DIR . '/statistiky';
        echo '<input type="text" name="cl_nastavenia[statistiky_priecinok]" value="' . esc_attr($priecinok) . '" class="regular-text" />';
        echo '<p class="description">Absolútna cesta k priečinku kde sa majú ukladať štatistiky</p>';
    }

    public function zobrazInputIntervalZalohy(): void {
        $nastavenia = get_option('cl_nastavenia');
        $interval = $nastavenia['interval_zalohy'] ?? 'daily';
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
        $nastavenia = get_option('cl_nastavenia');
        $pocet = $nastavenia['pocet_zaloh'] ?? '5';
        ?>
        <input type="number" name="cl_nastavenia[pocet_zaloh]" value="<?php echo esc_attr($pocet); ?>" class="small-text">
        <p class="description">
            Počet uchovávaných záloh. Staršie zálohy budú automaticky odstránené.
        </p>
        <?php
    }

    public function zobrazInputEmailNotifikacie(): void {
        $nastavenia = get_option('cl_nastavenia');
        $email = $nastavenia['email_notifikacie'] ?? '';
        ?>
        <input type="email" name="cl_nastavenia[email_notifikacie]" value="<?php echo esc_attr($email); ?>" class="regular-text">
        <p class="description">
            E-mailová adresa pre zasielanie notifikácií.
        </p>
        <?php
    }

    public function zobrazInputSirkaTlace(): void {
        $nastavenia = get_option('cl_nastavenia');
        $sirka = $nastavenia['sirka_tlace'] ?? '54';
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
        $nastavenia = get_option('cl_nastavenia');
        $debug = $nastavenia['debug_mode'] ?? '0';
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
        $nastavenia = get_option('cl_nastavenia');
        $lifetime = $nastavenia['cache_lifetime'] ?? '3600';
        ?>
        <input type="number" name="cl_nastavenia[cache_lifetime]" value="<?php echo esc_attr($lifetime); ?>" min="300" step="300">
        <p class="description">
            Doba v sekundách, po ktorú sa majú uchovávať dočasné dáta v cache.
            Minimálne 300 sekúnd (5 minút).
        </p>
        <?php
    }

    public function zobrazInputDbHost(): void {
        $nastavenia = get_option('cl_nastavenia');
        $hodnota = $nastavenia['db_backup_host'] ?? DB_HOST;
        ?>
        <input type="text" name="cl_nastavenia[db_backup_host]" value="<?php echo esc_attr($hodnota); ?>" class="regular-text">
        <p class="description">Adresa servera záložnej databázy (napr. localhost alebo IP adresa)</p>
        <?php
    }

    public function zobrazInputDbName(): void {
        $nastavenia = get_option('cl_nastavenia');
        $hodnota = $nastavenia['db_backup_name'] ?? DB_NAME . '_backup';
        ?>
        <input type="text" name="cl_nastavenia[db_backup_name]" value="<?php echo esc_attr($hodnota); ?>" class="regular-text">
        <p class="description">Názov záložnej databázy</p>
        <?php
    }

    public function zobrazInputDbUser(): void {
        $nastavenia = get_option('cl_nastavenia');
        $hodnota = $nastavenia['db_backup_user'] ?? DB_USER;
        ?>
        <input type="text" name="cl_nastavenia[db_backup_user]" value="<?php echo esc_attr($hodnota); ?>" class="regular-text">
        <p class="description">Používateľské meno pre prístup k záložnej databáze</p>
        <?php
    }

    public function zobrazInputDbPass(): void {
        $nastavenia = get_option('cl_nastavenia');
        $hodnota = $nastavenia['db_backup_pass'] ?? '';
        ?>
        <input type="password" name="cl_nastavenia[db_backup_pass]" value="<?php echo esc_attr($hodnota); ?>" class="regular-text">
        <p class="description">Heslo pre prístup k záložnej databáze</p>
        <?php
    }

    public function zobrazInputDbSyncInterval(): void {
        $nastavenia = get_option('cl_nastavenia');
        $interval = $nastavenia['db_sync_interval'] ?? '300';
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
}
