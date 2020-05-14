<?php

// Parametrit:
// $ftphost --> FTP-palvelin
// $ftpuser --> K�ytt�j�tunnus
// $ftppass --> Salasana
// $ftppath --> Kansio FTP-palvelimella jonne failit ty�nnet��n
// $ftpdest --> Minne tallennetaan
// $ftpport --> Custom portti, ei pakollinen
// $ftpskey --> SSH avain

if (empty($ftpskey)) {
  $ftpskey = "";
}

class SFTPConnection {
  private $connection;
  private $sftp;

  public function __construct($host, $port = 22) {
    $this->connection = ssh2_connect($host, $port);

    if (!$this->connection) {
      throw new Exception("Could not connect to remote host. ($host)");
    }
  }

  public function login($username, $password, $ftpskey) {

    if (!empty($ftpskey)) {
      ssh2_auth_pubkey_file($connection, $ftpuser, $ftpskey.'pub', $ftpskey);
    }

    if (!ssh2_auth_password($this->connection, $username, $password)) {
      throw new Exception("Could not login to remote host ($username, $password)");
    }

    $this->sftp = ssh2_sftp($this->connection);

    if (!$this->sftp) {
      throw new Exception("Could not initialize SFTP subsystem");
    }
  }

  private function scanDirectory($path) {
    $sftp = $this->sftp;
    $dir = "ssh2.sftp://".$sftp.$path;

    $handle = opendir($dir);

    if (!$handle) {
      throw new Exception("Could not read directory ({$path})");
    }

    return $handle;
  }

  public function getFilesFrom($path, $dest) {
    $sftp = $this->sftp;
    $dir = "ssh2.sftp://".$sftp.$path;

    $handle = $this->scanDirectory($path);

    while (false !== ($file = readdir($handle))) {
      if (in_array($file, array('.', '..'))) continue;

      $stream = fopen($dir.$file, 'r');

      if (!$stream) {
        throw new Exception("Unable to open remote file {$file}");
      }

      if (!file_put_contents($dest.$file, stream_get_contents($stream))) {
        throw new Exception("Unable to write local file to {$dest}{$file}");
      }

      fclose($stream);
    }
  }
}

// jos viimeinen merkki pathiss� ei ole kauttaviiva lis�t��n kauttaviiva...
if (substr($ftppath, -1) != "/") {
  $ftppath .= "/";
}

if (substr($ftpdest, -1) != "/") {
  $ftpdest .= "/";
}

try {
  $sftp = new SFTPConnection($ftphost, $ftpport);
  $sftp->login($ftpuser, $ftppass, $ftpskey);
  $sftp->getFilesFrom($ftppath, $ftpdest);
}
catch(Exception $e) {
  pupesoft_log("sftp_get", "Error: $e\n");
}
