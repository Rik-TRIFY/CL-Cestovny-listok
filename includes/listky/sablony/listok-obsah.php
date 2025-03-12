<?php
declare(strict_types=1);
?>
<div class="obsah">
    <?php foreach ($data['polozky'] as $polozka): ?>
        <div class="polozka">
            <span class="nazov"><?php echo esc_html($polozka['nazov']); ?></span>
            <span class="pocet"><?php echo intval($polozka['pocet']); ?>x</span>
            <span class="cena"><?php echo number_format($polozka['cena'], 2); ?> €</span>
        </div>
    <?php endforeach; ?>
    
    <div class="suma">
        <strong>CELKOM: <?php echo number_format($data['celkova_suma'], 2); ?> €</strong>
    </div>
    
    <div class="info">
        <p>
            <strong>Číslo lístka: <?php echo esc_html($data['cislo_listka']); ?></strong><br>
            Dátum: <?php echo date('d.m.Y H:i'); ?><br>
            Predajca: <?php echo esc_html($data['predajca']); ?>
        </p>
    </div>
</div>
