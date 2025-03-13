<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

global $wpdb;
$listky = $wpdb->get_results(
    "SELECT * FROM `{$wpdb->prefix}cl_typy_listkov` ORDER BY id ASC"
);
?>

<div class="wrap">
    <h1>Správa lístkov</h1>
    
    <div class="cl-listky-form">
        <h2>Pridať nový lístok</h2>
        <form id="novy-listok-form" method="post">
            <?php wp_nonce_field('cl_listky_nonce', 'nonce'); ?>
            <div class="form-group">
                <label>Názov lístka:</label>
                <input type="text" name="nazov" required class="regular-text">
            </div>
            <div class="form-group">
                <label>Cena (€):</label>
                <input type="number" name="cena" step="0.01" min="0" required>
            </div>
            <button type="submit" class="button button-primary">Pridať lístok</button>
        </form>
    </div>

    <div class="cl-listky-zoznam">
        <h2>Existujúce lístky</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Názov</th>
                    <th>Cena</th>
                    <th>Stav</th>
                    <th>Vytvorené</th>
                    <th>Akcie</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($listky as $listok): ?>
                <tr data-id="<?php echo $listok->id; ?>">
                    <td><?php echo esc_html($listok->id); ?></td>
                    <td>
                        <span class="listok-nazov"><?php echo esc_html($listok->nazov); ?></span>
                        <input type="text" class="listok-nazov-edit" value="<?php echo esc_attr($listok->nazov); ?>" style="display: none;">
                    </td>
                    <td>
                        <span class="listok-cena"><?php echo number_format($listok->cena, 2); ?> €</span>
                        <input type="number" class="listok-cena-edit" value="<?php echo $listok->cena; ?>" step="0.01" min="0" style="display: none;">
                    </td>
                    <td>
                        <label class="switch">
                            <input type="checkbox" class="aktivny-switch" <?php echo $listok->aktivny ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </td>
                    <td><?php echo date('d.m.Y H:i', strtotime($listok->vytvorene)); ?></td>
                    <td>
                        <button class="button edit-listok">Upraviť</button>
                        <button class="button button-primary save-listok" style="display: none;">Uložiť</button>
                        <button class="button cancel-edit" style="display: none;">Zrušiť</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
