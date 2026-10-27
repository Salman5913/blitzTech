<?php
include_once ("../connection/localhost.php");

/*if (isset($_GET['encoded_id']))
{
	$_GET['quote_id'] = ProjectQuote::decodeQuoteId($_GET['encoded_id']);
}*/

/**
 * Html2Pdf Library - example
 *
 * HTML => PDF converter
 * distributed under the OSL-3.0 License
 *
 * @package   Html2pdf
 * @author    Laurent MINGUET <webmaster@html2pdf.fr>
 * @copyright 2017 Laurent MINGUET
 */
require_once '../vendor/autoload.php';

use Spipu\Html2Pdf\Html2Pdf;
use Spipu\Html2Pdf\Exception\Html2PdfException;
use Spipu\Html2Pdf\Exception\ExceptionFormatter;

try {
    ob_start();
    include 'print.php';
    $content = ob_get_clean();


    $html2pdf = new Html2Pdf('L', 'A5', 'en', true, 'UTF-8', 3);
    $html2pdf->writeHTML($content);
    $html2pdf->output($_GET['__filename'].".pdf");
} catch (Html2PdfException $e) {
    $html2pdf->clean();

    $formatter = new ExceptionFormatter($e);
    echo $formatter->getHtmlMessage();
}
