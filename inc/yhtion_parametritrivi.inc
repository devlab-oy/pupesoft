<?php

if (isset($ytp_fieldname) and $ytp_fieldname != "") {
  $fieldname = $ytp_fieldname;
}
else {
  $ulos   = "";
  $jatko  = 1; // oletetaan normaali k‰sittely
  $tyyppi = 1; // oletetaan rivin n‰kyvyys

  if ($i == 0) { //yhtiˆt‰ ei n‰ytet‰
    $tyyppi = 0;
  }

  $fieldname = mysql_field_name($result, $i);
}

if ($fieldname == "css"
  or $fieldname == "css_classic"
  or $fieldname == "css_extranet"
  or $fieldname == "css_verkkokauppa"
  or $fieldname == "web_seuranta") {
  $ulos = "<td><textarea cols='45' rows='7' name='$fieldname'>$trow[$i]</textarea><input type='hidden' name='{$fieldname}i' value='$i'></td>";
  $jatko = 0;
}

if ($fieldname == "verkkolasku_lah") {
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel1 = "";
  $sel2 = "";
  $sel3 = "";
  $sel4 = "";
  $sel5 = "";
  $sel6 = "";
  $sel7 = "";

  if ($trow[$i] == 'iPost') {
    $sel3 = "selected";
  }
  elseif ($trow[$i] == 'apix') {
    $sel4 = "selected";
  }
  elseif ($trow[$i] == 'finvoice') {
    $sel2 = "selected";
  }
  elseif ($trow[$i] == 'maventa') {
    $sel5 = "selected";
  }
  elseif ($trow[$i] == 'servinet') {
    $sel6 = "selected";
  }
  elseif ($trow[$i] == 'trustpoint') {
    $sel7 = "selected";
  }
  else {
    $sel1 = "selected";
  }

  $ulos .= "<option value = '' $sel1>".t("Pupevoice Itella");
  $ulos .= "<option value = 'finvoice' $sel2>".t("Finvoice");
  $ulos .= "<option value = 'iPost' $sel3>".t("Finvoice Itella iPost");
  $ulos .= "<option value = 'apix' $sel4>".t("Apix-verkkolaskut");
  $ulos .= "<option value = 'maventa' $sel5>".t("Maventa-verkkolaskut");
  $ulos .= "<option value = 'servinet' $sel6>".t("Pupevoice Servinet");
  $ulos .= "<option value = 'trustpoint' $sel7>".t("Trustpoint-verkkolaskut");
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "finvoice_versio") {
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = "";

  if ($trow[$i] == '2') {
    $sel = "selected";
  }

  $ulos .= "<option value = ''>".t("Finvoice 1.2");
  $ulos .= "<option value = '2' $sel>".t("Finvoice 2.01");
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "verkkolasku_vienti") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel1 = "";
  $sel2 = "";
  $sel3 = "";

  if ($trow[$i] == 'K') {
    $sel3 = "selected";
  }
  elseif ($trow[$i] == 'E') {
    $sel2 = "selected";
  }
  else {
    $sel1 = "selected";
  }

  $ulos .= "<option value = ''  $sel1>".t("Vientilaskut (EU) siirret‰‰n verkkolaskuoperaattorille vain jos se asiakkaan takana on sallittu");
  $ulos .= "<option value = 'E' $sel2>".t("Vientilaskut (EU) siirret‰‰n verkkolaskuoperaattorille");
  $ulos .= "<option value = 'K' $sel3>".t("Kaikki vientilaskut siirret‰‰n verkkolaskuoperaattorille");
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "lahetteen_tulostustapa") {

  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array($trow[$i] => 'selected') + array('K' => '', 'L' => '', 'A' => '', 'B' => '', 'I' => '', 'X' => '');

  $ulos .= "<option value = 'K' {$sel['K']}>".t("Myyntitilausten ker‰yslistojen tulostukseen k‰ytet‰‰n tulostusjonoa")."</option>";
  $ulos .= "<option value = 'L' {$sel['L']}>".t("Myyntitilausten ker‰yslistojen ja l‰hetteen tulostukseen k‰ytet‰‰n tulostusjonoa")."</option>";
  $ulos .= "<option value = 'A' {$sel['A']}>".t("Myyntitilausten ker‰yslistat tulostetaan automaattisesti")."</option>";
  $ulos .= "<option value = 'B' {$sel['B']}>".t("Myyntitilausten ker‰yslistat ja l‰hetteet tulostetaan automaattisesti")."</option>";
  $ulos .= "<option value = 'I' {$sel['I']}>".t("Myyntitilausten ker‰yslistat ja l‰hetteet tulostetaan automaattisesti ja tilaus laitetaan toimitetuksi")."</option>";
  $ulos .= "<option value = 'X' {$sel['X']}>".t("Myyntitilausten ker‰yslistojen ja l‰hetteiden tulostus ohitetaan ja tilaus laitetaan suoraan ker‰ykseen")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "kulujen_laskeminen_hintoihin") {

  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = $trow[$i] == 'K' ? "selected" : "";

  $ulos .= "<option value = ''>".t("Hintoihin ei lis‰t‰ kuluja kuluprosentin mukaan")."</option>";
  $ulos .= "<option value = 'K' {$sel}>".t("Hintoihin lis‰t‰‰n kulut kuluprosentin mukaan")."</option>";

  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "myyntitilin_tulostustapa") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel1 = "";
  $sel2 = "";

  if ($trow[$i] == 'A') {
    $sel1 = "selected";
  }
  if ($trow[$i] == 'K') {
    $sel2 = "selected";
  }

  $ulos .= "<option value = 'K' $sel2>".t("Myyntitilien ker‰yslistojen tulostukseen k‰ytet‰‰n tulostusjonoa");
  $ulos .= "<option value = 'A' $sel1>".t("Myyntitilien ker‰yslistat tulostetaan automaattisesti");
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "valmistuksen_tulostustapa") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel1 = "";
  $sel2 = "";

  if ($trow[$i] == 'A') {
    $sel1 = "selected";
  }
  if ($trow[$i] == 'K') {
    $sel2 = "selected";
  }

  $ulos .= "<option value = 'K' $sel2>".t("Valmistuksien ker‰yslistojen tulostukseen k‰ytet‰‰n tulostusjonoa");
  $ulos .= "<option value = 'A' $sel1>".t("Valmistuksien ker‰yslistat tulostetaan automaattisesti");
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "siirtolistan_tulostustapa") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel1 = "";
  $sel2 = "";
  $sel3 = "";

  if ($trow[$i] == 'A') {
    $sel1 = "selected";
  }
  if ($trow[$i] == 'K') {
    $sel2 = "selected";
  }
  if ($trow[$i] == 'U') {
    $sel3 = "selected";
  }

  $ulos .= "<option value = 'K' $sel2>".t("Siirtolistojen tulostukseen k‰ytet‰‰n tulostusjonoa");
  $ulos .= "<option value = 'A' $sel1>".t("Siirtolistat tulostetaan automaattisesti");
  $ulos .= "<option value = 'U' $sel3>".t("Siirtolistat tulostetaan ker‰yserien kautta");
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "pankkitiedostot") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel1 = "";
  $sel2 = "";
  $sel3 = "";

  if ($trow[$i] == '') {
    $sel1 = "selected";
  }
  if ($trow[$i] == 'E') {
    $sel2 = "selected";
  }
  if ($trow[$i] == 'F') {
    $sel3 = "selected";
  }

  $ulos .= "<option value = '' $sel1>".t("Pankit ja p‰iv‰t yhdistet‰‰n");
  $ulos .= "<option value = 'E' $sel2>".t("Jokainen pankki ja p‰iv‰ omaan tiedostoon");
  $ulos .= "<option value = 'F' $sel3>".t("P‰iv‰t yhdistet‰‰n, yksi tiedosto per pankki");
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "tiliotteen_selvittelyt") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel1 = "";
  $sel2 = "";

  if ($trow[$i] == '') {
    $sel1 = "selected";
  }
  if ($trow[$i] == 'E') {
    $sel2 = "selected";
  }

  $ulos .= "<option value = '' $sel1>".t("Tiliotteen kaikki selvittelytiliˆinnit tehd‰‰n yhdelle tositteelle");
  $ulos .= "<option value = 'E' $sel2>".t("Tiliotteen kaikki selvittelytiliˆinnit tehd‰‰n omille tositteilleen");
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "kayttoliittyma") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel1 = "";
  $sel2 = "";

  if ($trow[$i] == '') {
    $sel1 = "selected";
  }
  if ($trow[$i] == 'U') {
    $sel2 = "selected";
  }

  $ulos .= "<option value = '' $sel1>".t("Pupesoft Classic");
  $ulos .= "<option value = 'U' $sel2>".t("Pupesoft Next");
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "laskutyyppi") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $apu = $trow[$i];
  $sel = array();
  $sel[$apu] = "selected";

  foreach (pupesoft_laskutyypit() as $ltyynumero => $ltyyteksti) {
    $ulos .= "<option value = '$ltyynumero' $sel[$ltyynumero]>".t($ltyyteksti)."</option>";
  }

  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "laskun_monistus_kommenttikentta") {
  $ulos = "<td><select name='{$nimi}' " . js_alasvetoMaxWidth($nimi, 400) . ">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "selected";

  $ulos .= "<option value = ''>" . t("Ei n‰ytet‰ kommenttikentt‰‰");
  $ulos .= "<option value = 'V' {$sel["V"]}>" . t("Laskua monistettaessa n‰ytet‰‰n kommenttikentt‰");
  $ulos .= "<option value = 'P' {$sel["P"]}>" . t("Laskua monistettaessa pakollinen kommenttikentt‰");
  $ulos .= "</select></td>";

  $jatko = 0;
}


if ($fieldname == "viivakoodi_laskulle") {
  $apu = $trow[$i];
  $sel = array();
  $sel[$apu] = "selected";

  $ulos  = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";
  $ulos .= "<option value = ''>".t("Laskulle ei tulosteta viivakoodia");
  $ulos .= "<option value = 'K' $sel[K]>".t("Laskulle tulostetaan viivakoodi");
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "koontilaskut_yhdistetaan") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array_fill_keys(explode(",", $trow[$i]), " selected") + array('T' => '', 'U' => '', 'V' => '');

  $ulos .= "<option value = ''>".t("Asiakkaan laskutusosoitteella")."</option>";
  $ulos .= "<option value = 'T' {$sel['T']}>".t("Asiakkaan laskutusosoitteella ja toimitusosoitteella")."</option>";
  $ulos .= "<option value = 'U' {$sel['U']}>".t("Asiakkaan laskutusosoitteella, reklamaatiot/takuut erikseen")."</option>";
  $ulos .= "<option value = 'V' {$sel['V']}>".t("Asiakkaan laskutusosoitteella ja toimitusosoitteella, reklamaatiot/takuut erikseen")."</option>";

  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "koontilaskut_alarajasumma") {
  $ulos = "<td><input type='text' name='{$nimi}' value = '{$trow[$i]}'></td>";
  $jatko = 0;
}

if ($fieldname == "koontilahete_kollitiedot") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $selE = $trow[$i] == 'E' ? 'selected' : '';

  $ulos .= "<option value = ''>".t("Ker‰ysvahvistus/s‰hkˆinen l‰hete: Tuotteiden kollitiedot (SSCC) n‰ytet‰‰n")."</option>";
  $ulos .= "<option value = 'E' {$selE}>".t("Ker‰ysvahvistus/s‰hkˆinen l‰hete: Tuotteiden kollitietoja (SSCC) ei n‰ytet‰")."</option>";

  $ulos .= "</select>";
  $ulos .=  "</td>";

  $jatko = 0;
}

if ($fieldname == "tilausvahvistustyyppi") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $apu = $trow[$i];
  $sel = array();
  $sel[$apu] = "selected";

  foreach (pupesoft_tilausvahvistustyypit() as $seltvtyyppi => $seltvtyyppiteksti) {
    $ulos .= "<option value = '$seltvtyyppi' $sel[$seltvtyyppi]>".t($seltvtyyppiteksti)."</option>";
  }

  $ulos .= "</select></td>";
  $jatko = 0;
} //Jamppa s‰‰t‰‰

