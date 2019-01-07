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
  curl_setopt($ch, CURLOPT_URL, "http://api.devlab.fi/referenssitullinimikkeet.sql");
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

  // Päivitetään tuotteet 2018 - 2019
  $muunnosavaimet = array(
    "03083010" => "03083080",
    "03083090" => "03083080",
    "27101251" => "27101250",
    "27101259" => "27101250",
    "76061220" => "76061219",
    "76061292" => "76061211",
    "84431331" => "84431332",
    "84431335" => "84431332",
    "84431339" => "84431334",
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
