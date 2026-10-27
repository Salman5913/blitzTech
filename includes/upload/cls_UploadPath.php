<?php

class UploadPath
{
    private string $Path = '';

    public function __construct($RequiredInClassName)
    {
        $this->Path = match ($RequiredInClassName) {
            'BankStatementImport' => '../assets/uploads/bank_statements/',
            'ContactCard' => '../assets/uploads/contact_card/',
            'Quotation' => '../assets/uploads/quotation/',
            default => ''
        };
    }

    function getPath()
    {
        if (empty($this->Path))
            return '';
        return str_ends_with($this->Path, "/") ? $this->Path : ($this->Path."/");
    }
}