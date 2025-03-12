<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

$databaza = new \CL\jadro\Databaza();
$listky = $databaza->nacitaj(
    "SELECT * FROM `{$wpdb->prefix}cl_typy_listkov` WHERE aktivny = TRUE ORDER BY id ASC"
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
    </div>
</div>
