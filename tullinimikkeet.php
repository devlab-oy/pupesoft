<?php

require "inc/parametrit.inc";

echo "<font class='head'>".t("Tullinimikkeet")."</font><hr>";

/*
// nämä pitää ajaa jos päivittää uudet tullinimikkeet:
update tullinimike set su=trim(su);
update tullinimike set su='' where su='-';
update tullinimike set su_vientiilmo='NAR' where su='p/st';
update tullinimike set su_vientiilmo='MIL' where su='1 000 p/st';
update tullinimike set su_vientiilmo='MIL' where su='1000 p/st';
update tullinimike set su_vientiilmo='LPA' where su='l alc. 100';
update tullinimike set su_vientiilmo='LTR' where su='l';
update tullinimike set su_vientiilmo='KLT' where su='1 000 l';
update tullinimike set su_vientiilmo='KLT' where su='1000 l';
update tullinimike set su_vientiilmo='TJO' where su='TJ';
update tullinimike set su_vientiilmo='MWH' where su='1 000 kWh';
update tullinimike set su_vientiilmo='MWH' where su='1000 kWh';
update tullinimike set su_vientiilmo='MTQ' where su='m³';
update tullinimike set su_vientiilmo='MTQ' where su='m3';
update tullinimike set su_vientiilmo='GRM' where su='g';
update tullinimike set su_vientiilmo='MTK' where su='m²';
update tullinimike set su_vientiilmo='MTK' where su='m2';
update tullinimike set su_vientiilmo='MTR' where su='m';
update tullinimike set su_vientiilmo='NPR' where su='pa';
update tullinimike set su_vientiilmo='CEN' where su='100 p/st';
update tullinimike set su_vientiilmo='KGE' where su='kg/net eda';
update tullinimike set su_vientiilmo='LPA' where su='l alc. 100%';
update tullinimike set su_vientiilmo='KCC' where su='kg C5H14ClNO';
update tullinimike set su_vientiilmo='MTC' where su='1000 m3';
update tullinimike set su_vientiilmo='KPP' where su='kg P2O5';
update tullinimike set su_vientiilmo='KSH' where su='kg NaOH';
update tullinimike set su_vientiilmo='KPH' where su='kg KOH';
update tullinimike set su_vientiilmo='KUR' where su='kg U';
update tullinimike set su_vientiilmo='GFI' where su='gi F/S';
update tullinimike set su_vientiilmo='KNS' where su='kg H2O2';
update tullinimike set su_vientiilmo='KMA' where su='kg met.am.';
update tullinimike set su_vientiilmo='KNI' where su='kg N';
update tullinimike set su_vientiilmo='KPO' where su='kg K2O';
update tullinimike set su_vientiilmo='KSD' where su='kg 90% sdt';
update tullinimike set su_vientiilmo='CTM' where su='c/k';
update tullinimike set su_vientiilmo='NCL' where su='ce/el';
update tullinimike set su_vientiilmo='CCT' where su='ct/l';
*/

if ($tee == "muuta") {

  $ok = 0;
  $uusitullinimike1 = trim($uusitullinimike1);
  $uusitullinimike2 = trim($uusitullinimike2);

  // katotaan, että tullinimike1 löytyy
  $query = "SELECT cn FROM tullinimike WHERE cn = '$uusitullinimike1' and kieli = '$yhtiorow[kieli]'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) != 1 or $uusitullinimike1 == "") {
    $ok = 1;
    echo "<font class='error'>Tullinimike 1 on virheellinen!</font><br>";
  }

  // kaks pitkä tai ei mitään
  if (strlen($uusitullinimike2) != 2) {
    $ok = 1;
    echo "<font class='error'>Tullinimike 2 tulee olla 2 merkkiä pitkä!</font><br>";
  }

  // tää on aika fiinisliippausta
  if ($ok == 1) echo "<br>";

  // jos kaikki meni ok, nii päivitetään
  if ($ok == 0) {

    if ($tullinimike2 != "") $lisa = " and tullinimike2='$tullinimike2'";
    else $lisa = "";

    $query = "update tuote set tullinimike1='$uusitullinimike1', tullinimike2='$uusitullinimike2' where yhtio='$kukarow[yhtio]' and tullinimike1='$tullinimike1' $lisa";
    $result = pupe_query($query);

    echo sprintf("<font class='message'>Päivitettiin %s tuotetta.</font><br><br>", mysql_affected_rows());

    $tullinimike1 = $uusitullinimike1;
    $tullinimike2 = $uusitullinimike2;
    $uusitullinimike1 = "";
    $uusitullinimike2 = "";
  }
}

