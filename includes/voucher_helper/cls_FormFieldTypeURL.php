<?php

class FormFieldTypeURL extends FormFieldType
{
    function getFieldDesign($Value, $CommonAttributes): void
    {
        ?>
        <input
            type="url"
            <?=$CommonAttributes?>
            value="<?=$Value?>"
        >
        <?php
    }

    function getFieldName(): null|string
    {
        return null;
    }
}