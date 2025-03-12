<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Cestovný lístok <?php echo esc_html($data['cislo_listka']); ?></title>
    <style>
        @page { size: 54mm 150mm; margin: 2mm; }
        body { font-family: Arial, sans-serif; font-size: 10pt; margin: 0; padding: 4mm; }
        .logo { max-width: 50mm; margin-bottom: 2mm; }
        .hlavicka { text-align: center; margin-bottom: 3mm; }
        .obsah { margin: 3mm 0; }
        .polozka { display: flex; justify-content: space-between; margin: 1mm 0; }
        .suma { border-top: 1px solid #000; margin-top: 2mm; padding-top: 2mm; }
        .paticka { margin-top: 4mm; text-align: center; font-size: 8pt; }
    </style>
</head>
<body>
    <div class="hlavicka">
        <?php if ($logo_url = get_option('cl_logo_url')): ?>
            <img class="logo" src="<?php echo esc_url($logo_url); ?>" alt="Logo">
        <?php endif; ?>
        <?php echo nl2br(esc_html(get_option('cl_hlavicka', ''))); ?>
    </div>
