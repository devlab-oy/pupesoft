<?php


require "inc/parametrit.inc";

echo "<font class='head'>".t("Pupesoft uudet ominaisuudet")."</font><hr><br>";

exec("git log --merges 08ed62251d182f06d01a027d900c9c311405ef91^..ef9413eb9aa5b4f6dd53984e3b32095a05e006d5 |grep \"pull request\"", $pulkkarit);

foreach ($pulkkarit as $pulkkari) {
  preg_match("/pull request #([0-9]*) from/", $pulkkari, $pulkkarinro);
  
  echo "$pulkkarinro[1]: $pulkkari: <a target='pulkkari' href='https://github.com/devlab-oy/pupesoft/pull/$pulkkarinro[1]'>$pulkkarinro[1]</a><br>";
}

exit;



// Haetaan pulkkareita
function get_pulkkarit ($page=1, $branch='master') {
	$ch  = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://api.github.com/repos/devlab-oy/pupesoft/pulls?state=closed&sort=updated&direction=desc&base=$branch&page=$page");
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER, FALSE);
  curl_setopt($ch, CURLOPT_USERAGENT, "Pupesoft");
	$pulkkarit = curl_exec($ch);
  $pulkkarit = json_decode($pulkkarit);

	return($pulkkarit);
}

// Nykyinen branchi
$branch = exec("git rev-parse --abbrev-ref HEAD");

// Mikä on viimeisin merge joka meillä lokaalisti on
$date = exec("git log -n 1 --merges --date=iso --pretty='%cd'");
$updatedtime = strtotime($date);

$page = 1;

while ($pulkkarit = get_pulkkarit($page, $branch)) {
  $page++;

  foreach ($pulkkarit as $pulkkari) {
    $merged = $pulkkari->merged_at;

    if ($merged != null) {
      $mergedtime = strtotime($merged);

      if ($mergedtime > $updatedtime) {
        $title   = utf8_decode($pulkkari->title);
        $body    = utf8_decode($pulkkari->body);
        $kuka    = utf8_decode($pulkkari->user->login);

        echo "$title, $kuka, $merged<br>";
      }
      else {
        break 2;
      }
    }
  }
}