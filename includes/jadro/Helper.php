<?php
declare(strict_types=1);

namespace CL\Jadro;

class Helper {
    public static function formatujCisloListka(string $cislo): string {
        return implode('-', str_split($cislo, 4));
    }
    
    public static function generujCisloPredaja(): string {
        return date('Ymd') . mt_rand(1000, 9999);
    }
    
    public static function zapisChybu(string $sprava): void {
        error_log(sprintf(
            "[CL-ERROR] [%s] %s",
            date('Y-m-d H:i:s'),
            $sprava
        ));
    }
}
