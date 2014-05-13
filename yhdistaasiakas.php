<?php
require ("inc/parametrit.inc");

if (!isset($konserni))  $konserni = '';
if (!isset($tee))     $tee = '';
if (!isset($oper))    $oper = '';
if (!isset($ojarj))    $ojarj = '';
if (!isset($tapa))    $tapa = 'a';

echo "<font class='head'>".t("Yhdist� asiakkaita")."</font><hr>";

if (isset($_FILES['userfile']) and is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE and $tee == 'YHDISTA_TIEDOSTOSTA') {

    $historia = '';

    $path_parts = pathinfo($_FILES['userfile']['name']);
    $ext = strtoupper($path_parts['extension']);

    if ($_FILES['userfile']['size']==0) {
      die ("<font class='error'><br>".t("Tiedosto on tyhj�")."!</font>");
    }

    $retval = tarkasta_liite("userfile", array("XLSX","XLS","ODS","SLK","XML","GNUMERIC","CSV","TXT","DATAIMPORT"));

    if ($retval !== TRUE) {
      die ("<font class='error'><br>".t("V��r� tiedostomuoto")."!</font>");
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

      if(isset($r['ASIAKASTUNNUS']))    $tunnukset['ASIAKASTUNNUS'] = $r['ASIAKASTUNNUS'];
      if(isset($r['YTUNNUS']))          $tunnukset['YTUNNUS'] = $r['YTUNNUS'];
      if(isset($r['OVTTUNNUS']))        $tunnukset['OVTTUNNUS'] = $r['OVTTUNNUS'];
      if(isset($r['TOIM_OVTTUNNUS']))   $tunnukset['TOIM_OVTTUNNUS'] = $r['TOIM_OVTTUNNUS'];

      if ( $tunnus = hae_asiakastunnus($tunnukset) ) {

          if ( strtoupper($r['JATA_TAMA']) != 'X' ) {
            $yhdistykset[$yr]['yhdista'][] = $tunnus;
          }
          elseif( isset($yhdistykset[$yr]) and count($yhdistykset[$yr]['yhdista']) > 0 ){
            $yhdistykset[$yr]['jata'] = $tunnus;
            $yr++;
          }
      }
  }

  if ($yr == 0) {
    echo t("Ei l�ytynyt yhdistett�vi� asiakkaita")."...<br />";
  }

  foreach ($yhdistykset as $y) {
      if(!isset($y['spessut'])) {
        $y['spessut'] = 0;
      }
        echo yhdista_asiakkaita( $y['jata'], $y['yhdista'] );
        echo '<hr>';
    }
  echo "<br /><br /><form><input type='submit' value='" . t("Yhdist� lis��") . "' /></form>";
}

if ($tee == 'YHDISTA' and $jataminut != '' and count($yhdista) != '') {
  echo yhdista_asiakkaita( $jataminut, $yhdista );
    echo "<br /><br /><form><input type='submit' value='" . t("Yhdist� lis��") . "' /></form>";
}

