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
        register_setting('cl_nastavenia', 'cl_nastavenia');
        
        add_settings_section(
            'cl_sekcia_statistiky',
            'Nastavenia štatistík',
            [$this, 'zobrazSekciuStatistiky'],
            'cl-nastavenia'
        );
        
        add_settings_field(
            'cl_statistiky_cas',
            'Čas aktualizácie štatistík',
            [$this, 'zobrazInputStatistikyCas'],
            'cl-nastavenia',
            'cl_sekcia_statistiky'
        );
        
        add_settings_field(
            'cl_statistiky_priecinok',
            'Cieľový priečinok pre štatistiky',
            [$this, 'zobrazInputStatistikyPriecinok'],
            'cl-nastavenia',
            'cl_sekcia_statistiky'
        );
    }

    public function zobrazInputStatistikyCas(): void {
        $nastavenia = get_option('cl_nastavenia');
        $cas = $nastavenia['statistiky_cas'] ?? '23:00';
        echo '<input type="time" name="cl_nastavenia[statistiky_cas]" value="' . esc_attr($cas) . '" />';
        echo '<p class="description">Čas kedy sa majú generovať denné štatistiky</p>';
    }

    public function zobrazInputStatistikyPriecinok(): void {
        $nastavenia = get_option('cl_nastavenia');
        $priecinok = $nastavenia['statistiky_priecinok'] ?? WP_CONTENT_DIR . '/statistiky';
        echo '<input type="text" name="cl_nastavenia[statistiky_priecinok]" value="' . esc_attr($priecinok) . '" class="regular-text" />';
        echo '<p class="description">Absolútna cesta k priečinku kde sa majú ukladať štatistiky</p>';
    }
}
