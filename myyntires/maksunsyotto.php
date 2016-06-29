<?php

require "../inc/parametrit.inc";

if ($livesearch_tee == "ASIAKASHAKU") {
  livesearch_asiakashaku();
  exit;
}

js_popup();
enable_ajax();

if ($tee != "CHECK" or $tiliote != 'Z') {
  echo "<font class='head'>".t("Suorituksen käsinsyöttö")."</font><hr>";
}

// Tultiinko tiliotteelta ja olisiko tämä jo viety?
if ($tiliote == 'Z' and $ytunnus != '' and $asiakasid != '') {
  $query = "SELECT tunnus
            FROM suoritus
            WHERE yhtio        = '$kukarow[yhtio]'
            AND asiakas_tunnus = '$asiakasid'
            AND summa          = '$summa'
            AND kirjpvm        = '$vva-$kka-$ppa'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) != 0) {
    echo "<br><font class='error'>".t("HUOM: Samanlainen suoritus on jo olemassa")."!</font><br><br>";
  }
}

if ($tee == "CHECK") {

  $errors = 0;

  if ($tilino == 0) {
    $error[] = t("Valitse saajan tilinumero").".";
  }

  if (strlen($summa) == 0) {
    $error[] = t("Anna suorituksen summa").".";
  }
  else {
    preg_match("/^[-+]?[0-9]+([,.][0-9]+)?/", $summa, $tsumma);

    if ($summa != $tsumma[0]) {
      $error[] = t("Summa on virheellinen").".";
    }
    else {
      $pistesumma = str_replace(",", ".", $summa);
    }
  }

  $kka = sprintf('%02d', $kka);
  $ppa = sprintf('%02d', $ppa);
  if ($vva < 1000) $vva += 2000;

  if (!checkdate($kka, $ppa, $vva)) {
    $error[] = t("Tarkista maksupäivä! Anna maksupäivä muodossa PP.KK.VVVV");
  }

  $errors = count($error);

  if ($errors > 0) {
    echo "<ul>";
    foreach ($error as $err) {
      echo "<li>$err</li>\n";
    }
    echo "</ul>";
    // mennään takasin selaukseen
    $tee = "";
  }
  else {
    // kaikki ok, laitetaan rivi kantaan
    $tee = "SYOTTO";
  }
}

