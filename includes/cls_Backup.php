<?php
/**
 * Created by PhpStorm.
 * User: mustafa
 * Date: 014, 1/14/2015
 * Time: 4:56 PM
 */
if (is_file("../connection/localhost.php"))
    include_once("../connection/localhost.php");
else
    include_once("connection/localhost.php");

class Backup
{
    protected $Username;
    protected $Password;
    protected $DatabaseName;
    protected $Directory;

    function __construct($Username, $Password, $DatabaseName, $Directory)
    {
        $this->Username = $Username;
        $this->Password = $Password;
        $this->DatabaseName = $DatabaseName;
        $this->Directory = $Directory;
    }

    private function getDumpPathForWindows()
    {
        $FileName = $this->getDumpFileName();
        $DriveLetter = substr(__FILE__, 0, 1);

        return $DriveLetter.":".$_SERVER['MYSQL_HOME']."\\mysqldump -u".$this->Username." -p".$this->Password." -hlocalhost ".$this->DatabaseName." > ".$this->Directory."\\daily_backups\\".$FileName;
    }

    private function getDumpPathForLinux()
    {
        $FileName = $this->getDumpFileName();

        return "mysqldump --opt -hlocalhost -u".$this->Username." -p".$this->Password." ".$this->DatabaseName." > ".$this->Directory."/daily_backups/".$FileName;
    }

    function getDumpFileName()
    {
        return date('Y-m-d').".finac";
    }

    private function isOSWindows()
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    /**
     * Takes backup and saves .finac.backup file in daily_backups folder
     */
    function takeBackup()
    {
        if ($this->isOSWindows())
            exec($this->getDumpPathForWindows());
        else
            exec($this->getDumpPathForLinux());

        /**
         * compress file
         */
        if ($this->gzCompressFile("../backup/daily_backups/".$this->getDumpFileName()))
            unlink("../backup/daily_backups/".$this->getDumpFileName());
        else
            ErrorHandler::setError("Unable To create Backup or Compress Backup File");
    }

    /**
     * generates .backup file (zip file)
     * @param $SourceFile
     * @param int $Level
     * @return bool|string
     */
    function gzCompressFile($SourceFile, $Level = 9)
    {
        $Destination = $SourceFile . '.backup';
        $Error = false;
        if ($FilePointerOut = gzopen($Destination, 'wb'.$Level))
        {
            if ($FilePointerIn = fopen($SourceFile,'rb'))
            {
                while (!feof($FilePointerIn))
                    gzwrite($FilePointerOut, fread($FilePointerIn, 1024 * 512));
                fclose($FilePointerIn);
            }
            else
            {
                $Error = true;
            }
            gzclose($FilePointerOut);
        }
        else
        {
            $Error = true;
        }
        if ($Error)
            return false;
        else
            return $Destination;
    }

    /**
     * Delete backups older than 30 days
     */
    function deleteOldBackups()
    {
        $Files = glob("daily_backups/*.finac.backup");
        $Time  = time();

        foreach ($Files as $File)
            if (is_file($File))
                if ($Time - filemtime($File) >= 60*60*24*30) // 30 days
                    unlink($File);
    }
}