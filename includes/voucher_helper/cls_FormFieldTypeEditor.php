<?php

class FormFieldTypeEditor extends FormFieldType
{
    function getFieldName(): null|string
    {
        return null;
    }

    function getFieldDesign($Value, $CommonAttributes): void
    {
        ?>
        <textarea
            <?=$CommonAttributes?>
        ><?=$Value?></textarea>
        <script>
            $(function () {
                tinymce.init({
                    selector: "#<?=$this->oFormFieldDesigner->getColumnName()?>",
                    height: 300,
                    plugins: ["advlist autolink link image lists charmap print preview hr anchor pagebreak spellchecker", "searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking", "save table contextmenu directionality emoticons template paste textcolor", "wordcount"],
                    toolbar: "undo redo | formatselect fontsizeselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link | print preview media fullpage | forecolor backcolor emoticons",
                });
            })
        </script>
        <?php
    }
}