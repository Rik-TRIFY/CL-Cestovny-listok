<?php
declare(strict_types=1);

/**
 * Hlavný dashboard administrácie
 * 
 * Zobrazuje:
 * - Prehľad denných tržieb
 * - Počet predaných lístkov
 * - Rýchle štatistiky
 * - Grafy predaja
 * - Notifikácie a upozornenia
 */

if (!defined('ABSPATH')) exit;

global $wpdb;

// Získanie štatistík
$spravca = \CL\jadro\SpravcaNastaveni::ziskajInstanciu();
$databaza = new \CL\jadro\Databaza();

// 1. Dnešné tržby
$dnesne_trzby = $databaza->nacitaj(
    "SELECT COALESCE(SUM(celkova_suma), 0) as suma 
     FROM `{$wpdb->prefix}cl_predaj` 
     WHERE DATE(datum_predaja) = CURDATE()",
    []
);

$suma = isset($dnesne_trzby[0]['suma']) ? (float)$dnesne_trzby[0]['suma'] : 0;

// 2. Počet dnešných predajov
$pocet_predajov = $databaza->nacitaj(
    "SELECT COUNT(*) as pocet 
     FROM `{$wpdb->prefix}cl_predaj` 
     WHERE DATE(datum_predaja) = CURDATE()",
    []
);

$pocet = isset($pocet_predajov[0]['pocet']) ? (int)$pocet_predajov[0]['pocet'] : 0;

// 3. Top predávané lístky
$top_listky = $databaza->nacitaj(
    "SELECT t.nazov, COUNT(*) as pocet
     FROM `{$wpdb->prefix}cl_polozky_predaja` pp
     JOIN `{$wpdb->prefix}cl_predaj` p ON pp.predaj_id = p.id
     JOIN `{$wpdb->prefix}cl_typy_listkov` t ON pp.typ_listka_id = t.id
     WHERE DATE(p.datum_predaja) = CURDATE()
     GROUP BY t.id, t.nazov
     ORDER BY pocet DESC
     LIMIT 5",
    []
);

// 4. Posledné predaje
$posledne_predaje = $databaza->nacitaj(
    "SELECT p.*, u.display_name as predajca
     FROM `{$wpdb->prefix}cl_predaj` p
     JOIN `{$wpdb->users}` u ON p.predajca_id = u.ID
     ORDER BY p.datum_predaja DESC
     LIMIT 10",
    []
);
?>

<div class="wrap">
    <h1>Cestovné lístky - Prehľad</h1>
    
    <div class="cl-dashboard-grid">
        <div class="cl-dashboard-widget">
            <h3>Dnešné tržby</h3>
            <div class="cl-widget-content">
                <span class="cl-big-number"><?php echo number_format($suma, 2); ?> €</span>
            </div>
        </div>

        <div class="cl-dashboard-widget">
            <h3>Počet predaných lístkov dnes</h3>
            <div class="cl-widget-content">
                <span class="cl-big-number"><?php echo $pocet; ?></span>
            </div>
        </div>
    </div>
</div>