if ( ( !isset($jataminut) and !isset($yhdista) ) and (!isset($_FILES['userfile']) or is_uploaded_file($_FILES['userfile']['tmp_name']) === false ) ) {

    echo "<br><form method='post' name='sendfile' enctype='multipart/form-data'>";
    echo "<input type='hidden' name='tee' value='YHDISTA_TIEDOSTOSTA'>";
    echo t("Lue yhdistett�v�t asiakkaat tiedostosta")."...<br /><br />";

    echo "<table>";
    echo "<tr><th>" . t("Asiakkaan valintatapa") . ":</th><td>";
    echo "<select name='tapa' onChange='submit();' >";

    if($tapa == 'b'){$b_sel = 'selected'; $a_sel = '';}else{$a_sel = 'selected'; $b_sel = '';}

    echo "<option value='a' $a_sel>" . t("Asiakastunnus") . "</option>";
    echo "<option value='b' $b_sel>" . t("ytunnus") . ", " .  t("ovttunnus") . ", " .  t("toim_ovttunnus") . "</option></td></tr>";
    echo "</table><br />";

    echo "<table>";
    echo "<tr><th colspan='99'>" . t("Excel-tiedosto seuraavin tiedoin") . ":</th></tr>";

    if($tapa == 'b'){
      echo "<tr><td>ytunnus</td><td>" .  t("ovttunnus") . "</td><td>" . t("toim_ovttunnus") . "</td><td>jata_tama</td></tr>";
    }
    else{
      echo "<tr><td>" . t("asiakastunnus") . "</td><td>" . t("jata_tama") . "</td></tr>";
    }

    echo "</table><br />";

    echo t("\"jata_tama\" kentt��n laitetaan arvoksi \"X\" niille riveille joihin edelliset rivit halutaan yhdist��. Jos yhdistett�vi� rivej� on paljon, saattaa toimenpide kest�� kauan").".<br /><br />";


    echo "<table>";
    echo "<tr><th>".t("Valitse tiedosto").":</th>";
    echo "<td><input name='userfile' type='file'></td>";
    echo "<td class='back'><input type='submit' value='".t("Jatka")."'></td></tr></table><br /></form>";

    echo t("Voit my�s valita yhdistett�v�t asiakkaat listasta.")."<br><br>";

    echo "<form method='post'>";
    echo "<input type='hidden' name='tee' value='YHDISTA'>";

    $monivalintalaatikot = array("ASIAKASOSASTO", "ASIAKASRYHMA", "ASIAKASPIIRI", "ASIAKASMYYJA", "ASIAKASTILA", "<br>DYNAAMINEN_ASIAKAS");
    $monivalintalaatikot_normaali = array();

    require ("tilauskasittely/monivalintalaatikot.inc");

    $kentat    = "asiakas.ytunnus::asiakas.ytunnus::asiakas.nimi>>asiakas.toim_nimi::asiakas.osoite>>asiakas.toim_osoite::asiakas.postino>>asiakas.toim_postino::asiakas.postitp>>asiakas.toim_postitp::asiakas.asiakasnro";
    $jarjestys = 'ytunnus, nimi, selaus, tunnus';

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

    $lisa .= " and asiakas.laji != 'P' ";

    $query = "  SELECT
        asiakas.tunnus,
        asiakas.ytunnus,
        concat(asiakas.nimi ,'<br>', asiakas.toim_nimi,'<br>',  asiakas.laskutus_nimi) 'nimi'  ,
        concat(asiakas.osoite ,'<br>', asiakas.toim_osoite,'<br>',  asiakas.laskutus_osoite) 'osoite',
        concat(asiakas.postino, '<br>', asiakas.toim_postino, asiakas.laskutus_postino) 'postino',
        concat(asiakas.postitp, '<br>', asiakas.toim_postitp, asiakas.laskutus_postitp) 'postitp',
        asiakas.asiakasnro,
        asiakas.yhtio
        FROM asiakas
        WHERE asiakas.yhtio = '$kukarow[yhtio]'
        $lisa
        ORDER BY $jarjestys
        LIMIT 500";
    $result = pupe_query($query);

    echo "<br><table>";
    echo "<tr>";

    for ($i = 1; $i < mysql_num_fields($result)-1; $i++) { // HAKUKENT�T
      echo "<th><a href='$PHP_SELF?ojarj=".mysql_field_name($result,$i).$ulisa."'>" . t(mysql_field_name($result,$i)) . "</a>";

      if  (mysql_field_len($result,$i)>20) $size='20';
      elseif  (mysql_field_len($result,$i)<=20)  $size='10';
      else  $size='10';

      if (!isset($haku[$i])) $haku[$i] = '';

      echo "<br><input type='text' name='haku[$i]' value='$haku[$i]' size='$size' maxlength='" . mysql_field_len($result,$i) ."'>";
      echo "</th>";
    }

    echo "<th>".t("Yhdist�")."</th><th>".t("j�t� t�m�")."</th>";
    echo "<td class='back'>&nbsp;&nbsp;<input type='Submit' value='".t("Etsi / yhdist�")."'></td></tr>\n\n";

    $kalalask = 1;

    while ($trow = mysql_fetch_array ($result)) { // tiedot
      echo "<tr class='aktiivi'>";

      for ($i=1; $i<mysql_num_fields($result)-1; $i++) {

          if ($i == 1) {
            if (trim($trow[1]) == '') $trow[1] = t("*tyhj�*");
            echo "<td><a name='2_$kalalask' href='".$palvelin2."yllapito.php?toim=asiakas&tunnus=$trow[tunnus]&lopetus=".$palvelin2."yhdistaasiakas.php////ojarj=$ojarj".str_replace("&", "//", $ulisa)."///2_$kalalask'>$trow[$i]</a></td>";
          }
          elseif (mysql_field_name($result,$i) == 'ytunnus') {
            echo "<td><a name='2_$kalalask' href='".$palvelin2."yllapito.php?toim=asiakas&tunnus=$trow[tunnus]&lopetus=".$palvelin2."yhdistaasiakas.php////ojarj=$ojarj".str_replace("&", "//", $ulisa)."///2_$kalalask'>$trow[$i]</a></td>";
          }
          else {
            echo "<td>$trow[$i]</td>";
          }
      }

      echo "<td><input type='checkbox' name='yhdista[$trow[tunnus]]' value='$trow[tunnus]' $sel/></td>";
      echo "<td><input type='radio' name='jataminut' value='$trow[tunnus]'/></td>";
      echo "</tr>\n\n";

      $kalalask++;
    }

    echo "</table><br><br>";

    echo "<input type='submit' value='".t("Yhdist� asiakkaat")."'>";
    echo "</form>";

}

