<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

if (isset($_POST['tiliote']) and $_POST['tiliote'] == '1') {
  $nayta_pdf = 1;
}

// DataTables päälle
$pupe_DataTables = "astilmyrerap";

require "../inc/parametrit.inc";

if ((isset($tiliote) and $tiliote == '1') or (!empty($tee) and $tee == 'TULOSTA_EMAIL' and !empty($asiakasid))) {

  require 'paperitiliote.php';

  if (!empty($tee) and $tee == 'TULOSTA_EMAIL' and !empty($email)) {

    $asiakasid = (int) $asiakasid;
    $email = mysql_real_escape_string($email);

    $query = "SELECT nimi, kieli
              FROM asiakas
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$asiakasid}'";
    $asiakasresult = pupe_query($query);
    $asiakasrow = mysql_fetch_assoc($asiakasresult);

    $params = array(
      'to' => $email,
      'cc' => '',
      'subject' => t("Asiakasraportit myyntilaskuista", $asiakasrow['kieli'])." - {$asiakasrow['nimi']}",
      'ctype' => 'html',
      'body' => "",
      'attachements' => array(
        array(
          "filename" => $pdffilenimi,
          "newfilename" => t("Asiakasraportti myyntilaskuista", $asiakasrow['kieli'])." - {$asiakasrow['nimi']}.pdf",
          "ctype" => "pdf",
        ),
      ),
    );

    pupesoft_sahkoposti($params);

    echo "<font class='info'>";
    echo t("Tiliote lähetettiin osoitteeseen"), ": {$email}<br /><br />";
    echo "</font>";
  }

  $tee = "";
  $tila = "tee_raportti";
}

if (!empty($tee) and $tee == 'TULOSTA_EMAIL_LASKUT' and !empty($laskunrot)) {

  if (@include_once "tilauskasittely/tulosta_lasku.inc");
  elseif (@include_once "tulosta_lasku.inc");
  else exit;

  foreach (explode(",", $laskunrot) as $laskunro) {
    tulosta_lasku("LASKU:{$laskunro}", $asiakasrow['kieli'], $tee, 'LASKU', "asiakasemail{$asiakasemail}", "", "");
  }

  echo "<font class='info'>";

  if (strpos($laskunrot, ",") !== FALSE) echo t("Laskut lähetettiin osoitteeseen"), ": {$asiakasemail}";
  else echo t("Lasku lähetettiin osoitteeseen"), ": {$asiakasemail}";

  echo "</font><br /><br />";

  $tee = "";
  $tila = "tee_raportti";
}

if (!isset($tee)) $tee = "";
if (!isset($ytunnus)) $ytunnus = "";
if (!isset($tila)) $tila = "";
if (!isset($asiakasid)) $asiakasid = 0;
if (!isset($savalkoodi)) $savalkoodi = "";
if (!isset($valintra)) $valintra = "";
if (!isset($alkupvm)) $alkupvm = "";
if (!isset($loppupvm)) $loppupvm = "";

// scripti balloonien tekemiseen
js_popup();