if ($tee == "synkronoi") {

  $ch  = curl_init();
  curl_setopt($ch, CURLOPT_URL, "http://pupeapi.sprintit.fi/referenssitullinimikkeet.sql");
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_HEADER, FALSE);
  $nimikeet = curl_exec($ch);

  // Käännetään aliakset UTF-8 muotoon, jos Pupe on UTF-8:ssa
  if (PUPE_UNICODE) {
    // Tässä on "//NO_MB_OVERLOAD"-kommentti
    // jotta UTF8-konversio ei osu tähän riviin
    $nimikeet = utf8_encode($nimikeet); //NO_MB_OVERLOAD
  }

  $nimikeet = explode("\n", trim($nimikeet));

  if (count($nimikeet) == 0) {
    echo t("Tiedoston avaus epäonnistui")."!";
    require "inc/footer.inc";
    exit;
  }

  echo "<br><br>";
  echo t("Poistetaan vanhat tullinimikkeet")."...<br>";

  // Poistetaan nykyiset nimikkeet....
  $query  = "DELETE FROM tullinimike";
  $result = pupe_query($query);

  // Päivitetään tuotteet 2019 - 2020
  $muunnosavaimet = array(
    "19019099" => "19019095",
    "19019099" => "19019099",
    "22082027" => "22082016",
    "22082029" => "22082018",
    "22082029" => "22082019",
    "22082029" => "22082028",
    "22082040" => "22082069",
    "22082040" => "22082088",
    "22082064" => "22082069",
    "22082087" => "22082066",
    "22082089" => "22082066",
    "22082089" => "22082069",
    "22082089" => "22082088",
    "27101964" => "27101966",
    "27101964" => "27101967",
    "27101968" => "27101967",
    "27102015" => "27102016",
    "27102017" => "27102016",
    "27102031" => "27102032",
    "27102035" => "27102032",
    "27102035" => "27102038",
    "27102039" => "27102038",
    "37079021" => "37079020",
    "37079029" => "37079020",
    "39269092" => "39269097",
    "39269097" => "39269097",
    "71042000" => "71042010",
    "71042000" => "71042090",
    "71049000" => "71049010",
    "71049000" => "71049090",
    "73071910" => "73071910",
    "73071990" => "73071910",
    "73071990" => "73071990",
    "73259910" => "73259910",
    "73259990" => "73259910",
    "73259990" => "73259990",
    "84729030" => "84729080",
    "84729040" => "84729080",
    "84729090" => "84729080",
    "85045020" => "85045000",
    "85045095" => "85045000",
    "85049005" => "85049019",
    "85049018" => "85049019",
    "85049091" => "85049090",
    "85049099" => "85049090",
    "85181030" => "85181000",
    "85181095" => "85181000",
    "85182930" => "85182900",
    "85182995" => "85182900",
    "85183020" => "85183000",
    "85183095" => "85183000",
    "85184030" => "85184000",
    "85184080" => "85184000",
    "85198111" => "85198100",
    "85198115" => "85198100",
    "85198121" => "85198100",
    "85198125" => "85198100",
    "85198131" => "85198100",
    "85198135" => "85198100",
    "85198145" => "85198100",
    "85198151" => "85198100",
    "85198170" => "85198100",
    "85198195" => "85198100",
    "85229020" => "85229000",
    "85229030" => "85229000",
    "85229041" => "85229000",
    "85229049" => "85229000",
    "85229070" => "85229000",
    "85229080" => "85229000",
    "85271210" => "85271200",
    "85271290" => "85271200",
    "85271310" => "85271300",
    "85271391" => "85271300",
    "85271399" => "85271300",
    "85279111" => "85279100",
    "85279119" => "85279100",
    "85279135" => "85279100",
    "85279191" => "85279100",
    "85279199" => "85279100",
    "85279210" => "85279200",
    "85279290" => "85279200",
    "85291031" => "85291030",
    "85291039" => "85291030",
    "85369020" => "85369095",
    "85369095" => "85369095",
    "90111010" => "90111000",
    "90111090" => "90111000",
    "90119010" => "90119000",
    "90119090" => "90119000",
    "90121010" => "90121000",
    "90121090" => "90121000",
    "90129010" => "90129000",
    "90129090" => "90129000",
    "90151010" => "90151000",
    "90151090" => "90151000",
    "90152010" => "90152000",
    "90152090" => "90152000",
    "90154010" => "90154000",
    "90154090" => "90154000",
    "90248011" => "90248000",
    "90248019" => "90248000",
    "90248090" => "90248000",
    "90251920" => "90251900",
    "90251980" => "90251900",
    "90278011" => "90278020",
    "90278013" => "90278080",
    "90278017" => "90278080",
    "90278091" => "90278080",
    "90278099" => "90278020",
    "90278099" => "90278080",
    "90279010" => "90279000",
    "90279050" => "90279000",
    "90279080" => "90279000",
    "90303330" => "90303370",
    "90303380" => "90303370",
    "90308930" => "90308900",
    "90308990" => "90308900",
  );

  echo t("Päivitetään muuttuneet tullinimikkeet tuotteille")."...<br>";

  foreach ($muunnosavaimet as $vanha => $uusi) {
    $query  = "UPDATE tuote set
               tullinimike1     = '$uusi'
               WHERE yhtio      = '$kukarow[yhtio]'
               AND tullinimike1 = '$vanha'";
    $result = pupe_query($query);
  }

  // Eka rivi roskikseen
  unset($nimikeet[0]);

  echo t("Lisätään uudet tullinimikkeet tietokantaan")."...<br>";

  foreach ($nimikeet as $rivi) {
    list($cnkey, $cn, $dashes, $dm, $su, $su_vientiilmo, $kieli) = explode("\t", trim($rivi));

    $dm = preg_replace("/([^A-Z0-9öäåÅÄÖ \.,\-_\:\/\!\|\?\+\(\)%#]|é)/i", "", $dm);

    $query  = "INSERT INTO tullinimike SET
               yhtio         = '$kukarow[yhtio]',
               cnkey         = '$cnkey',
               cn            = '$cn',
               dashes        = '$dashes',
               dm            = '$dm',
               su            = '$su',
               su_vientiilmo = '$su_vientiilmo',
               kieli         = '$kieli',
               laatija       = '$kukarow[kuka]',
               luontiaika    = now()";
    $result = pupe_query($query);
  }

  echo t("Päivitys valmis")."...<br><br><hr>";
}