if ($tee == "SYOTTO") {

  $myyntisaamiset = 0;

  switch ($vastatili) {
  case 'myynti' :
    $myyntisaamiset = $yhtiorow['myyntisaamiset'];
    break;
  case 'factoring' :
    $myyntisaamiset = $yhtiorow['factoringsaamiset'];
    break;
  case 'konserni' :
    $myyntisaamiset = $yhtiorow['konsernimyyntisaamiset'];
    break;
  default :
    echo t("Virheellinen vastatilitieto")."!";
    exit;
  }

  if ($myyntisaamiset == 0) {
    echo t("Myyntisaamiset-tilin selvittely epäonnistui");
    exit;
  }

  $query = "SELECT yriti.*, valuu.kurssi
            FROM yriti
            JOIN valuu ON (valuu.yhtio = yriti.yhtio and yriti.valkoodi = valuu.nimi)
            WHERE yriti.yhtio  = '$kukarow[yhtio]'
            AND yriti.tunnus   = '$tilino'
            and yriti.kaytossa = ''";
  $result = pupe_query($query);

  if ($row = mysql_fetch_assoc($result)) {

    $tilistr        = $row["tilino"];
    $kassatili      = $row["oletus_rahatili"];
    $tilivaluutta  = $row["valkoodi"];
    $tilikurssi    = $row["kurssi"];

    if ($row["valkoodi"] != $yhtiorow['valkoodi']) {
      // koitetaan hakea maksupäivän kurssi
      $query = "SELECT *
                FROM valuu_historia
                WHERE kotivaluutta = '$yhtiorow[valkoodi]'
                AND valuutta       = '$row[valkoodi]'
                AND kurssipvm      <= '$vva-$kka-$ppa'
                ORDER BY kurssipvm DESC
                LIMIT 1";
      $result = pupe_query($query);

      if (mysql_num_rows($result) == 1) {
        $row = mysql_fetch_assoc($result);
        $tilikurssi = $row["kurssi"];
        echo "<font class='message'>".t("Käytettiin kurssia")." $row[kurssipvm] = $tilikurssi</font><br>";
      }
      else {
        echo "<font class='message'>".t("Ei löydetty maksupäivän kurssia, käyttään tämänhetkistä kurssia")." $tilikurssi</font><br>";
      }
    }

    //suorituksen summa kotivaluutassa
    $omasumma = round($pistesumma * $tilikurssi, 2);
  }
  else {
    echo "<font class='error'>".t("Valitun pankkitilin tiedot ovat puutteelliset, tarkista pankkitilin tiedot!")."</font>";
    exit;
  }

  if (substr($ytunnus, 0, 1) == "£") {
    $query = "SELECT nimi, liitostunnus
              FROM lasku
              WHERE ytunnus = '".substr($ytunnus, 1)."'
              and yhtio     = '$kukarow[yhtio]'";
  }
  else {
    $query = "SELECT nimi
              FROM asiakas
              WHERE tunnus = '$asiakasid'
              and yhtio    = '$kukarow[yhtio]'";
  }
  $result = pupe_query($query);

  if ($row = mysql_fetch_assoc($result)) {
    $asiakas_nimi = pupesoft_cleanstring($row["nimi"]);
    $asiakasstr = substr($row["nimi"], 0, 12);

    if (substr($ytunnus, 0, 1) == "£") {
      $asiakasid = $row['liitostunnus'];
    }
  }

  // tehdään dummy-lasku johon liitetään kirjaukset
  $tapvm = $vva."-".$kka."-".$ppa;

  $query = "INSERT into lasku
            SET yhtio   = '$kukarow[yhtio]',
            tapvm      = '$tapvm',
            tila       = 'X',
            laatija    = '$kukarow[kuka]',
            luontiaika = now()";
  $result = pupe_query($query);
  $ltunnus = mysql_insert_id($GLOBALS["masterlink"]);

  $selite = pupesoft_cleanstring($selite);

  list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($myyntisaamiset);

  // Myyntisaamiset
  $query = "INSERT INTO tiliointi SET
            yhtio            = '$kukarow[yhtio]',
            laatija          = '$kukarow[kuka]',
            laadittu         = now(),
            tapvm            = '$tapvm',
            ltunnus          = '$ltunnus',
            tilino           = '$myyntisaamiset',
            summa            = $omasumma * -1,
            summa_valuutassa = $pistesumma * -1,
            valkoodi         = '$tilivaluutta',
            selite           = 'Käsin syötetty suoritus $asiakas_nimi $selite',
            lukko            = '1',
            kustp            = '{$kustp_ins}',
            kohde            = '{$kohde_ins}',
            projekti         = '{$projekti_ins}'";
  $result = pupe_query($query);
  $ttunnus = mysql_insert_id($GLOBALS["masterlink"]);

  list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($kassatili, $kustannuspaikka);

  // Rahatili
  $query = "INSERT INTO tiliointi SET
            yhtio            = '$kukarow[yhtio]',
            laatija          = '$kukarow[kuka]',
            laadittu         = now(),
            tapvm            = '$tapvm',
            ltunnus          = '$ltunnus',
            tilino           = '$kassatili',
            summa            = '$omasumma',
            summa_valuutassa = '$pistesumma',
            valkoodi         = '$tilivaluutta',
            selite           = 'Käsin syötetty suoritus $asiakas_nimi $selite',
            aputunnus        = '$ttunnus',
            lukko            = '1',
            kustp            = '{$kustp_ins}',
            kohde            = '{$kohde_ins}',
            projekti         = '{$projekti_ins}'";
  $result = pupe_query($query);

  // Näin kaikki tiliöinnit ovat kauniisti linkitetty toisiinsa
  $query = "INSERT INTO suoritus SET
            yhtio          = '$kukarow[yhtio]',
            tilino         = '$tilistr',
            nimi_maksaja   = '$asiakasstr',
            summa          = '$pistesumma',
            maksupvm       = '$tapvm',
            kirjpvm        = '$tapvm',
            asiakas_tunnus = '$asiakasid',
            ltunnus        = '$ttunnus',
            viesti         = '$selite',
            valkoodi       = '$tilivaluutta',
            kurssi         = '$tilikurssi'";
  $result = pupe_query($query);
  $suoritus_tunnus = mysql_insert_id($GLOBALS["masterlink"]);

  echo "<font class='message'>".t("Suoritus tallennettu").".</font><br>";

  // tulostetaan suorituksesta kuitti
  if ($tulostakuitti != "") {

    $query = "SELECT *
              FROM asiakas
              WHERE yhtio = '$kukarow[yhtio]'
              and tunnus  = '$asiakasid'";
    $result = pupe_query($query);
    $asiakasrow = mysql_fetch_assoc($result);

    $summa = $pistesumma;

    require "tilauskasittely/tulosta_kuitti.inc";

    // pdffän piirto
    $firstpage = alku();
    rivi($firstpage);
    loppu($firstpage);

    $pdffilenimi = "/tmp/kuitti-".md5(uniqid(mt_rand(), true)).".pdf";

    //kirjoitetaan pdf faili levylle..
    $fh = fopen($pdffilenimi, "w");
    if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus epäonnistui $pdffilenimi");
    fclose($fh);

    // itse print komento...
    $line = exec("$tulostakuitti $pdffilenimi");
    $line = exec("$tulostakuitti $pdffilenimi");

    //poistetaan tmp file samantien kuleksimasta...
    unlink($pdffilenimi);

    echo "<font class='message'>".t("Kuitti (2 kpl) tulostettu").".</font><br>";
  }

  // takasin jonnekin
  if (isset($s_tallenna_kohdista)) {
    echo "<br>";
    $oikeus = 1;
    $tila = "kohdistaminen";
    $PHP_SELF = 'manuaalinen_suoritusten_kohdistus.php';
    require "manuaalinen_suoritusten_kohdistus.php";
    exit;
  }

  if ($tiliote == 'Z') {
    require "inc/footer.inc";
    exit;
  }
  else {
    $ytunnus = "";
    $tee = "";
  }
}

