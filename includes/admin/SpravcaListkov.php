<?php
declare(strict_types=1);

/**
 * Správa typov lístkov
 */

namespace CL\Admin;

class SpravcaListkov {
    private \CL\jadro\Databaza $databaza;
    
    public function __construct() {
        $this->databaza = new \CL\jadro\Databaza();
        
        // Pridanie detailného logovania
        error_log('SpravcaListkov: Inicializácia');
        
        // AJAX handlery s ošetrením chýb
        add_action('wp_ajax_cl_pridaj_listok', [$this, 'pridajListokWrapper']);
        add_action('wp_ajax_cl_uprav_listok', [$this, 'upravListokWrapper']);
        add_action('wp_ajax_cl_prepni_aktivny', [$this, 'prepniAktivnyWrapper']);
        add_action('wp_ajax_cl_nacitaj_listok', [$this, 'nacitajListokWrapper']);
        add_action('wp_ajax_cl_zmaz_listok', [$this, 'zmazListokWrapper']);
        
        // Registrácia akcií musí byť v konštruktore
        add_action('init', [$this, 'registrujAkcie']);
        
        // Log registrácie AJAX handlerov
        error_log('SpravcaListkov: AJAX handlery zaregistrované');
    }

    public function registrujAkcie(): void {
        // Registrácia pre neverejné AJAX akcie
        add_action('wp_ajax_cl_nacitaj_listok', [$this, 'nacitajListokWrapper']);
        add_action('wp_ajax_cl_pridaj_listok', [$this, 'pridajListokWrapper']);
    }
    
    // Wrapper metódy, ktoré zachytia chyby
    public function pridajListokWrapper(): void {
        try {
            $this->pridajListok();
        } catch (\Exception $e) {
            $this->handleAjaxError($e);
        }
    }
    
    public function upravListokWrapper(): void {
        try {
            $this->upravListok();
        } catch (\Exception $e) {
            $this->handleAjaxError($e);
        }
    }
    
    public function prepniAktivnyWrapper(): void {
        try {
            $this->prepniAktivny();
        } catch (\Exception $e) {
            $this->handleAjaxError($e);
        }
    }
    
    public function nacitajListokWrapper(): void {
        try {
            error_log('SpravcaListkov: nacitajListokWrapper zavolaný s POST: ' . print_r($_POST, true));
            
            // Explicitná kontrola nonce
            if (!isset($_POST['nonce'])) {
                throw new \Exception('Chýba nonce parameter');
            }

            if (!wp_verify_nonce($_POST['nonce'], 'cl_listky_nonce')) {
                throw new \Exception('Neplatný nonce');
            }

            if (!isset($_POST['id'])) {
                throw new \Exception('Chýba ID parameter');
            }

            $this->nacitajListok();
        } catch (\Exception $e) {
            error_log('SpravcaListkov Exception: ' . $e->getMessage());
            wp_send_json_error([
                'message' => $e->getMessage(),
                'file' => basename($e->getFile()),
                'line' => $e->getLine()
            ]);
        } catch (\Error $e) {
            error_log('SpravcaListkov Fatal Error: ' . $e->getMessage() . ' v ' . $e->getFile() . ':' . $e->getLine());
            error_log('SpravcaListkov Stack trace: ' . $e->getTraceAsString());
            wp_send_json_error([
                'message' => $e->getMessage(),
                'file' => basename($e->getFile()),
                'line' => $e->getLine()
            ]);
        }
    }
    
    public function zmazListokWrapper(): void {
        try {
            $this->zmazListok();
        } catch (\Exception $e) {
            $this->handleAjaxError($e);
        }
    }
    
