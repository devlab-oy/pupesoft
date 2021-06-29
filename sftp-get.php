<?php

// Parametrit:
// $ftphost --> FTP-palvelin
// $ftpuser --> Käyttäjätunnus
// $ftppass --> Salasana
// $ftppath --> Kansio FTP-palvelimella jonne failit työnnetään
// $ftpdest --> Minne tallennetaan
// $ftpport --> Custom portti, ei pakollinen

class SFTPConnection {
  private $connection;
  private $sftp;
  private $ftpfilt;

  public function __construct($host, $port = 22, $filt = "") {
    $this->connection = ssh2_connect($host, $port);

    $this->ftpfilt = $filt;
    
    if (!$this->connection) {
      
      throw new Exception("Could not connect to remote host. ($host)");
    }
  }

  public function login($username, $password) {
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
    $dir = "ssh2.sftp://".intval($sftp).$path;

    $handle = opendir($dir);

    if (!$handle) {
      throw new Exception("Could not read directory ({$path})");
    }

    return $handle;
  }

  public function getFilesFrom($path, $dest, $ftp_exclude_files=array()) {
    
    $sftp = $this->sftp;
    $dir = "ssh2.sftp://".intval($sftp).$path;

    $handle = $this->scanDirectory($path);

    while (false !== ($file = readdir($handle))) {
      if (in_array($file, array('.', '..', ".DS_Store"))) continue;
      if (in_array($file, $ftp_exclude_files)) continue;

      if (isset($this->ftpfilt) and $this->ftpfilt != "") {
        // Skipataan ne tiedostot joissa ei ole määriteltyä stringiä nimessä
        if (stripos($file, $this->ftpfilt) === FALSE) {
          continue;
        }
      }
      
      $stream = fopen($dir.$file, 'r');

      if (!$stream) {
        throw new Exception("Unable to open remote file {$file}");
      }

      if (!file_put_contents($dest.$file, stream_get_contents($stream))) {
        throw new Exception("Unable to write local file to {$dest}{$file}");
      }

      fclose($stream);

      if (filesize($dest.$file) == 0) {
        unlink($dest.$file);
      }

      ssh2_sftp_rename($sftp, $path.$file, $path."done/".$file);
    }
  }
}

// jos viimeinen merkki pathissä ei ole kauttaviiva lisätään kauttaviiva...
if (substr($ftppath, -1) != "/") {
  $ftppath .= "/";
}

if (substr($ftpdest, -1) != "/") {
  $ftpdest .= "/";
}

if(!isset($ftp_exclude_files)) {
  $ftp_exclude_files = array();
}

try {
  
  $sftp = new SFTPConnection($ftphost, $ftpport, $ftpfilt);
  
  $sftp->login($ftpuser, $ftppass);
  
  $sftp->getFilesFrom($ftppath, $ftpdest, $ftp_exclude_files);
}
catch(Exception $e) {
  
  pupesoft_log("sftp_get", "Error: $e\n");
}