if (!empty($viite_haku) and $tee == "ETSI") {
  $query = "SELECT liitostunnus, valkoodi
            FROM lasku
            WHERE yhtio = '$kukarow[yhtio]'
            and tila    = 'U'
            and alatila = 'X'
            and viite   = '$viite_haku'";
  $result  = pupe_query($query);

  if ($asiakas = mysql_fetch_assoc($result)) {
    echo "<font class='message'>".t("Maksaja löytyi viitteellä")." $viite_haku:</font><br><br>";
    $asiakasid = $asiakas["liitostunnus"];
  }
}
elseif (!empty($laskunro_haku) and $tee == "ETSI") {
  $query = "SELECT liitostunnus
            FROM lasku
            WHERE yhtio  = '$kukarow[yhtio]'
            and tila     = 'U'
            and alatila  = 'X'
            and laskunro = '$laskunro_haku'";
  $result  = pupe_query($query);

  if ($asiakas = mysql_fetch_assoc($result)) {
    echo "<font class='message'>".t("Maksaja löytyi laskunumerolla")." $laskunro_haku:</font><br><br>";
    $asiakasid = $asiakas["liitostunnus"];
  }
}

if (empty($asiakasid) and $tee == "ETSI") {
  $tee = "";
}

if (!empty($asiakasid) and $tee == "ETSI") {
  // jos meillä on IE käytössä (eli ei livesearchia) tai ollaan submitattu jotain tekstiä, niin tehdään YTUNNUS haku, muuten asiakasid haku
  if ($ytunnus == "" and stripos($_SERVER['HTTP_USER_AGENT'], "MSIE") !== FALSE or stripos($_SERVER['HTTP_USER_AGENT'], "EXPLORER") !== FALSE or !is_numeric($asiakasid)) {
    $ytunnus = $asiakasid;
    $asiakasid = "";
  }

  require "inc/asiakashaku.inc";
  $tee = "";

  // otetaan muutparametrit takas
  list ($summa, $ppa, $kka, $vva, $mtili, $selite) = explode("#", $muutparametrit);
}

