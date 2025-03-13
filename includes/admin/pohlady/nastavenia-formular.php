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
            <a href="?page=cl-nastavenia&tab=pos" 
               class="nav-tab <?php echo $active_tab === 'pos' ? 'nav-tab-active' : ''; ?>">
                POS Terminál
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
                                <?php
                                wp_editor(
                                    get_option('cl_nastavenia')['sablona_listka'] ?? $this->getDefaultTemplate(),
                                    'sablona-listka',
                                    [
                                        'media_buttons' => true,
                                        'textarea_name' => 'cl_nastavenia[sablona_listka]',
                                        'textarea_rows' => 20,
                                        'teeny' => false,
                                        'wpautop' => false,
                                        'tinymce' => [
                                            'verify_html' => false,
                                            'cleanup' => false,
                                            'forced_root_block' => false,
                                            'valid_styles' => '*[*]',
                                            'extended_valid_elements' => '*[*]',
                                            'remove_linebreaks' => false,
                                            'toolbar1' => 'formatselect,bold,italic,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,wp_adv,cl_variables',
                                            'toolbar2' => 'strikethrough,hr,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help',
                                            'content_css' => CL_ASSETS_URL . 'css/editor-style.css'
                                        ]
                                    ]
                                );
                                ?>
                                <p class="description">
                                    Použite premenné: {datum}, {cas}, {predajca}, {logo}, {polozky}, {suma}, {cislo_listka}<br>
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
                                    <button type="button" class="button button-primary" id="preview-print">Test tlače</button>
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

                case 'pos':
                    ?>
                    <div class="cl-grid-container">
                        <!-- POS Nastavenia -->
                        <div class="cl-grid-left">
                            <div class="cl-editor-box">
                                <h3>Nastavenia POS terminálu</h3>
                                <?php do_settings_sections('cl_sekcia_pos'); ?>
                            </div>
                            <div class="cl-editor-box">
                                <h3>Štýlovanie tlačidiel</h3>
                                <?php do_settings_sections('cl_sekcia_pos_style'); ?>
                            </div>
                        </div>
                        
                        <!-- Náhľad -->
                        <div class="cl-grid-right">
                            <div class="cl-preview-box">
                                <h3>Live náhľad POS terminálu</h3>
                                <div id="cl-pos-preview" class="cl-preview-window">
                                    <?php echo do_shortcode('[pos_terminal preview="true"]'); ?>
                                </div>
                                <div class="cl-preview-tools">
                                    <button type="button" class="button" id="pos-preview-refresh">Obnoviť náhľad</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                    break;
            }
            ?>
        </div>

        <?php submit_button('Uložiť nastavenia'); ?>
    </form>
</div>
