<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

$spravca = new \CL\jadro\SpravcaZaloh();
$posledna_zaloha = $spravca->ziskajPoslednuZalohu();
$vsetky_zalohy = $spravca->ziskajZoznamZaloh();
?>

<div class="wrap">
    <h1>Správa záloh</h1>

    <div class="cl-backup-actions">
        <button type="button" class="button button-primary" id="create-backup">
            Vytvoriť zálohu teraz
        </button>
    </div>

    <div class="cl-backup-info">
        <h3>Informácie o zálohách</h3>
        <p>Posledná záloha: <?php echo $posledna_zaloha ? date('d.m.Y H:i', strtotime($posledna_zaloha)) : 'Zatiaľ nebola vytvorená žiadna záloha'; ?></p>
        <p>Počet záloh: <?php echo count($vsetky_zalohy); ?></p>
    </div>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Dátum vytvorenia</th>
                <th>Veľkosť</th>
                <th>Typ zálohy</th>
                <th>Akcie</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($vsetky_zalohy)): ?>
                <tr>
                    <td colspan="4">Zatiaľ neboli vytvorené žiadne zálohy.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($vsetky_zalohy as $zaloha): ?>
                    <tr>
                        <td><?php echo date('d.m.Y H:i', strtotime($zaloha['datum'])); ?></td>
                        <td><?php echo size_format($zaloha['velkost']); ?></td>
                        <td><?php echo esc_html($zaloha['typ']); ?></td>
                        <td>
                            <button class="button restore-backup" data-id="<?php echo esc_attr($zaloha['id']); ?>">
                                Obnoviť
                            </button>
                            <button class="button delete-backup" data-id="<?php echo esc_attr($zaloha['id']); ?>">
                                Zmazať
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
jQuery(document).ready(function($) {
    $('#create-backup').on('click', function() {
        if (!confirm('Naozaj chcete vytvoriť novú zálohu?')) return;
        
        $.post(ajaxurl, {
            action: 'cl_vytvor_zalohu',
            nonce: '<?php echo wp_create_nonce('cl_zalohy_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Chyba pri vytváraní zálohy: ' + response.data.message);
            }
        });
    });

    $('.restore-backup').on('click', function() {
        if (!confirm('POZOR! Obnovením zálohy prepíšete všetky aktuálne dáta. Pokračovať?')) return;
        
        var id = $(this).data('id');
        $.post(ajaxurl, {
            action: 'cl_obnov_zalohu',
            nonce: '<?php echo wp_create_nonce('cl_zalohy_nonce'); ?>',
            id: id
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Chyba pri obnove zálohy: ' + response.data.message);
            }
        });
    });

    $('.delete-backup').on('click', function() {
        if (!confirm('Naozaj chcete zmazať túto zálohu?')) return;
        
        var id = $(this).data('id');
        $.post(ajaxurl, {
            action: 'cl_zmaz_zalohu',
            nonce: '<?php echo wp_create_nonce('cl_zalohy_nonce'); ?>',
            id: id
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Chyba pri mazaní zálohy: ' + response.data.message);
            }
        });
    });
});
</script>
