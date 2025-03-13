<?php
declare(strict_types=1);

if (!defined('ABSPATH')) exit;
?>

<div class="wrap">
    <h1>Nastavenia</h1>
    
    <form method="post" action="options.php">
        <?php
        settings_fields('cl_nastavenia');
        
        // Záložky nastavení
        $active_tab = $_GET['tab'] ?? 'listok';
        ?>
        
        <nav class="nav-tab-wrapper">
            <a href="?page=cl-nastavenia&tab=listok" 
               class="nav-tab <?php echo $active_tab === 'listok' ? 'nav-tab-active' : ''; ?>">
                Nastavenie lístka
            </a>
            <a href="?page=cl-nastavenia&tab=predaj" 
               class="nav-tab <?php echo $active_tab === 'predaj' ? 'nav-tab-active' : ''; ?>">
                Nastavenie predaja
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
                    ?>
                    <div class="cl-grid-container">
                        <!-- HTML Editor -->
                        <div class="cl-grid-left">
                            <div class="cl-editor-box">
                                <h3>HTML šablóna lístka</h3>
                                <div class="cl-editor-toolbar">
                                    <button type="button" class="button" data-tag="b">Tučné</button>
                                    <button type="button" class="button" data-tag="i">Kurzíva</button>
                                    <button type="button" class="button" data-tag="center">Zarovnať na stred</button>
                                    <button type="button" class="button" data-var="{logo}">Logo</button>
                                    <button type="button" class="button" data-var="{datum}">Dátum</button>
                                    <button type="button" class="button" data-var="{cas}">Čas</button>
                                    <button type="button" class="button" data-var="{cislo_listka}">Číslo lístka</button>
                                    <button type="button" class="button" data-var="{predajca}">Predajca</button>
                                    <button type="button" class="button" data-var="{polozky}">Položky</button>
                                    <button type="button" class="button" data-var="{suma}">Suma</button>
                                </div>
                                <textarea name="cl_nastavenia[sablona_listka]" id="sablona-listka" class="large-text code" rows="20"><?php 
                                    echo esc_textarea(get_option('cl_nastavenia')['sablona_listka'] ?? $this->getDefaultTemplate()); 
                                ?></textarea>
                                <p class="description">
                                    Použite premenné v zložených zátvorkách: {datum}, {cas}, {predajca}, {logo}, {polozky}, {suma}, {cislo_listka}<br>
                                    Sekcia {polozky} bude automaticky nahradená položkami z predaja.
                                </p>
                            </div>
                            <?php do_settings_sections('cl_sekcia_listok'); ?>
                        </div>
                        
                        <!-- Náhľad -->
                        <div class="cl-grid-right">
                            <div class="cl-preview-box">
                                <h3>Náhľad lístka</h3>
                                <div id="cl-listok-preview" class="cl-preview-window">
                                    <!-- Tu sa zobrazí náhľad -->
                                </div>
                                <div class="cl-preview-tools">
                                    <button type="button" class="button" id="preview-refresh">Obnoviť náhľad</button>
                                    <label>
                                        <input type="checkbox" id="preview-auto-refresh" checked> 
                                        Automatický náhľad
                                    </label>
                                </div>
                                <p class="description">* Skutočný vzhľad sa môže mierne líšiť podľa typu tlačiarne</p>
                            </div>
                        </div>
                    </div>
                    <?php
                    break;
                
                case 'predaj':
                    do_settings_sections('cl_sekcia_predaj');
                    break;
                
                case 'databazy':
                    do_settings_sections('cl_sekcia_databazy');
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
                    do_settings_sections('cl_sekcia_system');
                    break;
            }
            ?>
        </div>

        <?php submit_button('Uložiť nastavenia'); ?>
    </form>
</div>
