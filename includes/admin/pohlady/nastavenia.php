<?php
declare(strict_types=1);

namespace CL\Admin;

class SystemSettings {
    public function __construct() {
        add_action('admin_init', [$this, 'registerSettings']);
    }

    public function registerSettings(): void {
        register_setting('cl_system_settings', 'cl_button_back');
        register_setting('cl_system_settings', 'cl_button_add_to_cart');
        register_setting('cl_system_settings', 'cl_button_checkout');
    }

    public function renderSettingsPage(): void {
        ?>
        <div class="wrap">
            <h1>Systémové nastavenia</h1>
            <form method="post" action="options.php">
                <?php settings_fields('cl_system_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Texty tlačidiel</th>
                        <td>
                            <p>
                                <label>Tlačidlo "Späť":<br>
                                <input type="text" name="cl_button_back" value="<?php echo esc_attr(get_option('cl_button_back', 'NASPÄŤ')); ?>" class="regular-text">
                                </label>
                            </p>
                            <p>
                                <label>Tlačidlo "Do košíka":<br>
                                <input type="text" name="cl_button_add_to_cart" value="<?php echo esc_attr(get_option('cl_button_add_to_cart', 'DO KOŠÍKA')); ?>" class="regular-text">
                                </label>
                            </p>
                            <p>
                                <label>Tlačidlo "Dokončiť":<br>
                                <input type="text" name="cl_button_checkout" value="<?php echo esc_attr(get_option('cl_button_checkout', 'DOKONČIŤ')); ?>" class="regular-text">
                                </label>
                            </p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}