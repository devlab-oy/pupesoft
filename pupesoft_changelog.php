<?php

require "inc/parametrit.inc";

echo "<font class='head'>".t("Uudet ominaisuudet")."</font><hr><br>";

// Haetaan pulkkareita githubista
function github_api ($url) {
  $ch  = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER, FALSE);
  curl_setopt($ch, CURLOPT_USERAGENT, "Pupesoft");

  $pulkkarit = curl_exec($ch);

  $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

  if ($code != 200) {
    return FALSE;
  }
  else {
    $pulkkarit = json_decode($pulkkarit);
	  return($pulkkarit);
  }
}

// Katostaan milloin ollaan viimeksi kutsuttu githubin apia
$query  = "SELECT max(date) haettu
           FROM git_paivitykset
           WHERE hash = 'github_api_request' ";
$apires = pupe_query($query);
$apirow = mysql_fetch_assoc($apires);

$haetaanpulkkarit = TRUE;

// Kutsutaan apia korkeintaan keran tunnissa
if (!empty($apirow['haettu']) and strtotime($apirow['haettu']) > strtotime("1 hour ago")) {
  $haetaanpulkkarit = FALSE;
}

if ($haetaanpulkkarit) {

  // Haetaan muuttuneet/uudet pulkkarit kantaan
  $query  = "SELECT max(updated) updated
             FROM git_pulkkarit";
  $prres = pupe_query($query);
  $prrow = mysql_fetch_assoc($prres);

  if (!empty($prrow['updated'])) {
    $updatedtime = strtotime($prrow['updated']);
  }
  else {
    // Ekalla ajolla haetaan vaikka parin kuukauden takaa
    $updatedtime = strtotime("2 month ago");
  }

  $page = 1;

  while ($pulkkarit = github_api("https://api.github.com/repos/devlab-oy/pupesoft/pulls?state=closed&sort=updated&direction=desc&page=$page")) {
    $page++;

    if ($pulkkarit === FALSE) break;

    // Tägätään apikutsun aikaleima
    $query  = "INSERT INTO git_paivitykset
               SET hash = 'github_api_request',
               date = now()";
    pupe_query($query);

    foreach ($pulkkarit as $pulkkari) {
      $updated = $pulkkari->updated_at;

      if ($updated != NULL) {
        if (strtotime($updated) > $updatedtime) {
          $number  = $pulkkari->number;
          $merged  = $pulkkari->merged_at;
          $title   = utf8_decode($pulkkari->title);

          $newfeature = (substr(trim($title), 0, 1) == "*") ? 1 : 0;

          $pulkkari_ser = mysql_real_escape_string(serialize($pulkkari));

          // Haetaan muuttuneet failit
          $filet = github_api("https://api.github.com/repos/devlab-oy/pupesoft/pulls/$number/files");

          $filetarr = array();

          foreach ($filet as $file) {
            $filename = $file->filename;

            //Tsekataan kannasta mikä softa
            if (substr($filename, -4) == ".php"
              and strpos($filename, "tulostakopio.php") === FALSE
              and strpos($filename, "yllapito.php") === FALSE
            ) {
              $query  = "SELECT DISTINCT sovellus, nimi, nimitys
                         FROM oikeu
                         WHERE yhtio   = '{$kukarow['yhtio']}'
                         and kuka      = ''
                         and sovellus != ''
                         and nimi like '%$filename'
                         ORDER BY sovellus, nimi";
              $menures = pupe_query($query);

              while ($menurow = mysql_fetch_assoc($menures)) {
                $filetarr[] = array($menurow["sovellus"], $menurow["nimi"], $menurow["nimitys"]);
              }
            }
          }

          $filet_ser = mysql_real_escape_string(serialize($filetarr));

          $query  = "INSERT INTO git_pulkkarit
                     SET id       = $number,
                     updated      = '$updated',
                     merged       = '$merged',
                     feature      = $newfeature,
                     pull_request = '$pulkkari_ser',
                     files        = '$filet_ser'
                     ON DUPLICATE KEY UPDATE
                     updated      = '$updated',
                     merged       = '$merged',
                     feature      = $newfeature,
                     pull_request = '$pulkkari_ser',
                     files        = '$filet_ser'";
          pupe_query($query);
        }
        else {
          break 2;
        }
      }
      else {
        break 2;
      }
    }
  }
}

