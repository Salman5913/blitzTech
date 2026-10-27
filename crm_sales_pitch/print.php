<?php
include_once ("../connection/localhost.php");
SecurityShell::redirectIfNoVoucherAccess(VoucherType::CRMI);

$oCRMInquiry = new CRMInquiry();
$oSalesPitch = new CRMSalesPitch();

$oCRMInquiry->setVoucherId($_GET['id']);
$RowInquiryDetails = $oCRMInquiry->getDetails();

$RowSalesPitch = $oSalesPitch->getDetailsByVoucherId($RowInquiryDetails['crm_sales_pitch_id']);

$LiColor = ['#FF715B', '#0CAADC', '#45cb85', '#e83e8c', '#6f42c1', '#3b5de7'];

$Modules = array_map("trim", explode(";", $RowSalesPitch['modules']));
$TopReports = array_map("trim", explode(";", $RowSalesPitch['top_reports']));

$_GET['__filename'] = $RowInquiryDetails['client_name']." - FINAC Profile";
?>
<link rel="stylesheet" href="../assets/css/icons.css">
<style type="text/css">
    table tr td {
        text-align: justify;
        color: #404040;
    }
    table tr td ul {
        margin: 0;
        padding: 0;
        list-style-type: circle;
    }
    table tr td ul li {
        padding-bottom: 15px;
        text-align: left;
    }
    h2 {
        margin: 0 0 10px;
        font-size: 18px;
        line-height: 28px;
        text-align: center;
    }
    h5 {
        margin: 0;
        padding: 0;
        font-size: 16px;
        line-height: 10px;
        color: #02568A;
    }
    .text-red {
        color: #C00000;
    }
    .text-decoration-underline {
        text-decoration: underline;
    }
    .testimonial img {
        max-width: 120px;
        margin-bottom: 10px;
    }
    .testimonial td {
        text-align: center;
    }
    .testimonial a {
        color: #FF715B;
        font-size: 14px;
        text-decoration: none;
    }
    .testimonial img.youtube-icon {
        max-width: 7px;
        margin-right: 5px;
    }
    .clients img {
        max-width: 100%;
        height: auto;
    }
    /*table tr td ul li {
        padding: 1px 13px;
        border: solid 1px;
        margin-bottom: 10px;
        display: inline;
        margin-right: 30px;
        margin-top: 10px
    }*/

</style>
<page backtop="20mm" backbottom="10mm" backleft="15mm" backright="15mm" backimg="../assets/images/sales-pitch/bg.jpg" style="font-size: 15px; line-height: 25px; font-family: helvetica, san-serif">
    <page_header>
        <table style="width: 100%; border-collapse: collapse">
            <tr>
                <td style="width: 50%"><img style="max-width: 120px; height: auto" src="../assets/images/logo-quote.jpg" alt=""></td>
                <td style="width: 50%; text-align: right"><img style="max-width: 160px; height: auto" src="../assets/images/blitztech.png" alt=""></td>
            </tr>
        </table>
    </page_header>
    <page_footer>
        <table style="width: 100%; border-collapse: collapse">
            <tr>
                <td style="width: 100%; text-align: center"><strong>P:</strong> +92 213 8888 651 | <strong>M:</strong> +92 300 3362353 | <strong>E:</strong> support@3dm-sols.com</td>
            </tr>
        </table>
    </page_footer>
    <table style="width: 100%;" cellspacing="5">
        <tr>
            <td><h2>Revolutionize Business with FINAC - Your Customizable Cloud ERP Partner!</h2></td>
        </tr>
        <tr>
            <td style="padding-top: 20px;">
                Dear <strong><?=$RowInquiryDetails['client_name']?></strong>,</td>
        </tr>
        <tr>
            <td>We appreciate the opportunity to assist you in managing the following aspects of your operations:</td>
        </tr>
        <tr>
            <td>
                <table style="width: 100%; padding-top: 30px" cellspacing="0" cellpadding="0">
                    <tr>
                        <td><h5>Modules for You!</h5></td>
                        <td><h5>You Might Need!</h5></td>
                    </tr>
                    <tr>
                        <td style="width: 50%; vertical-align: top">
                            <ul style="padding: 0; margin: -10px">
                                <? for($i=0; $i<count($Modules); $i++) { if (empty($Modules[$i])) continue ?>
                                    <li style="color: <?=$LiColor[$i % count($LiColor)]?>"> &nbsp; <?=$Modules[$i]?></li>
                                <? } ?>
                            </ul>
                        </td>
                        <td style="width: 50%; vertical-align: top">
                            <ul style="padding: 0; margin: -10px">
                                <li> &nbsp; HR with Payroll and Loan & Advances</li>
                                <li> &nbsp; Integrated SMS</li>
                                <li> &nbsp; Asset Management</li>
                                <li> &nbsp; Cost Center module for financial analysis</li>
                            </ul>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td class="text-red" style="text-align: center; padding-top: 10px; font-size: 18px; font-style: italic; font-weight: bold">“Tailored solution, Right for you!”</td>
        </tr>
    </table>
