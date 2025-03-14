<?php
declare(strict_types=1);

namespace CL\jadro;

class SpravcaPrekladov {
    private \wpdb $wpdb;
    private static ?self $instancia = null;
    private string $table_name;

    public const PREKLADY = [
        'button_back' => ['NASPÄŤ', 'Text tlačidla pre návrat'],
        'button_add' => ['PRIDAŤ DO KOŠÍKA', 'Text tlačidla pre pridanie do košíka'],
        'button_cart' => ['KOŠÍK', 'Text tlačidla košíka'],
        'button_checkout' => ['DOKONČIŤ A TLAČIŤ', 'Text tlačidla pre dokončenie'],
        'cart_empty' => ['Košík je prázdny', 'Text prázdneho košíka'],
        'cart_title' => ['Košík', 'Nadpis košíka'],
        // Nové preklady
        'total_sum' => ['SPOLU:', 'Text pre celkovú sumu'],
        'previous_tickets' => ['Predchádzajúce lístky', 'Nadpis sekcie predchádzajúcich lístkov'],
        'menu' => ['Menu', 'Text tlačidla menu'],
        'view_ticket' => ['Zobraziť', 'Text tlačidla pre zobrazenie lístka'],
        'print_ticket' => ['Tlačiť znova', 'Text tlačidla pre opätovnú tlač'],
        'no_previous_tickets' => ['Zatiaľ neboli pridané žiadne lístky', 'Text pri prázdnej histórii lístkov'],
        'close_shift' => ['Uzatvoriť zmenu', 'Text tlačidla pre uzatvorenie zmeny'],
        'confirm_close_shift' => ['Naozaj chcete uzatvoriť zmenu?', 'Potvrdzovacia správa pre uzatvorenie zmeny'],
        'confirm_reprint' => ['Naozaj chcete znovu vytlačiť tento lístok?', 'Potvrdzovacia správa pre opätovnú tlač'],
        // Pridané nové preklady
        'recently_added' => ['Naposledy pridané', 'Text pre sekciu naposledy pridaných položiek']
    ];

    public static function ziskajInstanciu(): self {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $this->wpdb->prefix . 'cl_preklady';
    }

    public function uloz(string $kluc, string $hodnota): bool {
        return (bool)$this->wpdb->replace(
            $this->table_name,
            [
                'kluc' => $kluc,
                'hodnota' => $hodnota
            ],
            ['%s', '%s']
        );
    }

    public function nacitaj(string $kluc, string $predvolena = ''): string {
        $hodnota = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT hodnota FROM {$this->table_name} WHERE kluc = %s",
            $kluc
        ));
        
        return $hodnota !== null ? $hodnota : $predvolena;
    }

    public function ziskajVsetky(): array {
        return $this->wpdb->get_results(
            "SELECT * FROM {$this->table_name}",
            ARRAY_A
        ) ?: [];
    }
}
