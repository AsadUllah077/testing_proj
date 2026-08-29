<?php

declare(strict_types=1);

use Dompdf\Dompdf;
use Dompdf\Options;

require dirname(__DIR__).'/vendor/autoload.php';

$root = dirname(__DIR__);
$source = $root.'/docs/warehouse-module-improvement-report-2026-07-29.html';
$target = $root.'/resources/views/pages/products/warehouse/Warehouse_Module_Improvement_Report_2026-07-29.pdf';

$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('isHtml5ParserEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml((string) file_get_contents($source), 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

file_put_contents($target, $dompdf->output());

echo $target.PHP_EOL;