</page>

<page backtop="20mm" backbottom="10mm" backleft="15mm" backright="15mm" backimg="../assets/images/sales-pitch/bg.jpg" style="font-size: 15px; line-height: 25px; font-family: helvetica, san-serif">
    <page_header>
        <table style="width: 100%; border-collapse: collapse">
            <tr>
                <td style="width: 50%"><img style="max-width: 120px; height: auto" src="../assets/images/logo-quote.jpg" alt=""></td>
                <td style="width: 50%; text-align: right"><img style="max-width: 160px; height: auto" src="../assets/images/blitztech.png" alt=""></td>
            </tr>
        </table>
    </page_header>
    <page_footer>
        <table style="width: 100%; border-collapse: collapse">
            <tr>
                <td style="width: 100%; text-align: center"><strong>P:</strong> +92 213 8888 651 | <strong>M:</strong> +92 300 3362353 | <strong>E:</strong> support@3dm-sols.com</td>
            </tr>
        </table>
    </page_footer>
    <table style="width: 100%;" cellspacing="5">
        <tr>
            <td><h2>Extensive Reporting</h2></td>
        </tr>
        <tr>
            <td>
                <p>FINAC offers an extensive range of reports tailored to your needs. Some <span class="font-weight-bold text-decoration-underline">notable reports</span> that will prove highly beneficial to you include:</p>
            </td>
        </tr>
        <tr>
            <td>
                <? shuffle($LiColor); ?>
                <table style="width: 100%; padding-top: 30px" cellspacing="0" cellpadding="0">
                    <tr>
                        <td><h5>Reports for You!</h5></td>
                        <td><h5>Few More!</h5></td>
                    </tr>
                    <tr>
                        <td style="width: 50%; vertical-align: top">
                            <ul style="padding: 0; margin: -10px">
                                <? for($i=0; $i<count($TopReports); $i++) { if (empty($TopReports[$i])) continue ?>
                                    <li style="color: <?=$LiColor[$i % count($LiColor)]?>"> &nbsp; <?=$TopReports[$i]?></li>
                                <? } ?>
                            </ul>
                        </td>
                        <td style="width: 50%; vertical-align: top">
                            <ul style="padding: 0; margin: -10px">
                                <li> &nbsp; Daily / Average Purchase, Sales</li>
                                <li> &nbsp; Trial Balance, Cash Flow</li>
                                <li> &nbsp; Balance Sheet, Income Statement</li>
                                <li> &nbsp; Accounts Ledger + reconciliation feature</li>
                                <li> &nbsp; Top customers & top products</li>
                            </ul>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="text-align: center; padding-top: 10px; font-size: 18px; font-style: italic; font-weight: bold">FINAC offers a multitude of reports beyond the suggested ones.</td>
        </tr>
    </table>
</page>

<page backtop="20mm" backbottom="10mm" backleft="15mm" backright="15mm" backimg="../assets/images/sales-pitch/bg.jpg" style="font-size: 15px; line-height: 25px; font-family: helvetica, san-serif">
    <page_header>
        <table style="width: 100%; border-collapse: collapse">
            <tr>
                <td style="width: 50%"><img style="max-width: 120px; height: auto" src="../assets/images/logo-quote.jpg" alt=""></td>
                <td style="width: 50%; text-align: right"><img style="max-width: 160px; height: auto" src="../assets/images/blitztech.png" alt=""></td>
            </tr>
        </table>
    </page_header>
    <page_footer>
        <table style="width: 100%; border-collapse: collapse">
            <tr>
                <td style="width: 100%; text-align: center"><strong>P:</strong> +92 213 8888 651 | <strong>M:</strong> +92 300 3362353 | <strong>E:</strong> support@3dm-sols.com</td>
            </tr>
        </table>
    </page_footer>
    <table style="width: 100%;" cellspacing="5">
        <tr>
            <td><h2>Website Integration</h2></td>
        </tr>
        <tr>
            <td>
                <p>In addition to its core functionalities, FINAC offers comprehensive support in establishing your <span style="text-decoration: underline">online presence through an  eCommerce website</span>.</p>
                <p>Our platform integrates with your <span class="text-red">WordPress / WooCommerce / Shopify website</span>, ensuring that your stock quantity and selling prices are automatically updated on the website.</p> <p>Furthermore, you can efficiently manage all your website orders from a centralized location within your FINAC ERP.</p>
                <p class="text-center" style=" text-align: center; padding-top: 20px; padding-right: 30px;">
                    <img src="../assets/images/sales-pitch/woo-commerce.jpg" alt="" style="max-width: 150px; margin-right: 50px">
                    <img src="../assets/images/sales-pitch/shopify.jpg" alt="" style="width: 150px; max-width: 150px">
                </p>
            </td>
        </tr>
    </table>
