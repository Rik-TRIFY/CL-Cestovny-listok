<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

$adresar = CL_PLUGIN_DIR . 'zalohy/';
$zalohy = glob($adresar . 'zaloha-*.json');
rsort($zalohy);
?>

<div class="wrap">
    <h1>Správa záloh</h1>
    
    <div class="cl-zalohy-box">
        <h2>Dostupné zálohy</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Dátum</th>
                    <th>Veľkosť</th>
                    <th>Akcie</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($zalohy as $zaloha): 
                    $datum = str_replace(['zaloha-', '.json'], '', basename($zaloha));
                ?>
                    <tr>
                        <td><?php echo date('d.m.Y', strtotime($datum)); ?></td>
                        <td><?php echo size_format(filesize($zaloha)); ?></td>
                        <td>
                            <a href="<?php echo esc_url(wp_nonce_url(
                                admin_url('admin-post.php?action=cl_stiahnut_zalohu&subor=' . basename($zaloha)),
                                'cl_stiahnut_zalohu'
                            )); ?>" class="button">Stiahnuť</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
