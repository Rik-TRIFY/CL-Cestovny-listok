<?php
declare(strict_types=1);

namespace CL\Admin;

class Nastavenia {
    public function __construct() {
        add_action('admin_init', [$this, 'registrujNastavenia']);
    }

    public function zobrazStranku(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Nedostatočné oprávnenia');
        }
        include CL_INCLUDES_DIR . 'admin/pohlady/nastavenia-formular.php';
    }

    public function registrujNastavenia(): void {
        register_setting('cl_nastavenia', 'cl_logo_url');
        register_setting('cl_nastavenia', 'cl_hlavicka');
        register_setting('cl_nastavenia', 'cl_paticka');
        register_setting('cl_nastavenia', 'cl_sirka_tlace');
        register_setting('cl_nastavenia', 'cl_auto_tlac');
        register_setting('cl_nastavenia', 'cl_interval_kontroly');
        register_setting('cl_nastavenia', 'cl_notifikacia_rozdielov');
    }
}
