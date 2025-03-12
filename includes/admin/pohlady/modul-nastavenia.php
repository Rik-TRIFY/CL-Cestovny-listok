<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

$nastavenia = get_option('cl_nastavenia', []);
?>

<div class="wrap">
    <h1>Nastavenia modulu</h1>
    
    <form method="post" action="options.php">
        <?php settings_fields('cl_nastavenia'); ?>
        
        <div class="cl-nastavenia-sekcia">
            <h2>Tlač lístkov</h2>
            <table class="form-table">
                <tr>
                    <th>Šírka tlače</th>
                    <td>
                        <select name="cl_nastavenia[sirka_tlace]">
                            <option value="54" <?php selected($nastavenia['sirka_tlace'] ?? '54', '54'); ?>>54mm</option>
                            <option value="80" <?php selected($nastavenia['sirka_tlace'] ?? '54', '80'); ?>>80mm</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>Automatická tlač</th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="cl_nastavenia[auto_tlac]" 
                                   value="1" 
                                   <?php checked($nastavenia['auto_tlac'] ?? false); ?>>
                            Automaticky vytlačiť lístok po predaji
                        </label>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="cl-nastavenia-sekcia">
            <h2>Databázy</h2>
            <table class="form-table">
                <tr>
                    <th>Interval kontroly</th>
                    <td>
                        <select name="cl_nastavenia[interval_kontroly]">
                            <option value="hourly">Každú hodinu</option>
                            <option value="twicedaily">Dvakrát denne</option>
                            <option value="daily">Raz denne</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>Upozornenia</th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="cl_nastavenia[notifikacia_rozdielov]" 
                                   value="1"
                                   <?php checked($nastavenia['notifikacia_rozdielov'] ?? true); ?>>
                            Zobraziť upozornenia pri rozdieloch v databázach
                        </label>
                    </td>
                </tr>
            </table>
        </div>
        
        <?php submit_button('Uložiť nastavenia'); ?>
    </form>
</div>
