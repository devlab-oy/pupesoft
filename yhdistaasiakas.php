<?php
require "inc/parametrit.inc";

if (!isset($konserni))  $konserni = '';
if (!isset($tee))     $tee = '';
if (!isset($oper))    $oper = '';
if (!isset($ojarj))    $ojarj = '';
if (!isset($tapa))    $tapa = 'a';

echo "<font class='head'>".t("Yhdistä asiakkaita")."</font><hr>";

if (isset($_FILES['userfile']) and is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE and $tee == 'YHDISTA_TIEDOSTOSTA') {

  $historia = '';

  $path_parts = pathinfo($_FILES['userfile']['name']);
  $ext = strtoupper($path_parts['extension']);

  if ($_FILES['userfile']['size']==0) {
    die ("<font class='error'><br>".t("Tiedosto on tyhjä")."!</font>");
  }

  $retval = tarkasta_liite("userfile", array("XLSX", "XLS", "ODS", "SLK", "XML", "GNUMERIC", "CSV", "TXT", "DATAIMPORT"));

  if ($retval !== TRUE) {
    die ("<font class='error'><br>".t("Väärä tiedostomuoto")."!</font>");
  }

  $excelrivit = pupeFileReader($_FILES['userfile']['tmp_name'], $ext);
  $headers = array();
  $headers = $excelrivit[0];
  unset($excelrivit[0]);
  $excelrivit = array_values($excelrivit);

  $taulunrivit = array();
  for ($y = 0; $y < count($excelrivit); $y++) {
    for ($h = 0; $h < count($headers); $h++) {
      $taulunrivit[$y][strtoupper(trim($headers[$h]))] = $excelrivit[$y][$h];
    }
  }

  $yhdistykset = array();

  $yr = 0;
  foreach ($taulunrivit as $r) {

    $tunnukset = array();

    if (isset($r['ASIAKASTUNNUS']))    $tunnukset['ASIAKASTUNNUS'] = $r['ASIAKASTUNNUS'];
    if (isset($r['YTUNNUS']))          $tunnukset['YTUNNUS'] = $r['YTUNNUS'];
    if (isset($r['OVTTUNNUS']))        $tunnukset['OVTTUNNUS'] = $r['OVTTUNNUS'];
    if (isset($r['TOIM_OVTTUNNUS']))   $tunnukset['TOIM_OVTTUNNUS'] = $r['TOIM_OVTTUNNUS'];

    if ( $tunnus = hae_asiakastunnus($tunnukset) ) {

      if ( strtoupper($r['JATA_TAMA']) != 'X' ) {
        $yhdistykset[$yr]['yhdista'][] = $tunnus;
      }
      elseif ( isset($yhdistykset[$yr]) and count($yhdistykset[$yr]['yhdista']) > 0 ) {
        $yhdistykset[$yr]['jata'] = $tunnus;
        $yr++;
      }
    }
  }

  if ($yr == 0) {
    echo t("Ei löytynyt yhdistettäviä asiakkaita")."...<br />";
  }

  foreach ($yhdistykset as $y) {
    if (!isset($y['spessut'])) {
      $y['spessut'] = 0;
    }
    echo yhdista_asiakkaita( $y['jata'], $y['yhdista'] );
    echo '<hr>';
  }
  echo "<br /><br /><form><input type='submit' value='" . t("Yhdistä lisää") . "' /></form>";
}

if ($tee == 'YHDISTA' and $jataminut != '' and count($yhdista) != '') {
  echo yhdista_asiakkaita( $jataminut, $yhdista );
  echo "<br /><br /><form><input type='submit' value='" . t("Yhdistä lisää") . "' /></form>";
}

if ( ( !isset($jataminut) and !isset($yhdista) ) and (!isset($_FILES['userfile']) or is_uploaded_file($_FILES['userfile']['tmp_name']) === false ) ) {

  echo "<br><form method='post' name='sendfile' enctype='multipart/form-data'>";
  echo "<input type='hidden' name='tee' value='YHDISTA_TIEDOSTOSTA'>";
  echo t("Lue yhdistettävät asiakkaat tiedostosta")."...<br /><br />";

  echo "<table>";
  echo "<tr><th>" . t("Asiakkaan valintatapa") . ":</th><td>";
  echo "<select name='tapa' onChange='submit();' >";

  if ($tapa == 'b') {$b_sel = 'selected'; $a_sel = '';}else {$a_sel = 'selected'; $b_sel = '';}

  echo "<option value='a' $a_sel>" . t("Asiakastunnus") . "</option>";
  echo "<option value='b' $b_sel>" . t("ytunnus") . ", " .  t("ovttunnus") . ", " .  t("toim_ovttunnus") . "</option></td></tr>";
  echo "</table><br />";

  echo "<table>";
  echo "<tr><th colspan='99'>" . t("Excel-tiedosto seuraavin tiedoin") . ":</th></tr>";

  if ($tapa == 'b') {
    echo "<tr><td>ytunnus</td><td>" .  t("ovttunnus") . "</td><td>" . t("toim_ovttunnus") . "</td><td>jata_tama</td></tr>";
  }
  else {
    echo "<tr><td>" . t("asiakastunnus") . "</td><td>" . t("jata_tama") . "</td></tr>";
  }

  echo "</table><br />";

  echo t("\"jata_tama\" kenttään laitetaan arvoksi \"X\" niille riveille joihin edelliset rivit halutaan yhdistää. Jos yhdistettäviä rivejä on paljon, saattaa toimenpide kestää kauan").".<br /><br />";


  echo "<table>";
  echo "<tr><th>".t("Valitse tiedosto").":</th>";
  echo "<td><input name='userfile' type='file'></td>";
  echo "<td class='back'><input type='submit' value='".t("Jatka")."'></td></tr></table><br /></form>";

  echo t("Voit myös valita yhdistettävät asiakkaat listasta.")."<br><br>";

  echo "<form method='post'>";
  echo "<input type='hidden' name='tee' value='YHDISTA'>";

  $monivalintalaatikot = array("ASIAKASOSASTO", "ASIAKASRYHMA", "ASIAKASPIIRI", "ASIAKASMYYJA", "ASIAKASTILA", "<br>DYNAAMINEN_ASIAKAS");
  $monivalintalaatikot_normaali = array();

  require "tilauskasittely/monivalintalaatikot.inc";

  $kentat    = "asiakas.ytunnus::asiakas.ytunnus::asiakas.nimi>>asiakas.toim_nimi::asiakas.osoite>>asiakas.toim_osoite::asiakas.postino>>asiakas.toim_postino::asiakas.postitp>>asiakas.toim_postitp::asiakas.asiakasnro";
  $jarjestys = poista_osakeyhtio_lyhenne_mysql("nimi").", nimitark, ytunnus, tunnus";

  $array = explode("::", $kentat);
  $count = count($array);

  for ($i = 0; $i <= $count; $i++) {
    if (isset($haku[$i]) and strlen($haku[$i]) > 0) {
      if ($array[$i] == "asiakas.ytunnus" || $array[$i] == "asiakas.asiakasnro") {
        $lisa .= " and " . $array[$i] . " like '%" . $haku[$i] . "%'";
        $ulisa .= "&haku[" . $i . "]=" . $haku[$i];
      }
      else {
        $toimlisa = explode(">>", $array[$i]);
        $lisa .= " and (" . $toimlisa[0] . " like '%" . $haku[$i] . "%'";
        $lisa .= " or " . $toimlisa[1] . " like '%" . $haku[$i] . "%')";
        $ulisa .= "&haku[" . $i . "]=" . $haku[$i];
      }
    }
  }

  if (strlen($ojarj) > 0) {
    $jarjestys = $ojarj;
  }

  $query = "SELECT
            asiakas.tunnus,
            asiakas.ytunnus,
            concat(asiakas.nimi ,'<br>', asiakas.toim_nimi,'<br>',  asiakas.laskutus_nimi) 'nimi'  ,
            concat(asiakas.osoite ,'<br>', asiakas.toim_osoite,'<br>',  asiakas.laskutus_osoite) 'osoite',
            concat(asiakas.postino, '<br>', asiakas.toim_postino, asiakas.laskutus_postino) 'postino',
            concat(asiakas.postitp, '<br>', asiakas.toim_postitp, asiakas.laskutus_postitp) 'postitp',
            asiakas.asiakasnro,
            asiakas.yhtio,
            asiakas.laji
            FROM asiakas
            WHERE asiakas.yhtio = '$kukarow[yhtio]'
            $lisa
            ORDER BY $jarjestys
            LIMIT 500";
  $result = pupe_query($query);

  echo "<br><table>";
  echo "<tr>";

  for ($i = 1; $i < mysql_num_fields($result)-1; $i++) { // HAKUKENTÄT
    echo "<th><a href='$PHP_SELF?ojarj=".mysql_field_name($result, $i).$ulisa."'>" . t(mysql_field_name($result, $i)) . "</a>";

    if  (mysql_field_len($result, $i)>20) $size='20';
    elseif  (mysql_field_len($result, $i)<=20)  $size='10';
    else  $size='10';

    if (!isset($haku[$i])) $haku[$i] = '';

    echo "<br><input type='text' name='haku[$i]' value='$haku[$i]' size='$size' maxlength='" . mysql_field_len($result, $i) ."'>";
    echo "</th>";
  }

  echo "<th>".t("Yhdistä")."</th><th>".t("jätä tämä")."</th>";
  echo "<td class='back'>&nbsp;&nbsp;<input type='submit' value='".t("Etsi / yhdistä")."'></td></tr>\n\n";

  $kalalask = 1;

  while ($trow = mysql_fetch_array($result)) { // tiedot

    if ($trow['laji'] == 'P') {
      $luokka = 'spec';
    }
    else {
      $luokka = 'aktiivi';
    }

    echo "<tr class='{$luokka}'>";

    for ($i=1; $i<mysql_num_fields($result)-1; $i++) {

      if ($i == 1) {
        if (trim($trow[1]) == '') $trow[1] = t("*tyhjä*");
        echo "<td><a name='2_$kalalask' href='".$palvelin2."yllapito.php?toim=asiakas&tunnus=$trow[tunnus]&lopetus=".$palvelin2."yhdistaasiakas.php////ojarj=$ojarj".str_replace("&", "//", $ulisa)."///2_$kalalask'>$trow[$i]</a></td>";
      }
      elseif (mysql_field_name($result, $i) == 'ytunnus') {
        echo "<td><a name='2_$kalalask' href='".$palvelin2."yllapito.php?toim=asiakas&tunnus=$trow[tunnus]&lopetus=".$palvelin2."yhdistaasiakas.php////ojarj=$ojarj".str_replace("&", "//", $ulisa)."///2_$kalalask'>$trow[$i]</a></td>";
      }
      else {
        echo "<td>$trow[$i]</td>";
      }
    }

    if ($trow['laji'] == 'P') {
      echo "<td colspan='2' style='text-align:center; vertical-align:middle;'>Poistettu</td>";
    }
    else {
      echo "<td align='center'><input type='checkbox' name='yhdista[$trow[tunnus]]' value='$trow[tunnus]' $sel/></td>";
      echo "<td align='center'><input type='radio' name='jataminut' value='$trow[tunnus]'/></td>";
    }

    echo "</tr>\n\n";

    $kalalask++;
  }

  echo "</table><br><br>";

  echo "<input type='submit' value='".t("Yhdistä asiakkaat")."'>";
  echo "</form>";

}