if ($tee == "") {

  /* visuaalinen esitys maksunopeudesta (hymynaama) */
  /* palauttaa listan arvoja, joissa ensimmäisessä on
   * pelkkä img-tagi oikeaan naamaan ja toisessa
   * koko maksunopeus-HTML
   */

  function laskeMaksunopeus($tunnukset, $yhtio) {

    global $palvelin2;

    // myohassa maksetut
    $query = "SELECT sum(if(erpcm < mapvm, summa, 0)) myohassa, sum(summa) yhteensa
              FROM lasku USE INDEX (yhtio_tila_liitostunnus_tapvm)
              WHERE yhtio      = '$yhtio'
              AND tila         = 'U'
              AND liitostunnus in ($tunnukset)
              AND tapvm        > '0000-00-00'
              AND mapvm        > '0000-00-00'
              AND alatila      = 'X'
              AND summa        > 0";
    $result = pupe_query($query);
    $laskut = mysql_fetch_array($result);

    if ($laskut['yhteensa'] != 0) {
      $maksunopeus = $laskut['myohassa'] / $laskut['yhteensa'] * 100;
    }
    else {
      $maksunopeus = "N/A";
    }

    if ($maksunopeus > 50) {
      $kuva = "asiakas_argh.gif";
    }
    elseif ($maksunopeus > 10) {
      $kuva = "asiakas_hui.gif";
    }
    else {
      $kuva = "asiakas_jee.gif";
    }

    $html = t("Myöhässä maksettuja laskuja").": ".sprintf('%.0f', $maksunopeus)."%";
    $kuvaurl = "<img valign='bottom' src='${palvelin2}pics/$kuva'>";

    return array($kuvaurl, $html);
  }

  echo "<font class='head'>".t("Asiakasraportit myyntilaskuista")."</font><hr>";

  if ($ytunnus != '' and (int) $asiakasid == 0) {
    $tila = "tee_raportti";

    require "inc/asiakashaku.inc";

    if ($ytunnus == "") {
      $tila = "";
    }
  }

  if ($tila == 'tee_raportti') {

    echo "  <script language='javascript' type='text/javascript'>

        $(function() {

          var val = $('#valintra').val();

          if (val == 'eraantyneet') {
            $('#infoteksti').html('", t("Erääntyneet laskut"), "');
          }
          else if (val == 'maksetut') {
            $('#infoteksti').html('", t("Maksetut laskut"), "');
          }
          else if (val == 'kaikki') {
            $('#infoteksti').html('", t("Kaikki laskut"), "');
          }
          else {
            $('#infoteksti').html('", t("Avoimet laskut"), "');
          }

          $('#valintra').on('change', function() {
            $('#riviformi').submit();
          });

          $('.date').on('keyup change blur', function() {

            var id = $(this).attr('id');

            $('#'+id+'_hidden').val($(this).val());
          });

          var laskunrot_loop = function() {

            var nrot = [];

            $('.laskunro:checked').each(function() {
              nrot.push($(this).val());
            });

            $('.laskunrot').val(nrot.join(','));

          }

          $('.laskunro').on('click', laskunrot_loop);

          $('.laskunro_checkall').on('click', function() {

            $('.laskunro').prop('checked', $(this).is(':checked'));

            laskunrot_loop();
          });

          $('.laskunrot_submit').on('click', function(event) {

            event.preventDefault();

            if ($(this).closest('.laskunrot').val() == '') {
              alert('".t("Et valinnut yhtään laskua")."!');
              return false;
            }

            $(this).closest('form').submit();
          });

        });

        </script>";

    if ($alatila == 'T' and (int) $asiakasid > 0) {
      $haku_sql = "tunnus = '$asiakasid'";
    }
    else {
      $ytunnus  = mysql_real_escape_string($ytunnus);
      $haku_sql = "ytunnus = '$ytunnus'";
    }

    $query = "SELECT tunnus,
              ytunnus,
              trim(concat(nimi, ' ', nimitark)) nimi,
              osoite,
              postino,
              postitp,
              maa,
              lasku_email,
              talhal_email,
              email
              FROM asiakas
              WHERE yhtio  = '{$kukarow['yhtio']}'
              and {$haku_sql}
              and laji    != 'P'
              ORDER BY talhal_email DESC, lasku_email DESC, email DESC";
    $result = pupe_query($query);

    if (mysql_num_rows($result) > 0) {

      $asiakasrow = mysql_fetch_array($result);

      // ekotetaan javascriptiä jotta saadaan pdf:ät uuteen ikkunaan
      js_openFormInNewWindow();

      $asiakasid   = $asiakasrow['tunnus'];
      $ytunnus   = $asiakasrow['ytunnus'];

      if ($alatila == "T") {
        $tunnukset   = $asiakasid;
        $nimet    = 1;
      }
      else {
        $query = "SELECT group_concat(tunnus) tunnukset, count(*) kpl
                  FROM asiakas
                  WHERE yhtio = '$kukarow[yhtio]'
                  and ytunnus = '$asiakasrow[ytunnus]'";
        $result = pupe_query($query);
        $asiakasrow2 = mysql_fetch_array($result);

        $tunnukset   = $asiakasrow2['tunnukset'];
        $nimet    = $asiakasrow2['kpl'];
      }

      // Kaatotilin saldo
      if ($savalkoodi != "") {
        $savalkoodi = mysql_real_escape_string($savalkoodi);
        $salisa = " and valkoodi='$savalkoodi' ";
      }
      else {
        $salisa = "";
      }

      $query = "SELECT valkoodi, sum(round(summa*if(kurssi=0, 1, kurssi),2)) summa, sum(summa) summa_valuutassa
                FROM suoritus
                WHERE yhtio         = '$kukarow[yhtio]'
                and kohdpvm         = '0000-00-00'
                and ltunnus         > 0
                and asiakas_tunnus  in ($tunnukset)
                and summa          != 0
                $salisa
                group by 1";
      $kaatoresult = pupe_query($query);

      $query = "SELECT valkoodi, count(tunnus) as maara,
                sum(if(mapvm = '0000-00-00',1,0)) avoinmaara,
                sum(if(erpcm < now() and mapvm = '0000-00-00',1,0)) eraantynytmaara,
                sum(summa-saldo_maksettu) as summa,
                sum(if(mapvm='0000-00-00',summa-saldo_maksettu,0)) avoinsumma,
                sum(if(erpcm < now() and mapvm = '0000-00-00',summa-saldo_maksettu,0)) eraantynytsumma,
                sum(summa_valuutassa-saldo_maksettu_valuutassa) as summa_valuutassa,
                sum(if(mapvm='0000-00-00',summa_valuutassa-saldo_maksettu_valuutassa,0)) avoinsumma_valuutassa,
                sum(if(erpcm < now() and mapvm = '0000-00-00',summa_valuutassa-saldo_maksettu_valuutassa,0)) eraantynytsumma_valuutassa,
                sum(if(mapvm = '0000-00-00' and TO_DAYS(NOW())-TO_DAYS(erpcm) <= -3,1,0)) maara1,
                sum(if(mapvm = '0000-00-00' and TO_DAYS(NOW())-TO_DAYS(erpcm) > -3 and TO_DAYS(NOW())-TO_DAYS(erpcm) <= -1,1,0)) maara2,
                sum(if(mapvm = '0000-00-00' and TO_DAYS(NOW())-TO_DAYS(erpcm) > -1 and TO_DAYS(NOW())-TO_DAYS(erpcm) <= 15,1,0)) maara3,
                sum(if(mapvm = '0000-00-00' and TO_DAYS(NOW())-TO_DAYS(erpcm) > 15 and TO_DAYS(NOW())-TO_DAYS(erpcm) <= 30,1,0)) maara4,
                sum(if(mapvm = '0000-00-00' and TO_DAYS(NOW())-TO_DAYS(erpcm) > 30 and TO_DAYS(NOW())-TO_DAYS(erpcm) <= 60,1,0)) maara5,
                sum(if(mapvm = '0000-00-00' and TO_DAYS(NOW())-TO_DAYS(erpcm) > 60,1,0)) maara6
                FROM lasku use index (yhtio_tila_liitostunnus_tapvm)
                WHERE yhtio      = '$kukarow[yhtio]'
                and tila         = 'U'
                and liitostunnus in ($tunnukset)
                $salisa
                and tapvm        > '0000-00-00'
                and mapvm        = '0000-00-00'
                group by 1";
      $result = pupe_query($query);

      if (mysql_num_rows($kaatoresult) > 1) {
        $riveja = mysql_num_rows($kaatoresult) + 1;
      }
      else {
        $riveja = 1;
        if (mysql_num_rows($kaatoresult) != 0) {
          $kaato = mysql_fetch_array($kaatoresult);
          mysql_data_seek($kaatoresult, 0);

          if (strtoupper($yhtiorow['valkoodi']) != strtoupper($kaato['valkoodi'])) {
            $riveja = 2;
          }
        }
      }

      echo "<table>
        <tr>
        <th rowspan='$riveja'><a href='".$palvelin2."crm/asiakasmemo.php?ytunnus=$ytunnus&asiakasid=$asiakasid&lopetus=$lopetus/SPLIT/".$palvelin2."myyntires/myyntilaskut_asiakasraportti.php////tila=$tila//ytunnus=$ytunnus//asiakasid=$asiakasid//alatila=$alatila//lopetus=$lopetus//valintra=$valintra//savalkoodi=$savalkoodi//ppa=$ppa//kka=$kka//vva=$vva//ppl=$ppl//kkl=$kkl//vvl=$vvl'>$asiakasrow[nimi]</a></th>
        <td rowspan='$riveja'>".t("Kaatotilillä")."</td>";

      if (mysql_num_rows($kaatoresult) > 1) { // Valuuttasummia
        $kotisumma = 0;
        while ($kaato = mysql_fetch_array($kaatoresult)) {
          echo "<td align='right'>$kaato[summa_valuutassa]</td><td>$kaato[valkoodi]</td></tr><tr>";
          $kotisumma += $kaato['summa'];
        }
        echo "<td align='right'>$kotisumma</td><td>$yhtiorow[valkoodi]</td></tr>";
      }
      else {
        $kaato = mysql_fetch_array($kaatoresult);
        if ($riveja == 2) {
          echo "<td align='right'>$kaato[summa_valuutassa]</td><td>$kaato[valkoodi]</td></tr>";
          echo "<tr><td align='right'>$kaato[summa]</td><td>$yhtiorow[valkoodi]</td></tr>";
        }
        else {
          echo "<td align='right'>$kaato[summa]</td>";
        }
      }

      if (mysql_num_rows($result) > 1) {
        $riveja = mysql_num_rows($result) + 1;
      }
      else {
        $riveja = 1;
        if (mysql_num_rows($result) != 0) {
          $kok = mysql_fetch_array($result);
          mysql_data_seek($result, 0);
          if (strtoupper($yhtiorow['valkoodi']) != strtoupper($kok['valkoodi'])) {
            $riveja = 2;
          }
        }
      }

      echo "<tr>
        <th rowspan='$riveja'>$ytunnus ($nimet)</th>
        <td rowspan='$riveja'>".t("Myöhässä olevia laskuja yhteensä")."</td>";

      if (mysql_num_rows($result) > 1) { // Valuuttasummia
        $kotisumma = 0;

        while ($kok = mysql_fetch_array($result)) {
          echo "<td align='right'>$kok[eraantynytsumma_valuutassa]</td><td>$kok[valkoodi]</td></tr>";
          $kotisumma += $kok['eraantynytsumma'];
        }
        echo "<td align='right'>$kotisumma</td><td>$yhtiorow[valkoodi]</td>";
      }
      else {
        $kok = mysql_fetch_array($result);

        if ($riveja == 2) {
          echo "<td align='right'>$kok[eraantynytsumma_valuutassa]</td><td>$kok[valkoodi]</td></tr>";
          echo "<tr><td align='right'>$kok[eraantynytsumma]</td><td>$yhtiorow[valkoodi]</td></tr>";
        }
        else {
          echo "<td align='right'>$kok[eraantynytsumma]</td></tr>";
        }
      }

      if (mysql_num_rows($result) > 0) mysql_data_seek($result, 0);

      echo "
        <tr>
        <th rowspan='$riveja'>$asiakasrow[osoite]</th>
        <td rowspan='$riveja'>".t("Avoimia laskuja yhteensä")."</td>";

      if (mysql_num_rows($result) > 1) { // Valuuttasummia
        $kotisumma = 0;

        while ($kok = mysql_fetch_array($result)) {
          echo "<td align='right'>$kok[avoinsumma_valuutassa]</td><td>$kok[valkoodi]</td></tr>";
          $kotisumma += $kok['avoinsumma'];
        }
        echo "<td align='right'>$kotisumma</td><td>$yhtiorow[valkoodi]</td>";
      }
      else {
        $kok = mysql_fetch_array($result);

        if ($riveja == 2) {
          echo "<td align='right'>$kok[avoinsumma_valuutassa]</td><td>$kok[valkoodi]</td></tr>";
          echo "<tr><td align='right'>$kok[avoinsumma]</td><td>$yhtiorow[valkoodi]</td></tr>";
        }
        else {
          echo "<td align='right'>$kok[avoinsumma]</td></tr>";
        }
      }

      echo "<tr>
        <th>$asiakasrow[postino] $asiakasrow[postitp]</th>
        <td colspan='2'></td></tr>";

      echo "<tr>
        <th>$asiakasrow[maa]</th><td colspan='2'><a href='{$palvelin2}raportit/asiakasinfo.php?ytunnus=$ytunnus&asiakasid=$asiakasid&lopetus=$lopetus/SPLIT/".$palvelin2."myyntires/myyntilaskut_asiakasraportti.php////tila=$tila//ytunnus=$ytunnus//asiakasid=$asiakasid//alatila=$alatila//lopetus=$lopetus//valintra=$valintra//savalkoodi=$savalkoodi//ppa=$ppa//kka=$kka//vva=$vva//ppl=$ppl//kkl=$kkl//vvl=$vvl'>".t("Asiakkaan myyntitiedot")."</a></td>
        </tr>";

      if ($asiakasrow['talhal_email'] != '') {
        echo "<tr>";
        echo "<th>", t("Sähköpostiosoite"), " (", t("Taloushallinto"), ")</th>";
        echo "<td colspan='2'>{$asiakasrow['talhal_email']}</td>";
        echo "</tr>";
      }

      if ($asiakasrow['lasku_email'] != '') {
        echo "<tr>";
        echo "<th>", t("Sähköpostiosoite"), " (", t("laskutus"), ")</th>";
        echo "<td colspan='2'>{$asiakasrow['lasku_email']}</td>";
        echo "</tr>";
      }

      if ($asiakasrow['email'] != '') {
        echo "<tr>";
        echo "<th>", t("Sähköpostiosoite"), "</th>";
        echo "<td colspan='2'>{$asiakasrow['email']}</td>";
        echo "</tr>";
      }

      $query  = "SELECT group_concat(distinct kentta01 SEPARATOR '<br>') viestit
                 FROM kalenteri
                  WHERE yhtio        = '$kukarow[yhtio]'
                  AND tyyppi         = 'Myyntireskontraviesti'
                    AND liitostunnus in ($tunnukset)
                 ORDER BY tunnus desc";
      $amres = pupe_query($query);
      $amrow = mysql_fetch_assoc($amres);

      if ($amrow['viestit'] != "") {
        echo "<tr>
          <th>".t("Reskontraviesti")."</th><td colspan='2'>{$amrow['viestit']}</td>
          </tr>";
      }

      echo "</table><br>";

      echo "<form action = 'myyntilaskut_asiakasraportti.php' method = 'post'>
        <input type='hidden' name='toim' value='$toim'>
        <input type='hidden' name='lopetus' value='$lopetus'>";
      echo "<input type='submit' value='".t("Vaihda asiakasta")."'>";
      echo "</form>";
      echo "<br><br>";

      if (!isset($vv)) $vv = date("Y");
      if (!isset($kk)) $kk = date("n");
      if (!isset($pp)) $pp = date("j");

      echo "<table>";
      echo "<tr><th>", t("Tiliote päivälle"), "</th>
          <td>
          <form id='tulosta_tiliote' name='tulosta_tiliote' method='post'>
          <input type='hidden' name = 'tee' value = 'NAYTATILAUS'>
          <input type='hidden' name = 'tiliote' value = '1'>
          <input type='hidden' name = 'ytunnus' value = '{$ytunnus}'>
          <input type='hidden' name = 'asiakasid' value = '{$asiakasid}'>
          <input type='hidden' name = 'alatila' value = '{$alatila}'>
          <input type = 'text' name = 'pp' id = 'pp' value='{$pp}' size=3 class='date'>
          <input type = 'text' name = 'kk' id = 'kk' value='{$kk}' size=3 class='date'>
          <input type = 'text' name = 'vv' id = 'vv' value='{$vv}' size=5 class='date'>
          </td>
          <td class='back'>
          <input type='submit' value='", t("Tulosta tiliote"), "' onClick=\"js_openFormInNewWindow('tulosta_tiliote', ''); return false;\">
          </form>
          </td>";

      $_email_ok = (!empty($asiakasrow['email']) or !empty($asiakasrow['talhal_email']) or !empty($asiakasrow['lasku_email']));

      if ($_email_ok) {

        echo "</tr><tr>";
        echo "<th>", t("Lähetä tiliote asiakkaan sähköpostiin"), "</th>";
        echo "<td class='back'>";

        if ($asiakasrow['talhal_email'] != '') {
          echo "<form id='tulosta_tiliote_email' name='tulosta_tiliote_email' method='post'>
              <input type='hidden' name = 'tee' value = 'TULOSTA_EMAIL'>
              <input type='hidden' name = 'email' value = '{$asiakasrow['talhal_email']}'>
              <input type='hidden' name = 'ytunnus' value = '{$ytunnus}'>
              <input type='hidden' name = 'asiakasid' value = '{$asiakasid}'>
              <input type='hidden' name = 'alatila' value = '{$alatila}'>
              <input type='hidden' name = 'pp' id='pp_hidden' value='{$pp}' size=2>
              <input type='hidden' name = 'kk' id='kk_hidden' value='{$kk}' size=2>
              <input type='hidden' name = 'vv' id='vv_hidden' value='{$vv}' size=4>
              <input type='submit' value='{$asiakasrow['talhal_email']}' />
              </form>";
        }

        if ($asiakasrow['lasku_email'] != '') {
          echo "<form id='tulosta_tiliote_email' name='tulosta_tiliote_email' method='post'>
              <input type='hidden' name = 'tee' value = 'TULOSTA_EMAIL'>
              <input type='hidden' name = 'email' value = '{$asiakasrow['lasku_email']}'>
              <input type='hidden' name = 'ytunnus' value = '{$ytunnus}'>
              <input type='hidden' name = 'asiakasid' value = '{$asiakasid}'>
              <input type='hidden' name = 'alatila' value = '{$alatila}'>
              <input type='hidden' name = 'pp' id='pp_hidden' value='{$pp}' size=2>
              <input type='hidden' name = 'kk' id='kk_hidden' value='{$kk}' size=2>
              <input type='hidden' name = 'vv' id='vv_hidden' value='{$vv}' size=4>
              <input type='submit' value='{$asiakasrow['lasku_email']}' />
              </form>";
        }

        if ($asiakasrow['email'] != '') {
          echo "<form id='tulosta_tiliote_email' name='tulosta_tiliote_email' method='post'>
              <input type='hidden' name = 'tee' value = 'TULOSTA_EMAIL'>
              <input type='hidden' name = 'email' value = '{$asiakasrow['email']}'>
              <input type='hidden' name = 'ytunnus' value = '{$ytunnus}'>
              <input type='hidden' name = 'asiakasid' value = '{$asiakasid}'>
              <input type='hidden' name = 'alatila' value = '{$alatila}'>
              <input type='hidden' name = 'pp' id='pp_hidden' value='{$pp}' size=2>
              <input type='hidden' name = 'kk' id='kk_hidden' value='{$kk}' size=2>
              <input type='hidden' name = 'vv' id='vv_hidden' value='{$vv}' size=4>
              <input type='submit' value='{$asiakasrow['email']}' />
              </form>";
        }

        echo "</td>";
      }

      echo "</tr>";
      echo "</table>";

      echo "<table><tr><td class='back'>";

      // ikäanalyysi
      echo "<br><table><tr><th>&lt; -2</th><th>-2 - -1</th><th>0 - 15</th><th>16 - 30</th><th>31 - 60</th><th>&gt; 60</th></tr>";

      $palkki_korkeus = 20;
      $palkki_leveys = 300;

      $kuvaurl[0] = "../pics/vaaleanvihrea.png";
      $kuvaurl[1] = "../pics/vihrea.png";
      $kuvaurl[2] = "../pics/keltainen.png";
      $kuvaurl[3] = "../pics/oranssi.png";
      $kuvaurl[4] = "../pics/oranssihko.png";
      $kuvaurl[5] = "../pics/punainen.png";

      $yhtmaara = $kok['avoinmaara'];

      echo "<tr>";
      echo "<td align='right'>".(int) $kok["maara1"]."</td>";
      echo "<td align='right'>".(int) $kok["maara2"]."</td>";
      echo "<td align='right'>".(int) $kok["maara3"]."</td>";
      echo "<td align='right'>".(int) $kok["maara4"]."</td>";
      echo "<td align='right'>".(int) $kok["maara5"]."</td>";
      echo "<td align='right'>".(int) $kok["maara6"]."</td>";
      echo "</tr>";

      if ($yhtmaara != 0) {
        echo "<tr><td colspan='6' class='back'>";
        echo "<img src='$kuvaurl[0]' height='$palkki_korkeus' width='" . ($kok['maara1']/$yhtmaara) * $palkki_leveys ."'>";
        echo "<img src='$kuvaurl[1]' height='$palkki_korkeus' width='" . ($kok['maara2']/$yhtmaara) * $palkki_leveys ."'>";
        echo "<img src='$kuvaurl[2]' height='$palkki_korkeus' width='" . ($kok['maara3']/$yhtmaara) * $palkki_leveys ."'>";
        echo "<img src='$kuvaurl[3]' height='$palkki_korkeus' width='" . ($kok['maara4']/$yhtmaara) * $palkki_leveys ."'>";
        echo "<img src='$kuvaurl[4]' height='$palkki_korkeus' width='" . ($kok['maara5']/$yhtmaara) * $palkki_leveys ."'>";
        echo "<img src='$kuvaurl[5]' height='$palkki_korkeus' width='" . ($kok['maara6']/$yhtmaara) * $palkki_leveys ."'>";
        echo "</td></tr>";
      }

      echo "</table>";

      echo "</td><td class='back' align='center' width='300'>";

      //visuaalinen esitys maksunopeudesta (hymynaama)
      list ($naama, $nopeushtml) = laskeMaksunopeus($tunnukset, $kukarow["yhtio"]);

      echo "<br>$naama<br>$nopeushtml</td>";
      echo "</tr></table><br>";

      //näytetäänkö maksetut vai avoimet
      $chk1 = $chk2 = $chk3 = $chk4 = '';

      if ($valintra == 'maksetut') {
        $chk2 = 'SELECTED';
        $mapvmlisa = " and mapvm > '0000-00-00' ";
      }
      elseif ($valintra == 'kaikki') {
        $chk3 = 'SELECTED';
        $mapvmlisa = " ";
      }
      elseif ($valintra == "eraantyneet") {
        $chk4 = 'SELECTED';
        $mapvmlisa = " and erpcm < now() and mapvm = '0000-00-00' ";
      }
      else {
        $chk1 = 'SELECTED';
        $mapvmlisa = " and mapvm = '0000-00-00' ";
      }

      if ($savalkoodi != "") {
        $salisa = " and lasku.valkoodi='$savalkoodi' ";
      }

      $laskupvm_where = "";
      if (!empty($ppa) and !empty($kka) and !empty($vva) and !empty($ppl) and !empty($kkl) and !empty($vvl)) {
        $alkupvm = "{$vva}-{$kka}-{$ppa}";
        $loppupvm = "{$vvl}-{$kkl}-{$ppl}";
        $laskupvm_where = "  AND tapvm >= '{$alkupvm}' AND tapvm <= '{$loppupvm}'";
      }

      $query = "SELECT laskunro, tapvm, erpcm,
                summa loppusumma,
                kassalipas,
                summa_valuutassa loppusumma_valuutassa,
                if(mapvm!='0000-00-00', 0, summa-saldo_maksettu) avoinsumma,
                if(mapvm!='0000-00-00', 0, summa_valuutassa-saldo_maksettu_valuutassa) avoinsumma_valuutassa,
                kapvm, kasumma, kasumma_valuutassa, mapvm,
                TO_DAYS(if(mapvm!='0000-00-00', mapvm, now())) - TO_DAYS(erpcm) ika,
                olmapvm korkolaspvm,
                tunnus,
                saldo_maksettu,
                saldo_maksettu_valuutassa,
                valkoodi
                FROM lasku USE INDEX (yhtio_tila_liitostunnus_tapvm)
                WHERE yhtio ='$kukarow[yhtio]'
                and tila         = 'U'
                and alatila      = 'X'
                and liitostunnus in ($tunnukset)
                AND tapvm        > '0000-00-00'
                {$laskupvm_where}
                $mapvmlisa
                $salisa
                ORDER BY erpcm";
      $result = pupe_query($query);

      echo "<form action = 'myyntilaskut_asiakasraportti.php' method = 'post' id='riviformi'>
          <input type='hidden' name = 'tila' value='$tila'>
          <input type='hidden' name = 'ytunnus' value = '$ytunnus'>
          <input type='hidden' name = 'asiakasid' value = '$asiakasid'>
          <input type='hidden' name = 'alatila' value = '$alatila'>
          <input type='hidden' name='lopetus' value = '$lopetus'>";

      echo "<table>";
      echo "<tr>
          <th>".t("Näytä").":</th>
          <td>
          <select name='valintra' id='valintra'>
          <option value='' $chk1>".t("Avoimet laskut")."</option>
          <option value='eraantyneet' $chk4>".t("Erääntyneet laskut")."</option>
          <option value='maksetut' $chk2>".t("Maksetut laskut")."</option>
          <option value='kaikki' $chk3>".t("Kaikki laskut")."</option>
          </select>
          </td></tr>";

      $query = "SELECT
                distinct upper(if(valkoodi='', '$yhtiorow[valkoodi]' , valkoodi)) valuutat
                FROM lasku use index (yhtio_tila_liitostunnus_tapvm)
                WHERE yhtio      = '$kukarow[yhtio]'
                and tila         = 'U'
                and liitostunnus in ($tunnukset)
                and tapvm        > '0000-00-00'
                $mapvmlisa";
      $aasres = pupe_query($query);

      if (mysql_num_rows($aasres) > 1) {

        echo "<tr><th>".t("Valuutta").":</th><td><select name='savalkoodi' onchange='submit();'>";
        echo "<option value = ''>".t("Kaikki")."</option>";

        while ($aasrow = mysql_fetch_array($aasres)) {
          $sel="";
          if ($aasrow["valuutat"] == strtoupper($savalkoodi)) {
            $sel = "selected";
          }
          echo "<option value = '$aasrow[valuutat]' $sel>$aasrow[valuutat]</option>";
        }
      }

      echo "</tr>";

      echo "<tr>";
      echo "<th>".t('Alkupäivämäärä')."</th>";
      echo "<td>";
      echo "<input type='text' id='ppa' name='ppa' value='{$ppa}' size='3'/>";
      echo "<input type='text' id='kka' name='kka' value='{$kka}' size='3' />";
      echo "<input type='text' id='vva' name='vva' value='{$vva}' size='6' />";
      echo "</td>";
      echo "<tr>";
      echo "<tr>";
      echo "<th>".t('Loppupäivämäärä')."</th>";
      echo "<td>";
      echo "<input type='text' id='ppl' name='ppl' value='{$ppl}' size='3' />";
      echo "<input type='text' id='kkl' name='kkl' value='{$kkl}' size='3' />";
      echo "<input type='text' id='vvl' name='vvl' value='{$vvl}' size='6' />";
      echo "</td>";
      echo "<td class='back'><input type='submit' value='".t('Hae')."' /></td>";
      echo "</tr>";
      echo "</table>";
      echo "</form><br>";

      if (mysql_num_rows($result) > 0) {
        echo "<form method = 'post'>
            <input type='hidden' name = 'tila' value='$tila'>
            <input type='hidden' name = 'ytunnus' value = '$ytunnus'>
            <input type='hidden' name = 'asiakasid' value = '$asiakasid'>
            <input type='hidden' name = 'valintra' value = '$valintra'>
            <input type='hidden' name = 'savalkoodi' value = '$savalkoodi'>
            <input type='hidden' name = 'alatila' value = '$alatila'>
            <input type='hidden' name = 'lopetus' value = '$lopetus'>";

        pupe_DataTables(array(array($pupe_DataTables, 12, 12, false, false)));

        echo "<table class='display dataTable' id='$pupe_DataTables'><thead>";
        echo "<tr>";
        echo "<th valign='top'>".t("Laskunro")."</th>";
        echo "<th valign='top'>".t("Pvm")."</th>";
        echo "<th valign='top'>".t("Eräpäivä")."</th>";
        echo "<th valign='top'>".t("Summa")."</th>";
        echo "<th valign='top'>".t("Avoinsaldo")."</th>";
        echo "<th valign='top'>".t("Kassa-ale")."<br>".t("pvm")."</th>";
        echo "<th valign='top'>".t("Kassa-ale")."<br>".t("summa")."</th>";
        echo "<th valign='top'>".t("Maksu")."<br>".t("pvm")."</th>";
        echo "<th valign='top'>".t("Ikä")."</th>";
        echo "<th valign='top'>".t("Korko")."</th>";
        echo "<th valign='top'>".t("Korkolasku")."<br>".t("pvm")."</th>";
        echo "<th valign='top'>".t("Maksusuoritukset")."</th>";
        echo "</tr>";

        echo "<tr>
        <td><input type='text' class='search_field' name='search_Laskunro'></td>
        <td><input type='text' class='search_field' name='search_Pvm'></td>
        <td><input type='text' class='search_field' name='search_Erapaiva'></td>
        <td><input type='text' class='search_field' name='search_Summa'></td>
        <td><input type='text' class='search_field' name='search_Avoinsumma'></td>
        <td><input type='text' class='search_field' name='search_Kale1'></td>
        <td><input type='text' class='search_field' name='search_Kale2'></td>
        <td><input type='text' class='search_field' name='search_Mapvm'></td>
        <td><input type='text' class='search_field' name='search_Ika'></td>
        <td><input type='text' class='search_field' name='search_Korko'></td>
        <td><input type='text' class='search_field' name='search_Korkolasku'></td>
        <td><input type='text' class='search_field' name='search_Osasuor'></td>
        </tr>";

        echo "</thead>";

        echo "<tbody>";

        $totaali = array();
        $avoimet = array();
        $korkoja = 0;

        while ($maksurow = mysql_fetch_array($result)) {

          echo "<tr class='aktiivi'>";
          echo "<td>".pupe_DataTablesEchoSort($maksurow['laskunro']);

          if ($_email_ok) {
            echo "<input class='laskunro' type='checkbox' value='{$maksurow['laskunro']}' /> ";
          }

          echo "<a href='".$palvelin2."muutosite.php?tee=E&tunnus=$maksurow[tunnus]&lopetus=$lopetus/SPLIT/".$palvelin2."myyntires/myyntilaskut_asiakasraportti.php////tila=$tila//ytunnus=$ytunnus//asiakasid=$asiakasid//alatila=$alatila//valintra=$valintra//savalkoodi=$savalkoodi//ppa=$ppa//kka=$kka//vva=$vva//ppl=$ppl//kkl=$kkl//vvl=$vvl'>$maksurow[laskunro]</a>";
          echo "</td>";

          echo "<td align='right'>".pupe_DataTablesEchoSort($maksurow['tapvm']).tv1dateconv($maksurow["tapvm"])."</td>";
          echo "<td align='right'>".pupe_DataTablesEchoSort($maksurow['erpcm']).tv1dateconv($maksurow["erpcm"])."</td>";
          echo "<td align='right'>".pupe_DataTablesEchoSort($maksurow['loppusumma'])."$maksurow[loppusumma] {$yhtiorow['valkoodi']}";

          if (strtoupper($yhtiorow['valkoodi']) != strtoupper($maksurow['valkoodi'])) {
            echo "<br />{$maksurow['loppusumma_valuutassa']} {$maksurow['valkoodi']}";
          }

          echo "</td>";

          echo "<td align='right'>";

          if ($maksurow["avoinsumma"] != 0) {
            echo "$maksurow[avoinsumma] {$yhtiorow['valkoodi']}";

            if (strtoupper($yhtiorow['valkoodi']) != strtoupper($maksurow['valkoodi'])) {
              echo "<br />{$maksurow['avoinsumma_valuutassa']} {$maksurow['valkoodi']}";
            }
          }

          echo "</td>";

          if ($maksurow["kapvm"] != '0000-00-00') echo "<td align='right'>".pupe_DataTablesEchoSort($maksurow['kapvm']).tv1dateconv($maksurow["kapvm"])."</td>";
          else echo "<td></td>";

          if ($maksurow["kasumma"] != 0) {
            echo "<td align='right'>".pupe_DataTablesEchoSort($maksurow['kasumma'])."$maksurow[kasumma]";

            if (strtoupper($yhtiorow['valkoodi']) != strtoupper($maksurow['valkoodi'])) {
              echo "<br />" . $maksurow['kasumma_valuutassa'] . ' ' . $maksurow['valkoodi'];
            }
            echo "</td>";
          }
          else {
            echo "<td></td>";
          }

          if ($maksurow["mapvm"] != '0000-00-00') echo "<td align='right'>".pupe_DataTablesEchoSort($maksurow['mapvm']).tv1dateconv($maksurow["mapvm"])."</td>";
          else echo "<td></td>";

          echo "<td align='right'>$maksurow[ika]</td>";

          $params = array(
            'tunnukset' => $maksurow['tunnus'],
          );

          $korkorivit = laske_korko($params);

          $korkosumma = 0;
          foreach ($korkorivit as $korkorow) {
            $korkosumma += $korkorow['korkosumma'];
          }
          if ($korkosumma > 0) echo "<td align='right'>$korkosumma</td>";
          else echo "<td></td>";

          if ($maksurow["korkolaspvm"] != '0000-00-00') echo "<td align='right'>".pupe_DataTablesEchoSort($maksurow['korkolaspvm']).tv1dateconv($maksurow["korkolaspvm"])."</td>";
          else echo "<td></td>";

          echo "<td align='right' nowrap>";

          $linkki  = "<a href='".$palvelin2."muutosite.php?tee=E&tunnus=##TUNNUS##";
          $linkki .= "&lopetus=".$lopetus."/SPLIT/".$palvelin2."myyntires/";
          $linkki .= "myyntilaskut_asiakasraportti.php////tila=".$tila."//ytunnus=".$ytunnus;
          $linkki .= "//asiakasid=".$asiakasid."//alatila=".$alatila."//lopetus=".$lopetus;
          $linkki .= "//valintra=".$valintra."//savalkoodi=".$savalkoodi."//ppa=".$ppa;
          $linkki .= "//kka=".$kka."//vva=".$vva."//ppl=".$ppl."//kkl=".$kkl."//vvl=".$vvl;
          $linkki .= "'>##NUMERO##</a>";

          hae_maksusuoritukset($maksurow, $linkki);

          echo '<br>';
          echo "</td>";
          echo "</tr>";

          if (!isset($totaali[$maksurow['valkoodi']])) $totaali[$maksurow['valkoodi']] = 0;
          if (!isset($avoimet[$maksurow['valkoodi']])) $avoimet[$maksurow['valkoodi']] = 0;

          if (strtoupper($yhtiorow['valkoodi']) != strtoupper($maksurow['valkoodi'])) {
            $totaali[$maksurow['valkoodi']] += $maksurow['loppusumma_valuutassa'];
            $avoimet[$maksurow['valkoodi']] += $maksurow['avoinsumma_valuutassa'];
          }
          else {
            $totaali[$maksurow['valkoodi']] += $maksurow['loppusumma'];
            $avoimet[$maksurow['valkoodi']] += $maksurow['avoinsumma'];
          }

          if ($korkosumma > 0) $korkoja += $korkosumma;
        }

        echo "</tbody>";

        echo "<tfoot>";
        echo "<tr><th colspan='3'>".t("Yhteensä")."</th>";
        echo "<th style='text-align:right'>";

        if (count($totaali) > 0) {
          foreach ($totaali as $valuutta => $valsumma) {
            echo sprintf('%.2f', $valsumma)." $valuutta<br>";
          }
        }
        echo "</th>";

        echo "<th style='text-align:right'>";

        if (count($avoimet) > 0) {
          foreach ($avoimet as $valuutta => $valsumma) {
            echo sprintf('%.2f', $valsumma)." $valuutta<br>";
          }
        }
        echo "</th>";

        echo "<th colspan='4'></th>";

        echo "<th style='text-align:right'>";
        echo sprintf('%.2f', $korkoja)."<br>";
        echo "</th>";

        echo "<th colspan='2'></th>";

        echo "</tr>";
        echo "</tfoot>";

        echo "</table>";
        echo "</form>";

        echo "<br><table>";

        if ($_email_ok) {

          echo "<tr><th style='width:200px;'>", t("Laskukopiot"), "</th>
              <td><input class='laskunro_checkall' type='checkbox' /> ".t("Valitse kaikki listatut laskut"), "</td>";
          echo "<td>";
          echo t("Lähetä laskukopiot valituista laskuista asiakkaan sähköpostiin"), ": ";

          if (!empty($asiakasrow['talhal_email'])) {
            echo "<form class='tulosta_lasku_email' name='tulosta_lasku_email' method='post'>
                  <input type='hidden' name = 'tee' value = 'TULOSTA_EMAIL_LASKUT'>
                  <input type='hidden' name = 'laskunrot' class='laskunrot' value = ''>
                  <input type='hidden' name = 'asiakasemail' value = '{$asiakasrow['talhal_email']}' />
                  <input type='hidden' name = 'asiakasid' value='{$asiakasrow['tunnus']}' />
                  <input type='hidden' name = 'ytunnus' value='{$ytunnus}' />
                  <input type='hidden' name = 'valintra' value='{$valintra}' />
                  <input type='submit' class='laskunrot_submit' value='{$asiakasrow['talhal_email']}' />
                  </form>";
          }

          if (!empty($asiakasrow['lasku_email'])) {
            echo "<form class='tulosta_lasku_email' name='tulosta_lasku_email' method='post'>
                  <input type='hidden' name = 'tee' value = 'TULOSTA_EMAIL_LASKUT'>
                  <input type='hidden' name = 'laskunrot' class='laskunrot' value = ''>
                  <input type='hidden' name = 'asiakasemail' value = '{$asiakasrow['lasku_email']}' />
                  <input type='hidden' name = 'asiakasid' value='{$asiakasrow['tunnus']}' />
                  <input type='hidden' name = 'ytunnus' value='{$ytunnus}' />
                  <input type='hidden' name = 'valintra' value='{$valintra}' />
                  <input type='submit' class='laskunrot_submit' value='{$asiakasrow['lasku_email']}' />
                  </form>";
          }

          if (!empty($asiakasrow['email'])) {
            echo "<form class='tulosta_lasku_email' name='tulosta_lasku_email' method='post'>
                  <input type='hidden' name = 'tee' value = 'TULOSTA_EMAIL_LASKUT'>
                  <input type='hidden' name = 'laskunrot' class='laskunrot' value = ''>
                  <input type='hidden' name = 'asiakasemail' value = '{$asiakasrow['email']}' />
                  <input type='hidden' name = 'asiakasid' value='{$asiakasrow['tunnus']}' />
                  <input type='hidden' name = 'ytunnus' value='{$ytunnus}' />
                  <input type='hidden' name = 'valintra' value='{$valintra}' />
                  <input type='submit' class='laskunrot_submit' value='{$asiakasrow['email']}' />
                  </form>";
          }

          echo "</td>";
          echo "</tr>";
        }

        echo "<tr><th style='width:200px;'>", t("Laskuraportti"), "<br>(<span id='infoteksti'></span>)</th>
            <td>
              <form id='tulosta_myra' name='tulosta_myra' method='post'>
              <input type='hidden' name = 'tee' value = 'NAYTATILAUS'>
              <input type='hidden' name = 'tiliote' value = '1'>
              <input type='hidden' name = 'ytunnus' value = '{$ytunnus}'>
              <input type='hidden' name = 'asiakasid' value = '{$asiakasid}'>
              <input type='hidden' name = 'alatila' value = '{$alatila}'>
              <input type='hidden' name = 'valintra' value='{$valintra}' />
              <input type='hidden' name = 'alkupvm' value='{$alkupvm}' />
              <input type='hidden' name = 'loppupvm' value='{$loppupvm}' />
              <input type='hidden' name = 'laskuraportti' value='1' />
              <input type='submit' value='", t("Tulosta"), "' onClick=\"js_openFormInNewWindow('tulosta_myra', ''); return false;\">
            </form>
            </td>";

        if ($_email_ok) {
          echo "<td>", t("Lähetä asiakkaan sähköpostiin"), ": ";

          if ($asiakasrow['talhal_email'] != '') {
            echo "<form id='tulosta_tiliote_email' name='tulosta_tiliote_email' method='post'>
              <input type='hidden' name = 'tee' value = 'TULOSTA_EMAIL'>
              <input type='hidden' name = 'email' value = '{$asiakasrow['talhal_email']}'>
              <input type='hidden' name = 'ytunnus' value = '{$ytunnus}'>
              <input type='hidden' name = 'asiakasid' value = '{$asiakasid}'>
              <input type='hidden' name = 'alatila' value = '{$alatila}'>
              <input type='hidden' name = 'pp' id='pp_hidden' value='{$pp}' size=2>
              <input type='hidden' name = 'kk' id='kk_hidden' value='{$kk}' size=2>
              <input type='hidden' name = 'vv' id='vv_hidden' value='{$vv}' size=4>
              <input type='hidden' name = 'valintra' value='{$valintra}' />
              <input type='submit' value='{$asiakasrow['talhal_email']}' />
              </form>";
          }

          if ($asiakasrow['lasku_email'] != '') {
            echo "<form id='tulosta_tiliote_email' name='tulosta_tiliote_email' method='post'>
              <input type='hidden' name = 'tee' value = 'TULOSTA_EMAIL'>
              <input type='hidden' name = 'email' value = '{$asiakasrow['lasku_email']}'>
              <input type='hidden' name = 'ytunnus' value = '{$ytunnus}'>
              <input type='hidden' name = 'asiakasid' value = '{$asiakasid}'>
              <input type='hidden' name = 'alatila' value = '{$alatila}'>
              <input type='hidden' name = 'pp' id='pp_hidden' value='{$pp}' size=2>
              <input type='hidden' name = 'kk' id='kk_hidden' value='{$kk}' size=2>
              <input type='hidden' name = 'vv' id='vv_hidden' value='{$vv}' size=4>
              <input type='hidden' name = 'valintra' value='{$valintra}' />
              <input type='submit' value='{$asiakasrow['lasku_email']}' />
              </form>";
          }

          if ($asiakasrow['email'] != '') {
            echo "<form id='tulosta_tiliote_email' name='tulosta_tiliote_email' method='post'>
              <input type='hidden' name = 'tee' value = 'TULOSTA_EMAIL'>
              <input type='hidden' name = 'email' value = '{$asiakasrow['email']}'>
              <input type='hidden' name = 'ytunnus' value = '{$ytunnus}'>
              <input type='hidden' name = 'asiakasid' value = '{$asiakasid}'>
              <input type='hidden' name = 'alatila' value = '{$alatila}'>
              <input type='hidden' name = 'pp' id='pp_hidden' value='{$pp}' size=2>
              <input type='hidden' name = 'kk' id='kk_hidden' value='{$kk}' size=2>
              <input type='hidden' name = 'vv' id='vv_hidden' value='{$vv}' size=4>
              <input type='hidden' name = 'valintra' value='{$valintra}' />
              <input type='submit' value='{$asiakasrow['email']}' />
              </form>";
          }

          echo "</td>";
        }

        echo "</tr>";
        echo "</table>";
      }
      else {
        echo t("Ei laskuja")."!<br>";
      }
    }
  }

  if ($ytunnus == '') {
    $formi = 'haku';
    $kentta = 'ytunnus';

    js_popup(-100);

    /* hakuformi */
    echo "<br><form name='{$formi}' method='GET'>";
    echo "<input type='hidden' name='alatila' value='etsi'>";
    echo "<table>";
    echo "<tr><th>", t("Asiakas"), ":</th>";
    echo "<td><input type='text' name='ytunnus'> ", asiakashakuohje(), "</td>";
    echo "<td class='back'></td></tr>";

    $sel = (!empty($alatila) and $alatila == 'T') ? "selected" : "";

    echo "<tr><th>", t("Asiakasraportin rajaus"), ":</th>";
    echo "<td><select name='alatila'>
      <option value='Y'>", t("Ytunnuksella"), "</option>
      <option value='T' {$sel}>", t("Asiakkaalla"), "</option>
      </select></td>";
    echo "<td class='back'><input type='submit' class='hae_btn' value = '".t("Etsi")."'></td></tr>";

    echo "</table>";
    echo "</form>";
  }
}

