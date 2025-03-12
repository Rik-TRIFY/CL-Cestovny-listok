<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

$backup_config = get_option('cl_zaloha_db', [
    'host' => '',
    'name' => '',
    'user' => '',
    'pass' => ''
]);
?>

<div class="wrap">
    <h2>Nastavenia záložnej databázy</h2>
    
    <form method="post" action="">
        <?php wp_nonce_field('cl_zaloha_db_settings'); ?>
        
        <table class="form-table">
            <tr>
                <th><label for="host">Host</label></th>
                <td>
                    <input type="text" id="host" name="zaloha_db[host]" 
                           value="<?php echo esc_attr($backup_config['host']); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="name">Názov databázy</label></th>
                <td>
                    <input type="text" id="name" name="zaloha_db[name]" 
                           value="<?php echo esc_attr($backup_config['name']); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="user">Používateľ</label></th>
                <td>
                    <input type="text" id="user" name="zaloha_db[user]" 
                           value="<?php echo esc_attr($backup_config['user']); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="pass">Heslo</label></th>
                <td>
                    <input type="password" id="pass" name="zaloha_db[pass]" 
                           value="<?php echo esc_attr($backup_config['pass']); ?>" class="regular-text">
                </td>
            </tr>
        </table>
        
        <?php submit_button('Uložiť nastavenia'); ?>
    </form>
</div>
