<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;
?>

<div class="wrap">
    <h1>Import/Export dát</h1>
    
    <div class="cl-import-export-container">
        <!-- Import sekcia -->
        <div class="cl-import-section">
            <form method="post" enctype="multipart/form-data" class="cl-import-form">
                <?php wp_nonce_field('cl_import_nonce'); ?>
                <div class="form-group">
                    <input type="file" name="import_subor" accept=".csv,.xlsx" required />
                    <button type="submit" name="cl_import" class="button button-primary">Importovať dáta</button>
                </div>
                <p class="description">Podporované formáty: CSV, XLSX</p>
            </form>
        </div>

        <hr>
        
        <!-- Export sekcia -->
        <div class="cl-export-section">
            <h3>Export dát</h3>
            <form method="post">
                <?php wp_nonce_field('cl_export_nonce'); ?>
                
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
</div>
