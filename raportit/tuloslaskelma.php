<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta JA master kantaa *//
$useslave = 1;

if (isset($_POST["teetiedosto"])) {
  if ($_POST["teetiedosto"] == 'lataa_tiedosto') $lataa_tiedosto=1;
  if ($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
}

if (strpos($_SERVER['SCRIPT_NAME'], "tuloslaskelma.php")  !== FALSE) {
  require "../inc/parametrit.inc";
}
else {
  if ((int) $mul_proj[0] == 0) {
    die("<font class='error'>Älä edes yritä!</font>");
  }
}

// Setataan muuttujat
if (!isset($from))           $from = "";
if (!isset($tltee))          $tltee = "";
if (!isset($toim))           $toim = "";
if (!isset($rtaso))          $rtaso = "TILI";
if (!isset($kaikkikaudet))   $kaikkikaudet = "";
if (!isset($vertailued))     $vertailued = "";
if (!isset($vertailubu))     $vertailubu = "";
if (!isset($vertailubu2))    $vertailubu2 = "";
if (!isset($ei_yhteensa))    $ei_yhteensa = "";
if (!isset($konsernirajaus)) $konsernirajaus = "";
if (!isset($sarakebox))      $sarakebox = "";
if (!isset($tkausi))         $tkausi = "";
if (!isset($ulisa))          $ulisa = "";
if (!isset($lisa))           $lisa = "";
if (!isset($toim_tee))       $toim_tee = "";

if (!isset($desi) or $desi == "") $desi = "2";
if (!isset($tyyppi) or $tyyppi == "") $tyyppi = "4";
if (!isset($tarkkuus) or $tarkkuus == "") $tarkkuus = "1";
if (!isset($alvp) or $alvp == "") $alvp = date("d", mktime(0, 0, 0, (date("m")+1), 0, date("Y")));
if (!isset($alvv) or $alvv == "") $alvv = date("Y");
if (!isset($alvk) or $alvk == "") $alvk = date("m");

// Rajataanko rappari näyttämään vain Omaa KustannusPaikkaa
if (substr($toim, -4) == '_OKP') {
  // käyttäjän osasto kertoo oletuskustannuspaikan
  $vainomakustp = TRUE;

  if (empty($kukarow["osasto"])) {
    echo "<br><br>".t("Käyttäjätiedoistasi puuttuu osasto")."!<br>";
    require "inc/footer.inc";
    exit;
  }

  $mul_kustp    = array();
  $mul_kustp[]  = $kukarow["osasto"];
}

if (isset($teetiedosto)) {
  if ($teetiedosto == "lataa_tiedosto") {
    readfile("/tmp/".$tmpfilenimi);
    exit;
  }
}
else {
  // Muokataan tilikartan rakennetta
  if (isset($tasomuutos)) {
    require "../tasomuutos.inc";
    require '../inc/footer.inc';
    exit;
  }

  echo "<font class='head'>".t("Tase/tuloslaskelma")."</font><hr>";

  if ($tltee == "aja") {

    if ($tyyppi == 4) {
      $sisulk = "sisainen";
      $sisulk_txt = "sisäinen";
    }
    else {
      $sisulk = "ulkoinen";
      $sisulk_txt = "ulkoinen";
    }

    if ($tyyppi == 3 or $tyyppi == 4) {
      $tililisa = " and left(tili.tilino, 1) >= 3 ";
    }
    elseif ($tyyppi == 1) {
      $tililisa = " and left(tili.tilino, 1) = 1 ";
    }
    elseif ($tyyppi == 2) {
      $tililisa = " and left(tili.tilino, 1) = 2 ";
    }
    else {
      $tililisa = " and left(tili.tilino, 1) <= 2 ";
    }

    // Katsotaan, että kaikilla tileillä on tasot kunnossa
    $query = "SELECT tili.*, taso.tunnus tasotunnus
              FROM tili
              LEFT JOIN taso ON taso.yhtio=tili.yhtio and tili.{$sisulk}_taso=taso.taso
              WHERE tili.yhtio = '$kukarow[yhtio]'
              $tililisa
              and taso.tunnus is null";
    $result = pupe_query($query);

    if (mysql_num_rows($result) > 0 ) {

      enable_ajax();

      echo "<br><font class='error'>".t("HUOM")."!  ".t("Sinulla on %s tiliä joilta puuttuu tai on virheellinen %s taso", "", mysql_num_rows($result), $sisulk_txt)."!<br>".t("Jos näillä tileillä on tapahtumia, niin tapahtumat eivät näy laskelmassa").".</font><br><a href=\"javascript:toggleGroup('eee')\">".t("Näytä / Piilota")." ".t("tilit")."</a><br><br>";

      if ($toim_tee != "") {
        echo "<div id='eee' style='display:block'>";
      }
      else {
        echo "<div id='eee' style='display:none'>";
      }

      echo "<table>";
      echo "<tr><th>".t("Tili")."</th><th>".t("$sisulk_txt taso")."</th></tr>";

      while ($vrow = mysql_fetch_assoc($result)) {
        if ($vrow["tasotunnus"] == "") {
          echo "<tr><td>$vrow[tilino]</td><td>$vrow[ulkoinen_taso]</td></tr>";
        }
      }

      echo "</table>";
      echo "</div>";
    }
  }

  if ($tltee == "aja") {
    if ($plvv * 12 + $plvk > $alvv * 12 + $alvk) {
      echo "<font class='error'>".t("Alkukausi on päättymiskauden jälkeen")."</font><br>";
      $tltee = '';
    }
  }

  $query = "SELECT min(tilikausi_alku) alkukausi, min(tilikausi_loppu) loppukausi
            FROM tilikaudet
            WHERE yhtio = '$kukarow[yhtio]'";
  $result = pupe_query($query);
  $ekakausirow = mysql_fetch_assoc($result);

  if (empty($ekakausirow['alkukausi'])) {
    $ekakausirow['alkukausi'] = date("Y")-10;
  }

  if (empty($ekakausirow['loppukausi'])) {
    $ekakausirow['loppukausi'] = date("Y")-10;
  }

  // tehdään käyttöliittymä, näytetään aina
  $sel = array(4 => "", 3 => "", "T" => "", 1 => "", 2 => "");
  if ($tyyppi != "") $sel[$tyyppi] = "SELECTED";

  echo "<br>";
  echo "  <form action = 'tuloslaskelma.php' method='post'>
      <input type = 'hidden' name = 'tltee' value = 'aja'>
      <input type='hidden' name='toim' value='$toim'>
      <table>";

  echo "  <tr>
      <th valign='top'>".t("Tyyppi")."</th>
      <td>";

  echo "  <select name = 'tyyppi'>
      <option $sel[4] value='4'>".t("Sisäinen tuloslaskelma")."</option>
      <option $sel[3] value='3'>".t("Ulkoinen tuloslaskelma")."</option>
      <option $sel[T] value='T'>".t("Tase")."</option>
      <option $sel[1] value='1'>".t("Vastaavaa")." (".t("Varat").")</option>
      <option $sel[2] value='2'>".t("Vastattavaa")." (".t("Velat").")</option>
      </select>";

  echo "</td></tr>";

  if (!isset($plvv)) {
    $query = "SELECT *
              FROM tilikaudet
              WHERE yhtio         = '$kukarow[yhtio]'
              and tilikausi_alku  <= now()
              and tilikausi_loppu >= now()";
    $result = pupe_query($query);
    $tilikausirow = mysql_fetch_assoc($result);

    $plvv = substr($tilikausirow['tilikausi_alku'], 0, 4);
    $plvk = substr($tilikausirow['tilikausi_alku'], 5, 2);
    $plvp = substr($tilikausirow['tilikausi_alku'], 8, 2);
  }

  echo "  <th valign='top'>".t("Alkukausi")."</th>
      <td><select name='plvv'>";

  $sel = array();
  $sel[$plvv] = "SELECTED";

  for ($i = date("Y"); $i >= (float) substr($ekakausirow['alkukausi'], 0, 4); $i--) {

    if (!isset($sel[$i])) {
      $sel[$i] = "";
    }

    echo "<option value='$i' $sel[$i]>$i</option>";
  }

  echo "</select>";

  $sel = array();
  $sel[$plvk] = "SELECTED";

  echo "<select name='plvk'>";

  for ($opt = 1; $opt <= 12; $opt++) {
    $opt = sprintf("%02d", $opt);

    if (!isset($sel[$opt])) {
      $sel[$opt] = "";
    }

    echo "<option $sel[$opt] value = '$opt'>$opt</option>";
  }

  echo "</select>";

  $sel = array();
  $sel[$plvp] = "SELECTED";

  echo "<select name='plvp'>";

  for ($opt = 1; $opt <= 31; $opt++) {
    $opt = sprintf("%02d", $opt);

    if (!isset($sel[$opt])) {
      $sel[$opt] = "";
    }

    echo "<option $sel[$opt] value = '$opt'>$opt</option>";
  }

  echo "</select></td></tr>";

  echo "<tr>
    <th valign='top'>".t("Loppukausi")."</th>
    <td><select name='alvv'>";

  $sel = array();
  $sel[$alvv] = "SELECTED";

  for ($i = date("Y")+1; $i >= (float) substr($ekakausirow['loppukausi'], 0, 4); $i--) {
    echo "<option value='$i' $sel[$i]>$i</option>";
  }

  echo "</select>";

  $sel = array();
  $sel[$alvk] = "SELECTED";

  echo "<select name='alvk'>";

  for ($opt = 1; $opt <= 12; $opt++) {
    $opt = sprintf("%02d", $opt);

    if (!isset($sel[$opt])) {
      $sel[$opt] = "";
    }

    echo "<option $sel[$opt] value = '$opt'>$opt</option>";
  }

  echo "</select>";

  $sel = array();
  $sel[$alvp] = "SELECTED";

  echo "<select name='alvp'>";

  for ($opt = 1; $opt <= 31; $opt++) {
    $opt = sprintf("%02d", $opt);

    if (!isset($sel[$opt])) {
      $sel[$opt] = "";
    }

    echo "<option $sel[$opt] value = '$opt'>$opt</option>";
  }

  echo "</select></td></tr>";

  echo "<tr><th valign='top'>".t("tai koko tilikausi")."</th>";

  $query = "SELECT *
            FROM tilikaudet
            WHERE yhtio = '$kukarow[yhtio]'
            ORDER BY tilikausi_alku DESC";
  $vresult = pupe_query($query);

  echo "<td><select name='tkausi'><option value='0'>".t("Ei valintaa");

  while ($vrow=mysql_fetch_assoc($vresult)) {
    $sel="";
    if ($tkausi == $vrow["tunnus"]) {
      $sel = "selected";
    }
    echo "<option value = '$vrow[tunnus]' $sel>".tv1dateconv($vrow["tilikausi_alku"])." - ".tv1dateconv($vrow["tilikausi_loppu"]);
  }
  echo "</select></td>";
  echo "</tr>";

  $sel = array();
  $sel[$rtaso] = "SELECTED";

  echo "<tr><th valign='top'>".t("Raportointitaso")."</th>
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

  $sel = array(1 => "", 1000 => "", 10000 => "", 100000 => "", 1000000 => "");
  $sel[$tarkkuus] = "SELECTED";

  echo "<tr><th valign='top'>".t("Lukujen tarkkuus")."</th>
      <td><select name='tarkkuus'>
        <option $sel[1]   value='1'>".t("Älä jaa lukuja")."</option>
        <option $sel[1000] value='1000'>".t("Jaa 1000:lla")."</option>
        <option $sel[10000] value='10000'>".t("Jaa 10 000:lla")."</option>
        <option $sel[100000] value='100000'>".t("Jaa 100 000:lla")."</option>
        <option $sel[1000000] value='1000000'>".t("Jaa 1 000 000:lla")."</option>
        </select>";

  $sel = array(0 => "", 1 => "", 2 => "");
  $sel[$desi] = "SELECTED";

  echo "<select name='desi'>
      <option $sel[2] value='2'>2 ".t("desimaalia")."</option>
      <option $sel[1] value='1'>1 ".t("desimaalia")."</option>
      <option $sel[0] value='0'>0 ".t("desimaalia")."</option>
      </select></td></tr>";

  $vchek = $bchek = $b2chek = $ychek = $vpvmchek = "";
  if ($vertailued != "")  $vchek = "CHECKED";
  if ($vertailubu != "")  $bchek = "CHECKED";
  if ($vertailubu2 != "") {
    $bchek = "";
    $b2chek = "CHECKED";
  }
  if ($vertailupvm != "") $vpvmchek = "CHECKED";
  if ($ei_yhteensa != "") $ychek = "CHECKED";

  $kausi = array("VY" => "", "KY" => "", "V" => "", "K" => "", "Y" => "");
  $kausi[$kaikkikaudet] = "SELECTED";

  echo "<tr><th valign='top'>".t("Näkymä")."</th>";

  echo "<td><select name='kaikkikaudet'>
      <option value='VY' $kausi[VY]>".t("Näytä vain viimeisin kausi ja yhteensäsumma")."</option>
      <option value='KY' $kausi[KY]>".t("Näytä kaikki kaudet ja yhteensäsumma")."</option>
      <option value='V'  $kausi[V]>".t("Näytä vain viimeisin kausi")."</option>
      <option value='K'  $kausi[K]>".t("Näytä kaikki kaudet")."</option>
      <option value='Y'  $kausi[Y]>".t("Näytä vain yhteensäsumma")."</option>
      </select>
      </td></tr>";

  echo "<tr><th valign='top'>".t("Vertailu")."</th>";
  echo "<td>";
  echo "&nbsp;<input type='checkbox' name='vertailued' $vchek> ".t("Edellinen vastaava");
  echo "<br>&nbsp;<input type='checkbox' name='vertailubu' $bchek> ".t("Budjetti");
  echo "<br>&nbsp;<input type='checkbox' name='vertailubu2' $b2chek> ".t("Budjetti tileittäin, summataan tileiltä tasoille");
  echo "<br>&nbsp;<input type='checkbox' name='vertailupvm' $vpvmchek> ".t("Vertailu vapaavalintaisen kauden kanssa");
  echo "</td></tr>";

  echo "<tr>";
  echo "<th valign='top'>" . t("Vertailukausi (alku)") . "</th>";
  echo "<td><select name='vavv'>";

  $sel = array();
  $sel[$vavv] = "SELECTED";

  for ($i = date("Y"); $i >= date("Y") - 5; $i--) {
    if (!isset($sel[$i])) {
      $sel[$i] = "";
    }

    echo "<option value='$i' $sel[$i]>$i</option>";
  }

  echo "</select>";

  $sel = array();
  $sel[$vakk] = "SELECTED";

  echo "<select name='vakk'>";

  for ($opt = 1; $opt <= 12; $opt++) {
    $opt = sprintf("%02d", $opt);

    if (!isset($sel[$opt])) {
      $sel[$opt] = "";
    }

    echo "<option $sel[$opt] value = '$opt'>$opt</option>";
  }

  echo "</select>";

  $sel = array();
  $sel[$vapp] = "SELECTED";

  echo "<select name='vapp'>";

  for ($opt = 1; $opt <= 31; $opt++) {
    $opt = sprintf("%02d", $opt);

    if (!isset($sel[$opt])) {
      $sel[$opt] = "";
    }

    echo "<option $sel[$opt] value = '$opt'>$opt</option>";
  }

  echo "</select>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th valign='top'>" . t("Vertailukausi (loppu)") . "</th>";
  echo "<td><select name='vlvv'>";

  $sel = array();
  $sel[$vlvv] = "SELECTED";

  for ($i = date("Y"); $i >= date("Y") - 5; $i--) {
    echo "<option value='$i' $sel[$i]>$i</option>";
  }

  echo "</select>";

  $sel = array();
  $sel[$vlkk] = "SELECTED";

  echo "<select name='vlkk'>";

  for ($opt = 1; $opt <= 12; $opt++) {
    $opt = sprintf("%02d", $opt);

    if (!isset($sel[$opt])) {
      $sel[$opt] = "";
    }

    echo "<option $sel[$opt] value = '$opt'>$opt</option>";
  }

  echo "</select>";

  $sel = array();
  $sel[$vlpp] = "SELECTED";

  echo "<select name='vlpp'>";

  for ($opt = 1; $opt <= 31; $opt++) {
    $opt = sprintf("%02d", $opt);

    if (!isset($sel[$opt])) {
      $sel[$opt] = "";
    }

    echo "<option $sel[$opt] value = '$opt'>$opt</option>";
  }

  echo "</select>";
  echo "</td>";
  echo "</tr>";

  echo "<tr><th valign='top'>".t("Konsernirajaus")."</th>";

  $konsel = array("AT" => "", "T" => "", "A" => "");
  $konsel[$konsernirajaus] = "SELECTED";

  echo "<td><select name='konsernirajaus' DISABLED>
      <option value=''>".t("Näytetään kaikki tiliöinnit")."</option>
      <option value='AT' $konsel[AT]>".t("Näytetään konserniasiakkaiden ja konsernitoimittajien tiliöinnit")."</option>
      <option value='T'  $konsel[T]>".t("Näytetään konsernitoimitajien tiliöinnit")."</option>
      <option value='A'  $konsel[A]>".t("Näytetään konserniasiakkaiden tiliöinnit")."</option>
      </select>
      HUOM: Ominaisuus huollossa!</td></tr>";

  echo "<tr><th valign='top'>".t("Sarakkeet")."</th>";

  $bchek = array("KUSTP" => "", "KOHDE" => "", "PROJEKTI" => "", "ASOSASTO" => "", "ASRYHMA" => "");

  if (is_array($sarakebox)) {
    foreach ($sarakebox as $sara => $sarav) {
      if ($sara != "") $bchek[$sara] = "CHECKED";
    }
  }

  echo "<td>";
  echo "&nbsp;<input type='checkbox' name='sarakebox[KUSTP]' $bchek[KUSTP]> ".t("Kustannuspaikoittain");
  echo "<br>&nbsp;<input type='checkbox' name='sarakebox[KOHDE]' $bchek[KOHDE]> ".t("Kohteittain");
  echo "<br>&nbsp;<input type='checkbox' name='sarakebox[PROJEKTI]' $bchek[PROJEKTI]> ".t("Projekteittain");
  echo "<br>&nbsp;<input type='checkbox' name='sarakebox[ASOSASTO]' $bchek[ASOSASTO] DISABLED> ".t("Asiakasosastoittain");
  echo "<br>&nbsp;<input type='checkbox' name='sarakebox[ASRYHMA]' $bchek[ASRYHMA] DISABLED> ".t("Asiakasryhmittäin");
  echo "</td></tr>";

  if ($teepdf != "") $vchek = "CHECKED";
  if ($teexls != "") $bchek = "CHECKED";

  echo "<tr><th valign='top'>".t("Tulostus")."</th>";
  echo "<td>";
  echo "&nbsp;<input type='checkbox' name='teepdf' value='OK' $vchek> ".t("Tee PDF");
  echo "<br>&nbsp;<input type='checkbox' name='teexls' value='OK' $bchek> ".t("Tee Excel");
  echo "</td></tr>";

  echo "</table><br>";

  // HUOM: ASIAKASRAJAUKSET HUOLLOSSA!
  // $monivalintalaatikot = array("KUSTP", "KOHDE", "PROJEKTI", "ASIAKASOSASTO", "ASIAKASRYHMA");
  $monivalintalaatikot = array("KUSTP", "KOHDE", "PROJEKTI");
  $noautosubmit = TRUE;

  require "tilauskasittely/monivalintalaatikot.inc";

  echo "<br><input type = 'submit' value = '".t("Näytä")."'></form><br><br>";

  if ($tltee == "aja") {

    // Desimaalit
    $muoto = "%.". (int) $desi . "f";

    if ($plvk == '' or $plvv == '') {
      $plvv = substr($yhtiorow['tilikausi_alku'], 0, 4);
      $plvk = substr($yhtiorow['tilikausi_alku'], 5, 2);
    }

    if ($tyyppi == "T") {
      // Vastaavaa Varat
      $otsikko   = "Tase";
      $kirjain   = "U";
      $aputyyppi   = "1', BINARY '2";
      $tilikarttataso = "ulkoinen_taso";
      $luku_kerroin = 1;
    }
    elseif ($tyyppi == "1") {
      // Vastaavaa Varat
      $otsikko   = "Vastaavaa Varat";
      $kirjain   = "U";
      $aputyyppi   = 1;
      $tilikarttataso = "ulkoinen_taso";
      $luku_kerroin = 1;
    }
    elseif ($tyyppi == "2") {
      // Vastattavaa Velat
      $otsikko   = "Vastattavaa Velat";
      $kirjain   = "U";
      $aputyyppi   = 2;
      $tilikarttataso = "ulkoinen_taso";
      $luku_kerroin = 1;
    }
    elseif ($tyyppi == "3") {
      // Ulkoinen tuloslaskelma
      $otsikko   = "Ulkoinen tuloslaskelma";
      $kirjain   = "U";
      $aputyyppi   = 3;
      $tilikarttataso = "ulkoinen_taso";
      $luku_kerroin = -1;
    }
    else {
      // Sisäinen tuloslaskelma
      $otsikko   = "Sisäinen tuloslaskelma";
      $kirjain   = "S";
      $aputyyppi   = 3;
      $tilikarttataso = "sisainen_taso";
      $luku_kerroin = -1;
    }

    // edellinen taso
    $taso           = array();
    $tasonimi       = array();
    $summattavattasot  = array();
    $summa          = array();
    $kaudet         = array();
    $tilisumma      = array();

    if ((int) $tkausi > 0) {
      $query = "SELECT tilikausi_alku, tilikausi_loppu
                FROM tilikaudet
                WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tkausi'";
      $result = pupe_query($query);
      $tkrow = mysql_fetch_assoc($result);

      $plvv = substr($tkrow['tilikausi_alku'], 0, 4);
      $plvk = substr($tkrow['tilikausi_alku'], 5, 2);
      $plvp = substr($tkrow['tilikausi_alku'], 8, 2);

      $alvv = substr($tkrow['tilikausi_loppu'], 0, 4);
      $alvk = substr($tkrow['tilikausi_loppu'], 5, 2);
      $alvp = substr($tkrow['tilikausi_loppu'], 8, 2);
    }

    // Tarkistetaan vielä päivämäärät
    if (!checkdate($plvk, $plvp, $plvv)) {
      echo "<font class='error'>".t("VIRHE: Alkupäivämäärä on virheellinen")."!</font><br>";
      $tltee = "";
    }

    if (!checkdate($alvk, $alvp, $alvv)) {
      echo "<font class='error'>".t("VIRHE: Loppupäivämäärä on virheellinen")."!</font><br>";
      $tltee = "";
    }

    $laskujoini    = "";
    $asiakasjoini  = "";
    $konsernijoini  = "";
    $tilijoini    = "";
    $konsernilisa  = "";
    $bulisa      = "";
    $groupsarake  = "";

    $asiakasosastot  = "";
    $asiakasryhmat   = "";
    $kustannuspaikat = "";
    $kohteet      = "";
    $projektit      = "";

    // Ajetaan rapotti kustannuspaikoittain
    if (isset($sarakebox["KUSTP"]) and $sarakebox["KUSTP"] != "") {
      // Kun tehdään monta saraketta niin ei joinata budjettiin
      $vertailubu = "";

      // Näitä tarvitaan kun piirretään headerit
      $query = "SELECT tunnus, concat_ws(' - ', if (koodi='', NULL, koodi), nimi) nimi
                FROM kustannuspaikka
                WHERE yhtio   = '$kukarow[yhtio]'
                and kaytossa != 'E'
                and tyyppi    = 'K'
                ORDER BY koodi+0, koodi, nimi";
      $vresult = pupe_query($query);

      while ($vrow = mysql_fetch_assoc($vresult)) {
        $kustannuspaikat[$vrow["tunnus"]] = $vrow["nimi"];
      }

      $groupsarake .= "'kustannuspaikat::',tiliointi.kustp,'#!#',";
    }

    // Ajetaan rapotti kohteittain
    if (isset($sarakebox["KOHDE"]) and $sarakebox["KOHDE"] != "") {
      // Kun tehdään monta saraketta niin ei joinata budjettiin
      $vertailubu = "";

      // Näitä tarvitaan kun piirretään headerit
      $query = "SELECT tunnus, concat_ws(' - ', if (koodi='', NULL, koodi), nimi) nimi
                FROM kustannuspaikka
                WHERE yhtio   = '$kukarow[yhtio]'
                and kaytossa != 'E'
                and tyyppi    = 'O'
                ORDER BY koodi+0, koodi, nimi";
      $vresult = pupe_query($query);

      while ($vrow = mysql_fetch_assoc($vresult)) {
        $kohteet[$vrow["tunnus"]] = $vrow["nimi"];
      }

      $groupsarake .= "'kohteet::',tiliointi.kohde,'#!#',";
    }

    // Ajetaan rapotti projekteittain
    if (isset($sarakebox["PROJEKTI"]) and $sarakebox["PROJEKTI"] != "") {
      // Kun tehdään monta saraketta niin ei joinata budjettiin
      $vertailubu = "";

      // Näitä tarvitaan kun piirretään headerit
      $query = "SELECT tunnus, concat_ws(' - ', if (koodi='', NULL, koodi), nimi) nimi
                FROM kustannuspaikka
                WHERE yhtio   = '$kukarow[yhtio]'
                and kaytossa != 'E'
                and tyyppi    = 'P'
                ORDER BY koodi+0, koodi, nimi";
      $vresult = pupe_query($query);

      while ($vrow = mysql_fetch_assoc($vresult)) {
        $projektit[$vrow["tunnus"]] = $vrow["nimi"];
      }

      $groupsarake .= "'projektit::',tiliointi.projekti,'#!#',";
    }

    // Tarvitaan lasku/asiakasjoini jos rajataan tai ajetaan raportti asiakasosatoittain tai asiakasryhmittäin
    if ((isset($lisa) and strpos($lisa, "asiakas.") !== FALSE) or (isset($sarakebox["ASOSASTO"]) and $sarakebox["ASOSASTO"] != "") or (isset($sarakebox["ASRYHMA"]) and $sarakebox["ASRYHMA"] != "")) {
      // Kun tehdään asiakas tai toimittajajoini niin ei vertailla budjettiin koska siinä ei olisi mitään järkeä
      $vertailubu = "";

      $laskujoini = " JOIN lasku ON tiliointi.yhtio = lasku.yhtio and tiliointi.ltunnus = lasku.tunnus ";
      $asiakasjoini = " JOIN asiakas ON lasku.yhtio = asiakas.yhtio and (lasku.liitostunnus = asiakas.tunnus or (tiliointi.liitos = 'A' and tiliointi.liitostunnus = asiakas.tunnus)) ";

      if (isset($sarakebox["ASOSASTO"]) and $sarakebox["ASOSASTO"] != "") {
        // Näitä tarvitaan kun piirretään headerit
        $vresult = t_avainsana("ASIAKASOSASTO");

        while ($vrow = mysql_fetch_assoc($vresult)) {
          $asiakasosastot[$vrow["selite"]] = $vrow["selitetark"];
        }

        $groupsarake .= "'asiakasosastot::',asiakas.osasto,'#!#',";
      }

      if (isset($sarakebox["ASRYHMA"]) and $sarakebox["ASRYHMA"] != "") {
        // Näitä tarvitaan kun piirretään headerit
        $vresult = t_avainsana("ASIAKASRYHMA");

        while ($vrow = mysql_fetch_assoc($vresult)) {
          $asiakasryhmat[$vrow["selite"]] = $vrow["selitetark"];
        }

        $groupsarake .= "'asiakasryhmat::',asiakas.ryhma,'#!#',";
      }
    }

    if ($groupsarake != "") {
      $groupsarake = "concat(".substr($groupsarake, 0, -7).")";
    }
    else {
      $groupsarake = "tiliointi.yhtio";
    }

    // Tarvitaan lasku/asiakasjoini/toimittajajoini jos rajataan tai ajetaan vain konserniasiakkaista tai konsernitoimittajista
    if (isset($konsernirajaus) and $konsernirajaus != "") {
      // Kun tehdään asiakas tai toimittajajoini niin ei vertailla budjettiin koska siinä ei olisi mitään järkeä
      $vertailubu = "";

      $laskujoini = " JOIN lasku ON tiliointi.yhtio = lasku.yhtio and tiliointi.ltunnus = lasku.tunnus ";

      if ($konsernirajaus == "AT") {
        $konsernijoini  = "  LEFT JOIN asiakas ka ON lasku.yhtio = ka.yhtio and ((lasku.liitostunnus = ka.tunnus) or (tiliointi.liitos='A' and ka.tunnus = tiliointi.liitostunnus)) and ka.konserniyhtio != ''
                  LEFT JOIN toimi kt ON lasku.yhtio = kt.yhtio and ((lasku.liitostunnus = kt.tunnus) or (tiliointi.liitos='T' and kt.tunnus = tiliointi.liitostunnus)) and kt.konserniyhtio != '' ";
        $konsernilisa = " and (ka.tunnus is not null or kt.tunnus is not null) ";
      }
      elseif ($konsernirajaus == "T") {
        $konsernijoini = "  LEFT JOIN toimi kt ON lasku.yhtio = kt.yhtio and ((lasku.liitostunnus = kt.tunnus) or (tiliointi.liitos='T' and kt.tunnus = tiliointi.liitostunnus)) and kt.konserniyhtio != '' ";
        $konsernilisa = " and kt.tunnus is not null ";
      }
      elseif ($konsernirajaus == "A") {
        $konsernijoini = "  LEFT JOIN asiakas ka ON lasku.yhtio = ka.yhtio and ((lasku.liitostunnus = ka.tunnus) or (tiliointi.liitos='A' and ka.tunnus = tiliointi.liitostunnus)) and ka.konserniyhtio != '' ";
        $konsernilisa = " and ka.tunnus is not null ";
      }
    }
  }

  // Budjettitauluun sopiva rajaus
  if (isset($lisa) and $lisa != "" and ($vertailubu != "" or $vertailubu2 != "")) {
    // Rajataan budjettia
    $bulisa = str_replace("tiliointi.", "budjetti.", $lisa);
  }

  if ($tltee == "aja") {

    // Tässä voidaan vain setata ei_yhteensä muuttuja, muut tehdään myöhemmin
    if ($kaikkikaudet == "KY" or $kaikkikaudet == "VY" or $kaikkikaudet == "Y") {
      $ei_yhteensa = "";
    }
    else {
      $ei_yhteensa = "JOO";
    }

    // Haetaan yhtiön tulostili
    $query = "SELECT tunnus, tilino
              FROM tili
              WHERE yhtio = '{$kukarow['yhtio']}'
              and tunnus  = '{$yhtiorow["tilikauden_tulos"]}'";
    $tulostilires = pupe_query($query);

    if (mysql_num_rows($tulostilires) == 1) {
      $tulostilirow = mysql_fetch_assoc($tulostilires);
    }

    // Tehdäänkö linkit päiväkirjaan
    $query = "SELECT alanimi
              FROM oikeu
              WHERE yhtio = '$kukarow[yhtio]'
              and kuka    = '$kukarow[kuka]'
              and nimi    = 'raportit.php'
              and alanimi IN ('paakirja','paakirja_OKP')";
    $oikresult = pupe_query($query);

    if (mysql_num_rows($oikresult) > 0) {
      $oikrow = mysql_fetch_assoc($oikresult);
      $paakirjalink = $oikrow["alanimi"];
    }
    else {
      $paakirjalink = FALSE;
    }

    $lopelinkki = "&lopetus=$PHP_SELF////tltee=$tltee//toim=$toim//tyyppi=$tyyppi//plvv=$plvv//plvk=$plvk//plvp=$plvp//alvv=$alvv//alvk=$alvk//alvp=$alvp//tkausi=$tkausi//rtaso=$rtaso//tarkkuus=$tarkkuus//desi=$desi//kaikkikaudet=$kaikkikaudet//ei_yhteensa=$ei_yhteensa//vertailued=$vertailued//vertailupvm=$vertailupvm//vertailubu=$vertailubu//vertailubu2=$vertailubu2".str_replace("&", "//", $ulisa);

    if ($vertailubu2 != "") {
      $vertailubu = "X";
    }

    $startmonth  = date("Ymd",   mktime(0, 0, 0, $plvk, 1, $plvv));
    $endmonth   = date("Ymd",   mktime(0, 0, 0, $alvk, 1, $alvv));

    $annettualk = $plvv."-".$plvk."-".$plvp;
    $totalloppu = $alvv."-".$alvk."-".$alvp;

    $budjettalk = date("Ym", mktime(0, 0, 0, $plvk, 1, $plvv));
    $budjettlop = date("Ym", mktime(0, 0, 0, $alvk+1, 0, $alvv));

    if ($vertailued != "") {
      $totalalku  = ($plvv-1)."-".$plvk."-".$plvp;

      if ((int) $alvk == 2 and (int) $alvp == 29 and date("t", mktime(0, 0, 0, $alvk, 1, $alvv)) == 29) {
        // onko tämä vuosi karkausvuosi?
        $totalloppued = ($alvv-1)."-".$alvk."-28";
      }
      elseif ((int) $alvk == 2 and (int) $alvp == 28 and date("t", mktime(0, 0, 0, $alvk, 1, ($alvv-1))) == 29) {
        // oliko edellinen vuosi karkausvuosi?
        $totalloppued = ($alvv-1)."-".$alvk."-29";
      }
      else {
        $totalloppued = ($alvv-1)."-".$alvk."-".$alvp;
      }
    }
    else {
      $totalalku = $plvv."-".$plvk."-".$plvp;
    }

    $alkuquery1 = "";
    $alkuquery2 = "";
    $alkuquery3 = "";

    for ($i = $startmonth;  $i <= $endmonth;) {

      if ($i == $startmonth) $alku = $plvv."-".$plvk."-".$plvp;
      else $alku = date("Y-m-d", mktime(0, 0, 0, substr($i, 4, 2), substr($i, 6, 2), substr($i, 0, 4)));

      if ($i == $endmonth) $loppu = $alvv."-".$alvk."-".$alvp;
      else $loppu = date("Y-m-d", mktime(0, 0, 0, substr($i, 4, 2)+1, 0, substr($i, 0, 4)));

      $bukausi = date("Ym",    mktime(0, 0, 0, substr($i, 4, 2), substr($i, 6, 2), substr($i, 0, 4)));
      $headny  = date("Y/m",   mktime(0, 0, 0, substr($i, 4, 2), substr($i, 6, 2),  substr($i, 0, 4)));

      if ($alkuquery1 != "") $alkuquery1 .= " ,";
      if ($alkuquery2 != "") $alkuquery2 .= " ,";

      $alkuquery1 .= "sum(if (tiliointi.tapvm >= '$alku' and tiliointi.tapvm <= '$loppu', tiliointi.summa, 0)) '$headny'\n";
      $alkuquery2 .= "sum(if (tiliointi.tapvm >= '$alku' and tiliointi.tapvm <= '$loppu', tiliointi.summa, 0)) '$headny'\n";

      $kaudet[] = $headny;

      if ($vertailued != "") {

        if ($i == $startmonth) $alku_ed = ($plvv-1)."-".$plvk."-".$plvp;
        else $alku_ed  = date("Y-m-d", mktime(0, 0, 0, substr($i, 4, 2), substr($i, 6, 2), substr($i, 0, 4)-1));

        if ($i == $endmonth) {

          if ((int) $alvk == 2 and (int) $alvp == 29 and date("t", mktime(0, 0, 0, $alvk, 1, $alvv)) == 29) {
            // onko tämä vuosi karkausvuosi?
            $loppu_ed = ($alvv-1)."-".$alvk."-28";
          }
          elseif ((int) $alvk == 2 and (int) $alvp == 28 and date("t", mktime(0, 0, 0, $alvk, 1, ($alvv-1))) == 29) {
            // oliko edellinen vuosi karkausvuosi?
            $loppu_ed = ($alvv-1)."-".$alvk."-29";
          }
          else {
            $loppu_ed = ($alvv-1)."-".$alvk."-".$alvp;
          }
        }
        else $loppu_ed = date("Y-m-d", mktime(0, 0, 0, substr($i, 4, 2)+1, 0, substr($i, 0, 4)-1));

        $headed   = date("Y/m",   mktime(0, 0, 0, substr($i, 4, 2), substr($i, 6, 2), substr($i, 0, 4)-1));

        $alkuquery1 .= " ,sum(if (tiliointi.tapvm >= '$alku_ed' and tiliointi.tapvm <= '$loppu_ed', tiliointi.summa, 0)) '$headed'\n";
        $alkuquery2 .= " ,sum(if (tiliointi.tapvm >= '$alku_ed' and tiliointi.tapvm <= '$loppu_ed', tiliointi.summa, 0)) '$headed'\n";

        $kaudet[] = $headed;
      }

      // budjettivertailu
      if ($vertailubu != "") {
        $alkuquery1 .= " ,(SELECT sum(budjetti.summa) FROM budjetti USE INDEX (yhtio_taso_kausi) WHERE budjetti.yhtio = tili.yhtio and budjetti.tyyppi = '$kirjain' and BINARY budjetti.taso = BINARY tili.$tilikarttataso and budjetti.kausi = '$bukausi' $bulisa) 'budj $headny'\n";
        if ($vertailubu2 == "") {
          $alkuquery2 .= " ,(SELECT sum(budjetti.summa) FROM budjetti USE INDEX (yhtio_taso_kausi) WHERE budjetti.yhtio = tili.yhtio and budjetti.tyyppi = '$kirjain' and BINARY budjetti.taso = BINARY tili.taso and budjetti.kausi = '$bukausi' $bulisa) 'budj $headny'\n";
        }
        $alkuquery3 .= " ,(SELECT sum(budjetti.summa) FROM budjetti USE INDEX (yhtio_taso_kausi) WHERE budjetti.yhtio = tili.yhtio and budjetti.tyyppi = '$kirjain' and BINARY budjetti.tili = BINARY tili.tili and BINARY tili.tili != 0 and budjetti.kausi = '$bukausi' $bulisa) 'budj-tili $headny'\n";
        $kaudet[] = "budj $headny";
      }

      $i = date("Ymd", mktime(0, 0, 0, substr($i, 4, 2)+1, 1, substr($i, 0, 4)));
    }

    $vka = date("Y/m", mktime(0, 0, 0, $plvk, 1, $plvv));
    $vkl = date("Y/m", mktime(0, 0, 0, $alvk+1, 0, $alvv));

    $vkaed = date("Y/m", mktime(0, 0, 0, $plvk, 1, $plvv-1));
    $vkled = date("Y/m", mktime(0, 0, 0, $alvk+1, 0, $alvv-1));

    // Yhteensäotsikkomukaan
    if ($ei_yhteensa == "") {
      $alkuquery1 .= " ,sum(if (tiliointi.tapvm >= '$annettualk' and tiliointi.tapvm <= '$totalloppu', tiliointi.summa, 0)) '$vka - $vkl' \n";
      $alkuquery2 .= " ,sum(if (tiliointi.tapvm >= '$annettualk' and tiliointi.tapvm <= '$totalloppu', tiliointi.summa, 0)) '$vka - $vkl' \n";
      $kaudet[] = $vka." - ".$vkl;

      if ($vertailued != "") {

        $alkuquery1 .= " ,sum(if (tiliointi.tapvm >= '$totalalku' and tiliointi.tapvm <= '$totalloppued', tiliointi.summa, 0)) '$vkaed - $vkled' \n";
        $alkuquery2 .= " ,sum(if (tiliointi.tapvm >= '$totalalku' and tiliointi.tapvm <= '$totalloppued', tiliointi.summa, 0)) '$vkaed - $vkled' \n";

        $kaudet[] = $vkaed." - ".$vkled;
      }

      // budjettivertailu
      if ($vertailubu != "") {
        $alkuquery1 .= " ,(SELECT sum(budjetti.summa) FROM budjetti USE INDEX (yhtio_taso_kausi) WHERE budjetti.yhtio = tili.yhtio and budjetti.tyyppi = '$kirjain' and BINARY budjetti.taso = BINARY tili.$tilikarttataso and budjetti.kausi >= '$budjettalk' and budjetti.kausi <= '$budjettlop' $bulisa) 'budj $vka - $vkl' \n";
        if ($vertailubu2 == "") {
          $alkuquery2 .= " ,(SELECT sum(budjetti.summa) FROM budjetti USE INDEX (yhtio_taso_kausi) WHERE budjetti.yhtio = tili.yhtio and budjetti.tyyppi = '$kirjain' and BINARY budjetti.taso = BINARY tili.taso and budjetti.kausi >= '$budjettalk' and budjetti.kausi <= '$budjettlop' $bulisa) 'budj $vka - $vkl' \n";
        }
        $alkuquery3 .= " ,(SELECT sum(budjetti.summa) FROM budjetti USE INDEX (yhtio_taso_kausi) WHERE budjetti.yhtio = tili.yhtio and budjetti.tyyppi = '$kirjain' and BINARY budjetti.tili = BINARY tili.tili and BINARY tili.tili != 0 and budjetti.kausi >= '$budjettalk' and budjetti.kausi <= '$budjettlop' $bulisa) 'budj-tili $vka - $vkl' \n";
        $kaudet[] = "budj ".$vka." - ".$vkl;
      }
    }

    if ($vertailubu != "") {
      $tilijoini = "  JOIN tili ON tiliointi.yhtio=tili.yhtio and tiliointi.tilino=tili.tilino";
    }

    // Vapaavalintainen vertailupäivä
    if (!empty($vertailupvm)) {
      $vertailu_alku  = date("Y-m-d", mktime(0, 0, 0, $vakk, $vapp, $vavv));
      $vertailu_loppu = date("Y-m-d", mktime(0, 0, 0, $vlkk, $vlpp, $vlvv));

      // HUOM, tässä ylikirjotetaan totalalku/totalloppu, jota käytetään yllä kyselyssä,
      // jotta saadaan pääqueryn whereen kaikki kaikki tapahtumat
      if ($vertailu_alku < $totalalku) {
        $totalalku = $vertailu_alku;
      }

      if ($vertailu_loppu > $totalloppu) {
        $totalloppu = $vertailu_loppu;
      }

      $alkuquery1 .= ", sum(if (tiliointi.tapvm >= '{$vertailu_alku}' and tiliointi.tapvm <= '{$vertailu_loppu}', tiliointi.summa, 0)) AS '{$vertailu_alku} - {$vertailu_loppu}' \n";
      $alkuquery2 .= ", sum(if (tiliointi.tapvm >= '{$vertailu_alku}' and tiliointi.tapvm <= '{$vertailu_loppu}', tiliointi.summa, 0)) AS '{$vertailu_alku} - {$vertailu_loppu}' \n";

      $kaudet[] = "{$vertailu_alku} - {$vertailu_loppu}";
    }

    // Rajataan AINA käyttäjän osaston kustannuspaikalla
    $vainomakustp_lisa = "";

    if (!empty($vainomakustp)) {
      $vainomakustp_lisa = " and tiliointi.kustp = '{$kukarow["osasto"]}' ";
    }

    // Haetaan kaikki tiliöinnit
    $query = "SELECT tiliointi.tilino, $groupsarake groupsarake, $alkuquery1
              FROM tiliointi USE INDEX (yhtio_tapvm_tilino)
              $laskujoini
              $asiakasjoini
              $konsernijoini
              $tilijoini
              WHERE tiliointi.yhtio  = '$kukarow[yhtio]'
              and tiliointi.korjattu = ''
              and tiliointi.tapvm    >= '$totalalku'
              and tiliointi.tapvm    <= '$totalloppu'
              $konsernilisa
              $vainomakustp_lisa
              $lisa
              GROUP BY tiliointi.tilino, groupsarake
              ORDER BY tiliointi.tilino, groupsarake";
    $tilires = pupe_query($query);

    $tilioinnit = array();
    $sarakkeet  = array();

    while ($tilirow = mysql_fetch_assoc($tilires)) {

      if (!isset($firstgroup)) $firstgroup = (string) $tilirow["groupsarake"];

      // Otetaan kaikki distinct sarakkeet
      $sarakkeet[(string) $tilirow["groupsarake"]] = (string) $tilirow["groupsarake"];

      $tilioinnit[(string) $tilirow["tilino"]][(string) $tilirow["groupsarake"]] = $tilirow;
    }

    //Haetaan tulos jos ajetaan taselaskelma
    if (isset($tulostilirow) and ($tyyppi == "T" or $tyyppi == "2")) {

      $tulokset = array();

      $query = "SELECT group_concat(concat('\'',tilino,'\'')) tilit
                FROM tili
                WHERE yhtio = '$kukarow[yhtio]'
                and LEFT(tili.ulkoinen_taso, 1) = BINARY '3'";
      $tulosres = pupe_query($query);
      $tulosrow = mysql_fetch_assoc($tulosres);

      if ($tulosrow['tilit'] != '') {
        // Haetaan firman tulos
        $query = "SELECT $groupsarake groupsarake, $alkuquery1
                  FROM tiliointi USE INDEX (yhtio_tapvm_tilino)
                  $laskujoini
                  $asiakasjoini
                  $konsernijoini
                  $tilijoini
                  WHERE tiliointi.yhtio  = '$kukarow[yhtio]'
                  and tiliointi.korjattu = ''
                  and tiliointi.tapvm    >= '$totalalku'
                  and tiliointi.tapvm    <= '$totalloppu'
                  and tiliointi.tilino   in ({$tulosrow['tilit']})
                  $konsernilisa
                  $vainomakustp_lisa
                  $lisa
                  GROUP BY groupsarake
                  ORDER BY groupsarake";
        $tulosres = pupe_query($query);

        while ($tulosrow = mysql_fetch_assoc($tulosres)) {
          // Jos tiliöintejä ei ole, niin laitetaan tulos suoraan tähän, muuten summataan yhteen myöhemmin
          if (!isset($tilioinnit[(string) $tulostilirow["tilino"]][(string) $tulosrow["groupsarake"]])) {
            $tilioinnit[(string) $tulostilirow["tilino"]][(string) $tulosrow["groupsarake"]] = $tulosrow;
          }
          else {
            $tulokset[(string) $tulosrow["groupsarake"]] = $tulosrow;
          }
        }
      }
    }

    // Haetaan kaikki budjetit
    $query = "SELECT budjetti.taso, budjetti.tili, budjetti.yhtio groupsarake, $alkuquery2
              $alkuquery3
              FROM budjetti
              JOIN budjetti tili ON (tili.yhtio = budjetti.yhtio and tili.tunnus = budjetti.tunnus)
              LEFT JOIN tiliointi USE INDEX (PRIMARY) ON (tiliointi.tunnus = 0)
              WHERE budjetti.yhtio = '$kukarow[yhtio]'
              AND budjetti.tyyppi  = '$kirjain'
              $bulisa
              GROUP BY budjetti.taso, budjetti.tili, groupsarake
              ORDER BY budjetti.taso, budjetti.tili, groupsarake";
    $tilires = pupe_query($query);

    $budjetit = array();
    $budjetit_tili = array();

    while ($tilirow = mysql_fetch_assoc($tilires)) {
      if (empty($tilirow['tili'])) {
        $budjetit[(string) $tilirow["taso"]][(string) $tilirow["groupsarake"]] = $tilirow;
      }
      else {
        $budjetit_tili[(string) $tilirow["tili"]][(string) $tilirow["groupsarake"]] = $tilirow;
      }
    }

    // Haetaan kaikki tasot ja rakennetaan tuloslaskelma-array
    $query = "SELECT *
              FROM taso
              WHERE yhtio  = '$kukarow[yhtio]'
              and tyyppi   = '$kirjain'
              and LEFT(taso, 1) in (BINARY '$aputyyppi')
              and taso    != ''
              ORDER BY taso";
    $tasores = pupe_query($query);

    while ($tasorow = mysql_fetch_assoc($tasores)) {

      // millä tasolla ollaan (1,2,3,4,5,6)
      $tasoluku = strlen($tasorow["taso"]);

      // tasonimi talteen (rightpäddätään Z:lla tai Ö:llä, niin saadaan oikeaan järjestykseen)
      if (PUPE_UNICODE) {
        $apusort = str_pad($tasorow["taso"], 20, "Z");
      }
      else {
        $apusort = str_pad($tasorow["taso"], 20, "Ö");
      }

      $tasonimi[$apusort] = $tasorow["nimi"];

      // Jos tasolla on oletusarvo per kk. Esim poistot, jotka kirjataan vasta tilinpäätöksessä
      if ($tasorow["oletusarvo"] != 0) {
        $oletusarvo[$tasorow["taso"]] = $tasorow["oletusarvo"];
      }

      if ($toim == "TASOMUUTOS") {
        $summattavattasot[$apusort] = $tasorow["summattava_taso"];
      }

      // pilkotaan taso osiin
      $taso = array();
      for ($i = 0; $i < $tasoluku; $i++) {
        $taso[$i] = substr($tasorow["taso"], 0, $i+1);
      }

      $query = "SELECT tilino, nimi, tunnus
                FROM tili
                WHERE yhtio = '$kukarow[yhtio]'
                and $tilikarttataso = BINARY '$tasorow[taso]'
                ORDER BY tilino";
      $tilires = pupe_query($query);

      if (mysql_num_rows($tilires) > 0) {
        while ($tilirow = mysql_fetch_assoc($tilires)) {

          $tilirow_summat = array();

          if (isset($tilioinnit[(string) $tilirow["tilino"]])) {
            $tilirow_summat = $tilioinnit[(string) $tilirow["tilino"]];
          }
          elseif (isset($budjetit[(string) $tasorow["taso"]])) {
            $tilirow_summat = $budjetit[(string) $tasorow["taso"]];
          }
          elseif ($toim == "TASOMUUTOS") {
            $tilirow_summat = array("$firstgroup" => 0);
          }

          if (isset($budjetit_tili[(string) $tilirow["tilino"]])) {
            $tilirow_summat = array_replace_recursive($budjetit_tili[(string) $tilirow["tilino"]], $tilirow_summat);
          }

          foreach ($tilirow_summat as $sarake => $tilirow_sum) {
            // summataan kausien saldot
            foreach ($kaudet as $kausi) {

              $_kausi = str_replace("budj", "budj-tili", $kausi);

              if ($vertailubu2 == "" and substr($kausi, 0, 4) == "budj") {
                $i = $tasoluku - 1;
                $summa[$kausi][$taso[$i]][(string) $sarake] = $tilirow_sum[$kausi];
              }
              else {
                // Summataan kaikkia pienempiä summaustasoja
                for ($i = $tasoluku - 1; $i >= 0; $i--) {
                  // Summat per kausi/taso
                  if (isset($summa[$kausi][$taso[$i]][(string) $sarake])) $summa[$kausi][$taso[$i]][(string) $sarake] += $tilirow_sum[$_kausi];
                  else $summa[$kausi][$taso[$i]][(string) $sarake] = $tilirow_sum[$_kausi];

                  //Onko tämä yhtiön tulostili? Jos on niin summataan tulos mukaan
                  if (isset($tulokset[$sarake][$kausi]) and ($tilirow["tunnus"] == $yhtiorow["tilikauden_tulos"])) {
                    $summa[$kausi][$taso[$i]][(string) $sarake] += $tulokset[$sarake][$_kausi];
                  }
                }
              }

              // Summat per taso/tili/kausi
              $i = $tasoluku - 1;
              $summakey = $tilirow["tilino"]."###".$tilirow["nimi"];

              if (isset($tilisumma[$taso[$i]][$summakey][$kausi][(string) $sarake])) $tilisumma[$taso[$i]][$summakey][$kausi][(string) $sarake] += $tilirow_sum[$_kausi];
              else $tilisumma[$taso[$i]][$summakey][$kausi][(string) $sarake] = $tilirow_sum[$_kausi];

              // Onko tämä yhtiön tulostili? Jos on niin summataan tulos mukaan
              if (isset($tulokset[$sarake][$kausi]) and ($tilirow["tunnus"] == $yhtiorow["tilikauden_tulos"])) {
                $tilisumma[$taso[$i]][$summakey][$kausi][(string) $sarake] += $tulokset[$sarake][$_kausi];
              }
            }
          }
        }
      }
      else {
        // vaikka ei ois yhtään tiliä niin voi olla, että budjetti on syötetty
        $tilirow_summat = $budjetit[(string) $tasorow["taso"]];

        foreach ($tilirow_summat as $sarake => $tilirow_sum) {
          // summataan kausien saldot
          foreach ($kaudet as $kausi) {
            if (substr($kausi, 0, 4) == "budj") {
              $i = $tasoluku - 1;

              $summa[$kausi][$taso[$i]][(string) $sarake] = $tilirow_sum[$kausi];
            }
          }
        }
      }

      // Summataan kaikkia pienempiä summaustasoja sijoittaen puuttuvat oletusarvot
      $summattavakausi = array(); //Täällä on summa, jota summataan ylös
      $summattavataso = ''; //Täällä on oletusarvon taso
      $summattavaluku = 0; //Täällä on tason oletusarvo
      $summattavaindeksi = $tasoluku -1;
      $kumulointikausi = $vka . " - " . $vkl;

      if (isset($oletusarvo[$taso[$summattavaindeksi]])) {
        //echo "Oletus löytyi " .  $taso[$summattavaindeksi] ."<br>";
        $summattavataso = $taso[$summattavaindeksi];
        $summattavaluku = $oletusarvo[$taso[$summattavaindeksi]];

        foreach ($sarakkeet as $sarake) {
          foreach ($kaudet as $kausi) {
            if (substr($kausi, 0, 4) != "budj") {
              //Käytetään oletusarvo, jos alkuperäisen tason arvo on 0
              if ((isset($summa[$kausi][$taso[$summattavaindeksi]][(string) $sarake]) and $summa[$kausi][$taso[$summattavaindeksi]][(string) $sarake] == 0) or !isset($summa[$kausi][$taso[$summattavaindeksi]][(string) $sarake])) {

                if ($kausi != $vka." - ".$vkl and $kausi != $vkaed." - ".$vkled and $kausi >= $vka) {
                  $summattavakausi[$kausi] = $summattavaluku;
                }
              }

              for ($i = $tasoluku - 1; $i >= 0; $i--) {
                if (isset($oletusarvo[$taso[$i]]) or $summattavaluku != 0) {
                  if (isset($summattavakausi[$kausi])) {
                    $summa[$kausi][$taso[$i]][(string) $sarake]  += $summattavaluku;

                    //Kumuloidaan oikealle
                    $summa[$kumulointikausi][$taso[$i]][(string) $sarake]  += $summattavaluku;
                  }

                  //Onko tämä yhtiön tulostili? Jos on niin summataan tulos mukaan
                  if (isset($tulokset[$sarake][$kausi]) and $tilirow["tunnus"] == $yhtiorow["tilikauden_tulos"]) {
                    $summa[$kausi][$taso[$i]][(string) $sarake] += $tulokset[$sarake][$kausi];
                  }
                }
              }
            }
          }
        }
      }
    }

    if ($kaikkikaudet == "VY") {
      // vika + yht
      $kaikkikaudet = "";

      $alkukausi = count($kaudet)-2;

      if ($vertailued != "") $alkukausi -= 2;
      if ($vertailubu != "") $alkukausi -= 2;

      if (!empty($vertailupvm)) {
        $alkukausi -= 1;
      }
    }
    elseif ($kaikkikaudet == "V") {
      // vika ei yht
      $kaikkikaudet = "";

      $alkukausi = count($kaudet)-2;

      if ($vertailued != "" and $vertailubu != "") $alkukausi -= 1;
      if ($vertailued == "" and $vertailubu == "") $alkukausi += 1;

      if (!empty($vertailupvm)) {
        $alkukausi -= 1;
      }
    }
    elseif ($kaikkikaudet == "KY") {
      // kaikki + yht
      $kaikkikaudet = "joo";

      $alkukausi = 0;
    }
    elseif ($kaikkikaudet == "K") {
      // kaikki ei yht
      $kaikkikaudet = "joo";

      $alkukausi = 0;
    }
    else {
      // vain yhteensä
      $alkukausi = count($kaudet)-1;

      if ($vertailued != "" and $vertailubu != "") {
        $alkukausi -= 2;
      }
      elseif ($vertailued != "" and $vertailubu == "") {
        $alkukausi -= 1;
      }
      elseif ($vertailued == "" and $vertailubu != "") {
        $alkukausi -= 1;
      }

      if (!empty($vertailupvm)) {
        $alkukausi -= 1;
      }
    }

    echo "<table>";

    // printataan headerit
    echo "<tr>";

    if ($toim == "TASOMUUTOS") {

      echo "  <form method='post'>
          <input type = 'hidden' name = 'tasomuutos' value = 'TRUE'>
          <input type = 'hidden' name = 'tee' value = 'tilitaso'>
          <input type = 'hidden' name = 'kirjain' value = '$kirjain'>
          <input type = 'hidden' name = 'taso' value = '$aputyyppi'>";

      $lopetus =  $palvelin2."raportit/tuloslaskelma.php////";

      foreach ($_REQUEST as $key => $value) {
        $lopetus .= $key."=".$value."//";
      }

      echo "<input type = 'hidden' name = 'lopetus' value = '$lopetus'>";

      echo "<td class='back' colspan='3'></td>";
    }
    else {
      echo "<td class='back' colspan='1'></td>";
    }

    for ($i = $alkukausi; $i < count($kaudet); $i++) {
      foreach ($sarakkeet as $sarake) {
        if (strpos($sarake, "::") !== FALSE) {
          list($muuarray, $arvo) = explode("::", $sarake);
          $sarakenimi = ${$muuarray}[$arvo];
        }
        else {
          $sarakenimi = "";
        }

        echo "<td class='tumma' align='right' valign='bottom'>$sarakenimi<br>$kaudet[$i]</td>";
      }
    }

    echo "</tr>\n";

    // sortataan array indexin (tason) mukaan
    ksort($tasonimi);

    $rivit_px = array();
    $px = 0;

    // loopataan tasot läpi
    foreach ($tasonimi as $key_c => $value) {

      $px++;

      // Päddäykset pois
      if (PUPE_UNICODE) {
        $key = str_replace("Z", "", $key_c); // Z-kirjaimet pois
      }
      else {
        $key = str_replace("Ö", "", $key_c); // Ö-kirjaimet pois
      }

      // tulostaan rivi vain jos se kuuluu rajaukseen
      if (strlen($key) <= $rtaso or $rtaso == "TILI") {

        $class = "";

        // laitetaan ykkös ja kakkostason rivit tummalla selkeyden vuoksi
        if (strlen($key) < 3 and $rtaso > 2) $class = "tumma";

        $rivi = "<tr class='aktiivi'>";

        if ($toim == "TASOMUUTOS") {
          $rivi .= "<td class='back' nowrap><a href='?tasomuutos=TRUE&taso=$key&kirjain=$kirjain&tee=muuta&lopetus=$lopetus'>$key</a></td>";
          $rivi .= "<td class='back' nowrap><a href='?tasomuutos=TRUE&taso=$key&kirjain=$kirjain&edtaso=$edkey&tee=lisaa&lopetus=$lopetus'>".t("Lisää taso tasoon")." $key</a></td>";
        }

        $tilirivi   = "";
        $tilirivi_px = array();

        if ($rtaso == "TILI") {

          $class = "tumma";

          if (isset($tilisumma[$key])) {
            foreach ($tilisumma[$key] as $tilitiedot => $tilisumkau) {
              $tilirivi2    = "";
              $tilirivi2_px  = array();
              $tulos      = 0;

              for ($i = $alkukausi; $i < count($kaudet); $i++) {
                foreach ($sarakkeet as $sarake) {

                  $apu = 0;
                  if (isset($tilisumkau[$kaudet[$i]][(string) $sarake])) $apu = sprintf($muoto, $tilisumkau[$kaudet[$i]][(string) $sarake] * $luku_kerroin / $tarkkuus);
                  if ($apu == 0) $apu = "";

                  if (is_numeric($apu)) {
                    $apu = number_format($apu, $desi, ',', ' ');
                  }

                  $tilirivi2 .= "<td align='right' nowrap>$apu</td>";
                  $tilirivi2_px[] = $apu;

                  if ($tilisumkau[$kaudet[$i]][(string) $sarake] != 0) {
                    $tulos++;
                  }
                }
              }

              if ($tulos > 0 or $toim == "TASOMUUTOS") {

                list($tnumero, $tnimi) = explode("###", $tilitiedot);

                $tilirivi .= "<tr>";

                if ($toim == "TASOMUUTOS") {
                  $tilirivi .= "<td class='back' nowrap>$key</td>";
                  $tilirivi .= "<td class='back' nowrap><input type='checkbox' name='tiliarray[]' value=\"'$tnumero'\"></td>";
                }

                $tilirivi .= "<td nowrap>";

                if (!empty($paakirjalink)) {
                  $tilirivi .= "<a href ='../raportit.php?toim=$paakirjalink&tee=P&mista=tuloslaskelma&alvv=$alvv&alvk=$alvk&tili=$tnumero$ulisa$lopelinkki'>$tnumero - $tnimi</a>";
                }
                else {
                  $tilirivi .= "$tnumero - $tnimi";
                }

                $tilirivi .= "</td>$tilirivi2</tr>";

                $rivit_px[$px] = array_merge(array($key, $tnumero, $tnimi), $tilirivi2_px);
                $px++;
              }
            }
          }
        }

        $rivi    .= "<th nowrap>$value</th>";
        $rivit_px[$px] = array($key, "", $value);

        $tulos = 0;

        for ($i = $alkukausi; $i < count($kaudet); $i++) {
          foreach ($sarakkeet as $sarake) {
            $query = "SELECT summattava_taso
                      FROM taso
                      WHERE yhtio          = '$kukarow[yhtio]'
                      and taso             = BINARY '$key'
                      and summattava_taso != ''
                      and tyyppi           = '$kirjain'";
            $summares = pupe_query($query);

            // Budjettia ei summata välttämättä
            if ($vertailubu2 == "") {
              $bu_check = (substr($kaudet[$i], 0, 4) != "budj");
            }
            else {
              $bu_check = true;
            }

            if ($summarow = mysql_fetch_assoc($summares) and $bu_check) {
              foreach (explode(",", $summarow["summattava_taso"]) as $staso) {
                $summa[$kaudet[$i]][$key][(string) $sarake] = $summa[$kaudet[$i]][$key][(string) $sarake] + $summa[$kaudet[$i]][$staso][(string) $sarake];
              }
            }

            // formatoidaan luku toivottuun muotoon
            $apu = 0;

            if (isset($summa[$kaudet[$i]][$key][(string) $sarake])) $apu = sprintf($muoto, $summa[$kaudet[$i]][$key][(string) $sarake] * $luku_kerroin / $tarkkuus);

            if ($apu == 0) {
              $apu = ""; // nollat spaseiks
            }
            else {
              $tulos++; // summaillaan tätä jos meillä oli rivillä arvo niin osataan tulostaa
            }

            if (is_numeric($apu)) {
              $apu = number_format($apu, $desi, ',', ' ');
            }

            $rivi .= "<td class='$class' align='right' nowrap>$apu</td>";

            $rivit_px[$px] = array_merge($rivit_px[$px], array($apu));
          }
        }

        if ($toim == "TASOMUUTOS" and $summattavattasot[$key_c] != "") {
          $rivi .= "<td class='back' nowrap>".t("Summattava taso").": ".$summattavattasot[$key_c]."</td>";
        }

        $rivi .= "</tr>\n";

        // kakkostason jälkeen aina yks tyhjä rivi.. paitsi jos otetaan vain kakkostason raportti
        if (strlen($key) == 2 and ($rtaso > 2 or $rtaso == "TILI")) {
          $rivi .= "<tr><td class='back'>&nbsp;</td></tr>";

          if ($tulos > 0) {
            $px++;
            $rivit_px[$px] = "";
          }
        }

        if (strlen($key) == 1 and ($rtaso > 1 or $rtaso == "TILI")) {
          $rivi .= "<tr><td class='back'><br><br></td></tr>";

          if ($tulos > 0) {
            $px++;
            $rivit_px[$px] = "";

            $px++;
            $rivit_px[$px] = "";
          }
        }

        // jos jollain kaudella oli summa != 0 niin tulostetaan rivi
        if ($tulos > 0 or $toim == "TASOMUUTOS") {
          echo $tilirivi, $rivi;
        }
        elseif (isset($rivit_px[$px])) {
          unset($rivit_px[$px]);
        }
      }

      $edkey = $key;
    }

    echo "</table><br><br>";

    // Excel-koodia
    if (isset($teexls) and $teexls == "OK") {
      include 'inc/pupeExcel.inc';

      $worksheet    = new pupeExcel();
      $format_bold = array("bold" => TRUE);
      $excelrivi    = 0;
      $excelhist   = 0;

      $pi = 1;

      for ($i = $alkukausi; $i < count($kaudet); $i++) {
        foreach ($sarakkeet as $sarake) {
          if (strpos($sarake, "::") !== FALSE) {
            list($muuarray, $arvo) = explode("::", $sarake);
            $sarakenimi = ${$muuarray}[$arvo]."\n";
          }
          else {
            $sarakenimi = "";
          }

          $worksheet->writeString(0, $pi, $sarakenimi.$kaudet[$i], $format_bold);
          $pi++;
        }
      }
    }

    // PDF-koodia
    if (isset($teepdf) and $teepdf == "OK") {
      require_once 'pdflib/phppdflib.class.php';

      $pdf = new pdffile;
      $pdf->set_default('margin', 0);
      $pdf->set_default('margin-left', 5);
      $rectparam["width"] = 0.3;

      $p["height"]   = 10;
      $p["font"]     = "Times-Roman";
      $b["height"]  = 8;
      $b["font"]     = "Times-Bold";

      if (count($kaudet) > 10 and $kaikkikaudet != "") {
        $p["height"]--;
        $b["height"]--;

        $saraklev       = 49;
        $yhteensasaraklev   = 66;
        $rivikork       = 13;
      }
      else {
        $saraklev       = 60;
        $yhteensasaraklev   = 70;
        $rivikork       = 15;
      }

      if ((count($kaudet) > 5 and $kaikkikaudet != "") or count($sarakkeet) > 2) {
        $vaslev = 802;
      }
      else {
        $vaslev = 545;
      }

      for ($i = $alkukausi; $i < count($kaudet); $i++) {
        foreach ($sarakkeet as $sarake) {
          $vaslev -= $saraklev;
        }
      }

      if ($vaslev > 300) {
        $vaslev = 300;
      }

      if (!function_exists("alku")) {
        function alku() {
          global $yhtiorow, $kukarow, $firstpage, $pdf, $bottom, $kaudet, $kaikkikaudet, $saraklev, $rivikork, $p, $b, $otsikko, $alkukausi, $yhteensasaraklev, $vaslev, $sarakkeet, $ei_yhteensa, $leveysarray;

          if ((count($kaudet) > 5 and $kaikkikaudet == "joo") or count($sarakkeet) > 2) {
            $firstpage = $pdf->new_page("842x595");
            $bottom = "535";
          }
          else {
            $firstpage = $pdf->new_page("a4");
            $bottom = "782";
          }

          unset($data);

          if ((int) $yhtiorow["lasku_logo"] > 0) {
            $liite = hae_liite($yhtiorow["lasku_logo"], "Yllapito", "array");
            $data = $liite["data"];
            $isizelogo[0] = $liite["image_width"];
            $isizelogo[1] = $liite["image_height"];
            unset($liite);
          }
          elseif (file_exists($yhtiorow["lasku_logo"])) {
            $filename = $yhtiorow["lasku_logo"];

            $fh = fopen($filename, "r");
            $data = fread($fh, filesize($filename));
            fclose($fh);

            $isizelogo = getimagesize($yhtiorow["lasku_logo"]);
          }

          if (isset($data) and $data) {
            $image = $pdf->jfif_embed($data);

            if (!$image) {
              echo t("Logokuvavirhe");
            }
            elseif ($bottom == "535") {
              tulosta_logo_pdf($pdf, $firstpage, "", 575, 0, 25, 120);
            }
            else {
              tulosta_logo_pdf($pdf, $firstpage, "", 0, 0, 25, 120);
            }
          }
          else {
            $pdf->draw_text(10, ($bottom+30), $yhtiorow["nimi"], $firstpage);
          }

          $pdf->draw_text(200, ($bottom+30), $otsikko, $firstpage);

          $leveysarray = array();
          $left = $vaslev;

          for ($i = $alkukausi; $i < count($kaudet); $i++) {
            foreach ($sarakkeet as $sarake) {

              if (strpos($sarake, "::") !== FALSE) {
                list($muuarray, $arvo) = explode("::", $sarake);
                $sarakenimi = $GLOBALS[$muuarray][$arvo];
              }
              else {
                $sarakenimi = "";
              }

              $oikpos1 = $pdf->strlen($kaudet[$i], $b);
              $oikpos2 = $pdf->strlen($sarakenimi, $b);

              if ($oikpos2 > $oikpos1) {
                $oikpos = $oikpos2;
              }
              else {
                $oikpos = $oikpos1;
              }

              if (($i+1) == count($kaudet) and $ei_yhteensa == "") {
                $lev = $yhteensasaraklev;
              }
              else {
                $lev = $saraklev;
              }

              // Tallentaan sarakkeiden kohdat...
              $leveysarray[] = $left+$lev;

              $pdf->draw_text($left-$oikpos2+$lev,  $bottom+8, $sarakenimi, $firstpage, $b);
              $pdf->draw_text($left-$oikpos1+$lev,  $bottom, $kaudet[$i], $firstpage, $b);

              $left += $saraklev;
            }
          }

          $bottom -= $rivikork;
        }
      }

      alku();
    }

    // Kirjoitetaan exceli ja/tai pdf:ä
    if ((isset($teepdf) and $teepdf == "OK") or (isset($teexls) and $teexls == "OK")) {

      $excelrivi = 1;

      foreach ($rivit_px as $sarakkeet_px) {

        if (isset($teepdf) and $teepdf == "OK" and $bottom < 20) alku();

        $pi = 0;

        foreach ($sarakkeet_px as $arvo) {
          if ($pi == 0) {
            $sisennys = 10+(strlen($arvo)-1)*3;

            if ($sarakkeet_px[1] != "") {
              $nimi = $sarakkeet_px[1]." - ".$sarakkeet_px[2];
            }
            else {
              $nimi = $sarakkeet_px[2];
            }

            if (isset($teexls) and $teexls == "OK") $worksheet->writeString($excelrivi, $pi, $nimi, $format_bold);
            if (isset($teepdf) and $teepdf == "OK") $pdf->draw_text($sisennys, $bottom, $nimi, $firstpage, $b);
          }
          elseif ($pi > 2) {

            if (isset($teexls) and $teexls == "OK") $worksheet->writeNumber($excelrivi, $pi-2, (float) str_replace(" ", "", str_replace(",", ".", $arvo)));

            if (isset($teepdf) and $teepdf == "OK") {
              $oikpos = $pdf->strlen($arvo, $p);
              $pdf->draw_text($leveysarray[$pi-3]-$oikpos, $bottom, $arvo, $firstpage, $p);
            }
          }

          $pi++;
        }

        if (isset($teexls) and $teexls == "OK") $excelrivi++;
        if (isset($teepdf) and $teepdf == "OK") $bottom -= $rivikork;
      }
    }

    if ($toim == "TASOMUUTOS") {
      echo "<br><input type='submit' value='".t("Anna tileille taso")."'></form><br><br>";
    }

    if (isset($teepdf) and $teepdf == "OK") {
      //keksitään uudelle failille joku varmasti uniikki nimi:
      list($usec, $sec) = explode(' ', microtime());
      mt_srand((float) $sec + ((float) $usec * 100000));
      $pdffilenimi = "Tuloslaskelma-".md5(uniqid(mt_rand(), true)).".pdf";

      //kirjoitetaan pdf faili levylle..
      $fh = fopen("/tmp/".$pdffilenimi, "w");
      if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF Error $pdffilenimi");
      fclose($fh);

      echo "<br><table>";
      echo "<tr><th>".t("Tallenna pdf").":</th>";
      echo "<form method='post' class='multisubmit'>";
      echo "<input type='hidden' name='toim' value='$toim'>";
      echo "<input type='hidden' name='teetiedosto' value='lataa_tiedosto'>";
      // poistetaan välilyönti
      $otsikko = str_replace(" ", "_", $otsikko);
      echo "<input type='hidden' name='kaunisnimi' value='$otsikko.pdf'>";
      echo "<input type='hidden' name='tmpfilenimi' value='$pdffilenimi'>";
      echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
      echo "</table><br>";
    }

    if (isset($teexls) and $teexls == "OK") {

      $excelnimi = $worksheet->close();

      echo "<br><table>";
      echo "<tr><th>".t("Tallenna excel-tulos").":</th>";
      echo "<form method='post' class='multisubmit'>";
      echo "<input type='hidden' name='toim' value='$toim'>";
      echo "<input type='hidden' name='teetiedosto' value='lataa_tiedosto'>";
      // poistetaan välilyönti
      $otsikko = str_replace(" ", "_", $otsikko);
      echo "<input type='hidden' name='kaunisnimi' value='$otsikko.xlsx'>";
      echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
      echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
      echo "</table><br>";
    }
  }

  require "inc/footer.inc";
}