// Haetaan kymmenen uiusnta narustavetoa kannasta
$query  = "SELECT *
           FROM git_paivitykset
           WHERE hash != 'github_api_request'
           ORDER BY id DESC
           LIMIT 10";
$vetores = pupe_query($query);

if (mysql_num_rows($vetores)) {

  $vedot = array();

  while ($vetorow = mysql_fetch_assoc($vetores)) {
    $vedot[] = $vetorow;
  }

  // Saadaan mukaan myös tulevat ominaisuudet
  array_unshift($vedot, "HEAD");

  echo "<table style='width: 90%;'>";

  foreach ($vedot as $i => $veto) {

    $taveto_hash = $veto["hash"];

    if (isset($vedot[$i+1])) {
      $edveto_hash = $vedot[$i+1]["hash"];
    }
    else {
      continue;
    }

    if ($veto == "HEAD") {
      $query  = "SELECT group_concat(id) idt
                 FROM git_pulkkarit
                 WHERE merged > '{$vedot[$i+1]["date"]}'
                 ORDER BY feature DESC, id";
      $pulres = pupe_query($query);
      $pulrow = mysql_fetch_assoc($pulres);

      if ($pulrow['idt'] == "") {
        continue;
      }

      echo "<tr><th colspan='3'>".t("Tulossa olevat ominaisuudet").":</th></tr>";
      $pull_ids = $pulrow['idt'];
    }
    else {
      echo "<tr><th colspan='3'>Pupesoft ".t("päivitys").": $veto[date]</th></tr>";

      $pulkkarit = array();
      exec("git log --merges $edveto_hash..$taveto_hash |grep \"pull request\"", $pulkkarit);

      $pull_ids = array();

      foreach ($pulkkarit as $pulkkari) {
        preg_match("/pull request #([0-9]*) from/", $pulkkari, $pulkkarinro);

        $pull_ids[] = $pulkkarinro[1];
      }

      $pull_ids = implode(",", $pull_ids);
    }

    if ($pull_ids != "") {

      $query  = "SELECT *
                 FROM git_pulkkarit
                 WHERE id in ($pull_ids)
                 ORDER BY feature DESC, id";
      $pulres = pupe_query($query);

      while ($pulrow = mysql_fetch_assoc($pulres)) {

        $pulkkaridata = unserialize($pulrow["pull_request"]);

        $title = utf8_decode($pulkkaridata->title);
        $body  = utf8_decode($pulkkaridata->body);

        echo "<tr>";

        if ($pulrow['feature'] == 1) {
          $title     = ltrim($title, " *");
          $class     = "spec";
          $fclass    = "message";
          $titlelisa = t("Uusi ominaisuus");
        }
        else {
          $class     = "";
          $fclass    = "";
          $titlelisa = t("Pienkehitys");
        }

        echo "<td class='$class'><font class='message'>$titlelisa</font>: <font class='$fclass'>$title</font></td>";
        echo "<td class='$class'><a target='pulkkari' href='https://github.com/devlab-oy/pupesoft/pull/$pulrow[id]'>$pulrow[id]</a></td>";
        echo "</tr>";
        echo "<tr><td colspan='3'><pre style='white-space: pre-wrap;'>$body</pre>";

        $files = unserialize($pulrow['files']);

        if (count($files)) {
          echo "<br>Päivitetyt ohjelmat:<br><table>";

          foreach ($files as $file) {
            list($sovellus, $filenimi, $nimitys) = $file;
            echo "<tr><td>$sovellus</td><td>$nimitys</td></tr>";
          }

          echo "</table>";
        }

        echo "</td></tr>";
        echo "<tr><td class='back'><br></td></tr>";

      }

      echo "<tr><td class='back'><br></td></tr>";
    }
  }

  echo "</table>";
}

require("inc/footer.inc");
