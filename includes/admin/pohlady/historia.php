<?php
declare(strict_types=1);

/**
 * História predajov
 * 
 * Funkcionalita:
 * - Zobrazenie histórie predajov
 * - Filtrovanie podľa dátumu
 * - Detaily jednotlivých predajov
 * - Možnosť stornovania
 * - Opätovná tlač lístkov
 */

if (!defined('ABSPATH')) exit;

$db = new \CL\jadro\Databaza();

// Stránkovanie
$na_stranu = 20;
$strana = isset($_GET['strana']) ? (int)$_GET['strana'] : 1;
$offset = ($strana - 1) * $na_stranu;

$predaje = $db->nacitaj(
    "SELECT p.*, u.display_name as predajca
     FROM `{$wpdb->prefix}cl_predaj` p
     LEFT JOIN `{$wpdb->users}` u ON p.predajca_id = u.ID
     ORDER BY p.datum_predaja DESC
     LIMIT %d OFFSET %d",
    [$na_stranu, $offset]
);

$celkom_predajov = $db->nacitaj(
    "SELECT COUNT(*) as pocet FROM `{$wpdb->prefix}cl_predaj`"
)[0]['pocet'];

$pocet_stran = ceil($celkom_predajov / $na_stranu);
?>

<div class="wrap">
    <h1>História predaja</h1>
    
    <div class="cl-historia-filter">
        <input type="date" id="historia-datum-od" />
        <input type="date" id="historia-datum-do" />
        <button class="button" id="historia-filter">Filtrovať</button>
    </div>

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
                <td><?php echo esc_html($predaj['cislo_predaja']); ?></td>
                <td><?php echo esc_html(date('d.m.Y H:i', strtotime($predaj['datum_predaja']))); ?></td>
                <td><?php echo esc_html($predaj['predajca']); ?></td>
                <td><?php echo number_format($predaj['celkova_cena'], 2); ?> €</td>
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
                    <?php if (!$predaj['storno']): ?>
                    <button class="button cl-storno-predaja" data-id="<?php echo $predaj['id']; ?>">
                        Stornovať
                    </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($pocet_stran > 1): ?>
    <div class="cl-pagination">
        <?php for ($i = 1; $i <= $pocet_stran; $i++): ?>
            <a href="?page=cl-historia&strana=<?php echo $i; ?>" 
               class="button <?php echo $strana === $i ? 'button-primary' : ''; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>
