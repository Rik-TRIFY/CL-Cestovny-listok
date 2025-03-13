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
            <a href="?page=cl-nastavenia&tab=databazy" 
               class="nav-tab <?php echo $active_tab === 'databazy' ? 'nav-tab-active' : ''; ?>">
                Správa databáz
            </a>
            <a href="?page=cl-nastavenia&tab=system" 
               class="nav-tab <?php echo $active_tab === 'system' ? 'nav-tab-active' : ''; ?>">
                Systémové nastavenia
            </a>
        </nav>

        <div class="tab-content">
            <?php
            switch ($active_tab) {
                case 'listok':
                    // Vzhľad lístka - existujúce polia (logo, hlavička, pätička)
                    do_settings_sections('cl_sekcia_listok');
                    break;
                    
                case 'predaj':
                    // Nastavenia predaja
                    do_settings_sections('cl_sekcia_predaj');
                    break;
                    
                case 'databazy':
                    do_settings_sections('cl_sekcia_databazy');
                    // Zobrazíme stav synchronizácie
                    $rozdiel = (new \CL\jadro\SpravcaDatabaz())->skontrolujRozdiely();
                    if ($rozdiel['ma_rozdiely']) {
                        echo '<div class="cl-db-warning">';
                        echo '<h3>Nájdené rozdiely v databázach</h3>';
                        echo '<table class="wp-list-table widefat">';
                        echo '<thead><tr><th>Tabuľka</th><th>Hlavná DB</th><th>Záložná DB</th><th>Rozdiel</th></tr></thead>';
                        echo '<tbody>';
                        foreach ($rozdiel['tabulky'] as $tabulka => $data) {
                            echo "<tr>";
                            echo "<td>{$tabulka}</td>";
                            echo "<td>{$data['primary']}</td>";
                            echo "<td>{$data['backup']}</td>";
                            echo "<td>" . ($data['primary'] - $data['backup']) . "</td>";
                            echo "</tr>";
                        }
                        echo '</tbody></table>';
                        echo '<div class="cl-db-actions">';
                        echo '<button class="button button-primary" id="sync-to-backup">Synchronizovať do záložnej DB</button>';
                        echo '<button class="button" id="sync-from-backup">Obnoviť zo záložnej DB</button>';
                        echo '</div>';
                        echo '</div>';
                    } else {
                        echo '<div class="cl-db-success">';
                        echo '<p>✓ Databázy sú synchronizované</p>';
                        echo '<p>Posledná kontrola: ' . get_option('cl_last_db_check', 'nikdy') . '</p>';
                        echo '</div>';
                    }
                    break;
                    
                case 'system':
                    // Systémové nastavenia
                    do_settings_sections('cl_sekcia_system');
                    break;
            }
            ?>
        </div>

        <?php submit_button(); ?>
    </form>

    <!-- Náhľad lístka - zobrazí sa len pri záložke "Vzhľad lístka" -->
    <?php if ($active_tab === 'listok'): ?>
    <div class="cl-nastavenia-preview">
        <h3>Náhľad lístka</h3>
        <div id="cl-listok-preview"></div>
        <p class="description">
            * Toto je orientačný náhľad. Skutočný vzhľad sa môže mierne líšiť v závislosti od použitej tlačiarne.
        </p>
    </div>
    <?php endif; ?>
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
