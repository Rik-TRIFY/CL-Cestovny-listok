<?php
declare(strict_types=1);

/**
 * Hlavný súbor pluginu pre predaj cestovných lístkov
 * 
 * Zabezpečuje:
 * - Inicializáciu pluginu a autoloading
 * - Vytvorenie databázových tabuliek
 * - Registráciu admin menu
 * - Správu assets (CSS/JS)
 * - Aktivačné/deaktivačné hooku
 * - Kontrolu závislostí
 */

/*
Plugin Name: CL - Cestovné lístky
Description: Evidencia a predaj cestovných lístkov
Version: 1.0.0
Author: Erik Fedor - TRIFY s.r.o.
Author URI: https://trify.sk
License: GPLv2 or later
Requires at least: 6.7.2
Requires PHP: 8.1
*/

namespace CL;

// Zabezpečenie priameho prístupu
if (!defined('ABSPATH')) exit;

// Základné konštanty
define('CL_VERSION', '1.0.0');
define('CL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CL_INCLUDES_DIR', CL_PLUGIN_DIR . 'includes/');
define('CL_ASSETS_URL', plugin_dir_url(__FILE__) . 'assets/');
define('CL_PREDAJ_DIR', CL_INCLUDES_DIR . 'predaj/');
define('CL_LOGS_DIR', CL_INCLUDES_DIR . 'logy/');

// Konfigurácia záložnej databázy - použijeme hlavné prihlasovacie údaje
if (!defined('DB_BACKUP_HOST')) define('DB_BACKUP_HOST', DB_HOST);
if (!defined('DB_BACKUP_USER')) define('DB_BACKUP_USER', DB_USER);
if (!defined('DB_BACKUP_PASSWORD')) define('DB_BACKUP_PASSWORD', DB_PASSWORD);
if (!defined('DB_BACKUP_NAME')) define('DB_BACKUP_NAME', DB_NAME . '_backup');

// Upravený autoloader
spl_autoload_register(function ($class) {
    // Kontrola či ide o náš namespace
    if (strpos($class, 'CL\\') === 0) {
        // Odstránime namespace prefix
        $relative_class = substr($class, strlen('CL\\'));
        
        // Vytvoríme cestu k súboru
        $file = CL_PLUGIN_DIR . 'includes/' . str_replace('\\', '/', $relative_class) . '.php';
        
        // Ak súbor existuje, načítame ho
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }
    return false;
});

class CestovneListky {
    private static ?self $instancia = null;

