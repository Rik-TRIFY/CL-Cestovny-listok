<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;
?>

<div class="wrap">
    <h1>Import dát</h1>
    
    <div class="cl-import-container">
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('cl_import_nonce'); ?>
            
            <h3>Import lístkov</h3>
            <input type="file" name="listky_subor" accept=".csv,.xlsx" />
            <p class="description">Podporované formáty: CSV, XLSX</p>
            
            <button type="submit" name="cl_import" class="button button-primary">Importovať dáta</button>
        </form>
    </div>
</div>