echo "<br><form method='post' autocomplete='off'>";
echo t("Listaa ja muokkaa tuotteiden tullinimikkeitä").":<br><br>";
echo "<table>";
echo "<tr>";
echo "<th>".t("Syötä tullinimike").":</th>";
echo "<td><input type='text' name='tullinimike1' value='$tullinimike1'></td>";
echo "</tr><tr>";
echo "<th>".t("Syötä tullinimikkeen lisäosa").":</th>";
echo "<td><input type='text' name='tullinimike2' value='$tullinimike2'> (ei pakollinen) </td>";
echo "<td class='back'><input type='submit' class='hae_btn' value='".t("Hae")."'></td>";
echo "</tr></table>";
echo "</form><br><br>";

echo "<form method='post' autocomplete='off'>";
echo "<input type='hidden' name='tee' value='synkronoi'>";
echo t("Päivitä järjestelmän tullinimiketietokanta").":<br><br>";
echo "<table>";
echo "<th>".t("Nouda uusimmat tullinimikkeet").":</th>";
echo "<td><input type='submit' value='".t("Nouda")."'></td>";
echo "</tr></table>";
echo "</form>";


if ($tullinimike1 != "") {

  if ($tullinimike2 != "") $lisa = " and tullinimike2='$tullinimike2'";
  else $lisa = "";

  $query = "SELECT *
            from tuote use index (yhtio_tullinimike)
            where yhtio      = '$kukarow[yhtio]'
            and tullinimike1 = '$tullinimike1' $lisa
            order by tuoteno";
  $resul = pupe_query($query);

  if (mysql_num_rows($resul) == 0) {
    echo "<font class='error'>Yhtään tuotetta ei löytynyt!</font><br>";
  }
  else {

    echo sprintf("<font class='message'>Haulla löytyi %s tuotetta.</font><br><br>", mysql_num_rows($resul));

    echo "<form method='post' autocomplete='off'>";
    echo "<input type='hidden' name='tullinimike1' value='$tullinimike1'>";
    echo "<input type='hidden' name='tullinimike2' value='$tullinimike2'>";
    echo "<input type='hidden' name='tee' value='muuta'>";

    echo "<table>";
    echo "<tr>";
    echo "<th>".t("Syötä uusi tullinimike").":</th>";
    echo "<td><input type='text' name='uusitullinimike1' value='$uusitullinimike1'></td>";
    echo "</tr><tr>";
    echo "<th>".t("Syötä tullinimikkeen lisäosa").":</th>";
    echo "<td><input type='text' name='uusitullinimike2' value='$uusitullinimike2'></td>";
    echo "<td class='back'><input type='submit' value='".t("Päivitä")."'></td>";
    echo "</tr></table>";
    echo "</form><br>";

    echo "<table>";
    echo "<tr>";
    echo "<th>".t("Tuoteno")."</th>";
    echo "<th>".t("Osasto")."</th>";
    echo "<th>".t("Try")."</th>";
    echo "<th>".t("Merkki")."</th>";
    echo "<th>".t("Nimitys")."</th>";
    echo "<th>".t("Tullinimike")."</th>";
    echo "<th>".t("Tullinimikkeen lisäosa")."</th>";
    echo "</tr>";

    while ($rivi = mysql_fetch_array($resul)) {

      // tehdään avainsana query
      $oresult = t_avainsana("OSASTO", "", "and avainsana.selite ='$rivi[osasto]'");
      $os = mysql_fetch_array($oresult);

      // tehdään avainsana query
      $tresult = t_avainsana("TRY", "", "and avainsana.selite ='$rivi[try]'");
      $try = mysql_fetch_array($tresult);

      echo "<tr>";
      echo "<td><a href='yllapito.php?toim=tuote&tunnus=$rivi[tunnus]&lopetus=tullinimikkeet.php'>$rivi[tuoteno]</a></td>";
      echo "<td>$rivi[osasto] $os[selitetark]</td>";
      echo "<td>$rivi[try] $try[selitetark]</td>";
      echo "<td>$rivi[tuotemerkki]</td>";
      echo "<td>".t_tuotteen_avainsanat($rivi, 'nimitys')."</td>";
      echo "<td>$rivi[tullinimike1]</td>";
      echo "<td>$rivi[tullinimike2]</td>";
      echo "</tr>";
    }
    echo "</table>";
  }
}

require "inc/footer.inc";
