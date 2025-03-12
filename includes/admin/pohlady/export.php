<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;
?>

<div class="wrap">
    <h1>Export dát</h1>
    
    <div class="cl-export-container">
        <div class="cl-export-box">
            <h2>Export predajov</h2>
            <form id="export-predaje" class="cl-export-form">
                <input type="hidden" name="typ" value="predaje">
                <div class="form-field">
                    <label>Časové obdobie:</label>
                    <input type="date" name="od" required>
                    <span>-</span>
                    <input type="date" name="do" required>
                </div>
                <div class="form-field">
                    <label>Formát:</label>
                    <select name="format">
                        <option value="xlsx">Excel (XLSX)</option>
                        <option value="csv">CSV</option>
                        <option value="pdf">PDF</option>
                    </select>
                </div>
                <button type="submit" class="button button-primary">Exportovať</button>
            </form>
        </div>
    </div>
</div>
