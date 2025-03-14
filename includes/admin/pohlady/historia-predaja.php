<?php
declare(strict_types=1);

if (!defined('ABSPATH')) exit;

$db = new \CL\jadro\Databaza();

// Filtrovanie podľa dátumu
$datum_od = $_GET['datum_od'] ?? '';
$datum_do = $_GET['datum_do'] ?? '';
$strana = isset($_GET['strana']) ? (int)$_GET['strana'] : 1;
$na_stranu = 20;
$offset = ($strana - 1) * $na_stranu;

// SQL podmienky pre filtrovanie
$where = [];
$params = [];

if ($datum_od) {
    $where[] = "DATE(p.datum_predaja) >= %s";
    $params[] = $datum_od;
}
if ($datum_do) {
    $where[] = "DATE(p.datum_predaja) <= %s";
    $params[] = $datum_do;
}

$where_sql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Získanie predajov - upravený SQL dotaz
$predaje = $db->nacitaj(
    "SELECT 
        DATE(p.datum_predaja) as den,
        p.cislo_predaja as cislo_listka,
        GROUP_CONCAT(DISTINCT u.display_name) as predajcovia,
        SUM(p.celkova_suma) as celkova_suma,
        GROUP_CONCAT(
            CONCAT(t.nazov, ': ', COUNT(pp.id), 'x')
            ORDER BY t.nazov
            SEPARATOR ', '
        ) as predane_listky
     FROM `{$wpdb->prefix}cl_predaj` p
     LEFT JOIN `{$wpdb->users}` u ON p.predajca_id = u.ID
     LEFT JOIN `{$wpdb->prefix}cl_polozky_predaja` pp ON p.id = pp.predaj_id
     LEFT JOIN `{$wpdb->prefix}cl_typy_listkov` t ON pp.typ_listka_id = t.id
     $where_sql
     GROUP BY DATE(p.datum_predaja)
     ORDER BY den DESC
     LIMIT %d OFFSET %d",
    array_merge($params, [$na_stranu, $offset])
);

// Celkový počet záznamov pre stránkovanie
$celkom = $db->nacitaj(
    "SELECT COUNT(*) as pocet 
     FROM `{$wpdb->prefix}cl_predaj` p
     $where_sql",
    $params
)[0]['pocet'];

$pocet_stran = ceil($celkom / $na_stranu);
?>

<div class="wrap">
    <h1>História predaja</h1>
    
    <!-- Filter -->
    <div class="cl-historia-filter">
        <input type="date" id="historia-datum-od" value="<?php echo esc_attr($datum_od); ?>" />
        <input type="date" id="historia-datum-do" value="<?php echo esc_attr($datum_do); ?>" />
        <button class="button" id="historia-filter">Filtrovať</button>
        <button class="button" id="historia-reset">Zrušiť filter</button>
    </div>

    <!-- Tabuľka predajov -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Číslo lístka</th>
                <th>Dátum</th>
                <th>Predajcovia</th>
                <th>Suma</th>
                <th>Predané lístky</th>
                <th>Akcie</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($predaje)): ?>
                <tr>
                    <td colspan="6" style="text-align: center;">Neboli nájdené žiadne záznamy.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($predaje as $predaj): ?>
                <tr>
                    <td><?php echo esc_html($predaj['cislo_listka']); ?></td>
                    <td><?php echo date('d.m.Y', strtotime($predaj['den'])); ?></td>
                    <td><?php echo esc_html(str_replace(',', ', ', $predaj['predajcovia'])); ?></td>
                    <td><?php echo number_format($predaj['celkova_suma'], 2); ?> €</td>
                    <td><?php echo esc_html($predaj['predane_listky']); ?></td>
                    <td>
                        <button class="button cl-detail-predaja" data-den="<?php echo $predaj['den']; ?>">
                            Detail dňa
                        </button>
                        <button class="button cl-tlac-report" data-den="<?php echo $predaj['den']; ?>">
                            Tlačiť report
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ($pocet_stran > 1): ?>
    <div class="cl-pagination">
        <?php for ($i = 1; $i <= $pocet_stran; $i++): ?>
            <a href="?page=cl-historia&strana=<?php echo $i; ?>&datum_od=<?php echo $datum_od; ?>&datum_do=<?php echo $datum_do; ?>" 
               class="button <?php echo $strana === $i ? 'button-primary' : ''; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>