    public static function ziskajInstanciu(): self {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    private function __construct() {
        $this->inicializacia();
    }
    
    private function inicializacia(): void {
        // Inicializácia error handlera pre lepšiu diagnostiku
        new jadro\ErrorHandler();
        
        // Pridáme SpravcaListkov do hlavnej inicializácie
        new admin\SpravcaListkov();
        
        $this->vytvorPriecinky();
        register_activation_hook(__FILE__, [$this, 'aktivacia']);
        add_action('admin_menu', [$this, 'adminMenu']);
        add_action('admin_enqueue_scripts', [$this, 'pridajAssets']);
        add_shortcode('pos_terminal', [$this, 'zobrazPOSTerminalShortcode']);
        
        // Pridané logovanie AJAX chýb
        add_action('wp_ajax_nopriv_cl_log_error', [$this, 'logJavascriptError']);
        add_action('wp_ajax_cl_log_error', [$this, 'logJavascriptError']);
        
        new jadro\Databaza();
        new admin\Nastavenia();
        new pos\Terminal();
        new admin\AdminRozhranie();
        new jadro\SpravcaVerzie();
        new jadro\Router();
        
        // Odstránené debug nástroje
        // new admin\AjaxDebug();
        // new admin\DirectAjaxTest();
    }
    
    private function vytvorPriecinky(): void {
        $priecinky = [
            CL_PREDAJ_DIR,
            CL_LOGS_DIR
        ];

        foreach ($priecinky as $priecinok) {
            if (!file_exists($priecinok)) {
                wp_mkdir_p($priecinok);
                file_put_contents($priecinok . 'index.php', '<?php // Silence is golden');
            }
        }
    }

    public function aktivacia(): void {
        if (!$this->skontrolujPoziadavky()) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die('Plugin nemôže byť aktivovaný - nesplnené minimálne požiadavky.');
        }

        // Vytvoríme/skontrolujeme tabuľky pri každej aktivácii
        $this->vytvorTabulky();

        // Vytvorenie priečinkov
        $priecinky = [
            CL_PREDAJ_DIR,
            CL_LOGS_DIR,
            CL_PLUGIN_DIR . 'zalohy',
            CL_PLUGIN_DIR . 'assets/images'
        ];

        foreach ($priecinky as $priecinok) {
            if (!file_exists($priecinok)) {
                wp_mkdir_p($priecinok);
                file_put_contents($priecinok . '/index.php', '<?php // Silence is golden');
                file_put_contents($priecinok . '/.htaccess', 'deny from all');
            }
        }

        // Vytvorenie databázových tabuliek
        $this->vytvorTabulky();

        // Pridanie stĺpca text_listok ak neexistuje
        global $wpdb;
        $table_name = $wpdb->prefix . 'cl_typy_listkov';
        $row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                                  WHERE TABLE_SCHEMA = '" . DB_NAME . "' 
                                  AND TABLE_NAME = '{$table_name}'
                                  AND COLUMN_NAME = 'text_listok'");
                                  
        if (empty($row)) {
            $wpdb->query("ALTER TABLE `{$table_name}` 
                         ADD COLUMN `text_listok` varchar(200) NOT NULL AFTER `nazov`");
        }

        // Základné nastavenia - zmenené na public metódu
        $spravca = new jadro\SpravcaVerzie();
        $spravca->inicializuj();

        // Aktivácia kontrol
        $kontroler = new jadro\Kontroler();
        $kontroler->aktivujKontroly();
    }
        
    public function skontrolujPoziadavky(): bool {
        global $wp_version;

        if (version_compare(PHP_VERSION, '8.1', '<')) {
            add_action('admin_notices', function() {
                echo '<div class="error"><p>Plugin CL - Cestovné lístky vyžaduje PHP 8.1 alebo novšie. Aktuálna verzia: ' . PHP_VERSION . '</p></div>';
            });
            return false;
        }

        if (version_compare($wp_version, '6.7.2', '<')) {
            add_action('admin_notices', function() {
                echo '<div class="error"><p>Plugin CL - Cestovné lístky vyžaduje WordPress 6.7.2 alebo novší. Aktuálna verzia: ' . $wp_version . '</p></div>';
            });
            return false;
        }

        return true;
    }

    private function vytvorTabulky(): void {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Kontrola, či existuje stará tabuľka
        $stara_tabulka = $wpdb->get_var("SHOW TABLES LIKE 'CL-typy_listkov'");
        if ($stara_tabulka) {
            // Skopírujme dáta zo starej tabuľky do novej
            $rows = $wpdb->get_results("SELECT * FROM `CL-typy_listkov`", ARRAY_A);
            
            if (!empty($rows)) {
                // Vytvoríme novú tabuľku
                $wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}cl_typy_listkov` LIKE `CL-typy_listkov`");
                
                // Skontrolujeme, či existuje stĺpec poradie v starej tabuľke
                $stary_poradie = $wpdb->get_results(
                    "SHOW COLUMNS FROM `CL-typy_listkov` LIKE 'poradie'"
                );
                
                if (empty($stary_poradie)) {
                    // Pridáme stĺpec do novej tabuľky, ak neexistuje v starej
                    $wpdb->query("ALTER TABLE `{$wpdb->prefix}cl_typy_listkov` 
                                 ADD COLUMN `poradie` int(11) DEFAULT 0 AFTER `aktivny`");
                }
                
                // Skopírujme dáta
                foreach ($rows as $row) {
                    // Skontrolujme či záznam už existuje
                    $exists = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM `{$wpdb->prefix}cl_typy_listkov` WHERE id = %d",
                        $row['id']
                    ));
                    
                    if (!$exists) {
                        // Pridáme stĺpec poradie ak neexistuje v starej tabuľke
                        if (empty($stary_poradie) && !isset($row['poradie'])) {
                            $row['poradie'] = 0;
                        }
                        $wpdb->insert($wpdb->prefix . 'cl_typy_listkov', $row);
                    }
                }
            }
        }
        
        // Kontrola a vytvorenie tabuliek v správnom poradí
        $sql = [
            // Tabuľka typov lístkov - explicitne zabezpečíme stĺpec poradie
            "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}cl_typy_listkov` (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                nazov varchar(100) NOT NULL,
                text_listok varchar(200) NOT NULL,
                cena decimal(10,2) NOT NULL,
                aktivny boolean DEFAULT TRUE,
                poradie int(11) DEFAULT 0,
                vytvorene datetime DEFAULT CURRENT_TIMESTAMP,
                aktualizovane datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY  (id)
            ) $charset_collate;",
            
            // Tabuľka predajov
            "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}cl_predaj` (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                cislo_predaja varchar(50) NOT NULL,
                predajca_id bigint(20) NOT NULL,
                celkova_suma decimal(10,2) NOT NULL,
                datum_predaja datetime DEFAULT CURRENT_TIMESTAMP,
                storno boolean DEFAULT FALSE,
                data_listka text,
                PRIMARY KEY  (id),
                KEY predajca_id (predajca_id)
            ) $charset_collate;",
            
            // Tabuľka položiek predaja
            "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}cl_polozky_predaja` (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                predaj_id mediumint(9) NOT NULL,
                typ_listka_id mediumint(9) NOT NULL,
                pocet int NOT NULL,
                cena_za_kus decimal(10,2) NOT NULL,
                PRIMARY KEY  (id),
                KEY predaj_id (predaj_id),
                KEY typ_listka_id (typ_listka_id)
            ) $charset_collate;",

            // Tabuľka nastavení
            "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}cl_nastavenia` (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                option_name varchar(191) NOT NULL,
                option_value longtext NOT NULL,
                autoload varchar(20) NOT NULL DEFAULT 'yes',
                created datetime DEFAULT CURRENT_TIMESTAMP,
                updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                UNIQUE KEY option_name (option_name)
            ) $charset_collate;"
        ];

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        foreach ($sql as $query) {
            dbDelta($query);
        }
        
        // Pridáme foreign keys v separátnych dotazoch
        $wpdb->query("ALTER TABLE `{$wpdb->prefix}cl_polozky_predaja` 
            ADD CONSTRAINT `fk_predaj` FOREIGN KEY (predaj_id) 
            REFERENCES `{$wpdb->prefix}cl_predaj` (id) ON DELETE CASCADE");
            
        $wpdb->query("ALTER TABLE `{$wpdb->prefix}cl_polozky_predaja` 
            ADD CONSTRAINT `fk_typ_listka` FOREIGN KEY (typ_listka_id) 
            REFERENCES `{$wpdb->prefix}cl_typy_listkov` (id)");

        // Inicializácia základných nastavení ak tabuľka práve bola vytvorená
        $defaultSettings = [
            'pos_width' => '375',
            'pos_height' => '667',
            'pos_layout' => 'grid',
            'pos_columns' => '4',
            'pos_button_size' => 'medium'
        ];

        foreach ($defaultSettings as $name => $value) {
            $wpdb->replace(
                $wpdb->prefix . 'cl_nastavenia',
                [
                    'option_name' => $name,
                    'option_value' => $value
                ],
                ['%s', '%s']
            );
        }
        
        // Pridanie stĺpca poradie ak neexistuje
        $row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                                  WHERE TABLE_SCHEMA = '" . DB_NAME . "' 
                                  AND TABLE_NAME = '{$wpdb->prefix}cl_typy_listkov'
                                  AND COLUMN_NAME = 'poradie'");
                                  
        if (empty($row)) {
            $wpdb->query("ALTER TABLE `{$wpdb->prefix}cl_typy_listkov` 
                         ADD COLUMN `poradie` int(11) DEFAULT 0 AFTER `aktivny`");
        }
    }

    public function adminMenu(): void {
        global $menu;
        add_menu_page(
            'Cestovné lístky',
            'Cestovné lístky',
            'manage_options',
            'cl-manager',
            [$this, 'zobrazDashboard'],
            'dashicons-tickets-alt',
            0
        );
        
        $menu[1] = array('', 'read', 'separator1', '', 'wp-menu-separator');
    
        // Prehľad (dashboard)
        add_submenu_page(
            'cl-manager',
            'Prehľad',
            'Prehľad (dashboard)',
            'manage_options',
            'cl-manager',
            [$this, 'zobrazDashboard']
        );
    
        // Správa položiek
        add_submenu_page(
            'cl-manager',
            'Správa položiek',
            'Správa položiek',
            'manage_options',
            'cl-polozky',
            [$this, 'zobrazSpravuPoloziek']
        );

        // História predaja
        add_submenu_page(
            'cl-manager',
            'História predaja',
            'História predaja',
            'manage_options',
            'cl-historia',
            [$this, 'zobrazHistoriu']
        );

        // Štatistiky
        add_submenu_page(
            'cl-manager',
            'Štatistiky',
            'Štatistiky',
            'manage_options',
            'cl-statistiky',
            [$this, 'zobrazStatistiky']
        );

        // Import/Export dát
        add_submenu_page(
            'cl-manager',
            'Import/Export dát',
            'Import/Export dát',
            'manage_options',
            'cl-import-export',
            [$this, 'zobrazImportExport']
        );

        // Zálohy
        add_submenu_page(
            'cl-manager',
            'Zálohy',
            'Zálohy',
            'manage_options',
            'cl-zalohy',
            [$this, 'zobrazZalohy']
        );

        // Nastavenia
        add_submenu_page(
            'cl-manager',
            'Nastavenia',
            'Nastavenia',
            'manage_options',
            'cl-nastavenia',
            [$this, 'zobrazNastavenia']
        );

        // Systémové logy
        add_submenu_page(
            'cl-manager',
            'Systémové logy',
            'Systémové logy',
            'manage_options',
            'cl-logy',
            [$this, 'zobrazLogy']
        );
    }

    public function pridajAssets(): void {
        $stranka = $_GET['page'] ?? '';
        
        if ($stranka === 'cl-polozky') {
            wp_enqueue_style('cl-admin', CL_ASSETS_URL . 'css/admin.css', [], CL_VERSION);
            wp_enqueue_style('cl-listky', CL_ASSETS_URL . 'css/listky.css', [], CL_VERSION);
            
            // Dôležité: jQuery ako závislosť musí byť ako pole
            wp_enqueue_script('cl-listky', CL_ASSETS_URL . 'js/listky.js', ['jquery'], CL_VERSION, true);
            
            // Dôležité: Pridanie debug informácií pre lepšie hľadanie chýb
            wp_localize_script('cl-listky', 'cl_admin', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('cl_listky_nonce'),
                'debug' => true,
                'version' => CL_VERSION
            ]);
        }
        
        if ($stranka === 'cl-nastavenia') {
            wp_enqueue_style('cl-nastavenia', CL_ASSETS_URL . 'css/nastavenia.css', [], CL_VERSION);
            wp_enqueue_media(); // Pre výber loga
            wp_enqueue_script('cl-nastavenia', CL_ASSETS_URL . 'js/nastavenia.js', ['jquery'], CL_VERSION, true);
        }
    }

    public function zobrazDashboard(): void {
        require_once CL_INCLUDES_DIR . 'admin/pohlady/dashboard.php';
    }

    public function zobrazPOSTerminal(): void {
        (new POS\Terminal())->zobrazPOSTerminal();
    }

    public function zobrazSpravuListkov(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Nedostatočné oprávnenia');
        }
        require CL_INCLUDES_DIR . 'admin/pohlady/sprava-listkov.php';
    }

    public function zobrazNastavenia(): void {
        (new Admin\Nastavenia())->zobrazStranku();
    }

    public function zobrazArchiv(): void {
        (new Admin\SpravcaArchivu())->zobrazArchiv();
    }

    public function zobrazZalohy(): void {
        require_once CL_INCLUDES_DIR . 'admin/pohlady/zalohy.php';
    }

    public function zobrazPredajcov(): void {
        require_once CL_INCLUDES_DIR . 'admin/pohlady/predajcovia.php';
    }

    public function zobrazStatistiky(): void {
        require_once CL_INCLUDES_DIR . 'admin/pohlady/statistiky.php';
    }

    public function zobrazLogy(): void {
        require_once CL_INCLUDES_DIR . 'admin/pohlady/logy.php';
    }

    public function zobrazPOSTerminalShortcode($atts): string {
        if (!is_user_logged_in()) {
            return '<p>Pre prístup k POS terminálu sa musíte prihlásiť.</p>';
        }

        // Načítame assets pre terminál
        wp_enqueue_style('cl-pos', CL_ASSETS_URL . 'css/pos.css', [], CL_VERSION);
        wp_enqueue_script('cl-pos', CL_ASSETS_URL . 'js/terminal.js', ['jquery'], CL_VERSION, true);
        
        wp_localize_script('cl-pos', 'cl_pos', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cl_pos_nonce')
        ]);

        ob_start();
        (new POS\Terminal())->zobrazTerminal();
        return ob_get_clean();
    }

    public function zobrazSpravuPoloziek(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Nedostatočné oprávnenia');
        }
        
        $akcia = $_GET['akcia'] ?? 'listky';
        
        switch ($akcia) {
            case 'kategorie':
                require CL_INCLUDES_DIR . 'admin/pohlady/sprava-kategorii.php';
                break;
            case 'ceny':
                require CL_INCLUDES_DIR . 'admin/pohlady/sprava-cien.php';
                break;
            default:
                require CL_INCLUDES_DIR . 'admin/pohlady/sprava-listkov.php';
        }
    }

    public function zobrazImportExport(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Nedostatočné oprávnenia');
        }
                
        require_once CL_INCLUDES_DIR . 'admin/pohlady/import-export.php';
    }

    public function logJavascriptError(): void {
        if (!isset($_POST['error'])) {
            wp_send_json_error('Chýba parameter error');
            return;
        }
        
        $error = sanitize_text_field($_POST['error']);
        $url = isset($_POST['url']) ? sanitize_text_field($_POST['url']) : '';
        $line = isset($_POST['line']) ? intval($_POST['line']) : 0;
        $file = isset($_POST['file']) ? sanitize_text_field($_POST['file']) : '';
        
        $log_message = sprintf(
            "[JavaScript Error] %s in %s at line %d (URL: %s)",
            $error,
            $file,
            $line,
            $url
        );
        
        // Zápis do WordPress logu
        error_log($log_message);
        
        // Zápis do vlastného logu
        $spravca = new jadro\SpravcaSuborov();
        $spravca->zapisDoLogu('JS_ERROR', [
            'message' => $error,
            'file' => $file,
            'line' => $line,
            'url' => $url,
            'timestamp' => current_time('mysql')
        ]);
               
        wp_send_json_success();
    }
}

/**
 * Registrácia shortcode pre POS terminál
 */
function registruj_shortcode_terminal(): void {
    add_shortcode('pos_terminal', 'cl_terminal_shortcode');
}

/**
 * Funkcia pre shortcode POS terminálu
 */
function cl_terminal_shortcode(): string {
    $terminal = new \CL\POS\Terminal();
    ob_start();
    $terminal->zobrazTerminal();
    return ob_get_clean();
}



CestovneListky::ziskajInstanciu();// Inicializácia pluginu
// Inicializácia pluginu
CestovneListky::ziskajInstanciu();