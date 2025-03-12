<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

$spravca = new \CL\jadro\SpravcaSuborov();
$logy = $spravca->nacitajLogy();
?>

<div class="wrap">
    <h1>Systémové logy</h1>
    
    <div class="cl-logy-container">
        <div class="cl-logy-filter">
            <select id="cl-log-typ">
                <option value="">Všetky typy</option>
                <option value="PREDAJ">Predaj</option>
                <option value="CHYBA">Chyby</option>
                <option value="SYSTEM">Systémové</option>
            </select>
            
            <input type="date" id="cl-log-datum" />
            <button class="button" id="cl-log-filter">Filtrovať</button>
        </div>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Čas</th>
                    <th>Typ</th>
                    <th>Správa</th>
                    <th>Detaily</th>
                </tr>
            </thead>
            <tbody id="cl-logy-zoznam">
                <?php foreach ($logy as $log): ?>
                <tr>
                    <td><?php echo esc_html($log['cas']); ?></td>
                    <td><?php echo esc_html($log['typ']); ?></td>
                    <td><?php echo esc_html($log['sprava']); ?></td>
                    <td>
                        <?php if ($log['data']): ?>
                            <button class="button cl-log-detail" data-log='<?php echo esc_attr(json_encode($log['data'])); ?>'>
                                Detail
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