// meillä on ytunnus, tehdään syöttöruutu
if ($ytunnus != '' and $tee == "") {

  //päivämäärän tarkistus
  $tilalk = explode("-", $yhtiorow["myyntireskontrakausi_alku"]);
  $tillop = explode("-", $yhtiorow["myyntireskontrakausi_loppu"]);

  $tilalkpp = $tilalk[2];
  $tilalkkk = $tilalk[1]-1;
  $tilalkvv = $tilalk[0];

  $tilloppp = $tillop[2];
  $tillopkk = $tillop[1]-1;
  $tillopvv = $tillop[0];

  echo "  <SCRIPT LANGUAGE=JAVASCRIPT>

      function verify(){
        var pp = document.formi.ppa;
        var kk = document.formi.kka;
        var vv = document.formi.vva;

        pp = Number(pp.value);
        kk = Number(kk.value)-1;
        vv = Number(vv.value);

        if (vv < 1000) {
          vv = vv+2000;
        }

        var dateSyotetty = new Date(vv,kk,pp);
        var dateTallaHet = new Date();
        var ero = (dateTallaHet.getTime() - dateSyotetty.getTime()) / 86400000;

        var tilalkpp = $tilalkpp;
        var tilalkkk = $tilalkkk;
        var tilalkvv = $tilalkvv;
        var dateTiliAlku = new Date(tilalkvv,tilalkkk,tilalkpp);
        dateTiliAlku = dateTiliAlku.getTime();


        var tilloppp = $tilloppp;
        var tillopkk = $tillopkk;
        var tillopvv = $tillopvv;
        var dateTiliLoppu = new Date(tillopvv,tillopkk,tilloppp);
        dateTiliLoppu = dateTiliLoppu.getTime();

        dateSyotetty = dateSyotetty.getTime();

        if(dateSyotetty < dateTiliAlku || dateSyotetty > dateTiliLoppu) {
          var msg = '".t("VIRHE: Syötetty päivämäärä ei sisälly kuluvaan tilikauteen")."!';
          alert(msg);

          skippaa_tama_submitti = true;
          return false;
        }

        if(ero >= 30) {
          var msg = '".t("Oletko varma, että haluat päivätä laskun yli 30pv menneisyyteen")."?';

          if (confirm(msg)) {
            return true;
          }
          else {
            skippaa_tama_submitti = true;
            return false;
          }
        }
        if(ero <= -14) {
          var msg = '".t("Oletko varma, että haluat päivätä laskun yli 14pv tulevaisuuteen")."?';

          if (confirm(msg)) {
            return true;
          }
          else {
            skippaa_tama_submitti = true;
            return false;
          }
        }

        if (vv < dateTallaHet.getFullYear()) {
          if (5 < dateTallaHet.getDate()) {
            var msg = '".t("Oletko varma, että haluat päivätä laskun menneisyyteen")."?';

            if (confirm(msg)) {
              return true;
            }
            else {
              skippaa_tama_submitti = true;
              return false;
            }
          }
        }
        else if (vv == dateTallaHet.getFullYear()) {
          if (kk < dateTallaHet.getMonth() && 5 < dateTallaHet.getDate()) {
            var msg = '".t("Oletko varma, että haluat päivätä laskun menneisyyteen")."?';

            if (confirm(msg)) {
              return true;
            }
            else {
              skippaa_tama_submitti = true;
              return false;
            }
          }
        }

      }
    </SCRIPT>";

  echo "<form method='post' onSubmit = 'return verify()' name='formi'>";
  echo "<input type='hidden' name='tee' value='CHECK'/>\n";
  echo "<input type='hidden' name='lopetus' value='$lopetus'>";

  if (substr($ytunnus, 0, 1) == "£") {
    $query = "SELECT concat('£',tunnus) tunnus, nimi, ytunnus
              FROM lasku
              WHERE ytunnus='".substr($ytunnus, 1)."'
              and yhtio = '$kukarow[yhtio]'";
  }
  else {
    $query = "SELECT asiakas.tunnus, asiakas.nimi, ytunnus, konserniyhtio, factoring_id
              FROM asiakas
              LEFT JOIN maksuehto ON maksuehto.tunnus = asiakas.maksuehto
              WHERE asiakas.tunnus = '$asiakasid'
              and asiakas.yhtio    = '$kukarow[yhtio]'";
  }
  $result  = pupe_query($query);
  $asiakas = mysql_fetch_assoc($result);

  echo "<input type='hidden' name='asiakasid' value='$asiakas[tunnus]'/>\n
      <input type='hidden' name='ytunnus' value='$asiakas[ytunnus]'/>\n
      <input type='hidden' name='mtili' value='$mtili'>\n
      <input type='hidden' name='pvm' value='$vva-$kka-$ppa'>\n";
  echo "<table>
  <tr>
    <th>".t("Maksaja")."</th>
    <td>
    $asiakas[nimi]
    </td>
  </tr>
  <tr>
    <th>".t("Saajan tilinumero")."</th>
    <td>";

  $haluttuselvittely = 0;

  if (isset($mtili)) {
    $query  = "SELECT *
               FROM yriti
               WHERE yhtio  = '$kukarow[yhtio]'
               and kaytossa = ''
               and tunnus   = '$mtili'
               order by oletus_rahatili, nimi";
    $result = pupe_query($query);

    if (mysql_num_rows($result) == 1) {
      $row = mysql_fetch_assoc($result);
      $haluttuselvittely = $row['oletus_selvittelytili'];
    }
  }
  if ($haluttuselvittely == 0) $haluttuselvittely = $yhtiorow['selvittelytili'];

  $query  = "SELECT *
             FROM yriti
             WHERE yhtio  = '$kukarow[yhtio]'
             and kaytossa = ''
             order by tilino";
  $result = pupe_query($query);

  $sel='';
  echo "<select name='tilino'>";
  echo "<option value='0'>".t("Valitse")."</option>\n";

  while ($row = mysql_fetch_assoc($result)) {
    if (!isset($tilino)) {
      if ($row['oletus_rahatili'] == $haluttuselvittely) $sel='selected';
    }
    else {
      if ($tilino == $row['tilino']) $sel='selected';
    }
    echo "<option value='$row[tunnus]' $sel>$row[nimi] $row[tilino] $row[valkoodi]</option>\n";
    $sel='';
  }
  echo "</select>";

  // Tehdään kustannuspaikkapopup
  $query = "SELECT tunnus, nimi
            FROM kustannuspaikka
            WHERE yhtio   = '$kukarow[yhtio]'
            and tyyppi    = 'K'
            and kaytossa != 'E'
            ORDER BY koodi+0, koodi, nimi";
  $kustpvr = pupe_query($query);

  echo "<select name='kustannuspaikka'>";
  echo "<option value=''>".t("Ei kustannuspaikkaa")."</option>";

  while ($apurow = mysql_fetch_assoc($kustpvr)) {
    echo "<option value ='$apurow[tunnus]'>$apurow[nimi]</option>";
  }

  echo "</select></td>
  </tr>";

  $sel1='';
  $sel2='';

  if (isset($asiakas['factoring_id'])) $sel2='checked';
  if ($sel2=='') $sel1='checked';

  echo "<tr><th>".t("Kohdistus")."</th><td>";
  if ($asiakas['konserniyhtio'] != '') {
    echo "<input type='hidden' name='vastatili' value='konserni'>".t("Konsernimyyntisaamiset");
  }
  else {
    echo "<input type='radio' name='vastatili' value='myynti' $sel1> ".t("Myyntisaamiset")."
        <input type='radio' name='vastatili' value='factoring' $sel2> ".t("Factoringsaamiset")."</td></tr>";
  }
  echo "
  <tr>
    <th>".t("Maksupäivä (pp kk vvvv)")."</th>";

  if ($kka == "")
    $kka = date("m", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
  if ($vva == "")
    $vva = date("Y", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
  if ($ppa == "")
    $ppa = date("d", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));


  echo "<td><input name='ppa' size='3' value='$ppa'> <input name='kka' size='3' value='$kka'> <input name='vva' size=5' value='$vva'></td>";

  echo "  </tr>
  <tr>
    <th>".t("Summa")."</th>
    <td>
    <input type='text' name='summa' value='$summa'>
    </td>
  </tr>
  <tr>
    <th>".t("Selite")."</th>
    <td>
    <input type='text' name='selite' size='40' value='$selite'>
    </td>
  </tr>
  <tr>
    <th>".t("Tulosta suorituksesta kuitti")."</th>
    <td>";

  echo "<select name='tulostakuitti'>";
  echo "<option value=''>".t("Ei tulosteta")."</option>";

  $querykieli = "SELECT *
                 FROM kirjoittimet
                 WHERE yhtio  = '$kukarow[yhtio]'
                 AND komento != 'EDI'
                 ORDER BY kirjoitin";
  $kires = pupe_query($querykieli);

  while ($kirow=mysql_fetch_assoc($kires)) {
    echo "<option value='$kirow[komento]'>$kirow[kirjoitin]</option>";
  }

  echo "</select>";

  echo "</td>
  </tr>
  </table>
  <br>
  <input type='submit' name='s_tallenna_kohdista' value='".t("Tallenna suoritus ja siirry kohdistukseen")."'>
  <input type='submit' name='s_tallenna' value='".t("Tallenna suoritus")."'>
  </form>";

  $formi = "formi";
  $kentta = "summa";
}

if ($tee == "" and $ytunnus == "") {

  if (empty($iframe)) {
    $maksaja_haku = htmlentities($maksaja_haku);
  }

  echo "<form method='post' name='maksaja'>";
  echo "<input type='hidden' name='tee' value='ETSI'>";
  echo "<input type='hidden' name='lopetus' value='$lopetus'>";
  echo "<input type='hidden' name='muutparametrit' value='$summa#$ppa#$kka#$vva#$mtili#$selite'>";

  echo "<table>";
  echo "<tr>";
  echo "<th>", t("Maksaja"), "</th>";
  echo "<td>", livesearch_kentta("maksaja", "ASIAKASHAKU", "asiakasid", 300, $maksaja_haku), "</td>";
  echo "<td class='back'>", asiakashakuohje(), "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td class='back' colspan='2'><br>tai etsi maksaja laskunumerolla:<br><br></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>", t("Laskunro")."</th><td><input type='text' name='laskunro_haku' size='25'></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td class='back' colspan='2'><br>tai etsi maksaja viitenumerolla:<br><br></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>", t("Viitenumero")."</th><td><input type='text' name='viite_haku' size='25'></td>";
  echo "</tr>";
  echo "</table>";

  echo "<br>";
  echo "<input type='submit' class='hae_btn' value='".t("Etsi")."'>";
  echo "</form>";

  $formi = "maksaja";
  $kentta = "asiakasid";
}

require "inc/footer.inc";
