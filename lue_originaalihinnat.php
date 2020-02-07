<?php

require "inc/parametrit.inc";

//  Tehd‰‰n kaikki tarkastukset onko t‰m‰ uploadattu tiedosto ok!
if (! function_exists('tarkasta_userfile')) {
  function tarkasta_userfile($userfile, $sallitut_tiedostot) {
    global $kukarow, $yhtiorow, $_FILES, $kieli;

    $file = $_FILES[$userfile];

    // otetaan file extensio
    $path_parts = pathinfo($file['name']);
    $ext = strtoupper($path_parts['extension']);
    if ($ext == "JPEG") $ext = "jpg";

    //  Ei saatu erroreita. jatketaan..
    if ($file["error"] == 0) {

      //  Paketti riitt‰v‰n pieni mysql:lle
      $query = "SHOW variables like 'max_allowed_packet'";
      $result = mysql_query($query) or pupe_error($query);
      $varirow = mysql_fetch_array($result);

      if ($file["size"] < $varirow[1]) {
        if (in_array($ext, $sallitut_tiedostot)) {
          $file["ext"] = $ext;
          return $file;
        }
        else {
          if (count($sallitut_tiedostot)>1) {

            //  Kaunistellaan..
            echo "<font class='error'>".t("VIRHE: Tiedostomuoto ei kelpaa, sallitut tiedostomuodot on %s ja %s", $kieli, implode(", ", array_slice($sallitut_tiedostot, 0, -1)), end($sallitut_tiedostot)).".</font><br><br>";

          }
          else {
            echo "<font class='error'>".t("VIRHE: Tiedostomuoto ei kelpaa, sallittu tiedostomuoto on %s", $kieli, current($sallitut_tiedostot)).".</font><br><br>";
          }

          return false;
        }
      }
      else {
        echo "<font class='error'>".t("VIRHE: Ladattu tiedosto oli liian suuri! Suurin sallittu tiedostokoko on %s", $kieli, size_readable($file["size"]))."!</font><br><br>";
        return false;
      }
    }
    elseif ($file["error"] == 1 or $file["error"] == 2) {
      echo "<font class='error'>".t("VIRHE: Tiedosto on liian suuri!")."!</font><br><br>";
      return false;
    }
    elseif ($file["error"] == 3) {
      echo "<font class='error'>".t("VIRHE: Tiedoston lataus ep‰onnistui!")."!</font><br><br>";
      return false;
    }
    elseif ($file["error"] == 4) {
      return false;
    }
    elseif ($file["error"] == 7) {
      echo "<font class='error'>".t("VIRHE: Palvelinasetuksissa on virhe!")."!</font><br><br>";
      return false;
    }
    else {
      echo "<font class='error'>".t("VIRHE: Tapahtui virhe tallennettaessa tiedostoa!")."!</font><br><br>";
      return false;
    }
  }


}

if ($tee == "paivita") {
  $file = tarkasta_userfile("userfile", array("TXT", "CSV", "XLS"));

  //  Tiedosto ok
  if ($file !== false) {

    if (in_array($file["ext"], array("TXT", "CSV"))) {
      $file=file($file['tmp_name'], FILE_IGNORE_NEW_LINES);
    }
    else {
      require_once 'excel_reader/reader.php';

      // ExcelFile
      $data = new Spreadsheet_Excel_Reader();

      // Set output Encoding.
      $data->setOutputEncoding('CP1251');
      $data->setRowColOffset(0);
      $data->read($file['tmp_name']);

      $file = $data->sheets[0]['cells'];
    }

    unset($otsikot);
    foreach ($file as $key => $value) {

      if (!isset($otsikot)) {
        if (is_array($value)) {
          foreach ($value as $v8) {
            $otsikot[]=strtolower($v8);
          }
        }
        else {
          $rivi=explode("\t", strtolower($key));
          $otsikot=$rivi;
        }
      }
      else {
        if (is_array($value)) {
          $rivi=$value;
        }
        else {
          $rivi=explode("\t", $key);
        }

        if (count($rivi)<>count($otsikot)) {
          echo "<font class='error'>".t("VIRHE: aineistovirhe rivill‰").": $maara otsikoiden ja arvojen m‰‰r‰ ei ole sama! (".count($rivi)." != ".count($otsikot).")</font>";
          break;
        }

        $rivi=array_combine($otsikot, $rivi);

        if ($rivi["orig_tuoteno"] != "") {
          //  P‰ivitet‰‰n hinnat
          $query = "UPDATE tuotteen_orginaalit SET
                    orig_hinta  = '".str_replace(",", ".", $rivi["orig_hinta"])."'
                    WHERE yhtio = '{$kukarow["yhtio"]}' and orig_tuoteno = '{$rivi["orig_tuoteno"]}'";
          $updres = mysql_query($query) or pupe_error($query);
          $n=mysql_affected_rows();
          if ($n>0) {
            echo "<font class='message'>".t("P‰ivitet‰‰n tuotteen %s originaalihinnat. P‰ivitettiin %s rivi‰", $kieli, $rivi["orig_tuoteno"], $n)."</font><br>";
          }
          else {
            $query = "SELECT tunnus
                      FROM tuotteen_orginaalit
                      WHERE yhtio = '{$kukarow["yhtio"]}' and orig_tuoteno = '{$rivi["orig_tuoteno"]}'";
            $selres = mysql_query($query) or pupe_error($query);
            if (mysql_num_rows($selres) > 0) {
              echo "<font class='info'>".t("Yht‰‰n rivi‰ ei p‰ivitetty")." {$rivi["orig_tuoteno"]}</font><br>";
            }
            else {
              echo "<font class='error'>".t("VIRHE: Yht‰‰n rivi‰ ei lˆydetty")." {$rivi["orig_tuoteno"]}</font><br>";
            }
          }
        }
      }
    }

    //  Lis‰t‰‰n viel‰ 2 tyhj‰‰ rivi‰ loppuun
    $maara+=2;

  }
}


echo "<font class='head'>".t("Lue tuotteen originaalien hintoja")."</font><hr>";

echo "<br><form method='post' name='sendfile' enctype='multipart/form-data'>
    <input type='hidden' name='tee' value='paivita'>
    <table>
      <tr>
        <th>".t("Sis‰‰nlue tiedosto")."</th>
      </tr>
      <tr>
        <td><input type='file' name='userfile'></td>
        <td class='back'><input type='submit' value='".t("L‰het‰")."'></td>
      </tr>
      <tr>
        <td><font class='info'>".t("Sallitut tiedostot ovat xls, txt ja csv")."<br>".t("Mallitiedostot").": <a href='mallitiedostot/tuotteen_originaalihinnat.txt'>.txt</a>, <a href='mallitiedostot/tuotteen_originaalihinnat.csv'>.csv</a> ja <a href='mallitiedostot/tuotteen_originaalihinnat.xls'>.xls</a>
      </tr>
    </table>
    </form>";

require "inc/footer.inc";