</page>

<page backtop="20mm" backbottom="10mm" backleft="15mm" backright="15mm" backimg="../assets/images/sales-pitch/bg.jpg" style="font-size: 15px; line-height: 25px; font-family: helvetica, san-serif">
    <page_header>
        <table style="width: 100%; border-collapse: collapse">
            <tr>
                <td style="width: 50%"><img style="max-width: 120px; height: auto" src="../assets/images/logo-quote.jpg" alt=""></td>
                <td style="width: 50%; text-align: right"><img style="max-width: 160px; height: auto" src="../assets/images/blitztech.png" alt=""></td>
            </tr>
        </table>
    </page_header>
    <page_footer>
        <table style="width: 100%; border-collapse: collapse">
            <tr>
                <td style="width: 100%; text-align: center"><strong>P:</strong> +92 213 8888 651 | <strong>M:</strong> +92 300 3362353 | <strong>E:</strong> support@3dm-sols.com</td>
            </tr>
        </table>
    </page_footer>
    <table style="width: 100%;" cellspacing="5">
        <tr>
            <td><h2>Why FINAC?</h2></td>
        </tr>
        <tr>
            <td>
                <p>FINAC is a cloud-based, <a class="text-decoration-underline" style="color: #3b5de7" href="https://www.finac-erp.com/" target="_blank">customizable ERP</a> solution that has been <strong>trusted by over 150 businesses</strong> with a combined <strong class="text-red">turnover of Rs 4 billion</strong> in the <strong class="text-red">past 10 years</strong>. FINAC is the solution to your business's accounting and inventory management needs.</p>
                <p>The real value of FINAC lies in its customization. Our system is designed to meet the unique needs of your business, <span class="text-blue">no matter what industry you're in</span>. We understand that every business is different, and we work closely with our clients to ensure that our system meets their specific needs.</p>
                <p style="text-align: center; color: #f1734f; font-style: italic; font-weight: bold; padding-top: 10px; font-size: 18px">This means you'll never have to worry about using a one-size-fits-all solution that doesn't work for your business.</p>
            </td>
        </tr>
    </table>
</page>

<page backtop="20mm" backbottom="10mm" backleft="15mm" backright="15mm" backimg="../assets/images/sales-pitch/bg.jpg" style="font-size: 15px; line-height: 25px; font-family: helvetica, san-serif">
    <page_header>
        <table style="width: 100%; border-collapse: collapse">
            <tr>
                <td style="width: 50%"><img style="max-width: 120px; height: auto" src="../assets/images/logo-quote.jpg" alt=""></td>
                <td style="width: 50%; text-align: right"><img style="max-width: 160px; height: auto" src="../assets/images/blitztech.png" alt=""></td>
            </tr>
        </table>
    </page_header>
    <page_footer>
        <table style="width: 100%; border-collapse: collapse">
            <tr>
                <td style="width: 100%; text-align: center"><strong>P:</strong> +92 213 8888 651 | <strong>M:</strong> +92 300 3362353 | <strong>E:</strong> support@3dm-sols.com</td>
            </tr>
        </table>
    </page_footer>
    <table style="width: 100%;" cellspacing="5">
        <tr>
            <td><h2>What Customers Says About Us?</h2></td>
        </tr>
        <tr>
            <td>
                <p>3DM Solutions, prioritizing customer satisfaction. Witness the transformative power of our digital portfolio solutions through client testimonials, revolutionizing businesses and propelling them towards digital success.</p>
                <table style="width: 100%; text-align: center" class="testimonial">
                    <tr>
                        <td style="width: 25%"><a href="https://www.youtube.com/watch?v=WbPYm4PZzAk" target="_blank"><img src="../assets/images/sales-pitch/onederland.jpg" alt=""><br><img src="../assets/images/sales-pitch/play-button-arrowhead.jpg" class="youtube-icon"> Watch Now</a></td>
                        <td style="width: 25%"><a href="https://www.youtube.com/watch?v=n-ahLU3ybO8" target="_blank"><img src="../assets/images/sales-pitch/spectra.jpg" alt=""><br><img src="../assets/images/sales-pitch/play-button-arrowhead.jpg" class="youtube-icon"> Watch Now</a></td>
                        <td style="width: 25%"><a href="https://www.youtube.com/watch?v=sNrJxcEvwlQ" target="_blank"><img src="../assets/images/sales-pitch/winglass.jpg" alt=""><br><img src="../assets/images/sales-pitch/play-button-arrowhead.jpg" class="youtube-icon"> Watch Now</a></td>
                        <td style="width: 25%"><a href="https://www.youtube.com/watch?v=VDtoaAUP32c" target="_blank"><img src="../assets/images/sales-pitch/wallman.jpg" alt=""><br><img src="../assets/images/sales-pitch/play-button-arrowhead.jpg" class="youtube-icon"> Watch Now</a></td>
                    </tr>
                    <tr>
                        <td><a href="https://www.youtube.com/watch?v=eu2kCUfCNVw" target="_blank"><img src="../assets/images/sales-pitch/nowhr.jpg" alt=""><br><img src="../assets/images/sales-pitch/play-button-arrowhead.jpg" class="youtube-icon"> Watch Now</a></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</page>

