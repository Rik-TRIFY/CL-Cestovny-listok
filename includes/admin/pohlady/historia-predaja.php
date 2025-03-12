<?php
declare(strict_types=1);

if (!defined('ABSPATH')) exit;

$stranka = isset($_GET['stranka']) ? (int)$_GET['stranka'] : 1;
$na_stranku = 20;
$offset = ($stranka - 1) * $na_stranku;

global $wpdb;
$celkom = $wpdb->get_var("SELECT COUNT(*) FROM `CL-predaj`");
$predaje = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM `CL-predaj` ORDER BY datum_predaja DESC LIMIT %d OFFSET %d",
    $na_stranku, $offset
));
?>
<div class="wrap">
    <h1>História predaja</h1>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Číslo predaja</th>
                <th>Dátum</th>
                <th>Predajca</th>
                <th>Suma</th>
                <th>Stav</th>
                <th>Akcie</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($predaje as $predaj): ?>
                <tr>
                    <td><?php echo esc_html($predaj->cislo_predaja); ?></td>
                    <td><?php echo date('d.m.Y H:i', strtotime($predaj->datum_predaja)); ?></td>
                    <td><?php echo esc_html(get_userdata($predaj->predajca_id)->display_name); ?></td>
                    <td><?php echo number_format($predaj->celkova_suma, 2); ?> €</td>
                    <td><?php echo $predaj->storno ? 'Storno' : 'Aktívny'; ?></td>
                    <td>
                        <a href="<?php echo esc_url(CL_PLUGIN_URL . 'includes/predaj/listok-' . $predaj->cislo_predaja . '.html'); ?>" 
                           class="button" target="_blank">Zobraziť lístok</a>
                        <button class="button tlacit-listok" 
                                data-cislo="<?php echo esc_attr($predaj->cislo_predaja); ?>">Tlačiť</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <?php
    echo paginate_links([
        'base' => add_query_arg('stranka', '%#%'),
        'format' => '',
        'current' => $stranka,
        'total' => ceil($celkom / $na_stranku)
    ]);
    ?>
</div>
