<?php
// kansio josta luetaan
$dir = rtrim('/home/extranet/vertailut', '/');

// lähetetään file
if (isset($_GET['get'])) {
  $kaunisnimi = basename(urldecode($_GET['get']));
  $file_check = $dir . DIRECTORY_SEPARATOR . $kaunisnimi;
  if (file_exists($file_check)) {
    $file = file_get_contents($file_check);
    $lataa_tiedosto = 1;
    require 'parametrit.inc';
    echo $file;
    exit;
  } else {
    require 'functions.inc';
    echo "<font class='error'>" . t('Tiedosto ei löydy') . "</font>";
  }
}

require 'parametrit.inc';
require 'functions.inc';

echo "<font class='head'>" . t('Vertailut') . "</font><hr />";

if (! ($handle = @opendir($dir))) {
  echo "<font class='error'>" . t('Kansio ei avaudu') . ': ' . $dir . "</font>";
  exit;
}

$i=0;

// varmuuden vuoksi tyhjennetään stat cache
clearstatcache();

while ($file = readdir($handle)) {
  $lista[$i] = $file;
  $i=$i+1;
}

sort($lista);
$i=0;

echo "<table>";
while ($i < count($lista)) {

  $file = $lista[$i];
  $filename = $dir . DIRECTORY_SEPARATOR . $file;
  $i=$i+1;

  if (! is_dir($filename) and $file != "." and $file != ".." and $file != "index.php" and $file != ".htaccess") {
    $filesize  = filesize($filename)." bytes";
    $filemtime = date("H:i:s d/m/Y", filemtime($filename));

    $uri = $_SERVER['REQUEST_URI'] . '?get=' . urlencode($file);

    $type = array('b', 'kb', 'mb', 'gb');

    for ($ii=0; $filesize>1024; $ii++) {
      $filesize /= 1024;
    }

    $filesize = sprintf("%.2f", $filesize) . " $type[$ii]";

    echo "<tr>
      <td><a href='{$uri}'>$file</a></td>
      <td align='right'>$filesize</td>
      <td align='right'>$filemtime</td>
      </tr>";
  }
}

echo "</table>";

closedir($handle);
