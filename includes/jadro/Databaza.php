<?php
declare(strict_types=1);

namespace CL\jadro;

class Databaza {
    private $db_primary;
    private $db_backup;
    
    public function __construct() {
        global $wpdb;
        $this->db_primary = $wpdb;
        
        // Inicializácia záložnej DB
        $this->db_backup = new \wpdb(
            defined('DB_BACKUP_USER') ? DB_BACKUP_USER : DB_USER,
            defined('DB_BACKUP_PASSWORD') ? DB_BACKUP_PASSWORD : DB_PASSWORD,
            defined('DB_BACKUP_NAME') ? DB_BACKUP_NAME : DB_NAME,
            defined('DB_BACKUP_HOST') ? DB_BACKUP_HOST : DB_HOST
        );
    }

    public function init(): void {
        $this->vytvorTabulky($this->db_primary);
        $this->vytvorTabulky($this->db_backup);
    }

    private function vytvorTabulky($db): void {
        $charset_collate = $db->get_charset_collate();

        $sql = [
            "CREATE TABLE IF NOT EXISTS `CL-typy_listkov` (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                nazov varchar(100) NOT NULL,
                trieda varchar(50) DEFAULT NULL,
                skupina varchar(50) DEFAULT NULL,
                cena decimal(10,2) NOT NULL,
                aktivny boolean DEFAULT TRUE,
                poradie int(11) DEFAULT 0,
                vytvorene datetime DEFAULT CURRENT_TIMESTAMP,
                aktualizovane datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) $charset_collate;",

            "CREATE TABLE IF NOT EXISTS `CL-predaj` (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                cislo_predaja varchar(20) NOT NULL UNIQUE,
                predajca_id bigint(20) NOT NULL,
                celkova_suma decimal(10,2) NOT NULL,
                datum_predaja datetime DEFAULT CURRENT_TIMESTAMP,
                storno boolean DEFAULT FALSE,
                poznamka text DEFAULT NULL,
                PRIMARY KEY (id)
            ) $charset_collate;",

