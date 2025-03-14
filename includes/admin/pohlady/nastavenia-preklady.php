<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

$spravca = \CL\jadro\SpravcaPrekladov::ziskajInstanciu();
?>

<table class="form-table">
    <?php foreach (\CL\jadro\SpravcaPrekladov::PREKLADY as $kluc => $data): ?>
    <tr>
        <th><?php echo esc_html($data[1]); ?></th>
        <td>
            <input type="text" 
                   name="cl_nastavenia[preklady][<?php echo esc_attr($kluc); ?>]" 
                   value="<?php echo esc_attr($spravca->nacitaj($kluc, $data[0])); ?>" 
                   class="regular-text">
        </td>
    </tr>
    <?php endforeach; ?>
</table>
