<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

$spravca = new \CL\jadro\SpravcaSuborov();
$zalohy = $spravca->nacitajZalohy();
?>

<div class="wrap">
    <h1>Zálohy systému</h1>

    <div class="cl-zalohy-akcie">
        <button class="button button-primary" id="vytvor-zalohu">Vytvoriť zálohu</button>
        <p class="description">Posledná záloha: <?php echo $spravca->ziskajPoslednaZaloha(); ?></p>
    </div>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Dátum vytvorenia</th>
                <th>Veľkosť</th>
                <th>Typ</th>
                <th>Akcie</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($zalohy as $zaloha): ?>
            <tr>
                <td><?php echo esc_html(date('d.m.Y H:i', $zaloha['cas'])); ?></td>
                <td><?php echo esc_html($zaloha['velkost']); ?></td>
                <td><?php echo esc_html($zaloha['typ']); ?></td>
                <td>
                    <button class="button cl-obnov-zalohu" data-id="<?php echo esc_attr($zaloha['id']); ?>">
                        Obnoviť
                    </button>
                    <button class="button cl-zmaz-zalohu" data-id="<?php echo esc_attr($zaloha['id']); ?>">
                        Zmazať
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
