<?php
declare(strict_types=1);

namespace CL\Admin;

class AdminRozhranie {
    public function __construct() {
        add_action('admin_footer', [$this, 'pridajPodpis']);
        add_action('admin_enqueue_scripts', [$this, 'pridajAdminStyley']);
    }
    
    public function pridajAdminStyley(): void {
        wp_enqueue_style(
            'cl-admin-signature', 
            plugins_url('assets/css/admin-signature.css', dirname(__DIR__))
        );
    }
    
    public function pridajPodpis(): void {
        $current_screen = get_current_screen();
        if (strpos($current_screen->id, 'cl-') === 0) {
            echo '<div class="footer-signature">
                <span>Tvorím kód, weby, grafiku a iné...<br />
                <a href="https://pro.trify.sk" target="_blank">https://pro.trify.sk</a></span>
                <a href="https://pro.trify.sk" target="_blank">
                    <img src="' . plugins_url('assets/images/nakodovane_od_erika_v.png', dirname(__DIR__)) . '" 
                         alt="Nakódované od Erika">
                </a>
            </div>';
        }
    }
}
