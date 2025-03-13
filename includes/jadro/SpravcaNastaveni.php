<?php
declare(strict_types=1);

/**
 * Správca nastavení systému
 * 
 * Singleton trieda pre:
 * - Ukladanie nastavení do vlastnej DB tabuľky
 * - Načítanie nastavení
 * - Správu perzistentných dát
 * - Automatickú konverziu JSON hodnôt
 */

namespace CL\jadro;

class SpravcaNastaveni {
    private \wpdb $wpdb;
    private static ?self $instancia = null;

    public static function ziskajInstanciu(): self {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public function uloz(string $name, $value): bool {
        try {
            $this->wpdb->replace(
                $this->wpdb->prefix . 'cl_nastavenia',
                [
                    'option_name' => $name,
                    'option_value' => is_array($value) ? json_encode($value) : $value
                ],
                ['%s', '%s']
            );
            return true;
        } catch (\Exception $e) {
            error_log('Chyba pri ukladaní nastavenia: ' . $e->getMessage());
            return false;
        }
    }

    public function nacitaj(string $name, $default = null) {
        $value = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT option_value FROM {$this->wpdb->prefix}cl_nastavenia WHERE option_name = %s",
            $name
        ));

        if ($value === null) {
            return $default;
        }

        // Skúsime dekódovať JSON
        $decoded = json_decode($value, true);
        return (json_last_error() === JSON_ERROR_NONE) ? $decoded : $value;
    }

    public function zmaz(string $name): bool {
        return (bool)$this->wpdb->delete(
            $this->wpdb->prefix . 'cl_nastavenia',
            ['option_name' => $name],
            ['%s']
        );
    }

    public function existuje(string $name): bool {
        return (bool)$this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->wpdb->prefix}cl_nastavenia WHERE option_name = %s",
            $name
        ));
    }

    public function nacitajVsetkyPrefixom(string $prefix): array {
        $query = $this->wpdb->prepare(
            "SELECT option_name, option_value FROM {$this->wpdb->prefix}cl_nastavenia WHERE option_name LIKE %s",
            $prefix . '_%'
        );
        
        $vysledky = $this->wpdb->get_results($query, ARRAY_A);
        $nastavenia = [];
        
        foreach ($vysledky as $vysledok) {
            $key = str_replace($prefix . '_', '', $vysledok['option_name']);
            $value = $vysledok['option_value'];
            
            // Skúsime dekódovať JSON
            $decoded = json_decode($value, true);
            $nastavenia[$key] = (json_last_error() === JSON_ERROR_NONE) ? $decoded : $value;
        }
        
        return $nastavenia;
    }

    // Pridáme metódu pre zmazanie všetkých nastavení s prefixom
    public function zmazVsetkyPrefixom(string $prefix): bool {
        return (bool)$this->wpdb->query($this->wpdb->prepare(
            "DELETE FROM {$this->wpdb->prefix}cl_nastavenia WHERE option_name LIKE %s",
            $prefix . '_%'
        ));
    }
}
