<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

$listky = (new \CL\Jadro\Databaza())->nacitaj(
    "SELECT * FROM `CL-typy_listkov` WHERE aktivny = TRUE ORDER BY poradie ASC"
);
?>

<div class="wrap">
    <div class="cl-terminal-container">
        <div class="cl-listky">
            <?php foreach ($listky as $listok): ?>
                <button class="cl-listok button button-primary" 
                        data-id="<?php echo $listok['id']; ?>"
                        data-nazov="<?php echo esc_attr($listok['nazov']); ?>"
                        data-cena="<?php echo esc_attr($listok['cena']); ?>">
                    <span class="nazov"><?php echo esc_html($listok['nazov']); ?></span>
                    <span class="cena"><?php echo number_format($listok['cena'], 2); ?> €</span>
                </button>
            <?php endforeach; ?>
        </div>

        <div class="cl-kosik">
            <h2>Košík</h2>
            <div id="polozky-kosika"></div>
            <div class="cl-kosik-suma">
                Celkom: <span id="celkova-suma">0.00</span> €
            </div>
            <button id="dokoncit-predaj" class="button button-primary">TOTAL</button>
        </div>
    </div>
</div>
