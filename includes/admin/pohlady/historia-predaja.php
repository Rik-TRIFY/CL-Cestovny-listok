<?php
declare(strict_types=1);

if (!defined('ABSPATH')) exit;

global $wpdb;
$databaza = new \CL\jadro\Databaza();

// Stránkovanie
$na_stranu = 20;
$aktualna_strana = isset($_GET['strana']) ? max(1, intval($_GET['strana'])) : 1;
$offset = ($aktualna_strana - 1) * $na_stranu;

// Upravený SQL dotaz - pridané id do GROUP BY
$predaje = $databaza->nacitaj(
    "SELECT 
        p.id,
        DATE(p.datum_predaja) as den,
        p.cislo_listka,
        GROUP_CONCAT(DISTINCT u.display_name) as predajcovia,
        p.celkova_suma,
        GROUP_CONCAT(
            CONCAT(t.nazov, ': ', pp.pocet, 'x')
            ORDER BY t.nazov
            SEPARATOR ', '
        ) as predane_listky
     FROM `{$wpdb->prefix}cl_predaj` p
     LEFT JOIN `{$wpdb->users}` u ON p.predajca_id = u.ID
     LEFT JOIN `{$wpdb->prefix}cl_polozky_predaja` pp ON p.id = pp.predaj_id
     LEFT JOIN `{$wpdb->prefix}cl_typy_listkov` t ON pp.typ_listka_id = t.id
     GROUP BY p.id, DATE(p.datum_predaja), p.cislo_listka, p.celkova_suma
     ORDER BY p.datum_predaja DESC
     LIMIT %d OFFSET %d",
    [$na_stranu, $offset]
);

// Celkový počet záznamov
$celkovy_pocet = $databaza->nacitaj(
    "SELECT COUNT(*) as pocet FROM `{$wpdb->prefix}cl_predaj`"
);

$pocet_stran = ceil(($celkovy_pocet[0]['pocet'] ?? 0) / $na_stranu);
?>

<div class="wrap">
    <h1>História predaja</h1>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Dátum</th>
                <th>Číslo lístka</th>
                <th>Predajca</th>
                <th>Položky</th>
                <th>Suma</th>
                <th>Akcie</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($predaje)): ?>
                <tr>
                    <td colspan="6" style="text-align: center;">Zatiaľ neboli zaznamenané žiadne predaje.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($predaje as $predaj): ?>
                    <tr>
                        <td><?php echo esc_html(wp_date('d.m.Y H:i', strtotime($predaj['den']))); ?></td>
                        <td><?php echo esc_html($predaj['cislo_listka']); ?></td>
                        <td><?php echo esc_html($predaj['predajcovia']); ?></td>
                        <td><?php echo esc_html($predaj['predane_listky']); ?></td>
                        <td><?php echo number_format($predaj['celkova_suma'], 2); ?> €</td>
                        <td>
                            <button class="button" onclick="tlacDennyReport('<?php echo esc_attr($predaj['den']); ?>')">
                                Tlačiť report
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ($pocet_stran > 1): ?>
        <div class="tablenav">
            <div class="tablenav-pages">
                <?php
                echo paginate_links([
                    'base' => add_query_arg('strana', '%#%'),
                    'format' => '',
                    'prev_text' => __('&laquo;'),
                    'next_text' => __('&raquo;'),
                    'total' => $pocet_stran,
                    'current' => $aktualna_strana
                ]);
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function tlacDennyReport(den) {
    jQuery.post(ajaxurl, {
        action: 'cl_tlac_denny_report',
        nonce: '<?php echo wp_create_nonce("cl_pos_nonce"); ?>',
        den: den
    }, function(response) {
        if (response.success) {
            const w = window.open('', 'PRINT', 'height=600,width=800');
            w.document.write(response.data.html);
            w.document.close();
            w.focus();
            setTimeout(function() {
                w.print();
                w.close();
            }, 250);
        }
    });
}
</script>
