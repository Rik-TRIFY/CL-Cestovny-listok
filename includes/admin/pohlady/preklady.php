<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

$spravca = \CL\jadro\SpravcaPrekladov::ziskajInstanciu();
$preklady = $spravca->ziskajVsetky();

if (isset($_POST['ulozit_preklady']) && check_admin_referer('ulozit_preklady')) {
    foreach ($_POST['preklad'] as $kluc => $hodnota) {
        $spravca->uloz($kluc, sanitize_text_field($hodnota));
    }
    echo '<div class="notice notice-success"><p>Preklady boli úspešne uložené.</p></div>';
}
?>

<div class="wrap">
    <h1>Nastavenie prekladov</h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('ulozit_preklady'); ?>
        <table class="form-table">
            <tr>
                <th>Text tlačidla "Späť"</th>
                <td>
                    <input type="text" name="preklad[button_back]" 
                           value="<?php echo esc_attr($spravca->nacitaj('button_back', 'NASPÄŤ')); ?>" 
                           class="regular-text">
                </td>
            </tr>
            <tr>
                <th>Text tlačidla "Pridať do košíka"</th>
                <td>
                    <input type="text" name="preklad[button_add]" 
                           value="<?php echo esc_attr($spravca->nacitaj('button_add', 'PRIDAŤ DO KOŠÍKA')); ?>" 
                           class="regular-text">
                </td>
            </tr>
            <tr>
                <th>Text tlačidla "Košík"</th>
                <td>
                    <input type="text" name="preklad[button_cart]" 
                           value="<?php echo esc_attr($spravca->nacitaj('button_cart', 'KOŠÍK')); ?>" 
                           class="regular-text">
                </td>
            </tr>
            <tr>
                <th>Text tlačidla "Dokončiť"</th>
                <td>
                    <input type="text" name="preklad[button_checkout]" 
                           value="<?php echo esc_attr($spravca->nacitaj('button_checkout', 'DOKONČIŤ A TLAČIŤ')); ?>" 
                           class="regular-text">
                </td>
            </tr>
            <tr>
                <th>Text prázdneho košíka</th>
                <td>
                    <input type="text" name="preklad[cart_empty]" 
                           value="<?php echo esc_attr($spravca->nacitaj('cart_empty', 'Košík je prázdny')); ?>" 
                           class="regular-text">
                </td>
            </tr>
            <tr>
                <th>Nadpis košíka</th>
                <td>
                    <input type="text" name="preklad[cart_title]" 
                           value="<?php echo esc_attr($spravca->nacitaj('cart_title', 'Košík')); ?>" 
                           class="regular-text">
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <input type="submit" name="ulozit_preklady" class="button button-primary" value="Uložiť preklady">
        </p>
    </form>
</div>
