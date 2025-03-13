<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

global $wpdb;
$listky = $wpdb->get_results(
    "SELECT * FROM `{$wpdb->prefix}cl_typy_listkov` ORDER BY nazov ASC"
);
?>

<div class="wrap">
    <h1>Správa položiek
        <button id="pridat-listok" class="page-title-action">Pridať lístok</button>
    </h1>
    
    <div class="cl-listky-zoznam">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Názov</th>
                    <th>Cena</th>
                    <th>Stav</th>
                    <th>Akcie</th>
                </tr>
            </thead>
            <tbody id="listky-telo">
                <?php foreach ($listky as $listok): ?>
                <tr>
                    <td><?php echo esc_html($listok->nazov); ?></td>
                    <td><?php echo number_format($listok->cena, 2); ?> €</td>
                    <td>
                        <span class="cl-stav <?php echo $listok->aktivny ? 'aktivny' : 'neaktivny'; ?>">
                            <?php echo $listok->aktivny ? 'Aktívny' : 'Neaktívny'; ?>
                        </span>
                    </td>
                    <td class="akcie">
                        <button class="button toggle-aktivny" data-id="<?php echo $listok->id; ?>" data-aktivny="<?php echo $listok->aktivny ? '1' : '0'; ?>">
                            <?php echo $listok->aktivny ? 'Deaktivovať' : 'Aktivovať'; ?>
                        </button>
                        <button class="button upravit-listok" data-id="<?php echo $listok->id; ?>">
                            Upraviť
                        </button>
                        <button class="button button-link-delete zmazat-listok" data-id="<?php echo $listok->id; ?>">
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
            <?php wp_nonce_field('cl_listky_nonce'); ?>
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
            
            <div class="form-actions">
                <button type="button" class="button cl-modal-zrusit">Zrušiť</button>
                <button type="submit" class="button button-primary">Uložiť</button>
            </div>
        </form>
    </div>
</div>
