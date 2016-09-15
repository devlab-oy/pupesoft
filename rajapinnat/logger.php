<?php

require_once "inc/functions.inc";

class Logger {
  private $log_file = null;

  public function __construct($log_file) {
    $this->log_file = $log_file;
  }

  public function log($message, $exception = null) {
    if (!is_null($exception)) {
      $message = "{$message} (" . $exception->getMessage() . ")";
    }

    pupesoft_log($this->log_file, $message);
  }

  public function log_file() {
    return $this->log_file;
  }
}