    // Centralizované spracovanie chýb
    private function handleAjaxError(\Exception $e): void {
        error_log('SpravcaListkov AJAX chyba: ' . $e->getMessage() . ' v ' . $e->getFile() . ':' . $e->getLine());
        wp_send_json_error([
            'message' => $e->getMessage(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]);
    }

    public function zobrazSpravuListkov(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Nedostatočné oprávnenia');
        }
        
        error_log('SpravcaListkov: Zobrazujem správu lístkov');
        
        // Kontrola existencie pohľadového súboru
        $pohladovy_subor = CL_INCLUDES_DIR . 'admin/pohlady/sprava-listkov.php';
        if (!file_exists($pohladovy_subor)) {
            error_log("SpravcaListkov: Pohľadový súbor $pohladovy_subor neexistuje!");
            wp_die("Chyba: Pohľadový súbor $pohladovy_subor neexistuje!");
            return;
        }
        
        require_once $pohladovy_subor;
    }

    public function pridajListok(): void {
        error_log('SpravcaListkov: Spustená metóda pridajListok()');
        
        // Kontrola AJAX nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cl_listky_nonce')) {
            error_log('SpravcaListkov: Neplatný nonce token');
            wp_send_json_error('Neplatný bezpečnostný token');
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Nedostatočné oprávnenia');
            return;
        }
        
        global $wpdb;
        $nazov = sanitize_text_field($_POST['nazov']);
        $text_listok = sanitize_text_field($_POST['text_listok']);
        $cena = (float)$_POST['cena'];
        $poradie = isset($_POST['poradie']) ? (int)$_POST['poradie'] : 0;
        
        if (empty($nazov) || empty($text_listok) || $cena <= 0) {
            wp_send_json_error('Neplatné údaje');
            return;
        }
        
        // Kontrola existencie stĺpca poradie
        $check_column = $wpdb->get_results("SHOW COLUMNS FROM `{$wpdb->prefix}cl_typy_listkov` LIKE 'poradie'");
        if (empty($check_column)) {
            $wpdb->query("ALTER TABLE `{$wpdb->prefix}cl_typy_listkov` ADD COLUMN `poradie` int(11) DEFAULT 0 AFTER `aktivny`");
        }
        
        $success = $wpdb->insert(
            $wpdb->prefix . 'cl_typy_listkov',
            [
                'nazov' => $nazov,
                'text_listok' => $text_listok,
                'cena' => $cena,
                'poradie' => $poradie,
                'aktivny' => true
            ],
            ['%s', '%s', '%f', '%d', '%d']
        );
        
