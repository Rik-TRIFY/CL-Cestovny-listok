<?php
declare(strict_types=1);

namespace CL\Listky;

class Generator {
    public function generujListok(array $data): string {
        ob_start();
        include CL_INCLUDES_DIR . 'listky/sablony/listok-hlavicka.php';
        include CL_INCLUDES_DIR . 'listky/sablony/listok-obsah.php';
        include CL_INCLUDES_DIR . 'listky/sablony/listok-paticka.php';
        return ob_get_clean();
    }
}
