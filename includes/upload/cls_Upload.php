<?php

class Upload
{
	private $ApprovedExtension = array();
	
	function __construct(array $ApprovedExtensionList=[])
	{
	    if (empty($ApprovedExtensionList))
            $this->ApprovedExtension = array(
                                                'zip' => 'zip',
                                                'rar' => 'rar',
                                                'ai' => 'ai',
                                                'pdf' => 'pdf',
                                                'jpg' => 'jpg',
                                                'jpeg' => 'jpeg',
                                                'bmp' => 'bmp',
                                                'png' => 'png',
                                                'psd' => 'psd',
                                                'gif' => 'gif',
                                                'tiff' => 'tiff',
                                                'eps' => 'eps',
                                                'swf' => 'swf',
                                                'doc' => 'doc',
                                                'docx' => 'docx',
                                                'ppt' => 'ppt',
                                                'pptx' => 'pptx',
                                                'xls' => 'xls',
                                                'xlsx' => 'xlsx',
                                                'pub' => 'pub',
                                                'csv' => 'csv'
                                            );
	    else
	        $this->ApprovedExtension = $ApprovedExtensionList;
	}
	
	function approvedExtension($ExtensionName)
	{
		$ExtensionName = strtolower($ExtensionName);
		$this->ApprovedExtension[$ExtensionName] = $ExtensionName;
	}
	
	function removeExtension($ExtensionName)
	{
		unset($this->ApprovedExtension[$ExtensionName]);
	}
	
	function getExtension($FileName)
	{
		$Extension = explode(".", $FileName);
		return $Extension[count($Extension)-1];
	}
	
	function checkExtension($Extension)
	{
		return in_array(strtolower($Extension), $this->ApprovedExtension);
	}
	
	function folderCheck($FolderName)
	{
		if (!is_dir($FolderName))
			mkdir($FolderName, 0777, true);
	}
	
	private function uploadFile($FileName, $FileTempName, $FolderName, $AddRandomName)
	{
		$Extension = $this->getExtension($FileName);

		if (!$this->checkExtension($Extension)) return false;

        $FileName = str_replace(' ', '-', $FileName);

		$Name = $AddRandomName ? (mt_rand(0, 9999999)."-".$FileName) : $FileName;
        $FolderName = rtrim($FolderName, "/");

		move_uploaded_file($FileTempName, $FolderName."/".$Name);

        if (in_array($Extension, ['jpg', 'jpeg', 'png', 'gif']))
        {
            $oResizeImage = new ResizeImage($FolderName."/".$Name);
            $oResizeImage->resizeTo('1080', '1080');
            $oResizeImage->saveImage($FolderName."/".$Name);
        }

		return $Name;
	}
	
	function upload($File, $FolderName, $AddRandomName=false)
	{
		$this->folderCheck($FolderName);

		if (is_array($File['name'])) // we have multiple files uploaded
		{
			$Count = count($File['name']);
			$Names = array();
			
			for($i=0; $i<$Count; $i++)
			{
				if ($File['name'][$i] != '')
					$Names[] = $this->uploadFile($File['name'][$i], $File['tmp_name'][$i], $FolderName, $AddRandomName);
			}
			
		}
		else
		{
			$Names = $this->uploadFile($File['name'], $File['tmp_name'], $FolderName, $AddRandomName);
		}
		
		return $Names;
	}

	function uploadArrayFileSingle($FileName, $TmpName, $FolderName, $AddRandomName=false)
    {
        $this->folderCheck($FolderName);

        return $this->uploadFile($FileName, $TmpName, $FolderName, $AddRandomName);
    }
	
	function makeFileName($Path)
	{
		$Path = explode("/", $Path);
		return $Path[count($Path)-1];
	}
}