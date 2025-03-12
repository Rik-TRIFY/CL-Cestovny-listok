<?php
declare(strict_types=1);

if (!defined('ABSPATH')) exit;
?>
<div class="wrap">
    <h1>Nastavenia cestovných lístkov</h1>
    
    <form method="post" action="options.php">
        <?php settings_fields('cl_nastavenia'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">Logo</th>
                <td>
                    <input type="hidden" id="cl-logo-url" name="cl_logo_url" 
                           value="<?php echo esc_attr(get_option('cl_logo_url')); ?>">
                    <img class="cl-nahladok-loga" src="<?php echo esc_url(get_option('cl_logo_url')); ?>" 
                         style="max-width:200px;display:block;margin-bottom:10px;">
                    <input type="button" class="button cl-nahraj-logo" value="Vybrať logo">
                </td>
            </tr>
            <tr>
                <th scope="row">Hlavička lístka</th>
                <td>
                    <textarea name="cl_hlavicka" rows="5" class="large-text"><?php 
                        echo esc_textarea(get_option('cl_hlavicka')); 
                    ?></textarea>
                </td>
            </tr>
            <tr>
                <th scope="row">Pätička lístka</th>
                <td>
                    <textarea name="cl_paticka" rows="5" class="large-text"><?php 
                        echo esc_textarea(get_option('cl_paticka')); 
                    ?></textarea>
                </td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    </form>
</div>

<?php
$tab = $_GET['tab'] ?? 'general';
if ($tab === 'databazy'):
    $rozdiely = get_option('cl_db_differences', []);
    if (!empty($rozdiely)): ?>
        <div class="card">
            <h2>Rozdiely v databázach</h2>
            <p>Zistené <?php echo date('d.m.Y H:i', strtotime($rozdiely['timestamp'])); ?></p>
            
            <?php foreach ($rozdiely as $tabulka => $data): ?>
                <h3>Tabuľka: <?php echo esc_html($tabulka); ?></h3>
                <p>Počet záznamov:</p>
                <ul>
                    <li>Hlavná DB: <?php echo $data['primary_count']; ?></li>
                    <li>Záložná DB: <?php echo $data['backup_count']; ?></li>
                </ul>
                
                <div class="cl-db-actions">
                    <button class="button sync-db" data-source="primary">
                        Použiť dáta z hlavnej DB
                    </button>
                    <button class="button sync-db" data-source="backup">
                        Použiť dáta zo záložnej DB
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif;
endif;
?>