require "inc/footer.inc";

function hae_maksusuoritukset($maksurow, $linkki) {
  global $kukarow, $yhtiorow, $palvelin2;

  // tiliöinneistä haettavat osasuoritukset ensin
  // haetaan kaikki yrityksen rahatilit mysql muodossa
  $query  = "SELECT concat(group_concat(distinct concat('\'',oletus_rahatili) SEPARATOR '\', '),'\'') rahatilit
             FROM yriti
             WHERE yhtio          = '$kukarow[yhtio]'
             and kaytossa         = ''
             and oletus_rahatili != ''";
  $ratire = pupe_query($query);
  $ratiro = mysql_fetch_array($ratire);


  if ($ratiro["rahatilit"] != "") {

    // KASSALIPAS-BLOKKI
    $tilinolisa = '';

    // Etsitään kassalippaan tilinumerot
    $query = "SELECT kassa, pankkikortti, luottokortti
              FROM kassalipas
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$maksurow['kassalipas']}'";
    $keijo = pupe_query($query);
    $ressukka = mysql_fetch_assoc($keijo);

    if (!empty($ressukka['kassa']))        $tilinolisa .= ",'{$ressukka['kassa']}'";
    if (!empty($ressukka['pankkikortti'])) $tilinolisa .= ",'{$ressukka['pankkikortti']}'";
    if (!empty($ressukka['luottokortti'])) $tilinolisa .= ",'{$ressukka['luottokortti']}'";

    $query = "SELECT *
              FROM tiliointi USE INDEX (tositerivit_index)
              WHERE yhtio  = '{$kukarow['yhtio']}'
              AND ltunnus  = '{$maksurow['tunnus']}'
              AND tilino   IN ({$ratiro['rahatilit']}{$tilinolisa})
              AND korjattu = ''";
    $lasktilitre = pupe_query($query);

    // listataan osasuoritukset jos maksupäivä on nollaa tai jos suorituksia on//oli enemmän kuin yksi
    // (eli silloin kun lasku on osasuoritettu//tullaan osasuorittamaan)
    if ($maksurow["mapvm"] == "0000-00-00" or mysql_num_rows($lasktilitre) > 1) {

      // katsotaan kyseisen laskun ensimmäisen kohdistustiedon ajankohta ja
      // otetaan vain sitä edeltävät suoritukset
      $alku_query = "SELECT min(kohdistuspvm) kohdistuspvm
                     FROM suorituksen_kohdistus
                     WHERE yhtio     = '{$kukarow['yhtio']}'
                     AND laskutunnus = '{$maksurow['tunnus']}'";
      $alku_res = pupe_query($alku_query);
      $alku = mysql_result($alku_res, 0);

      if (!$alku) {
        $alku = '3000-01-01';
      }

      while ($lasktilitro = mysql_fetch_array($lasktilitre)) {

        if (strtotime($lasktilitro['laadittu']) < strtotime($alku)) {
          if ($lasktilitro['tilino'] == $ressukka['kassa']) echo t("Käteisellä").": ";
          elseif ($lasktilitro['tilino'] == $ressukka['pankkikortti']) echo t("Pankkikortilla").": ";
          elseif ($lasktilitro['tilino'] == $ressukka['luottokortti']) echo t("Luottokortilla").": ";

          if ($lasktilitro["summa_valuutassa"] != 0
            and $lasktilitro["valkoodi"] != $yhtiorow["valkoodi"]
            and $lasktilitro["valkoodi"] != "") {
            echo "<span style='font-weight:bold'> ".t("Suoritus")."</span> &#124; $lasktilitro[summa_valuutassa] ";
            echo "$lasktilitro[valkoodi] ($lasktilitro[summa] $yhtiorow[valkoodi]) &#124; ";
            echo tv1dateconv($lasktilitro["tapvm"]), " <br>";
          }
          else {
            echo "<span style='font-weight:bold'> ".t("Suoritus")."</span> &#124 ";
            echo "$lasktilitro[summa] $yhtiorow[valkoodi] &#124; ";
            echo tv1dateconv($lasktilitro["tapvm"]), "<br>";
          }
        }
      }
    }
  }

  // sitten uudet suorituksen_kohdistus taulun kautta haetut tapahtumat
  // haetaan käytetyn suorituksen tunnus

  $qry1 = "SELECT group_concat(suoritustunnus) as suoritukset
           FROM suorituksen_kohdistus
           WHERE yhtio     = '{$kukarow['yhtio']}'
           AND laskutunnus = '{$maksurow['tunnus']}'";
  $res1 = pupe_query($qry1);
  $row1 = mysql_fetch_assoc($res1);

  // jos löytyy suorituksia niin jatketaan
  if (!empty($row1['suoritukset'])) {

    // haetaan asiaan kuuluvien laskujen tunnukset
    $qry2 = "SELECT group_concat(laskutunnus) as laskut
             FROM suorituksen_kohdistus
             WHERE yhtio        = '{$kukarow['yhtio']}'
             AND suoritustunnus IN ({$row1['suoritukset']})";
    $res2 = pupe_query($qry2);
    $row2 = mysql_fetch_assoc($res2);

    if (!empty($row2['laskut'])) {
      $qry3 = "SELECT *
               FROM suoritus
               WHERE yhtio = '{$kukarow['yhtio']}'
               AND tunnus  IN ({$row1['suoritukset']})";
      $res3 = pupe_query($qry3);

      // echotaan suoritusten tiedot
      while ($row3 = mysql_fetch_assoc($res3)) {
        echo "<span style='font-weight:bold'> ".t("Suoritus")."</span> &#124; ", $row3['summa'], " ";
        echo $row3['valkoodi'], " &#124; ", tv1dateconv($row3['maksupvm']);

        // ja mahdollinen kommentti
        if (!empty($row3['viesti'])) {
          echo " <img class='tooltip' id='$row3[tunnus]' src='{$palvelin2}pics/lullacons/info.png'>";
          echo "<div id='div_$row3[tunnus]' class='popup' style='width: 500px;'>";
          echo $row3['viesti'];
          echo "</div>";
        }

        echo "<br>";
      }

      // haetaan laskujen tiedot
      $qry4 = "SELECT *
               FROM lasku
               WHERE yhtio  = '{$kukarow['yhtio']}'
               AND tunnus   IN ({$row2['laskut']})
               AND tunnus  != '{$maksurow['tunnus']}'";
      $res4 = pupe_query($qry4);

      // echotaan laskujen tiedot
      while ($row4 = mysql_fetch_assoc($res4)) {

        $vaihdot = array("##TUNNUS##" => $row4['tunnus'], "##NUMERO##" => $row4['laskunro']);

        echo "<span style='font-weight:bold'> ".t("Lasku")."</span> &#124; ";
        echo strtr($linkki, $vaihdot);
        echo " &#124; ", $row4['summa'], " ";
        echo $yhtiorow['valkoodi'], " &#124; ", tv1dateconv($row4['tapvm']), "<br>";

      }
    }
  }

  // haetaan mahdollista kassa-alennusta
  $qry5 = "SELECT *
           FROM suorituksen_kohdistus
           WHERE yhtio     = '{$kukarow['yhtio']}'
           AND laskutunnus = '{$maksurow['tunnus']}'
           AND kaatosumma IS NOT NULL";
  $res5 = pupe_query($qry5);
  $row5 = mysql_fetch_assoc($res5);

  if ($row5['kaatosumma'] != 0) {
    echo "<span style='font-weight:bold'> ".t("Kassa-ale")."</span> &#124; ", $row5['kaatosumma'], " ";
    echo $yhtiorow['valkoodi'], ' &#124; ', tv1dateconv($row5['kohdistuspvm']), '<br>';
  }

  // haetaan vielä mahdolliset luottotappiot ja echotaan
  $qry6 = "SELECT round(SUM(summa*(1+vero/100)), 2) as summa, tapvm
           FROM tiliointi
           WHERE yhtio  = '{$kukarow['yhtio']}'
           AND ltunnus  = '{$maksurow['tunnus']}'
           AND tilino   = '{$yhtiorow['luottotappiot']}'
           AND korjattu = ''";
  $res6 = pupe_query($qry6);
  $row6 = mysql_fetch_assoc($res6);

  if ($row6['summa'] != 0) {
    echo "<span style='font-weight:bold'> ".t("Luottotappio")."</span> &#124; ", $row6['summa'], " ";
    echo $yhtiorow['valkoodi'], ' &#124; ', tv1dateconv($row6['tapvm']), '<br>';
  }
}
