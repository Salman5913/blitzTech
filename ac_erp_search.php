<?php
include_once("connection/localhost.php");

$oSearch = new Search($_GET['term']);
$Select = $oSearch->getResults();

$Output = [];
$i=0;

while ($Row = DBQM::fetch_assoc($Select))
{
    $Output[$i]['value'] = $Row['column_name'];
    $Output[$i]['link'] = ProjectHelper::SiteURL().$oSearch->getLink($Row['result_type'], $Row['id']);
    $Output[$i]['description'] = $oSearch->getDescription($Row['result_type']);
    $Output[$i]['css_class'] = $Row['css_class'];

    if ($Row['result_type'] == 'account' AND SecurityShell::isAccess("Chart of Account", "Transaction Account"))
    {
        $i++;

        $Output[$i]['value'] = "<small class='text-danger'><i class='mdi mdi-pencil'></i> Edit</small>";
        $Output[$i]['link'] = ProjectHelper::SiteURL().'chart_of_account/manage_accounts.php?id='.$Row['id'];
        $Output[$i]['description'] = '';
        $Output[$i]['css_class'] = "text-danger";
    }

    $i++;
}

foreach (Search::ADDITIONAL_LINKS as $ReportName => $Link)
{
    if (strstr(strtolower($ReportName), $_GET['term']))
    {
        $Output[$i]['value'] = $ReportName;
        $Output[$i]['link'] = ProjectHelper::SiteURL().$Link;
        $Output[$i]['description'] = '';
        $Output[$i]['css_class'] = 'text-warning';

        $i++;
    }
}

$SearchUtils = $oSearch->searchViaUtils();
if (is_array($SearchUtils))
{
    $Output[$i]['value'] = $SearchUtils[0];
    $Output[$i]['link'] = $SearchUtils[1];
    $Output[$i]['description'] = $SearchUtils[2];
    $Output[$i]['css_class'] = $SearchUtils[3];

    $i++;
}

echo json_encode($Output);