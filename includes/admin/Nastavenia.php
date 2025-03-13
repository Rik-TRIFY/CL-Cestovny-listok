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
        
        // Základné nastavenia lístka
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
}
