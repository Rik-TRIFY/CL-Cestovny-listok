<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;
?>

<div class="cl-rozdiely-wrapper">
    <?php foreach ($rozdiely as $tabulka => $data): ?>
        <div class="cl-rozdiel-tabulka">
            <h3><?php echo esc_html($tabulka); ?></h3>
            <div class="cl-rozdiel-detail">
                <div class="cl-rozdiel-info">
                    <p>Primary DB: <?php echo count($data['primary_sample']); ?> záznamov</p>
                    <p>Backup DB: <?php echo count($data['backup_sample']); ?> záznamov</p>
                </div>
                
                <div class="cl-rozdiel-ukazka">
                    <h4>Ukážka rozdielov:</h4>
                    <div class="cl-db-compare">
                        <div class="cl-db-primary">
                            <h5>Primary DB</h5>
                            <pre><?php echo esc_html(json_encode($data['primary_sample'][0] ?? [], JSON_PRETTY_PRINT)); ?></pre>
                        </div>
                        <div class="cl-db-backup">
                            <h5>Backup DB</h5>
                            <pre><?php echo esc_html(json_encode($data['backup_sample'][0] ?? [], JSON_PRETTY_PRINT)); ?></pre>
                        </div>
                    </div>
                </div>
                
                <div class="cl-rozdiel-akcie">
                    <button class="button button-primary sync-db" 
                            data-tabulka="<?php echo esc_attr($tabulka); ?>"
                            data-zdroj="primary">
                        Použiť dáta z Primary DB
                    </button>
                    <button class="button sync-db"
                            data-tabulka="<?php echo esc_attr($tabulka); ?>"
                            data-zdroj="backup">
                        Použiť dáta z Backup DB
                    </button>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
