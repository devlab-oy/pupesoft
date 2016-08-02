<?php

// Kutsutaanko CLI:st‰
$php_cli = (php_sapi_name() == 'cli');

if ($php_cli) {
  // otetaan tietokanta connect
  require "inc/connect.inc";
  require "inc/functions.inc";

  if (!isset($argv[1]) or $argv[1] == '') {
    echo "Anna yhtiˆ!!!\n";
    die;
  }

  // Haetaan yhtiˆrow ja kukarow
  $yhtiorow = hae_yhtion_parametrit($argv[1]);
  $kukarow  = hae_kukarow('admin', $yhtiorow['yhtio']);

  if (empty($yhtiorow['changelog_email'])) {
    exit;
  }

  ob_start();
}
else {

  require "inc/parametrit.inc";

  echo "  <script type='text/javascript'>

      $(function() {

        $('.nayta_rivit').on('click', function() {
          var id = $(this).attr('id');
          var table = $('#table_'+id);

          if (table.is(':visible')) {
            table.hide();
          }
          else {
            table.show();
          }
        });
      });

      </script>";
}

$pupe_root_polku = dirname(__FILE__);

echo "<font class='head'>".t("Uudet ominaisuudet")."</font><hr><br>";

// Github curl API
function github_api($url) {
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
    return $pulkkarit;
  }
}

