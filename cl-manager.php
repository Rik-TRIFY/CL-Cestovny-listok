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

// Konfigurácia záložnej databázy
if (!defined('DB_BACKUP_HOST')) define('DB_BACKUP_HOST', 'localhost');
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
        
        // Debug log pre sledovanie načítavania tried
        error_log("Loading class $class from file $file");
        
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

        // Základné nastavenia
        $nastavenia = new jadro\SpravcaVerzie();
        $nastavenia->nastavZakladneNastavenia();

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
                global $wp_version;
                echo '<div class="error"><p>Plugin CL - Cestovné lístky vyžaduje WordPress 6.7.2 alebo novší. Aktuálna verzia: ' . $wp_version . '</p></div>';
            });
            return false;
        }

        return true;
    }
    
    private function vytvorTabulky(): void {
        global $wpdb;
    
        $charset_collate = $wpdb->get_charset_collate();
    
        // Vytvorenie tabuliek s prepared statements
        $sql1 = $wpdb->prepare("CREATE TABLE IF NOT EXISTS `CL-typy_listkov` (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            nazov varchar(100) NOT NULL,
            cena decimal(10,2) NOT NULL,
            aktivny boolean DEFAULT TRUE,
            vytvorene datetime DEFAULT CURRENT_TIMESTAMP,
            aktualizovane datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) %s", $charset_collate);
    
        $sql2 = $wpdb->prepare("CREATE TABLE IF NOT EXISTS `CL-predaj` (
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
            CONSTRAINT `fk_typ_listka` FOREIGN KEY (`typ_listka_id`) REFERENCES `CL-typy_listkov` (`id`)
        ) %s", $charset_collate);
    
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql1);
        dbDelta($sql2);
    }
    
    public function adminMenu(): void {
        add_menu_page(
            'Cestovné lístky',
            'Cestovné lístky',
            'manage_options',
            'cl-manager',
            [$this, 'zobrazPOSTerminal'],
            'dashicons-tickets-alt'
        );
    
        add_submenu_page(
            'cl-manager',
            'POS Terminál',
            'POS Terminál',
            'manage_options',
            'cl-manager',
            [$this, 'zobrazPOSTerminal']
        );
    
        add_submenu_page(
            'cl-manager',
            'Správa lístkov',
            'Správa lístkov',
            'manage_options',
            'cl-listky',
            [$this, 'zobrazSpravuListkov']
        );

        add_submenu_page(
            'cl-manager',
            'História predaja',
            'História predaja',
            'manage_options',
            'cl-historia',
            [$this, 'zobrazHistoriu']
        );

        add_submenu_page(
            'cl-manager',
            'Export dát',
            'Export dát',
            'manage_options',
            'cl-export',
            [$this, 'zobrazExport']
        );

        add_submenu_page(
            'cl-manager',
            'Zálohy',
            'Zálohy',
            'manage_options',
            'cl-zalohy',
            [$this, 'zobrazZalohy']
        );

        add_submenu_page(
            'cl-manager',
            'Nastavenia',
            'Nastavenia',
            'manage_options',
            'cl-nastavenia',
            [$this, 'zobrazNastavenia']
        );
    }

    public function pridajAssets(): void {
        // CSS
        wp_enqueue_style('cl-admin', CL_ASSETS_URL . 'css/admin.css', [], CL_VERSION);
        wp_enqueue_style('cl-terminal', CL_ASSETS_URL . 'css/terminal.css', [], CL_VERSION);
        wp_enqueue_style('cl-listky', CL_ASSETS_URL . 'css/listky.css', [], CL_VERSION);
        wp_enqueue_style('cl-prehlad', CL_ASSETS_URL . 'css/prehlad.css', [], CL_VERSION);
        wp_enqueue_style('cl-export', CL_ASSETS_URL . 'css/export.css', [], CL_VERSION);
        wp_enqueue_style('cl-notifikacie', CL_ASSETS_URL . 'css/notifikacie.css', [], CL_VERSION);
        
        // JavaScript
        wp_enqueue_script('cl-common', CL_ASSETS_URL . 'js/common.js', [], CL_VERSION, true);
        wp_enqueue_script('cl-admin', CL_ASSETS_URL . 'js/admin.js', ['jquery'], CL_VERSION, true);
        wp_enqueue_script('cl-terminal', CL_ASSETS_URL . 'js/terminal.js', ['jquery'], CL_VERSION, true);
        wp_enqueue_script('cl-listky', CL_ASSETS_URL . 'js/listky.js', ['jquery'], CL_VERSION, true);
        wp_enqueue_script('cl-prehlad', CL_ASSETS_URL . 'js/prehlad.js', ['jquery'], CL_VERSION, true);
        wp_enqueue_script('cl-export', CL_ASSETS_URL . 'js/export.js', ['jquery'], CL_VERSION, true);
        wp_enqueue_script('cl-tlac', CL_ASSETS_URL . 'js/tlac.js', ['jquery'], CL_VERSION, true);
        
        // Lokalizácia premenných pre JavaScript
        wp_localize_script('cl-admin', 'cl_admin', [
            'nonce' => wp_create_nonce('cl_admin_nonce'),
            'auto_tlac' => get_option('cl_nastavenia')['auto_tlac'] ?? false
        ]);
        wp_localize_script('cl-terminal', 'cl_pos', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cl_pos_nonce')
        ]);
    }

    public function zobrazPOSTerminal(): void {
        (new POS\Terminal())->zobrazPOSTerminal();
    }

    public function zobrazSpravuListkov(): void {
        (new Admin\SpravcaListkov())->zobrazSpravuListkov();
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
}

// Inicializácia pluginu
CestovneListky::ziskajInstanciu();