<?php

class Logger {

  private $log_filepath = null;
  private $file_handle = null;
  private $date_format = 'd.m.y H:i:s';

  public function __construct($log_filepath) {
    $this->log_filepath = $log_filepath;

    if (file_exists($this->log_filepath) && !is_writable($this->log_filepath)) {
      throw new RuntimeException('Tiedostoon ei pystynyt kirjoittamaan');
    }

    $this->file_handle = fopen($this->log_filepath, 'a');
  }

  public function __destruct() {
    if ($this->file_handle) {
      fclose($this->file_handle);
    }
  }

  public function log($message) {
    $message = $this->formatMessage($message);
    $this->write($message);
  }

  public function write($message) {
    if (!is_null($this->file_handle)) {
      if (fwrite($this->file_handle, $message) === false) {
        throw new RuntimeException('Tiedostoon ei pystynyt kirjoittamaan');
      }
    }
  }

  public function set_date_format($date_format) {
    $this->date_format = $date_format;
  }

  private function formatMessage($message) {
    return "[{$this->get_timestamp()}] {$message}" . PHP_EOL;
  }

  private function get_timestamp() {
    return date($this->date_format);
  }
}