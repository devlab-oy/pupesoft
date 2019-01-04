<?php

//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *//
$useslave = 1;

if (isset($_REQUEST["tee"]) and $_REQUEST["tee"] == "NAYTATILAUS") {
  $no_head = "yes";

  if (isset($_REQUEST["xml"])) {
    header("Content-type: text/xml");
  }

  if (isset($_REQUEST["pdf"])) {
    // T‰ss‰ on "//NO_MB_OVERLOAD"-kommentti
    // jotta UTF8-konversio ei osu t‰h‰n riviin
    $pdf_size = strlen(urldecode($_REQUEST["pdf"])); //NO_MB_OVERLOAD

    header("Content-type: application/pdf");
    header("Content-length: {$pdf_size}");
    header("Content-Disposition: inline; filename=Pupesoft_lasku");
    header("Content-Description: Pupesoft_lasku");
  }

  flush();
}

require "inc/parametrit.inc";

if (isset($livesearch_tee) and $livesearch_tee == "ASIAKASHAKU") {
  livesearch_asiakashaku();
  exit;
}

if ($_REQUEST["tee"] == "NAYTATILAUS" and isset($_REQUEST["xml"])) {
  $xml = urldecode($_REQUEST["xml"]);
  $xml = str_replace("\"Finvoice.dtd\"", "\"{$palvelin2}datain/Finvoice.dtd\"", $xml);
  $xml = str_replace("\"Finvoice.xsl\"", "\"{$palvelin2}datain/Finvoice.xsl\"", $xml);

  if (stripos($xml, "Finvoice.dtd") === FALSE) {
    $xml = str_replace(
      "<?xml version='1.0' encoding='UTF-8'?>",
      "<!DOCTYPE Finvoice SYSTEM '{$palvelin2}datain/Finvoice.dtd'>
      <?xml-stylesheet type='text/xsl' href='{$palvelin2}datain/Finvoice.xsl'?>",
      $xml
    );
  }
  echo $xml;
  exit;
}

if ($_REQUEST["tee"] == "NAYTATILAUS" and isset($_REQUEST["pdf"])) {
  $pdf = urldecode($_REQUEST["pdf"]);
  echo $pdf;
  exit;
}

enable_ajax();

// Otetaan defaultit, jos ei olla yliajettu salasanat.php:ss‰
$verkkolaskut_in     = $finvoice_myyntilasku_kansio;
$verkkolaskut_ok     = $finvoice_myyntilasku_kansio_valmis;
$verkkolaskut_error  = $finvoice_myyntilasku_kansio_error;
$verkkolaskut_reject = $finvoice_myyntilasku_kansio_reject;

$verkkolaskut_in     = rtrim($verkkolaskut_in, "/");
$verkkolaskut_ok     = rtrim($verkkolaskut_ok, "/");
$verkkolaskut_error  = rtrim($verkkolaskut_error, "/");
$verkkolaskut_reject = rtrim($verkkolaskut_reject, "/");

$verkkolaskuvirheet_kasittele  = $verkkolaskut_in;
$verkkolaskuvirheet_vaarat     = $verkkolaskut_error;
$verkkolaskuvirheet_poistetut  = $verkkolaskut_reject;

echo "<font class='head'>".t("Virheelliset myyntilaskut")."</font><hr>";

// VIRHE: verkkolasku-kansiot on v‰‰rin m‰‰ritelty!
if (!is_dir($verkkolaskuvirheet_poistetut) or !is_dir($verkkolaskuvirheet_vaarat) or !is_dir($verkkolaskuvirheet_kasittele)) {
  echo t("Kansioissa ongelmia").": $verkkolaskuvirheet_poistetut, $verkkolaskuvirheet_vaarat, $verkkolaskuvirheet_kasittele<br>";
  exit;
}

// ekotetaan javascripti‰ jotta saadaan pdf:‰t uuteen ikkunaan
js_openFormInNewWindow();

