<?php
declare(strict_types=1);

namespace CL\Jadro;

use TCPDF;

class SpravcaExportu {
    private Databaza $databaza;
    
    public function __construct() {
        $this->databaza = new Databaza();
    }
    
    public function exportujDoPDF(array $data, string $nazov): string {
        require_once CL_PLUGIN_DIR . 'vendor/tecnickcom/tcpdf/tcpdf.php';
        
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->SetCreator('CL - Cestovné lístky');
        $pdf->SetTitle($nazov);
        
        // Pridáme logo a hlavičku
        if ($logo_url = get_option('cl_logo_url')) {
            $pdf->Image($logo_url, 10, 10, 30);
        }
        
        $pdf->SetFont('dejavusans', '', 10);
        // ...pokračovanie s generovaním PDF...
        
        return $pdf->Output($nazov . '.pdf', 'S');
    }

    private function exportujStatistiky(string $od, string $do): array {
        try {
            $statistiky = $this->databaza->nacitaj(
                "SELECT 
                    DATE(datum_predaja) as den,
                    COUNT(*) as pocet_predajov,
                    SUM(celkova_suma) as celkovy_obrat
                 FROM `CL-predaj`
                 WHERE DATE(datum_predaja) BETWEEN %s AND %s
                 GROUP BY DATE(datum_predaja)
                 ORDER BY den ASC",
                [$od, $do]
            );
            
            return [
                'statistiky' => $statistiky,
                'sumar' => [
                    'celkovy_pocet' => array_sum(array_column($statistiky, 'pocet_predajov')),
                    'celkovy_obrat' => array_sum(array_column($statistiky, 'celkovy_obrat'))
                ]
            ];
        } catch (\Exception $e) {
            $this->spravca->zapisDoLogu('EXPORT_ERROR', [
                'typ' => 'statistiky',
                'chyba' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
