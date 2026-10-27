<?
if (is_file("../connection/localhost.php"))
    include_once("../connection/localhost.php");
else
    include_once("../../connection/localhost.php");

class Pagination
{
	private $Limit = 50;
	
	private $TotalRec = 0;
	private $PageNo = 0;
	private $TotalPageNo = 0;
	
	private $PageNoDone = array();
	
	private $URL;
	
	function __construct()
	{
		parse_str($_SERVER['QUERY_STRING'], $QSVars);
		unset($QSVars['page_no']);
		$NewQS = http_build_query($QSVars);

		$this->URL = $_SERVER['PHP_SELF'] . "?" . ((strlen($NewQS) > 0) ? $NewQS."&" : "") . "page_no=";
	}
	
	function makeQuery($Query, $PageNo, $OrderBy='')
	{
		$PageNo = ($PageNo == 0 || empty($PageNo)) ? 1 : $PageNo;
		 
		$this->PageNo = $PageNo;

		$TotRec = DBQM::query($Query);
		$this->TotalRec = $TotRec->num_rows;
		
		$Query = $Query.$OrderBy;
		$Query = $Query . " LIMIT ".(($PageNo-1) * $this->Limit).", ".$this->Limit; 
		$SelectQuery = DBQM::query($Query);

		return $SelectQuery;
	}
	
	function makePagingSummary()
	{
		$Form = (($this->PageNo-1) * $this->Limit) +1;
		$To = (($this->PageNo) * $this->Limit);
		$To = ($To <= $this->TotalRec) ? $To : $this->TotalRec;
		
		$Summary = "<div id='paging_summary'>";
		$Summary .= "Displaying Records ".number_format($Form)." - ".number_format($To)." of ".number_format($this->TotalRec);
		$Summary .= "</div>";
		
		return $Summary;
	}
	
	function makePaging()
	{
		$LeftSide = '<div id="paging_pageno"><ul><li class="no-style">Page Number</li>';
		
		$this->TotalPageNo = $TotalPages = ceil($this->TotalRec / $this->Limit);
		
		$SideRecords = 2;
		$MiddleRecords = 3;
		
		for ($i=1; $i<=$SideRecords; $i++)
		{
			$LeftSide .= $this->makePageNoLi($i);
		}
		
		if ($this->PageNo > ($SideRecords + $MiddleRecords + 1))	
			$LeftSide .= "<li>...</li>";
		
		for ($i=$this->PageNo-$MiddleRecords; $i<=$this->PageNo+$MiddleRecords; $i++)
		{
			$LeftSide .= $this->makePageNoLi($i);
		}

		if ($this->PageNo < ($TotalPages - ($SideRecords + $MiddleRecords)))
			$LeftSide .= "<li>...</li>";
		
		for ($i=$TotalPages-($SideRecords-1); $i<=$TotalPages; $i++)
		{
			$LeftSide .= $this->makePageNoLi($i);
		}
		
		$LeftSide .= '</ul></div>';
		
		return $LeftSide;
	}
	
	private function makePageNoLi($i)
	{
		if ($i > $this->TotalPageNo or $i < 1) return '';
		
		if (!in_array($i, $this->PageNoDone))
		{
			$this->PageNoDone[] = $i;
			return "<li".(($this->PageNo == $i) ? " class='current'" : '')."><a href='".$this->URL.$i."'>".$i."</a></li>";
		}
		else
			return '';
	}
	

}