require "inc/footer.inc";

function yhdista_asiakkaita($jataminut, $yhdista) {
  global $kukarow;

  // tässä on jätettävän asiakkaan tiedot
  $jquery = "SELECT *
             FROM asiakas
             where yhtio = '$kukarow[yhtio]'
             and tunnus  = '$jataminut' ";
  $jresult = pupe_query($jquery);
  $jrow = mysql_fetch_assoc($jresult);

  if (empty($jrow)) {
    return t('Asiakasta johon oltiin yhdistämässä ei löytynyt');
  }

  echo "<br>".t("Jätetään asiakas").": $jrow[ytunnus] $jrow[nimi] ".$jrow['osoite']." ".$jrow['postino']." ".$jrow['postitp']."<br>";

  // Otetaan jätettävä pois poistettavista jos se on sinne ruksattu
  unset($yhdista[$jataminut]);

  $historia = t("Asiakkaaseen").": ". $jrow["nimi"].", ". t("ytunnus").": ". $jrow["ytunnus"].", ".t("asiakasnro").": ". $jrow["asiakasnro"] ." ".t("liitettiin seuraavat asiakkaat").": <br />";

  foreach ($yhdista as $haettava) {

    // haetaan "Yhdistettävän" firman tiedot esille niin saadaan oikeat parametrit.
    $asquery = "SELECT * FROM asiakas WHERE yhtio='$kukarow[yhtio]' AND tunnus = '{$haettava}'";
    $asresult = pupe_query($asquery);

    if (mysql_num_rows($asresult) == 1) {

      $asrow = mysql_fetch_assoc($asresult);

      echo "<br>".t("Yhdistetään").": $asrow[ytunnus] $asrow[nimi] ".$asrow['osoite']." ".$asrow['postino']." ".$asrow['postitp']."<br><br>";

      // haetaan asiakashinta ensin Ytunnuksella.
      $hquery = "SELECT *
                 FROM asiakashinta
                 WHERE ytunnus = '$asrow[ytunnus]'
                 AND asiakas   = 0
                 AND yhtio ='$kukarow[yhtio]'";
      $hresult = pupe_query($hquery);

      if (mysql_num_rows($hresult) == 0) {
        echo "<font class='error'>".t("Ei asiakashintoja y-tunnuksella")."</font><br>";
      }
      else {
        echo "<font class='ok'>".t("Löytyi asiakashintoja y-tunnuksella")."</font><br>";
        while ($ahrow = mysql_fetch_assoc($hresult)) {

          $tarksql = "SELECT *
                      FROM asiakashinta
                      where yhtio           = '$kukarow[yhtio]'
                      and tuoteno           = '$ahrow[tuoteno]'
                      and ryhma             = '$ahrow[ryhma]'
                      and asiakas           = 0
                      and ytunnus           = '$jrow[ytunnus]'
                      and asiakas_ryhma     = '$ahrow[asiakas_ryhma]'
                      and asiakas_segmentti = '$ahrow[asiakas_segmentti]'
                      and piiri             = '$ahrow[piiri]'
                      and hinta             = '$ahrow[hinta]'
                      and valkoodi          = '$ahrow[valkoodi]'
                      and minkpl            = '$ahrow[minkpl]'
                      and maxkpl            = '$ahrow[maxkpl]'
                      and alkupvm           = '$ahrow[alkupvm]'
                      and loppupvm          = '$ahrow[loppupvm]'
                      and laji              = '$ahrow[laji]'";
          $tarkesult = pupe_query($tarksql);
          $ahy = mysql_num_rows($tarkesult);

          if ($ahy == 0) {
            $ahinsert = "INSERT INTO asiakashinta SET
                         yhtio             = '$kukarow[yhtio]',
                         tuoteno           = '$ahrow[tuoteno]',
                         ryhma             = '$ahrow[ryhma]',
                         asiakas           = 0,
                         ytunnus           = '$jrow[ytunnus]',
                         asiakas_ryhma     = '$ahrow[asiakas_ryhma]',
                         asiakas_segmentti = '$ahrow[asiakas_segmentti]',
                         piiri             = '$ahrow[piiri]',
                         hinta             = '$ahrow[hinta]',
                         valkoodi          = '$ahrow[valkoodi]',
                         minkpl            = '$ahrow[minkpl]',
                         maxkpl            = '$ahrow[maxkpl]',
                         alkupvm           = '$ahrow[alkupvm]',
                         loppupvm          = '$ahrow[loppupvm]',
                         laji              = '$ahrow[laji]',
                         laatija           = '$kukarow[kuka]',
                         luontiaika        = now()";
            $ahinsertresult = pupe_query($ahinsert);

            synkronoi($kukarow["yhtio"], "asiakashinta", mysql_insert_id($GLOBALS["masterlink"]), "", "");
          }
        }
      }

      // haetaan asiakashinta sitten asiakastunnuksella.
      $hquery = "SELECT *
                 FROM asiakashinta
                 WHERE asiakas = '$asrow[tunnus]'
                 AND yhtio ='$kukarow[yhtio]'";
      $hresult = pupe_query($hquery);

      if (mysql_num_rows($hresult) == 0) {
        echo "<font class='error'>".t("Ei asiakashintoja asiakastunnuksella")."</font><br>";
      }
      else {
        echo "<font class='ok'>".t("Löytyi asiakashintoja asiakastunnuksella")."</font><br>";
        while ($ahrow = mysql_fetch_assoc($hresult)) {

          // Ytunnus voi olla myös setattu, mutta ei huomioida sitä tässä...
          $tarksql = "SELECT *
                      FROM asiakashinta
                      where yhtio           = '$kukarow[yhtio]'
                      and tuoteno           = '$ahrow[tuoteno]'
                      and ryhma             = '$ahrow[ryhma]'
                      and asiakas           = '$jrow[tunnus]'
                      #and ytunnus            = ''
                      and asiakas_ryhma     = '$ahrow[asiakas_ryhma]'
                      and asiakas_segmentti = '$ahrow[asiakas_segmentti]'
                      and piiri             = '$ahrow[piiri]'
                      and hinta             = '$ahrow[hinta]'
                      and valkoodi          = '$ahrow[valkoodi]'
                      and minkpl            = '$ahrow[minkpl]'
                      and maxkpl            = '$ahrow[maxkpl]'
                      and alkupvm           = '$ahrow[alkupvm]'
                      and loppupvm          = '$ahrow[loppupvm]'
                      and laji              = '$ahrow[laji]'";
          $tarkesult = pupe_query($tarksql);
          $ahy = mysql_num_rows($tarkesult);

          if ($ahy == 0) {
            $ahinsert = "INSERT INTO asiakashinta SET
                         yhtio             = '$kukarow[yhtio]',
                         tuoteno           = '$ahrow[tuoteno]',
                         ryhma             = '$ahrow[ryhma]',
                         asiakas           = '$jrow[tunnus]',
                         ytunnus           = '',
                         asiakas_ryhma     = '$ahrow[asiakas_ryhma]',
                         asiakas_segmentti = '$ahrow[asiakas_segmentti]',
                         piiri             = '$ahrow[piiri]',
                         hinta             = '$ahrow[hinta]',
                         valkoodi          = '$ahrow[valkoodi]',
                         minkpl            = '$ahrow[minkpl]',
                         maxkpl            = '$ahrow[maxkpl]',
                         alkupvm           = '$ahrow[alkupvm]',
                         loppupvm          = '$ahrow[loppupvm]',
                         laji              = '$ahrow[laji]',
                         laatija           = '$kukarow[kuka]',
                         luontiaika        = now()";
            $ahinsertresult = pupe_query($ahinsert);

            synkronoi($kukarow["yhtio"], "asiakashinta", mysql_insert_id($GLOBALS["masterlink"]), "", "");
          }
        }
      }

      // haetaan asiakasalennus ensin Ytunnuksella.
      $hquery = "SELECT *
                 FROM asiakasalennus
                 WHERE ytunnus = '$asrow[ytunnus]'
                 AND asiakas   = 0
                 AND yhtio ='$kukarow[yhtio]'";
      $hresult = pupe_query($hquery);

      if (mysql_num_rows($hresult) == 0) {
        echo "<font class='error'>".t("Ei asiakasalennuksia y-tunnuksella")."</font><br>";
      }
      else {
        echo "<font class='ok'>".t("Löytyi asiakasalennuksia y-tunnuksella")."</font><br>";
        while ($alrow = mysql_fetch_assoc($hresult)) {

          $tarksql = "SELECT * FROM asiakasalennus
                      where yhtio           = '$kukarow[yhtio]'
                      and tuoteno           = '$alrow[tuoteno]'
                      and ryhma             = '$alrow[ryhma]'
                      and asiakas           = 0
                      and ytunnus           = '$jrow[ytunnus]'
                      and asiakas_ryhma     = '$alrow[asiakas_ryhma]'
                      and asiakas_segmentti = '$alrow[asiakas_segmentti]'
                      and piiri             = '$alrow[piiri]'
                      and alennus           = '$alrow[alennus]'
                      and alennuslaji       = '$alrow[alennuslaji]'
                      and minkpl            = '$alrow[minkpl]'
                      and alkupvm           = '$alrow[alkupvm]'
                      and loppupvm          = '$alrow[loppupvm]'";
          $tarkesult = pupe_query($tarksql);
          $ahy = mysql_num_rows($tarkesult);

          if ($ahy == 0) {
            $alinsert = "INSERT INTO asiakasalennus SET
                         yhtio             = '$kukarow[yhtio]',
                         tuoteno           = '$alrow[tuoteno]',
                         ryhma             = '$alrow[ryhma]',
                         asiakas           = 0,
                         ytunnus           = '$jrow[ytunnus]',
                         asiakas_ryhma     = '$alrow[asiakas_ryhma]',
                         asiakas_segmentti = '$alrow[asiakas_segmentti]',
                         piiri             = '$alrow[piiri]',
                         alennus           = '$alrow[alennus]',
                         alennuslaji       = '$alrow[alennuslaji]',
                         minkpl            = '$alrow[minkpl]',
                         alkupvm           = '$alrow[alkupvm]',
                         loppupvm          = '$alrow[loppupvm]',
                         laatija           = '$kukarow[kuka]',
                         luontiaika        = now()";
            $alinsertresult = pupe_query($alinsert);

            synkronoi($kukarow["yhtio"], "asiakasalennus", mysql_insert_id($GLOBALS["masterlink"]), "", "");
          }
        }
      }

      // haetaan asiakasalennus sitten asiakastunnuksella.
      $hquery = "SELECT *
                 FROM asiakasalennus
                 WHERE asiakas = '$asrow[tunnus]'
                 #AND ytunnus = ''
                 AND yhtio ='$kukarow[yhtio]'";
      $hresult = pupe_query($hquery);

      if (mysql_num_rows($hresult) == 0) {
        echo "<font class='error'>".t("Ei asiakasalennuksia asiakastunnuksella")."</font><br>";
      }
      else {
        echo "<font class='ok'>".t("Löytyi asiakasalennuksia asiakastunnuksella")."</font><br>";
        while ($alrow = mysql_fetch_assoc($hresult)) {
          // Ytunnus voi olla myös setattu, mutta ei huomioida sitä tässä...
          $tarksql = "SELECT * FROM asiakasalennus
                      where yhtio           = '$kukarow[yhtio]'
                      and tuoteno           = '$alrow[tuoteno]'
                      and ryhma             = '$alrow[ryhma]'
                      and asiakas           = '$jrow[tunnus]'
                      #and ytunnus            = ''
                      and asiakas_ryhma     = '$alrow[asiakas_ryhma]'
                      and asiakas_segmentti = '$alrow[asiakas_segmentti]'
                      and piiri             = '$alrow[piiri]'
                      and alennus           = '$alrow[alennus]'
                      and alennuslaji       = '$alrow[alennuslaji]'
                      and minkpl            = '$alrow[minkpl]'
                      and monikerta         = '$alrow[monikerta]'
                      and alkupvm           = '$alrow[alkupvm]'
                      and loppupvm          = '$alrow[loppupvm]'";
          $tarkesult = pupe_query($tarksql);
          $ahy = mysql_num_rows($tarkesult);

          if ($ahy == 0) {
            $alinsert = "INSERT INTO asiakasalennus SET
                         yhtio             = '$kukarow[yhtio]',
                         tuoteno           = '$alrow[tuoteno]',
                         ryhma             = '$alrow[ryhma]',
                         asiakas           = '$jrow[tunnus]',
                         ytunnus           = '',
                         asiakas_ryhma     = '$alrow[asiakas_ryhma]',
                         asiakas_segmentti = '$alrow[asiakas_segmentti]',
                         piiri             = '$alrow[piiri]',
                         alennus           = '$alrow[alennus]',
                         alennuslaji       = '$alrow[alennuslaji]',
                         minkpl            = '$alrow[minkpl]',
                         monikerta         = '$alrow[monikerta]',
                         alkupvm           = '$alrow[alkupvm]',
                         loppupvm          = '$alrow[loppupvm]',
                         laatija           = '$kukarow[kuka]',
                         luontiaika        = now()";
            $alinsertresult = pupe_query($alinsert);

            synkronoi($kukarow["yhtio"], "asiakasalennus", mysql_insert_id($GLOBALS["masterlink"]), "", "");
          }
        }
      }

      // !!!!!!!! ASIAKASKOMMENTTI OSIO !!!!!!!!!!!!
      $hquery = "SELECT *
                 FROM asiakaskommentti
                 WHERE yhtio ='$kukarow[yhtio]'
                 AND ytunnus = '$asrow[ytunnus]'";
      $hresult = pupe_query($hquery);

      if (mysql_num_rows($hresult) == 0) {
        echo "<font class='error'>".t("Ei löytynyt asiakaskommentteja asiakkaalta")."</font><br>";
      }
      else {
        echo "<font class='ok'>".t("Löytyi asiakaskommentteja asiakkaalta")."</font><br>";
        while ($ahrow = mysql_fetch_assoc($hresult)) {

          $tarksql = "SELECT *
                      FROM asiakaskommentti
                      where yhtio   = '$kukarow[yhtio]'
                      and kommentti = '$ahrow[kommentti]'
                      and tuoteno   = '$ahrow[tuoteno]'
                      and ytunnus   = '$jrow[ytunnus]'";
          $tarkesult = pupe_query($tarksql);
          $ahy = mysql_num_rows($tarkesult);

          if ($ahy == 0) {
            $ahinsert = "INSERT INTO asiakaskommentti SET
                         yhtio      = '$kukarow[yhtio]',
                         kommentti  = '$ahrow[kommentti]',
                         tuoteno    = '$ahrow[tuoteno]',
                         ytunnus    = '$jrow[ytunnus]',
                         tyyppi     = '$ahrow[tyyppi]',
                         laatija    = '$kukarow[kuka]',
                         luontiaika = now()";
            $ahinsertresult = pupe_query($ahinsert);

            synkronoi($kukarow["yhtio"], "asiakaskommentti", mysql_insert_id($GLOBALS["masterlink"]), "", "");
          }
        }
      }

      // !!!!!!!! RAHTISOPIMUS OSIO !!!!!!!!!!!!
      $hquery = "SELECT *
                 FROM rahtisopimukset
                 WHERE yhtio = '$kukarow[yhtio]'
                 AND asiakas = 0
                 AND ytunnus = '$asrow[ytunnus]'";
      $hresult = pupe_query($hquery);

      if (mysql_num_rows($hresult) == 0) {
        echo "<font class='error'>".t("Ei löytynyt rahtisopimuksia y-tunnuksella")."</font><br>";
      }
      else {
        echo "<font class='ok'>".t("Löytyi rahtisopimuksia y-tunnuksella")."</font><br>";
        while ($ahrow = mysql_fetch_assoc($hresult)) {

          $tarksql = "SELECT *
                      FROM rahtisopimukset
                      where yhtio      = '$kukarow[yhtio]'
                      and toimitustapa = '$ahrow[toimitustapa]'
                      and asiakas      = 0
                      and ytunnus      = '$jrow[ytunnus]'
                      and rahtisopimus = '$ahrow[rahtisopimus]'
                      and selite       = '$ahrow[selite]'
                      and muumaksaja   = '$ahrow[muumaksaja]'";
          $tarkesult = pupe_query($tarksql);
          $ahy = mysql_num_rows($tarkesult);

          if ($ahy == 0) {
            $ahinsert = "INSERT INTO rahtisopimukset SET
                         yhtio        = '$kukarow[yhtio]',
                         toimitustapa = '$ahrow[toimitustapa]',
                         asiakas      = 0,
                         ytunnus      = '$jrow[ytunnus]',
                         rahtisopimus = '$ahrow[rahtisopimus]',
                         selite       = '$ahrow[selite]',
                         muumaksaja   = '$ahrow[muumaksaja]',
                         laatija      = '$kukarow[kuka]',
                         luontiaika   = now()";
            $ahinsertresult = pupe_query($ahinsert);

            synkronoi($kukarow["yhtio"], "rahtisopimukset", mysql_insert_id($GLOBALS["masterlink"]), "", "");
          }
        }
      }

      $hquery = "SELECT *
                 FROM rahtisopimukset
                 WHERE yhtio ='$kukarow[yhtio]'
                 AND asiakas = '$asrow[tunnus]'";
      $hresult = pupe_query($hquery);

      if (mysql_num_rows($hresult) == 0) {
        echo "<font class='error'>".t("Ei löytynyt rahtisopimuksia asiakastunnuksella")."</font><br>";
      }
      else {
        echo "<font class='ok'>".t("Löytyi rahtisopimuksia asiakastunnuksella")."</font><br>";
        while ($ahrow = mysql_fetch_assoc($hresult)) {

          $tarksql = "SELECT *
                      FROM rahtisopimukset
                      where yhtio      = '$kukarow[yhtio]'
                      and toimitustapa = '$ahrow[toimitustapa]'
                      and asiakas      = '$jrow[tunnus]'
                      and rahtisopimus = '$ahrow[rahtisopimus]'
                      and selite       = '$ahrow[selite]'
                      and muumaksaja   = '$ahrow[muumaksaja]'";
          $tarkesult = pupe_query($tarksql);
          $ahy = mysql_num_rows($tarkesult);

          if ($ahy == 0) {
            $ahinsert = "INSERT INTO rahtisopimukset SET
                         yhtio        = '$kukarow[yhtio]',
                         toimitustapa = '$ahrow[toimitustapa]',
                         asiakas      = '$jrow[tunnus]',
                         ytunnus      = '',
                         rahtisopimus = '$ahrow[rahtisopimus]',
                         selite       = '$ahrow[selite]',
                         muumaksaja   = '$ahrow[muumaksaja]',
                         laatija      = '$kukarow[kuka]',
                         luontiaika   = now()";
            $ahinsertresult = pupe_query($ahinsert);

            synkronoi($kukarow["yhtio"], "rahtisopimukset", mysql_insert_id($GLOBALS["masterlink"]), "", "");
          }
        }
      }

      // !!!!!!!! YHTEYSHENKILÖ OSIO !!!!!!!!!!!!
      $hquery = "SELECT *
                 FROM yhteyshenkilo
                 WHERE yhtio      = '$kukarow[yhtio]'
                 AND liitostunnus = '$asrow[tunnus]'
                 and tyyppi       = 'A'";
      $hresult = pupe_query($hquery);

      if (mysql_num_rows($hresult) == 0) {
        echo "<font class='error'>".t("Ei löytynyt yhteyshenkilöitä asiakkaalta")."</font><br>";
      }
      else {
        echo "<font class='ok'>".t("Löytyi yhteyshenkilöitä asiakkaalta")."</font><br>";
        while ($ahrow = mysql_fetch_assoc($hresult)) {

          $tarksql = "SELECT *
                      FROM yhteyshenkilo
                      where yhtio             = '$kukarow[yhtio]'
                      and tyyppi              = '$ahrow[tyyppi]'
                      and liitostunnus        = '$jrow[tunnus]'
                      and nimi                = '$ahrow[nimi]'
                      and titteli             = '$ahrow[titteli]'
                      and rooli               = '$ahrow[rooli]'
                      and suoramarkkinointi   = '$ahrow[suoramarkkinointi]'
                      and email               = '$ahrow[email]'
                      and puh                 = '$ahrow[puh]'
                      and gsm                 = '$ahrow[gsm]'
                      and fax                 = '$ahrow[fax]'
                      and www                 = '$ahrow[www]'
                      and fakta               = '$ahrow[fakta]'
                      and tilausyhteyshenkilo = '$ahrow[tilausyhteyshenkilo]'
                      and oletusyhteyshenkilo = '$ahrow[oletusyhteyshenkilo]'";
          $tarkesult = pupe_query($tarksql);
          $ahy = mysql_num_rows($tarkesult);

          if ($ahy == 0) {
            $ahinsert = "INSERT INTO yhteyshenkilo SET
                         yhtio               = '$kukarow[yhtio]',
                         tyyppi              = '$ahrow[tyyppi]',
                         liitostunnus        = '$jrow[tunnus]',
                         nimi                = '$ahrow[nimi]',
                         titteli             = '$ahrow[titteli]',
                         rooli               = '$ahrow[rooli]',
                         suoramarkkinointi   = '$ahrow[suoramarkkinointi]',
                         email               = '$ahrow[email]',
                         puh                 = '$ahrow[puh]',
                         gsm                 = '$ahrow[gsm]',
                         fax                 = '$ahrow[fax]',
                         www                 = '$ahrow[www]',
                         fakta               = '$ahrow[fakta]',
                         tilausyhteyshenkilo = '$ahrow[tilausyhteyshenkilo]',
                         oletusyhteyshenkilo = '$ahrow[oletusyhteyshenkilo]',
                         laatija             = '$kukarow[kuka]',
                         luontiaika          = now()";
            $ahinsertresult = pupe_query($ahinsert);

            synkronoi($kukarow["yhtio"], "yhteyshenkilo", mysql_insert_id($GLOBALS["masterlink"]), "", "");
          }
        }
      }

      // !!!!!!!! ASIAKKAAN_AVAINSANA OSIO !!!!!!!!!!!!
      $hquery = "SELECT *
                 FROM asiakkaan_avainsanat
                 WHERE yhtio      = '$kukarow[yhtio]'
                 AND liitostunnus = '$asrow[tunnus]'";
      $hresult = pupe_query($hquery);

      if (mysql_num_rows($hresult) == 0) {
        echo "<font class='error'>".t("Ei löytynyt avainsanoja asiakkaalta")."</font><br>";
      }
      else {
        echo "<font class='ok'>".t("Löytyi avainsanoja asiakkaalta")."</font><br>";
        while ($ahrow = mysql_fetch_assoc($hresult)) {

          $tarksql = "SELECT *
                      FROM asiakkaan_avainsanat
                      where yhtio      = '$kukarow[yhtio]'
                      and liitostunnus = '$jrow[tunnus]'
                      and kieli        = '$ahrow[kieli]'
                      and laji         = '$ahrow[laji]'
                      and avainsana    = '$ahrow[avainsana]'
                      and tarkenne     = '$ahrow[tarkenne]'";
          $tarkesult = pupe_query($tarksql);
          $ahy = mysql_num_rows($tarkesult);

          if ($ahy == 0) {
            $ahinsert = "INSERT INTO asiakkaan_avainsanat SET
                         yhtio        = '$kukarow[yhtio]',
                         liitostunnus = '$jrow[tunnus]',
                         kieli        = '$ahrow[kieli]',
                         laji         = '$ahrow[laji]',
                         avainsana    = '$ahrow[avainsana]',
                         tarkenne     = '$ahrow[tarkenne]',
                         laatija      = '$kukarow[kuka]',
                         luontiaika   = now()";
            $ahinsertresult = pupe_query($ahinsert);

            synkronoi($kukarow["yhtio"], "asiakkaan_avainsanat", mysql_insert_id($GLOBALS["masterlink"]), "", "");
          }
        }
      }

      // !!!!!!!! ASIAKASLIITE OSIO !!!!!!!!!!!!
      $hquery = "SELECT *
                 FROM liitetiedostot
                 WHERE yhtio      = '$kukarow[yhtio]'
                 AND liitos       = 'asiakas'
                 AND liitostunnus = '$asrow[tunnus]'";
      $hresult = pupe_query($hquery);

      if (mysql_num_rows($hresult) == 0) {
        echo "<font class='error'>".t("Ei löytynyt liitteitä asiakkaalta")."</font><br>";
      }
      else {
        echo "<font class='ok'>".t("Löytyi liitteitä asiakkaalta")."</font><br>";
        while ($ahrow = mysql_fetch_assoc($hresult)) {

          $ahrow['filename'] = mysql_real_escape_string($ahrow['filename']);

          $tarksql = "SELECT *
                      FROM liitetiedostot
                      where yhtio         = '$kukarow[yhtio]'
                      and liitos          = '$ahrow[liitos]'
                      and liitostunnus    = '$jrow[tunnus]'
                      and selite          = '$ahrow[selite]'
                      and kieli           = '$ahrow[kieli]'
                      and filename        = '$ahrow[filename]'
                      and filesize        = '$ahrow[filesize]'
                      and filetype        = '$ahrow[filetype]'
                      and image_width     = '$ahrow[image_width]'
                      and image_height    = '$ahrow[image_height]'
                      and image_bits      = '$ahrow[image_bits]'
                      and image_channels  = '$ahrow[image_channels]'
                      and kayttotarkoitus = '$ahrow[kayttotarkoitus]'
                      and jarjestys       = '$ahrow[jarjestys]'";
          $tarkesult = pupe_query($tarksql);
          $ahy = mysql_num_rows($tarkesult);

          if ($ahy == 0) {
            $ahinsert = "INSERT INTO liitetiedostot SET
                         yhtio           = '$kukarow[yhtio]',
                         liitos          = '$ahrow[liitos]',
                         liitostunnus    = '$jrow[tunnus]',
                         data            = '".mysql_real_escape_string($ahrow["data"])."',
                         selite          = '$ahrow[selite]',
                         kieli           = '$ahrow[kieli]',
                         filename        = '$ahrow[filename]',
                         filesize        = '$ahrow[filesize]',
                         filetype        = '$ahrow[filetype]',
                         image_width     = '$ahrow[image_width]',
                         image_height    = '$ahrow[image_height]',
                         image_bits      = '$ahrow[image_bits]',
                         image_channels  = '$ahrow[image_channels]',
                         kayttotarkoitus = '$ahrow[kayttotarkoitus]',
                         jarjestys       = '$ahrow[jarjestys]',
                         laatija         = '$kukarow[kuka]',
                         luontiaika      = now()";
            $ahinsertresult = pupe_query($ahinsert);
          }
        }
      }

      // !!!!!!!! PUUN_ALKIO OSIO !!!!!!!!!!!!
      $hquery = "SELECT *, if(kutsuja = '', laji, kutsuja) AS kutsuja
                 FROM puun_alkio
                 WHERE yhtio = '$kukarow[yhtio]'
                 AND laji    = 'Asiakas'
                 AND liitos  = '$asrow[tunnus]'";
      $hresult = pupe_query($hquery);

      if (mysql_num_rows($hresult) == 0) {
        echo "<font class='error'>".t("Ei löytynyt dynaamisen puun liitoksia asiakkaalta")."</font><br>";
      }
      else {
        echo "<font class='ok'>".t("Löytyi dynaamisen puun liitoksia asiakkaalta")."</font><br>";
        while ($ahrow = mysql_fetch_assoc($hresult)) {

          $tarksql = "SELECT *
                      FROM puun_alkio
                      where yhtio     = '$kukarow[yhtio]'
                      and liitos      = '$jrow[tunnus]'
                      and kieli       = '$ahrow[kieli]'
                      and laji        = '$ahrow[laji]'
                      and kutsuja     = '{$ahrow['kutsuja']}'
                      and puun_tunnus = '$ahrow[puun_tunnus]'";
          $tarkesult = pupe_query($tarksql);
          $ahy = mysql_num_rows($tarkesult);

          if ($ahy == 0) {

            $ahinsert = "INSERT INTO puun_alkio SET
                         yhtio       = '$kukarow[yhtio]',
                         liitos      = '$jrow[tunnus]',
                         kieli       = '$ahrow[kieli]',
                         laji        = '$ahrow[laji]',
                         kutsuja     = '{$ahrow['kutsuja']}',
                         puun_tunnus = '$ahrow[puun_tunnus]',
                         jarjestys   = '$ahrow[jarjestys]',
                         laatija     = '$kukarow[kuka]',
                         luontiaika  = now()";
            $ahinsertresult = pupe_query($ahinsert);
          }
        }
      }

      // !!!!!!!! HUOLTO OSIO !!!!!!!!!!!!
      if (table_exists('huolto')) {
        $hquery = "SELECT *
                   FROM huolto
                   WHERE yhtio        = '{$kukarow['yhtio']}'
                   AND asiakas_tunnus = '{$asrow['tunnus']}'";
        $hresult = pupe_query($hquery);

        if (mysql_num_rows($hresult) == 0) {
          echo "<font class='error'>".t("Ei löytynyt huoltoja asiakkaalta")."</font><br>";
        }
        else {
          echo "<font class='ok'>".t("Löytyi huoltoja asiakkaalta")."</font><br>";
          while ($ahrow = mysql_fetch_assoc($hresult)) {

            $ahrow = array_map('addslashes', $ahrow);

            $tarksql = "SELECT *
                        FROM huolto
                        WHERE yhtio              = '{$kukarow['yhtio']}'
                        AND tila                 = '{$ahrow['tila']}'
                        AND kommentti            = '{$ahrow['kommentti']}'
                        AND mittarilukema        = '{$ahrow['mittarilukema']}'
                        AND kokohinta            = '{$ahrow['kokohinta']}'
                        AND lasku_tunnus         = {$ahrow['lasku_tunnus']}
                        AND laskuaika            = '{$ahrow['laskuaika']}'
                        AND asiakas_tunnus       = {$jrow['tunnus']}
                        AND huoltoauto_tunnus    = {$ahrow['huoltoauto_tunnus']}
                        AND huoltoasiakas_tunnus = {$ahrow['huoltoasiakas_tunnus']}
                        AND reknro               = '{$ahrow['reknro']}'
                        AND autoid               = '{$ahrow['autoid']}'
                        AND mid                  = '{$ahrow['mid']}'
                        AND link_sg              = '{$ahrow['link_sg']}'
                        AND link_rt              = '{$ahrow['link_rt']}'
                        AND link_td              = '{$ahrow['link_td']}'";
            $tarkesult = pupe_query($tarksql);

            if (mysql_num_rows($tarkesult) == 0) {
              $ahinsert = "INSERT INTO huolto SET
                           yhtio                = '{$kukarow['yhtio']}',
                           tila                 = '{$ahrow['tila']}',
                           kommentti            = '{$ahrow['kommentti']}',
                           mittarilukema        = '{$ahrow['mittarilukema']}',
                           kokohinta            = '{$ahrow['kokohinta']}',
                           lasku_tunnus         = {$ahrow['lasku_tunnus']},
                           laskuaika            = '{$ahrow['laskuaika']}',
                           asiakas_tunnus       = {$jrow['tunnus']},
                           huoltoauto_tunnus    = {$ahrow['huoltoauto_tunnus']},
                           huoltoasiakas_tunnus = {$ahrow['huoltoasiakas_tunnus']},
                           reknro               = '{$ahrow['reknro']}',
                           autoid               = '{$ahrow['autoid']}',
                           mid                  = '{$ahrow['mid']}',
                           link_sg              = '{$ahrow['link_sg']}',
                           link_rt              = '{$ahrow['link_rt']}',
                           link_td              = '{$ahrow['link_td']}',
                           laatija              = '{$kukarow['kuka']}',
                           luontiaika           = NOW(),
                           muuttaja             = '{$kukarow['kuka']}',
                           muutosaika           = NOW()";
              pupe_query($ahinsert);
            }
          }
        }
      }

      // !!!!!!!! HUOLTO_ASIAKAS OSIO !!!!!!!!!!!!
      if (table_exists('huolto_asiakas')) {
        $hquery = "SELECT *
                   FROM huolto_asiakas
                   WHERE yhtio        = '{$kukarow['yhtio']}'
                   AND asiakas_tunnus = '{$asrow['tunnus']}'";
        $hresult = pupe_query($hquery);

        if (mysql_num_rows($hresult) == 0) {
          echo "<font class='error'>".t("Ei löytynyt huolto asiakkaita asiakkaalta")."</font><br>";
        }
        else {
          echo "<font class='ok'>".t("Löytyi huolto asiakkaita asiakkaalta")."</font><br>";
          while ($ahrow = mysql_fetch_assoc($hresult)) {

            $ahrow = array_map('addslashes', $ahrow);

            $tarksql = "SELECT *
                        FROM huolto_asiakas
                        WHERE yhtio        = '{$kukarow['yhtio']}'
                        AND nimi           = '{$ahrow['nimi']}'
                        AND etunimi        = '{$ahrow['etunimi']}'
                        AND sukunimi       = '{$ahrow['sukunimi']}'
                        AND ytunnus        = '{$ahrow['ytunnus']}'
                        AND osoite         = '{$ahrow['osoite']}'
                        AND postino        = '{$ahrow['postino']}'
                        AND postitp        = '{$ahrow['postitp']}'
                        AND puhelin        = '{$ahrow['puhelin']}'
                        AND email          = '{$ahrow['email']}'
                        AND markkinointi   = '{$ahrow['markkinointi']}'
                        AND asiakas_tunnus = '{$jrow['tunnus']}'";
            $tarkesult = pupe_query($tarksql);

            if (mysql_num_rows($tarkesult) == 0) {
              $ahinsert = "INSERT INTO huolto_asiakas SET
                           yhtio          = '{$kukarow['yhtio']}',
                           nimi           = '{$ahrow['nimi']}',
                           etunimi        = '{$ahrow['etunimi']}',
                           sukunimi       = '{$ahrow['sukunimi']}',
                           ytunnus        = '{$ahrow['ytunnus']}',
                           osoite         = '{$ahrow['osoite']}',
                           postino        = '{$ahrow['postino']}',
                           postitp        = '{$ahrow['postitp']}',
                           puhelin        = '{$ahrow['puhelin']}',
                           email          = '{$ahrow['email']}',
                           markkinointi   = '{$ahrow['markkinointi']}',
                           asiakas_tunnus = '{$jrow['tunnus']}',
                           laatija        = '{$kukarow['kuka']}',
                           luontiaika     = NOW(),
                           muuttaja       = '{$kukarow['kuka']}',
                           muutosaika     = NOW()";
              pupe_query($ahinsert);
            }
          }
        }
      }

      // !!!!!!!! HUOLTO_ASIAKAS_OMARIVI OSIO !!!!!!!!!!!!
      if (table_exists('huolto_asiakas_omarivi')) {
        $hquery = "SELECT *
                   FROM huolto_asiakas_omarivi
                   WHERE yhtio        = '{$kukarow['yhtio']}'
                   AND asiakas_tunnus = '{$asrow['tunnus']}'";
        $hresult = pupe_query($hquery);

        if (mysql_num_rows($hresult) == 0) {
          echo "<font class='error'>".t("Ei löytynyt huolto asiakkaita asiakkaalta")."</font><br>";
        }
        else {
          echo "<font class='ok'>".t("Löytyi huolto asiakkaita asiakkaalta")."</font><br>";
          while ($ahrow = mysql_fetch_assoc($hresult)) {

            $ahrow = array_map('addslashes', $ahrow);

            $tarksql = "SELECT *
                        FROM huolto_asiakas_omarivi
                        WHERE yhtio         = '{$kukarow['yhtio']}'
                        AND asiakas_tunnus  = '{$jrow['tunnus']}'
                        AND tyyppi          = '{$ahrow['tyyppi']}'
                        AND tuoteno         = '{$ahrow['tuoteno']}'
                        AND ref             = '{$ahrow['ref']}'
                        AND nimitys         = '{$ahrow['nimitys']}'
                        AND merkki          = '{$ahrow['merkki']}'
                        AND oljy_laatu      = '{$ahrow['oljy_laatu']}'
                        AND oljy_luokitus   = '{$ahrow['oljy_luokitus']}'
                        AND maara           = '{$ahrow['maara']}'
                        AND hinta           = '{$ahrow['hinta']}'
                        AND alv             = '{$ahrow['alv']}'
                        AND puh             = '{$ahrow['puh']}'
                        AND email           = '{$ahrow['email']}'
                        AND omistaja_tunnus = '{$ahrow['omistaja_tunnus']}'
                        AND info            = '{$ahrow['info']}'
                        AND myyntihinta     = '{$ahrow['myyntihinta']}'";
            $tarkesult = pupe_query($tarksql);

            if (mysql_num_rows($tarkesult) == 0) {
              $ahinsert = "INSERT INTO huolto_asiakas_omarivi SET
                           yhtio           = '{$kukarow['yhtio']}',
                           asiakas_tunnus  = '{$jrow['tunnus']}',
                           tyyppi          = '{$ahrow['tyyppi']}',
                           tuoteno         = '{$ahrow['tuoteno']}',
                           ref             = '{$ahrow['ref']}',
                           nimitys         = '{$ahrow['nimitys']}',
                           merkki          = '{$ahrow['merkki']}',
                           oljy_laatu      = '{$ahrow['oljy_laatu']}',
                           oljy_luokitus   = '{$ahrow['oljy_luokitus']}',
                           maara           = '{$ahrow['maara']}',
                           hinta           = '{$ahrow['hinta']}',
                           alv             = '{$ahrow['alv']}',
                           puh             = '{$ahrow['puh']}',
                           email           = '{$ahrow['email']}',
                           omistaja_tunnus = '{$ahrow['omistaja_tunnus']}',
                           info            = '{$ahrow['info']}',
                           myyntihinta     = '{$ahrow['myyntihinta']}',
                           laatija         = '{$kukarow['kuka']}',
                           luontiaika      = NOW(),
                           muuttaja        = '{$kukarow['kuka']}',
                           muutosaika      = NOW()";
              pupe_query($ahinsert);
            }
          }
        }
      }

      // !!!!!!!! HUOLTO_AUTO OSIO !!!!!!!!!!!!
      if (table_exists('huolto_auto')) {
        $hquery = "SELECT *
                   FROM huolto_auto
                   WHERE yhtio        = '{$kukarow['yhtio']}'
                   AND asiakas_tunnus = '{$asrow['tunnus']}'";
        $hresult = pupe_query($hquery);

        if (mysql_num_rows($hresult) == 0) {
          echo "<font class='error'>".t("Ei löytynyt huolto autoja asiakkaalta")."</font><br>";
        }
        else {
          echo "<font class='ok'>".t("Löytyi huolto autoja asiakkaalta")."</font><br>";
          while ($ahrow = mysql_fetch_assoc($hresult)) {

            $ahrow = array_map('addslashes', $ahrow);

            $tarksql = "SELECT *
                        FROM huolto_auto
                        WHERE yhtio              = '{$kukarow['yhtio']}'
                        AND reknro               = '{$ahrow['reknro']}'
                        AND valmistenumero       = '{$ahrow['valmistenumero']}'
                        AND nimi                 = '{$ahrow['nimi']}'
                        AND atyyppi              = '{$ahrow['atyyppi']}'
                        AND autoid               = '{$ahrow['autoid']}'
                        AND mid                  = '{$ahrow['mid']}'
                        AND mittarilukema        = '{$ahrow['mittarilukema']}'
                        AND link_sg              = '{$ahrow['link_sg']}'
                        AND link_rt              = '{$ahrow['link_rt']}'
                        AND link_td              = '{$ahrow['link_td']}'
                        AND asiakas_tunnus       = '{$jrow['tunnus']}'
                        AND huoltoasiakas_tunnus = '{$ahrow['huoltoasiakas_tunnus']}'";
            $tarkesult = pupe_query($tarksql);

            if (mysql_num_rows($tarkesult) == 0) {
              $ahinsert = "INSERT INTO huolto_auto SET
                           yhtio                = '{$kukarow['yhtio']}',
                           reknro               = '{$ahrow['reknro']}',
                           valmistenumero       = '{$ahrow['valmistenumero']}',
                           nimi                 = '{$ahrow['nimi']}',
                           atyyppi              = '{$ahrow['atyyppi']}',
                           autoid               = '{$ahrow['autoid']}',
                           mid                  = '{$ahrow['mid']}',
                           mittarilukema        = '{$ahrow['mittarilukema']}',
                           link_sg              = '{$ahrow['link_sg']}',
                           link_rt              = '{$ahrow['link_rt']}',
                           link_td              = '{$ahrow['link_td']}',
                           asiakas_tunnus       = '{$jrow['tunnus']}',
                           huoltoasiakas_tunnus = '{$ahrow['huoltoasiakas_tunnus']}',
                           laatija              = '{$kukarow['kuka']}',
                           luontiaika           = NOW(),
                           muuttaja             = '{$kukarow['kuka']}',
                           muutosaika           = NOW()";
              pupe_query($ahinsert);
            }
          }
        }
      }

      // !!!!!!!! HUOLTO_RIVI OSIO !!!!!!!!!!!!
      if (table_exists('huolto_rivi')) {
        $hquery = "SELECT *
                   FROM huolto_rivi
                   WHERE yhtio        = '{$kukarow['yhtio']}'
                   AND asiakas_tunnus = '{$asrow['tunnus']}'";
        $hresult = pupe_query($hquery);

        if (mysql_num_rows($hresult) == 0) {
          echo "<font class='error'>".t("Ei löytynyt huolto rivejä asiakkaalta")."</font><br>";
        }
        else {
          echo "<font class='ok'>".t("Löytyi huolto rivejä asiakkaalta")."</font><br>";
          while ($ahrow = mysql_fetch_assoc($hresult)) {

            $ahrow = array_map('addslashes', $ahrow);

            $tarksql = "SELECT *
                        FROM huolto_rivi
                        WHERE yhtio        = '{$kukarow['yhtio']}'
                        AND tyyppi         = '{$ahrow['tyyppi']}'
                        AND link           = '{$ahrow['link']}'
                        AND seq            = '{$ahrow['seq']}'
                        AND addserv        = '{$ahrow['addserv']}'
                        AND op_ref         = '{$ahrow['op_ref']}'
                        AND group_id       = '{$ahrow['group_id']}'
                        AND kesto          = '{$ahrow['kesto']}'
                        AND tuntihinta     = '{$ahrow['tuntihinta']}'
                        AND nettohinta     = '{$ahrow['nettohinta']}'
                        AND alv            = '{$ahrow['alv']}'
                        AND otsikko        = '{$ahrow['otsikko']}'
                        AND asiakas_tunnus = '{$jrow['tunnus']}'
                        AND huolto_tunnus  = '{$ahrow['huolto_tunnus']}'";
            $tarkesult = pupe_query($tarksql);

            if (mysql_num_rows($tarkesult) == 0) {
              $ahinsert = "INSERT INTO huolto_rivi SET
                           yhtio          = '{$kukarow['yhtio']}',
                           tyyppi         = '{$ahrow['tyyppi']}',
                           link           = '{$ahrow['link']}',
                           seq            = '{$ahrow['seq']}',
                           addserv        = '{$ahrow['addserv']}',
                           op_ref         = '{$ahrow['op_ref']}',
                           group_id       = '{$ahrow['group_id']}',
                           kesto          = '{$ahrow['kesto']}',
                           tuntihinta     = '{$ahrow['tuntihinta']}',
                           nettohinta     = '{$ahrow['nettohinta']}',
                           alv            = '{$ahrow['alv']}',
                           otsikko        = '{$ahrow['otsikko']}',
                           asiakas_tunnus = '{$jrow['tunnus']}',
                           huolto_tunnus  = '{$ahrow['huolto_tunnus']}',
                           laatija        = '{$kukarow['kuka']}',
                           luontiaika     = NOW(),
                           muuttaja       = '{$kukarow['kuka']}',
                           muutosaika     = NOW()";
              pupe_query($ahinsert);
            }
          }
        }
      }

      // !!!!!!!! HUOLTO_RIVI_TUOTE OSIO !!!!!!!!!!!!
      if (table_exists('huolto_rivi_tuote')) {
        $hquery = "SELECT *
                   FROM huolto_rivi_tuote
                   WHERE yhtio        = '{$kukarow['yhtio']}'
                   AND asiakas_tunnus = '{$asrow['tunnus']}'";
        $hresult = pupe_query($hquery);

        if (mysql_num_rows($hresult) == 0) {
          echo "<font class='error'>".t("Ei löytynyt huolto rivi tuotteita asiakkaalta")."</font><br>";
        }
        else {
          echo "<font class='ok'>".t("Löytyi huolto rivi tuotteita asiakkaalta")."</font><br>";
          while ($ahrow = mysql_fetch_assoc($hresult)) {

            $ahrow = array_map('addslashes', $ahrow);

            $tarksql = "SELECT *
                        FROM huolto_rivi_tuote
                        WHERE yhtio           = '{$kukarow['yhtio']}'
                        AND tyyppi            = '{$ahrow['tyyppi']}'
                        AND tuoteno           = '{$ahrow['tuoteno']}'
                        AND maara             = '{$ahrow['maara']}'
                        AND tilattu_maara     = '{$ahrow['tilattu_maara']}'
                        AND hinta             = '{$ahrow['hinta']}'
                        AND alv               = '{$ahrow['alv']}'
                        AND op_ref            = '{$ahrow['op_ref']}'
                        AND nimitys           = '{$ahrow['nimitys']}'
                        AND merkki            = '{$ahrow['merkki']}'
                        AND oljy_laatu        = '{$ahrow['oljy_laatu']}'
                        AND oljy_luokitus     = '{$ahrow['oljy_luokitus']}'
                        AND tilaaja           = '{$ahrow['tilaaja']}'
                        AND tilausaika        = '{$ahrow['tilausaika']}'
                        AND asiakas_tunnus    = '{$jrow['tunnus']}'
                        AND huoltorivi_tunnus = '{$ahrow['huoltorivi_tunnus']}'";
            $tarkesult = pupe_query($tarksql);

            if (mysql_num_rows($tarkesult) == 0) {
              $ahinsert = "INSERT INTO huolto_rivi_tuote SET
                           yhtio             = '{$kukarow['yhtio']}',
                           tyyppi            = '{$ahrow['tyyppi']}',
                           tuoteno           = '{$ahrow['tuoteno']}',
                           maara             = '{$ahrow['maara']}',
                           tilattu_maara     = '{$ahrow['tilattu_maara']}',
                           hinta             = '{$ahrow['hinta']}',
                           alv               = '{$ahrow['alv']}',
                           op_ref            = '{$ahrow['op_ref']}',
                           nimitys           = '{$ahrow['nimitys']}',
                           merkki            = '{$ahrow['merkki']}',
                           oljy_laatu        = '{$ahrow['oljy_laatu']}',
                           oljy_luokitus     = '{$ahrow['oljy_luokitus']}',
                           tilaaja           = '{$ahrow['tilaaja']}',
                           tilausaika        = '{$ahrow['tilausaika']}',
                           asiakas_tunnus    = '{$jrow['tunnus']}',
                           huoltorivi_tunnus = '{$ahrow['huoltorivi_tunnus']}',
                           laatija           = '{$kukarow['kuka']}',
                           luontiaika        = NOW(),
                           muuttaja          = '{$kukarow['kuka']}',
                           muutosaika        = NOW()";
              pupe_query($ahinsert);
            }
          }
        }
      }

      // !!!!!! Asiakasmemot, kalenterit, siellä olevat liitetiedostot menee kalenterintunnuksen mukaan, joten niiitä ei tarvitse erikseen päivittää
      $memohaku = "SELECT liitostunnus, asiakas
                   FROM kalenteri
                   WHERE yhtio      = '$kukarow[yhtio]'
                   AND liitostunnus = '$asrow[tunnus]'";
      $memores = pupe_query($memohaku);
      $ahy = mysql_num_rows($memores);

      if ($ahy != 0) {
        echo "<font class='ok'>".t("Päivitettiin CRM-tiedot asiakkaalta")."</font><br>";

        $memosql = "UPDATE kalenteri
                    SET asiakas = '$jrow[ytunnus]', liitostunnus = '$jrow[tunnus]'
                    WHERE yhtio      = '$kukarow[yhtio]'
                    AND liitostunnus = '$asrow[tunnus]'";
        $memores = pupe_query($memosql);
      }
      else {
        echo "<font class='error'>".t("Ei löytynyt CRM-tietoja asiakkaalta")."</font><br>";
      }

      // !!!!!!!! LASKUTUS OSIO !!!!!!!!!!!!
      $lquery = " SELECT group_concat(tunnus) tunnukset FROM lasku WHERE yhtio ='$kukarow[yhtio]' AND liitostunnus = '$asrow[tunnus]' AND tila not IN ('G','O','K','H','Y','M','P','Q','X')";
      $lresult = pupe_query($lquery);
      $lrow = mysql_fetch_assoc($lresult);

      if (trim($lrow['tunnukset']) != "") {
        $lupdate = "UPDATE lasku SET liitostunnus = '$jrow[tunnus]' WHERE yhtio ='$kukarow[yhtio]' and liitostunnus='$asrow[tunnus]' AND tunnus IN ($lrow[tunnukset])";
        $lupdateresult = pupe_query($lupdate);
        echo "<font class='ok'>".t("Asiakkaan laskut päivitettiin")."</font><br><br>";
      }
      else {
        echo "<font class='error'>".t("Ei löytynyt laskuja asiakkaalta")."</font><br><br>";
      }

      $_query = "UPDATE kuka SET
                 oletus_asiakas     = '{$jataminut}'
                 WHERE yhtio        = '{$kukarow['yhtio']}'
                 AND oletus_asiakas = '{$asrow['tunnus']}'";
      $upd_res = pupe_query($_query);

      $_query = "UPDATE kuka SET
                 oletus_asiakastiedot     = '{$jataminut}'
                 WHERE yhtio              = '{$kukarow['yhtio']}'
                 AND oletus_asiakastiedot = '{$asrow['tunnus']}'";
      $upd_res = pupe_query($_query);

      // Muutetaan asiakkaan laji = 'P', jätetään varmuudeksi talteen, toistaiseksi.
      $paivitys = "UPDATE asiakas set laji='P' where yhtio ='$kukarow[yhtio]' AND tunnus = '$asrow[tunnus]'";
      $pairesult = pupe_query($paivitys);

      synkronoi($kukarow["yhtio"], "asiakas", $asrow["tunnus"], $asrow, "");

      $historia .= "+ ".t("Asiakas").": ".$asrow["nimi"] .", ".t("ytunnus").": ".$asrow["ytunnus"] .", ".t("asiakasnro").": ". $asrow["asiakasnro"] ."<br />";

    }//if
  }//foreach

  $historia_tietokantaan = str_replace('<br />', '\\n', $historia);

  $kysely = "INSERT INTO kalenteri
             SET tapa    = '".t("Muu syy (muista selite!)")."',
             asiakas      = '$jrow[ytunnus]',
             liitostunnus = '$jrow[tunnus]',
             kuka         = '$kukarow[kuka]',
             yhtio        = '$kukarow[yhtio]',
             tyyppi       = 'Memo',
             kentta01     = '$historia_tietokantaan',
             pvmalku      = now(),
             laatija      = '$kukarow[kuka]',
             luontiaika   = now()";
  $result = pupe_query($kysely);

  return $historia;
}

function hae_asiakastunnus($tunnukset) {
  global $kukarow;

  if ($tunnukset['ASIAKASTUNNUS'] != '') {
    $query = "SELECT tunnus
              FROM asiakas
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$tunnukset['ASIAKASTUNNUS']}'";
  }
  else {
    $query = "SELECT tunnus
              FROM asiakas
              WHERE yhtio        = '{$kukarow['yhtio']}'
              AND ytunnus        = '{$tunnukset['YTUNNUS']}'
              AND ovttunnus      = '{$tunnukset['OVTTUNNUS']}'
              AND toim_ovttunnus = '{$tunnukset['TOIM_OVTTUNNUS']}'";
  }

  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {
    $tunnus = mysql_result($result, 0);
    if ($tunnus != '') return $tunnus;
  }
  return false;
}
