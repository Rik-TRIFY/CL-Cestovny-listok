<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;
?>

<div class="wrap">
    <h1>Štatistiky predaja</h1>
    
    <div class="cl-stats-container">
        <div class="cl-stats-filters">
            <select id="cl-stats-obdobie">
                <option value="den">Deň</option>
                <option value="tyzden">Týždeň</option>
                <option value="mesiac">Mesiac</option>
                <option value="rok">Rok</option>
            </select>
            
            <input type="date" id="cl-stats-datum-od" />
            <input type="date" id="cl-stats-datum-do" />
            
            <button class="button button-primary" id="cl-stats-filter">Filtrovať</button>
        </div>
        
        <div id="cl-stats-graf"></div>
        <div id="cl-stats-tabulka"></div>
    </div>
</div>
