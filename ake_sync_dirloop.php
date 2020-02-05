<?php

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
}

echo "Ake/Forba synkronointi\n\n";

//require ("/Users/juppe/Sites/devlab/pupesoft/inc/connect.inc");
//require ("/Users/juppe/Sites/devlab/pupesoft/inc/functions.inc");

require "/var/www/html/pupesoft/inc/connect.inc";
require "/var/www/html/pupesoft/inc/functions.inc";

// Logitetaan ajo
cron_log();

$timeparts = explode(" ", microtime());
$starttime = $timeparts[1].substr($timeparts[0], 1);

$kuka  = "akesync";

if (!isset($argv[1]) or $argv[1] == '') {
  echo "Anna polku!!!\n";
  die;
}

if (!isset($argv[2]) or $argv[2] == '') {
  echo "Anna akedatan nimi esim. (AKE_2009-05)!!!\n";
  die;
}

if (!isset($argv[3]) or $argv[3] == '') {
  echo "Anna yhtiöiden koodit! esim artr,allr\n";
  die;
}

if ($handle = opendir($argv[1])) {

  echo "Directory handle: $handle - $argv[1]\n";
  echo "Files:\n";

  $filet = array();

  while (false !== ($file = readdir($handle))) {

    if (substr($file, 0, 1) != "." and strpos($file, "TPERA") !== FALSE) {
      echo "$file\n";

      $filet[] = $file;
    }
  }

  closedir($handle);
}

echo "\n\n";

foreach ($filet as $filename) {
  passthru("php ake_sync.php ".$argv[1].$filename." ".$argv[2]." ".$argv[3]);
}