if ($fieldname == "tilausvahvistus_tilausnumero") {

  $ulos = "<td><select name='{$nimi}' " . js_alasvetoMaxWidth($nimi, 400) . ">";

  $apu       = $trow[$i];
  $sel       = array();
  $sel[$apu] = "selected";

  $ulos .= "<option value = '0' $sel[0]>" . t("Tilausvahvistukseen tulee tilausnumero") . "</option>";
  $ulos .= "<option value = '1' $sel[1]>" . t("Tilausvahvistukseen tulee asiakkaan tilausnumero, jos se on annettu") . "</option>";

  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "tilausvahvistus_lahetys") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $apu = $trow[$i];
  $sel = array();
  $sel[$apu] = "selected";

  $ulos .= "<option value = '0' $sel[0]>".t("Tilausvahvistus l‰hetet‰‰n jokaisesta osatilauksesta erikseen");
  $ulos .= "<option value = '1' $sel[1]>".t("Tilausvahvistus l‰hetet‰‰n alkuper‰isest‰ tilauksesta");

  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "tilausvahvistus_sisaisista") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $apu = $trow[$i];
  $sel = array();
  $sel[$apu] = "selected";

  $ulos .= "<option value = ''>".t("Tilausvahvistusta ei l‰hetet‰ asiakkaalle sis‰isist‰ laskuista eik‰ suoraan laskutusjonoon laitetuista tilauksista");
  $ulos .= "<option value = 'U' $sel[U]>".t("Tilausvahvistus l‰hetet‰‰n asiakkaalle suoraan laskutusjonoon laitetuista tilauksista");
  $ulos .= "<option value = 'S' $sel[S]>".t("Tilausvahvistus l‰hetet‰‰n asiakkaalle sis‰isist‰ laskuista sek‰ suoraan laskutusjonoon laitetuista tilauksista");

  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "varastosiirto_tilausvahvistus") {
  $apu = $trow[$i];
  $sel = array();
  $sel[$apu] = "selected";

  $ulos  = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";
  $ulos .= "<option value = ''>".t("Varastosiirroista ei l‰hetet‰ vahvistusta");
  $ulos .= "<option value = 'K' $sel[K]>".t("Varastosiirroista l‰hetet‰‰n vahvistus");
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "varastosiirto_kohdepaikka") {
  $sel = $trow[$i] == "K" ? "selected" : "";

  $ulos  = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";
  $ulos .= "<option value = ''>".t("N‰ytet‰‰n varaston sis‰isiss‰ siirroissa kohdepaikka")."</option>";
  $ulos .= "<option value = 'K' {$sel}>".t("N‰ytet‰‰n kaikissa siirroissa kohdepaikka")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "ostotilaustyyppi") {

  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array_fill_keys(array($trow[$i]), 'selected') + array_fill_keys(array('0', 'A', 'B', 'C'), '');

  $ulos .= "<option value = '0' {$sel[0]}>".t("Normaali ostotilauspohja");
  $ulos .= "<option value = 'A' {$sel['A']}>".t("Tulosta nimitykset, piilotetaan toimitusp‰iv‰")."</option>";
  $ulos .= "<option value = 'B' {$sel['B']}>".t("Piilota nimitykset, tulosta toimitusp‰iv‰")."</option>";
  $ulos .= "<option value = 'C' {$sel['C']}>".t("Piilota toimitusp‰iv‰ ja nimitykset")."</option>";
  //$ulos .= "<option value = '1' $sel[1]>".t("Pelkistetty ostotilauspohja");
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "ostotilaukseen_toimittajan_toimaika") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $apu = (int)$trow[$i];
  $sel = array();
  $sel[$apu] = "selected";

  $ulos .= "<option value = '0' $sel[0]>".t("Ei k‰ytet‰");
  $ulos .= "<option value = '1' $sel[1]>".t("Ehdottaa toimitusaikaa");
  $ulos .= "<option value = '2' $sel[2]>".t("Laittaa automaattisesti toimitusajan");

  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "ostotilaus_saman_tuotteen_lisays") {

  $ulos = "<td><select name='$nimi' " . js_alasvetoMaxWidth($nimi, 400) . ">";

  $apu       = $trow[$i];
  $sel       = array();
  $sel[$apu] = "selected";

  $ulos .= "<option value = ''>" . t("Sama tuote voidaan lis‰t‰ ostotilaukselle useaan kertaan");
  $ulos .= "<option value = 'V' $sel[V]>" . t("Saman tuotteen lis‰yksest‰ ostotilaukselle useaan kertaan varoitetaan");
  $ulos .= "<option value = 'E' $sel[E]>" . t("Saman tuotteen lis‰ys ostotilaukselle useaan kertaan estet‰‰n");

  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "ostolaskujen_paivays") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $apu = (int)$trow[$i];
  $sel = array();
  $sel[$apu] = "selected";

  $ulos .= "<option value = '' $sel[0]>".t("Ostolaskulle syˆtet‰‰n vain laskun p‰iv‰");
  $ulos .= "<option value = '1' $sel[1]>".t("Ostolaskulle syˆtet‰‰n laskun p‰iv‰ sek‰ kirjausp‰iv‰");

  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "ostolaskujen_oletusvaluutta") {

  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = $trow[$i] == 'T' ? "selected" : "";

  $ulos .= "<option value = ''>";
  $ulos .= t("Verkkolaskujen vastaanotossa k‰ytet‰‰n ensisijaisesti verkkolaskuaineiston valuuttaa");
  $ulos .= "</option>";

  $ulos .= "<option value = 'T' {$sel}>";
  $ulos .= t("Verkkolaskujen vastaanotossa k‰ytet‰‰n ensisijaisesti toimittajan oletusvaluuttaa");
  $ulos .= "</option>";

  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "ostolaskujen_kurssipaiva") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $apu = (int) $trow[$i];
  $sel = array("0" => "", "1" => "", "2" => "");
  $sel[$apu] = "selected";

  $ulos .= "<option value = '0' $sel[0]>".t("Ostolaskun valuuttakurssi syˆttˆp‰iv‰m‰‰r‰n mukaan");
  $ulos .= "<option value = '1' $sel[1]>".t("Ostolaskun valuuttakurssi kirjausp‰iv‰m‰‰r‰n mukaan");
  $ulos .= "<option value = '2' $sel[2]>".t("Ostolaskun valuuttakurssi laskun p‰iv‰yksen mukaan");

  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "myyntilaskujen_kurssipaiva") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $apu = (int) $trow[$i];
  $sel = array("0" => "", "1" => "", "2" => "", "3" => "");
  $sel[$apu] = "selected";

  $ulos .= "<option value = '0' $sel[0]>".t("Myyntilaskun valuuttakurssi tilauksen luontip‰iv‰n mukaan");
  $ulos .= "<option value = '1' $sel[1]>".t("Myyntilaskun valuuttakurssi tilauksen toimitusp‰iv‰n mukaan");
  $ulos .= "<option value = '2' $sel[2]>".t("Myyntilaskun valuuttakurssi laskun p‰iv‰yksen mukaan");
  $ulos .= "<option value = '3' $sel[3]>".t("Myyntilaskun valuuttakurssi laskutushetken mukaan");

  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "laskutus_tulevaisuuteen") {

  $sel = $trow[$i] == "S" ? "selected" : "";

  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";
  $ulos .= "<option value = ''>".t("Ei sallita")."</option>";
  $ulos .= "<option value = 'S' {$sel}>".t("Saldottomia tuotteita voi laskuttaa tulevaisuuteen")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "kateiskuitin_paivays") {

  $sel = $trow[$i] == "S" ? "selected" : "";

  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";
  $ulos .= "<option value = ''>".t("Kassamyyj‰ ei voi syˆtt‰‰ poikkeavaa p‰iv‰m‰‰r‰‰ k‰teiskuitille")."</option>";
  $ulos .= "<option value = 'S' {$sel}>".t("Kassamyyj‰ voi syˆtt‰‰ poikkeavan p‰iv‰m‰‰r‰n k‰teiskuitille")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "tyomaaraystyyppi") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $sel[$trow[$i]] = "selected";

  $ulos .= "<option value = ''>".t("Tuotetiedot, hinnat ja alennukset");
  $ulos .= "<option value = 'Q' $sel[Q]>".t("Tuotetiedot, hinnat ja alennukset (Verolliset hinnat)");
  $ulos .= "<option value = 'O' $sel[O]>".t("Tuotetiedot");
  $ulos .= "<option value = 'P' $sel[P]>".t("Tuotetiedot ja varastopaikka");


  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "viivakoodi_purkulistaan") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $sel[$trow[$i]] = "selected";

  $ulos .= "<option value = ''>".t("Ei")."</option>";
  $ulos .= "<option value = 'K' $sel[K]>".t("Kyll‰")."</option>";


  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "purkulistan_asettelu") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = $trow[$i] == 'A' ? "selected" : "";

  $ulos .= "<option value = ''>".t("Tuoteno, nimitys, paikka -asettelu")."</option>";
  $ulos .= "<option value = 'A' {$sel}>".t("Tuoteno, paikka, nimitys -asettelu")."</option>";


  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "variaatiomyynti") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $sel[$trow[$i]] = "selected";

  $ulos .= "<option value = ''>".t("Tilausrivien syˆtˆss‰ ei k‰ytet‰ variaatiomatriisia");
  $ulos .= "<option value = 'K' $sel[K]>".t("Tilausrivien syˆtˆss‰ k‰ytet‰‰n variaatiomatriisia");


  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "tarjouksen_tuotepaikat") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $sel[$trow[$i]] = "selected";

  $ulos .= "<option value = ''>".t("Tarjouksella tuotepaikat k‰ytˆss‰");
  $ulos .= "<option value = 'X' $sel[X]>".t("Tarjouksella ei viel‰ valita tuotepaikkoja");


  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "tarjouksen_alv_kasittely") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $sel[$trow[$i]] = "selected";

  $ulos .= "<option value = ''>".t("K‰ytet‰‰n yhtiˆn alv k‰sittely‰")."</option>";
  $ulos .= "<option value = 'S' $sel[S]>".t("Tuotteiden myyntihinnat sis‰lt‰v‰t arvonlis‰veron")."</option>";
  $ulos .= "<option value = 'X' $sel[X]>".t("Tuotteiden myyntihinnat ovat arvonlis‰verottomia")."</option>";


  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "rahti_hinnoittelu") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  if ($trow[$i] == '') {
    $trow[$i] = 'A';
  }
  $sel[$trow[$i]] = "selected";

  $ulos .= "<option value=''  $sel[A]>".t("Rahtikulu muodostuu painon perusteella")."</option>";
  $ulos .= "<option value='o' $sel[o]>".t("Rahtikulu muodostuu tilauksen hinnan perusteella")."</option>";
  $ulos .= "<option value='P' $sel[P]>".t("Rahtikulu muodostuu tuotteilta lˆytyvien painojen mukaan automaattisesti")."</option>";
  $ulos .= "<option value='K' $sel[K]>".t("Rahtikulu syˆtet‰‰n k‰sin")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "alv_kasittely") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel1="";
  $sel2="";

  if ($trow[$i] == "") {
    $sel1 = "selected";
  }
  else {
    $sel2 = "selected";
  }

  $ulos .= "<option value='' $sel1>".t("Tuotteiden myyntihinnat sis‰lt‰v‰t arvonlis‰veron")."</option>";
  $ulos .= "<option value='o' $sel2>".t("Tuotteiden myyntihinnat ovat arvonlis‰verottomia")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "alv_kasittely_hintamuunnos") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel1="";
  $sel2="";

  if ($trow[$i] == "") {
    $sel1 = "selected";
  }
  else {
    $sel2 = "selected";
  }

  $ulos .= "<option value='' $sel1>".t("Tuotteiden myyntihinnat syˆtet‰‰n alv_kasittely-parametrin mukaisesti verollisina/verottomina")."</option>";
  $ulos .= "<option value='o' $sel2>".t("Tuotteiden myyntihinnat syˆtet‰‰n myyntitilauksella verollisina/verottomina sen mukaan n‰ytet‰‰nkˆ tilauksella verolliset vai verottomat hinnat")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "alv_velvollinen") {
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel1 = "";
  $sel2 = "";

  if ($trow[$i] == "") {
    $sel1 = "selected";
  }
  else {
    $sel2 = "selected";
  }

  $ulos .= "<option value='' $sel1>".t("Normaali verok‰sittely")."</option>";
  $ulos .= "<option value='E' $sel2>".t("Verkkolaskut kirjataan aina verottomina")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "asiakashinta_netto") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel1="";
  $sel2="";
  $sel3="";

  if ($trow[$i] == "") {
    $sel1 = "selected";
  }
  elseif ($trow[$i] == "E") {
    $sel2 = "selected";
  }
  elseif ($trow[$i] == "B") {
    $sel3 = "selected";
  }

  $ulos .= "<option value=''  $sel1>".t("Asiakashinnoille ei anneta mit‰‰n alennuksia")."</option>";
  $ulos .= "<option value='E' $sel2>".t("Asiakashinnoille annetaan vain erikoisalennus")."</option>";
  $ulos .= "<option value='B' $sel3>".t("Asiakashinnoille annetaan kaikki alennukset")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "puute_jt_oletus") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel1 = "";
  $sel2 = "";
  $sel3 = "";
  $sel4 = "";
  $sel5 = "";
  $sel6 = "";

  if ($trow[$i] == "") {
    $sel1 = "selected";
  }
  elseif ($trow[$i] == "J") {
    $sel2 = "selected";
  }
  elseif ($trow[$i] == "H") {
    $sel3 = "selected";
  }
  elseif ($trow[$i] == "O") {
    $sel4 = "selected";
  }
  elseif ($trow[$i] == "A") {
    $sel5 = "selected";
  }
  elseif ($trow[$i] == "B") {
    $sel6 = "selected";
  }

  $ulos .= "<option value=''  $sel1>".t("Jos varastosaldo ei riit‰, tilausrivi merkataan puuteriviksi")."</option>";
  $ulos .= "<option value='A' $sel5>".t("Jos varastosaldo ei riit‰, tilausrivi merkataan puutteeksi uusimmalla tuotteella")."</option>";
  $ulos .= "<option value='J' $sel2>".t("Jos varastosaldo ei riit‰, tilausrivi merkataan j‰lkitoimitukseksi")."</option>";
  $ulos .= "<option value='B' $sel6>".t("Jos varastosaldo ei riit‰, tilausrivi merkataan j‰lkitoimitukseksi uusimmalla tuotteella")."</option>";
  $ulos .= "<option value='H' $sel3>".t("Jos varastosaldo ei riit‰, tilausrivi myyd‰‰n normaalisti")."</option>";
  $ulos .= "<option value='O' $sel4>".t("Kaikki myyd‰‰n aina oletuspaikalta")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "puute_jt_kerataanko") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "SELECTED";

  $ulos .= "<option value = ''>".t("Puuterivej‰ ja JT-rivej‰ EI ker‰t‰")."</option>";
  $ulos .= "<option value = 'P' $sel[P]>".t("Puuterivit ker‰t‰‰n")."</option>";
  $ulos .= "<option value = 'J' $sel[J]>".t("JT-rivit ker‰t‰‰n")."</option>";
  $ulos .= "<option value = 'Q' $sel[Q]>".t("Puuterivit ja JT-rivit ker‰t‰‰n")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "myynti_jt_huom") {

  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = $trow[$i] == 'K' ? 'selected' : '';

  $ulos .= "<option value = ''>".t("Myyntitilauksella ei ilmoiteta k‰ytt‰j‰lle jos tuotetta on jo j‰lkitoimituksessa")."</option>";
  $ulos .= "<option value = 'K' {$sel}>".t("Myyntitilauksella ilmoitetaan k‰ytt‰j‰lle jos tuotetta on jo j‰lkitoimituksessa")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "saldo_kasittely") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel1 = "";
  $sel2 = "";
  $sel3 = "";

  if ($trow[$i] == "") {
    $sel1 = "selected";
  }
  elseif ($trow[$i] == "T") {
    $sel2 = "selected";
  }
  elseif ($trow[$i] == "U") {
    $sel3 = "selected";
  }

  $ulos .= "<option value = ''  $sel1>".t("Myyt‰viss‰m‰‰r‰ ajasta riippumaton")."</option>";
  $ulos .= "<option value = 'T' $sel2>".t("Myyt‰viss‰m‰‰r‰ lasketaan ker‰yspvm:n mukaan")."</option>";
  $ulos .= "<option value = 'U' $sel3>".t("Myyt‰viss‰m‰‰r‰ lasketaan ker‰yspvm:n mukaan niin, ett‰ tulevat tapahtumat vaikuttavat t‰m‰n hetken myyt‰viss‰m‰‰r‰‰n")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "kerataanko_saldottomat") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel1 = "";
  $sel2 = "";

  if ($trow[$i] == "") {
    $sel1 = "selected";
  }
  elseif ($trow[$i] == "S") {
    $sel2 = "selected";
  }


  $ulos .= "<option value = ''  $sel1>".t("Saldottomia tuotteita ei ker‰t‰")."</option>";
  $ulos .= "<option value = 'S' $sel2>".t("Saldottomat tuotteet ker‰t‰‰n")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "kerataanko_valmistukset") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array($trow[$i] => "SELECTED");

  $ulos .= "<option value = ''>".t("Valmistukset ker‰t‰‰n")."</option>";
  $ulos .= "<option value = 'E' $sel[E]>".t("Valmistuksia ei ker‰t‰")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "kerataanko_tyomaaraykset") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array($trow[$i] => "SELECTED");

  $ulos .= "<option value = ''>".t("Tyˆm‰‰r‰ykset ker‰t‰‰n")."</option>";
  $ulos .= "<option value = 'E' $sel[E]>".t("Tyˆm‰‰r‰yksi‰ ei ker‰t‰")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "kerayslistojen_yhdistaminen") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel1 = "";
  $sel2 = "";
  $sel3 = "";

  if ($trow[$i] == "") {
    $sel1 = "selected";
  }
  elseif ($trow[$i] == "Y") {
    $sel2 = "selected";
  }
  elseif ($trow[$i] == "T") {
    $sel3 = "selected";
  }

  $ulos .= "<option value=''  $sel1>".t("Asiakkaan tilauksia EI yhdistet‰ yhdelle ker‰yslistalle")."</option>";
  $ulos .= "<option value='Y' $sel2>".t("Asiakkaan tilaukset yhdistet‰‰n yhdelle ker‰yslistalle jos mahdollista")."</option>";
  $ulos .= "<option value='T' $sel3>".t("Asiakkaan tilaukset yhdistet‰‰n yhdelle ker‰yslistalle Ytunnuksen mukaan")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "karayksesta_rahtikirjasyottoon") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel1 = "";
  $sel2 = "";
  $sel3 = "";

  if ($trow[$i] == "") {
    $sel1 = "selected";
  }
  elseif ($trow[$i] == "Y") {
    $sel2 = "selected";
  }
  elseif ($trow[$i] == "H") {
    $sel3 = "selected";
  }

  $ulos .= "<option value=''  $sel1>".t("Ker‰yksest‰ ei siirryt‰ suoraan rahtikirjan syˆttˆˆn")."</option>";
  $ulos .= "<option value='Y' $sel2>".t("Ker‰yksest‰ siirryt‰‰n suoraan rahtikirjan syˆttˆˆn")."</option>";
  $ulos .= "<option value='H' $sel3>".t("Ker‰yksest‰ siirryt‰‰n suoraan rahtikirjan syˆttˆˆn, jos halutaan")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "rahtikirjojen_esisyotto") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel1 = "";
  $sel2 = "";

  if ($trow[$i] == "") {
    $sel1 = "selected";
  }
  elseif ($trow[$i] == "M") {
    $sel2 = "selected";
  }

  $ulos .= "<option value=''  $sel1>".t("Ei rahtikirjojen esisyˆttˆ‰")."</option>";
  $ulos .= "<option value='M' $sel2>".t("Rahtikirjojen esisyˆttˆ mahdollista myynnin yhteydess‰")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "rahtikirjan_kollit_ja_lajit") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel1 = "";
  $sel2 = "";

  if ($trow[$i] == "") {
    $sel1 = "selected";
  }
  elseif ($trow[$i] == "E") {
    $sel2 = "selected";
  }

  $ulos .= "<option value=''  $sel1>".t("Yhteen")."</option>";
  $ulos .= "<option value='E' $sel2>".t("Erikseen")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "tee_osto_myyntitilaukselta") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array_fill_keys(array('A', 'B', 'C', 'I', 'J', 'Q', 'U', 'V', 'W', 'X', 'Y', 'Z'), '');
  $sel[$trow[$i]] = " SELECTED";

  $ulos .= "<option value=''>".t("Myyntitilaukselta ei voi luoda ostorivej‰")."</option>";
  $ulos .= "<option value='X' {$sel['X']}>".t("Myyntitilaukselta voi luoda ostorivej‰ varastoon")."</option>";
  $ulos .= "<option value='U' {$sel['U']}>".t("Myyntitilaukselta voi luoda ostorivej‰ asiakkaalle")."</option>";
  $ulos .= "<option value='Y' {$sel['Y']}>".t("Myyntitilaukselta voi luoda ostorivej‰ asiakkaalle ja varastoon")."</option>";
  $ulos .= "<option value='A' {$sel['A']}>".t("Myyntitilaukselta voi luoda yhden toimittajan ostorivej‰ varastoon")."</option>";
  $ulos .= "<option value='B' {$sel['B']}>".t("Myyntitilaukselta voi luoda yhden toimittajan ostorivej‰ asiakkaalle")."</option>";
  $ulos .= "<option value='C' {$sel['C']}>".t("Myyntitilaukselta voi luoda yhden toimittajan ostorivej‰ asiakkaalle ja varastoon")."</option>";

  $ulos .= "<option value='V' {$sel['V']}>".t("Myyntitilaukselta voi luoda ostorivej‰ varastoon, jos se on tuotteen takana sallittu")."</option>";
  $ulos .= "<option value='W' {$sel['W']}>".t("Myyntitilaukselta voi luoda ostorivej‰ asiakkaalle ja varastoon, jos se on tuotteen takana sallittu")."</option>";

  $ulos .= "<option value='I' {$sel['I']}>".t("Myyntitilaukselta voi luoda yhden toimittajan ostorivej‰ varastoon, jos se on tuotteen takana sallittu")."</option>";
  $ulos .= "<option value='J' {$sel['J']}>".t("Myyntitilaukselta voi luoda yhden toimittajan ostorivej‰ asiakkaalle ja varastoon, jos se on tuotteen takana sallittu")."</option>";

  $ulos .= "<option value='Z' {$sel['Z']}>".t("Myyntitilauksen voi siirt‰‰ varaston ostotilaukseksi")."</option>";
  $ulos .= "<option value='Q' {$sel['Q']}>".t("Myyntitilauksen voi siirt‰‰ ostotilaukseksi, joka toimitetaan suoraan asiakkaalle")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "tee_automaattinen_siirto_myyntitilaukselta") {

  $sel = $trow[$i] == 'K' ? ' selected' : '';

  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $ulos .= "<option value=''>".t("Ei k‰ytˆss‰")."</option>";
  $ulos .= "<option value='K' {$sel}>".t("Tehd‰‰n siirtoja jos l‰hdevaraston myyt‰viss‰m‰‰r‰ riitt‰‰")."</option>";

  $ulos .= "</select>";
  $ulos .= "</td>";

  $jatko = 0;
}

