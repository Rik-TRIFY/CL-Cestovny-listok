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

// Načítáme nastavení a uživatele
$current_user = wp_get_current_user();
$user_initials = substr($current_user->display_name, 0, 2);
$listky = $wpdb->get_results("SELECT * FROM `{$wpdb->prefix}cl_typy_listkov` WHERE aktivny = TRUE ORDER BY id ASC");

// Načítame nastavenia POS terminálu
$pos_layout = $spravca->nacitaj('pos_layout', 'grid');
$pos_columns = $spravca->nacitaj('pos_columns', '4');
$pos_button_size = $spravca->nacitaj('pos_button_size', 'medium');

// Načítame nastavenia farieb a aplikujeme ich ako inline štýly
$bgColor = $spravca->nacitaj('pos_bg_color', '#f0f0f0');
$buttonColor = $spravca->nacitaj('pos_button_color', '#ffffff');
$textColor = $spravca->nacitaj('pos_text_color', '#000000');
$cartColor = $spravca->nacitaj('pos_cart_color', '#2271b1');
?>
<style>
:root {
    --pos-bg-color: <?php echo esc_html($bgColor); ?>;
    --pos-button-color: <?php echo esc_html($buttonColor); ?>;
    --pos-text-color: <?php echo esc_html($textColor); ?>;
    --pos-cart-color: <?php echo esc_html($cartColor); ?>;
}
</style>

<!-- Header -->
<header class="pos-header">
    <div class="pos-logo">
        Predaj lístkov
    </div>
    <div class="pos-user">
        <div class="pos-user-avatar"><?php echo esc_html($user_initials); ?></div>
        <span><?php echo esc_html($current_user->display_name); ?></span>
    </div>
</header>

<!-- Main Container -->
<div class="pos-container">
    <!-- Products Grid -->
    <div class="pos-products">
        <div class="pos-grid">
            <?php foreach ($listky as $listok): ?>
            <div class="pos-product" data-id="<?php echo esc_attr($listok->id); ?>">
                <div class="pos-product-name"><?php echo esc_html($listok->nazov); ?></div>
                <div class="pos-product-price"><?php echo number_format($listok->cena, 2); ?> €</div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Cart Sidebar -->
    <div class="pos-cart">
        <div class="pos-cart-header">
            <h2>Košík</h2>
        </div>
        
        <div class="pos-cart-items">
            <!-- Dynamicky plněno JavaScriptem -->
        </div>

        <div class="pos-cart-footer">
            <div class="pos-total">
                <span>Spolu:</span>
                <span class="pos-total-amount">0.00 €</span>
            </div>
            <button class="pos-checkout">Dokončiť a tlačiť</button>
        </div>
    </div>
</div>

<!-- Mobile Cart Toggle -->
<div class="pos-cart-toggle">🛒</div>