if (isset($tiedosto)) {
  if ($tapa == 'U') {
    rename($verkkolaskuvirheet_vaarat."/".$tiedosto, $verkkolaskuvirheet_kasittele."/".$tiedosto);
    echo "<font class='message'>".t("Tiedosto k‰sitell‰‰n uudestaan")."</font><br>";
  }

  if ($tapa == 'U_JA_P') {
    // P‰ivitet‰‰n asiakkaan tunnus aineistoon, niin saadaan lasku reskontraan
    $finkkari = simplexml_load_file($verkkolaskuvirheet_vaarat."/".$tiedosto);

    if (!isset($asiakas_haku)) {
      $finkkari->BuyerPartyDetails->addChild('BuyerPupesoftId', $tunnus);
      $muutos_ok = true;
    }
    else {
      $asiakas = hae_asiakas($asiakas_haku);

      if (!empty($asiakas['tunnus'])) {
        $finkkari->BuyerPartyDetails->addChild('BuyerPupesoftId', $asiakas['tunnus']);
        $muutos_ok = true;
      }
      //unsetataan, ettei p‰ivity domin formeihin
      unset($asiakas_haku);
    }

    if ($muutos_ok) {
      file_put_contents($verkkolaskuvirheet_vaarat."/".$tiedosto, $finkkari->asXML());

      rename($verkkolaskuvirheet_vaarat."/".$tiedosto, $verkkolaskuvirheet_kasittele."/".$tiedosto);
      echo "<font class='message'>".t("Tiedosto k‰sitell‰‰n uudestaan")."</font><br>";
    }
    else {
      echo "<font class='message'>".t("Tiedoston korjaamisessa tapahtui virhe")."</font><br>";
    }
  }

  if ($tapa == 'P') {
    rename($verkkolaskuvirheet_vaarat."/".$tiedosto, $verkkolaskuvirheet_poistetut."/".$tiedosto);
    echo "<font class='message'>".t("Tiedosto hyl‰ttiin")."</font><br>";
  }
}

$laskuri = 0;
$valitutlaskut = 0;

