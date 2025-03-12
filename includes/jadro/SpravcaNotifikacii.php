<?php
declare(strict_types=1);

namespace CL\Jadro;

class SpravcaNotifikacii {
    private $notifikacie = [];
    
    public function pridajNotifikaciu(string $sprava, string $typ = 'info'): void {
        $this->notifikacie[] = [
            'sprava' => $sprava,
            'typ' => $typ
        ];
        
        if (!has_action('admin_notices', [$this, 'zobrazNotifikacie'])) {
            add_action('admin_notices', [$this, 'zobrazNotifikacie']);
        }
    }
    
    public function zobrazNotifikacie(): void {
        foreach ($this->notifikacie as $notifikacia) {
            printf(
                '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
                esc_attr($notifikacia['typ']),
                esc_html($notifikacia['sprava'])
            );
        }
    }
}