// Haetaan pulkkareita githubista
function hae_pulkkarit($updatedtime, $repo, $url) {
  global $kukarow;

  $page = 1;

  while ($pulkkarit = github_api($url."/pulls?state=closed&sort=updated&direction=desc&page=$page")) {
    $page++;

    if ($pulkkarit === FALSE) break;

    // T‰g‰t‰‰n apikutsun aikaleima
    $query  = "INSERT INTO git_paivitykset
               SET hash_pupesoft = 'github_api_request',
               repository = '$repo',
               date       = now()";
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
          $filet = github_api($url."/pulls/$number/files");

          $filetarr = array();

          foreach ($filet as $file) {
            $filename = $file->filename;

            //Tsekataan kannasta mik‰ softa
            if (substr($filename, -4) == ".php"
              and strpos($filename, "tulostakopio.php") === FALSE
              and strpos($filename, "yllapito.php") === FALSE
            ) {
              $query  = "SELECT DISTINCT sovellus, nimi, nimitys
                         FROM oikeu
                         WHERE yhtio   = '{$kukarow['yhtio']}'
                         and kuka      = ''
                         and sovellus != ''
                         and nimi      like '%$filename'
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
                     repository   = '$repo',
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

// Parsitaan git-logista kahden narustavedon v‰liset mergetykset
function git_log($repo, $edveto_hash, $taveto_hash) {
  global $pupe_root_polku;

  $pulkkarit = array();

  if ($repo == "pupenext") {
    $polku = $pupe_root_polku."/pupenext";
  }
  else {
    $polku = $pupe_root_polku;
  }

  exec("cd $polku; git log --merges $edveto_hash..$taveto_hash |grep \"pull request\"", $pulkkarit);

  $pull_ids = array();

  foreach ($pulkkarit as $pulkkari) {
    preg_match("/pull request #([0-9]*) from/", $pulkkari, $pulkkarinro);

    $pull_ids[] = $repo."#".$pulkkarinro[1];
  }

  $pull_ids = implode(",", $pull_ids);

  return $pull_ids;
}

// Katsotaan milloin ollaan viimeksi kutsuttu githubin apia
// ja haetaan omaan kantaan githubin pulkkarien tiedot
$query  = "SELECT max(date) haettu
           FROM git_paivitykset
           WHERE hash_pupesoft = 'github_api_request'";
$apires = pupe_query($query);
$apirow = mysql_fetch_assoc($apires);

$haetaanpulkkarit = TRUE;

if (!$php_cli and !empty($apirow['haettu']) and strtotime($apirow['haettu']) > strtotime("1 hour ago")) {
  // Kutsutaan apia korkeintaan kerran tunnissa
  $haetaanpulkkarit = FALSE;
}
elseif ($php_cli and !empty($apirow['haettu']) and strtotime($apirow['haettu']) > strtotime("5 minute ago")) {
  // Kutsutaan apia korkeintaan kerran 5 minuutissa kun vedet‰‰n narusta
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

  hae_pulkkarit($updatedtime, "pupesoft", "https://api.github.com/repos/devlab-oy/pupesoft");
  hae_pulkkarit($updatedtime, "pupenext", "https://api.github.com/repos/devlab-oy/pupenext");
}

if ($php_cli) {
  $display_h = "";
  // Pit‰‰ hakea kaksi uusinta vetoa, jotta voidaan hakea niitten v‰liset muutokset logista
  $limit = 2;
  $muutoksiaoli = FALSE;
}
else {
  $limit = 50;
  $display_h = "display:none;";
}

// Haetaan uusimmat narustavedot kannasta
// ja n‰ytet‰‰n ruudulla p‰ivityksess‰ tulleet uudet ominaisuudet
$query  = "SELECT *
           FROM git_paivitykset
           WHERE hash_pupesoft != 'github_api_request'
           ORDER BY id DESC
           LIMIT $limit";
$vetores = pupe_query($query);

if (mysql_num_rows($vetores)) {

  $vedot = array();
  $taveto_hash = "";
  $taveto_hash_pupenext = "";

  while ($vetorow = mysql_fetch_assoc($vetores)) {
    $vedot[] = $vetorow;
  }

  // Saadaan mukaan myˆs tulevat ominaisuudet
  array_unshift($vedot, "HEAD");

  echo "<table style='width: 90%;'>";

  foreach ($vedot as $i => $veto) {

    if (!empty($veto["hash_pupesoft"])) $taveto_hash = $veto["hash_pupesoft"];
    if (!empty($veto["hash_pupenext"])) $taveto_hash_pupenext = $veto["hash_pupenext"];

    if (isset($vedot[$i+1])) {
      if (!empty($vedot[$i+1]["hash_pupesoft"])) {
        $edveto_hash = $vedot[$i+1]["hash_pupesoft"];
      }
      if (!empty($vedot[$i+1]["hash_pupenext"])) {
        $edveto_hash_pupenext = $vedot[$i+1]["hash_pupenext"];
      }
    }
    else {
      continue;
    }

    if ($veto == "HEAD") {
      // Tulossa olevat ominaisuudet
      $query  = "SELECT group_concat(concat_ws('#', repository, id) ORDER BY feature DESC, merged) idt
                 FROM git_pulkkarit
                 WHERE merged > '{$vedot[$i+1]["date"]}'";
      $pulres = pupe_query($query);
      $pulrow = mysql_fetch_assoc($pulres);

      if ($pulrow['idt'] == "") {
        continue;
      }

      echo "<tr><th>";
      if (!$php_cli) echo "<img style='float:left;' class='nayta_rivit' id='HEAD' src='{$palvelin2}pics/lullacons/switch.png' /> ";
      echo t("Tulossa olevat ominaisuudet").":</th></tr>";
      echo "<tr><td class='back' style='padding:0px;'><table id='table_HEAD'>";

      $pull_ids = $pulrow['idt'];
    }
    else {
      $pull_ids = "";

      // Narustavedossa tulleet pupesoft-ominaisuudet
      if (!empty($edveto_hash) and !empty($taveto_hash)) {
        $pull_ids .= git_log("pupesoft", $edveto_hash, $taveto_hash);
      }

      // Narustavedossa tulleet pupenext-ominaisuudet
      if (!empty($edveto_hash_pupenext) and !empty($taveto_hash_pupenext)) {
        $pull_ids_next = git_log("pupenext", $edveto_hash_pupenext, $taveto_hash_pupenext);

        if (!empty($pull_ids) and !empty($pull_ids_next)) {
          $pull_ids .= ",";
        }
        if (!empty($pull_ids_next)) {
          $pull_ids .= $pull_ids_next;
        }
      }

      // jos ei ollut yht‰‰n pulkkaria, niin skipataan koko rivi
      if ($pull_ids == "") continue;

      echo "<tr><th>";
      if (!$php_cli) echo "<img style='float:left;' class='nayta_rivit' id='{$taveto_hash}' src='{$palvelin2}pics/lullacons/switch.png' />";
      echo t("P‰ivitys").": ".tv1dateconv($veto["date"], "P")."</th></tr>";
      echo "<tr><td class='back' style='padding:0px;'><table id='table_{$taveto_hash}' style='$display_h'>";
    }

    if ($pull_ids != "") {
      foreach (explode(",", $pull_ids) as $pull) {
        list($repo, $pullid) = explode("#", $pull);

        $query  = "SELECT *
                   FROM git_pulkkarit
                   WHERE repository = '$repo'
                   AND id           = $pullid";
        $pulres = pupe_query($query);
        $pulrow = mysql_fetch_assoc($pulres);

        $pulkkaridata = unserialize($pulrow["pull_request"]);

        $title = utf8_decode($pulkkaridata->title);
        $body  = utf8_decode($pulkkaridata->body);

        echo "<tr>";

        if ($pulrow['feature'] == 1) {
          $title     = ltrim($title, " *");
          $class     = "spec";
          $fclass    = "message";
          $titlelisa = t("Uusi %s ominaisuus", "", $repo);
        }
        else {
          $class     = "";
          $fclass    = "";
          $titlelisa = ucfirst($repo)."-".t("pienkehitys");
        }

        echo "<td class='$class' style='width: 100%;'><font class='message'>$titlelisa</font>: <font class='$fclass'>$title</font></td>";
        echo "<td class='$class' style='width: 100%;'><a target='pulkkari' href='https://github.com/devlab-oy/pupesoft/pull/$pulrow[id]'>$pulrow[id]</a></td>";
        echo "</tr>";
        echo "<tr><td colspan='3' style='width: 100%;'><pre style='white-space: pre-wrap;'>$body</pre>";

        $files = unserialize($pulrow['files']);

        if (is_array($files) and count($files) > 0) {
          echo "<br>".t("P‰ivitetyt ohjelmat").":<br><table>";

          foreach ($files as $file) {
            list($sovellus, $filenimi, $nimitys) = $file;
            echo "<tr><td>$sovellus</td><td>$nimitys</td></tr>";
          }

          echo "</table>";
        }

        echo "</td></tr>";
        echo "<tr><td class='back'><br></td></tr>";

      }
    }

    echo "</table>";
    echo "</td></tr>";

    if ($php_cli and $veto != "HEAD") {
      $muutoksiaoli = TRUE;
      break;
    }
  }

  echo "</table>";
}

if ($php_cli) {
  $viesti = ob_get_contents();
  ob_end_clean();
}

if ($php_cli and $muutoksiaoli) {
  if ($yhtiorow["kayttoliittyma"] == "U") {
    $css = $yhtiorow['css'];
  }
  else {
    $css = $yhtiorow['css_classic'];
  }

  $ulos  = "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\">\n<html>\n<head>\n";
  $ulos .= "<style type='text/css'>$css</style>\n";

  $ulos .= "<title>Pupesoft-".t("p‰ivitys")."</title>\n";
  $ulos .= "</head>\n";

  $ulos .= "<body>\n";
  $ulos .= $viesti."\n";
  $ulos .= "</body></html>";

  $params = array(
    "to"      => $yhtiorow['changelog_email'],
    "subject" => "Pupesoft update: ".date("H:i d.m.Y"),
    "ctype"   => "html",
    "body"    => $ulos
  );

  pupesoft_sahkoposti($params);
}

if (!$php_cli) {
  require "inc/footer.inc";
}
