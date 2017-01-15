<?php

require_once 'vendor/autoload.php';
use splitbrain\PHPArchive\Tar;

require_once __DIR__.DIRECTORY_SEPARATOR."Incremental_File_Backup_Class.php";

$test = new Incremental_File_Backup_Tar();
$test->launchTest();


exit;

$tar = new Tar();
$tar->setCompression(9);
//$tmp = tempnam(sys_get_temp_dir(), 'dwtartest');

$tar_file = "tests/out.tgz";

$file1 = 'tests/giphy.gif';
$file2 = 'tests/file.txt';

//$bin_file = "giphy.gif";

$tar->create($tar_file);

$start = 0;
/*
while($start>=0)
{
	 $start = $tar->appendFileData($file1, $start, 100);
	 echo $start."\n";
}

*/

$start = $tar->appendFileData($file1, $start, 100);



$tar->openForAppend($tar_file);

if($start >= 0 ){
	echo $start;
	$start = $tar->appendFileData($file1, $start, 100);
	
	$tar->addFile($file2);
}
else
{
$tar->close();
}


echo $start;

echo "\n";
