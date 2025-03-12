<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

$rozdiely = get_option('cl_db_differences', []);
?>

<div class="wrap">
    <h1>Správa databáz</h1>
    
    <?php if (!empty($rozdiely)): ?>
        <div class="notice notice-warning">
            <h2>Zistené rozdiely v databázach</h2>
            <p>Dátum kontroly: <?php echo date('d.m.Y H:i', strtotime($rozdiely['timestamp'])); ?></p>
            
            <?php foreach ($rozdiely['rozdiely'] as $rozdiel): ?>
                <div class="cl-rozdiel-box">
                    <h3>Záznam ID: <?php echo esc_html($rozdiel['id']); ?></h3>
                    <p>Typ rozdielu: <?php echo esc_html($rozdiel['typ']); ?></p>
                    
                    <div class="cl-porovnanie">
                        <div class="cl-primary">
                            <h4>Hlavná databáza</h4>
                            <pre><?php echo esc_html(json_encode($rozdiel['primary_data'], JSON_PRETTY_PRINT)); ?></pre>
                        </div>
                        <div class="cl-backup">
                            <h4>Záložná databáza</h4>
                            <pre><?php echo esc_html(json_encode($rozdiel['backup_data'], JSON_PRETTY_PRINT)); ?></pre>
                        </div>
                    </div>
                    
                    <div class="cl-akcie">
                        <button class="button opravit-zaznam" 
                                data-id="<?php echo esc_attr($rozdiel['id']); ?>"
                                data-zdroj="primary">
                            Použiť dáta z hlavnej DB
                        </button>
                        <button class="button opravit-zaznam"
                                data-id="<?php echo esc_attr($rozdiel['id']); ?>"
                                data-zdroj="backup">
                            Použiť dáta zo záložnej DB
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="notice notice-success">
            <p>Databázy sú synchronizované, neboli nájdené žiadne rozdiely.</p>
        </div>
    <?php endif; ?>
</div>
