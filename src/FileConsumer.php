<?php
namespace Sensor;
class FileConsumer extends AbstractConsumer
{

    private $file_handler;

    public function __construct($filename)
    {
        $this->file_handler = fopen($filename, 'a+');
    }

    public function send($msg)
    {
        if ($this->file_handler === null) {
            return false;
        }
        return fwrite($this->file_handler, $msg . "\n") === false ? false : true;
    }

    public function close()
    {
        if ($this->file_handler === null) {
            return false;
        }
        return fclose($this->file_handler);
    }
}