if ($fieldname == "tee_automaattinen_osto_myyntitilaukselta") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array_fill_keys(array('X', 'U', 'V', 'Y', 'H', 'W'), '');
  $sel[$trow[$i]] = " SELECTED";

  $ulos .= "<option value=''>".t("Myyntitilauksella ei voi luoda automaattisesti ostorivej‰")."</option>";
  $ulos .= "<option value='X' {$sel['X']}>".t("Myyntitilauksella luodaan automaattisesti ostorivej‰ varastoon jos tee_osto_myyntitilaukselta-parametri sallii sen")."</option>";
  $ulos .= "<option value='U' {$sel['U']}>".t("Myyntitilauksella luodaan automaattisesti ostorivej‰ asiakkaalle jos tee_osto_myyntitilaukselta-parametri sallii sen")."</option>";
  $ulos .= "<option value='Y' {$sel['Y']}>".t("Myyntitilauksella luodaan automaattisesti ostorivej‰ varastoon jos tee_osto_myyntitilaukselta-parametri sallii sen ja tehtaansaldo riitt‰‰")."</option>";
  $ulos .= "<option value='V' {$sel['V']}>".t("Myyntitilauksella luodaan automaattisesti ostorivej‰ asiakkaalle jos tee_osto_myyntitilaukselta-parametri sallii sen ja tehtaansaldo riitt‰‰")."</option>";
  $ulos .= "<option value='H' {$sel['H']}>".t("Myyntitilauksella luodaan automaattisesti ostorivej‰ varastoon jos tee_osto_myyntitilaukselta-parametri sallii sen ja tehtaansaldo riitt‰‰ (tilattava tuote ensisijaisesti)")."</option>";
  $ulos .= "<option value='W' {$sel['W']}>".t("Myyntitilauksella luodaan automaattisesti ostorivej‰ asiakkaalle jos tee_osto_myyntitilaukselta-parametri sallii sen ja tehtaansaldo riitt‰‰ (tilattava tuote ensisijaisesti)")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "tee_valmistus_myyntitilaukselta") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "selected";

  $ulos .= "<option value = ''>".t("Myyntitilaukselta ei voi luoda valmistusrivej‰")."</option>";
  $ulos .= "<option value = 'K' $sel[K]>".t("Myyntitilaukselta voi luoda valmistusrivej‰")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "tee_siirtolista_myyntitilaukselta") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "selected";

  $ulos .= "<option value = ''>".t("Myyntitilaukselta ei voi luoda siirtolistoja")."</option>";
  $ulos .= "<option value = 'K' $sel[K]>".t("Myyntitilaukselta voi luoda siirtolistoja")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "siirtolistat_vastaanotetaan_per_lahto") {

  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = $trow[$i] == 'K' ? "selected" : "";

  $ulos .= "<option value = ''>".t("Ei")."</option>";
  $ulos .= "<option value = 'K' {$sel}>".t("Kyll‰")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "kirjanpidollinen_varastosiirto_myyntitilaukselta") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "selected";

  $ulos .= "<option value = ''>".t("Myyntitilauksesta ei luoda kirjanpidollisia varastosiirtoja")."</option>";
  $ulos .= "<option value = 'K' $sel[K]>".t("Myyntitilauksesta luodaan kirjanpidollisia varastosiirtoja")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "automaattinen_jt_toimitus") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $sel[$trow[$i]] = "SELECTED";

  $ulos .= "<option value=''>".t("J‰lkitoimitusrivej‰ ei toimiteta eik‰ kohdisteta automaattisesti")."</option>";
  $ulos .= "<option value='K' $sel[K]>".t("J‰lkitoimitusrivit kohdistetaan automaattisesti varastoonviennin yhteydess‰")."</option>";
  $ulos .= "<option value='T' $sel[T]>".t("J‰lkitoimitusrivit kohdistetaan ja toimitetaan automaattisesti varastoonviennin yhteydess‰, jos k‰ytt‰j‰ll‰ on p‰ivitysoikeus JT-selaukseen")."</option>";
  $ulos .= "<option value='J' $sel[J]>".t("J‰lkitoimitusrivit kohdistetaan ja toimitetaan automaattisesti varastoonviennin yhteydess‰")."</option>";
  $ulos .= "<option value='A' $sel[A]>".t("J‰lkitoimitusrivit kohdistetaan ja toimitetaan automaattisesti varastoonviennin yhteydess‰ ker‰ysp‰iv‰n mukaisesti")."</option>";
  $ulos .= "<option value='S' $sel[S]>".t("Ainoastaan suoratoimitetut j‰lkitoimitusrivit kohdistetaan ja toimitetaan automaattisesti varastoonviennin yhteydess‰")."</option>";
  $ulos .= "<option value='V' $sel[V]>".t("Suoratoimitetut sek‰ myyntitilaukselta varastoon tilatut j‰lkitoimitusrivit kohdistetaan ja toimitetaan automaattisesti varastoonviennin yhteydess‰")."</option>";
  $ulos .= "<option value='U' $sel[U]>".t("Osatoimituskiellossa olevien tilausten j‰lkitoimitusrivit kohdistetaan ja toimitetaan automaattisesti varastoonviennin yhteydess‰")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "automaattinen_jt_toimitus_valmistus") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $sel[$trow[$i]] = "SELECTED";

  $ulos .= "<option value=''>".t("Valmistusrivej‰ ei toimiteta eik‰ kohdisteta automaattisesti")."</option>";
  $ulos .= "<option value='K' $sel[K]>".t("J‰lkitoimitusrivit kohdistetaan automaattisesti valmistuksen yhteydess‰")."</option>";
  $ulos .= "<option value='T' $sel[T]>".t("J‰lkitoimitusrivit kohdistetaan ja toimitetaan automaattisesti valmistuksen yhteydess‰, jos k‰ytt‰j‰ll‰ on p‰ivitysoikeus JT-selaukseen")."</option>";
  $ulos .= "<option value='J' $sel[J]>".t("J‰lkitoimitusrivit kohdistetaan ja toimitetaan automaattisesti valmistuksen yhteydess‰")."</option>";
  $ulos .= "<option value='S' $sel[S]>".t("Ainoastaan asiakkaalle valmistetut j‰lkitoimitusrivit kohdistetaan ja siirret‰‰n tulostusjonoon automaattisesti valmistuksen yhteydess‰")."</option>";
  $ulos .= "<option value='V' $sel[V]>".t("Asiakkaalle sek‰ myyntitilaukselta varastoon valmistetut j‰lkitoimitusrivit kohdistetaan asiakkaalle ja toimitetaan automaattisesti valmistuksen yhteydess‰")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "automaattinen_jt_toimitus_siirtolista") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $sel[$trow[$i]] = "SELECTED";

  $ulos .= "<option value=''>".t("Siirtolistarivej‰ ei toimiteta eik‰ kohdisteta automaattisesti")."</option>";
  $ulos .= "<option value='K' $sel[K]>".t("J‰lkitoimitusrivit kohdistetaan automaattisesti vastaanoton yhteydess‰")."</option>";
  $ulos .= "<option value='T' $sel[T]>".t("J‰lkitoimitusrivit kohdistetaan ja toimitetaan automaattisesti vastaanoton yhteydess‰, jos k‰ytt‰j‰ll‰ on p‰ivitysoikeus JT-selaukseen")."</option>";
  $ulos .= "<option value='J' $sel[J]>".t("J‰lkitoimitusrivit kohdistetaan ja toimitetaan automaattisesti vastaanoton yhteydess‰")."</option>";
  $ulos .= "<option value='S' $sel[S]>".t("Ainoastaan asiakkaalle myydyt j‰lkitoimitusrivit kohdistetaan ja siirret‰‰n tulostusjonoon automaattisesti siirtolistan vastaanoton yhteydess‰")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "kerataanko_jos_vain_puute_jt") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $sel[$trow[$i]] = "SELECTED";

  $ulos .= "<option value = ''>".t("Tilausta EI ker‰t‰ jos listalla on vain Puuterivej‰ JA/TAI JT-rivej‰")."</option>";
  $ulos .= "<option value = 'P' $sel[P]>".t("Tilaus ker‰t‰‰n jos listalla on vain Puuterivej‰")."</option>";
  $ulos .= "<option value = 'J' $sel[J]>".t("Tilaus ker‰t‰‰n jos listalla on vain JT-rivej‰")."</option>";
  $ulos .= "<option value = 'Q' $sel[Q]>".t("Tilaus ker‰t‰‰n jos listalla on vain Puuterivej‰ JA/TAI JT-rivej‰")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "jt_automatiikka") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $sel[$trow[$i]] = "SELECTED";

  $ulos .= "<option value=''>".t("Asiakkaan JT-rivej‰ ei kohdisteta automaattisesti")."</option>";
  $ulos .= "<option value='Y' $sel[Y]>".t("Asiakkaan JT-rivej‰ kohdistetaan automaattisesti sis‰‰ntulevalle EDI-tilaukselle")."</option>";
  $ulos .= "<option value='X' $sel[X]>".t("Asiakkaan JT-rivej‰ kohdistetaan automaattisesti Extranet-myyntitilaukselle")."</option>";
  $ulos .= "<option value='W' $sel[W]>".t("Asiakkaan JT-rivej‰ kohdistetaan automaattisesti Extranet-myyntitilaukselle ja sis‰‰ntulevalle EDI-tilaukselle")."</option>";
  $ulos .= "<option value='M' $sel[M]>".t("Asiakkaan JT-rivej‰ kohdistetaan automaattisesti myyntitilaukselle")."</option>";
  $ulos .= "<option value='K' $sel[K]>".t("Asiakkaan JT-rivej‰ kohdistetaan automaattisesti myyntitilaukselle ja sis‰‰ntulevalle EDI-tilaukselle")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "jt_automatiikka_mitatoi_tilaus") {

  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = $trow[$i] == 'E' ? "selected" : "";

  $ulos .= "<option value = ''>".t("Jos myyntitilaus mit‰tˆid‰‰n, poistetaan tilauksella olevat JT-rivit (ei koske extranet:ia)")."</option>";
  $ulos .= "<option value = 'E' {$sel}>".t("Ei mit‰tˆid‰ JT-rivej‰ myyntitilauksen mukana, vaan palautetaan ne alkuper‰iselle tilaukselle")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "saapumisen_jt_kasittely") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $sel[$trow[$i]] = "SELECTED";

  $ulos .= "<option value=''>".t("Ostotilauksen saapuvan m‰‰r‰n ollessa tilattua pienempi, erotukselle ei tehd‰ mit‰‰n")."</option>";
  $ulos .= "<option value='K' $sel[K]>".t("Ostotilauksen saapuvan m‰‰r‰n ollessa tilattua pienempi, erotus siirret‰‰n uudelle riville")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "jt_toimitus_varastorajaus") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $sel[$trow[$i]] = " SELECTED";

  $ulos .= "<option value=''>".t("J‰lkitoimituksia toimitetaan kaikista varastoista")."</option>";
  $ulos .= "<option value='K'{$sel['K']}>".t("J‰lkitoimituksia toimitaan ainoastaan myyntitilauksen otsikolle valitusta varastosta")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "tuotekommentti_tilausriville") {

  $sel = array($trow[$i] => 'selected') + array('M' => '', 'O' => '');

  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";
  $ulos .= "<option value=''>".t("Tuotekommentti tilausriville toimii myynti- ja ostotilauksella")."</option>";
  $ulos .= "<option value='M' {$sel['M']}>".t("Tuotekommentti tilausriville toimii vain myyntitilauksella")."</option>";
  $ulos .= "<option value='O' {$sel['O']}>".t("Tuotekommentti tilausriville toimii vain ostotilauksella")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "naytetaan_katteet_tilauksella") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel1 = "";
  $sel2 = "";

  if ($trow[$i] == "") {
    $sel1 = "selected";
  }
  elseif ($trow[$i] == "Y") {
    $sel2 = "selected";
  }
  elseif ($trow[$i] == "B") {
    $sel3 = "selected";
  }

  $ulos .= "<option value=''  $sel1>".t("Katetta ei n‰ytet‰ tilauksentekovaiheessa")."</option>";
  $ulos .= "<option value='Y' $sel2>".t("Kate n‰ytet‰‰n tilauksentekovaiheessa")."</option>";
  $ulos .= "<option value='B' $sel3>".t("Bruttokate n‰ytet‰‰n tilauksentekovaiheessa ja tuotekyselyss‰")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "nimityksen_muutos_tilauksella") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel1 = "";
  $sel2 = "";
  $sel3 = "";

  if ($trow[$i] == "") {
    $sel1 = "selected";
  }
  elseif ($trow[$i] == "Y") {
    $sel2 = "selected";
  }
  elseif ($trow[$i] == "T") {
    $sel3 = "selected";
  }

  $ulos .= "<option value=''  $sel1>".t("Tuotteen nimityst‰ ei voi muokata tilauksentekovaiheessa")."</option>";
  $ulos .= "<option value='Y' $sel2>".t("Tuotteen nimityst‰ voi muokata tilauksentekovaiheessa")."</option>";
  $ulos .= "<option value='T' $sel3>".t("Tuotteen nimityst‰ voi muokata tarjouksentekovaiheessa")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "hintapyoristys") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array_fill(2, 5, "");
  $sel[$trow[$i]] = "SELECTED";

  $ulos .= "<option value='2' $sel[2]>".t("Yksikkˆ- ja rivihinnat pyˆristet‰‰n kahteen desimaaliin")."</option>";
  $ulos .= "<option value='3' $sel[3]>".t("Yksikkˆ- ja rivihinnat pyˆristet‰‰n kolmeen desimaaliin")."</option>";
  $ulos .= "<option value='4' $sel[4]>".t("Yksikkˆ- ja rivihinnat pyˆristet‰‰n nelj‰‰n desimaaliin")."</option>";
  $ulos .= "<option value='5' $sel[5]>".t("Yksikkˆ- ja rivihinnat pyˆristet‰‰n viiteen desimaaliin")."</option>";
  $ulos .= "<option value='6' $sel[6]>".t("Yksikkˆ- ja rivihinnat pyˆristet‰‰n kuuteen desimaaliin")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "hintapyoristys_loppunollat") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel1="";

  if ($trow[$i] != "") {
    $sel1 = "selected";
  }

  $ulos .= "<option value=''>".t("Yksikkˆ- ja rivihinnan kaikki desimaalit n‰ytet‰‰n")."</option>";
  $ulos .= "<option value='P' $sel1>".t("Yksikkˆ- ja rivihinnoista poistetaan merkityksettˆm‰t loppunollat")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "laskunsummapyoristys") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel1="";
  $sel2="";

  if ($trow[$i] == "") {
    $sel1 = "selected";
  }
  else {
    $sel2 = "selected";
  }

  $ulos .= "<option value='' $sel1>".t("Laskun loppusummaa ei pyˆristet‰")."</option>";
  $ulos .= "<option value='o' $sel2>".t("Laskun loppusumma pyˆristet‰‰n lahimp‰‰n kokonaislukuun")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "dynaaminen_kassamyynti") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel1="";
  $sel2="";

  if ($trow[$i] == "") {
    $sel1 = "selected";
  }
  else {
    $sel2 = "selected";
  }

  $ulos .= "<option value='' $sel1>".t("Normaalimyyj‰ ei toimi kassamyyj‰n‰")."</option>";
  $ulos .= "<option value='o' $sel2>".t("Normaalimyyj‰ voi toimia tarpeen mukaan kassamyyj‰n‰")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "maksupaate_kassamyynti") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel1="";

  if ($trow[$i] != "") {
    $sel1 = "selected";
  }

  $ulos .= "<option value=''>".t("Ei k‰ytet‰ maksup‰‰tett‰ kassamyynniss‰")."</option>";
  $ulos .= "<option value='K' $sel1>".t("K‰ytet‰‰n maksup‰‰tett‰ kassamyynniss‰")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "pikatilaus_focus") {
  $ulos = "<td><select name='$nimi' " . js_alasvetoMaxWidth($nimi, 400) . ">";

  $sel = array();
  $sel[$trow[$i]]  = " selected";

  $ulos .= "<option value = ''>" . t("Uudella pikatilauksella kursori oletuksena tuotenumero-kent‰ss‰");
  $ulos .= "<option value = 'A' $sel[A]>" . t("Uudella pikatilauksella kursori oletuksena asiakas-kent‰ss‰");
  $ulos .= "<option value = 'M' $sel[M]>" . t("Uudella pikatilauksella kursori oletuksena myyj‰nro-kent‰ss‰");
  $ulos .= "<option value = 'Y' $sel[Y]>" . t("Uudella pikatilauksella kursori oletuksena myyj‰nro-kent‰ss‰. Myyj‰numero on syˆtett‰v‰");
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "kerayspoikkeama_email") {

  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = $trow[$i] == "P" ? " selected" : "";

  $ulos .= "<option value=''>".t("Ker‰yspoikkeama-s‰hkˆpostit ovat HTML-pohjaisia")."</option>";
  $ulos .= "<option value='P' {$sel}>".t("Ker‰yspoikkeama-s‰hkˆpostit ovat tekstipohjaisia")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "kerayspoikkeama_kasittely") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel      = array();
  $sel[$trow[$i]]  = " selected";

  $ulos .= "<option value=''>".t("Ker‰yspoikkeamia ei k‰sitell‰")."</option>";
  $ulos .= "<option value='o' $sel[o]>".t("Ker‰‰j‰ p‰‰tt‰‰ ker‰yspoikkeaman k‰sittelyst‰")."</option>";
  $ulos .= "<option value='P' $sel[P]>".t("Ker‰‰j‰ p‰‰tt‰‰ ker‰yspoikkeaman k‰sittelyst‰ ja voi vaihtaa ker‰yspaikkaa")."</option>";
  $ulos .= "<option value='J' $sel[J]>".t("Ker‰‰j‰ p‰‰tt‰‰ ker‰yspoikkeaman k‰sittelyst‰ (Oletuksena JT)")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "kerayspoikkeamaviestin_lahetys") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel    = array();
  $sel[$trow[$i]]  = " selected";

  $ulos .= "<option value='0' $sel[0]>".t("Ker‰yspoikkeamaviesti l‰hetet‰‰n asiakkaalle ja myyj‰lle")."</option>";
  $ulos .= "<option value='2' $sel[2]>".t("Ker‰yspoikkeamaviesti l‰hetet‰‰n vain myyj‰lle")."</option>";
  $ulos .= "<option value='1' $sel[1]>".t("Ker‰yspoikkeamaviesti‰ ei l‰hetet‰ ollenkaan")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "keraysvahvistus_lahetys") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel      = array();
  $sel = array_fill_keys(array($trow[$i]), ' selected') + array_fill_keys(array('o', 'k', 'L', 'M', 'N', 'Q', 'P'), '');

  $ulos .= "<option value=''>".t("Ker‰ysvahvistusta/s‰hkˆist‰ l‰hetett‰ ei l‰hetet‰")."</option>";
  $ulos .= "<option value='o'{$sel['o']}>".t("Ker‰ysvahvistus/s‰hkˆinen l‰hete l‰hetet‰‰n asiakkaalle jokaisesta toimituksesta erikseen")."</option>";
  $ulos .= "<option value='k'{$sel['k']}>".t("Ker‰ysvahvistus/s‰hkˆinen l‰hete l‰hetet‰‰n asiakkaalle kun koko alkuper‰inen tilaus on ker‰tty. Paperista l‰hetett‰ ei tulosteta. ")."</option>";
  $ulos .= "<option value='L'{$sel['L']}>".t("Ker‰ysvahvistusta/s‰hkˆist‰ l‰hetett‰ ei l‰hetet‰. Paperinen l‰hete tulostetaan asiakkaalle kun koko alkuper‰inen tilaus on ker‰tty")."</option>";
  $ulos .= "<option value='M'{$sel['M']}>".t("Ker‰ysvahvistus/s‰hkˆinen ja paperinen l‰hete l‰hetet‰‰n/tulostetaan asiakkaalle kun koko alkuper‰inen tilaus on ker‰tty")."</option>";
  $ulos .= "<option value='N'{$sel['N']}>".t("Ker‰ysvahvistus/s‰hkˆinen l‰hete l‰hetet‰‰n asiakkaalle kun koko alkuper‰inen tilaus (myˆs JT-rivit) on ker‰tty. Paperista l‰hetett‰ ei tulosteta.")."</option>";
  $ulos .= "<option value='Q'{$sel['Q']}>".t("Ker‰ysvahvistusta/s‰hkˆist‰ l‰hetett‰ ei l‰hetet‰. Paperinen l‰hete tulostetaan asiakkaalle kun koko alkuper‰inen tilaus (myˆs JT-rivit) on ker‰tty")."</option>";
  $ulos .= "<option value='P'{$sel['P']}>".t("Ker‰ysvahvistus/s‰hkˆinen ja paperinen l‰hete l‰hetet‰‰n/tulostetaan asiakkaalle kun koko alkuper‰inen tilaus (myˆs JT-rivit) on ker‰tty")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "kerays_riveittain") {

  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = $trow[$i] == 'K' ? 'selected' : '';

  $ulos .= "<option value=''>".t("Ker‰ys merkataan valmiiksi kerralla")."</option>";
  $ulos .= "<option value='K' {$sel}>".t("Ker‰ys merkataan valmiiksi vasta kun kaikki rivit on merkattu erikseen ker‰tyiksi")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "oletus_toimitusehto") {

  $kires = t_avainsana("TOIMEHTO");

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";
  $ulos .= "<option value=''>".t("Ei oletustoimitusehtoa")."</option>";

  while ($kirow=mysql_fetch_array($kires)) {
    if ($kirow["selite"]." ".$kirow["selitetark"] == $trow[$i]) $select='SELECTED';
    else $select = '';

    $ulos .= "<option value='$kirow[selite] $kirow[selitetark]' $select>$kirow[selite] - $kirow[selitetark]</option>";
  }

  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "oletus_toimitusehto2") {

  $kires = t_avainsana("TOIMEHTO");

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";
  $ulos .= "<option value=''>".t("Ei oletustoimitusehtoa vaihtokohteille")."</option>";

  while ($kirow=mysql_fetch_array($kires)) {
    if ($kirow["selite"]." ".$kirow["selitetark"] == $trow[$i]) $select='SELECTED';
    else $select = '';

    $ulos .= "<option value='$kirow[selite] $kirow[selitetark]' $select>$kirow[selite] - $kirow[selitetark]</option>";
  }

  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "lasku_tulostin") {

  $query = "SELECT *
            from kirjoittimet
            where yhtio  = '$kukarow[yhtio]'
            AND komento != 'EDI'
            ORDER BY kirjoitin";
  $kires = pupe_query($query);

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";
  $ulos .= "<option value=''>".t("Ei kirjoitinta")."</option>";

  while ($kirow = mysql_fetch_array($kires)) {
    if ($kirow["tunnus"]==$trow[$i]) $select='SELECTED';
    else $select = '';

    $ulos .= "<option value='$kirow[tunnus]' $select>$kirow[kirjoitin]</option>";
  }

  if ($trow[$i] == -88) $select = 'SELECTED';
  else $select = '';

  $ulos .= "<option value='-88' $select>".t("PDF Ruudulle")."</option>";

  if ($trow[$i] == -99) $select = 'SELECTED';
  else $select = '';

  $ulos .= "<option value='-99' $select>".t("Asiakkaan s‰hkˆpostiin")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "laskutuskielto") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel1 = "";
  $sel2 = "";

  if ($trow[$i] == '0') {
    $sel1 = "selected";
  }
  if ($trow[$i] == '1') {
    $sel2 = "selected";
  }

  $ulos .= "<option value = '0' $sel1>".t("Ei k‰ytet‰ laskutuskieltoa");
  $ulos .= "<option value = '1' $sel2>".t("K‰ytet‰‰n laskutuskieltoa");
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "splittauskielto") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array($trow[$i] => "SELECTED");

  $ulos .= "<option value = ''>".t("Tilaukset jaetaan osiin varastoittain")."</option>";
  $ulos .= "<option value = 'E' $sel[E]>".t("Tilauksia ei jaeta osiin")."</option>";
  $ulos .= "<option value = 'K' $sel[K]>".t("Tilaukset jaetaan osiin ker‰ysp‰iv‰n mukaan")."</option>";
  $ulos .= "<option value = 'V' $sel[V]>".t("Tilaukset jaetaan osiin tuotteen ker‰ysvyˆhykkeen mukaan")."</option>";
  $ulos .= "<option value = 'W' $sel[W]>".t("Tilaukset jaetaan osiin varaston hyllypaikan ker‰ysvyˆhykkeen mukaan")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "valmistuksen_etusivu") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array($trow[$i] => "SELECTED");

  $ulos .= "<option value = ''>".t("Valmistuksille ei tulosteta etusivua");
  $ulos .= "<option value = 'E' $sel[E]>".t("Valmistuksille tulostetaan etusivu");
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "jyvita_alennus") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel1 = "";
  $sel2 = "";

  if ($trow[$i] == 'T') {
    $sel1 = "selected";
  }
  if ($trow[$i] == 'P') {
    $sel2 = "selected";
  }
  else {
    $sel = "selected";
  }

  $ulos .= "<option value = '' $sel>".t("Alennus jyvitet‰‰n tilausriveille");
  $ulos .= "<option value = 'T' $sel1>".t("Alennus jyvitet‰‰n aina tuotteella");
  $ulos .= "<option value = 'P' $sel2>".t("Alennus jyvitet‰‰n Projektilla tuotteella");
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "salli_jyvitys_myynnissa") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $sel[$trow[$i]] = "selected";

  $ulos .= "<option value = ''>".t("Kassamyyj‰ saa jyvitt‰‰ myyntitilauksella");
  $ulos .= "<option value = 'K' $sel[K]>".t("Kaikki saa jyvitt‰‰ myyntitilauksella");
  $ulos .= "<option value = 'S' $sel[S]>".t("Kaikki saa jyvitt‰‰ sek‰ osajyvitt‰‰ myyntitilauksella");
  $ulos .= "<option value = 'V' $sel[V]>".t("Vain valitut k‰ytt‰j‰t saa jyvitt‰‰ myyntitilauksella");
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "salli_jyvitys_tarjouksella") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $sel[$trow[$i]] = "selected";

  $ulos .= "<option value = ''>".t("Tarjouksella ei sallita katteen jyvityst‰");
  $ulos .= "<option value = 'K' $sel[K]>".t("Kaikki saa jyvitt‰‰ katteen perusteella tarjouksella");
  $ulos .= "<option value = 'S' $sel[S]>".t("Kaikki saa jyvitt‰‰ sek‰ osajyvitt‰‰ katteen perusteella tarjouksella");
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "naytetaan_tilausvahvistusnappi") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $sel[$trow[$i]] = "selected";

  $ulos .= "<option value = ''>".t("Ei n‰ytet‰ 'N‰yt‰ tilausvahvistus' -painiketta myyntitilauksella");
  $ulos .= "<option value = 'K' $sel[K]>".t("N‰ytet‰‰n 'N‰yt‰ tilausvahvistus' -painike myyntitilauksella");
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "rivinumero_syotto") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel1 = "";
  $sel2 = "";

  if ($trow[$i] != '') {
    $sel1 = "selected";
  }
  else {
    $sel2 = "selected";
  }

  $ulos .= "<option value = '' $sel>".t("Rivinumeroita ei voi syˆtt‰‰ tilaukselle");
  $ulos .= "<option value = 'K' $sel1>".t("Rivinumeroita voi syˆtt‰‰ tilaukselle");
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "sad_lomake_tyyppi") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel1 = "";
  $sel2 = "";

  if ($trow[$i] == 'T') {
    $sel2 = "selected";
  }
  else {
    $sel1 = "selected";
  }

  $ulos .= "<option value = 'P' $sel1>".t("SAD-lomake pdf-muodossa");
  $ulos .= "<option value = 'T' $sel2>".t("SAD-lomake tekstimuodossa");
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "lahetteen_jarjestys" or
  $fieldname == "laskun_jarjestys" or
  $fieldname == "tilauksen_jarjestys" or
  $fieldname == "kerayslistan_jarjestys" or
  $fieldname == "valmistus_kerayslistan_jarjestys" or
  $fieldname == "laskun_jarjestys" or
  $fieldname == "ostotilauksen_jarjestys" or
  $fieldname == "tarjouksen_jarjestys" or
  $fieldname == "tyomaarayksen_jarjestys") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $sel[$trow[$i]] = "selected";

  $ulos .= "<option value = '0' $sel[0]>".t("Varastopaikkaj‰rjestys, tuoteperheet pidet‰‰n yhdess‰, erikoistuotteet loppuun");
  $ulos .= "<option value = '1' $sel[1]>".t("Varastopaikkaj‰rjestys, tuoteperheet pidet‰‰n yhdess‰");
  $ulos .= "<option value = '2' $sel[2]>".t("Varastopaikkaj‰rjestys, erikoistuotteet loppuun");
  $ulos .= "<option value = '3' $sel[3]>".t("Varastopaikkaj‰rjestys");
  $ulos .= "<option value = '4' $sel[4]>".t("Tuotenumeroj‰rjestys, tuoteperheet pidet‰‰n yhdess‰, erikoistuotteet loppuun");
  $ulos .= "<option value = '5' $sel[5]>".t("Tuotenumeroj‰rjestys, tuoteperheet pidet‰‰n yhdess‰");
  $ulos .= "<option value = '6' $sel[6]>".t("Tuotenumeroj‰rjestys, erikoistuotteet loppuun");
  $ulos .= "<option value = '7' $sel[7]>".t("Tuotenumeroj‰rjestys");
  $ulos .= "<option value = '8' $sel[8]>".t("Tilausj‰rjestys, tuoteperheet pidet‰‰n yhdess‰");
  $ulos .= "<option value = '9' $sel[9]>".t("Tilausj‰rjestys");
  $ulos .= "<option value = 'M' $sel[M]>".t("K‰ytt‰j‰n asettama j‰rjestys");

  if (  $fieldname == "valmistus_kerayslistan_jarjestys" or
    $fieldname == "kerayslistan_jarjestys") {
    $ulos .= "<option value = 'S' $sel[S]>".t("Rivit summattuina");
  }
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "lahetteen_jarjestys_suunta" or
  $fieldname == "laskun_jarjestys_suunta" or
  $fieldname == "tilauksen_jarjestys_suunta" or
  $fieldname == "kerayslistan_jarjestys_suunta" or
  $fieldname == "valmistus_kerayslistan_jarjestys_suunta" or
  $fieldname == "laskun_jarjestys_suunta" or
  $fieldname == "ostotilauksen_jarjestys_suunta" or
  $fieldname == "tarjouksen_jarjestys_suunta" or
  $fieldname == "tyomaarayksen_jarjestys_suunta") {


  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $sel[$trow[$i]] = "selected";

  $ulos .= "<option value = 'ASC'  $sel[ASC]>".t("Nouseva");
  $ulos .= "<option value = 'DESC' $sel[DESC]>".t("Laskeva");

  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "lahetteen_palvelutjatuottet" or
  $fieldname == "laskun_palvelutjatuottet" or
  $fieldname == "kerayslistan_palvelutjatuottet" or
  $fieldname == "valmistus_kerayslistan_palvelutjatuottet" or
  $fieldname == "tarjouksen_palvelutjatuottet" or
  $fieldname == "tyomaarayksen_palvelutjatuottet") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $sel[$trow[$i]] = "selected";

  $ulos .= "<option value = ''>".t("Yhdess‰");
  $ulos .= "<option value = 'E' $sel[E]>".t("Erikseen");

  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "valmistuksessa_kaytetaan_tilakoodeja") {

  $sel = array();
  $sel[$trow[$i]] = "selected";

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";
  $ulos .= "<option value=''>" .t("Ei"). "</option>";
  $ulos .= "<option value='K' $sel[K]>" .t("Kyll‰"). "</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "lahete_allekirjoitus") {

  $sela = $selb = $selc = $seld = $sele = "";

  if ($trow[$i] == 'K') {
    $selb = "selected";
  }
  elseif ($trow[$i] == 'A') {
    $selc = "selected";
  }
  elseif ($trow[$i] == 'E') {
    $seld = "selected";
  }
  elseif ($trow[$i] == 'S') {
    $sele = "selected";
  }
  else {
    $sela = "selected";
  }

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">
        <option value='' $sela>".t("Ei tulosteta allekirjoitussivua")."</option>
        <option value='A' $selc>".t("Tulostetaan allekirjoitus samalle sivulle vain kun toimitustapa on nouto")."</option>
        <option value='K' $selb>".t("Tulostetaan erillinen allekirjoitussivu vain kun toimitustapa on nouto")."</option>
        <option value='E' $seld>".t("Tulostetaan erillinen allekirjoitussivu kaikilla toimitustavoilla")."</option>
        <option value='S' $sele>".t("Tulostetaan allekirjoitussivu samalle sivulle kaikilla toimitustavoilla")."</option>
      </select></td>";

  $jatko = 0;
}