<page backtop="20mm" backbottom="10mm" backleft="15mm" backright="15mm" backimg="../assets/images/sales-pitch/bg.jpg" style="font-size: 15px; line-height: 25px; font-family: helvetica, san-serif">
    <page_header>
        <table style="width: 100%; border-collapse: collapse">
            <tr>
                <td style="width: 50%"><img style="max-width: 120px; height: auto" src="../assets/images/logo-quote.jpg" alt=""></td>
                <td style="width: 50%; text-align: right"><img style="max-width: 160px; height: auto" src="../assets/images/blitztech.png" alt=""></td>
            </tr>
        </table>
    </page_header>
    <page_footer>
        <table style="width: 100%; border-collapse: collapse">
            <tr>
                <td style="width: 100%; text-align: center"><strong>P:</strong> +92 213 8888 651 | <strong>M:</strong> +92 300 3362353 | <strong>E:</strong> support@3dm-sols.com</td>
            </tr>
        </table>
    </page_footer>
    <table style="width: 100%;" cellspacing="5">
        <tr>
            <td><h2>Our Clients</h2></td>
        </tr>
        <tr>
            <td>
                <table style="width: 100%; text-align: center" class="clients">
                    <tr>
                        <td style="width: 16.66%; text-align: center"><img class="img-fluid" src="../assets/images/sales-pitch/Fairdeals.jpg" alt=""></td>
                        <td style="width: 16.66%; text-align: center"><img class="img-fluid" src="../assets/images/sales-pitch/Pakistan-SG.jpg" alt=""></td>
                        <td style="width: 16.66%; text-align: center"><img class="img-fluid" src="../assets/images/sales-pitch/BDC.jpg" alt=""></td>
                        <td style="width: 16.66%; text-align: center"><img class="img-fluid" src="../assets/images/sales-pitch/hebatullah.jpg" alt=""></td>
                        <td style="width: 16.66%; text-align: center"><img class="img-fluid" src="../assets/images/sales-pitch/ezziel.jpg" alt=""></td>
                        <td style="width: 16.66%; text-align: center"><img class="img-fluid" src="../assets/images/sales-pitch/bhs.jpg" alt=""></td>
                    </tr>
                    <tr>
                        <td style="width: 16.66%; text-align: center"><img class="img-fluid" src="../assets/images/sales-pitch/al-clean.jpg" alt=""></td>
                        <td style="width: 16.66%; text-align: center"><img class="img-fluid" src="../assets/images/sales-pitch/royal-pastic.jpg" alt=""></td>
                        <td style="width: 16.66%; text-align: center"><img class="img-fluid" src="../assets/images/sales-pitch/hk.jpg" alt=""></td>
                        <td style="width: 16.66%; text-align: center"><img class="img-fluid" src="../assets/images/sales-pitch/nn-sweet.jpg" alt=""></td>
                        <td style="width: 16.66%; text-align: center"><img class="img-fluid" src="../assets/images/sales-pitch/MAJ1.jpg" alt=""></td>
                        <td style="width: 16.66%; text-align: center"><img class="img-fluid" src="../assets/images/sales-pitch/DE2.jpg" alt=""></td>
                    </tr>
                    <tr>
                        <td style="width: 16.66%; text-align: center"><img class="img-fluid" src="../assets/images/sales-pitch/alpha-CC1.jpg" alt=""></td>
                        <td style="width: 16.66%; text-align: center"><img class="img-fluid" src="../assets/images/sales-pitch/Forth-Lighting.jpg" alt=""></td>
                        <td style="width: 16.66%; text-align: center"><img class="img-fluid" src="../assets/images/sales-pitch/nuri.jpg" alt=""></td>
                        <td style="width: 16.66%; text-align: center"><img class="img-fluid" src="../assets/images/sales-pitch/sardar.jpg" alt=""></td>
                        <td style="width: 16.66%; text-align: center"><img class="img-fluid" src="../assets/images/sales-pitch/EFA.jpg" alt=""></td>
                        <td style="width: 16.66%; text-align: center"><img class="img-fluid" src="../assets/images/sales-pitch/azure.jpg" alt=""></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</page>

