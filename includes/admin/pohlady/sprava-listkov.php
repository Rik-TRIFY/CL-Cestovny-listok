<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

global $wpdb;

// Debug pre kontrolu tabuľky
$table_name = $wpdb->prefix . 'cl_typy_listkov';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
echo '<!-- Kontrola tabuľky: ' . $table_name . ' - ' . ($table_exists ? 'Existuje' : 'Neexistuje') . ' -->';

// Kontrola, či existuje stĺpec 'poradie'
$check_column = $wpdb->get_results("SHOW COLUMNS FROM `{$wpdb->prefix}cl_typy_listkov` LIKE 'poradie'");
$has_order_column = !empty($check_column);

// Základný dotaz
if ($has_order_column) {
    $listky = $wpdb->get_results(
        "SELECT * FROM `{$wpdb->prefix}cl_typy_listkov` ORDER BY poradie ASC, nazov ASC"
    );
} else {
    $listky = $wpdb->get_results(
        "SELECT * FROM `{$wpdb->prefix}cl_typy_listkov` ORDER BY nazov ASC"
    );
    // Zároveň pridajme stĺpec do tabuľky
    $wpdb->query("ALTER TABLE `{$wpdb->prefix}cl_typy_listkov` ADD COLUMN `poradie` INT DEFAULT 0 AFTER `aktivny`");
}
?>

<div class="wrap">
    <h1>Správa položiek
        <button type="button" id="pridat-listok" class="page-title-action">Pridať lístok</button>
    </h1>
    
    <!-- Debug informácie -->
    <?php if (empty($listky)): ?>
    <div class="notice notice-warning">
        <p>Neboli nájdené žiadne lístky. Tabuľka: <?php echo $wpdb->prefix; ?>cl_typy_listkov</p>
        <?php 
            // Skontrolujme alternatívnu tabuľku
            $alt_table = 'CL-typy_listkov';
            $alt_exists = $wpdb->get_var("SHOW TABLES LIKE '{$alt_table}'");
            $alt_listky = [];
            
            if ($alt_exists) {
                $alt_listky = $wpdb->get_results("SELECT * FROM `{$alt_table}`");
                echo '<p>Alternatívna tabuľka ' . $alt_table . ' obsahuje ' . count($alt_listky) . ' záznamov.</p>';
                
                // Ak existuje alternatívna tabuľka, použime ju
                if (!empty($alt_listky)) {
                    $listky = $alt_listky;
                }
            }
        ?>
    </div>
    <?php endif; ?>
    
    <div class="cl-listky-zoznam">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Názov</th>
                    <th>Cena</th>
                    <th>Poradie</th>
                    <th style="width:80px">Stav</th>
                    <th style="min-width:300px">Akcie</th>
                </tr>
            </thead>
            <tbody id="listky-telo">
                <?php foreach ($listky as $listok): ?>
                <tr>
                    <td><?php echo esc_html($listok->nazov); ?></td>
                    <td><?php echo number_format((float)$listok->cena, 2); ?> €</td>
                    <td><?php echo (int)$listok->poradie; ?></td>
                    <td>
                        <span class="cl-stav <?php echo $listok->aktivny ? 'aktivny' : 'neaktivny'; ?>">
                            <?php echo $listok->aktivny ? 'Aktívny' : 'Neaktívny'; ?>
                        </span>
                    </td>
                    <td class="akcie">
                        <button type="button" class="button toggle-aktivny" data-id="<?php echo esc_attr($listok->id); ?>" data-aktivny="<?php echo $listok->aktivny ? '1' : '0'; ?>">
                            <?php echo $listok->aktivny ? 'Deaktivovať' : 'Aktivovať'; ?>
                        </button>
                        <button type="button" class="button upravit-listok" data-id="<?php echo esc_attr($listok->id); ?>">
                            Upraviť
                        </button>
                        <button type="button" class="button button-link-delete zmazat-listok" data-id="<?php echo esc_attr($listok->id); ?>">
                            Zmazať
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal pre pridanie/úpravu lístka -->
<div id="listok-modal" class="cl-modal">
    <div class="cl-modal-content">
        <span class="cl-modal-close">&times;</span>
        <h2 id="modal-title">Pridať nový lístok</h2>
        
        <form id="listok-formular">
            <?php wp_nonce_field('cl_listky_nonce', 'listok_nonce'); ?>
            <input type="hidden" name="id" id="listok-id">
            
            <div class="form-field">
                <label>Názov lístka:</label>
                <input type="text" name="nazov" id="listok-nazov" required>
                <p class="description">Interný názov pre správu a štatistiky</p>
            </div>
            
            <div class="form-field">
                <label>Text na lístku:</label>
                <input type="text" name="text_listok" id="listok-text" required>
                <p class="description">Text, ktorý sa zobrazí na vytlačenom lístku</p>
            </div>
            
            <div class="form-field">
                <label>Cena (€):</label>
                <input type="number" name="cena" id="listok-cena" step="0.01" min="0" required>
            </div>
            
            <div class="form-field">
                <label>Poradie zobrazenia:</label>
                <input type="number" name="poradie" id="listok-poradie" value="0" min="0" step="1">
                <p class="description">Určuje poradie zobrazovania v POS terminále (0 = štandardné)</p>
            </div>
            
            <div class="form-actions">
                <button type="button" class="button cl-modal-zrusit">Zrušiť</button>
                <button type="submit" class="button button-primary">Uložiť</button>
            </div>
        </form>
    </div>
</div>

<!-- Pridané inline JavaScript pre debugging -->
<script type="text/javascript">
console.log('Stranka sprava-listkov.php načítaná');
console.log('Nonce zo stránky:', typeof cl_admin !== 'undefined' ? cl_admin.nonce : 'cl_admin nie je definovaný!');
</script>