if ($handle = opendir($verkkolaskuvirheet_vaarat)) {

  require "inc/verkkolasku-in.inc";

  echo "<table><tr>";
  echo "<th>".t("Laskuttaja")."</th>
    <th>".t("Toiminto")."</th>
    <th>".t("Ovttunnus")."<br>".t("Y-tunnus")."</th>
    <th>".t("Asiakas")."</th>
    <th>".t("Maksuehto")."</th>
    <th>".t("Laskunumero")."<br>".t("Maksutili")."<br>".t("Summa")."</th>
    <th>".t("Pvm")."</th></tr><tr>";

  while (($file = readdir($handle)) !== FALSE) {

    if (is_file($verkkolaskuvirheet_vaarat."/".$file) and substr($file, 0, 1) != ".") {
      unset($lasku_asiakas);

      // Luetaan file muuttujaan
      $xmlstr = file_get_contents($verkkolaskuvirheet_vaarat."/".$file);

      $valitutlaskut++;

      // Otetaan tarvittavat muuttujat t‰nnekin
      $xml = simplexml_load_string($xmlstr);

      // Katsotaan mit‰ aineistoa k‰pistell‰‰n
      require "inc/verkkolasku-in-finvoice.inc";

      echo "<tr>";
      echo "<td>$laskuttajan_nimi</td>";
      echo "<td nowrap>";

      $asiakas = finvoice_myyntilaskuksi_valitse_asiakas($toim_asiakkaantiedot, $ostaja_asiakkaantiedot, $laskun_asiakaspupetunnus);

      if ($asiakas["tunnus"] == 0) {
        echo "<form name='asiakashaku_form' action='' method='POST'>";
        echo "<input type='hidden' name = 'tiedosto' value='$file'>";
        echo "<input type='hidden' name='tapa' value='U_JA_P' />";

        echo t('Etsi asiakas ja k‰sittele lasku uudestaan').':<br>';
        echo livesearch_kentta("eisaaollaoikee", "ASIAKASHAKU", "asiakas_haku", 140, $asiakas_haku, '', '', 'asiakas_haku', 'ei_break_all');
        echo "<input type='submit' value='".t("K‰sittele uudestaan")."' />";
        echo "</form>";
        echo "<br/>";
        echo "<br/>";

        echo t("Perusta uusi asiakas").":<br>";
        echo "<form action='{$palvelin2}yllapito.php' method='post'>
            <input type = 'hidden' name = 'toim' value = 'asiakas'>
            <input type = 'hidden' name = 'uusi' value = '1'>
            <input type = 'hidden' name = 't[5]' value = '$ostaja_asiakkaantiedot[nimi]'>
            <input type = 'hidden' name = 't[7]' value = '$ostaja_asiakkaantiedot[osoite]'>
            <input type = 'hidden' name = 't[8]' value = '$ostaja_asiakkaantiedot[postino]'>
            <input type = 'hidden' name = 't[9]' value = '$ostaja_asiakkaantiedot[postitp]'>
            <input type = 'hidden' name = 't[13]' value = '$ostaja_asiakkaantiedot[maa]'>
            <input type = 'hidden' name = 't[14]' value = '$ostaja_asiakkaantiedot[maa]'>
            <input type = 'hidden' name = 't[3]' value = '$ostaja_asiakkaantiedot[ytunnus]'>
            <input type = 'hidden' name = 'lopetus' value = '".$palvelin2."verkkolaskuvirheet_myynti.php////'>
            <input type='submit' value = '".t("Perusta")."'></form><br><br>";
      }
      else {
        echo t("K‰sittele lasku uudestaan").":<br>";
        echo "<form method='post'>
            <input type='hidden' name = 'tiedosto' value ='$file'>
            <input type='hidden' name = 'tapa' value ='U'>
            <input type='submit' value = '".t("K‰sittele uudestaan")."'></form><br><br>";
      }

      echo t("Hylk‰‰ lasku").":<br>";
      echo "<form method='post'>
          <input type='hidden' name = 'tiedosto' value ='$file'>
          <input type='hidden' name = 'tapa' value ='P'>
          <input type='submit' value = '".t("Hylk‰‰")."'></form>";

      echo "</td>";

      echo "<td>$ostaja_asiakkaantiedot[ytunnus]</td>";
      echo "<td>$ostaja_asiakkaantiedot[nimi]<br>$ostaja_asiakkaantiedot[osoite]<br>$ostaja_asiakkaantiedot[postino]<br>$ostaja_asiakkaantiedot[postitp]<br>$ostaja_asiakkaantiedot[maa]</td>";

      $laskun_tapvm = substr($laskun_tapvm, 0, 4)."-".substr($laskun_tapvm, 4, 2)."-".substr($laskun_tapvm, 6, 2);
      $laskun_lapvm = substr($laskun_lapvm, 0, 4)."-".substr($laskun_lapvm, 4, 2)."-".substr($laskun_lapvm, 6, 2);
      $laskun_erapaiva = substr($laskun_erapaiva, 0, 4)."-".substr($laskun_erapaiva, 4, 2)."-".substr($laskun_erapaiva, 6, 2);
      $laskun_kapvm = substr($laskun_kapvm, 0, 4)."-".substr($laskun_kapvm, 4, 2)."-".substr($laskun_kapvm, 6, 2);

      $maksuehto = finvoice_myyntilaskuksi_valitse_maksuehto($laskun_maksuehtoteksti, $laskun_lapvm, $laskun_erapaiva);

      if (empty($maksuehto)) {
        echo "<td>".t("VIRHE: Sopivaa maksuehtoa ei lˆydy!").": $laskun_maksuehtoteksti<br>";
      }
      else {
        echo "<td>$maksuehto[teksti]<br>";
      }

      echo "<td>$laskun_numero<br>$laskun_summa_eur<br>";
      echo "<form id='form_2_$valitutlaskut' name='form_2_$valitutlaskut' method='post'>
      <input type='hidden' name = 'tee' value ='NAYTATILAUS'>
      <input type='hidden' name = 'xml' value ='".urlencode($xmlstr)."'>
      <input type='submit' value = '".t("N‰yt‰ Finvoice")."' onClick=\"js_openFormInNewWindow('form_2_$valitutlaskut', 'form_2_$valitutlaskut'); return false;\"></form>";
      echo "</td>";

      echo "<td>".tv1dateconv($laskun_tapvm)."</td>";
      echo "</tr>";
    }
  }
  closedir($handle);
  echo "</table>";
}

if ($valitutlaskut == 0) {
  echo "<font class='message'>".t("Ei hyl‰ttyj‰ laskuja")."</font><br>";
}

require "inc/footer.inc";
