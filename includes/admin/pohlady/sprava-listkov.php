<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Správa lístkov</h1>
    <a href="#" class="page-title-action" id="pridat-listok">Pridať nový lístok</a>
    
    <div class="cl-listky-container">
        <div class="cl-listky-filter">
            <input type="text" id="vyhladavanie" placeholder="Vyhľadať lístok...">
            <select id="filter-trieda">
                <option value="">Všetky triedy</option>
            </select>
            <select id="filter-skupina">
                <option value="">Všetky skupiny</option>
            </select>
        </div>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Názov</th>
                    <th>Cena</th>
                    <th>Trieda</th>
                    <th>Skupina</th>
                    <th>Poradie</th>
                    <th>Stav</th>
                    <th>Akcie</th>
                </tr>
            </thead>
            <tbody id="listky-zoznam">
                <!-- Dynamicky generované JavaScript-om -->
            </tbody>
        </table>
    </div>
</div>

<!-- Modal pre pridanie/úpravu lístka -->
<div id="listok-modal" class="cl-modal">
    <div class="cl-modal-content">
        <span class="cl-modal-close">&times;</span>
        <h2>Lístok</h2>
        <form id="listok-formular">
            <input type="hidden" name="id" id="listok-id">
            <div class="form-field">
                <label>Názov</label>
                <input type="text" name="nazov" required>
            </div>
            <div class="form-field">
                <label>Cena</label>
                <input type="number" name="cena" step="0.01" required>
            </div>
            <div class="form-field">
                <label>Trieda</label>
                <input type="text" name="trieda">
            </div>
            <div class="form-field">
                <label>Skupina</label>
                <input type="text" name="skupina">
            </div>
            <div class="form-field">
                <label>Poradie</label>
                <input type="number" name="poradie" value="0">
            </div>
            <div class="form-field">
                <label>
                    <input type="checkbox" name="aktivny" checked>
                    Aktívny
                </label>
            </div>
            <div class="form-actions">
                <button type="submit" class="button button-primary">Uložiť</button>
                <button type="button" class="button cl-modal-close">Zrušiť</button>
            </div>
        </form>
    </div>
</div>
