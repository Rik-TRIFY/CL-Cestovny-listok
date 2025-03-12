<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

$od = $_GET['od'] ?? date('Y-m-d', strtotime('-7 days'));
$do = $_GET['do'] ?? date('Y-m-d');
$stranka = isset($_GET['stranka']) ? (int)$_GET['stranka'] : 1;
$na_stranku = 50;

global $wpdb;
$where = $wpdb->prepare("WHERE DATE(datum_predaja) BETWEEN %s AND %s", $od, $do);
$celkom = $wpdb->get_var("SELECT COUNT(*) FROM `CL-predaj` $where");

$offset = ($stranka - 1) * $na_stranku;
$listky = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM `CL-predaj` 
     $where 
     ORDER BY datum_predaja DESC 
     LIMIT %d OFFSET %d",
    $na_stranku, $offset
));
?>

<div class="wrap">
    <h1>Prehľad predaných lístkov</h1>
    
    <div class="cl-filter-panel">
        <form method="get" class="cl-date-filter">
            <input type="hidden" name="page" value="cl-prehlad">
            <label>
                Od: <input type="date" name="od" value="<?php echo esc_attr($od); ?>">
            </label>
            <label>
                Do: <input type="date" name="do" value="<?php echo esc_attr($do); ?>">
            </label>
            <button type="submit" class="button">Filtrovať</button>
        </form>
        
        <div class="cl-stats">
            <p>Celkový počet: <?php echo $celkom; ?> lístkov</p>
        </div>
    </div>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Číslo lístka</th>
                <th>Dátum</th>
                <th>Predajca</th>
                <th>Suma</th>
                <th>Položky</th>
                <th>Akcie</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($listky as $listok): 
                $data = json_decode($listok->data_listka, true);
            ?>
                <tr>
                    <td><?php echo esc_html($listok->cislo_predaja); ?></td>
                    <td><?php echo date('d.m.Y H:i', strtotime($listok->datum_predaja)); ?></td>
                    <td><?php echo esc_html(get_userdata($listok->predajca_id)->display_name); ?></td>
                    <td><?php echo number_format($listok->celkova_suma, 2); ?> €</td>
                    <td>
                        <?php 
                        $polozky = array_map(function($p) {
                            return "{$p['nazov']} ({$p['pocet']}x)";
                        }, $data['polozky']);
                        echo esc_html(implode(', ', $polozky));
                        ?>
                    </td>
                    <td>
                        <button class="button zobraz-listok" 
                                data-url="<?php echo esc_url(CL_PREDAJ_DIR . 'listok-' . $listok->cislo_predaja . '.html'); ?>">
                            Zobraziť
                        </button>
                        <button class="button tlacit-listok" 
                                data-id="<?php echo esc_attr($listok->cislo_predaja); ?>">
                            Tlačiť
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <?php echo paginate_links([
        'base' => add_query_arg('stranka', '%#%'),
        'format' => '',
        'current' => $stranka,
        'total' => ceil($celkom / $na_stranku)
    ]); ?>
</div>

<!-- Modal pre zobrazenie lístka -->
<div id="listok-preview" class="cl-modal">
    <div class="cl-modal-content">
        <span class="cl-modal-close">&times;</span>
        <div id="listok-obsah"></div>
    </div>
</div>