if ($fieldname == "lomakkeiden_allekirjoitus") {
  $sela = $selb = "";

  if ($trow[$i] == 'X') {
    $selb = "selected";
  }
  else {
    $sela = "selected";
  }

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">
        <option value='' $sela>".t("Ei n‰ytet‰ allekirjoitusta myyntilasku, ostotilaus, l‰hete tai tarjous tulosteella")."</option>
        <option value='X' $selb>".t("N‰ytet‰‰n allekirjoitus myyntilasku, ostotilaus, l‰hete ja tarjous tulosteella")."</option>
      </select></td>";

  $jatko = 0;
}

if ($fieldname == "lahete_tyyppi_tulostus") {

  $sela = $selb = "";

  if ($trow[$i] == 'K') {
    $selb = "selected";
  }
  else {
    $sela = "selected";
  }

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">
        <option value='' $sela>".t("L‰hetetyyppi‰ EI SAA vaihtaa tulostuksessa")."</option>
        <option value='K' $selb>".t("L‰hetetyyppi‰ SAA vaihtaa tulostuksessa")."</option>
      </select></td>";

  $jatko = 0;
}

if ($fieldname == "tilausvahvistus_tyyppi_tulostus") {

  $sela = $selb = "";

  if ($trow[$i] == 'K') {
    $selb = "selected";
  }
  else {
    $sela = "selected";
  }

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">
        <option value='' $sela>".t("Tilausvahvistustyyppi‰ EI SAA vaihtaa tulostuksessa")."</option>
        <option value='K' $selb>".t("Tilausvahvistustyyppi‰ SAA vaihtaa tulostuksessa")."</option>
      </select></td>";

  $jatko = 0;
}

if ($fieldname == "valmistusten_yhdistaminen") {

  $sela = "";
  $selb = "";

  if ($trow[$i] == 'P') {
    $selb = "selected";
  }
  else {
    $sela = "selected";
  }

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">
        <option value='' $sela>".t("Tuotteen yhdistet‰‰n normaalisti")."</option>
        <option value='P' $selb>".t("Myˆs tuoteperheet koitetaan yhdist‰‰")."</option>
      </select></td>";

  $jatko = 0;
}

if ($fieldname == "tarjouksen_voimaika") {

  $ulos = "<td><input type='text' name='$nimi' value = '$trow[$i]' size='5'> (P‰iv‰‰ luontiajasta)</td>";

  $jatko = 0;
}

if ($fieldname == "tilauksen_yhteyshenkilot") {

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "selected";

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">
        <option value=''>".t("Vain tilausyhteyshenkilˆ")."</option>
        <option value='P' $sel[P]>".t("Vain tilausyhteyshenkilˆ, pakollinen")."</option>
        <option value='K' $sel[K]>".t("Tekninen yhteyshenkilˆ, kaupallinen yhteyshenkilˆ ja projektip‰‰llikkˆ")."</option>
      </select></td>";


  $jatko = 0;
}

if ($fieldname == "tarjouksen_voi_versioida") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel1 = "";
  $sel2 = "";

  if ($trow[$i] == "") {
    $sel1 = "selected";
  }
  elseif ($trow[$i] == "Y") {
    $sel2 = "selected";
  }

  $ulos .= "<option value=''  $sel1>".t("Tarjoksesta ei voi tehd‰ versioita")."</option>";
  $ulos .= "<option value='Y' $sel2>".t("Tarjouksesta voi tehd‰ versioita")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "rekursiiviset_reseptit") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel1 = "";
  $sel2 = "";

  if ($trow[$i] == "Y") {
    $sel1 = "selected";
  }
  elseif ($trow[$i] == "X") {
    $sel2 = "selected";
  }

  $ulos .= "<option value=''>".t("Resepteiss‰ vain yksi taso")."</option>";
  $ulos .= "<option value='Y' $sel1>".t("Reseptit k‰sitell‰‰n rekursiivisesti")."</option>";
  $ulos .= "<option value='X' $sel2>".t("Reseptit k‰sitell‰‰n rekursiivisesti. Oletuksena lapsireseptej‰ ei avata.")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "rekursiiviset_tuoteperheet") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel1 = "";
  $sel2 = "";

  if ($trow[$i] == "") {
    $sel1 = "selected";
  }
  elseif ($trow[$i] == "Y") {
    $sel2 = "selected";
  }

  $ulos .= "<option value=''  $sel1>".t("Tuoteperheiss‰ vain yksi taso")."</option>";
  $ulos .= "<option value='Y' $sel2>".t("Tuoteperheet k‰sitell‰‰n rekursiivisesti")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "valmistustapa") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel1 = "";
  $sel2 = "";

  if ($trow[$i] == "") {
    $sel1 = "selected";
  }
  elseif ($trow[$i] == "Y") {
    $sel2 = "selected";
  }

  $ulos .= "<option value=''  $sel1>".t("Normaali valmistus")."</option>";
  $ulos .= "<option value='Y' $sel2>".t("Yksinkertaisempi valmistus")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "rahtikirjan_kopiomaara") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel1 = "";
  $sel2 = "";

  if ($trow[$i] == 3) {
    $sel1 = "selected";
  }
  elseif ($trow[$i] == 4) {
    $sel2 = "selected";
  }

  $ulos .= "<option value='3' $sel1>".t("Rahtikirjasta tulostetaan 3 kopiota.")."</option>";
  $ulos .= "<option value='4' $sel2>".t("Rahtikirjasta tulostetaan 4 kopiota.")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "oletus_laskukpl_toimitatilaus") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel1 = "";
  $sel2 = "";

  if ($trow[$i] == 1) {
    $sel1 = "selected";
  }
  elseif ($trow[$i] == 2) {
    $sel2 = "selected";
  }
  elseif ($trow[$i] == 3) {
    $sel3 = "selected";
  }
  elseif ($trow[$i] == 4) {
    $sel4 = "selected";
  }

  $ulos .= "<option value='1' $sel1>".t("Laskusta tulostetaan 1 kopio.")."</option>";
  $ulos .= "<option value='2' $sel2>".t("Laskusta tulostetaan 2 kopiota.")."</option>";
  $ulos .= "<option value='3' $sel3>".t("Laskusta tulostetaan 3 kopiota.")."</option>";
  $ulos .= "<option value='4' $sel4>".t("Laskusta tulostetaan 4 kopiota.")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "vientikiellon_ohitus") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel1 = ($trow[$i] == "K") ? "selected" : "";

  $ulos .= "<option value='' >".t("Vientikiellossa olevaa tuotetta ei voi lis‰t‰ myyntitilaukselle")."</option>";
  $ulos .= "<option value='K' $sel1>".t("Vientikiellossa olevan tuotteen voi hyv‰ksy‰ myyntitilaukselle")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "vienti_erittelyn_tulostus") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel1 = "";
  $sel2 = "";
  $sel3 = "";

  if ($trow[$i] == "") {
    $sel1 = "selected";
  }
  elseif ($trow[$i] == "I") {
    $sel2 = "selected";
  }
  elseif ($trow[$i] == "E") {
    $sel3 = "selected";
  }
  elseif ($trow[$i] == "T") {
    $sel4 = "selected";
  }
  elseif ($trow[$i] == "P") {
    $sel5 = "selected";
  }

  $ulos .= "<option value=''  $sel1>".t("Vientierittely tulostetaan")."</option>";
  $ulos .= "<option value='T' $sel4>".t("Vientierittely tulostetaan tullinimikkeell‰ ryhmiteltyn‰")."</option>";
  $ulos .= "<option value='I' $sel2>".t("Vientierittely tulostetaan ilman alkuper‰maatietoja")."</option>";
  $ulos .= "<option value='P' $sel5>".t("Vientierittely tulostetaan ilman alkuper‰maatietoja tullinimikkeell‰ ryhmiteltyn‰")."</option>";
  $ulos .= "<option value='E' $sel3>".t("Vientierittely‰ ei tulosteta")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "oletus_lahetekpl") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel0 = "";
  $sel1 = "";
  $sel2 = "";
  $sel3 = "";

  if ($trow[$i] == 0) {
    $sel0 = "selected";
  }
  elseif ($trow[$i] == 1) {
    $sel1 = "selected";
  }
  elseif ($trow[$i] == 2) {
    $sel2 = "selected";
  }
  elseif ($trow[$i] == 3) {
    $sel3 = "selected";
  }

  $ulos .= "<option value='0' $sel0>".t("L‰hetteest‰ tulostetaan 0 kopiota.")."</option>";
  $ulos .= "<option value='1' $sel1>".t("L‰hetteest‰ tulostetaan 1 kopio.")."</option>";
  $ulos .= "<option value='2' $sel2>".t("L‰hetteest‰ tulostetaan 2 kopiota.")."</option>";
  $ulos .= "<option value='3' $sel3>".t("L‰hetteest‰ tulostetaan 3 kopiota.")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "oletus_lahetekpl_siirtolista") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel0 = "";
  $sel1 = "";
  $sel2 = "";
  $sel3 = "";

  if ($trow[$i] == 0) {
    $sel0 = "selected";
  }
  elseif ($trow[$i] == 1) {
    $sel1 = "selected";
  }
  elseif ($trow[$i] == 2) {
    $sel2 = "selected";
  }
  elseif ($trow[$i] == 3) {
    $sel3 = "selected";
  }

  $ulos .= "<option value='0' $sel0>".t("Siirtolistan l‰hetteest‰ tulostetaan 0 kopiota.")."</option>";
  $ulos .= "<option value='1' $sel1>".t("Siirtolistan l‰hetteest‰ tulostetaan 1 kopio.")."</option>";
  $ulos .= "<option value='2' $sel2>".t("Siirtolistan l‰hetteest‰ tulostetaan 2 kopiota.")."</option>";
  $ulos .= "<option value='3' $sel3>".t("Siirtolistan l‰hetteest‰ tulostetaan 3 kopiota.")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "oletus_oslappkpl") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel1 = "";
  $sel2 = "";

  if ($trow[$i] == 0) {
    $sel0 = "selected";
  }
  elseif ($trow[$i] == 1) {
    $sel1 = "selected";
  }
  elseif ($trow[$i] == 2) {
    $sel2 = "selected";
  }
  elseif ($trow[$i] == 3) {
    $sel3 = "selected";
  }

  $ulos .= "<option value='0' $sel0>".t("Osoitelapusta tulostetaan 0 kopiota.")."</option>";
  $ulos .= "<option value='1' $sel1>".t("Osoitelapusta tulostetaan 1 kopio.")."</option>";
  $ulos .= "<option value='2' $sel2>".t("Osoitelapusta tulostetaan 2 kopiota.")."</option>";
  $ulos .= "<option value='3' $sel3>".t("Osoitelapusta tulostetaan 3 kopiota.")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "oletus_rahtikirja_lahetekpl") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel1 = "";
  $sel2 = "";

  if ($trow[$i] == 0) {
    $sel0 = "selected";
  }
  elseif ($trow[$i] == 1) {
    $sel1 = "selected";
  }
  elseif ($trow[$i] == 2) {
    $sel2 = "selected";
  }
  elseif ($trow[$i] == 3) {
    $sel3 = "selected";
  }

  $ulos .= "<option value='0' $sel0>".t("L‰hetteest‰ tulostetaan 0 kopiota.")."</option>";
  $ulos .= "<option value='1' $sel1>".t("L‰hetteest‰ tulostetaan 1 kopio.")."</option>";
  $ulos .= "<option value='2' $sel2>".t("L‰hetteest‰ tulostetaan 2 kopiota.")."</option>";
  $ulos .= "<option value='3' $sel3>".t("L‰hetteest‰ tulostetaan 3 kopiota.")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "oletus_rahtikirja_oslappkpl") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel1 = "";
  $sel2 = "";

  if ($trow[$i] == 0) {
    $sel0 = "selected";
  }
  elseif ($trow[$i] == 1) {
    $sel1 = "selected";
  }
  elseif ($trow[$i] == 2) {
    $sel2 = "selected";
  }
  elseif ($trow[$i] == 3) {
    $sel3 = "selected";
  }

  $ulos .= "<option value='0' $sel0>".t("Osoitelapusta tulostetaan 0 kopiota.")."</option>";
  $ulos .= "<option value='1' $sel1>".t("Osoitelapusta tulostetaan 1 kopio.")."</option>";
  $ulos .= "<option value='2' $sel2>".t("Osoitelapusta tulostetaan 2 kopiota.")."</option>";
  $ulos .= "<option value='3' $sel3>".t("Osoitelapusta tulostetaan 3 kopiota.")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "viitteen_kasinsyotto") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel1="";
  $sel2="";
  $sel3="";

  if ($trow[$i] == "") {
    $sel1 = "selected";
  }
  elseif ($trow[$i] == "X") {
    $sel2 = "selected";
  }
  else {
    $sel3 = "selected";
  }

  $ulos .= "<option value=''  $sel1>".t("Viitett‰ ei saa syˆtt‰‰ k‰sin")."</option>";
  $ulos .= "<option value='X' $sel2>".t("Viitteen saa syˆtt‰‰ k‰sin")."</option>";
  $ulos .= "<option value='Y' $sel3>".t("Viitteen saa syˆtt‰‰ k‰sin yll‰pitosopimuksille")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "automaattinen_tuotehaku") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel1="";
  $sel2="";

  if ($trow[$i] == "") {
    $sel1 = "selected";
  }
  else {
    $sel2 = "selected";
  }

  $ulos .= "<option value=''  $sel1>".t("Automaattinen tuotehaku ei k‰ytˆss‰")."</option>";
  $ulos .= "<option value='X' $sel2>".t("Automaattinen tuotehaku k‰ytˆss‰")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "tuotekysely") {

  $sel = $trow[$i] == "K" ? "selected" : "";

  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";
  $ulos .= "<option value=''>".t("Tuotekyselyss‰ haetaan kaikki tiedot oletuksena")."</option>";
  $ulos .= "<option value='K' {$sel}>".t("Tuotekyselyss‰ k‰ytt‰j‰ hakee tiedot moduulikohtaisesti")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "oslapp_rakir_logo") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel1="";
  $sel2="";

  if ($trow[$i] == "") {
    $sel1 = "selected";
  }
  else {
    $sel2 = "selected";
  }

  $ulos .= "<option value=''  $sel1>".t("Logo tulostetaan rahtikirjaan ja osoitelappuun")."</option>";
  $ulos .= "<option value='X' $sel2>".t("Logoa ei tulosteta rahtikirjaan eik‰ osoitelappuun")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "osoitelappu_lisatiedot") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel1="";
  $sel2="";

  if ($trow[$i] == "") {
    $sel1 = "selected";
  }
  else {
    $sel2 = "selected";
  }

  $ulos .= "<option value=''  $sel1>".t("Asiaakkaan tilausnumero ja tilausyhteyshenkilˆ tulostetaan osoitelapulle")."</option>";
  $ulos .= "<option value='X' $sel2>".t("Asiakkaan tilausnumeroa tai tilausyhteyshenkilˆ‰ EI tulosteta osoitelapulle")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "ytunnus_tarkistukset") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "SELECTED";

  $ulos .= "<option value = ''>".t("Tarkistetaan kotimaiset toimittajat ja asiakkaat")."</option>";
  $ulos .= "<option value = 'T' $sel[T]>".t("Tarkistetaan vain kotimaiset toimittajat")."</option>";
  $ulos .= "<option value = 'A' $sel[A]>".t("Tarkistetaan vain kotimaiset asiakkaat")."</option>";
  $ulos .= "<option value = 'E' $sel[E]>".t("Ei ytunnus tarkistuksia")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "rahti_ja_kasittelykulut_kasin") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "SELECTED";

  $ulos .= "<option value = ''>".t("Rahtikirjan syˆtˆss‰ ei syˆtet‰ rahti ja k‰sittelykuluja")."</option>";
  $ulos .= "<option value = 'K' $sel[K]>".t("Rahtikirjan syˆtˆss‰ voi syˆtt‰‰ rahti ja k‰sittelykuluja")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "myyntitilaus_osatoimitus") {

  $sel = array($trow[$i] => 'selected') + array('K' => '', 'T' => '', 'E' => '');

  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";
  $ulos .= "<option value = ''>".t("Myyntitilauksesta ei voi tehd‰ osatoimitusta k‰sin")."</option>";
  $ulos .= "<option value = 'K' {$sel['K']}>".t("Myyntitilauksesta voi tehd‰ osatoimituksia k‰sin")."</option>";
  $ulos .= "<option value = 'T' {$sel['T']}>".t("Myyntitilauksesta voi tehd‰ osatoimituksia k‰sin, jos tilaus odottaa toimittamista")."</option>";
  $ulos .= "<option value = 'E' {$sel['E']}>".t("Tilauksia ei osatoimiteta")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "myyntitilaus_ytunnus_syotto") {

  $sel = $trow[$i] == 'K' ? 'selected' : '';

  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";
  $ulos .= "<option value = ''>".t("Myyntitilauksella ei voi syˆtt‰‰ ytunnusta k‰sin")."</option>";
  $ulos .= "<option value = 'K' {$sel}>".t("Myyntitilauksella voi syˆtt‰‰ ytunnuksen k‰sin")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "varaako_jt_saldoa") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "SELECTED";

  $ulos .= "<option value = ''>".t("J‰lkitoimitusrivit eiv‰t varaa saldoa")."</option>";
  $ulos .= "<option value = 'K' $sel[K]>".t("J‰lkitoimitusrivit varaavat saldoa")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "varastopaikan_lippu") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "SELECTED";

  $ulos .= "<option value = ''>".t("Myynniss‰ ei n‰ytet‰ vaarastopaikan maan lippu")."</option>";
  $ulos .= "<option value = 'K' $sel[K]>".t("Myynniss‰ n‰ytet‰‰n vaarastopaikan maan lippu")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "myyntitilaus_asiakasmemo") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "SELECTED";

  $ulos .= "<option value = ''>".t("Myyntitilauksella ei n‰ytet‰ asiakasmemoa")."</option>";
  $ulos .= "<option value = 'K' $sel[K]>".t("Myyntitilauksella n‰ytet‰‰n asiakasmemo")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "myyntitilaus_saatavat") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "SELECTED";

  $ulos .= "<option value = ''>".t("Myyntitilauksella n‰ytet‰‰n saatavat per asiakas")."</option>";
  $ulos .= "<option value = 'Y' $sel[Y]>".t("Myyntitilauksella n‰ytet‰‰n saatavat per ytunnus")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "myyntitilaus_tarjoukseksi") {

  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = $trow[$i] == 'K' ? "selected" : "";

  $ulos .= "<option value = ''>".t("Myyntitilauksella ei n‰ytet‰ /Tee tilauksesta tarjous/-nappia")."</option>";
  $ulos .= "<option value = 'K' {$sel}>".t("Myyntitilauksella n‰ytet‰‰n /Tee tilauksesta tarjous/-nappi")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "myyntitilauksen_liitteet") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "SELECTED";

  $ulos .= "<option value = ''>".t("Myyntitilaukselle ei saa liitt‰‰ liitetiedostoja")."</option>";
  $ulos .= "<option value = 'K' $sel[K]>".t("Myyntitilaukselle saa liitt‰‰ liitetiedostoja")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "myyntitilauksen_toimipaikka") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "SELECTED";

  $ulos .= "<option value = ''>".t("K‰ytet‰‰n ensisijaisesti k‰ytt‰j‰n toimipaikkaa")."</option>";
  $ulos .= "<option value = 'A' $sel[A]>".t("K‰ytet‰‰n ensisijaisesti asiakkaan toimipaikkaa")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "logo" or $fieldname == "lasku_logo") {
  $tyyppi = 5;
}

if ($fieldname == "korvaavan_hinta_ylaraja") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $apu = $trow[$i];
  $sel = array();
  $sel[$apu] = "selected";

  $ulos .= "<option value = ''>".t("Korvaava tuote myyd‰‰n hinnasta riippumatta")."</option>";
  $ulos .= "<option value = '10' $sel[10]>".sprintf(t('Korvaava tuote myyd‰‰n jos se on korkeintaan %s kalliimpi'), "10%")."</option>";
  $ulos .= "<option value = '20' $sel[20]>".sprintf(t('Korvaava tuote myyd‰‰n jos se on korkeintaan %s kalliimpi'), "20%")."</option>";
  $ulos .= "<option value = '30' $sel[30]>".sprintf(t('Korvaava tuote myyd‰‰n jos se on korkeintaan %s kalliimpi'), "30%")."</option>";
  $ulos .= "<option value = '40' $sel[40]>".sprintf(t('Korvaava tuote myyd‰‰n jos se on korkeintaan %s kalliimpi'), "40%")."</option>";
  $ulos .= "<option value = '50' $sel[50]>".sprintf(t('Korvaava tuote myyd‰‰n jos se on korkeintaan %s kalliimpi'), "50%")."</option>";
  $ulos .= "<option value = '60' $sel[60]>".sprintf(t('Korvaava tuote myyd‰‰n jos se on korkeintaan %s kalliimpi'), "60%")."</option>";
  $ulos .= "<option value = '70' $sel[70]>".sprintf(t('Korvaava tuote myyd‰‰n jos se on korkeintaan %s kalliimpi'), "70%")."</option>";
  $ulos .= "<option value = '80' $sel[80]>".sprintf(t('Korvaava tuote myyd‰‰n jos se on korkeintaan %s kalliimpi'), "80%")."</option>";
  $ulos .= "<option value = '90' $sel[90]>".sprintf(t('Korvaava tuote myyd‰‰n jos se on korkeintaan %s kalliimpi'), "90%")."</option>";
  $ulos .= "<option value = '100' $sel[100]>".sprintf(t('Korvaava tuote myyd‰‰n jos se on korkeintaan %s kalliimpi'), "100%")."</option>";
  $ulos .= "<option value = 'MIN' $sel[MIN]>".sprintf(t('Korvaava tuote myyd‰‰n aina halvemman tuotteen hinnalla, mik‰li tuote on loppu'))."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "korvaavat_hyvaksynta") {
  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "SELECTED";

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";
  $ulos .= "<option value='' $sel>".t("Ei. Korvaava tuote lis‰t‰‰n myyntitilaukselle automaattisesti.")."</option>";
  $ulos .= "<option value='K' $sel[K]>".t("Kyll‰. Korvaava tuote lis‰t‰‰n myyntitilauksella vain jos myyj‰ hyv‰ksyy korvaavan tuotteen.")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "monikayttajakalenteri") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "SELECTED";

  $ulos .= "<option value = '' $sel>".t("Kyll‰")."</option>";
  $ulos .= "<option value = 'E' $sel[E]>".t("Ei")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "automaattinen_asiakasnumerointi") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "SELECTED";

  $ulos .= "<option value = ''>".t("Ei")."</option>";
  $ulos .= "<option value = 'K' $sel[K]>".t("Kyll‰")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "asiakasnumeroinnin_aloituskohta") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "SELECTED";

  $ulos = "<td><input type='text' name='$nimi' value = '$trow[$i]' size='11'></td>";

  $jatko = 0;
}