require ("inc/footer.inc");

function yhdista_asiakkaita($jataminut, $yhdista) {
  global $kukarow;

    // t�ss� on j�tett�v�n asiakkaan tiedot
    $jquery = " SELECT *
          FROM asiakas
          where yhtio = '$kukarow[yhtio]'
          and tunnus = '$jataminut' ";
    $jresult = pupe_query($jquery);
    $jrow = mysql_fetch_assoc($jresult);

    echo "<br>".t("J�tet��n asiakas").": $jrow[ytunnus] $jrow[nimi] ".$jrow['osoite']." ".$jrow['postino']." ".$jrow['postitp']."<br>";

    // Otetaan j�tett�v� pois poistettavista jos se on sinne ruksattu
    unset($yhdista[$jataminut]);

    $historia = t("Asiakkaaseen").": ". $jrow["nimi"].", ". t("ytunnus").": ". $jrow["ytunnus"].", ".t("asiakasnro").": ". $jrow["asiakasnro"] ." ".t("liitettiin seuraavat asiakkaat").": <br />";

    foreach ($yhdista as $haettava) {

      // haetaan "Yhdistett�v�n" firman tiedot esille niin saadaan oikeat parametrit.
      $asquery = "SELECT * FROM asiakas WHERE yhtio='$kukarow[yhtio]' AND tunnus = '{$haettava}'";
      $asresult = pupe_query($asquery);

      if (mysql_num_rows($asresult) == 1) {

        $asrow = mysql_fetch_assoc($asresult);

        echo "<br>".t("Yhdistet��n").": $asrow[ytunnus] $asrow[nimi] ".$asrow['osoite']." ".$asrow['postino']." ".$asrow['postitp']."<br><br>";

        // haetaan asiakashinta ensin Ytunnuksella.
        $hquery = " SELECT *
              FROM asiakashinta
              WHERE ytunnus = '$asrow[ytunnus]'
              AND asiakas = 0
              AND yhtio ='$kukarow[yhtio]'";
        $hresult = pupe_query($hquery);

        if (mysql_num_rows($hresult) == 0) {
          echo "<font class='error'>".t("Ei asiakashintoja y-tunnuksella")."</font><br>";
        }
        else {
          echo "<font class='ok'>".t("L�ytyi asiakashintoja y-tunnuksella")."</font><br>";
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
                  and laji        = '$ahrow[laji]'";
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

              synkronoi($kukarow["yhtio"], "asiakashinta", mysql_insert_id(), "", "");
            }
          }
        }

        // haetaan asiakashinta sitten asiakastunnuksella.
        $hquery = " SELECT *
              FROM asiakashinta
              WHERE asiakas = '$asrow[tunnus]'
              AND yhtio ='$kukarow[yhtio]'";
        $hresult = pupe_query($hquery);

        if (mysql_num_rows($hresult) == 0) {
          echo "<font class='error'>".t("Ei asiakashintoja asiakastunnuksella")."</font><br>";
        }
        else {
          echo "<font class='ok'>".t("L�ytyi asiakashintoja asiakastunnuksella")."</font><br>";
          while ($ahrow = mysql_fetch_assoc($hresult)) {

            // Ytunnus voi olla my�s setattu, mutta ei huomioida sit� t�ss�...
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
                  and laji        = '$ahrow[laji]'";
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

              synkronoi($kukarow["yhtio"], "asiakashinta", mysql_insert_id(), "", "");
            }
          }
        }

        // haetaan asiakasalennus ensin Ytunnuksella.
        $hquery = " SELECT *
              FROM asiakasalennus
              WHERE ytunnus = '$asrow[ytunnus]'
              AND asiakas = 0
              AND yhtio ='$kukarow[yhtio]'";
        $hresult = pupe_query($hquery);

        if (mysql_num_rows($hresult) == 0) {
          echo "<font class='error'>".t("Ei asiakasalennuksia y-tunnuksella")."</font><br>";
        }
        else {
          echo "<font class='ok'>".t("L�ytyi asiakasalennuksia y-tunnuksella")."</font><br>";
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
                  and alennus             = '$alrow[alennus]'
                  and alennuslaji     = '$alrow[alennuslaji]'
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
                  alennus             = '$alrow[alennus]',
                  alennuslaji     = '$alrow[alennuslaji]',
                  minkpl            = '$alrow[minkpl]',
                  alkupvm           = '$alrow[alkupvm]',
                  loppupvm          = '$alrow[loppupvm]',
                  laatija           = '$kukarow[kuka]',
                  luontiaika        = now()";
              $alinsertresult = pupe_query($alinsert);

              synkronoi($kukarow["yhtio"], "asiakasalennus", mysql_insert_id(), "", "");
            }
          }
        }

        // haetaan asiakasalennus sitten asiakastunnuksella.
        $hquery = " SELECT *
              FROM asiakasalennus
              WHERE asiakas = '$asrow[tunnus]'
              #AND ytunnus = ''
              AND yhtio ='$kukarow[yhtio]'";
        $hresult = pupe_query($hquery);

        if (mysql_num_rows($hresult) == 0) {
          echo "<font class='error'>".t("Ei asiakasalennuksia asiakastunnuksella")."</font><br>";
        }
        else {
          echo "<font class='ok'>".t("L�ytyi asiakasalennuksia asiakastunnuksella")."</font><br>";
          while ($alrow = mysql_fetch_assoc($hresult)) {
            // Ytunnus voi olla my�s setattu, mutta ei huomioida sit� t�ss�...
            $tarksql = "SELECT * FROM asiakasalennus
                  where yhtio           = '$kukarow[yhtio]'
                  and tuoteno           = '$alrow[tuoteno]'
                  and ryhma             = '$alrow[ryhma]'
                  and asiakas           = '$jrow[tunnus]'
                  #and ytunnus            = ''
                  and asiakas_ryhma     = '$alrow[asiakas_ryhma]'
                  and asiakas_segmentti = '$alrow[asiakas_segmentti]'
                  and piiri             = '$alrow[piiri]'
                  and alennus             = '$alrow[alennus]'
                  and alennuslaji     = '$alrow[alennuslaji]'
                  and minkpl            = '$alrow[minkpl]'
                  and monikerta           = '$alrow[monikerta]'
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
                    alennus             = '$alrow[alennus]',
                    alennuslaji     = '$alrow[alennuslaji]',
                    minkpl            = '$alrow[minkpl]',
                    monikerta           = '$alrow[monikerta]',
                    alkupvm           = '$alrow[alkupvm]',
                    loppupvm          = '$alrow[loppupvm]',
                    laatija           = '$kukarow[kuka]',
                    luontiaika        = now()";
              $alinsertresult = pupe_query($alinsert);

              synkronoi($kukarow["yhtio"], "asiakasalennus", mysql_insert_id(), "", "");
            }
          }
        }

        // !!!!!!!! ASIAKASKOMMENTTI OSIO !!!!!!!!!!!!
        $hquery = " SELECT *
              FROM asiakaskommentti
              WHERE yhtio ='$kukarow[yhtio]'
              AND ytunnus = '$asrow[ytunnus]'";
        $hresult = pupe_query($hquery);

        if (mysql_num_rows($hresult) == 0) {
          echo "<font class='error'>".t("Ei l�ytynyt asiakaskommentteja asiakkaalta")."</font><br>";
        }
        else {
          echo "<font class='ok'>".t("L�ytyi asiakaskommentteja asiakkaalta")."</font><br>";
          while ($ahrow = mysql_fetch_assoc($hresult)) {

            $tarksql = "SELECT *
                  FROM asiakaskommentti
                  where yhtio     = '$kukarow[yhtio]'
                  and kommentti   = '$ahrow[kommentti]'
                  and tuoteno     = '$ahrow[tuoteno]'
                  and ytunnus     = '$jrow[ytunnus]'";
            $tarkesult = pupe_query($tarksql);
            $ahy = mysql_num_rows($tarkesult);

            if ($ahy == 0) {
              $ahinsert = "INSERT INTO asiakaskommentti SET
                    yhtio       = '$kukarow[yhtio]',
                    kommentti   = '$ahrow[kommentti]',
                    tuoteno     = '$ahrow[tuoteno]',
                    ytunnus     = '$jrow[ytunnus]',
                    laatija     = '$kukarow[kuka]',
                    luontiaika  = now()";
              $ahinsertresult = pupe_query($ahinsert);

              synkronoi($kukarow["yhtio"], "asiakaskommentti", mysql_insert_id(), "", "");
            }
          }
        }

        // !!!!!!!! RAHTISOPIMUS OSIO !!!!!!!!!!!!
        $hquery = " SELECT *
              FROM rahtisopimukset
              WHERE yhtio = '$kukarow[yhtio]'
              AND asiakas = 0
              AND ytunnus = '$asrow[ytunnus]'";
        $hresult = pupe_query($hquery);

        if (mysql_num_rows($hresult) == 0) {
          echo "<font class='error'>".t("Ei l�ytynyt rahtisopimuksia y-tunnuksella")."</font><br>";
        }
        else {
          echo "<font class='ok'>".t("L�ytyi rahtisopimuksia y-tunnuksella")."</font><br>";
          while ($ahrow = mysql_fetch_assoc($hresult)) {

            $tarksql = "SELECT *
                  FROM rahtisopimukset
                  where yhtio       = '$kukarow[yhtio]'
                  and toimitustapa  = '$ahrow[toimitustapa]'
                  and asiakas     = 0
                  and ytunnus     = '$jrow[ytunnus]'
                  and rahtisopimus  = '$ahrow[rahtisopimus]'
                  and selite      = '$ahrow[selite]'
                  and muumaksaja    = '$ahrow[muumaksaja]'";
            $tarkesult = pupe_query($tarksql);
            $ahy = mysql_num_rows($tarkesult);

            if ($ahy == 0) {
              $ahinsert = "INSERT INTO rahtisopimukset SET
                    yhtio         = '$kukarow[yhtio]',
                    toimitustapa  = '$ahrow[toimitustapa]',
                    asiakas     = 0,
                    ytunnus     = '$jrow[ytunnus]',
                    rahtisopimus  = '$ahrow[rahtisopimus]',
                    selite      = '$ahrow[selite]',
                    muumaksaja    = '$ahrow[muumaksaja]',
                    laatija       = '$kukarow[kuka]',
                    luontiaika    = now()";
              $ahinsertresult = pupe_query($ahinsert);

              synkronoi($kukarow["yhtio"], "rahtisopimukset", mysql_insert_id(), "", "");
            }
          }
        }

        $hquery = " SELECT *
              FROM rahtisopimukset
              WHERE yhtio ='$kukarow[yhtio]'
              AND asiakas = '$asrow[tunnus]'";
        $hresult = pupe_query($hquery);

        if (mysql_num_rows($hresult) == 0) {
          echo "<font class='error'>".t("Ei l�ytynyt rahtisopimuksia asiakastunnuksella")."</font><br>";
        }
        else {
          echo "<font class='ok'>".t("L�ytyi rahtisopimuksia asiakastunnuksella")."</font><br>";
          while ($ahrow = mysql_fetch_assoc($hresult)) {

            $tarksql = "SELECT *
                  FROM rahtisopimukset
                  where yhtio       = '$kukarow[yhtio]'
                  and toimitustapa  = '$ahrow[toimitustapa]'
                  and asiakas     = '$jrow[tunnus]'
                  and rahtisopimus  = '$ahrow[rahtisopimus]'
                  and selite      = '$ahrow[selite]'
                  and muumaksaja    = '$ahrow[muumaksaja]'";
            $tarkesult = pupe_query($tarksql);
            $ahy = mysql_num_rows($tarkesult);

            if ($ahy == 0) {
              $ahinsert = "INSERT INTO rahtisopimukset SET
                    yhtio         = '$kukarow[yhtio]',
                    toimitustapa  = '$ahrow[toimitustapa]',
                    asiakas     = '$jrow[tunnus]',
                    ytunnus     = '',
                    rahtisopimus  = '$ahrow[rahtisopimus]',
                    selite      = '$ahrow[selite]',
                    muumaksaja    = '$ahrow[muumaksaja]',
                    laatija       = '$kukarow[kuka]',
                    luontiaika    = now()";
              $ahinsertresult = pupe_query($ahinsert);

              synkronoi($kukarow["yhtio"], "rahtisopimukset", mysql_insert_id(), "", "");
            }
          }
        }

        // !!!!!!!! YHTEYSHENKIL� OSIO !!!!!!!!!!!!
        $hquery = " SELECT *
              FROM yhteyshenkilo
              WHERE yhtio    = '$kukarow[yhtio]'
              AND liitostunnus = '$asrow[tunnus]'
              and tyyppi     = 'A'";
        $hresult = pupe_query($hquery);

        if (mysql_num_rows($hresult) == 0) {
          echo "<font class='error'>".t("Ei l�ytynyt yhteyshenkil�it� asiakkaalta")."</font><br>";
        }
        else {
          echo "<font class='ok'>".t("L�ytyi yhteyshenkil�it� asiakkaalta")."</font><br>";
          while ($ahrow = mysql_fetch_assoc($hresult)) {

            $tarksql = "SELECT *
                  FROM yhteyshenkilo
                  where yhtio         = '$kukarow[yhtio]'
                  and tyyppi        = '$ahrow[tyyppi]'
                  and liitostunnus    = '$jrow[tunnus]'
                  and nimi        = '$ahrow[nimi]'
                  and titteli       = '$ahrow[titteli]'
                  and rooli       = '$ahrow[rooli]'
                  and suoramarkkinointi = '$ahrow[suoramarkkinointi]'
                  and email       = '$ahrow[email]'
                  and puh         = '$ahrow[puh]'
                  and gsm         = '$ahrow[gsm]'
                  and fax         = '$ahrow[fax]'
                  and www         = '$ahrow[www]'
                  and fakta       = '$ahrow[fakta]'
                  and tilausyhteyshenkilo = '$ahrow[tilausyhteyshenkilo]'
                  and oletusyhteyshenkilo = '$ahrow[oletusyhteyshenkilo]'";
            $tarkesult = pupe_query($tarksql);
            $ahy = mysql_num_rows($tarkesult);

            if ($ahy == 0) {
              $ahinsert = "INSERT INTO yhteyshenkilo SET
                    yhtio           = '$kukarow[yhtio]',
                    tyyppi        = '$ahrow[tyyppi]',
                    liitostunnus    = '$jrow[tunnus]',
                    nimi        = '$ahrow[nimi]',
                    titteli       = '$ahrow[titteli]',
                    rooli       = '$ahrow[rooli]',
                    suoramarkkinointi = '$ahrow[suoramarkkinointi]',
                    email       = '$ahrow[email]',
                    puh         = '$ahrow[puh]',
                    gsm         = '$ahrow[gsm]',
                    fax         = '$ahrow[fax]',
                    www         = '$ahrow[www]',
                    fakta       = '$ahrow[fakta]',
                    tilausyhteyshenkilo = '$ahrow[tilausyhteyshenkilo]',
                    oletusyhteyshenkilo = '$ahrow[oletusyhteyshenkilo]',
                    laatija         = '$kukarow[kuka]',
                    luontiaika      = now()";
              $ahinsertresult = pupe_query($ahinsert);

              synkronoi($kukarow["yhtio"], "yhteyshenkilo", mysql_insert_id(), "", "");
            }
          }
        }

        // !!!!!!!! ASIAKKAAN_AVAINSANA OSIO !!!!!!!!!!!!
        $hquery = " SELECT *
              FROM asiakkaan_avainsanat
              WHERE yhtio = '$kukarow[yhtio]'
              AND liitostunnus = '$asrow[tunnus]'";
        $hresult = pupe_query($hquery);

        if (mysql_num_rows($hresult) == 0) {
          echo "<font class='error'>".t("Ei l�ytynyt avainsanoja asiakkaalta")."</font><br>";
        }
        else {
          echo "<font class='ok'>".t("L�ytyi avainsanoja asiakkaalta")."</font><br>";
          while ($ahrow = mysql_fetch_assoc($hresult)) {

            $tarksql = "SELECT *
                  FROM asiakkaan_avainsanat
                  where yhtio       = '$kukarow[yhtio]'
                  and liitostunnus  = '$jrow[tunnus]'
                  and kieli       = '$ahrow[kieli]'
                  and laji        = '$ahrow[laji]'
                  and avainsana   = '$ahrow[avainsana]'";
            $tarkesult = pupe_query($tarksql);
            $ahy = mysql_num_rows($tarkesult);

            if ($ahy == 0) {
              $ahinsert = "INSERT INTO asiakkaan_avainsanat SET
                    yhtio     = '$kukarow[yhtio]',
                    liitostunnus  = '$jrow[tunnus]',
                    kieli       = '$ahrow[kieli]',
                    laji        = '$ahrow[laji]',
                    avainsana   = '$ahrow[avainsana]',
                    laatija       = '$kukarow[kuka]',
                    luontiaika    = now()";
              $ahinsertresult = pupe_query($ahinsert);

              synkronoi($kukarow["yhtio"], "asiakkaan_avainsanat", mysql_insert_id(), "", "");
            }
          }
        }

        // !!!!!!!! ASIAKASLIITE OSIO !!!!!!!!!!!!
        $hquery = " SELECT *
              FROM liitetiedostot
              WHERE yhtio = '$kukarow[yhtio]'
              AND liitos = 'asiakas'
              AND liitostunnus = '$asrow[tunnus]'";
        $hresult = pupe_query($hquery);

        if (mysql_num_rows($hresult) == 0) {
          echo "<font class='error'>".t("Ei l�ytynyt liitteit� asiakkaalta")."</font><br>";
        }
        else {
          echo "<font class='ok'>".t("L�ytyi liitteit� asiakkaalta")."</font><br>";
          while ($ahrow = mysql_fetch_assoc($hresult)) {

            $tarksql = "SELECT *
                  FROM liitetiedostot
                  where yhtio       = '$kukarow[yhtio]'
                  and liitos      = '$ahrow[liitos]'
                  and liitostunnus  = '$jrow[tunnus]'
                  and selite      = '$ahrow[selite]'
                  and kieli       = '$ahrow[kieli]'
                  and filename    = '$ahrow[filename]'
                  and filesize    = '$ahrow[filesize]'
                  and filetype    = '$ahrow[filetype]'
                  and image_width   = '$ahrow[image_width]'
                  and image_height  = '$ahrow[image_height]'
                  and image_bits    = '$ahrow[image_bits]'
                  and image_channels  = '$ahrow[image_channels]'
                  and kayttotarkoitus = '$ahrow[kayttotarkoitus]'
                  and jarjestys     = '$ahrow[jarjestys]'";
            $tarkesult = pupe_query($tarksql);
            $ahy = mysql_num_rows($tarkesult);

            if ($ahy == 0) {
              $ahinsert = "INSERT INTO liitetiedostot SET
                    yhtio     = '$kukarow[yhtio]',
                    liitos      = '$ahrow[liitos]',
                    liitostunnus  = '$jrow[tunnus]',
                    data      = '".mysql_real_escape_string($ahrow["data"])."',
                    selite      = '$ahrow[selite]',
                    kieli       = '$ahrow[kieli]',
                    filename    = '$ahrow[filename]',
                    filesize    = '$ahrow[filesize]',
                    filetype    = '$ahrow[filetype]',
                    image_width   = '$ahrow[image_width]',
                    image_height  = '$ahrow[image_height]',
                    image_bits    = '$ahrow[image_bits]',
                    image_channels  = '$ahrow[image_channels]',
                    kayttotarkoitus = '$ahrow[kayttotarkoitus]',
                    jarjestys     = '$ahrow[jarjestys]',
                    laatija       = '$kukarow[kuka]',
                    luontiaika    = now()";
              $ahinsertresult = pupe_query($ahinsert);
            }
          }
        }

        // !!!!!!!! PUUN_ALKIO OSIO !!!!!!!!!!!!
        $hquery = " SELECT *, if(kutsuja = '', laji, kutsuja) AS kutsuja
              FROM puun_alkio
              WHERE yhtio = '$kukarow[yhtio]'
              AND laji = 'Asiakas'
              AND liitos = '$asrow[tunnus]'";
        $hresult = pupe_query($hquery);

        if (mysql_num_rows($hresult) == 0) {
          echo "<font class='error'>".t("Ei l�ytynyt dynaamisen puun liitoksia asiakkaalta")."</font><br>";
        }
        else {
          echo "<font class='ok'>".t("L�ytyi dynaamisen puun liitoksia asiakkaalta")."</font><br>";
          while ($ahrow = mysql_fetch_assoc($hresult)) {

            $tarksql = "SELECT *
                  FROM puun_alkio
                  where yhtio   = '$kukarow[yhtio]'
                  and liitos    = '$jrow[tunnus]'
                  and kieli     = '$ahrow[kieli]'
                  and laji    = '$ahrow[laji]'
                  and kutsuja = '{$ahrow['kutsuja']}'
                  and puun_tunnus = '$ahrow[puun_tunnus]'";
            $tarkesult = pupe_query($tarksql);
            $ahy = mysql_num_rows($tarkesult);

            if ($ahy == 0) {

              $ahinsert = "INSERT INTO puun_alkio SET
                    yhtio   = '$kukarow[yhtio]',
                    liitos    = '$jrow[tunnus]',
                    kieli     = '$ahrow[kieli]',
                    laji    = '$ahrow[laji]',
                    kutsuja    = '{$ahrow['kutsuja']}',
                    puun_tunnus = '$ahrow[puun_tunnus]',
                    jarjestys   = '$ahrow[jarjestys]',
                    laatija     = '$kukarow[kuka]',
                    luontiaika  = now()";
              $ahinsertresult = pupe_query($ahinsert);
            }
          }
        }

        // !!!!!! Asiakasmemot, kalenterit, siell� olevat liitetiedostot menee kalenterintunnuksen mukaan, joten niiit� ei tarvitse erikseen p�ivitt��
        $memohaku = " SELECT liitostunnus, asiakas
                FROM kalenteri
                WHERE yhtio = '$kukarow[yhtio]'
                AND liitostunnus = '$asrow[tunnus]'";
        $memores = pupe_query($memohaku);
        $ahy = mysql_num_rows($memores);

        if ($ahy != 0) {
          echo "<font class='ok'>".t("P�ivitettiin CRM-tiedot asiakkaalta")."</font><br>";

          $memosql = "UPDATE kalenteri
                SET asiakas = '$jrow[ytunnus]', liitostunnus = '$jrow[tunnus]'
                WHERE yhtio = '$kukarow[yhtio]'
                AND liitostunnus = '$asrow[tunnus]'";
          $memores = pupe_query($memosql);
        }
        else {
          echo "<font class='error'>".t("Ei l�ytynyt CRM-tietoja asiakkaalta")."</font><br>";
        }

        // !!!!!!!! LASKUTUS OSIO !!!!!!!!!!!!
        $lquery = " SELECT group_concat(tunnus) tunnukset FROM lasku WHERE yhtio ='$kukarow[yhtio]' AND liitostunnus = '$asrow[tunnus]' AND tila not IN ('G','O','K','H','Y','M','P','Q','X')";
        $lresult = pupe_query($lquery);
        $lrow = mysql_fetch_assoc($lresult);

        if (trim($lrow['tunnukset']) != "") {
          $lupdate = "UPDATE lasku SET liitostunnus = '$jrow[tunnus]' WHERE yhtio ='$kukarow[yhtio]' and liitostunnus='$asrow[tunnus]' AND tunnus IN ($lrow[tunnukset])";
          $lupdateresult = pupe_query($lupdate);
          echo "<font class='ok'>".t("Asiakkaan laskut p�ivitettiin")."</font><br><br>";
        }
        else {
          echo "<font class='error'>".t("Ei l�ytynyt laskuja asiakkaalta")."</font><br><br>";
        }

        $_query = " UPDATE kuka SET
                    oletus_asiakas = '{$jataminut}'
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND oletus_asiakas = '{$asrow['tunnus']}'";
        $upd_res = pupe_query($_query);

        $_query = " UPDATE kuka SET
                    oletus_asiakastiedot = '{$jataminut}'
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND oletus_asiakastiedot = '{$asrow['tunnus']}'";
        $upd_res = pupe_query($_query);

        // Muutetaan asiakkaan laji = 'P', j�tet��n varmuudeksi talteen, toistaiseksi.
        $paivitys = "UPDATE asiakas set laji='P' where yhtio ='$kukarow[yhtio]' AND tunnus = '$asrow[tunnus]'";
        $pairesult = pupe_query($paivitys);

        synkronoi($kukarow["yhtio"], "asiakas", $asrow["tunnus"], $asrow, "");

        $historia .= "+ ".t("Asiakas").": ".$asrow["nimi"] .", ".t("ytunnus").": ".$asrow["ytunnus"] .", ".t("asiakasnro").": ". $asrow["asiakasnro"] ."<br />";

      }//if
    }//foreach

    $historia_tietokantaan = str_replace('<br />', '\\n', $historia);

    $kysely = " INSERT INTO kalenteri
          SET tapa    = '".t("Muu syy (muista selite!)")."',
          asiakas     = '$jrow[ytunnus]',
          liitostunnus  = '$jrow[tunnus]',
          kuka        = '$kukarow[kuka]',
          yhtio       = '$kukarow[yhtio]',
          tyyppi      = 'Memo',
          kentta01    = '$historia_tietokantaan',
          pvmalku     = now(),
          laatija     = '$kukarow[kuka]',
          luontiaika    = now()";
    $result = pupe_query($kysely);

    return $historia;
}

function hae_asiakastunnus($tunnukset) {
  global $kukarow;

  if($tunnukset['ASIAKASTUNNUS'] != ''){
    $query = "SELECT tunnus
              FROM asiakas
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND laji != 'P'
              AND tunnus = '{$tunnukset['ASIAKASTUNNUS']}'";
  }
  else{
    $query = "SELECT tunnus
              FROM asiakas
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND laji != 'P'
              AND ytunnus = '{$tunnukset['YTUNNUS']}'
              AND ovttunnus = '{$tunnukset['OVTTUNNUS']}'
              AND toim_ovttunnus = '{$tunnukset['TOIM_OVTTUNNUS']}'";
  }

  $result = pupe_query($query);

  if(mysql_num_rows($result) > 0){
    $tunnus = mysql_result($result, 0);
    if ($tunnus != '') return $tunnus;
  }
  return false;
}
