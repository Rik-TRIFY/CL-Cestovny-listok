<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;
?>

<div class="wrap">
    <h1>Export dát</h1>
    
    <div class="cl-export-container">
        <form method="post">
            <?php wp_nonce_field('cl_export_nonce'); ?>
            
            <h3>Export predajov</h3>
            <div class="cl-export-options">
                <label>
                    <input type="radio" name="format" value="csv" checked /> CSV
                </label>
                <label>
                    <input type="radio" name="format" value="xlsx" /> XLSX
                </label>
                <label>
                    <input type="radio" name="format" value="pdf" /> PDF
                </label>
            </div>
            
            <div class="cl-export-dates">
                <label>Od: <input type="date" name="datum_od" required /></label>
                <label>Do: <input type="date" name="datum_do" required /></label>
            </div>
            
            <button type="submit" name="cl_export" class="button button-primary">Exportovať dáta</button>
        </form>
    </div>
</div>
