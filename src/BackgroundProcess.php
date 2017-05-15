<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

namespace CourseSelection;

/**
 * Background Processor
 *
 * @version v14
 * @since   7th May 2017
 */
class BackgroundProcess
{
    protected $filePath;

    protected $processFilename = 'Processing.txt';
    protected $outputFilename = 'Output.txt';

    /**
     * Create a background processor, outputting to a specific folder
     *
     * @param  string  $filePath
     */
    public function __construct($filePath)
    {
        $this->filePath = rtrim($filePath, '/');

        if (!is_dir($this->filePath)) {
            mkdir($this->filePath, 0755);
        }
    }

    /**
     * Starts a new background process with a given name (used to check it's status). Pass args as an array.
     *
     * @param   string  $processName
     * @param   string  $phpFile
     * @param   array   $args
     * @return  bool
     */
    public function startProcess($processName, $phpFile, array $args = array()) : bool
    {
        if (empty($processName) || empty($phpFile) || !is_array($args)) {
            throw new \InvalidArgumentException();
        }

        if (!file_exists($phpFile)) {
            throw new \BadMethodCallException('File not found: '.$phpFile);
        }

        if ($this->isProcessRunning($processName)) {
            throw new \RuntimeException('Process is already running.');
        }

        try {
            $argsEsc = array_map(function($arg) { return escapeshellarg($arg); }, $args);
            $command = PHP_BINDIR.'/php '.escapeshellarg($phpFile).' '.implode(' ', $argsEsc);

            exec(sprintf("%s > %s 2>&1 & echo $! > %s",
                    $command,
                    $this->getOutputFile($processName),
                    $this->getProcessFile($processName)
                )
            );

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Called from inside the process, to clear the current processor files.
     *
     * @param   string  $processName
     * @return  bool
     */
    public function stopProcess($processName) : bool
    {
        return unlink($this->getProcessFile($processName));
    }

    /**
     * Kills the process by name and clear the files.
     *
     * @param   string  $processName
     * @return  bool
     */
    public function cancelProcess($processName) : bool
    {
        exec('kill -9 '.$this->getPID($processName));
        return $this->stopProcess($processName);
    }

    /**
     * Check if a process is running by polling the pID, as well as checking the processFile existance.
     *
     * @param   string  $processName
     * @return  bool
     */
    public function isProcessRunning($processName) : bool
    {
        $pID = $this->getPID($processName);

        if (empty($pID)) return false;

        if (!empty($pID)) {
            $checkProcess = shell_exec('ps '.$pID);
            if (stripos($checkProcess, $pID) !== false) {
                return true;
            }
        }

        unlink($this->getProcessFile($processName));
        return false;
    }

    /**
     * Return the system pID of the process by name
     *
     * @param   string  $processName
     * @return  string
     */
    public function getPID($processName) : string
    {
        if (file_exists($this->getProcessFile($processName))) {
            $pid = file_get_contents($this->getProcessFile($processName));
            return preg_replace('/[^0-9]/', '', $pid);
        } else {
            return '';
        }
    }

    /**
     * Get the absolute file path of the Output File
     *
     * @param   string  $processName
     * @return  string
     */
    public function getOutputFile($processName) : string
    {
        $processName = $this->sanitizeProcessName($processName);

        return $this->filePath.'/'.$processName.$this->outputFilename;
    }

    /**
     * Get the absolute file path of the Process File (system pID)
     *
     * @param   string  $processName
     * @return  string
     */
    public function getProcessFile($processName) : string
    {
        $processName = $this->sanitizeProcessName($processName);

        return $this->filePath.'/'.$processName.$this->processFilename;
    }

    /**
     * Remove special characters and limit the process name length.
     *
     * @param   string  $processName
     * @return  string
     */
    protected function sanitizeProcessName($processName) : string
    {
        $processName = preg_replace('/[^a-zA-Z0-9]/', '', $processName);
        return substr($processName, 0, 20);
    }
}
