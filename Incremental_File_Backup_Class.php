<?php

require_once 'vendor/autoload.php';
use splitbrain\PHPArchive\Tar;
use splitbrain\PHPArchive\Archive;
use splitbrain\PHPArchive\FileInfo;

class Incremental_File_Backup_Tar extends Tar{
	
	public $file_archive_increment = 100;
	    
    /**
     * Open a TAR archive and put the file cursor at the end for data appending
     *
     * If $file is empty, the tar file will be created in memory
     *
     * @param string $file
     * @throws ArchiveIOException
     */
    public function openForAppend($file = '')
    {
        $this->file   = $file;
        $this->memory = '';
        $this->fh     = 0;

        if ($this->file) {
            // determine compression
            if ($this->comptype == Archive::COMPRESS_AUTO) {
                $this->setCompression($this->complevel, $this->filetype($file));
            }

            if ($this->comptype === Archive::COMPRESS_GZIP) {
                $this->fh = @gzopen($this->file, 'ab'.$this->complevel);
            } elseif ($this->comptype === Archive::COMPRESS_BZIP) {
                $this->fh = @bzopen($this->file, 'a');
            } else {
                $this->fh = @fopen($this->file, 'ab');
            }

            if (!$this->fh) {
                throw new ArchiveIOException('Could not open file for writing: '.$this->file);
            }
        }
        $this->writeaccess = true;
        $this->closed      = false;
    }
    
    
     /**
     * Append data to a file to the current TAR archive using an existing file in the filesystem
     *
     * @param string          	$file     path to the original file
     * @param int          		$start    starting reading position in file
     * @param int          		$end      end position in reading multiple with 512
     * @param string|FileInfo $fileinfo either the name to us in archive (string) or a FileInfo oject with all meta data, empty to take from original
     * @throws ArchiveIOException
     */
    public function appendFileData($file, $start = 0, $end = 0, $fileinfo = '')
    {
		$end = $start+($end*512);
		
		//check to see if we are at the begining of writing the file
		if(!$start)
		{
	        if (is_string($fileinfo)) {
				$fileinfo = FileInfo::fromPath($file, $fileinfo);
	        }
		}
		
        if ($this->closed) {
            throw new ArchiveIOException('Archive has been closed, files can no longer be added');
        }

        $fp = fopen($file, 'rb');
        
        fseek($fp, $start);
        
        if (!$fp) {
            throw new ArchiveIOException('Could not open file for reading: '.$file);
        }

        // create file header
		if(!$start)
			$this->writeFileHeader($fileinfo);

        // write data
        while ($end >=ftell($fp) and !feof($fp) ) {
            $data = fread($fp, 512);
            if ($data === false) {
                break;
            }
            if ($data === '') {
                break;
            }
            $packed = pack("a512", $data);
            $this->writebytes($packed);
        }
        
        //if we are not at the end of file, we return the current position for incremental writing
        if(!feof($fp))
			$last_position = ftell($fp);
		else
			$last_position = -1;
			
        fclose($fp);
        
        return $last_position;
    }
    
    /**
     * Adds a file to a TAR archive by appending it's data
     *
     * @param string $archive			name of the archive file
     * @param string $file				name of the file to read data from				
     * @param string $start				start position from where to start reading data
     * @throws ArchiveIOException
     */
	public function addFileToArchive($archive, $file, $start = 0)
	{
		$this->openForAppend($archive);
		return $start = $this->appendFileData($file, $start, $this->file_archive_increment);
	}
	
	/**
	 * Do a test launch
	 */ 
	public function launchTest()
	{
		
		$this->setCompression(9);

		$archive = "tests/result.tgz";

		$files[] = 'tests/giphy.gif';
		$files[] = 'tests/file.txt';
		
		foreach($files as $file)
		{
			$start = 0;
			while($start >=0)
				$start = $this->addFileToArchive($archive, $file, $start);
		}
	}
	
	
}

