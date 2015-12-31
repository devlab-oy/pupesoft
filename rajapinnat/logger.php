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

    if ($this->file_handle === false) {
      throw new RuntimeException('File handle is false');
    }
  }

  public function __destruct() {
    if ($this->file_handle) {
      fclose($this->file_handle);
    }
  }

  /**
   *
   * @param string  $message
   * @param Exception $exception
   */


  public function log($message, $exception = null) {
    if (!is_null($exception)) {
      $message = $this->exception_message($exception, $message);
    }
    $message = $this->format_message($message, $exception);
    $this->write($message);
  }

  /**
   *
   * @param string  $date_format
   */
  public function set_date_format($date_format) {
    $this->date_format = $date_format;
  }

  /**
   *
   * @param string  $message
   * @throws RuntimeException
   */
  private function write($message) {
    if (!is_null($this->file_handle)) {
      if (fwrite($this->file_handle, $message) === false) {
        throw new RuntimeException('Tiedostoon ei pystynyt kirjoittamaan');
      }
    }
  }

  /**
   * Returns message with exception message
   *
   * @param Exception $exception
   * @param string  $message
   * @return string
   */
  private function exception_message($exception, $message = '') {
    return "{$message} (" . $exception->getMessage() . ")";
  }

  /**
   * Timestamps log lines
   *
   * @param string  $message
   * @return string
   */
  private function format_message($message) {
    $returnthis = "[{$this->get_timestamp()}] {$message}" . PHP_EOL;
    return utf8_encode($returnthis);
  }

  /**
   *
   * @return string
   */
  private function get_timestamp() {
    return date($this->date_format);
  }
}