<page backtop="20mm" backbottom="10mm" backleft="15mm" backright="15mm" backimg="../assets/images/sales-pitch/bg.jpg" style="font-size: 15px; line-height: 25px; font-family: helvetica, san-serif">
    <page_header>
        <table style="width: 100%; border-collapse: collapse">
            <tr>
                <td style="width: 50%"><img style="max-width: 120px; height: auto" src="../assets/images/logo-quote.jpg" alt=""></td>
                <td style="width: 50%; text-align: right"><img style="max-width: 160px; height: auto" src="../assets/images/blitztech.png" alt=""></td>
            </tr>
        </table>
    </page_header>
    <page_footer>
        <table style="width: 100%; border-collapse: collapse">
            <tr>
                <td style="width: 100%; text-align: center"><strong>P:</strong> +92 213 8888 651 | <strong>M:</strong> +92 300 3362353 | <strong>E:</strong> support@3dm-sols.com</td>
            </tr>
        </table>
    </page_footer>
    <table style="width: 100%;" cellspacing="5">
        <tr>
            <td>
                <p>Our one-time cost is a small investment for the incredible value you'll receive in return. With FINAC, you'll see almost half the staff training costs saved, and our small monthly maintenance fee and cloud system cost are more than worth it for the peace of mind that comes with knowing your data is secure and always accessible.</p>
                <p>With FINAC, you'll have access to a wide range of features, from inventory management tools to accounting and billing software, all designed to help you manage your business more efficiently and effectively than ever before.</p>
                <p>Plus, </p>
            </td>
        </tr>
        <tr>
            <td class="text-red" style="text-align: center; padding-top: 10px; font-size: 18px; font-style: italic; font-weight: bold">With our user-friendly interface, your staff will be able to learn the system quickly, reducing training time and minimizing the risk of errors.</td>
        </tr>
    </table>
</page>

<page backtop="20mm" backbottom="10mm" backleft="15mm" backright="15mm" backimg="../assets/images/sales-pitch/bg.jpg" style="font-size: 15px; line-height: 25px; font-family: helvetica, san-serif">
    <page_header>
        <table style="width: 100%; border-collapse: collapse">
            <tr>
                <td style="width: 50%"><img style="max-width: 120px; height: auto" src="../assets/images/logo-quote.jpg" alt=""></td>
                <td style="width: 50%; text-align: right"><img style="max-width: 160px; height: auto" src="../assets/images/blitztech.png" alt=""></td>
            </tr>
        </table>
    </page_header>
    <page_footer>
        <table style="width: 100%; border-collapse: collapse">
            <tr>
                <td style="width: 100%; text-align: center"><strong>P:</strong> +92 213 8888 651 | <strong>M:</strong> +92 300 3362353 | <strong>E:</strong> support@3dm-sols.com</td>
            </tr>
        </table>
    </page_footer>
    <table style="width: 100%; text-align: center" cellspacing="5">
        <tr>
            <td style="text-align: center">
                <p class="text-red" style="text-align: center; padding-top:50px; font-size: 18px; font-style: italic; font-weight: bold">“Investing in FINAC means investing in the future of your business.”</p>
                <p>Our ERP system is designed to help you succeed, and we're proud to have helped over 150 businesses do just that.</p>
                <p>Say goodbye to the headaches of traditional accounting and inventory management and <br><span style="font-style: italic; color: #3b5de7">SWITCH TO FINAC TODAY!</span></p>
            </td>
        </tr>
    </table>
</page>