if ($fieldname == "asiakkaan_tarkenne") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "SELECTED";

  $ulos .= "<option value = '' $sel>".t("Ovttunnus ja Toimitusosoitteen Ovttunnus")."</option>";
  $ulos .= "<option value = 'A' $sel[A]>".t("Asiakasnro")."</option>";
  $ulos .= "<option value = 'B' $sel[B]>".t("Ytunnus")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "tuotteen_oletuspaikka") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "SELECTED";

  $ulos = "<td><input type='text' name='$nimi' value = '$trow[$i]' size='25'></td>";

  $jatko = 0;
}

if ($fieldname == "haejaselaa_konsernisaldot") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "SELECTED";

  $ulos .= "<option value = ''>".t("Konserniyhtiˆiden saldoja EI n‰ytet‰ Hae ja Selaa-ohjelmassa")."</option>";
  $ulos .= "<option value = 'K' $sel[K]>".t("Konserniyhtiˆiden saldot n‰ytet‰‰n Hae ja Selaa-ohjelmassa")."</option>";
  $ulos .= "<option value = 'S' $sel[S]>".t("Konserniyhtiˆiden saldot n‰ytet‰‰n Hae ja Selaa-ohjelmassa sek‰ tuotekyselyss‰")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "rahtivapaa_alarajasumma") {

  $ulos = "<td><input type='text' name='$nimi' id='$nimi' value='$trow[$i]' size='15'></td>";
  $jatko = 0;
}

if ($fieldname == "jt_rahti") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "selected";

  $ulos .= "<option value = 'A' $sel[A]>".t("Ennakko/J‰lkitoimitus on rahtivapaa, jos alkuper‰inen tilaus oli rahtivapaa")."</option>";
  $ulos .= "<option value = 'B' $sel[B]>".t("Ennakot/J‰lkitoimitukset ovat aina rahtivapaita")."</option>";
  $ulos .= "<option value = 'C' $sel[C]>".t("Ennakoihin/J‰lkitoimituksiin lis‰t‰‰n aina rahtikulu")."</option>";
  $ulos .= "<option value = 'D' $sel[D]>".t("Ennakot/J‰lkitoimitukset ovat rahtivapaita, jos tilauksen arvo on yli rahtivapausrajan")."</option>";
  $ulos .= "<option value = 'E' $sel[E]>".t("Ensimm‰isen‰ toimitetulle ennakko-/j‰lkitoimitustilaukselle lis‰t‰‰n rahtikulu koko tilauksesta")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "jt_rivien_kasittely") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "selected";

  $ulos .= "<option value = ''>".t("Salli kaikki toiminnot")."</option>";
  $ulos .= "<option value = 'E' $sel[E]>".t("Est‰ jt-rivien mit‰tˆiminen extranetiss‰")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "jt_toimitusaika_email_vahvistus") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "selected";

  $ulos .= "<option value = ''>".t("JT-rivien toimitusajan vahvistuss‰hkˆpostia ei l‰hetet‰")."</option>";
  $ulos .= "<option value = 'K' $sel[K]>".t("Ostotilauksen toimitusajan vahvistuksesta l‰hetet‰‰n asiakkaalle JT-rivien toimitusajan vahvistuss‰hkˆposti")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "vahvistusviesti_asiakkaalle") {
  $ulos = "<td><select name='$nimi' " . js_alasvetoMaxWidth($nimi, 400) . ">";

  $sel = $trow[$i] == 'Y' ? ' selected' : '';

  $ulos .= "<option value = ''>" .
    t("Ei l‰hetet‰ asiakkaalle vahvistusviesti‰");
  $ulos .= "<option value = 'Y' $sel>" .
    t("L‰hetet‰‰n asiakkaalle tekstiviesti ja s‰hkˆposti, kun j‰lkitoimitustuotteet ovat " .
    "valmiina noudettaviksi, tai kun tyˆm‰‰r‰ys on valmis");
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "jt_manual") {

  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = $trow[$i] == 'K' ? "selected" : "";

  $ulos .= "<option value = ''>".t("Ei k‰ytˆss‰")."</option>";
  $ulos .= "<option value = 'K' {$sel}>".t("Myyntitilaukselle voidaan lis‰t‰ j‰lkitoimitusrivi johon ei JT automatiikat koske")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "jt_asiakkaan_tilausnumero") {

  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = $trow[$i] == 'K' ? " selected" : "";

  $ulos .= "<option value = ''>".t("Asiakkaan tilausnumeroa ei huomioida kun tutkitaan pit‰‰kˆ perustaa uusi jt-tilaus.")."</option>";
  $ulos .= "<option value = 'K'{$sel}>".t("Asiakkaan tilausnumero huomioidaan kun tutkitaan pit‰‰kˆ perustaa uusi jt-tilaus.")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "jt_siirtolistojen_yhdistaminen") {

  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = $trow[$i] == 'K' ? " selected" : "";

  $ulos .= "<option value = ''>".t("Siirtolistat yhdistet‰‰n JT-rivien vapautuksessa, jos t‰m‰ on mahdollista")."</option>";
  $ulos .= "<option value = 'K'{$sel}>".t("Siirtolistoja ei yhdistet‰ JT-rivien vapautuksessa")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "jt_email_tilauksessa") {

  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = $trow[$i] == 'K' ? " selected" : "";

  $ulos .= "<option value = ''>".t("L‰hetet‰‰n j‰lkitoimitus-s‰hkˆposti aina")."</option>";
  $ulos .= "<option value = 'K'{$sel}>".t("L‰hetet‰‰n j‰lkitoimitus-s‰hkˆposti vain jos j‰lkitoimitukseen j‰tetty‰ tuotetta ei ole riitt‰v‰sti ostotilauksilla")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "tilausvahvistus_tallenna") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "selected";

  $ulos .= "<option value = ''>".t("Tilausvahvistusta ei tallenneta myyntitilauksen liitetiedostoksi")."</option>";
  $ulos .= "<option value = 'K' $sel[K]>".t("Tilausvahvistus tallennetaan myyntitilauksen liitetiedostoksi")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "laskutuslisa" or
  $fieldname == "kuljetusvakuutus") {

  if ($trow[$i] == 0) {
    $trow[$i] = "";
  }
}

