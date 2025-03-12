<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;
?>

<?php foreach ($listky as $listok): ?>
    <tr data-id="<?php echo $listok['id']; ?>">
        <td><?php echo esc_html($listok['nazov']); ?></td>
        <td><?php echo number_format($listok['cena'], 2); ?> €</td>
        <td><?php echo esc_html($listok['trieda']); ?></td>
        <td><?php echo esc_html($listok['skupina']); ?></td>
        <td><?php echo esc_html($listok['poradie']); ?></td>
        <td>
            <span class="status-<?php echo $listok['aktivny'] ? 'active' : 'inactive'; ?>">
                <?php echo $listok['aktivny'] ? 'Aktívny' : 'Neaktívny'; ?>
            </span>
        </td>
        <td class="akcie">
            <button class="button upravit-listok" data-id="<?php echo $listok['id']; ?>">
                Upraviť
            </button>
            <button class="button button-link-delete zmaz-listok" data-id="<?php echo $listok['id']; ?>">
                Zmazať
            </button>
        </td>
    </tr>
<?php endforeach; ?>