        if ($success) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Chyba pri ukladaní: ' . $wpdb->last_error);
        }
    }

    public function upravListok(): void {
        error_log('SpravcaListkov: Spustená metóda upravListok()');
        
        // Kontrola AJAX nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cl_listky_nonce')) {
            error_log('SpravcaListkov: Neplatný nonce token');
            wp_send_json_error('Neplatný bezpečnostný token');
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Nedostatočné oprávnenia');
            return;
        }
        
        $id = (int)$_POST['id'];
        $nazov = sanitize_text_field($_POST['nazov']);
        $text_listok = isset($_POST['text_listok']) ? sanitize_text_field($_POST['text_listok']) : '';
        $cena = (float)$_POST['cena'];
        $poradie = isset($_POST['poradie']) ? (int)$_POST['poradie'] : 0;
        
        if (empty($nazov) || $cena <= 0) {
            wp_send_json_error('Neplatné údaje');
            return;
        }
        
        global $wpdb;
        
        // Skontrolujeme, či existuje stĺpec text_listok
        $has_text_field = $wpdb->get_results("SHOW COLUMNS FROM `{$wpdb->prefix}cl_typy_listkov` LIKE 'text_listok'");
        
        $data = [
            'nazov' => $nazov,
            'cena' => $cena,
            'poradie' => $poradie
        ];
        
        $format = ['%s', '%f', '%d'];
        
        // Pridáme text_listok, ak stĺpec existuje a údaj bol poskytnutý
        if (!empty($has_text_field) && !empty($text_listok)) {
            $data['text_listok'] = $text_listok;
            $format[] = '%s';
        }
        
        $success = $wpdb->update(
            $wpdb->prefix . 'cl_typy_listkov',
            $data,
            ['id' => $id],
            $format,
            ['%d']
        );
        
        if ($success !== false) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Chyba pri ukladaní: ' . $wpdb->last_error);
        }
    }

    public function prepniAktivny(): void {
        error_log('SpravcaListkov: Spustená metóda prepniAktivny()');
        
        // Diagnostika parametrov
        error_log('POST dáta: ' . print_r($_POST, true));
        
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cl_listky_nonce')) {
            error_log('SpravcaListkov: Neplatný nonce token');
            throw new \Exception('Neplatný bezpečnostný token');
        }
        
        if (!current_user_can('manage_options')) {
            throw new \Exception('Nedostatočné oprávnenia');
        }
        
        if (!isset($_POST['id']) || !isset($_POST['aktivny'])) {
            throw new \Exception('Chýbajú povinné parametre (id alebo aktivny)');
        }
        
        $id = (int)$_POST['id'];
        $aktivny = (int)$_POST['aktivny']; // Explicitná konverzia na int
        
        global $wpdb;
        
        // Skúsime najprv novú tabuľku
        $table_name = $wpdb->prefix . 'cl_typy_listkov';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        
        if ($table_exists) {
            // Kontrola existencie záznamu
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM `$table_name` WHERE id = %d", 
                $id
            ));
            
            if ($exists) {
                $result = $wpdb->update(
                    $table_name,
                    ['aktivny' => $aktivny],
                    ['id' => $id],
                    ['%d'],
                    ['%d']
                );
                
                if ($result !== false) {
                    wp_send_json_success();
                    return;
                }
                
                error_log("SpravcaListkov: Chyba pri aktualizácii v $table_name: " . $wpdb->last_error);
            }
        }
        
        // Ak sme neuspeli s novou tabuľkou, skúsime starú
        $alt_table = 'CL-typy_listkov';
        $alt_exists = $wpdb->get_var("SHOW TABLES LIKE '$alt_table'");
        
        if ($alt_exists) {
            // Kontrola existencie záznamu
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM `$alt_table` WHERE id = %d", 
                $id
            ));
            
            if ($exists) {
                $result = $wpdb->update(
                    $alt_table,
                    ['aktivny' => $aktivny],
                    ['id' => $id],
                    ['%d'],
                    ['%d']
                );
                
                if ($result !== false) {
                    wp_send_json_success();
                    return;
                }
                
                error_log("SpravcaListkov: Chyba pri aktualizácii v $alt_table: " . $wpdb->last_error);
            }
        }
        
        throw new \Exception('Lístok nebol nájdený alebo aktualizácia zlyhala');
    }

    public function nacitajListok(): void {
        error_log('SpravcaListkov: Spustená metóda nacitajListok() - POST data: ' . print_r($_POST, true));
        
        // Bezpečnostné kontroly s explicitnejšími chybami
        if (!isset($_POST['nonce'])) {
            error_log('SpravcaListkov CHYBA: Chýbajúci nonce parameter');
            wp_send_json_error([
                'message' => 'Chýbajúci bezpečnostný token (nonce)',
                'code' => 'missing_nonce'
            ]);
            return;
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'cl_listky_nonce')) {
            error_log('SpravcaListkov CHYBA: Neplatný nonce: ' . $_POST['nonce']);
            wp_send_json_error([
                'message' => 'Neplatný bezpečnostný token',
                'code' => 'invalid_nonce',
                'provided_nonce' => $_POST['nonce']
            ]);
            return;
        }
        
        if (!current_user_can('manage_options')) {
            error_log('SpravcaListkov CHYBA: Používateľ nemá dostatočné oprávnenia');
            wp_send_json_error([
                'message' => 'Nedostatočné oprávnenia',
                'code' => 'insufficient_permissions'
            ]);
            return;
        }
        
        if (!isset($_POST['id'])) {
            error_log('SpravcaListkov CHYBA: Chýba ID parameter');
            wp_send_json_error([
                'message' => 'Chýba parameter ID lístka',
                'code' => 'missing_id'
            ]);
            return;
        }
        
        global $wpdb;
        $id = (int)$_POST['id'];
        
        // Detailné logovanie pre debugovanie
        error_log("SpravcaListkov: Načítavam lístok ID=$id");
        
        // Skúsime najprv nový formát tabuľky
        $table_name = $wpdb->prefix . 'cl_typy_listkov';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        error_log("SpravcaListkov: Kontrola existencie tabuľky $table_name: " . ($table_exists ? 'ÁNO' : 'NIE'));
        
        if ($table_exists) {
            $listok = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM `$table_name` WHERE id = %d",
                    $id
                )
            );
            
            if ($listok) {
                // Kontrola stĺpca text_listok
                if (!isset($listok->text_listok)) {
                    $has_text_field = $wpdb->get_results("SHOW COLUMNS FROM `$table_name` LIKE 'text_listok'");
                    
                    if (empty($has_text_field)) {
                        // Stĺpec neexistuje, pridáme ho
                        $wpdb->query("ALTER TABLE `$table_name` ADD COLUMN `text_listok` VARCHAR(200) NOT NULL AFTER `nazov`");
                        error_log("SpravcaListkov: Pridaný chýbajúci stĺpec text_listok");
                        
                        // Nastavíme predvolenú hodnotu - rovnakú ako názov
                        $wpdb->update(
                            $table_name,
                            ['text_listok' => $listok->nazov],
                            ['id' => $id]
                        );
                        
                        // Znovu načítame lístok
                        $listok = $wpdb->get_row(
                            $wpdb->prepare(
                                "SELECT * FROM `$table_name` WHERE id = %d",
                                $id
                            )
                        );
                    }
                }
                
                error_log("SpravcaListkov: Lístok nájdený v $table_name");
                wp_send_json_success($listok);
                return;
            }
        }
        
        // Ak sme nenašli v novej tabuľke, skúsime starú tabuľku
        $alt_table = 'CL-typy_listkov';
        $alt_exists = $wpdb->get_var("SHOW TABLES LIKE '$alt_table'");
        error_log("SpravcaListkov: Kontrola existencie alternatívnej tabuľky $alt_table: " . ($alt_exists ? 'ÁNO' : 'NIE'));
        
        if ($alt_exists) {
            $listok = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM `$alt_table` WHERE id = %d",
                    $id
                )
            );
            
            if ($listok) {
                // Kontrola stĺpca text_listok v starej tabuľke
                if (!isset($listok->text_listok)) {
                    $has_text_field = $wpdb->get_results("SHOW COLUMNS FROM `$alt_table` LIKE 'text_listok'");
                    
                    if (empty($has_text_field)) {
                        // Pridáme vlastnosť text_listok do objektu s rovnakou hodnotou ako nazov
                        $listok->text_listok = $listok->nazov;
                    }
                }
                
                error_log("SpravcaListkov: Lístok nájdený v $alt_table");
                wp_send_json_success($listok);
                return;
            }
        }
        
        error_log("SpravcaListkov: Lístok s ID=$id nebol nájdený v žiadnej tabuľke");
        throw new \Exception('Lístok nebol nájdený');
    }

    public function zmazListok(): void {
        error_log('SpravcaListkov: Spustená metóda zmazListok()');
        
        // Kontrola AJAX nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cl_listky_nonce')) {
            error_log('SpravcaListkov: Neplatný nonce token');
            wp_send_json_error('Neplatný bezpečnostný token');
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Nedostatočné oprávnenia');
            return;
        }
        
        global $wpdb;
        $id = (int)$_POST['id'];
        
        $vysledok = $wpdb->delete(
            $wpdb->prefix . 'cl_typy_listkov',
            ['id' => $id],
            ['%d']
        );
        
        if ($vysledok !== false) {
            wp_send_json_success();
        } else {
            // Skúsme alternatívnu tabuľku
            $alt_table = 'CL-typy_listkov';
            $alt_exists = $wpdb->get_var("SHOW TABLES LIKE '{$alt_table}'");
            
            if ($alt_exists) {
                $alt_vysledok = $wpdb->delete(
                    $alt_table,
                    ['id' => $id],
                    ['%d']
                );
                
                if ($alt_vysledok !== false) {
                    wp_send_json_success();
                    return;
                }
            }
            
            wp_send_json_error('Chyba pri mazaní: ' . $wpdb->last_error);
        }
    }
}