if ($fieldname == "laskutuslisa_tyyppi") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "selected";

  /*1) lis‰kulu kaikkiin laskuihin 2) kaikki paitsi k‰teinen 3) kaikki paitsi nouto.*/
  $ulos .= "<option value = ''>".t("Ei laskutuslis‰‰")."</option>";
  $ulos .= "<option value = 'A' $sel[A]>".t("Laskutuslis‰ lis‰t‰‰n kaikkiin laskuihin")."</option>";
  $ulos .= "<option value = 'B' $sel[B]>".t("Laskutuslis‰ lis‰t‰‰n kaikkiin paitsi k‰teislaskuihin")."</option>";
  $ulos .= "<option value = 'C' $sel[C]>".t("Laskutuslis‰ lis‰t‰‰n kaikkiin paitsi noutolaskuihin")."</option>";
  $ulos .= "<option value = 'L' $sel[L]>".t("Prosentuaalinen laskutuslis‰ lis‰t‰‰n kaikkiin laskuihin")."</option>";
  $ulos .= "<option value = 'K' $sel[K]>".t("Prosentuaalinen laskutuslis‰ lis‰t‰‰n kaikkiin paitsi k‰teislaskuihin")."</option>";
  $ulos .= "<option value = 'N' $sel[N]>".t("Prosentuaalinen laskutuslis‰ lis‰t‰‰n kaikkiin paitsi noutolaskuihin")."</option>";
  $ulos .= "<option value = 'T' $sel[T]>".t("Laskutuslis‰ lis‰t‰‰n kaikkiin laskuihin. K‰ytet‰‰n laskutuslis‰tuotteen asiakashintaa ja alennusta.")."</option>";
  $ulos .= "<option value = 'U' $sel[U]>".t("Laskutuslis‰ lis‰t‰‰n muihin laskuihin paitsi k‰teislaskuihin. K‰ytet‰‰n laskutuslis‰tuotteen asiakashintaa ja alennusta.")."</option>";
  $ulos .= "<option value = 'V' $sel[V]>".t("Laskutuslis‰ lis‰t‰‰n muihin laskuihin paitsi noutolaskuihin. K‰ytet‰‰n laskutuslis‰tuotteen asiakashintaa ja alennusta.")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "kuljetusvakuutus_tyyppi") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "selected";

  $ulos .= "<option value = ''>".t("Ei kuljetusvakuutusta")."</option>";
  $ulos .= "<option value = 'A' $sel[A]>".t("Valuuttam‰‰r‰inen kuljetusvakuutus lis‰t‰‰n")."</option>";
  $ulos .= "<option value = 'B' $sel[B]>".t("Prosentuaalinen kuljetusvakuutus lis‰t‰‰n")."</option>";
  $ulos .= "<option value = 'F' $sel[F]>".t("Valuuttam‰‰r‰inen kuljetusvakuutus lis‰t‰‰n. K‰ytet‰‰n kuljetusvakuutustuotteen asiakashintaa ja alennusta")."</option>";
  $ulos .= "<option value = 'G' $sel[G]>".t("Prosentuaalinen kuljetusvakuutus lis‰t‰‰n. K‰ytet‰‰n kuljetusvakuutustuotteen asiakasalennusta")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "kuljetusvakuutus_koonti") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = $trow[$i] == "L" ? " selected" : "";

  $ulos .= "<option value = ''>".t("Kuljetusvakuutus muodostuu per tilaus")."</option>";
  $ulos .= "<option value = 'L' {$sel}>".t("Kuljetusvakuutus muodostuu per lasku")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "viikkosuunnitelma") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "selected";

  $ulos .= "<option value = ''>".t("Viikkosuunnitelma k‰ytˆss‰")."</option>";
  $ulos .= "<option value = 'E' $sel[E]>".t("Ei k‰ytet‰ viikkosuunnitelmaa")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "kalenterimerkinnat") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "selected";

  $ulos .= "<option value = ''>".t("Kalenterimerkintˆj‰ lis‰t‰‰n automaattisesti")."</option>";
  $ulos .= "<option value = 'E' $sel[E]>".t("Kalenterimerkintˆj‰ EI lis‰t‰ automaattisesti")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "tuntikirjausten_erittely") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "selected";

  $ulos .= "<option value = ''>".t("Tuntikirjauksia ei eritell‰")."</option>";
  $ulos .= "<option value = 'E' $sel[E]>".t("Tuntikirjaukset eritell‰‰n")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "pakkaamolokerot") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "selected";

  $ulos .= "<option value = ''>".t("Ei k‰ytet‰ pakkaamojen lokeroita")."</option>";
  $ulos .= "<option value = 'K' $sel[K]>".t("K‰ytet‰‰n pakkaamojen lokeroita. Tilaukselle jaetaan lokero per pakkaamo")."</option>";
  $ulos .= "<option value = 'L' $sel[L]>".t("K‰ytet‰‰n pakkaamojen lokeroita. Tilaukselle jaetaan vain yksi lokero")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "huomautetaanko_poistuvasta") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "selected";

  $ulos .= "<option value = ''>".t("Ei")."</option>";
  $ulos .= "<option value = 'K' $sel[K]>".t("Kyll‰")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "myytitilauksen_kululaskut") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "selected";

  $ulos .= "<option value = ''>".t("Myyntitilauksiin ei voida liitt‰‰ kululaskuja")."</option>";
  $ulos .= "<option value = 'K' $sel[K]>".t("Myyntitilauksiin voi liitt‰‰ kululaskuja")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "palvelutuotteiden_kehahinnat") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "selected";

  $ulos .= "<option value = ''>".t("Palvelutuotteen hankintahintaa ei voi syˆtt‰‰ k‰sin")."</option>";
  $ulos .= "<option value = 'K' $sel[K]>".t("Palvelutuotteen hankintahinta voidaan syˆtt‰‰ k‰sin")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "rinnakkaisostaja_myynnissa") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "selected";

  $ulos .= "<option value = ''>".t("Myynniss‰ voi t‰ytt‰‰ rinnakkaisostajan tiedot")."</option>";
  $ulos .= "<option value = 'K' $sel[K]>".t("Myynniss‰ ei voi t‰ytt‰‰ rinnakkaisostajan tietoja")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "tyomaaraystiedot_tarjouksella") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "selected";

  $ulos .= "<option value = ''>".t("Tarjoukselle voi lis‰t‰ tyˆm‰‰r‰ystietoja")."</option>";
  $ulos .= "<option value = 'K' $sel[K]>".t("Tarjoukselle ei voi lis‰t‰ tyˆm‰‰r‰ystietoja")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "tilausrivien_toimitettuaika") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "selected";

  $ulos .= "<option value = ''>".t("Tuotteiden toimitettuaika tulostetaan laskulle normaalisti")."</option>";
  $ulos .= "<option value = 'K' $sel[K]>".t("Saldottomien tuotteiden toimitettuajaksi tulostetaan laskulle rivin toimitusaika")."</option>";
  $ulos .= "<option value = 'X' $sel[X]>".t("Kaikkien tuotteiden toimitettuajaksi tulostetaan laskulle rivin toimitusaika")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "tilausrivin_esisyotto") {

  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = $trow[$i] == 'K' ? 'selected' : '';

  $ulos .= "<option value = ''>".t("Myyntitilauksella / tarjouksella ei esisyˆtet‰ hintaa, alennuksia ja nettoa kun syˆtet‰‰n tilausriville tuotenumero ja kpl")."</option>";
  $ulos .= "<option value = 'K' {$sel}>".t("Myyntitilauksella / tarjouksella esisyˆtet‰‰n hinta, alennukset ja netto kun syˆtet‰‰n tilausriville tuotenumero ja kpl")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "kehahinarvio_ennen_ensituloa") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "selected";

  $ulos .= "<option value = ''>".t("Tuotteille ei voi syˆtt‰‰ keskihankintahinta-arviota ennen ensimm‰ist‰ tuloa")."</option>";
  $ulos .= "<option value = 'K' $sel[K]>".t("Tuotteille voidaan syˆtt‰‰ keskihankintahinta-arvio ennen ensimm‰ist‰ tuloa")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "konsernivarasto") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "selected";

  $ulos .= "<option value = ''>".t("Ei k‰ytˆss‰")."</option>";
  $ulos .= "<option value = 'K' $sel[K]>".t("K‰ytˆss‰")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "livetuotehaku_tilauksella") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "selected";

  $ulos .= "<option value = ''>".t("Ei k‰ytˆss‰")."</option>";
  $ulos .= "<option value = 'K' $sel[K]>".t("K‰ytˆss‰")."</option>";
  $ulos .= "<option value = 'J' $sel[J]>".t("K‰ytˆss‰ extranetiss‰ ja normimyynniss‰")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "livetuotehaku_tilauksella_minimi") {

  $sel = $trow[$i] == 2 ? 'selected' : '';

  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";
  $ulos .= "<option value = '3'>".t("V‰hint‰‰n 3 merkki‰")."</option>";
  $ulos .= "<option value = '2' {$sel}>".t("V‰hint‰‰n 2 merkki‰")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "kerayslista_kerayspaikka") {

  $sel = $trow[$i] == 'K' ? 'selected' : '';

  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";
  $ulos .= "<option value = ''>".t("N‰ytet‰‰n ker‰yslistan rivill‰ yksi paikka")."</option>";
  $ulos .= "<option value = 'K' {$sel}>".t("N‰ytet‰‰n ker‰yslistan rivill‰ saman varaston toinen paikka jolla on eniten saldoa")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "livetuotehaku_poistetut") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "selected";

  $ulos .= "<option value = ''>".t("Poistuvia/poistettuja tuotteita ei n‰ytet‰")."</option>";
  $ulos .= "<option value = 'X' $sel[X]>".t("Poistuvat tuotteet n‰ytet‰‰n")."</option>";
  $ulos .= "<option value = 'P' $sel[P]>".t("Kaikki tuotteet n‰ytet‰‰n")."</option>";
  $ulos .= "<option value = 'Y' $sel[Y]>".t("Poistuvat tuotteet n‰ytet‰‰n sek‰ ep‰kurantit korostetaan")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "poistetut_lisays") {
  $ulos = "<td><select name='$nimi' " . js_alasvetoMaxWidth($nimi, 400) . ">";

  $sel       = array();
  $apu       = $trow[$i];
  $sel[$apu] = "selected";

  $ulos .= "<option value = ''>" . t("Ei anneta lis‰t‰ poistettuja tuotteita ostotilauksella, eik‰ extranetiss‰") . "</option>";
  $ulos .= "<option value = 'P' $sel[P]>" . t("Annetaan lis‰t‰ poistettuja tuotteita ostotilauksella ja extranetiss‰, jolloin poistetut voi korvata korvaavilla tuotteilla") . "</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "livetuotehaku_hakutapa") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array_fill_keys(explode(",", $trow[$i]), " selected") + array('E' => '', 'F' => '', 'G' => '', 'H' => '', 'I' => '');

  $ulos .= "<option value = ''>".t("Livetuotehaku hakee tuotenumeron ja nimityksen mill‰ tahansa osalla. (Hidas tapa)")."</option>";
  $ulos .= "<option value = 'E' {$sel['E']}>".t("Livetuotehaku hakee tuotenumeron ja nimityksen mill‰ tahansa osalla. Tehd‰‰n myˆs saldokysely. (Todella hidas tapa)")."</option>";
  $ulos .= "<option value = 'F' {$sel['F']}>".t("Livetuotehaku hakee vain tuotenumeron ja nimityksen alulla. (Nopea tapa)")."</option>";
  $ulos .= "<option value = 'G' {$sel['G']}>".t("Livetuotehaku hakee vain tuotenumeron ja nimityksen alulla. Tehd‰‰n myˆs saldokysely. (Todella hidas tapa)")."</option>";
  $ulos .= "<option value = 'H' {$sel['H']}>".t("Livetuotehaku hakee tuotenumeron, nimityksen ja lempinimen mill‰ tahansa osalla. (Hidas tapa)")."</option>";
  $ulos .= "<option value = 'I' {$sel['I']}>".t("Livetuotehaku hakee tuotenumeron, nimityksen ja lempinimen mill‰ tahansa osalla. Tehd‰‰n myˆs saldokysely. (Todella hidas tapa)")."</option>";
  $ulos .= "<option value = 'J' {$sel['J']}>".t("Livetuotehaku hakee tuotenumeron, nimityksen ja EAN-koodin mill‰ tahansa osalla. (Hidas tapa)")."</option>";
  $ulos .= "<option value = 'K' {$sel['K']}>".t("Livetuotehaku hakee tuotenumeron, nimityksen ja EAN-koodin mill‰ tahansa osalla. Tehd‰‰n myˆs saldokysely. (Todella hidas tapa)")."</option>";
  $ulos .= "<option value = 'L' {$sel['L']}>".t("Livetuotehaku hakee tuotenumeron, nimityksen, lempinimen ja EAN-koodin mill‰ tahansa osalla. (Hidas tapa)")."</option>";
  $ulos .= "<option value = 'M' {$sel['M']}>".t("Livetuotehaku hakee tuotenumeron, nimityksen, lempinimen ja EAN-koodin mill‰ tahansa osalla. Tehd‰‰n myˆs saldokysely. (Todella hidas tapa)")."</option>";

  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "naytetaanko_osaston_ja_tryn_selite") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "selected";

  $ulos .= "<option value = ''>".t("Tuoteosaston/tuoteryhm‰n koodi ja nimi n‰ytet‰‰n")."</option>";
  $ulos .= "<option value = 'K' $sel[K]>".t("Tuoteosaston/tuoteryhm‰n nimi n‰ytet‰‰n")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "naytetaanko_ale_peruste_tilausrivilla") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "selected";

  $ulos .= "<option value = ''>".t("Aleperustetta ei n‰ytet‰ tilausrivill‰")."</option>";
  $ulos .= "<option value = 'o' $sel[o]>".t("Aleperuste n‰ytet‰‰n tilausrivill‰")."</option>";
  $ulos .= "<option value = 't' $sel[t]>".t("Aleperuste n‰ytet‰‰n tilausrivill‰ tiivistettyn‰")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "tilausvahvistus_jttoimituksista") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "selected";

  $ulos .= "<option value = ''>".t("JT-toimituksista ei l‰hetet‰ tilausvahvistusta")."</option>";
  $ulos .= "<option value = 'K' $sel[K]>".t("JT-toimituksista l‰hetet‰‰n tilausvahvistus")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "jt_rivien_saapumisajan_nayttaminen") {
  $ulos = "<td><select name='{$nimi}' " . js_alasvetoMaxWidth($nimi, 400) . ">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "selected";

  $ulos .= "<option value=''>" . t("Ei n‰ytet‰ jt-rivien saapumisaikoja") . "</option>";
  $ulos .= "<option value='K' {$sel["K"]}>" . t("N‰ytet‰‰n jt-rivien saapumisaika " .
    "tilausvahvistuksessa ja l‰hetteess‰, jos se on " .
    "tiedossa") . "</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "tilauksen_myyntieratiedot") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "selected";

  $ulos .= "<option value = ''>".t("Tilauksen myyntier‰ ja tuotetietoja ei n‰ytet‰")."</option>";
  $ulos .= "<option value = 'o' $sel[o]>".t("Tilauksen myyntier‰ ja tuotetiedot n‰ytet‰‰n tarjouksella / myyntitilauksella")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "iltasiivo_mitatoi_ext_tilauksia") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "selected";

  $ulos .= "<option value = ''>".t("Iltasiivo ei mit‰tˆi kesken olevia extranet-tilauksia")."</option>";
  $ulos .= "<option value = '12' $sel[12]>".t("Iltasiivo mit‰tˆi 12 tuntia vanhoja kesken olevia extranet-tilauksia")."</option>";
  $ulos .= "<option value = '24' $sel[24]>".t("Iltasiivo mit‰tˆi 1 p‰iv‰n vanhoja kesken olevia extranet-tilauksia")."</option>";
  $ulos .= "<option value = '48' $sel[48]>".t("Iltasiivo mit‰tˆi 2 p‰iv‰‰ vanhoja kesken olevia extranet-tilauksia")."</option>";
  $ulos .= "<option value = '168' $sel[168]>".t("Iltasiivo mit‰tˆi 7 p‰iv‰‰ vanhoja kesken olevia extranet-tilauksia")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "extranet_tilaus_varaa_saldoa") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "selected";

  $ulos .= "<option value = ''>".t("Extranet-tilaus varaa saldoa")."</option>";
  $ulos .= "<option value = 'E' $sel[E]>".t("Extranet-tilaus ei varaa saldoa")."</option>";
  $ulos .= "<option value = '1' $sel[1]>".t("Extranet-tilaus varaa saldoa 1 tunnin")."</option>";
  $ulos .= "<option value = '8' $sel[8]>".t("Extranet-tilaus varaa saldoa 8 tuntia")."</option>";
  $ulos .= "<option value = '24' $sel[24]>".t("Extranet-tilaus varaa saldoa 24 tuntia")."</option>";
  $ulos .= "<option value = '48' $sel[48]>".t("Extranet-tilaus varaa saldoa 48 tuntia")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "extranet_nayta_saldo") {
  $ulos = "<td><select name='$nimi' " . js_alasvetoMaxWidth($nimi, 400) . ">";

  $sel = $trow[$i] == 'Y' ? ' selected' : '';

  $ulos .= "<option value = ''>" . t("Ei n‰ytet‰ saldoa extranetiss‰");
  $ulos .= "<option value = 'Y' $sel>" . t("N‰ytet‰‰n saldo extranetiss‰");
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "extranet_poikkeava_toimitusosoite") {
  $ulos = "<td><select name='{$nimi}' " . js_alasvetoMaxWidth($nimi, 400) . ">";

  $sel = $trow[$i] == 'Y' ? ' selected' : '';

  $ulos .= "<option value = ''>" . t("Ei poikkeavaa toimitusosoitetta extranet-tilauksissa");
  $ulos .=
    "<option value = 'Y' {$sel}>" .
    t("Extranet-tilauksissa mahdollisuus syˆtt‰‰ poikkeava toimitusosoite");
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "extranet_keraysprioriteetti") {
  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = $trow[$i] == 'A' ? ' selected' : '';

  $ulos .= "<option value=''>";
  $ulos .= t("Extranet-tilaukset ker‰t‰‰n aina parhaalla prioriteetilla");
  $ulos .= "</option>";

  $ulos .= "<option value='A' {$sel}>";
  $ulos .= t("Extranet-tilaukset ker‰t‰‰n asiakkaan prioriteetilla");
  $ulos .= "</option>";

  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "extranet_private_label") {
  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = $trow[$i] == 'E' ? ' selected' : '';

  $ulos .= "<option value=''>".t("Oletus")."</option>";
  $ulos .= "<option value='E' {$sel}>".t("Private label -ominaisuus toimii vain extranetiss‰")."</option>";

  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "ext_tilauksen_hyvaksyja_myyjaksi") {
  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = $trow[$i] == 'X' ? ' selected' : '';

  $ulos .= "<option value = ''>".t("Hyv‰ksyj‰‰ ei tallenneta tilauksen myyj‰ksi")."</option>";
  $ulos .= "<option value = 'X' {$sel}>".t("Hyv‰ksyj‰ tallennetaan tilauksen myyj‰ksi")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "raaka_aine_tiliointi") {
  $ulos = "<td><select name='$nimi' " . js_alasvetoMaxWidth($nimi, 400) . ">";

  $sel = $trow[$i] == 'Y' ? ' selected' : '';

  $ulos .= "<option value = ''>" .
    t("Tiliˆid‰‰n raaka-aineiden varasto ja varastonmuutokset varasto ja " .
    "varastonmuutostileille");
  $ulos .= "<option value = 'Y' $sel>" .
    t("Tiliˆid‰‰n varasto ja varastonmuutos raaka-ainetileille, jos tuotetyyppi on " .
    "raaka-aine");
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "nayta_variaatiot") {
  $ulos = "<td><select name='$nimi' " . js_alasvetoMaxWidth($nimi, 400) . ">";

  $sel = $trow[$i] == 'Y' ? ' selected' : '';

  $ulos .= "<option value = ''>" . t("Ei n‰ytet‰ tuotteiden variaatioita tuotelistauksessa");
  $ulos .= "<option value = 'Y' $sel>" . t("Tuotteiden listauksessa Hae ja selaa-ohjelmassa " .
    "mahdollisuus n‰hd‰ tuotteen variaatiot (Esim. eri koot ja v‰rit)");
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "tuoteperhe_suoratoimitus") {
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "selected";

  $ulos .= "<option value = ''>".t("Tuoteperheen lapsituotteet voidaan suoratoimittaa erikseen")."</option>";
  $ulos .= "<option value = 'M' $sel[M]>".t("Tuoteperheen lapsituotteet suoratoimitetaan yhdess‰ ja on sallittu vain myyntitilauksella")."</option>";
  $ulos .= "<option value = 'E' $sel[E]>".t("Tuoteperheen lapsituotteet suoratoimitetaan yhdess‰ ja on sallittu myyntitilauksella ja extranetissa")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "tuoteperheinfo_lahetteella") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "selected";

  $ulos .= "<option value = ''>".t("Tuoteperheeseen sis‰ltyv‰t tuotteet listataan l‰hetteell‰ kaikkien tuoteperheeseen kuuluvien rivien kohdalla")."</option>";
  $ulos .= "<option value = 'E' $sel[E]>".t("Tuoteperheeseen sis‰ltyvi‰ tuotteita EI listata l‰hetteell‰ kaikkien tuoteperheeseen kuuluvien rivien kohdalla")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "tyomaarays_tulostus_lisarivit") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "selected";

  $ulos .= "<option value = ''>".t("Tyˆm‰‰r‰yksen tulosteeseen ei lis‰t‰ tyhji‰ kirjoitusrivej‰")."</option>";
  $ulos .= "<option value = 'L' $sel[L]>".t("Tyˆm‰‰r‰yksen tulosteeseen lis‰t‰‰n tyhji‰ kirjoitusrivej‰")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "tyomaarays_asennuskalenteri_muistutus") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "selected";

  $ulos .= "<option value = ''>".t("Tyˆm‰‰r‰yksen asennuskalenterissa ei voi lis‰t‰ muistutusta")."</option>";
  $ulos .= "<option value = 'K' $sel[K]>".t("Tyˆm‰‰r‰yksen asennuskalenterissa voi lis‰t‰ muistutuksen")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "saldovirhe_esto_laskutus") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "selected";

  $ulos .= "<option value = ''>".t("Ei estet‰ laskutusta")."</option>";
  $ulos .= "<option value = 'H' $sel[H]>".t("Mik‰li laskutuksessa tuotteen varastosaldo v‰henee negatiiviseksi, hyl‰t‰‰n KAIKKI tilaukset, joilla on kyseist‰ tuotetta")."</option>";
  $ulos .= "<option value = 'K' $sel[K]>".t("Sallitaan tilauksien laskutus luontij‰rjestyksess‰, kunnes tuotteen varastosaldo loppuu")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "kehahinvirhe_esto_laskutus") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "selected";

  $ulos .= "<option value = ''>".t("Ei estet‰ laskutusta")."</option>";
  $ulos .= "<option value = 'N' $sel[N]>".t("Estet‰‰n laskutus mik‰li keskihankintahinta on nolla tai rivin kate on negatiivinen")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "kirjanpidon_tarkenteet") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "selected";

  $ulos .= "<option value = ''>".t("Ei tarkenteita velka-/saatavat-tileille")."</option>";
  $ulos .= "<option value = 'K' $sel[K]>".t("Lis‰t‰‰n velka-/saatavat-tileille suurimman velan/saatavan tarkenteet")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "vaihda_asiakas_hintapaiv") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "selected";

  $ulos .= "<option value = ''>".t("Myyntitilauksen hinnat (paitsi nettohinnat) p‰ivitet‰‰n kun vaihdetaan tilauksen asiakas")."</option>";
  $ulos .= "<option value = 'A' $sel[A]>".t("Myyntitilauksen hinnat (paitsi nettohinnat) p‰ivitet‰‰n vain jos uudella asiakkaalla on asiakashinta/-alennus kun vaihdetaan tilauksen asiakas")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "tarjoustyyppi") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $apu = $trow[$i];
  $sel = array();
  $sel[$apu] = "selected";

  $ulos .= "<option value = '0' $sel[0]>".t("Normaali tarjouspohja");
  $ulos .= "<option value = '2' $sel[2]>".t("Normaali tarjouspohja ei ALV");
  $ulos .= "<option value = '1' $sel[1]>".t("Pelkistetty tarjouspohja");
  $ulos .= "<option value = '3' $sel[3]>".t("Lyhyt tarjouspohja");
  $ulos .= "<option value = '4' $sel[4]>".t("Tarjouspohja, jossa ainoastaan nettohinnat");
  $ulos .= "<option value = '5' $sel[5]>".t("Kuvallinen tarjouspohja");
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "hyvaksy_tarjous_tilaustyyppi") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $apu = $trow[$i];
  $sel = array();
  $sel[$apu] = "selected";

  $ulos .= "<option value = ''>".t("Hyv‰ksytyst‰ tarjouksesta luodaan oletuksena myyntitilaus");
  $ulos .= "<option value = 'T' $sel[T]>".t("Hyv‰ksytyst‰ tarjouksesta luodaan oletuksena tyˆm‰‰r‰ys");
  $ulos .= "<option value = 'P' $sel[P]>".t("Hyv‰ksytyst‰ tarjouksesta luodaan oletuksena projekti");
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "tuoteperhe_kasittely") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $apu = $trow[$i];
  $sel = array();
  $sel[$apu] = "selected";

  $ulos .= "<option value = ''>".t("Tuoteperheiden is‰-/lapsituote kombinaatiot ovat uniikkeja");
  $ulos .= "<option value = 'M' $sel[M]>".t("Tuoteperheisiin voi lis‰t‰ samaa tuotetta monesti");
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "keraysaikarajaus") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "selected";

  $ulos .= "<option value = '1'>".t("Ker‰yslistan tulostusjonossa n‰ytet‰‰n seuraavana p‰iv‰n‰ ker‰tt‰v‰t tilaukset")."</option>";
  $ulos .= "<option value = '3' $sel[3]>".t("Ker‰yslistan tulostusjonossa n‰ytet‰‰n seuraavan %s p‰iv‰n aikana ker‰tt‰v‰t tilaukset", "", "3")."</option>";
  $ulos .= "<option value = '5' $sel[5]>".t("Ker‰yslistan tulostusjonossa n‰ytet‰‰n seuraavan %s p‰iv‰n aikana ker‰tt‰v‰t tilaukset", "", "5")."</option>";
  $ulos .= "<option value = '7' $sel[7]>".t("Ker‰yslistan tulostusjonossa n‰ytet‰‰n seuraavan %s p‰iv‰n aikana ker‰tt‰v‰t tilaukset", "", "7")."</option>";
  $ulos .= "<option value = '14' $sel[14]>".t("Ker‰yslistan tulostusjonossa n‰ytet‰‰n seuraavan %s p‰iv‰n aikana ker‰tt‰v‰t tilaukset", "", "14")."</option>";
  $ulos .= "<option value = 'KK' $sel[KK]>".t("Ker‰yslistan tulostusjonossa n‰ytet‰‰n kaikki tilaukset")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "liitetiedostojen_nimeaminen") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $apu = $trow[$i];
  $sel[$apu] = "selected";

  $ulos .= "<option value = ''>".t("Liitetiedostojen nimiin ei lis‰t‰ asiakkaan nime‰")."</option>";
  $ulos .= "<option value = 'N' $sel[N]>".t("Liitetiedostojen nimiin lis‰t‰‰n asiakkaan nimi")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "ostoreskontra_kassaalekasittely") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $apu = $trow[$i];
  $sel = array();
  $sel[$apu] = "selected";

  $ulos .= "<option value = '0' $sel[0]>".t("Ostoreskontran maksatuksessa k‰ytet‰‰n automaattisesti kassa-alennus vain kassa-alennusp‰iv‰‰n menness‰");
  $ulos .= "<option value = '1' $sel[1]>".t("Ostoreskontran maksatuksessa k‰ytet‰‰n automaattisesti kassa-alennus, vaikka kassa-alennus on er‰‰ntynyt p‰iv‰ sitten");
  $ulos .= "<option value = '2' $sel[2]>".t("Ostoreskontran maksatuksessa k‰ytet‰‰n automaattisesti kassa-alennus, vaikka kassa-alennus on er‰‰ntynyt kaksi p‰iv‰‰ sitten");
  $ulos .= "<option value = '3' $sel[3]>".t("Ostoreskontran maksatuksessa k‰ytet‰‰n automaattisesti kassa-alennus, vaikka kassa-alennus on er‰‰ntynyt kolme p‰iv‰‰ sitten");
  $ulos .= "<option value = '4' $sel[4]>".t("Ostoreskontran maksatuksessa k‰ytet‰‰n automaattisesti kassa-alennus, vaikka kassa-alennus on er‰‰ntynyt nelj‰ p‰iv‰‰ sitten");
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "myyntilaskun_erapvmlaskenta") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = "";

  if ($trow[$i] != "") {
    $sel = "selected";
  }

  $ulos .= "<option value = ''>".t("Myyntilaskun er‰p‰iv‰ lasketaan aina laskun tapahtumap‰iv‰m‰‰r‰st‰");
  $ulos .= "<option value = 'K' $sel>".t("Myyntilaskun er‰p‰iv‰ voidaan laskea myˆs laskutushetkest‰ kun laskulle syˆtet‰‰n poikkeava tapahtumap‰iv‰m‰‰r‰");
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "varastoonvientipaiva") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $sel[$trow[$i]] = " selected";

  $ulos .= "<option value = ''>".t("Tavaran varastoonvientip‰iv‰ on saapumisen varastoonvientip‰iv‰");
  $ulos .= "<option value = 'K' {$sel['K']}>".t("Tavaran varastoonvientip‰iv‰ voidaan syˆtt‰‰ saapumisen varastoonvienniss‰");
  $ulos .= "<option value = 'R' {$sel['R']}>".t("Tavaran varastoonvientip‰iv‰ korjataan automaattisesti edelt‰m‰‰n tapahtumaa joka aiheutti negatiivisen saldon");
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "varastopaikkojen_maarittely") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $sel[$trow[$i]] = " selected";

  $ulos .= "<option value = ''>".t("Varastopaikkoja, ja niiden tilavuuksia, ei tarvitse m‰‰ritell‰ etuk‰teen")."</option>";
  $ulos .= "<option value = 'M'{$sel['M']}>".t("Varastopaikat, ja niiden tilavuudet, t‰ytyy m‰‰ritell‰ etuk‰teen")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "varastontunniste") {

  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array_fill_keys(array($trow[$i]), " selected") + array_fill_keys(array(1, 2, 3), '');

  $ulos .= "<option value = ''>".t("Varaston tunnisteet ei ole k‰ytˆss‰")."</option>";
  $ulos .= "<option value = '1'{$sel[1]}>".t("Varaston tunnisteena k‰ytet‰‰n hyllyalueen ensimm‰ist‰ merkki‰")."</option>";
  $ulos .= "<option value = '2'{$sel[2]}>".t("Varaston tunnisteena k‰ytet‰‰n hyllyalueen 2 ensimm‰ist‰ merkki‰")."</option>";
  $ulos .= "<option value = '3'{$sel[3]}>".t("Varaston tunnisteena k‰ytet‰‰n hyllyalueen 3 ensimm‰ist‰ merkki‰")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "pakollinen_varasto") {

  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = $trow[$i] == 'K' ? "selected" : "";

  $ulos .= "<option value = ''>".t("Varaston valinta ei pakollinen. Tilaukselle voidaan lis‰t‰ rivej‰ useasta varastosta")."</option>";
  $ulos .= "<option value = 'K'{$sel}>".t("Varaston valinta on pakollinen. Tilaukselle voidaan lis‰t‰ rivej‰ vain yhdest‰ varastosta: myyntitilauksella, ostotilauksella, tarjouksella ja reklamaatiolla")."</option>";

  $jatko = 0;
}

if ($fieldname == "ulkoinen_jarjestelma") {

  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array($trow[$i] => 'selected') + array('K' => '', 'S' => '');

  $ulos .= "<option value = ''>".t("Varaston ulkoinen j‰rjestelm‰ toimii ker‰yksen ja saapumisen yhteydess‰")."</option>";
  $ulos .= "<option value = 'K'{$sel['K']}>".t("Varaston ulkoinen j‰rjestelm‰ toimii vain ker‰yksen yhteydess‰")."</option>";
  $ulos .= "<option value = 'S'{$sel['S']}>".t("Varaston ulkoinen j‰rjestelm‰ toimii vain saapumisen yhteydess‰")."</option>";

  $jatko = 0;
}

if ($fieldname == "suuntalavat") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $sel[$trow[$i]] = " selected";

  $ulos .= "<option value = ''>".t("Suuntalavoja ei k‰ytet‰ tuloutuksessa")."</option>";
  $ulos .= "<option value = 'S'{$sel['S']}>".t("Suuntalavoja k‰ytet‰‰n tuloutuksessa")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "kerayserat") {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array_fill_keys(explode(",", $trow[$i]), " selected") + array('K' => '', 'P' => '', 'A' => '');

  $ulos .= "<option value = ''>".t("Ker‰yseri‰ ei k‰ytet‰ ker‰yksess‰")."</option>";
  $ulos .= "<option value = 'K'{$sel['K']}>".t("K‰ytet‰‰n mittatietoihin perustuvia ker‰yseri‰")."</option>";
  $ulos .= "<option value = 'P'{$sel['P']}>".t("K‰ytet‰‰n painoon perustuvia ker‰yseri‰")."</option>";
  $ulos .= "<option value = 'A'{$sel['A']}>".t("K‰ytet‰‰n painoon perustuvia ker‰yseri‰, vain jos se on asiakkaan takana sallittu")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "myyntiera_pyoristys" ) {

  $sela = $selb = "";

  $sel = array($trow[$i] => 'selected') + array('', 'K', 'S');

  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">
        <option value = ' '>".t("Myyntirivin m‰‰r‰‰ ei pyˆristet‰ seuraavaan t‰yteen myyntier‰‰n")."</option>
        <option value = 'K' {$sel['K']}>".t("Myyntirivin m‰‰r‰ pyˆristet‰‰n seuraavaan t‰yteen myyntier‰‰n")."</option>
        <option value = 'S' {$sel['S']}>".t("Myyntirivin ja siirtorivin m‰‰r‰ pyˆristet‰‰n seuraavaan t‰yteen myyntier‰‰n")."</option>
      </select></td>";

  $jatko = 0;
}

if ($fieldname == "ostoera_pyoristys" ) {

  $sela = $selb = "";

  if ($trow[$i] == 'K') {
    $selb = "selected";
  }
  else {
    $sela = "selected";
  }

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">
        <option value = ' ' $sela>".t("Ostorivin m‰‰r‰‰ ei pyˆristet‰ seuraavaan t‰yteen ostoer‰‰n")."</option>
        <option value = 'K' $selb>".t("Ostorivin m‰‰r‰ pyˆristet‰‰n seuraavaan t‰yteen ostoer‰‰n")."</option>
      </select></td>";

  $jatko = 0;
}

if ($fieldname == "reklamaation_kasittely") {

  $sel = array($trow[$i] => 'selected') + array('' => '', 'U' => '', 'X' => '');

  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">
        <option value = ''>".t("Reklamaation k‰sittelyss‰ k‰ytet‰‰n lyhyemp‰‰ prosessia")."</option>
        <option value = 'U' {$sel['U']}>".t("Reklamaation k‰sittelyss‰ k‰ytet‰‰n laajempaa prosessia vastaanotolla, purkulistalla ja purkukuittauksella")."</option>
        <option value = 'X' {$sel['X']}>".t("Reklamaation k‰sittelyss‰ k‰ytet‰‰n laajempaa prosessia purkulistalla ja purkukuittauksella. Takuu lyhyemm‰ll‰ prosessilla")."</option>
      </select></td>";

  $jatko = 0;
}

if ($fieldname == "reklamaation_kasittely_tuoteperhe") {

  $sela = $selb = "";

  if ($trow[$i] == 'U') {
    $selb = "selected";
  }
  else {
    $sela = "selected";
  }

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">
        <option value = '' $sela>".t("Reklamaatiolla k‰ytet‰‰n tuoteperheit‰")."</option>
        <option value = 'U' $selb>".t("Reklamaatiolla ei k‰ytet‰ tuoteperheit‰")."</option>
      </select></td>";

  $jatko = 0;
}

if ($fieldname == "reklamaation_hinnoittelu") {

  $sela = $selb = "";

  if ($trow[$i] == 'K') {
    $selb = "selected";
  }
  else {
    $sela = "selected";
  }

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">
        <option value = '' $sela>".t("Reklamaatiot hinnoitellaan manuaalisesti")."</option>
        <option value = 'K' $selb>".t("Reklamaation hinnoittelus‰‰nnˆt tulee tallentaa etuk‰teen")."</option>
      </select></td>";
  $jatko = 0;
}

if ($fieldname == "vak_kasittely") {

  $sel = "";

  if ($trow[$i] != '') {
    $sel = "selected";
  }

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">
        <option value = ''>".t("Normaali VAK-k‰sittely")."</option>
        <option value = 'P' $sel>".t("K‰ytet‰‰n VAK-tietokantaa ja DGD-lomaketta")."</option>
      </select></td>";

  $jatko = 0;
}

if ($fieldname == "vak_erittely") {

  $sel = "";

  if ($trow[$i] != '') {
    $sel = "selected";
  }

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">
        <option value = ''>".t("Ei tulosteta VAK/ADR-erittely‰ l‰hetteen kanssa")."</option>
        <option value = 'K' $sel>".t("Tulostetaan VAK/ADR-erittely l‰hetteen tulostuksen kanssa")."</option>
      </select></td>";

  $jatko = 0;
}

