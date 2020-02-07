<?php

// Tehdään SSH-tunneli Örskan palvelimelle
// ssh -f -L 4444:193.185.248.70:4444 -N devlab@193.185.248.70

require_once 'PJBS.php';

$drv = new PJBS('UTF-8', 'UTF-8');

$con = $drv->connect('jdbc:solid://mergs014:2000/pupesoft/pupe', 'pupesoft', 'pupe');

if ($con === false) {
  // jdbc ei nappaa
}

$res = $drv->exec('SELECT * FROM ASIAKPER WHERE AP_ASIAKASNRO > 1100');

$i = 1;

while ($row = $drv->fetch_array($res)) {

  echo "{$i}: {$row['AP_ASIAKASNRO']}\n";

  $i++;
}

$drv->free_result($res);
