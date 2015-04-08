<?php

// Kutsutaanko CLI:stä
$php_cli = (php_sapi_name() == 'cli');

if ($php_cli) {
  // otetaan tietokanta connect
  require "inc/connect.inc";
  require "inc/functions.inc";

  if (!isset($argv[1]) or $argv[1] == '') {
    echo "Anna yhtiö!!!\n";
    die;
  }

  // Haetaan yhtiörow ja kukarow
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

echo "<font class='head'>".t("Uudet ominaisuudet")."</font><hr><br>";

// Haetaan pulkkareita githubista
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

// Katostaan milloin ollaan viimeksi kutsuttu githubin apia
$query  = "SELECT max(date) haettu
           FROM git_paivitykset
           WHERE hash = 'github_api_request' ";
$apires = pupe_query($query);
$apirow = mysql_fetch_assoc($apires);

$haetaanpulkkarit = TRUE;

if (!$php_cli and !empty($apirow['haettu']) and strtotime($apirow['haettu']) > strtotime("1 hour ago")) {
  // Kutsutaan apia korkeintaan kerran tunnissa
  $haetaanpulkkarit = FALSE;
}
elseif ($php_cli and !empty($apirow['haettu']) and strtotime($apirow['haettu']) > strtotime("5 minute ago")) {
  // Kutsutaan apia korkeintaan kerran 5 minuutissa kun vedetään narusta
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

if ($php_cli) {
  $display_h = "";
  // Pitää hakea kaksi uusinta vetoa, jotta voidaan hakea niitten väliset muutokset logista
  $limit = 2;
  $muutoksiaoli = FALSE;
}
else {
  $limit = 50;
  $display_h = "display:none;";
}

// Haetaan uusimmat narustavedot kannasta
$query  = "SELECT *
           FROM git_paivitykset
           WHERE hash != 'github_api_request'
           ORDER BY id DESC
           LIMIT $limit";
$vetores = pupe_query($query);

if (mysql_num_rows($vetores)) {

  $vedot = array();
  $taveto_hash = "";

  while ($vetorow = mysql_fetch_assoc($vetores)) {
    $vedot[] = $vetorow;
  }

  // Saadaan mukaan myös tulevat ominaisuudet
  array_unshift($vedot, "HEAD");

  echo "<table style='width: 90%;'>";

  foreach ($vedot as $i => $veto) {

    if (!empty($veto["hash"])) $taveto_hash = $veto["hash"];

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

      echo "<tr><th>";
      if (!$php_cli) echo "<img style='float:left;' class='nayta_rivit' id='HEAD' src='{$palvelin2}pics/lullacons/switch.png' /> ";
      echo t("Tulossa olevat ominaisuudet").":</th></tr>";
      echo "<tr><td class='back' style='padding:0px;'><table id='table_HEAD'>";

      $pull_ids = $pulrow['idt'];
    }
    else {

      $pulkkarit = array();
      exec("git log --merges $edveto_hash..$taveto_hash |grep \"pull request\"", $pulkkarit);

      $pull_ids = array();

      foreach ($pulkkarit as $pulkkari) {
        preg_match("/pull request #([0-9]*) from/", $pulkkari, $pulkkarinro);

        $pull_ids[] = $pulkkarinro[1];
      }

      $pull_ids = implode(",", $pull_ids);

      // jos ei ollut yhtään pulkkaria, niin skipataan koko rivi
      if ($pull_ids == "") continue;

      echo "<tr><th>";
      if (!$php_cli) echo "<img style='float:left;' class='nayta_rivit' id='{$taveto_hash}' src='{$palvelin2}pics/lullacons/switch.png' />";
      echo "Pupesoft-".t("päivitys").": ".tv1dateconv($veto["date"], "P")."</th></tr>";
      echo "<tr><td class='back' style='padding:0px;'><table id='table_{$taveto_hash}' style='$display_h'>";
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

        echo "<td class='$class' style='width: 100%;'><font class='message'>$titlelisa</font>: <font class='$fclass'>$title</font></td>";
        echo "<td class='$class' style='width: 100%;'><a target='pulkkari' href='https://github.com/devlab-oy/pupesoft/pull/$pulrow[id]'>$pulrow[id]</a></td>";
        echo "</tr>";
        echo "<tr><td colspan='3' style='width: 100%;'><pre style='white-space: pre-wrap;'>$body</pre>";

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

  $ulos .= "<title>Pupesoft-".t("päivitys")."</title>\n";
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
