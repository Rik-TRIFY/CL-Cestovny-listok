<?php
declare(strict_types=1);

/**
 * Zobrazenie POS terminálu
 * 
 * Poskytuje:
 * - Grid/List zobrazenie lístkov
 * - Správu košíka
 * - Dokončenie predaja
 * - Históriu posledných predajov
 * - Responzívny dizajn
 */

if (!defined('ABSPATH')) exit;

global $wpdb;
$databaza = new \CL\jadro\Databaza();
$spravca = \CL\jadro\SpravcaNastaveni::ziskajInstanciu();

// Načítame nastavenia z našej DB
$nastavenia = [
    'pos_layout' => $spravca->nacitaj('pos_layout', 'grid'),
    'pos_columns' => $spravca->nacitaj('pos_columns', 4),
    'pos_width' => $spravca->nacitaj('pos_width', '375'),
    'pos_height' => $spravca->nacitaj('pos_height', '667')
];

// Načítame aktívne lístky
$listky = $wpdb->get_results(
    "SELECT * FROM `{$wpdb->prefix}cl_typy_listkov` WHERE aktivny = TRUE ORDER BY id ASC"
);

// Upravíme SQL dopyt aby používal správny prefix a kontroloval existenciu tabuliek
$posledne_predaje = [];
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}cl_polozky_predaja'");

if ($table_exists) {
    $posledne_predaje = $wpdb->get_results("
        SELECT p.*, GROUP_CONCAT(pp.pocet, 'x ', tl.nazov SEPARATOR ', ') as polozky
        FROM `{$wpdb->prefix}cl_predaj` p
        LEFT JOIN `{$wpdb->prefix}cl_polozky_predaja` pp ON p.id = pp.predaj_id
        LEFT JOIN `{$wpdb->prefix}cl_typy_listkov` tl ON pp.typ_listka_id = tl.id
        WHERE p.predajca_id = " . get_current_user_id() . "
        GROUP BY p.id
        ORDER BY p.datum_predaja DESC
        LIMIT 3"
    );
}
?>

<div class="cl-terminal-container">
    <div class="cl-terminal-header">
        <h2>POS Terminál</h2>
        <div class="cl-predajca-info">
            Predajca: <strong><?php echo esc_html(wp_get_current_user()->display_name); ?></strong>
        </div>
    </div>

    <div class="cl-terminal <?php echo esc_attr($nastavenia['pos_layout']); ?>">
        <div class="cl-listky" style="grid-template-columns: repeat(<?php echo (int)$nastavenia['pos_columns']; ?>, 1fr);">
            <?php foreach ($listky as $listok): ?>
                <button class="cl-listok" 
                        data-id="<?php echo esc_attr($listok->id); ?>"
                        data-nazov="<?php echo esc_attr($listok->nazov); ?>"
                        data-cena="<?php echo esc_attr($listok->cena); ?>">
                    <span class="nazov"><?php echo esc_html($listok->nazov); ?></span>
                    <span class="cena"><?php echo number_format((float)$listok->cena, 2); ?> €</span>
                </button>
            <?php endforeach; ?>
        </div>

        <div class="cl-kosik">
            <h2>Košík</h2>
            <div id="polozky-kosika"></div>
            <div class="cl-kosik-suma">
                Celkom: <span id="celkova-suma">0.00</span> €
            </div>
            <button id="dokoncit-predaj" class="button button-primary">Dokončiť predaj</button>
        </div>

        <!-- História posledných predajov -->
        <div class="cl-posledne-predaje">
            <h3>Posledné predaje</h3>
            <div class="cl-predaje-container">
                <?php foreach ($posledne_predaje as $predaj): ?>
                <div class="cl-predaj">
                    <div class="cl-predaj-info">
                        <span class="cl-predaj-cislo"><?php echo esc_html($predaj->cislo_predaja); ?></span>
                        <span class="cl-predaj-cas"><?php echo date('H:i', strtotime($predaj->datum_predaja)); ?></span>
                        <span class="cl-predaj-suma"><?php echo number_format($predaj->celkova_suma, 2); ?> €</span>
                    </div>
                    <div class="cl-predaj-polozky">
                        <?php echo esc_html($predaj->polozky); ?>
                    </div>
                    <button class="button cl-znovu-tlacit" data-id="<?php echo $predaj->id; ?>">
                        Vytlačiť znova
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
