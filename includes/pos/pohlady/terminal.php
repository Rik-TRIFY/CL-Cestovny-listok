<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

$databaza = new \CL\jadro\Databaza();
$listky = $databaza->nacitaj(
    "SELECT * FROM `{$wpdb->prefix}cl_typy_listkov` WHERE aktivny = TRUE ORDER BY id ASC"
);

// Načítame posledné 3 predaje
$posledne_predaje = $wpdb->get_results(
    "SELECT p.*, GROUP_CONCAT(CONCAT(tl.nazov, ' (', pp.pocet, 'x)') SEPARATOR ', ') as polozky
     FROM `{$wpdb->prefix}cl_predaj` p
     LEFT JOIN `{$wpdb->prefix}cl_polozky_predaja` pp ON p.id = pp.predaj_id
     LEFT JOIN `{$wpdb->prefix}cl_typy_listkov` tl ON pp.typ_listka_id = tl.id
     WHERE p.predajca_id = " . get_current_user_id() . "
     GROUP BY p.id
     ORDER BY p.datum_predaja DESC
     LIMIT 3"
);
?>

<div class="cl-terminal-container">
    <div class="cl-terminal-header">
        <h2>POS Terminál</h2>
        <div class="cl-predajca-info">
            Predajca: <strong><?php echo esc_html($predajca->display_name); ?></strong>
        </div>
    </div>
    
    <div class="cl-terminal">
        <div class="cl-listky">
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
