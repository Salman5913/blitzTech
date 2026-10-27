<?php

/**
 * Created by PhpStorm.
 * User: mustafa
 * Date: 03-Nov-16
 * Time: 2:39 PM
 */
class UtilsPrint
{
    static function button()
    {
        ?>
        <div class="bs-label">
            <a href="javascript:void(0)" class="print_button" style="line-height: 35px; margin-right: 10px"><i class="mdi mdi-printer"></i> Print</a>
            <a href="javascript:void(0)" class="excel_button"><i class="mdi mdi-file-excel-outline"></i> Excel</a>
        </div>
        <?
    }

    static function makePrintable()
    {
        echo " printable ";
    }

    static function init($ShortDescription='' , $PreserverColors=false, $ShowAppliedFilter=true)
    {
        $DateFields = [];

        ?>
        <script>
            var __print_filter_applied = '';
        </script>
        <?php
        foreach ($_GET as $Name => $Value)
        {
            if (strstr($Name, "date"))
                $DateFields[] = $Name;

            if (!empty($Value) and substr($Name, 0, 1) != "_" AND !strstr($Name, "DataTables") AND !strstr($Name, "date"))
            {
                ?>
                <script>
                    var input_element = $("[name='<?=$Name?>']");
                    var label_element = $("label[for='"+input_element.attr("id")+"']");
                    var _value =  input_element.val();

                    if (input_element.is("select"))
                        _value = $( "option:selected", input_element ).text();

                    if (label_element.length === 0)
                        label_element = input_element.closest("div").find("label");

                    __print_filter_applied += label_element.html() + ": " + _value +", ";
                </script>
                <?php
            }
        }


        if (count($DateFields) != 0)
        {
            if (count($DateFields) == 1)
            {
                if (empty($_GET[$DateFields[0]]))
                    $_GET[$DateFields[0]] = date("M d, Y");
                ?>
                <script>__print_filter_applied += "As on: <?=$_GET[$DateFields[0]]?>";</script>
                <?php
            }
            elseif (count($DateFields) == 2)
            {
                if (empty($_GET[$DateFields[0]]))
                    $_GET[$DateFields[0]] = "From Start";
                if (empty($_GET[$DateFields[1]]))
                    $_GET[$DateFields[1]] = date("M d, Y");
                ?>
                <script>__print_filter_applied += "Date Range: <?=$_GET[$DateFields[0]]?> To <?=$_GET[$DateFields[1]]?> ";</script>
                <?php
            }
        }

        ?>
        <script type="text/javascript">
            $(function () {

                $(".print_button").click(function () {
                    if ($("table.printable").hasClass("data-table-report"))
                        $("table.data-table-report").dataTable().fnLengthChange( -1 );

                    var OpenWindow = window.open("<?=ProjectHelper::SiteURL()?>includes/popup_report_print.php", "my_window"+Math.random()+"asd", '');
                    OpenWindow.onload = function(){
                        OpenWindow.title = $("h4.page-title").html(); // dataFromParent is a variable in child.html
                        OpenWindow.table_data = '<table class="table color table-striped table-bordered">' + $("table.printable").html() + '</table>';
                        OpenWindow.short_description = <?=$ShowAppliedFilter ? '__print_filter_applied' : "'".$ShortDescription."'"; ?>;
                        OpenWindow.init();
                    }
                });

                $(".excel_button").click(function () {
                    if ($("table.printable").hasClass("data-table-report"))
                        $("table.data-table-report").dataTable().fnLengthChange( -1 );

                    $("table.printable").table2excel({
                        // exclude CSS class
                        exclude: ".print-hidden",
                        name: "Worksheet Name",
                        filename: $("h4.page-title").text().replace(" ", "-").toLowerCase(), //do not include extension
                        fileext: ".xlxs", // file extension,
                        exclude_img:true,
                        exclude_links:true,
                        exclude_inputs:true,
                        preserveColors: <?=$PreserverColors ? 'true' : 'false' ?>
                    });
                });
            });
        </script>
        <?
    }

    static function childInit()
    {
        ?>
        <script type="text/javascript">
            var title;
            var table_data;
            var short_description;
            function init() {
                $(".popup-header").html(title);
                $(".table-data").html(table_data);
                $(".short-description").html(short_description);

                $('a:not(.print-accept)').contents().unwrap();

                document.title = title + " - FINAC";

                setTimeout(function () { window.print(); }, 1000);
            }
        </script>
        <?
    }
}