            "CREATE TABLE IF NOT EXISTS `CL-polozky_predaja` (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                predaj_id mediumint(9) NOT NULL,
                typ_listka_id mediumint(9) NOT NULL,
                pocet int(11) NOT NULL,
                cena_za_kus decimal(10,2) NOT NULL,
                PRIMARY KEY (id)
            ) $charset_collate;",

            // Pridáme stĺpec pre JSON dáta
            "ALTER TABLE `CL-predaj` 
             ADD COLUMN IF NOT EXISTS `data_listka` LONGTEXT 
             COMMENT 'JSON data pre generovanie lístka';"
        ];

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        foreach ($sql as $query) {
            dbDelta($query);
        }
    }

    public function vloz($tabulka, $data): int {
        try {
            // Pokus o zápis do primárnej DB
            $result = $this->db_primary->insert($tabulka, $data);
            $id = $this->db_primary->insert_id;
            
            // Ak sa podarí, zapíšeme aj do záložnej
            if ($result !== false) {
                $data['id'] = $id;
                $this->db_backup->insert($tabulka, $data);
            }
            
            return $id;
        } catch (\Exception $e) {
            // Ak zlyhá primárna, skúsime záložnú
            $result = $this->db_backup->insert($tabulka, $data);
            return $this->db_backup->insert_id;
        }
    }

    /**
     * Načíta dáta z databázy
     *
     * @param string $query SQL dotaz s možnosťou použitia placeholderov
     * @param array $params Parametre pre SQL dotaz
     * @param string $output_type Typ výstupných dát (ARRAY_A, OBJECT)
     * @return array|object[] Výsledky dotazu
     */
    public function nacitaj($query, $params = [], $output_type = ARRAY_A): array {
        global $wpdb;
        
        // Úprava dotazu - nahradenie CL- prefixu s prefixom WordPress
        $query = str_replace('`CL-', '`' . $wpdb->prefix . 'cl_', $query);
        
        // Pripravíme dotaz s parametrami
        if (!empty($params)) {
            $prepared_query = $this->db_primary->prepare($query, $params);
        } else {
            $prepared_query = $query;
        }
        
        // Získame dáta z primárnej DB
        $primary_data = $this->db_primary->get_results($prepared_query, $output_type);
        
        // Pre kontrolu integrity získame dáta aj zo záložnej DB, ale len v ARRAY_A formáte
        $backup_data = $this->db_backup->get_results($prepared_query, ARRAY_A);
        
        // Ak výstup nie je pole, pre porovnanie získame dáta aj v ARRAY_A formáte
        $primary_data_array = ($output_type !== ARRAY_A) ? 
            $this->db_primary->get_results($prepared_query, ARRAY_A) : $primary_data;
        
        // Hľadáme konkrétne rozdiely (len ak používame ARRAY_A výstup)
        $rozdiely = $this->najdiRozdiely($primary_data_array, $backup_data);
        
        if (!empty($rozdiely)) {
            // Uložíme len problémové záznamy
            update_option('cl_db_differences', [
                'timestamp' => current_time('mysql'),
                'query' => $query,
                'rozdiely' => $rozdiely
            ]);
            
            // Notifikácia pre admina (nezastaví systém)
            add_action('admin_notices', function() use ($rozdiely) {
                echo '<div class="notice notice-warning"><p>Zistené rozdiely v ' . 
                     count($rozdiely) . ' záznamoch medzi databázami. ' .
                     '<a href="' . admin_url('admin.php?page=cl-settings&tab=databazy') . 
                     '">Zobraziť detaily</a></p></div>';
            });
        }

        // Vždy vrátime dáta z primárnej DB
        return $primary_data !== null ? $primary_data : [];
    }

    /**
     * Načíta dáta z databázy ako asociatívne pole
     *
     * @param string $query SQL dotaz s možnosťou použitia placeholderov
     * @param array $params Parametre pre SQL dotaz
     * @return array Výsledky dotazu ako asociatívne pole
     */
    public function nacitajPole(string $query, array $params = []): array {
        return $this->nacitaj($query, $params, ARRAY_A);
    }

    /**
     * Načíta dáta z databázy ako objekty
     *
     * @param string $query SQL dotaz s možnosťou použitia placeholderov
     * @param array $params Parametre pre SQL dotaz
     * @return object[] Výsledky dotazu ako objekty
     */
    public function nacitajObjekty(string $query, array $params = []): array {
        return $this->nacitaj($query, $params, OBJECT);
    }

    private function najdiRozdiely(array $primary, array $backup): array {
        $rozdiely = [];
        
        // Indexujeme záznamy podľa ID pre rýchlejšie porovnanie
        $backup_index = array_column($backup, NULL, 'id');
        
        foreach ($primary as $zaznam) {
            $id = $zaznam['id'];
            if (!isset($backup_index[$id])) {
                // Záznam chýba v záložnej DB
                $rozdiely[] = [
                    'id' => $id,
                    'typ' => 'chyba_v_backup',
                    'primary_data' => $zaznam,
                    'backup_data' => null
                ];
                continue;
            }
            
            if ($zaznam !== $backup_index[$id]) {
                // Záznam sa líši
                $rozdiely[] = [
                    'id' => $id,
                    'typ' => 'rozdiel',
                    'primary_data' => $zaznam,
                    'backup_data' => $backup_index[$id]
                ];
            }
        }
        
        // Kontrola záznamov, ktoré sú v backup ale nie v primary
        foreach ($backup as $zaznam) {
            $id = $zaznam['id'];
            if (!array_column($primary, 'id', 'id')[$id]) {
                $rozdiely[] = [
                    'id' => $id,
                    'typ' => 'chyba_v_primary',
                    'primary_data' => null,
                    'backup_data' => $zaznam
                ];
            }
        }
        
        return $rozdiely;
    }

    public function opravZaznam(int $id, string $tabulka, string $zdroj = 'primary'): void {
        if (!current_user_can('manage_options')) {
            throw new \Exception('Nedostatočné oprávnenia');
        }

        // Získame dáta zo zvolenej DB
        $source_db = $zdroj === 'primary' ? $this->db_primary : $this->db_backup;
        $target_db = $zdroj === 'primary' ? $this->db_backup : $this->db_primary;

        // Získame konkrétny záznam
        $data = $source_db->get_row(
            $source_db->prepare("SELECT * FROM `$tabulka` WHERE id = %d", $id),
            ARRAY_A
        );

        if (!$data) {
            throw new \Exception('Záznam neexistuje');
        }

        // Aktualizujeme/vložíme záznam do cieľovej DB
        $target_db->replace($tabulka, $data);

        // Vyčistíme notifikáciu ak nie sú ďalšie rozdiely
        $rozdiely = get_option('cl_db_differences', []);
        unset($rozdiely['rozdiely'][$id]);
        
        if (empty($rozdiely['rozdiely'])) {
            delete_option('cl_db_differences');
        } else {
            update_option('cl_db_differences', $rozdiely);
        }
    }

    private function porovnajData($primary, $backup): bool {
        if (!is_array($primary) || !is_array($backup)) {
            return false;
        }

        // Porovnáme počet záznamov
        if (count($primary) !== count($backup)) {
            return false;
        }

        // Porovnáme obsah
        foreach ($primary as $key => $value) {
            if (!isset($backup[$key]) || $backup[$key] !== $value) {
                return false;
            }
        }

        return true;
    }

    public function synchronizujDatabazy(): void {
        // Získame všetky tabuľky
        $tabulky = ['CL-typy_listkov', 'CL-predaj', 'CL-polozky_predaja'];

        foreach ($tabulky as $tabulka) {
            // Získame dáta z primárnej DB
            $primary_data = $this->db_primary->get_results("SELECT * FROM `$tabulka`", ARRAY_A);

            // Vyčistíme záložnú tabuľku
            $this->db_backup->query("TRUNCATE TABLE `$tabulka`");

            // Vložíme dáta do záložnej DB
            foreach ($primary_data as $row) {
                $this->db_backup->insert($tabulka, $row);
            }
        }

        $this->zapisDoLogu('SYNCHRONIZACIA', [
            'datum' => current_time('mysql'),
            'tabulky' => $tabulky
        ]);
    }

    private function zapisDoLogu($typ, $data): void {
        $spravca = new SpravcaSuborov();
        $spravca->zapisDoLogu('DB_' . $typ, $data);
    }

    public function kontrolujIntegrituDatabaz(): array {
        $rozdiely = [];
        $tabulky = ['CL-typy_listkov', 'CL-predaj', 'CL-polozky_predaja'];
        
        foreach ($tabulky as $tabulka) {
            $primary = $this->db_primary->get_results("SELECT * FROM `$tabulka` ORDER BY id", ARRAY_A);
            $backup = $this->db_backup->get_results("SELECT * FROM `$tabulka` ORDER BY id", ARRAY_A);
            
            if ($primary !== $backup) {
                $rozdiely[$tabulka] = [
                    'primary_count' => count($primary),
                    'backup_count' => count($backup),
                    'timestamp' => current_time('mysql')
                ];
            }
        }
        
        return $rozdiely;
    }

    public function opravIntegritu(): void {
        $tabulky = ['CL-typy_listkov', 'CL-predaj', 'CL-polozky_predaja'];
        
        $this->db_backup->query('START TRANSACTION');
        try {
            foreach ($tabulky as $tabulka) {
                // Získame dáta z primárnej DB
                $data = $this->db_primary->get_results("SELECT * FROM `$tabulka`", ARRAY_A);
                
                // Vyčistíme a naplníme záložnú DB
                $this->db_backup->query("TRUNCATE TABLE `$tabulka`");
                foreach ($data as $row) {
                    $this->db_backup->insert($tabulka, $row);
                }
            }
            $this->db_backup->query('COMMIT');
        } catch (\Exception $e) {
            $this->db_backup->query('ROLLBACK');
            throw $e;
        }
    }

    public function skontrolujRozdiely(): array {
        $rozdiely = [];
        $tabulky = ['CL-typy_listkov', 'CL-predaj', 'CL-polozky_predaja'];
        
        foreach ($tabulky as $tabulka) {
            $primary = $this->db_primary->get_results("SELECT * FROM `$tabulka` ORDER BY id", ARRAY_A);
            $backup = $this->db_backup->get_results("SELECT * FROM `$tabulka` ORDER BY id", ARRAY_A);
            
            if ($primary !== $backup) {
                $rozdiely[$tabulka] = [
                    'primary_count' => count($primary),
                    'backup_count' => count($backup),
                    'primary_sample' => array_slice($primary, 0, 5),
                    'backup_sample' => array_slice($backup, 0, 5),
                    'timestamp' => current_time('mysql')
                ];
            }
        }
        
        if (!empty($rozdiely)) {
            update_option('cl_db_differences', $rozdiely);
        }
        
        return $rozdiely;
    }

    public function synchronizujPodlaVyberu(string $zdroj = 'primary'): void {
        if (!current_user_can('manage_options')) {
            throw new \Exception('Nedostatočné oprávnenia');
        }

        $tabulky = ['CL-typy_listkov', 'CL-predaj', 'CL-polozky_predaja'];
        
        if ($zdroj === 'primary') {
            $source_db = $this->db_primary;
            $target_db = $this->db_backup;
        } else {
            $source_db = $this->db_backup;
            $target_db = $this->db_primary;
        }

        foreach ($tabulky as $tabulka) {
            $data = $source_db->get_results("SELECT * FROM `$tabulka`", ARRAY_A);
            $target_db->query("TRUNCATE TABLE `$tabulka`");
            
            foreach ($data as $row) {
                $target_db->insert($tabulka, $row);
            }
        }

        delete_option('cl_db_differences');
    }

    public function zmaz(string $tabulka, array $where): bool {
        try {
            // Zmazanie z primárnej DB
            $result_primary = $this->db_primary->delete($tabulka, $where);
            
            // Zmazanie zo záložnej DB
            $result_backup = $this->db_backup->delete($tabulka, $where);
            
            if ($result_primary === false || $result_backup === false) {
                throw new \Exception('Chyba pri mazaní záznamu');
            }
            
            return true;
        } catch (\Exception $e) {
            $this->zapisDoLogu('DELETE_ERROR', [
                'tabulka' => $tabulka,
                'where' => $where,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function ziskajStatistiky(string $od, string $do): array {
        $statistiky = $this->nacitaj(
            "SELECT 
                DATE(datum_predaja) as den,
                COUNT(*) as pocet_predajov,
                SUM(celkova_suma) as celkovy_obrat,
                COUNT(DISTINCT predajca_id) as pocet_predajcov
            FROM `CL-predaj`
            WHERE DATE(datum_predaja) BETWEEN %s AND %s
            AND storno = FALSE
            GROUP BY DATE(datum_predaja)
            ORDER BY den ASC",
            [$od, $do]
        );

        return [
            'denne_statistiky' => $statistiky,
            'celkom' => [
                'pocet_predajov' => array_sum(array_column($statistiky, 'pocet_predajov')),
                'celkovy_obrat' => array_sum(array_column($statistiky, 'celkovy_obrat')),
                'priemer_na_den' => count($statistiky) ? 
                    array_sum(array_column($statistiky, 'celkovy_obrat')) / count($statistiky) : 0
            ]
        ];
    }
}