if ($fieldname == "nouto_suoraan_laskutukseen") {

  $sel = $trow[$i] == 'K' ? " selected" : "";

  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">
        <option value = ''>".t("Noutotilauksia ei laiteta suoraan laskutusjonoon")."</option>
        <option value = 'K' {$sel}>".t("Noutotilaukset aina suoraan laskutusjonoon")."</option>
      </select></td>";

  $jatko = 0;
}

if ($fieldname == "reklamaation_vastaanottovarasto" ) {
  $query = "SELECT tunnus, nimitys
            FROM varastopaikat
            WHERE yhtio = '$kukarow[yhtio]' AND tyyppi != 'P'
            ORDER BY tyyppi, nimitys";
  $sresult = pupe_query($query);

  $ulos = "<td><select name='$nimi'>";
  $ulos .= "<option value=' '>".t("Ei oletusvarastoa valittu")."</option>";

  while ($srow = mysql_fetch_array($sresult)) {
    $sel = '';
    if ($trow[$i] == $srow['tunnus']) {
      $sel = "selected";
    }
    $ulos .= "<option value='$srow[tunnus]' $sel>".$srow['nimitys']."</option>";
  }
  $ulos .= "</select></td>";
  $jatko = 0;
}

if ($fieldname == "myynnin_alekentat") {

  $apu = $trow[$i];
  $sel = array();
  $sel[$apu] = "selected";

  // Jos lis‰‰t uuden alennuskent‰n, lis‰‰ se myˆs myynnin_alekentat_muokkaus parametriin
  $ulos  = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";
  $ulos .= "<option value = '1'{$sel[1]}>".t("Myynniss‰ k‰ytet‰‰n yht‰ alennuskentt‰‰")."</option>";
  $ulos .= "<option value = '2'{$sel[2]}>".t("Myynniss‰ k‰ytet‰‰n kahta alennuskentt‰‰")."</option>";
  $ulos .= "<option value = '3'{$sel[3]}>".t("Myynniss‰ k‰ytet‰‰n kolmea alennuskentt‰‰")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "myynnin_alekentat_muokkaus") {
  $ulos = "<td>";

  $sel = array_fill_keys(explode(",", $trow[$i]), " checked") + array_fill(1, 3, '');

  $ulos .= "<input type='hidden' name='{$nimi}[]' value='default' />";

  $ulos .= "<input type='checkbox' name='{$nimi}[]' value='1'{$sel[1]}>".t("Ale1 muokkaus")."<br />";
  $ulos .= "<input type='checkbox' name='{$nimi}[]' value='2'{$sel[2]}>".t("Ale2 muokkaus")."<br />";
  $ulos .= "<input type='checkbox' name='{$nimi}[]' value='3'{$sel[3]}>".t("Ale3 muokkaus")."<br />";

  $ulos .= "</td>";
  $jatko = 0;
}

if ($fieldname == "oston_alekentat") {

  $apu = $trow[$i];
  $sel = array();
  $sel[$apu] = "selected";

  // Jos lis‰‰t uuden alennuskent‰n, lis‰‰ se myˆs myynnin_alekentat_muokkaus parametriin
  $ulos  = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";
  $ulos .= "<option value = '1'{$sel[1]}>".t("Ostossa k‰ytet‰‰n yht‰ alennuskentt‰‰")."</option>";
  $ulos .= "<option value = '2'{$sel[2]}>".t("Ostossa k‰ytet‰‰n kahta alennuskentt‰‰")."</option>";
  $ulos .= "<option value = '3'{$sel[3]}>".t("Ostossa k‰ytet‰‰n kolmea alennuskentt‰‰")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "intrastat_kaytossa") {

  $apu = $trow[$i];
  $sel = array($apu => "selected");

  $ulos  = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";
  $ulos .= "<option value = ''>".t("Viennin intrastattia k‰ytet‰‰n")."</option>";
  $ulos .= "<option value = 'V'{$sel['V']}>".t("Viennin intrastattia ei k‰ytet‰")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "intrastat_pvm") {

  $apu = $trow[$i];
  $sel = array($apu => "selected");

  $ulos  = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";
  $ulos .= "<option value = ''>".t("Tuonnin intrastat luodaan saapumisen virallisen varastonarvolaskentap‰iv‰m‰‰r‰n mukaan (HUOM: Tuotteet n‰ytet‰‰n Intrastatissa vasta kun saapuminen suljetaan!)")."</option>";
  $ulos .= "<option value = 'V'{$sel['V']}>".t("Tuonnin intrastat luodaan tavaran varastoonvientip‰iv‰m‰‰r‰n mukaan (HUOM: Intrastat muuttuu takautuvasti jos ostorivien hintoja muutetaan varastoonviennin j‰lkeen!)")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "myynti_asiakhin_tallenna") {

  $apu = $trow[$i];
  $sel = array($apu => "selected");

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">
        <option value = ''>".t("Myyntitilauksella ei voi tallentaa asiakashintoja")."</option>
        <option value = 'K' {$sel["K"]}>".t("Myyntitilauksen hinnat voi tallentaa asiakkaalle asiakashinnoiksi")."</option>
        <option value='V' {$sel["V"]}>" . t("Myyntitilauksen hinnat voi tallentaa asiakkaalle " .
    "asiakashinnoiksi valitulla tavalla") . "</option>
      </select></td>";

  $jatko = 0;
}

if ($fieldname == "haejaselaa_saapumispvm") {
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $apu = $trow[$i];
  $sel = array();
  $sel[$apu] = "selected";

  $ulos .= "<option value = ''>".t("N‰ytet‰‰n jos miss‰‰n varastossa ei ole saldoa");
  $ulos .= "<option value = 'A' $sel[A]>".t("Seuraava saapuminen n‰ytet‰‰n aina");
  $ulos .= "<option value = 'B' $sel[B]>".t("N‰ytet‰‰n jos normivarastossa ei saldoa");
  $ulos .= "</select></td>";

  $jatko = 0;
}

// t‰ll‰ ominaisuudella voidaan yhdest‰ tilausrivist‰ tehd‰ monta tilausta
// jos tilausrivill‰ on kappaleena 5, niin siint‰ tehd‰‰n 5 tilausta ja kussakin tilauksessa yksi kappale kyseist‰ tuotetta
if ($fieldname == "tilausrivi_omalle_tilaukselle") {
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = $trow[$i] == 'K' ? " selected" : '';

  $ulos .= "<option value = ''>".t("Myynniss‰ tilausrivi‰ ei voi pakottaa uudelle tilaukselle")."</option>";
  $ulos .= "<option value = 'K'{$sel}>".t("Myynniss‰ tilausrivin voi pakottaa uudelle tilaukselle")." (".t("tilaus per kpl").")</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "jalkilaskenta_kuluperuste") {
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $apu = $trow[$i];
  $sel = array();
  $sel[$apu] = "selected";

  $ulos .= "<option value = ''>".t("Virallisen varastonarvon laskentaan k‰ytet‰‰n saapumiseen liitettyjen huolinta-/rahtilaskujen summaa")."</option>";
  $ulos .= "<option value = 'KP' {$sel["KP"]}>".t("Virallisen varastonarvon laskentaan k‰ytet‰‰n saapumiselle syˆtetty‰ kulusummaa/kuluprosenttia")."</option>";
  $ulos .= "<option value = 'TP' {$sel["TP"]}>".t("Virallisen varastonarvon laskentaan k‰ytet‰‰n saapumiselle syˆtetty‰ kulusummaa/tuotteen kuluprosenttia/kuluprosenttia")."</option>";
  $ulos .= "<option value = 'PX' {$sel["PX"]}>".t("Virallisen varastonarvon laskentaan k‰ytet‰‰n kululaskujen summaa, mik‰li kululaskut on syˆtetty, muuten kulusummaa/kuluprosenttia")."</option>";
  $ulos .= "<option value = 'VS' {$sel["VS"]}>".t("Vaihto-omaisuuslaskua ei tarvitse liitt‰‰ saapumiselle, virallisen varastonarvon laskentaan k‰ytet‰‰n kulusummaa / kuluprosenttia.")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == 'varaston_splittaus') {
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = $trow[$i] == 'K' ? ' selected' : '';

  $ulos .= "<option value = ''>".t("Myyd‰‰n ensin oletuspaikka tyhj‰ksi ja k‰ytet‰‰n sen j‰lkeen muita tuotepaikkoja saldoj‰rjestyksess‰")."</option>";
  $ulos .= "<option value = 'K' {$sel}>".t("Yritet‰‰n myyd‰ kaikki saman varaston sis‰lt‰, ensisijaisesti oletuspaikan varastosta")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == 'oletusvarasto_tilaukselle') {
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = $trow[$i] == 'O' ? ' selected' : '';

  $ulos .= "<option value = ''>".t("Ei esivalita myyntitilaukselle k‰ytt‰j‰n oletusvarastoa")."</option>";
  $ulos .= "<option value = 'O' {$sel}>".t("Esivalitaan myyntitilaukselle k‰ytt‰j‰n oletusvarasto")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == 'siirtolistatyyppi') {
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = $trow[$i] == 'A' ? ' selected' : '';

  $ulos .= "<option value = ''>".t("Siirtolistalla ei n‰ytet‰ tuotteen myyntihintaa")."</option>";
  $ulos .= "<option value = 'A' {$sel}>".t("Siirtolistalla n‰ytet‰‰n tuotteen myyntihinta")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == 'tuotteiden_jarjestys_raportoinnissa') {
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = $trow[$i] == 'V' ? ' selected' : '';

  $ulos .= "<option value = ''>".t("Tuotenumeron mukaan");
  $ulos .= "<option value = 'V' $sel>".t("Variaation, v‰rin ja koon mukaan");
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == 'ennakkotilausten_toimitus') {
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = $trow[$i] == 'M' ? ' selected' : '';

  $ulos .= "<option value = ''>".t("Ennakkotilaukset toimitetaan Ennakko-selaus ohjelmalla");
  $ulos .= "<option value = 'M' $sel>".t("Ennakkotilaukset toimitetaan tilauskittain ja/tai Ennakko-selaus ohjelmalla");
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == 'ennakkolaskun_tyyppi') {
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array($trow[$i] => 'selected') + array('E' => '', 'K' => '');

  $ulos .= "<option value = ''>".t("Normaali ennakkolasku");
  $ulos .= "<option value = 'E' {$sel['E']}>".t("Ennakkolaskulle jokainen rivi erikseen");
  $ulos .= "<option value = 'K' {$sel['K']}>".t("Ennakkolaskulle jokainen rivi erikseen ja ennakkolasku-tekstit piilotetaan");
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == 'raaka_aineet_valmistusmyynti') {
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = $trow[$i] == 'N' ? ' selected' : '';

  $ulos .= "<option value = ''>".t("N‰ytet‰‰n raaka-aineet valmistusmyynniss‰ (asiakkaallevalmistus)");
  $ulos .= "<option value = 'N' $sel>".t("Ei n‰ytet‰ raaka-aineita valmistusmyynniss‰ (asiakkaallevalmistus)");
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == 'valmistuksien_kasittely') {
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = $trow[$i] == 'Y' ? ' selected' : '';

  $ulos .= "<option value = ''>".t("Valmistuksella voi olla useita valmisteita")."</option>";
  $ulos .= "<option value = 'Y' $sel>".t("Valmistuksella voi olla vain yksi valmiste")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == 'kehahinta_valmistuksella') {
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = $trow[$i] == 'K' ? ' selected' : '';

  $ulos .= "<option value = ''>".t("Valmistuksella ei n‰ytet‰ keskihankintahintoja")."</option>";
  $ulos .= "<option value = 'K' $sel>".t("Valmistuksella n‰ytet‰‰n keskihankintahinnat")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == 'saldo_varastossa_valmistuksella') {
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = $trow[$i] == 'S' ? ' selected' : '';

  $ulos .= "<option value = ''>".t("Valmistuksella ei n‰ytet‰ varastossa olevaa saldoa")."</option>";
  $ulos .= "<option value = 'S' $sel>".t("Valmistuksella n‰ytet‰‰n varastossa oleva saldo")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == 'tulosta_valmistus_tulosteet') {
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = $trow[$i] == 'V' ? ' selected' : '';

  $ulos .= "<option value = ''>".t("Tulostetaan Ker‰yslista ja Valmistuslista")."</option>";
  $ulos .= "<option value = 'V' $sel>".t("Tulostetaan vain Valmistuslista")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == 'tarkista_eankoodi') {
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = $trow[$i] == 'K' ? ' selected' : '';

  $ulos .= "<option value = ''>".t("Ei tehd‰ tuotteen EAN13-koodin oikeellisuustarkistusta")."</option>";
  $ulos .= "<option value = 'K' $sel>".t("Tarkistetaan tuotteen EAN13-koodin oikeellisuus")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == 'teeostotilaus_valmistuksen_tulosjonosta') {
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = $trow[$i] == 'K' ? ' selected' : '';

  $ulos .= "<option value = ''>".t("Valmistusten tulostusjonosta ei luoda ostotilauksia");
  $ulos .= "<option value = 'K' $sel>".t("Valmistusten tulostusjonosta voi luoda ostotilauksia");
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == 'maksukehotus_kentat') {
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $sel[$trow[$i]] = "selected";

  $ulos .= "<option value = ''>".t("N‰ytet‰‰n viimeisin muistutusp‰iv‰ ja perint‰kerrat")."</option>";
  $ulos .= "<option value = 'J' $sel[J]>".t("N‰ytet‰‰n viimeisin muistutusp‰iv‰ ja perint‰kerrat sek‰ korot ja lis‰t‰‰n ne maksukehotuksen summaan")."</option>";
  $ulos .= "<option value = 'K' $sel[K]>".t("Ei n‰ytet‰ viimeisint‰ muistutusp‰iv‰‰ eik‰ perint‰kertoja eik‰ korkoja")."</option>";
  $ulos .= "<option value = 'L' $sel[L]>".t("Ei n‰ytet‰ viimeisint‰ muistutusp‰iv‰‰ eik‰ perint‰kertoja. N‰ytet‰‰n korot ja lis‰t‰‰n ne maksukehotuksen summaan")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == 'viitemaksujen_kohdistus_sallittu_heitto') {
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $apu = $trow[$i]*100;
  $sel = array();
  $sel[$apu] = "selected";

  $ulos .= "<option value = '0.00' {$sel[0]}>0.00</option>";
  $ulos .= "<option value = '0.05' {$sel[5]}>0.05</option>";
  $ulos .= "<option value = '0.10' {$sel[10]}>0.10</option>";
  $ulos .= "<option value = '0.15' {$sel[15]}>0.15</option>";
  $ulos .= "<option value = '0.20' {$sel[20]}>0.20</option>";
  $ulos .= "<option value = '0.25' {$sel[25]}>0.25</option>";
  $ulos .= "<option value = '0.30' {$sel[30]}>0.30</option>";
  $ulos .= "<option value = '0.35' {$sel[35]}>0.35</option>";
  $ulos .= "<option value = '0.40' {$sel[40]}>0.40</option>";
  $ulos .= "<option value = '0.45' {$sel[45]}>0.45</option>";
  $ulos .= "<option value = '0.50' {$sel[50]}>0.50</option>";
  $ulos .= "<option value = '0.55' {$sel[55]}>0.55</option>";
  $ulos .= "<option value = '0.60' {$sel[60]}>0.60</option>";
  $ulos .= "<option value = '0.65' {$sel[65]}>0.65</option>";
  $ulos .= "<option value = '0.70' {$sel[70]}>0.70</option>";
  $ulos .= "<option value = '0.75' {$sel[75]}>0.75</option>";
  $ulos .= "<option value = '0.80' {$sel[80]}>0.80</option>";
  $ulos .= "<option value = '0.85' {$sel[85]}>0.85</option>";
  $ulos .= "<option value = '0.90' {$sel[90]}>0.90</option>";
  $ulos .= "<option value = '0.95' {$sel[95]}>0.95</option>";
  $ulos .= "<option value = '1.00' {$sel[100]}>1.00</option>";

  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == 'luottorajan_ylitys') {
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $apu = $trow[$i];
  $sel = array();
  $sel[$apu] = "selected";

  $ulos .= "<option value = ''>".t("Myynti‰ ei estet‰, vaikka luottoraja on ylitetty")."</option>";
  $ulos .= "<option value = 'K' {$sel["K"]}>".t("Kaikki myynti estet‰‰n, jos luottoraja on ylitetty. Tilaus j‰tet‰‰n odottaa suoritusta-tilaan")."</option>";
  $ulos .= "<option value = 'J' {$sel["J"]}>".t("Kaikki myynti estet‰‰n, jos luottoraja on ylitetty. Tilaus j‰tet‰‰n kesken")."</option>";
  $ulos .= "<option value = 'L' {$sel["L"]}>".t("Kaikki myynti estet‰‰n, jos luottoraja on ylitetty. Tilaukselle ei voi k‰yttˆliittym‰st‰ lis‰t‰ rivej‰. Tilaus j‰tet‰‰n kesken")."</option>";
  $ulos .= "<option value = 'M' {$sel["M"]}>".t("Kaikki myynti estet‰‰n, jos luottoraja on ylitetty. Tilaukselle ei voi k‰yttˆliittym‰st‰ lis‰t‰ rivej‰. Tilaus j‰tet‰‰n odottaa suoritusta-tilaan")."</option>";
  $ulos .= "<option value = 'E' {$sel["E"]}>".t("Muu kuin myyjien tekem‰ myynti estet‰‰n, jos luottoraja on ylitetty. Tilaus j‰tet‰‰n odottaa suoritusta-tilaan")."</option>";
  $ulos .= "<option value = 'F' {$sel["F"]}>".t("Muu kuin myyjien tekem‰ myynti estet‰‰n, jos luottoraja on ylitetty. Tilaus j‰tet‰‰n kesken")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == 'erapaivan_ylityksen_toimenpide') {

  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array_fill_keys(array('K', 'E', 'J', 'F', 'L', 'M'), '');
  $sel[$trow[$i]] = " SELECTED";

  $ulos .= "<option value = ''>".t("Myynti‰ ei estet‰, vaikka er‰p‰iv‰ olisi liikaa ylitetty")."</option>";
  $ulos .= "<option value = 'K' {$sel["K"]}>".t("Kaikki myynti estet‰‰n, jos er‰p‰iv‰ on liikaa ylitetty. Tilaus j‰tet‰‰n odottaa suoritusta-tilaan")."</option>";
  $ulos .= "<option value = 'J' {$sel["J"]}>".t("Kaikki myynti estet‰‰n, jos er‰p‰iv‰ on liikaa ylitetty. Tilaus j‰tet‰‰n kesken")."</option>";
  $ulos .= "<option value = 'L' {$sel["L"]}>".t("Kaikki myynti estet‰‰n, jos er‰p‰iv‰ on liikaa ylitetty. Tilaukselle ei voi k‰yttˆliittym‰st‰ lis‰t‰ rivej‰. Tilaus j‰tet‰‰n kesken")."</option>";
  $ulos .= "<option value = 'M' {$sel["M"]}>".t("Kaikki myynti estet‰‰n, jos er‰p‰iv‰ on liikaa ylitetty. Tilaukselle ei voi k‰yttˆliittym‰st‰ lis‰t‰ rivej‰. Tilaus j‰tet‰‰n odottaa suoritusta-tilaan")."</option>";
  $ulos .= "<option value = 'E' {$sel["E"]}>".t("Muu kuin myyjien tekem‰ myynti estet‰‰n, jos er‰p‰iv‰ on liikaa ylitetty. Tilaus j‰tet‰‰n odottaa suoritusta-tilaan")."</option>";
  $ulos .= "<option value = 'F' {$sel["F"]}>".t("Muu kuin myyjien tekem‰ myynti estet‰‰n, jos er‰p‰iv‰ on liikaa ylitetty. Tilaus j‰tet‰‰n kesken")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;

}

if ($fieldname == 'naytayhteensamaarat') {
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = $trow[$i] == 'K' ? ' selected' : '';

  $ulos .= "<option value = ''>".t("Ei n‰ytet‰ kokonaistuotem‰‰r‰‰ l‰hetteell‰, tilausvahvistuksella eik‰ laskulla")."</option>";
  $ulos .= "<option value = 'K' $sel>".t("N‰ytet‰‰n kokonaistuotem‰‰r‰ l‰hetteell‰, tilausvahvistuksella sek‰ laskulla")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == 'vastaanottoraportti') {
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = $trow[$i] == 'K' ? ' selected' : '';

  $ulos .= "<option value = ''>".t("Vastaanottoraportissa ei n‰ytet‰ keskihankintahintoja")."</option>";
  $ulos .= "<option value = 'K' $sel>".t("Vastaanottoraportissa n‰ytet‰‰n keskihankintahinnat")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "maventa_aikaleima") {
  $tyyppi = 3;
}

