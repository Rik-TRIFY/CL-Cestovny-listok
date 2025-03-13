<?php
declare(strict_types=1);

if (!defined('ABSPATH')) exit;
?>

<div class="wrap">
    <h1>Nastavenia cestovných lístkov</h1>
    
    <form method="post" action="options.php">
        <?php
        settings_fields('cl_nastavenia');
        
        // Záložky nastavení
        $active_tab = $_GET['tab'] ?? 'listok';
        ?>
        
        <nav class="nav-tab-wrapper">
            <a href="?page=cl-nastavenia&tab=listok" 
               class="nav-tab <?php echo $active_tab === 'listok' ? 'nav-tab-active' : ''; ?>">
                Vzhľad lístka
            </a>
            <a href="?page=cl-nastavenia&tab=predaj" 
               class="nav-tab <?php echo $active_tab === 'predaj' ? 'nav-tab-active' : ''; ?>">
                Nastavenia predaja
            </a>
            <a href="?page=cl-nastavenia&tab=statistiky" 
               class="nav-tab <?php echo $active_tab === 'statistiky' ? 'nav-tab-active' : ''; ?>">
                Štatistiky
            </a>
            <a href="?page=cl-nastavenia&tab=zalohy" 
               class="nav-tab <?php echo $active_tab === 'zalohy' ? 'nav-tab-active' : ''; ?>">
                Zálohovanie
            </a>
            <a href="?page=cl-nastavenia&tab=notifikacie" 
               class="nav-tab <?php echo $active_tab === 'notifikacie' ? 'nav-tab-active' : ''; ?>">
                Notifikácie
            </a>
        </nav>

        <div class="tab-content">
            <?php
            switch ($active_tab) {
                case 'listok':
                    do_settings_sections('cl_sekcia_listok');
                    break;
                case 'predaj':
                    do_settings_sections('cl_sekcia_predaj');
                    break;
                case 'statistiky':
                    do_settings_sections('cl_sekcia_statistiky');
                    break;
                case 'zalohy':
                    do_settings_sections('cl_sekcia_zalohy');
                    break;
                case 'notifikacie':
                    do_settings_sections('cl_sekcia_notifikacie');
                    break;
            }
            ?>
        </div>

        <?php submit_button(); ?>
    </form>

    <!-- Náhľad lístka -->
    <div class="cl-nastavenia-preview">
        <h3>Náhľad lístka</h3>
        <div id="cl-listok-preview">
            <!-- JavaScript vloží náhľad lístka -->
        </div>
    </div>
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
