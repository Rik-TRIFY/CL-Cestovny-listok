<?php
declare(strict_types=1);

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
        $this->vytvorPriecinky();
        register_activation_hook(__FILE__, [$this, 'aktivacia']);
        add_action('admin_menu', [$this, 'adminMenu']);
        add_action('admin_enqueue_scripts', [$this, 'pridajAssets']);
        add_shortcode('pos_terminal', [$this, 'zobrazPOSTerminalShortcode']);
        
        new jadro\Databaza();
        new admin\Nastavenia();
        new pos\Terminal();
        new admin\AdminRozhranie();
        new jadro\SpravcaVerzie();
        new jadro\Router();
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
    
        $sql = [
            "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}cl_typy_listkov` (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                nazov varchar(100) NOT NULL,
                cena decimal(10,2) NOT NULL,
                aktivny boolean DEFAULT TRUE,
                vytvorene datetime DEFAULT CURRENT_TIMESTAMP,
                aktualizovane datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY  (id)
            ) $charset_collate",
    
            "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}cl_predaj` (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                typ_listka_id mediumint(9) NOT NULL,
                pocet int(11) NOT NULL,
                celkova_cena decimal(10,2) NOT NULL,
                predajca_id bigint(20) NOT NULL,
                datum_predaja datetime DEFAULT CURRENT_TIMESTAMP,
                storno boolean DEFAULT FALSE,
                PRIMARY KEY  (id),
                KEY `typ_listka_id` (`typ_listka_id`),
                KEY `predajca_id` (`predajca_id`),
                CONSTRAINT `fk_typ_listka` FOREIGN KEY (`typ_listka_id`) 
                REFERENCES `{$wpdb->prefix}cl_typy_listkov` (`id`)
            ) $charset_collate"
        ];
    
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        foreach ($sql as $query) {
            dbDelta($query);
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
        if (!isset($_GET['page']) || !in_array($_GET['page'], ['cl-listky'])) {
            return;
        }

        // CSS
        wp_enqueue_style('cl-admin', CL_ASSETS_URL . 'css/admin.css', [], CL_VERSION);
        wp_enqueue_style('cl-listky', CL_ASSETS_URL . 'css/listky.css', [], CL_VERSION);
        wp_enqueue_style('cl-import-export', CL_ASSETS_URL . 'css/import-export.css', [], CL_VERSION);
        
        // JavaScript
        wp_enqueue_script('cl-listky', CL_ASSETS_URL . 'js/listky.js', ['jquery'], CL_VERSION, true);
        
        // Lokalizácia premenných pre JavaScript
        wp_localize_script('cl-listky', 'cl_admin', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cl_listky_nonce')
        ]);
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

    public function zobrazExport(): void {
        require_once CL_INCLUDES_DIR . 'admin/pohlady/export.php';
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

    public function zobrazImport(): void {
        require_once CL_INCLUDES_DIR . 'admin/pohlady/import.php';
    }

    public function zobrazLogy(): void {
        require_once CL_INCLUDES_DIR . 'admin/pohlady/logy.php';
    }

    public function zobrazPOSTerminalShortcode($atts): string {
        if (!is_user_logged_in()) {
            return '<p>Pre prístup k POS terminálu sa musíte prihlásiť.</p>';
        }

        // Načítame assets pre terminál
        wp_enqueue_style('cl-terminal', CL_ASSETS_URL . 'css/terminal.css', [], CL_VERSION);
        wp_enqueue_script('cl-terminal', CL_ASSETS_URL . 'js/terminal.js', ['jquery'], CL_VERSION, true);
        wp_localize_script('cl-terminal', 'cl_pos', [
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
        
        $akcia = $_GET['akcia'] ?? 'import';
        
        if ($akcia === 'export') {
            require CL_INCLUDES_DIR . 'admin/pohlady/export.php';
        } else {
            require CL_INCLUDES_DIR . 'admin/pohlady/import.php';
        }
    }
}

// Inicializácia pluginu
CestovneListky::ziskajInstanciu();