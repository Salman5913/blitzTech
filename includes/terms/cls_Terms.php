<?php
/**
 * Created by PhpStorm.
 * User: mustafa
 * Date: 024, 12/24/2014
 * Time: 5:17 PM
 */

class Terms extends BasicForm
{

    function __construct()
    {
        parent::__construct(
            "terms", '', '', 'Terms Management'
        );
        $this->setDeleteDataUponSave(false);
    }
    function getAll()
    {
        return DBQM::query("
                                    SELECT
                                       *
                                    FROM
                                        terms
                                    ORDER BY 
                                    id
                                    
                              ");
    }

    function getSelectedTerms(array $Ids=[])
    {
        $Ids = implode(", ", array_map("escape", $Ids));

        return DBQM::query("SELECT * FROM terms WHERE id IN ($Ids)");
    }

} 