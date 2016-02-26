<?php

if (isset($_REQUEST["tee"])) {
  if ($_REQUEST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
  if ($_REQUEST["kaunisnimi"] != '') $_REQUEST["kaunisnimi"] = str_replace("/", "", $_REQUEST["kaunisnimi"]);
}

require "inc/parametrit.inc";

if (isset($tee) and $tee == "lataa_tiedosto") {
  readfile("/tmp/".$tmpfilenimi);
  exit;
}
else {

  echo "<font class='head'>", t("Budjetin yll‰pito"), "</font><hr>";

  if (is_array($luvut)) {
    $paiv = 0;
    $lisaa = 0;

    foreach ($luvut as $u_taso => $rivit) {
      foreach ($rivit as $u_tili => $solut) {
        foreach ($solut as $u_kausi => $solu) {

          $solu = str_replace(",", ".", $solu);

          if ($solu == '!' or (float) $solu != 0) {

            if ($solu == '!') $solu = 0;

            $solu = (float) $solu;

            $query = "SELECT summa
                      FROM budjetti
                      WHERE yhtio  = '$kukarow[yhtio]'
                      AND kausi    = '$u_kausi'
                      AND tyyppi   = '$tasotyyppi'
                      AND taso     = '$u_taso'
                      AND tili     = '$u_tili'
                      AND kustp    = '$kustp'
                      AND kohde    = '$kohde'
                      AND projekti = '$proj'";
            $result = pupe_query($query);

            if (mysql_num_rows($result) == 1) {

              $budjrow = mysql_fetch_assoc($result);

              if ($budjrow['summa'] != $solu) {

                if ($solu == 0) {
                  $query = "DELETE FROM budjetti
                            WHERE yhtio  = '$kukarow[yhtio]'
                            AND kausi    = '$u_kausi'
                            AND tyyppi   = '$tasotyyppi'
                            AND taso     = '$u_taso'
                            AND tili     = '$u_tili'
                            AND kustp    = '$kustp'
                            AND kohde    = '$kohde'
                            AND projekti = '$proj'";
                }
                else {
                  $query  = "UPDATE budjetti SET
                             summa        = $solu,
                             muuttaja     = '$kukarow[kuka]',
                             muutospvm    = now()
                             WHERE yhtio  = '$kukarow[yhtio]'
                             AND tyyppi   = '$tasotyyppi'
                             AND kausi    = '$u_kausi'
                             AND taso     = '$u_taso'
                             AND tili     = '$u_tili'
                             AND kustp    = '$kustp'
                             AND kohde    = '$kohde'
                             AND projekti = '$proj'";
                }
                $result = pupe_query($query);
                $paiv++;
              }
            }
            elseif ($solu != 0) {
              $query = "INSERT INTO budjetti SET
                        summa      = $solu,
                        tyyppi     = '$tasotyyppi',
                        yhtio      = '$kukarow[yhtio]',
                        kausi      = '$u_kausi',
                        taso       = '$u_taso',
                        tili       = '$u_tili',
                        kustp      = '$kustp',
                        kohde      = '$kohde',
                        projekti   = '$proj',
                        laatija    = '$kukarow[kuka]',
                        luontiaika = now()";
              $result = pupe_query($query);
              $lisaa++;
            }
          }
        }
      }
    }

    echo "<font class='message'>".t("P‰ivitin")." $paiv. ".t("Lis‰sin")." $lisaa</font><br><br>";
  }

  if (isset($tyyppi)) {

    $sel1 = "";
    $sel2 = "";
    $sel3 = "";
    $sel4 = "";
    $sel5 = "";

    switch ($tyyppi) {
    case (1):
      $sel1 = 'selected';
      break;
    case (2):
      $sel2 = 'selected';
      break;
    case (3):
      $sel3 = 'selected';
      break;
    case (4):
      $sel4 = 'selected';
      break;
    case (5):
      $sel5 = 'selected';
      break;
    }
  }

  echo "<form method='post'>";

  echo "<table>";

  echo "  <tr>
      <th>".t("Tyyppi")."</th>
      <td><select name = 'tyyppi'>
      <option value='4' $sel4>".t("Tuloslaskelma")." ".t("sis‰inen")."</option>
      <option value='5' $sel5>".t("Tuloslaskelma")." ".t("ulkoinen")."</option>
      <option value='2' $sel2>".t("Vastattavaa")."</option>
      <option value='1' $sel1>".t("Vastaavaa")."</option>
      </select></td></tr>";

  echo "<tr><th>".t("Tilikausi");

  $query = "SELECT *
            FROM tilikaudet
            WHERE yhtio = '$kukarow[yhtio]'
            ORDER BY tilikausi_alku desc";
  $vresult = pupe_query($query);

  echo "</th><td><select name='tkausi'>";

  while ($vrow = mysql_fetch_assoc($vresult)) {
    $sel="";
    if ($tkausi == $vrow['tunnus']) {
      $sel = "selected";
    }
    echo "<option value = '$vrow[tunnus]' $sel>".tv1dateconv($vrow["tilikausi_alku"])." - ".tv1dateconv($vrow["tilikausi_loppu"])."</option>";
  }

  echo "</select></td></tr>";

  echo "<tr><th>".t("Kustannuspaikka")."</th>";

  $query = "SELECT tunnus, nimi
            FROM kustannuspaikka
            WHERE yhtio   = '$kukarow[yhtio]'
            and kaytossa != 'E'
            and tyyppi    = 'K'
            ORDER BY koodi+0, koodi, nimi";
  $vresult = pupe_query($query);

  echo "<td><select name='kustp'><option value='0'>".t("Ei valintaa")."</option>";

  while ($vrow = mysql_fetch_assoc($vresult)) {
    $sel="";
    if ($kustp == $vrow['tunnus']) {
      $sel = "selected";
    }
    echo "<option value = '$vrow[tunnus]' $sel>$vrow[tunnus] $vrow[nimi]</option>";
  }
  echo "</select></td>";
  echo "</tr>";
  echo "<tr><th>".t("Kohde")."</th>";

  $query = "SELECT tunnus, nimi
            FROM kustannuspaikka
            WHERE yhtio   = '$kukarow[yhtio]'
            and kaytossa != 'E'
            and tyyppi    = 'O'
            ORDER BY koodi+0, koodi, nimi";
  $vresult = pupe_query($query);

  echo "<td><select name='kohde'><option value='0'>".t("Ei valintaa")."</option>";

  while ($vrow = mysql_fetch_assoc($vresult)) {
    $sel="";
    if ($kohde == $vrow['tunnus']) {
      $sel = "selected";
    }
    echo "<option value = '$vrow[tunnus]' $sel>$vrow[nimi]</option>";
  }

  echo "</select></td>";
  echo "</tr>";
  echo "<tr><th>".t("Projekti")."</th>";

  $query = "SELECT tunnus, nimi
            FROM kustannuspaikka
            WHERE yhtio   = '$kukarow[yhtio]'
            and kaytossa != 'E'
            and tyyppi    = 'P'
            ORDER BY koodi+0, koodi, nimi";
  $vresult = pupe_query($query);

  echo "<td><select name='proj'><option value='0'>".t("Ei valintaa")."</option>";

  while ($vrow = mysql_fetch_assoc($vresult)) {
    $sel="";
    if ($proj == $vrow['tunnus']) {
      $sel = "selected";
    }
    echo "<option value = '$vrow[tunnus]' $sel>$vrow[nimi]</option>";
  }

  echo "</select></td></tr>";

  $sel = array();
  $sel[$rtaso] = "SELECTED";

  echo "<tr><th valign='top'>".t("Budjetointitaso")."</th>
      <td><select name='rtaso'>";

  $query = "SELECT max(length(taso)) taso
            from taso
            where yhtio = '$kukarow[yhtio]'";
  $vresult = pupe_query($query);
  $vrow = mysql_fetch_assoc($vresult);

  echo "<option value='TILI'>".t("Tili taso")."</option>\n";

  for ($i=$vrow["taso"]-1; $i >= 0; $i--) {
    echo "<option ".$sel[$i+2]." value='".($i+2)."'>".t("Taso %s", '', $i+1)."</option>\n";
  }

  echo "</select></td></tr>";

  echo "</table>";
  echo "<br>";
  echo "<input type='submit' VALUE='".t("N‰yt‰/Tallenna")."'>";
  echo "<br><br>";

  $excelsarake = 0;
  $excelrivi++;

  $tasotyyppi  = "U";
  $tilityyppi  = "ulkoinen_taso";
  $cleantyyppi = $tyyppi;

  // Sis‰inen tuloslaskelma
  if ($tyyppi == 4) {
    $tasotyyppi = "S";
    $tyyppi = 3;
    $tilityyppi = "sisainen_taso";
  }

  // Ulkoinen tuloslaskelma
  if ($tyyppi == 5) {
    $tyyppi = 3;
  }

  // T‰m‰ tulee tallentaa kantaan
  echo "<input type='hidden' name='tasotyyppi' value='$tasotyyppi'>";

  // Haetaan kaikki tasot ja rakennetaan tuloslaskelma-array
  $query = "SELECT *
            FROM taso
            WHERE yhtio  = '$kukarow[yhtio]'
            and tyyppi   = '$tasotyyppi'
            and LEFT(taso, 1) in (BINARY '$tyyppi')
            and taso    != ''
            ORDER BY taso";
  $tasores = pupe_query($query);

  // Jos meill‰ on tasoja piirret‰‰n taulukko
  while ($tasorow = mysql_fetch_assoc($tasores)) {
    // mill‰ tasolla ollaan (1,2,3,4,5,6)
    $tasoluku = strlen($tasorow["taso"]);

    // tasonimi talteen (rightp‰dd‰t‰‰n ÷:ll‰, niin saadaan oikeaan j‰rjestykseen)
    $apusort = str_pad($tasorow["taso"], 20, "Z");
    $tasonimi[$apusort] = $tasorow["nimi"];

    // pilkotaan taso osiin
    $taso = array();
    for ($i = 0; $i < $tasoluku; $i++) {
      $taso[$i] = substr($tasorow["taso"], 0, $i+1);
    }
  }

  if (isset($tkausi)) {
    $query = "SELECT *
              FROM tilikaudet
              WHERE yhtio = '$kukarow[yhtio]'
              and tunnus  = '$tkausi'";
    $vresult = pupe_query($query);

    if (mysql_num_rows($vresult) == 1) {
      $tilikaudetrow = mysql_fetch_assoc($vresult);
    }
  }

  if (is_array($tilikaudetrow) and count($tasonimi) > 0) {

    if (is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE) {

      $kasiteltava_tiedoto_path = $_FILES['userfile']['tmp_name'];
      $path_parts = pathinfo($_FILES['userfile']['name']);
      $ext = strtoupper($path_parts['extension']);

      $excelrivit = pupeFileReader($kasiteltava_tiedoto_path, $ext);

      echo "<br /><br /><font class='message'>".t("Luetaan l‰hetetty tiedosto")."...<br><br></font>";

      $headers     = array();
      $taulunrivit = array();

      // rivim‰‰r‰ exceliss‰
      $excelrivimaara = count($excelrivit);

      // sarakem‰‰r‰ exceliss‰
      $excelsarakemaara = count($headers);

      for ($excei = 1; $excei < $excelrivimaara; $excei++) {
        for ($excej = 3; $excej < 20; $excej++) {

          $nro = trim($excelrivit[$excei][$excej]);

          if ((float) $nro != 0 or $nro == "!") {
            $taulunrivit[$excelrivit[$excei][0]][$excelrivit[$excei][1]][$excej-3] = $nro;
          }
        }
      }
    }
    else {
      unset($taulunrivit);
    }

    include 'inc/pupeExcel.inc';

    $worksheet    = new pupeExcel();
    $format_bold = array("bold" => TRUE);
    $excelrivi    = 0;
    $excelsarake = 0;

    $worksheet->writeString($excelrivi, $excelsarake, t("Tili / Taso"), $format_bold);
    $excelsarake++;

    $worksheet->writeString($excelrivi, $excelsarake, t("Nro"), $format_bold);
    $excelsarake++;

    $worksheet->writeString($excelrivi, $excelsarake, t("Nimi"), $format_bold);
    $excelsarake++;

    echo t("Budjettiluvun voi poistaa huutomerkill‰ (!)")."<br><br>";

    //Parametrit mihin t‰m‰ taulukko liittyy
    echo "<table>\n";
    echo "<tr>\n";
    echo "<td class='back'></td>\n";

    $j = 0;
    $raja = '0000-00';
    $rajataulu = array();
    $budjetit = array();

    while ($raja < substr($tilikaudetrow['tilikausi_loppu'], 0, 7)) {

      $vuosi = substr($tilikaudetrow['tilikausi_alku'], 0, 4);
      $kk = substr($tilikaudetrow['tilikausi_alku'], 5, 2);
      $kk += $j;

      if ($kk > 12) {
        $vuosi++;
        $kk -= 12;
      }

      if ($kk < 10) $kk = '0'.$kk;

      $raja = $vuosi."-".$kk;
      $rajataulu[$j] = $vuosi.$kk;

      echo "<th>$raja</th>\n";
      $j++;

      $worksheet->writeString($excelrivi, $excelsarake, $raja, $format_bold);
      $excelsarake++;

      // Haetaan budjetit
      $query = "SELECT *
                from budjetti
                where yhtio  = '$kukarow[yhtio]'
                and kausi    = '$vuosi$kk'
                and kustp    = '$kustp'
                and kohde    = '$kohde'
                and projekti = '$proj'";
      $xresult = pupe_query($query);

      while ($brow = mysql_fetch_assoc($xresult)) {
        $budjetit[(string) $brow["taso"]][(string) $brow["tili"]][(string) $brow["kausi"]] = $brow["summa"];
      }
    }

    echo "</tr>\n";

    $excelrivi++;
    $excelsarake = 0;

    // sortataan array indexin (tason) mukaan
    ksort($tasonimi);

    // loopataan tasot l‰pi
    foreach ($tasonimi as $key_c => $value) {

      $key = str_replace("Z", "", $key_c); // ÷-kirjaimet pois

      // tulostaan rivi vain jos se kuuluu rajaukseen
      if (strlen($key) <= $rtaso or $rtaso == "TILI") {

        $class = "";

        // laitetaan ykkˆs ja kakkostason rivit tummalla selkeyden vuoksi
        if (strlen($key) < 3 and $rtaso > 2) $class = "tumma";

        if ($rtaso == "TILI") {

          $class = "tumma";

          $query = "SELECT tilino, nimi
                    FROM tili
                    WHERE yhtio = '$kukarow[yhtio]'
                    AND $tilityyppi = '$key'
                    ORDER BY 1,2";
          $tiliresult = pupe_query($query);

          while ($tilirow = mysql_fetch_assoc($tiliresult)) {

            $worksheet->writeString($excelrivi, $excelsarake, "TILI");
            $excelsarake++;

            $worksheet->writeString($excelrivi, $excelsarake, $tilirow['tilino']);
            $excelsarake++;

            $worksheet->writeString($excelrivi, $excelsarake, $tilirow['nimi']);
            $excelsarake++;

            echo "<tr><th nowrap>$tilirow[tilino] - $tilirow[nimi]</th>\n";

            for ($k = 0; $k < $j; $k++) {

              $nro = "";

              if (isset($taulunrivit["TILI"][$tilirow["tilino"]][$k])) {
                $nro = $taulunrivit["TILI"][$tilirow["tilino"]][$k];
              }
              elseif (isset($budjetit[$key][$tilirow["tilino"]][$rajataulu[$k]])) {
                $nro = $budjetit[$key][$tilirow["tilino"]][$rajataulu[$k]];
              }

              echo "<td align='right' nowrap><input type='text' name = 'luvut[$key][$tilirow[tilino]][$rajataulu[$k]]' value='$nro' size='10'></td>\n";

              $worksheet->write($excelrivi, $excelsarake, $nro);
              $excelsarake++;
            }

            echo "</tr>\n";

            $excelsarake = 0;
            $excelrivi++;
          }
        }

        $worksheet->writeString($excelrivi, $excelsarake, "TASO");
        $excelsarake++;

        $worksheet->writeString($excelrivi, $excelsarake, $key);
        $excelsarake++;

        $worksheet->writeString($excelrivi, $excelsarake, $value);
        $excelsarake++;

        echo "<tr><th nowrap>$key - $value</th>\n";

        for ($k = 0; $k < $j; $k++) {

          $nro = "";

          if (isset($taulunrivit["TASO"][$key][$k])) {
            $nro = $taulunrivit["TASO"][$key][$k];
          }
          elseif (isset($budjetit[$key]["0"][$rajataulu[$k]])) {
            $nro = $budjetit[$key]["0"][$rajataulu[$k]];
          }

          echo  "<td class='$class' align='right' nowrap><input type='text' name = 'luvut[$key][0][$rajataulu[$k]]' value='$nro' size='10'></td>\n";

          $worksheet->write($excelrivi, $excelsarake, $nro);
          $excelsarake++;
        }

        echo "</tr>\n";

        $excelsarake = 0;
        $excelrivi++;

        // kakkostason j‰lkeen aina yks tyhj‰ rivi.. paitsi jos otetaan vain kakkostason raportti
        if (strlen($key) == 2 and ($rtaso > 2 or $rtaso == "TILI")) {
          echo "<tr><td class='back'>&nbsp;</td></tr>\n";
        }

        if (strlen($key) == 1 and ($rtaso > 1 or $rtaso == "TILI")) {
          echo "<tr><td class='back'><br><br></td></tr>\n";
        }
      }

      $edkey = $key;
    }

    echo "</table></form><br>";

    $excelnimi = $worksheet->close();

    echo "<form method='post' class='multisubmit'>";
    echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
    echo "<input type='hidden' name='kaunisnimi' value='Budjettimatriisi.xlsx'>";
    echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
    echo "<table>";
    echo "<tr><th>", t("Tallenna budjetti (xlsx)"), ":</th>";
    echo "<td class='back'><input type='submit' value='", t("Tallenna"), "'></td></tr>";
    echo "</table></form><br />";

    echo "<form method='post' name='sendfile' enctype='multipart/form-data'>";
    echo "<input type='hidden' name='tee'    value = 'file'>";
    echo "<input type='hidden' name='kustp' value = '$kustp'>";
    echo "<input type='hidden' name='kohde' value = '$kohde'>";
    echo "<input type='hidden' name='proj'  value = '$proj'>";
    echo "<input type='hidden' name='tyyppi' value = '$cleantyyppi'>";
    echo "<input type='hidden' name='tkausi' value = '$tkausi'>";
    echo "<input type='hidden' name='rtaso'  value = '$rtaso'>";
    echo "<table>";
    echo "<tr><th>", t("Valitse tiedosto"), "</th><td><input type='file' name='userfile' /></td><td class='back'><input type='submit' value='", t("L‰het‰"), "' /></td></tr>";
    echo "</table>";
    echo "</form>";

  }
  else {
    echo t("Ei tasoja!");
  }

  require "inc/footer.inc";
}