if ($fieldname == 'maksukehotuksen_osoitetiedot') {
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array_fill_keys(array('K', 'C'), '');
  $sel[$trow[$i]] = " SELECTED";

  $ulos .= "<option value = ''>".t("K‰ytet‰‰n asiakkaan virallista osoitetta")."</option>";
  $ulos .= "<option value = 'K' {$sel["K"]}>".t("K‰ytet‰‰n asiakkaan laskutusosoitetta")."</option>";
  $ulos .= "<option value = 'C' {$sel["C"]}>".t("K‰ytet‰‰n asiakkaan laskutusosoitetta ja n‰ytet‰‰n myˆs asiakkaan virallinen osoite")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == 'korvaavuusketjun_jarjestys') {
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = $trow[$i] == 'K' ? ' selected' : '';

  $ulos .= "<option value = ''>".t("Korvaavaksi tuotteeksi kelpaa korvaavuusketjun kaikki tuotteet")."</option>";
  $ulos .= "<option value = 'K' $sel>".t("Korvaaviksi tuotteeksi kelpaa korvaavuusketjussa j‰rjestykselt‰‰n pienemm‰t tuotteet")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == 'korvaavuusketjun_puutekasittely') {
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel1 = $trow[$i] == 'K' ? ' selected' : '';
  $sel2 = $trow[$i] == 'V' ? ' selected' : '';

  $ulos .= "<option value = ''>".t("Jos kaikki tuotteet on loppu, lis‰t‰‰n syˆtetty tuote puutteeksi/j‰lkitoimitukseen")."</option>";
  $ulos .= "<option value = 'K' $sel1>".t("Jos kaikki tuotteet on loppu, lis‰t‰‰n korvaavuusketjun ensimm‰inen tuote puutteeksi/j‰lkitoimitukseen")."</option>";
  $ulos .= "<option value = 'V' $sel2>".t("Jos kaikki tuotteet on loppu, lis‰t‰‰n vastaavuusketjun ensimm‰inen tuote puutteeksi/j‰lkitoimitukseen")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == 'vastaavuusketjun_jarjestys') {
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = $trow[$i] == 'K' ? ' selected' : '';

  $ulos .= "<option value = ''>".t("Vastaavaksi tuotteiksi kelpaa vastaavuusketjun kaikki tuotteet")."</option>";
  $ulos .= "<option value = 'K' $sel>".t("Vastaavaksi tuotteeksi kelpaa vastaavuusketjun j‰rjestykselt‰‰n suuremmat tuotteet")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == 'ostolaskun_kulutilit') {
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array_fill_keys(explode(",", $trow[$i]), " selected") + array_fill(1, 3, '');

  $ulos .= "<option value = ''>".t("Ei k‰ytet‰ vaihto-omaisuuslaskun kulutilej‰")."</option>";
  $ulos .= "<option value = '1' {$sel[1]}>".t("K‰ytet‰‰n vaihto-omaisuuslaskun rahdit tili‰")."</option>";
  $ulos .= "<option value = '2' {$sel[2]}>".t("K‰ytet‰‰n vaihto-omaisuuslaskun rahdit ja -kulut tilej‰")."</option>";
  $ulos .= "<option value = '3' {$sel[3]}>".t("K‰ytet‰‰n vaihto-omaisuuslaskun rahdit, -kulut ja -tuoterivikohtaiset kulut tilej‰")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == 'ostolaskun_kulutilit_kayttaytyminen') {
  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = $trow[$i] == 'E' ? "selected" : "";

  $ulos .= "<option value = ''>".t("Vaihto-omaisuuslaskun rahdit/kulut ei vied‰ varastonarvoon")."</option>";
  $ulos .= "<option value = 'E' {$sel}>".t("Vaihto-omaisuuslaskun rahdit/kulut vied‰‰n kirjanpidon varastonarvoon, mutta ei vied‰ tuotteiden varastonarvoon")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "tarkenteiden_tarkistus_hyvaksynnassa") {

  $ulos = "<td><select name='{$nimi}' " . js_alasvetoMaxWidth($nimi, 400) . ">";

  $sel = $trow[$i] == 'K' ? "selected" : "";

  $ulos .= "<option value = ''>" . t("Kirjanpitotilin pakollisia tarkenteita ei tarkasteta ostolaskua hyv‰ksytt‰ess‰") . "</option>";
  $ulos .= "<option value = 'K' {$sel}>" . t("Kirjanpitotilin pakolliset tarkenteet tarkistetaan kun ostolaskun viimeinen hyv‰ksyj‰ hyv‰ksyy laskun") . "</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == 'maksusopimus_toimitus') {
  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = $trow[$i] == "X" ? "selected" : "";

  $ulos .= "<option value = ''>".t("Tilauksen voi toimittaa vaikka ennakkolaskuja ei ole maksettu")."</option>";
  $ulos .= "<option value = 'X' {$sel}>".t("Tilauksen voi toimittaa vasta kun ennakkolaskut on maksettu")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == 'ennakkolasku_myyntitilaukselta') {
  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array_fill_keys(array('K', 'E', 'J', 'F', 'L', 'M'), '');
  $sel[$trow[$i]] = " SELECTED";

  $ulos .= "<option value = ''>".t("Myyntitilauksella ei voi tehd‰ ennakkolaskua pikan‰pp‰imell‰")."</option>";
  $ulos .= "<option value = 'A' {$sel['A']}>".t("Myyntitilauksella voi tehd‰ ennakkolaskun pikan‰pp‰imell‰")."</option>";
  $ulos .= "<option value = 'B' {$sel['B']}>".t("Myyntitilauksella voi tehd‰ ennakkolaskun ja sis‰isen ennakklaskun pikan‰pp‰imell‰")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == 'tositteen_tilioinnit') {
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = "";
  if ($trow[$i] != "") $sel = "SELECTED";

  $ulos .= "<option value = ''>".t("Tositteella n‰ytet‰‰n oletuksena kaikki tiliˆintirivit")."</option>";
  $ulos .= "<option value = 'X' {$sel}>".t("Tositteella n‰ytet‰‰n tiliˆintirivit summattuna per tili/pvm/tarkenne")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == 'saldotarkistus_tulostusjonossa') {
  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array_fill_keys(explode(",", $trow[$i]), " selected") + array('P' => '', 'JT' => '', 'PJ' => '');

  $ulos .= "<option value = ''>".t("Ei tehd‰ saldotarkistusta")."</option>";
  $ulos .= "<option value = 'P' {$sel['P']}>".t("Tarkistetaan tilauksen saldot ennen ker‰yslistan tulostusta, p‰ivitet‰‰n Puute rivit")."</option>";
  $ulos .= "<option value = 'JT' {$sel['JT']}>".t("Tarkistetaan tilauksen saldot ennen ker‰yslistan tulostusta, p‰ivitet‰‰n JT rivit")."</option>";
  $ulos .= "<option value = 'PJ' {$sel['PJ']}>".t("Tarkistetaan tilauksen saldot ennen ker‰yslistan tulostusta, p‰ivitet‰‰n Puute+JT rivit")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == 'ei_alennuksia_lapsituotteille') {
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = $trow[$i] == 'X' ? ' selected' : '';

  $ulos .= "<option value = ''>".t("Tuoteperheiden lapsituotteille lasketaan asiakashinta ja -alennus")."</option>";
  $ulos .= "<option value = 'X' $sel>".t("Tuoteperheiden lapsituotteille ei lasketa asiakashintoja eik‰ -alennuksia")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == 'epakurantoinnin_myyntihintaleikkuri') {
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $sel['Z'] = $trow[$i] == 'Z' ? 'selected' : '';

  $ulos .= "<option value = ''>".t("Hintaa ei leikata")."</option>";
  $ulos .= "<option value = 'Z' {$sel["Z"]}>".t("Myyntihintaa leikataan 25% per taso")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == 'seuraava_tuotenumero') {
  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $sel['K'] = $trow[$i] == 'K' ? 'selected' : '';
  $sel['O'] = $trow[$i] == 'O' ? 'selected' : '';

  $ulos .= "<option value = ''>".t("Ei ehdoteta seuraavaa vapaata tuotenumeroa tuotetta perustettaessa")."</option>";
  $ulos .= "<option value = 'K' {$sel['K']}>".t("Ehdotetaan seuraava vapaa tuotenumero tuotetta perustettaessa")."</option>";
  $ulos .= "<option value = 'O' {$sel['O']}>".t("Ehdotetaan seuraava vapaa tuotenumero tuotetta perustettaessa (vain ostotilauksella)")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == 'myyntitilausrivi_rekisterinumero') {
  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $sel['K'] = $trow[$i] == 'K' ? 'selected' : '';

  $ulos .= "<option value = ''>".t("Myyntitilauksen tilausriveill‰ ei n‰ytet‰ rekisterinumerovalintaa")."</option>";
  $ulos .= "<option value = 'K' {$sel['K']}>".t("Myyntitilauksen tilausriveill‰ n‰ytet‰‰n rekisterinumerovalinta")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == 'myyntihinta_paivitys_saapuminen') {

  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = $trow[$i] == 'K' ? 'selected' : '';

  $ulos .= "<option value = ''>".t("Myyntihintaa ei voi p‰ivitt‰‰ saapumisella")."</option>";
  $ulos .= "<option value = 'K' {$sel}>".t("Myyntihinnan voi p‰ivitt‰‰ saapumisella")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == 'suoratoim_lisamyynti_osto') {

  $ulos = "<td><select name='{$nimi}' " . js_alasvetoMaxWidth($nimi, 400) . ">";

  $sel = $trow[$i] == 'K' ? 'selected' : '';

  $ulos .= "<option value=''>" . t("Suoratoimitusmyyntirivin m‰‰r‰‰ ei voi lis‰t‰ ostopuolella") . "</option>";
  $ulos .= "<option value='K' {$sel}>" .
    t("Suoratoimitusmyyntirivin m‰‰r‰‰ voi lis‰t‰ ostopuolella") .
    "</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == 'matkalaskun_tarkastus') {
  $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $sel['Z'] = $trow[$i] == 'Z' ? 'selected' : '';

  $ulos .= "<option value = ''>".t("Matkalaskun tarkastaja ei voi muokata matkalaskua")."</option>";
  $ulos .= "<option value = 'Z' {$sel["Z"]}>".t("Matkalaskun tarkastaja voi muokata matkalaskua")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "ostotilauksen_kasittely") {

  $myynnista_osto_avainsanat = t_avainsana("MYYNNISTA_OSTO");

  if (mysql_num_rows($myynnista_osto_avainsanat) > 0) {

    $ulos  = "<td><select name='{$nimi}'>";
    $ulos .= "<option value=''>".t("Ei valintaa")."</option>";

    while ($myynnista_osto_avainsanat_row = mysql_fetch_assoc($myynnista_osto_avainsanat)) {

      $sel = $myynnista_osto_avainsanat_row['selite'] == $trow[$i] ? " selected" : "";

      $ulos .= "<option value='{$myynnista_osto_avainsanat_row['selite']}'{$sel}>{$myynnista_osto_avainsanat_row['selitetark']}</option>";
    }

    $ulos .= "</select></td>";

    $jatko = 0;
  }
  else {
    $tyyppi = 0;
  }
}

if ($fieldname == 'ostotilauksen_toimittajien_saldo_tarkistus') {
  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array();
  $sel['K'] = $trow[$i] == 'K' ? 'selected' : '';

  $ulos .= "<option value = ''>".t("Ostotilauksella ei tarkisteta toimittajien saldoja")."</option>";
  $ulos .= "<option value = 'K' {$sel['K']}>".t("Ostotilauksella tarkistetaan toimittajien saldoja")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == 'vastaavat_tuotteet_esitysmuoto') {
  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array_fill_keys(explode(",", $trow[$i]), " selected") + array('S' => '', 'A' => '');

  $ulos .= "<option value = ''>".t("Vastaavat tuotteet n‰ytet‰‰n normaalisti")."</option>";
  $ulos .= "<option value = 'S' {$sel['S']}>".t("Vastaavat tuotteet n‰ytet‰‰n vaikka saldo on nolla. N‰ytet‰‰n myˆs siirrett‰v‰ saldo.")."</option>";
  $ulos .= "<option value = 'A' {$sel['A']}>".t("Vastaavat tuotteet n‰ytet‰‰n aina, jos rivi on puute. N‰ytet‰‰n myˆs siirrett‰v‰ saldo.")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == 'paivita_oletuspaikka') {
  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array($trow[$i] => 'selected') + array('' => '', 'S' => '', 'M' => '');

  $ulos .= "<option value = ''>".t("P‰ivitet‰‰n uusi oletuspaikka avoimille ostoriveille")."</option>";
  $ulos .= "<option value = 'S' {$sel['S']}>".t("P‰ivitet‰‰n uusi oletuspaikka avoimille ja saapumisiin liitetyille ostoriveille.")."</option>";
  $ulos .= "<option value = 'M' {$sel['M']}>".t("P‰ivitet‰‰n uusi oletuspaikka avoimille ja saapumisiin liitetyille ostoriveille, myyntiriveille ja siirtoriveille.")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == 'tilaukselle_mittatiedot') {
  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = array($trow[$i] => 'selected') + array('' => '', 'M' => '', 'A' => '');

  $ulos .= "<option value = ''>".t("Myyntitilauksella, ostotilauksella sek‰ tarjouksella ei n‰ytet‰ mittatietojen yhteenvetoa")."</option>";
  $ulos .= "<option value = 'M' {$sel['M']}>".t("Myyntitilauksella, ostotilauksella sek‰ tarjouksella n‰ytet‰‰n mittatietojen yhteenveto")."</option>";
  $ulos .= "<option value = 'A' {$sel['A']}>".t("Myyntitilauksella, ostotilauksella sek‰ tarjouksella n‰ytet‰‰n mittatietojen yhteenveto sek‰ asiakasosasto")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == 'tarkenteiden_prioriteetti') {
  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = $trow[$i] == 'T' ? 'selected' : '';

  $ulos .= "<option value = ''>".t("Kirjanpidon tarkenteet haetaan j‰rjestyksell‰: Tuote, Asiakas, Toimipaikka, Yhtiˆ")."</option>";
  $ulos .= "<option value = 'T' {$sel}>".t("Kirjanpidon tarkenteet haetaan j‰rjestyksell‰: Toimipaikka, Tuote, Asiakas, Yhtiˆ")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == 'toimipaikkakasittely') {
  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = $trow[$i] == 'L' ? 'selected' : '';

  $ulos .= "<option value = ''>".t("Suppeampi toimipaikkak‰sittely. K‰ytt‰j‰n toimipaikka tallennetaan tilauksille.")."</option>";
  $ulos .= "<option value = 'L' {$sel}>".t("Laajempi toimipaikkak‰sittely. K‰ytt‰j‰n toimipaikka talennetaan tilauksille ja toimipaikka rajaa tilaus-/saapumislistauksia")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == 'suoratoimitusvarasto') {
  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $ulos .= "<option value = '0'>".t("Ei valittu")."</option>";

  $query  = "SELECT *
             FROM varastopaikat
             WHERE yhtio = '{$kukarow['yhtio']}' AND tyyppi != 'P'
             ORDER BY tyyppi, nimitys";
  $vares = pupe_query($query);

  while ($varow = mysql_fetch_assoc($vares)) {
    $sel = $trow[$i] == $varow['tunnus'] ? "selected" : "";
    $ulos .= "<option value = '{$varow['tunnus']}' {$sel}>{$varow['nimitys']}</option>";
  }

  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == 'yhdistetaan_identtiset_laskulla') {
  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = $trow[$i] == 'k' ? 'selected' : '';

  $ulos .= "<option value = ''>".t("Samalta tilaukselta lˆytyvi‰ jakaantuneita tilausrivej‰ ei yhdistet‰ laskulla")."</option>";
  $ulos .= "<option value = 'k' {$sel}>".t("Samalta tilaukselta lˆytyv‰t jakaantuneet tilausrivit yhdistet‰‰n laskulla")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == 'sallitaanko_kateismyynti_laskulle') {
  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = $trow[$i] == 'k' ? 'selected' : '';

  $ulos .= "<option value = ''>".t("Ei sallita osamaksu / rahoitus (laskulle) maksutapaa k‰teismyynniss‰")."</option>";
  $ulos .= "<option value = 'k' {$sel}>".t("Sallitaan osamaksu / rahoitus (laskulle) maksutapa k‰teismyynniss‰")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == 'lapsituotteen_poiston_esto') {
  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = $trow[$i] == '1' ? 'selected' : '';

  $ulos .= "<option value = ''>".t("Sallitaan tuoteperheen lapsituotteen poisto myyntitilauksella")."</option>";
  $ulos .= "<option value = '1' {$sel}>".t("Ei sallita tuoteperheen lapsituotteen poistoa myyntitilauksella")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == 'varastonarvon_jako_usealle_valmisteelle') {
  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = $trow[$i] == 'K' ? 'selected' : '';

  $ulos .= "<option value = ''>".t("Raaka-aineen arvo jaetaan valmisteille m‰‰rien ja valmisteiden keskihankintahinnan mukaan kun raaka-aine/aineet jaetaan useaksi valmisteeksi")."</option>";
  $ulos .= "<option value = 'K' {$sel}>".t("Raaka-aineen arvo voidaan manuaaliesti jakaa valmisteille kun raaka-aine/aineet jaetaan useaksi valmisteeksi")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == 'pura_osaluettelot') {
  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $sel = $trow[$i] == '1' ? 'selected' : '';

  $ulos .= "<option value = ''>".t("Tuotteen osaluetteloa ei pureta auki")."</option>";
  $ulos .= "<option value = '1' {$sel}>".t("Tuotteen osaluettelo puretaan auki tilausvahvistuksella, l‰hetteell‰ ja laskulla")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "laiterekisteri_kaytossa") {
  $apu = $trow[$i];
  $sel = array();
  $sel[$apu] = "selected";

  $ulos  = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";
  $ulos .= "<option value = ''>".t("Laiterekisteri ei ole k‰ytˆss‰");
  $ulos .= "<option value = 'K' $sel[K]>".t("Laiterekisteri on k‰ytˆss‰");
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "inventointi_yhteenveto") {
  $sel = $trow[$i] == "K" ? "selected" : "";

  $ulos  = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $ulos .= "<option value = ''>";
  $ulos .= t("Inventointilistaa inventoidessa ei n‰ytet‰ yhteenvetoa");
  $ulos .= "</option>";

  $ulos .= "<option value = 'K' {$sel}>";
  $ulos .= t("Inventointilistaa inventoidessa n‰ytet‰‰n yhteenveto");
  $ulos .= "</option>";

  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "laaja_inventointilista") {
  $sel = $trow[$i] == "K" ? "selected" : "";

  $ulos  = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $ulos .= "<option value = ''>";
  $ulos .= t("Inventointilistaa inventoidessa ei k‰ytet‰ laajaa inventointilistaa");
  $ulos .= "</option>";

  $ulos .= "<option value = 'K' {$sel}>";
  $ulos .= t("Inventointilistaa inventoidessa k‰ytet‰‰n laajaa inventointilistaa");
  $ulos .= "</option>";

  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "inventointi_siirron_yhteydessa") {
  $sel = $trow[$i] == "K" ? "selected" : "";

  $ulos  = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $ulos .= "<option value = ''>";
  $ulos .= t("Ei inventoida ");
  $ulos .= "</option>";

  $ulos .= "<option value = 'K' {$sel}>";
  $ulos .= t("Inventoidaan varastosiirron yhteydess‰ automaattisesti jos hylly ennest‰‰n tyhj‰");
  $ulos .= "</option>";

  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == 'takuuvarasto') {
  $ulos = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $ulos .= "<option value = '0'>".t("Ei valittu")."</option>";

  $query  = "SELECT *
             FROM varastopaikat
             WHERE yhtio = '{$kukarow['yhtio']}' AND tyyppi != 'P'
             ORDER BY tyyppi, nimitys";
  $vares = pupe_query($query);

  while ($varow = mysql_fetch_assoc($vares)) {
    $sel = $trow[$i] == $varow['tunnus'] ? "selected" : "";
    $ulos .= "<option value = '{$varow['tunnus']}' {$sel}>{$varow['nimitys']}</option>";
  }

  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "muokkaatilaus_pv_rajaus") {

  $_alkiot = array(
    '0' => '',
    '1' => '',
    '2' => '',
    '3' => '',
    '5' => '',
    '7' => '',
    '14' => '',
    '30' => '',
    '60' => '',
    '90' => '',
  );

  $_sel = array($trow[$i] => 'selected') + $_alkiot;

  $ulos  = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $ulos .= "<option value='0' {$_sel['0']}>".t("Ei rajausta")."</option>";
  $ulos .= "<option value='1' {$_sel['1']}>".t("%d p‰iv‰‰", "", 1)."</option>";
  $ulos .= "<option value='2' {$_sel['2']}>".t("%d p‰iv‰‰", "", 2)."</option>";
  $ulos .= "<option value='3' {$_sel['3']}>".t("%d p‰iv‰‰", "", 3)."</option>";
  $ulos .= "<option value='5' {$_sel['5']}>".t("%d p‰iv‰‰", "", 5)."</option>";
  $ulos .= "<option value='7' {$_sel['7']}>".t("%d p‰iv‰‰", "", 7)."</option>";
  $ulos .= "<option value='14' {$_sel['14']}>".t("%d p‰iv‰‰", "", 14)."</option>";
  $ulos .= "<option value='30' {$_sel['30']}>".t("%d p‰iv‰‰", "", 30)."</option>";
  $ulos .= "<option value='60' {$_sel['60']}>".t("%d p‰iv‰‰", "", 60)."</option>";
  $ulos .= "<option value='90' {$_sel['90']}>".t("%d p‰iv‰‰", "", 90)."</option>";

  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "tilausrivin_korvamerkinta") {
  $sel = $trow[$i] == "K" ? "selected" : "";

  $ulos  = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $ulos .= "<option value = ''>";
  $ulos .= t("Ei sallita tilausrivien korvamerkint‰‰");
  $ulos .= "</option>";

  $ulos .= "<option value = 'K' {$sel}>";
  $ulos .= t("Sallitaan tilausrivien korvamerkint‰ vapaalla tekstill‰");
  $ulos .= "</option>";

  $ulos .= "<option value = 'A' {$sel}>";
  $ulos .= t("Sallitaan tilausrivien korvamerkint‰ avainsanoilla");
  $ulos .= "</option>";

  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "pdf_ruudulle_kieli") {
  $sel = $trow[$i] == "A" ? "selected" : "";

  $ulos  = "<td><select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">";

  $ulos .= "<option value = ''>";
  $ulos .= t("PDF-lomakkeet avataan ruudulle oletuksena yhtiˆn kielell‰");
  $ulos .= "</option>";

  $ulos .= "<option value = 'A' {$sel}>";
  $ulos .= t("PDF-lomakkeet avataan ruudulle oletuksena asiakkaan kielell‰");
  $ulos .= "</option>";

  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "tilausrivin_kateraja") {
  $ulos = "<td><input type='number' min='0' max='100' name='{$nimi}' value='{$trow[$i]}'></td>";

  $jatko = 0;
}

if ($fieldname == "viitemaksujen_oikaisut") {
  $apu = $trow[$i];
  $sel = array();
  $sel[$apu] = "selected";

  $ulos  = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";
  $ulos .= "<option value = ''>".t("Viitemaksujen oikaisuja ei tallenneta");
  $ulos .= "<option value = 'K' $sel[K]>".t("Viitemaksujen oikaisut tallennetaan");
  $ulos .= "</select></td>";

  $jatko = 0;
}

if ($fieldname == "laskun_kanavointitiedon_syotto") {
  $apu = $trow[$i];
  $sel = array($apu => " selected");

  $ulos = "<td>
             <select name='{$nimi}' ".js_alasvetoMaxWidth($nimi, 400).">
               <option value='0'>".t("Laskun kanavointitietoa ei voi syˆtt‰‰ k‰sin")."
               <option value='1'{$sel["1"]}>".t("Laskun kanavointitiedon voi syˆtt‰‰ k‰sin yll‰pitosopimukselle")."
             </select>
           </td>";

  $jatko = 0;
}
