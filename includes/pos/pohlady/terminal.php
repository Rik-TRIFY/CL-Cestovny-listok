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
$spravca = \CL\jadro\SpravcaNastaveni::ziskajInstanciu();
$listky = $wpdb->get_results("SELECT * FROM `{$wpdb->prefix}cl_typy_listkov` WHERE aktivny = TRUE ORDER BY id ASC");

// Načítame nastavenia POS terminálu
$pos_layout = $spravca->nacitaj('pos_layout', 'grid');
$pos_columns = $spravca->nacitaj('pos_columns', '4');
$pos_button_size = $spravca->nacitaj('pos_button_size', 'medium');
?>

<div class="cl-pos-container">
    <!-- Hlavná obrazovka -->
    <div id="cl-hlavna-obrazovka" class="cl-screen active">
        <div class="cl-buttons-grid <?php 
            echo 'layout-' . esc_attr($pos_layout); ?> <?php 
            echo 'columns-' . esc_attr($pos_columns); ?> <?php
            echo 'size-' . esc_attr($pos_button_size); 
        ?>">
            <?php 
            // Prvých 9 tlačidiel pre lístky
            for ($i = 1; $i <= 9; $i++) {
                $listok = $listky[$i-1] ?? null;
                echo '<button class="cl-button' . (!$listok ? ' empty' : '') . '"' . 
                     ($listok ? ' data-id="'.$listok->id.'"' : ' disabled') . '>';
                echo $i;
                echo '</button>';
            }
            ?>
            
            <!-- Funkčné tlačidlá -->
            <button class="cl-button empty"></button>
            <button class="cl-button" id="cl-kosik">T</button>
            <button class="cl-button empty">K</button>
        </div>
    </div>

    <!-- Obrazovka košíka -->
    <div id="cl-kosik-obrazovka" class="cl-screen">
        <div class="cl-kosik-hlavicka">
            <button class="cl-back">&larr; Späť</button>
            <h2>Košík</h2>
        </div>
        <div class="cl-kosik-polozky"></div>
        <div class="cl-kosik-suma">
            Spolu: <span>0.00 €</span>
        </div>
        <button id="cl-dokoncit" class="cl-button-primary">Dokončiť a tlačiť</button>
    </div>
</div>
