<?php
namespace Kahlan\Reporter;

class OutputReporter extends Terminal 
{
	/**
     * File pointer of output file
     *
     * @var resource
     */
    protected $_fp;

    public function start($params) {
		$outputFile = $this->_outputFile;
    	if ($outputFile === null) {
    		$this->_write("Error: You must specify an output file through --output flag\n", "red");
    		parent::stop();
    	}

    	// Otherwise we should check that we can write in that file
    	if (file_exists($outputFile) && !is_writable($outputFile)) {
    		$this->_write("Error: please check that file '{$outputFile}' is writable\n", "red");
    		parent::stop();
    	} else {
    		$this->_fp = @fopen($outputFile, "w");

    		if (!$this->_fp) {
    			$this->_write("Error: can't create file '{$outputFile}' for write\n", "red");
    			parent::stop();
    		}
    	}
    	parent::start($params);
    }

}