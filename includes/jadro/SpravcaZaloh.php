<?php
declare(strict_types=1);

namespace CL\jadro;

class SpravcaZaloh {
    private \wpdb $wpdb;
    private string $zalohy_adresar;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->zalohy_adresar = CL_PLUGIN_DIR . 'zalohy/';
    }

    public function ziskajPoslednuZalohu(): ?string {
        $zalohy = $this->ziskajZoznamZaloh();
        return !empty($zalohy) ? $zalohy[0]['datum'] : null;
    }

    public function ziskajZoznamZaloh(): array {
        $files = glob($this->zalohy_adresar . '*.sql');
        $zalohy = [];
        
        if ($files) {
            foreach ($files as $file) {
                $zalohy[] = [
                    'id' => basename($file, '.sql'),
                    'datum' => date('Y-m-d H:i:s', filemtime($file)),
                    'velkost' => filesize($file),
                    'typ' => 'Databáza'
                ];
            }
            usort($zalohy, fn($a, $b) => strtotime($b['datum']) - strtotime($a['datum']));
        }
        
        return $zalohy;
    }

    public function vytvorZalohu(): string {
        $nazov = date('Y-m-d_H-i-s') . '_backup.sql';
        $cesta = $this->zalohy_adresar . $nazov;

        try {
            // Tu pridáme logiku pre vytvorenie zálohy
            $tables = $this->wpdb->get_results("SHOW TABLES LIKE '{$this->wpdb->prefix}cl_%'", ARRAY_N);
            
            if (empty($tables)) {
                throw new \Exception('Nenašli sa žiadne tabuľky na zálohovanie');
            }

            $dump = "-- Záloha databázy " . date('Y-m-d H:i:s') . "\n\n";
            
            foreach ($tables as $table) {
                $dump .= $this->vytvorZalohuTabulky($table[0]);
            }

            if (!file_put_contents($cesta, $dump)) {
                throw new \Exception('Nepodarilo sa uložiť zálohu');
            }

            return $nazov;
        } catch (\Exception $e) {
            error_log('Chyba pri vytváraní zálohy: ' . $e->getMessage());
            throw $e;
        }
    }

    private function vytvorZalohuTabulky(string $table): string {
        $dump = "DROP TABLE IF EXISTS `$table`;\n";
        
        $create = $this->wpdb->get_row("SHOW CREATE TABLE `$table`", ARRAY_N);
        $dump .= $create[1] . ";\n\n";
        
        $rows = $this->wpdb->get_results("SELECT * FROM `$table`", ARRAY_A);
        if ($rows) {
            $dump .= "INSERT INTO `$table` VALUES\n";
            foreach ($rows as $i => $row) {
                $values = array_map(function($value) {
                    return is_null($value) ? 'NULL' : $this->wpdb->prepare("%s", $value);
                }, $row);
                $dump .= "(" . implode(',', $values) . ")";
                $dump .= $i < count($rows) - 1 ? "," : "";
                $dump .= "\n";
            }
            $dump .= ";\n\n";
        }
        
        return $dump;
    }
}
