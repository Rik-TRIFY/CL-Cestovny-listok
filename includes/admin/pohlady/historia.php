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
    $where[] = "DATE(datum_predaja) >= %s";
    $params[] = $datum_od;
}
if ($datum_do) {
    $where[] = "DATE(datum_predaja) <= %s";
    $params[] = $datum_do;
}

$where_sql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Získanie predajov
$predaje = $db->nacitaj(
    "SELECT p.*, u.display_name as predajca
     FROM `{$wpdb->prefix}cl_predaj` p
     LEFT JOIN `{$wpdb->users}` u ON p.predajca_id = u.ID
     $where_sql
     ORDER BY p.datum_predaja DESC
     LIMIT %d OFFSET %d",
    array_merge($params, [$na_stranu, $offset])
);

// Celkový počet záznamov pre stránkovanie
$celkom = $db->nacitaj(
    "SELECT COUNT(*) as pocet 
     FROM `{$wpdb->prefix}cl_predaj`
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
                <th>Číslo predaja</th>
                <th>Dátum</th>
                <th>Predajca</th>
                <th>Suma</th>
                <th>Stav</th>
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
                    <td><?php echo esc_html($predaj['cislo_predaja']); ?></td>
                    <td><?php echo date('d.m.Y H:i', strtotime($predaj['datum_predaja'])); ?></td>
                    <td><?php echo esc_html($predaj['predajca']); ?></td>
                    <td><?php echo number_format($predaj['celkova_suma'], 2); ?> €</td>
                    <td>
                        <?php if ($predaj['storno']): ?>
                            <span class="cl-storno">Stornované</span>
                        <?php else: ?>
                            <span class="cl-aktivne">Aktívne</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="button cl-detail-predaja" data-id="<?php echo $predaj['id']; ?>">
                            Detail
                        </button>
                        <a href="<?php echo esc_url(CL_PLUGIN_URL . 'includes/predaj/listok-' . $predaj['cislo_predaja'] . '.html'); ?>" 
                           class="button" target="_blank">Zobraziť</a>
                        <button class="button cl-tlac-listok" data-cislo="<?php echo $predaj['cislo_predaja']; ?>">
                            Vytlačiť
                        </button>
                        <?php if (!$predaj['storno']): ?>
                        <button class="button cl-storno-predaja" data-id="<?php echo $predaj['id']; ?>">
                            Stornovať
                        </button>
                        <?php endif; ?>
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
