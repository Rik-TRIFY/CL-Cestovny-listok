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

$db = new \CL\jadro\Databaza();
$dnesne_trzby = $db->nacitaj("SELECT SUM(celkova_cena) as suma FROM `{$wpdb->prefix}cl_predaj` WHERE DATE(datum_predaja) = CURDATE()")[0]['suma'] ?? 0;
$pocet_predanych = $db->nacitaj("SELECT COUNT(*) as pocet FROM `{$wpdb->prefix}cl_predaj` WHERE DATE(datum_predaja) = CURDATE()")[0]['pocet'] ?? 0;
?>

<div class="wrap">
    <h1>Cestovné lístky - Prehľad</h1>
    
    <div class="cl-dashboard-grid">
        <div class="cl-dashboard-widget">
            <h3>Dnešné tržby</h3>
            <div class="cl-widget-content">
                <span class="cl-big-number"><?php echo number_format($dnesne_trzby, 2); ?> €</span>
            </div>
        </div>

        <div class="cl-dashboard-widget">
            <h3>Počet predaných lístkov dnes</h3>
            <div class="cl-widget-content">
                <span class="cl-big-number"><?php echo $pocet_predanych; ?></span>
            </div>
        </div>
    </div>
</div>
