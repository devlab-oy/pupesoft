<?php

if (!isset($tila)) $tila = "";
if (!isset($komm)) $komm = "";

if ($tila != 'ostolasku_kulut') {
  echo "  <SCRIPT LANGUAGE=JAVASCRIPT>
      function verify(msg){
        if (confirm(msg)) {
          return true;
        }
        else {
          skippaa_tama_submitti = true;
          return false;
        }
      }
    </SCRIPT>";
}

if (!function_exists("erottele")) {
  function erottele($pvm) {
    $paluu['pp'] = substr($pvm, -2);
    $paluu['kk'] = substr($pvm, 5, 2);
    $paluu['vv'] = substr($pvm, 0, 4);
    return $paluu;
  }
}

$query = "SELECT *
          FROM lasku
          WHERE tunnus = '$tunnus'";
$result = pupe_query($query);

if (mysql_num_rows($result) != 1) {
  echo t("Tositetta")." $tunnus ".t("ei lˆytynytk‰‰n")."!";
  exit;
}

$trow = mysql_fetch_assoc($result);

$query = "SELECT GROUP_CONCAT(DISTINCT oletus_rahatili) rahatilit,
          GROUP_CONCAT(DISTINCT oletus_kulutili) kulutilit
          FROM yriti
          WHERE yhtio  = '{$kukarow['yhtio']}'
          AND kaytossa = ''";
$tilit_chk_res = pupe_query($query);
$tilit_chk_row = mysql_fetch_assoc($tilit_chk_res);

$not_in_tilit = "";

if (!empty($tilit_chk_row['rahatilit'])) {
  $not_in_tilit = ",{$tilit_chk_row['rahatilit']}";
}

if (!empty($tilit_chk_row['kulutilit'])) {
  if (!empty($not_in_tilit)) $not_in_tilit .= ",{$tilit_chk_row['kulutilit']}";
  else $not_in_tilit = ",{$tilit_chk_row['kulutilit']}";
}

//Poistetaan suoraveloitus
if ($tila == 'A') {
  $query = "UPDATE lasku SET
            suoraveloitus  = '',
            tila           = if(hyvak1 != '', 'H', 'M'),
            hyvaksyja_nyt  = hyvak1,
            h1time         = '',
            h2time         = '',
            h3time         = '',
            h4time         = '',
            h5time         = ''
            WHERE tunnus   = '$tunnus' and
            yhtio          = '$kukarow[yhtio]' and
            suoraveloitus != '' and
            maksu_tili     = ''";
  $result = pupe_query($query);
  $tee = 'E';
  $tila = '';

  // haetaan laskun tiedot uudestaan
  $query = "SELECT *
            FROM lasku
            WHERE tunnus = '$tunnus'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) != 1) {
    echo t("Tositetta")." $tunnus ".t("ei lˆytynytk‰‰n")."!";
    exit;
  }

  $trow = mysql_fetch_assoc($result);
}

// Tasolla 1 ei saa muuttaa tapvm_‰‰ tai maksutili‰
if ($kukarow['taso'] != 1) {

  // Laskun maksutilintarkastus
  if ($tila == 'T' and strtoupper($trow['maa']) == "FI") {

    $pankkitili = $tili;
    $ultilno = strtoupper($ultilno);
    $swift = trim(strtoupper($swift));

    require "inc/pankkitilinoikeellisuus.php";

    if ($pankkitili == '' and $ultilno == '') {
      echo "<font class='error'>".t("Pankkitili on virheellinen")."</font><br>";
      $tee='E';
      $tila='';
    }
    else {
      $tili = $pankkitili;
    }

    if ($trow['ultilno_maa'] != "") $ibanmaa = $trow['ultilno_maa'];
    else $ibanmaa = $trow['maa'];

    // Jos SEPA-maa, tarkistetaan IBAN
    if (tarkista_sepa($ibanmaa) and tarkista_iban($ultilno) != $ultilno) {
      echo "<font class='error'>".t("Virheellinen IBAN!")." $ultilno</font><br>";
      $tee = 'E';
      $tila = '';
    }

    // Jos SEPA-maa, tarkistetaan BIC
    if (tarkista_sepa($ibanmaa) and tarkista_bic($swift) === FALSE) {
      echo "<font class='error'>".t("Virheellinen BIC!")." $swift</font><br>";
      $tee = 'E';
      $tila = '';
    }

  }

  // Jaaha p‰ivit‰mme maksutilin!
  if ($tila == 'T') {

    $kommentti = "(" . $kukarow['nimi'] . "@" . date('Y-m-d') .") ".t('Laskun maksutili muutettiin. Vanha oli %s.', '', $ultilno)."<br>" . $trow['comments'];

    if ($ibanmaa == "FI" or substr($ultilno, 0, 2) == "FI") {
      // Haetaan uuden tilinumeron mukainen swift koodi
      include "inc/pankkitiedot.inc";
      $vastaus = pankkitiedot($ultilno, '');
      $swift = $vastaus['swift'];
    }

    // P‰ivitet‰‰n lasku
    $query = "UPDATE lasku
              SET tilinumero = '',
              ultilno      = '$ultilno',
              swift        = '$swift',
              comments     = '$kommentti'
              WHERE tunnus = '$tunnus'
              and yhtio    = '$kukarow[yhtio]'";
    $result = pupe_query($query);

    $tee = 'M';
    $tila = 'nollaahyvak';
    $nollaahyvak = "(" . $kukarow['nimi'] . "@" . date('Y-m-d') .") ".t("Lasku palautettiin hyv‰ksynt‰‰n koska laskun maksutili muutettiin.")."<br>";
  }

  // Laskun maksutilintarkastus (ulkomaat)
  if ($tila == 'TU') {

    if ($ultilno == '' or $swift == '') {
      echo "<font class='error'>".t("IBAN & SWIFT on annettava")."</font><br>";
      $tee='E';
      $tila='';
    }

    $ultilno     = strtoupper($ultilno);
    $swift       = trim(strtoupper($swift));
    $pankki1     = strtoupper($pankki1);
    $pankki2     = strtoupper($pankki2);
    $pankki3     = strtoupper($pankki3);
    $pankki4     = strtoupper($pankki4);

    if ($trow['ultilno_maa'] != "") $ibanmaa = $trow['ultilno_maa'];
    else $ibanmaa = $trow['maa'];

    // Jos SEPA-maa, tarkistetaan IBAN
    if (tarkista_sepa($ibanmaa) and tarkista_iban($ultilno) != $ultilno) {
      echo "<font class='error'>".t("Virheellinen IBAN!")." $ultilno</font><br>";
      $tee = 'E';
      $tila = '';
    }

    // Jos SEPA-maa, tarkistetaan BIC
    if (tarkista_sepa($ibanmaa) and tarkista_bic($swift) === FALSE) {
      echo "<font class='error'>".t("Virheellinen BIC!")." $swift</font><br>";
      $tee = 'E';
      $tila = '';
    }

  }

  // Jaaha p‰ivit‰mme maksutilin! (ulkomaat)
  if ($tila == 'TU') {
    $query = "SELECT comments, ultilno, swift, pankki_haltija, pankki1, pankki2, pankki3, pankki4, sisviesti1
              FROM lasku
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  = '$tunnus'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) != 1) {
      echo t('lasku kateissa') . "$tunnus</font>";
      exit;
    }

    $trow = mysql_fetch_assoc($result);

    $kommentti = "($kukarow[nimi]@".date('Y-m-d').") ".t("Laskun maksutili muutettiin. Vanha oli").": $trow[pankki_haltija], $trow[ultilno], $trow[swift], $trow[pankki1], $trow[pankki2], $trow[pankki3], $trow[pankki4], $trow[sisviesti1].<br>$trow[comments]";

    if ($ibanmaa == "FI" or substr($ultilno, 0, 2) == "FI") {
      // Haetaan uuden tilinumeron mukainen swift koodi
      include "inc/pankkitiedot.inc";
      $vastaus = pankkitiedot($ultilno, '');
      $swift = $vastaus['swift'];
    }

    //P‰ivitet‰‰n lasku
    $query = "UPDATE lasku SET
              ultilno        = '$ultilno',
              swift          = '$swift',
              pankki_haltija = '$pankki_haltija',
              pankki1        = '$pankki1',
              pankki2        = '$pankki2',
              pankki3        = '$pankki3',
              pankki4        = '$pankki4',
              sisviesti1     = '$ohjeitapankille',
              comments       = '$kommentti'
              WHERE tunnus   = '$tunnus'
              AND yhtio      = '$kukarow[yhtio]'";
    $result = pupe_query($query);

    $tee  = 'M';
    $tila = 'nollaahyvak';
    $nollaahyvak = "(" . $kukarow['nimi'] . "@" . date('Y-m-d') .") ".t("Lasku palautettiin hyv‰ksynt‰‰n koska laskun maksutili muutettiin.")."<br>";
  }

  // Jaaha tarkastamme viestin!
  if ($tila == 'MVIESTI') {
    $viesti = trim($viesti);
    $viite = trim($viite);


    if (strlen($viite) > 0 and substr($viite, 0, 2) != "RF" and tarkista_viite($viite) === FALSE) {
      echo "<br><font class='error'>".t("Viite on v‰‰rin")."!</font><br><br>";
      $tee = 'E';
      $tila = '';
    }

    if (strlen($viite) > 0 and substr($viite, 0, 2) == "RF" and tarkista_rfviite($viite) === FALSE) {
      echo "<br><font class='error'>".t("RF-Viite on v‰‰rin")."!</font><br><br>";
      $tee = 'E';
      $tila = '';
    }

    if (strlen($viite) > 0 and strlen($viesti) > 0) {
      echo "<br><font class='error'>".t("Viitett‰ ja viesti‰ ei voi antaa yhtaikaa")."!</font><br><br>";
      $tee = 'E';
      $tila = '';
    }

    if (strlen($viite) == 0 and $viesti == "") {
      echo "<br><font class='error'>".t("Viesti ei saa olla tyhj‰")."!</font><br><br>";
      $tee  = 'E';
      $tila = '';
    }
  }

  // Jaaha p‰ivit‰mme viestin!
  if ($tila == 'MVIESTI') {
    //P‰ivitet‰‰n lasku
    $query = "UPDATE lasku
              set viesti='$viesti', viite='$viite'
              WHERE tunnus = '$tunnus' and yhtio = '$kukarow[yhtio]'";
    $result = pupe_query($query);

    $tee = 'E';
    $tila = 'X';
  }

  //T‰‰ll‰ tarkistetaan pvm
  if ($tila == 'P') {
    if ($vv < 1000) $vv += 2000;
    $val = checkdate($kk, $pp, $vv);

    if (!$val) {
      echo "<font class='error'>".t("Virheellinen tapahtumapvm")." $vv-$kk-$pp</font><br>";
      $tee = 'E';
      $tila = '';
    }

    $pvm = date("Y-m-d", mktime(0, 0, 0, $kk, $pp, $vv));

    if ($pvm < $yhtiorow['tilikausi_alku']) {
      echo "<font class='error'>".t("Tapahtumapvm ei ole kuluvalla tilikaudella")." $pvm</font><br>";
      $tee = 'E';
      $tila = '';
    }

    if ($trow['tapvm'] < $yhtiorow['tilikausi_alku']) {
      echo "<font class='error'>".t("Lasku ei ole kuluvalla tilikaudella")." $trow[tapvm]</font><br>";
      $tee = 'E';
      $tila = '';
    }
  }

  //T‰‰ll‰ p‰ivitet‰‰n muun tositteen pvm
  if ($tila == 'P') {

    // P‰ivitet‰‰n myyntilaskulle "popvm"-jotta me saadaan osittain k‰teisell‰ maksetut laskut
    // n‰kym‰‰n k‰teismyynnit-raportissa oikealla p‰iv‰ll‰ vaikka mapvm on nolla
    // T‰m‰ siis vain jos myyntlaskun popvm oli ennest‰‰n setattu
    $query = "UPDATE lasku
              SET tapvm = '$vv-$kk-$pp',
              popvm        = if (tila = 'U' and popvm > 0, '$vv-$kk-$pp', popvm)
              WHERE tunnus = '$tunnus'
              AND yhtio    = '$kukarow[yhtio]'
              AND tapvm    = '$trow[tapvm]'";
    $result = pupe_query($query);

    $query = "LOCK table tiliointi WRITE";
    $result = pupe_query($query);

    $query = "SELECT tunnus FROM tiliointi WHERE ltunnus = '$tunnus' and yhtio = '$kukarow[yhtio]' and tapvm = '$trow[tapvm]' and korjattu=''";
    $result = pupe_query($query);

    while ($xrow=mysql_fetch_assoc($result)) {
      kopioitiliointi($xrow['tunnus'], $kukarow['kuka']);
    }

    $query = "UPDATE tiliointi SET tapvm = '$vv-$kk-$pp', laadittu=now(), laatija='$kukarow[kuka]'
              WHERE ltunnus = '$tunnus' and yhtio = '$kukarow[yhtio]' and tapvm = '$trow[tapvm]' and korjattu=''";
    $result = pupe_query($query);

    $query = "UNLOCK table";
    $result = pupe_query($query);

    $tee = 'E';
    $tila = 'X';
  }

  if ($tila == 'MAKSATUKSEEN') {
    $query = "UPDATE lasku set
              maksaja      = '',
              maksuaika    = '0000-00-00',
              maksu_kurssi = '0',
              maksu_tili   = '',
              tila         = 'M',
              alatila      = '',
              olmapvm      = if(kapvm < now(), erpcm, kapvm),
              popvm        = '',
              comments     = trim(concat(comments, '\n $kukarow[nimi] ".t("siirsi laskun takaisin maksatukseen").".'))
              WHERE tunnus = '$tunnus'
              and yhtio    = '$kukarow[yhtio]'
              and tila     = 'Q'";
    $updresult = pupe_query($query);

    if (mysql_affected_rows() != 1) { // Jotain meni pieleen
      echo "System error Debug --> $query<br>";

      require "inc/footer.inc";
      exit;
    }
    else {
      echo t("Lasku siirrettiin takaisin maksatukseen")."!<br><br>";
    }
  }

  if ($tila == 'ODOTTAMAAN_SUORITUSTA') {

    if (strtotime($trow['mapvm']) > strtotime($trow['tapvm'])) {
      $query = "SELECT GROUP_CONCAT(DISTINCT oletus_rahatili) rahatilit,
                GROUP_CONCAT(DISTINCT oletus_selvittelytili) selvittelytilit
                FROM yriti
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND (oletus_rahatili != '' or oletus_selvittelytili != '')";
      $tilires = pupe_query($query);
      $tilirow = mysql_fetch_assoc($tilires);

      $tilit = array_merge(explode(",", $tilirow['rahatilit']), explode(",", $tilirow['selvittelytilit']), array($yhtiorow['selvittelytili']));
      $tilit = array_unique($tilit);

      // poistetaan tyhj‰t alkiot array_filterin avulla ilman callbackia
      // If no callback is supplied, all entries of input equal to FALSE will be removed.
      $tilit = array_filter($tilit);

      if (count($tilit) > 0) {
        $tilit = "'".implode("','", $tilit)."'";

        // Etsit‰‰n oikea kirjaus
        // Pit‰isi aina lˆyty‰ vain yksi osuma, siksi ei ole while-looppi
        // Rahatiliˆinti
        $query = "SELECT *
                  FROM tiliointi
                  WHERE yhtio  = '{$kukarow['yhtio']}'
                  AND ltunnus  = '{$tunnus}'
                  AND tilino   IN ({$tilit})
                  AND tapvm    = '{$trow['mapvm']}'
                  AND korjattu = ''";
        $tiliointires = pupe_query($query);
        $tiliointirow = mysql_fetch_assoc($tiliointires);

        // Etsit‰‰n Ostovelkatiliˆinti
        $query = "SELECT tunnus
                  FROM tiliointi
                  WHERE yhtio  = '{$kukarow['yhtio']}'
                  AND ltunnus  = '{$tunnus}'
                  AND tapvm    = '{$trow['mapvm']}'
                  AND tilino   IN ('{$yhtiorow['ostovelat']}', '{$yhtiorow['konserniostovelat']}')
                  AND summa    = '{$trow['summa']}'
                  AND korjattu = ''";
        $ostovelkares = pupe_query($query);
        $ostovelkarow = mysql_fetch_assoc($ostovelkares);

        if (mysql_num_rows($tiliointires) == 1 and mysql_num_rows($ostovelkares) == 1) {
          // Yliviivataan molemmat tiliˆinnit
          $query = "UPDATE tiliointi SET
                    korjattu    = '{$kukarow['kuka']}',
                    korjausaika = now()
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND tunnus  IN ({$tiliointirow['tunnus']}, {$ostovelkarow['tunnus']})";
          $updres = pupe_query($query);

          // Katsotaan onko tiliˆinneill‰ kassa-alea
          $query = "SELECT *
                    FROM tiliointi
                    WHERE yhtio  = '{$kukarow['yhtio']}'
                    AND ltunnus  = '{$tunnus}'
                    AND tapvm    = '{$trow['mapvm']}'
                    AND tilino   = '{$yhtiorow['kassaale']}'
                    AND korjattu = ''";
          $kassaale_chk_res = pupe_query($query);

          if (mysql_num_rows($kassaale_chk_res) != 0) {

            $kassaale_chk_row = mysql_fetch_assoc($kassaale_chk_res);

            $query = "UPDATE tiliointi SET
                      korjattu    = '{$kukarow['kuka']}',
                      korjausaika = now()
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      AND tunnus  = '{$kassaale_chk_row['tunnus']}'";
            $updres = pupe_query($query);

            if ($kassaale_chk_row['vero'] != 0) {

              // Kassa-ale alv
              $query = "UPDATE tiliointi SET
                        korjattu      = '{$kukarow['kuka']}',
                        korjausaika   = now()
                        WHERE yhtio   = '{$kukarow['yhtio']}'
                        AND ltunnus   = '{$tunnus}'
                        AND aputunnus = '{$kassaale_chk_row['tunnus']}'
                        AND korjattu  = ''";
              $updres = pupe_query($query);
            }
          }

          // Ylikirjataan valuuttaero jos on valuuttaero-kirjaus
          $query = "UPDATE tiliointi SET
                    korjattu     = '{$kukarow['kuka']}',
                    korjausaika  = now()
                    WHERE yhtio  = '{$kukarow['yhtio']}'
                    AND ltunnus  = '{$tunnus}'
                    AND tapvm    = '{$trow['mapvm']}'
                    AND tilino   = '{$yhtiorow['valuuttaero']}'
                    AND vero     = 0
                    AND korjattu = ''";
          $valuuttaero_upd_res = pupe_query($query);

          $query = "UPDATE lasku SET
                    mapvm        = '0000-00-00',
                    maksu_kurssi = 0,
                    tila         = 'Q',
                    comments     = trim(concat(comments, '\n $kukarow[nimi] ".t("siirsi maksetun laskun takaisin odottamaan suoritusta").".'))
                    WHERE yhtio  = '{$kukarow['yhtio']}'
                    AND tunnus   = '{$tunnus}'";
          $updres = pupe_query($query);

          echo "<font class='message'>", t("Lasku siirrettiin odottamaan suoritusta"), "!</font><br /><br />";
        }
        else {
          echo "<font class='error'>", t("Ei lˆytynyt oikea m‰‰r‰ kirjauksia"), "!</font><br /><br />";
        }
      }
      else {
        echo "<font class='error'>", t("Ei lˆytynyt tarvittavia tilej‰"), "!</font><br /><br />";
      }
    }
    else {
      echo "<font class='error'>", t("Laskun maksup‰iv‰m‰‰r‰ on ennen tapahtumap‰iv‰m‰‰r‰‰"), "!</font><br /><br />";
    }

    $tee = 'E';
    $tila = 'X';
  }
}

// T‰‰ll‰ tarkistetaan laskun uusi er‰pvm
if ($tila == 'R') {
  if ($vv < 1000) $vv += 2000;
  $val = checkdate($kk, $pp, $vv);
  if (!$val) {
    echo "<font class='error'>".t("Virheellinen er‰pvm")." $vv-$kk-$pp</font><br>";
    $tee = 'E';
    $tila = '';
  }
  else {
    $pvm = date("Y-m-d", mktime(0, 0, 0, $kk, $pp, $vv));
    if ($pvm < $trow['tapvm']) {
      echo "<font class='error'>".t("Er‰pvm on ennen laskunp‰iv‰yst‰")." $pvm, $trow[tapvm]</font><br>";
      $tee = 'E';
      $tila = '';
    }
  }
  if ($trow['mapvm'] != '0000-00-00') {
    echo "<font class='error'>".t("Lasku on  maksettu!")." $trow[mapvm]</font><br>";
    $tee = 'E';
    $tila = '';
  }
}

//T‰‰ll‰ p‰ivitet‰‰n laskun uusi er‰pvm
if ($tila == 'R') {

  $rlisa='';

  // Vanha er‰pvm on myˆs oletusmaksupvm, joten sekin on muutettava
  if ($trow['olmapvm'] == $trow['erpcm']) {
    $rlisa = ", olmapvm = '$pvm'";
  }

  $query = "UPDATE lasku SET erpcm = '$pvm' $rlisa WHERE tunnus = '$tunnus' and yhtio = '$kukarow[yhtio]'";

  $result = pupe_query($query);
  $tee = 'E';
  $tila = 'X';
}

//T‰‰ll‰ p‰ivitet‰‰n maksuehto
if ($tila == 'maksuehto') {

  // Etsit‰‰n lasku
  $query = "SELECT summa, summa_valuutassa FROM lasku WHERE tunnus = '$tunnus' and yhtio = '$kukarow[yhtio]'";
  $lasres = pupe_query($query);
  $lasrow = mysql_fetch_assoc($lasres);

  // Etsit‰‰n maksuehto
  $query = "SELECT *
            FROM maksuehto
            WHERE tunnus = '$maksuehto' and yhtio = '$kukarow[yhtio]'";
  $mehres = pupe_query($query);
  $mehrow = mysql_fetch_assoc($mehres);

  if ($mehrow['kassa_abspvm'] !== null or $mehrow["kassa_relpvm"] > 0) {
    if ($mehrow['kassa_abspvm'] === null) {
      $kassa_erapvm = "adddate(tapvm, interval $mehrow[kassa_relpvm] day)";
    }
    else {
      $kassa_erapvm = "'$mehrow[kassa_abspvm]'";
    }

    $kassa_loppusumma         = round((float) $lasrow["summa"] * $mehrow['kassa_alepros']/100, 2);
    $kassa_loppusumma_valuutassa   = round((float) $lasrow["summa_valuutassa"] * $mehrow['kassa_alepros']/100, 2);
  }
  else {
    $kassa_erapvm          = "0000-00-00";
    $kassa_loppusumma        = 0;
    $kassa_loppusumma_valuutassa = 0;
  }

  if ($mehrow['abs_pvm'] === null) {
    $uerapvm = "adddate(tapvm, interval $mehrow[rel_pvm] day)";
  }
  else {
    $uerapvm = "'$mehrow[abs_pvm]'";
  }

  $query = "UPDATE lasku
            SET maksuehto     = '$maksuehto',
            erpcm              = $uerapvm,
            kapvm              = $kassa_erapvm,
            kasumma            = '$kassa_loppusumma',
            kasumma_valuutassa = '$kassa_loppusumma_valuutassa'
            WHERE tunnus       = '$tunnus'
            and yhtio          = '$kukarow[yhtio]'";
  $result = pupe_query($query);


  $tee  = 'E';
  $tila = 'X';
}

//T‰‰ll‰ tarkistetaan laskun uusi kassa-alepvm
if ($tila == 'B') {

  if ($vv < 1000) $vv += 2000;
  $val = checkdate($kk, $pp, $vv);

  if (!$val) {
    echo "<font class='error'>".t("Virheellinen kassa-alepvm")." $vv-$kk-$pp</font><br>";
    $tee = 'E';
    $tila = '';
  }
  else {
    $pvm = date("Y-m-d", mktime(0, 0, 0, $kk, $pp, $vv));
    if ($pvm < $trow['tapvm']) {
      echo "<font class='error'>".t("Kassa-alepvm on ennen laskunp‰iv‰yst‰")."</font><br>";
      $tee = 'E';
      $tila = '';
    }
    if ($pvm > $trow['erpcm']) {
      echo "<font class='error'>".t("Kassa-alepvm on er‰pvmn j‰lkeen")."</font><br>";
      $tee = 'E';
      $tila = '';
    }
  }
  if ($trow['mapvm'] != '0000-00-00') {
    echo "<font class='error'>".t("Lasku on  maksettu!")." $trow[mapvm]</font><br>";
    $tee = 'E';
    $tila = '';
  }
}

//T‰‰ll‰ p‰ivitet‰‰n laskun uusi kassa-alepvm
if ($tila == 'B') {
  $query = "UPDATE lasku SET kapvm = '$pvm' WHERE tunnus = '$tunnus' and yhtio = '$kukarow[yhtio]'";
  $result = pupe_query($query);

  $query = "UPDATE lasku SET olmapvm=if(kapvm<now(),if(kapvm='0000-00-00',erpcm,kapvm),erpcm) WHERE tunnus = '$tunnus' and yhtio = '$kukarow[yhtio]'";
  $result = pupe_query($query);

  $tee = 'E';
  $tila = 'X';
}

//T‰‰ll‰ tarkistetaan kapvm ja muutenkin
if ($tila == 'C') {

  if ($vv < 1000) $vv += 2000;
  $val = checkdate($kk, $pp, $vv);

  if (!$val and $summa != 0) {
    echo "<font class='error'>".t("Virheellinen kassapvm")." $vv-$kk-$pp</font><br>";
    $tee = 'E';
    $tila = '';
  }
  elseif ($summa != 0) {
    $pvm = date("Y-m-d", mktime(0, 0, 0, $kk, $pp, $vv));
    if ($pvm < $trow['tapvm']) {
      echo "<font class='error'>".t("Kassa-alepvm on ennen laskunp‰iv‰yst‰")."</font><br>";
      $tee = 'E';
      $tila = '';
    }
    if ($pvm > $trow['erpcm']) {
      echo "<font class='error'>".t("Kassa-alepvm on er‰pvmn j‰lkeen")."</font><br>";
      $tee = 'E';
      $tila = '';
    }
  }

  //Poistetaan p‰iv‰ys
  if ($summa == 0) {
    $vv='0000';
    $kk='00';
    $pp='00';
  }

  if ($trow['mapvm'] != '0000-00-00') {
    echo "<font class='error'>".t("Lasku on  maksettu!")." $trow[mapvm]</font><br>";
    $tee = 'E';
    $tila = '';
  }
}

//T‰‰ll‰ p‰ivitet‰‰n laskun uusi kassa-ale ja kapvm
if ($tila == 'C') {
  // Pilkut pisteiksi
  $summa = str_replace( ",", ".", $summa);
  $summa = $summa * 1;

  // P‰ivitet‰‰n lasku
  $query = "UPDATE lasku SET kasumma = '$summa', kapvm='$vv-$kk-$pp' WHERE tunnus = '$tunnus' and yhtio = '$kukarow[yhtio]'";
  $result = pupe_query($query);

  //P‰ivitet‰‰n kenties oletusmaksup‰iv‰
  $query = "UPDATE lasku SET olmapvm=if(kapvm<now(), erpcm, kapvm) WHERE tunnus = '$tunnus' and yhtio = '$kukarow[yhtio]'";
  $result = pupe_query($query);
  $tee = 'E';
  $tila = 'X';
}

//T‰‰ll‰ tarkistetaan laskun uusi summa
if ($tila == 'Q') {

  if ($trow['tapvm'] < $yhtiorow['tilikausi_alku']) {
    echo "<font class='error'>".t("Tapahtuma ei ole kuluvalla tilikaudella")." $trow[tapvm]</font><br>";
    $tee = 'E';
    $tila = '';
  }

  if ($trow['mapvm'] != '0000-00-00' and $trow['suoraveloitus'] == "") {
    echo "<font class='error'>".t("Lasku on maksettu. Summaa ei voi en‰‰ muuttaa")." $trow[mapvm]</font><br>";
    $tee = 'E';
    $tila = '';
  }
}

//T‰‰ll‰ p‰ivitet‰‰n laskun uusi summa
if ($tila == 'Q') {

  // Pilkut pisteiksi
  $laskun_summa = (float) str_replace( ",", ".", $summa);

  // P‰ivitet‰‰n ostovelkatapahtuma siis euroina!
  $query = "LOCK table lasku WRITE, tiliointi WRITE";
  $result = pupe_query($query);

  // Hetaan laskun tiedot
  $query  = "SELECT *
             FROM lasku
             WHERE lasku.yhtio = '{$kukarow['yhtio']}'
             AND lasku.tunnus  = $tunnus";
  $lasres = pupe_query($query);
  $lasrow = mysql_fetch_assoc($lasres);

  // Haetaan tiliˆinnit
  $query = "SELECT *
            FROM tiliointi
            WHERE yhtio   = '$kukarow[yhtio]'
            AND ltunnus   = $lasrow[tunnus]
            AND aputunnus = 0
            AND korjattu  = ''";
  $xres = pupe_query($query);

  $oletusalv = 0;
  $tiliointien_maara = mysql_num_rows($xres);
  $korjattujen_tiliointien_maara = 0;

  // katotaan onko verkollisia kirjauksia ja otetaan eka verokanta oletusveroksi
  while ($xrow = mysql_fetch_assoc($xres)) {
    if ($xrow['vero'] > 0) {
      $oletusalv = $xrow['vero'];
      break;
    }
  }

  // Katsotaan onko kyseess‰ valuuttalasku
  // ja jos on niin muutetaan laskun summa yhtiˆn valuuttaan
  // koska tiliˆinnit on joka tapauksessa yhtiˆn valuutassa
  if ($lasrow["vienti_kurssi"] != 1) {
    $lasrow["summa"] = round($lasrow["summa"] * $lasrow["vienti_kurssi"], 2);
  }

  mysql_data_seek($xres, 0);

  while ($xrow = mysql_fetch_assoc($xres)) {

    $korjataan = 0;

    // Korjataan summat vain jos tiliˆinnin summa on sama ku laskun summa tai veroton laskun summa
    if ($xrow['vero'] == 0 and $lasrow['summa'] == $xrow['summa']) {
      $korjataan = 1;

      $summa            = round($laskun_summa * $lasrow["vienti_kurssi"], 2);
      $summa_valuutassa = $laskun_summa;
      $alv              = 0;
      $alv_valuutassa   = 0;
    }
    elseif ($xrow['vero'] == 0 and $lasrow['summa'] == (-1 * $xrow['summa'])) {
      $korjataan = 1;

      $summa            = round(-1 * $laskun_summa * $lasrow["vienti_kurssi"], 2);
      $summa_valuutassa = -1 * $laskun_summa;
      $alv              = 0;
      $alv_valuutassa   = 0;
    }
    elseif ($oletusalv > 0 and $xrow['vero'] == 0 and $lasrow['summa'] == round($xrow['summa'] * (1 + $oletusalv/100), 2)) {
      $korjataan = 1;

      $summa            = round($laskun_summa / (1 + $oletusalv / 100) * $lasrow["vienti_kurssi"], 2);
      $summa_valuutassa = $laskun_summa / (1 + $oletusalv / 100);
      $alv              = 0;
      $alv_valuutassa   = 0;
    }
    elseif ($oletusalv > 0 and $xrow['vero'] == 0 and $lasrow['summa'] == round(-1 * $xrow['summa'] * (1 + $oletusalv/100), 2)) {
      $korjataan = 1;

      $summa            = round(-1 * $laskun_summa / (1 + $oletusalv / 100) * $lasrow["vienti_kurssi"], 2);
      $summa_valuutassa = -1 * $laskun_summa / (1 + $oletusalv / 100);
      $alv              = 0;
      $alv_valuutassa   = 0;
    }
    elseif ($xrow['vero'] > 0 and $lasrow['summa'] == round($xrow['summa'] * (1 + $xrow['vero']/100), 2)) {
      $korjataan = 2;

      $summa            = round($laskun_summa / (1 + $xrow['vero'] / 100) * $lasrow["vienti_kurssi"], 2);
      $summa_valuutassa = $laskun_summa / (1 + $xrow['vero'] / 100);
      $alv              = round(($laskun_summa * $lasrow["vienti_kurssi"]) - $summa, 2);
      $alv_valuutassa   = round($laskun_summa - $summa_valuutassa, 2);
    }
    elseif ($xrow['vero'] > 0 and $lasrow['summa'] == round(-1 * $xrow['summa'] * (1 + $xrow['vero']/100), 2)) {
      $korjataan = 2;

      $summa            = round(-1 * $laskun_summa / (1 + $xrow['vero'] / 100) * $lasrow["vienti_kurssi"], 2);
      $summa_valuutassa = -1 * $laskun_summa / (1 + $xrow['vero'] / 100);
      $alv              = round((-1 *  $laskun_summa * $lasrow["vienti_kurssi"]) - $summa, 2);
      $alv_valuutassa   = round((-1 * $laskun_summa) - $summa_valuutassa, 2);
    }

    if ($korjataan != 0) {
      // Tehd‰‰n alkuper‰isest‰ yliviivattu kopio
      kopioitiliointi($xrow['tunnus'], $kukarow['kuka']);

      // Korjataan kirjaus
      $query = "UPDATE tiliointi SET
                summa            = $summa,
                summa_valuutassa = $summa_valuutassa,
                laadittu         = now(),
                laatija          = '$kukarow[kuka]'
                WHERE tunnus     = $xrow[tunnus]";
      pupe_query($query);

      $korjattujen_tiliointien_maara++;

      if ($korjataan == 2) {
        $query = "SELECT *
                  FROM tiliointi
                  WHERE aputunnus = $xrow[tunnus]
                  AND ltunnus     = $lasrow[tunnus]
                  AND yhtio       = '$kukarow[yhtio]'
                  AND korjattu    = ''";
        $ares = pupe_query($query);

        if (mysql_num_rows($ares) == 1) {
          $arow = mysql_fetch_assoc($ares);

          // Tehd‰‰n alkuper‰isest‰ verokirjauksesta yliviivattu kopio
          kopioitiliointi($arow['tunnus'], $kukarow['kuka']);

          // Korjataan verokirjaus
          $query = "UPDATE tiliointi SET
                    summa            = $alv,
                    summa_valuutassa = $alv_valuutassa,
                    laadittu         = now(),
                    laatija          = '$kukarow[kuka]'
                    WHERE tunnus     = $arow[tunnus]";
          pupe_query($query);
        }
      }
    }
  }

  // P‰ivitet‰‰n lasku
  $query = "UPDATE lasku SET
            summa        = $laskun_summa
            WHERE tunnus = '$tunnus'
            and yhtio    = '$kukarow[yhtio]'";
  pupe_query($query);

  $query = "UNLOCK tables";
  pupe_query($query);

  echo t("Laskun summa p‰ivitetty")."! ";

  if ($tiliointien_maara != $korjattujen_tiliointien_maara) {
    echo "<font class='error'>".t("HUOM: Kaikkia tiliˆintej‰ ei osattu p‰ivitt‰‰")."!</font>";
  }

  echo "<br><br>";

  $tee  = 'E';
  $tila = 'X';
}

//T‰‰ll‰ tarkistetaan laskun uusi maksupvm
if ($tila == 'S') {
  if ($vv < 1000) $vv += 2000;
  $val = checkdate($kk, $pp, $vv);

  if (!$val) {
    echo "<font class='error'>".t("Virheellinen maksupvm")." $vv-$kk-$pp</font><br>";
    $tee = 'E';
    $tila = '';
  }
  else {
    $pvm = date("Y-m-d", mktime(0, 0, 0, $kk, $pp, $vv));
    if ($pvm < $trow['tapvm']) {
      echo "<font class='error'>".t("Maksupvm on ennen laskunp‰iv‰yst‰")." $pvm, $trow[tapvm]</font><br>";
      $tee = 'E';
      $tila = '';
    }
    if ($pvm < $yhtiorow['tilikausi_alku']) {
      echo "<font class='error'>".t("Maksupvm ei ole kuluvalla tilikaudella")." $pvm, $trow[tapvm]</font><br>";
      $tee = 'E';
      $tila = '';
    }
  }

  if ($trow['mapvm'] == '0000-00-00') {
    echo "<font class='error'>".t("Laskua ei ole maksettu!")."</font><br>";
    $tee = 'E';
    $tila = '';
  }
}

//T‰‰ll‰ p‰ivitet‰‰n laskun uusi maksupvm
if ($tila == 'S') {

  $query = "UPDATE lasku SET
            mapvm        = '$pvm'
            WHERE tunnus = '$tunnus' and
            yhtio        = '$kukarow[yhtio]'";
  $result = pupe_query($query);

  // Jos noi oli sama ei tehd‰ mit‰‰n
  if ($trow['mapvm'] != $trow['tapvm']) {

    $query = "LOCK table tiliointi WRITE";
    $result = pupe_query($query);

    $query = "SELECT tunnus FROM tiliointi WHERE ltunnus = '$tunnus' and yhtio = '$kukarow[yhtio]' and tapvm = '$trow[mapvm]' and korjattu = ''";
    $result = pupe_query($query);

    while ($xrow=mysql_fetch_assoc($result)) {
      kopioitiliointi($xrow['tunnus'], $kukarow['kuka']);
    }

    $query = "UPDATE tiliointi SET tapvm = '$pvm', laadittu=now(), laatija='$kukarow[kuka]' WHERE ltunnus = '$tunnus' and yhtio = '$kukarow[yhtio]' and tapvm = '$trow[mapvm]' and korjattu=''";
    $result = pupe_query($query);

    $query = "UNLOCK table";
    $result = pupe_query($query);
  }
  else {
    echo "<font class='message'>".t("En siirt‰nyt muita tiliˆintej‰, koska tapahtuma- ja maksupvmt olivat samat")."<br>".t("Siirr‰ nyt suoritustapahtumat k‰sin")."</font>";

    $query = "LOCK table tiliointi WRITE";
    $result = pupe_query($query);

    // Ei myyntitilaus
    if ($trow['tila'] != 'U') {

      $query = "SELECT tunnus
                FROM tiliointi
                WHERE ltunnus = '$tunnus'
                and yhtio     = '$kukarow[yhtio]'
                and tilino    in ('$yhtiorow[ostovelat]', '$yhtiorow[konserniostovelat]')
                and abs(round($trow[summa] * $trow[vienti_kurssi],2) - summa) <= 0.01
                and korjattu  = ''";
      $result = pupe_query($query);

      while ($xrow=mysql_fetch_assoc($result)) {
        kopioitiliointi($xrow['tunnus'], $kukarow['kuka']);
      }

      $query = "UPDATE tiliointi SET
                tapvm         = '$pvm',
                laadittu      = now(),
                laatija       = '$kukarow[kuka]'
                WHERE ltunnus = '$tunnus'
                and yhtio     = '$kukarow[yhtio]'
                and tilino    in ('$yhtiorow[ostovelat]', '$yhtiorow[konserniostovelat]')
                and abs(round($trow[summa] * $trow[vienti_kurssi],2) - summa) <= 0.01
                and korjattu  = ''";
      $result = pupe_query($query);
    }
    else {
      $query = "SELECT tunnus
                FROM tiliointi
                WHERE ltunnus = '$tunnus'
                and yhtio     = '$kukarow[yhtio]'
                and tilino    in ('$yhtiorow[myyntisaamiset]', '$yhtiorow[konsernimyyntisaamiset]')
                and abs($trow[summa] + summa) <= 0.01
                and korjattu  = ''";
      $result = pupe_query($query);

      while ($xrow = mysql_fetch_assoc($result)) {
        kopioitiliointi($xrow['tunnus'], $kukarow['kuka']);
      }

      $query = "UPDATE tiliointi SET
                tapvm         = '$pvm',
                laadittu      = now(),
                laatija       = '$kukarow[kuka]'
                WHERE ltunnus = '$tunnus'
                and yhtio     = '$kukarow[yhtio]'
                and tilino    in ('$yhtiorow[myyntisaamiset]', '$yhtiorow[konsernimyyntisaamiset]')
                and abs($trow[summa] + summa) <= 0.01
                and korjattu  = ''";
      $result = pupe_query($query);
    }
    $query = "UNLOCK table";
    $result = pupe_query($query);
  }
  $tee = 'E';
  $tila = 'X';
}

//Muutetaan laskun tila
if ($tila == 'W') {
  $query = "SELECT nimi, laskunro from lasku where yhtio='$kukarow[yhtio]' and tila='K' and vanhatunnus='$trow[tunnus]'";
  $keikres = pupe_query($query);

  if (!(($trow['tila'] == 'H' or $trow['tila'] == 'M' or $trow['tila'] == 'P' or $trow['tila'] == 'Q' or $trow['tila'] == 'Y') and mysql_num_rows($keikres) == 0)) {
    echo "<font class='error'>".t("Laskun tila tai tapvm on muuttunut tai se on liitetty saapumiseen")."</font><br>";

    if (mysql_num_rows($keikres) > 0) {
      $keikrow = mysql_fetch_assoc($keikres);

      echo "<font class='message'>".t("Saapuminen").": $keikrow[nimi] / $keikrow[laskunro]</font><br>";
    }

    $tee = 'E';
    $tila = '';
  }
}

//Muutetaan laskun tila
if ($tila == 'W') {
  if ($trow['tapvm'] >= $yhtiorow['tilikausi_alku']) {
    // Poistetaan kirjanpidosta mahdolliset varastoviennit
    $query = "UPDATE tiliointi
              SET korjattu = '$kukarow[kuka]',
              korjausaika   = now()
              WHERE ltunnus = '$tunnus'
              AND  yhtio    = '$kukarow[yhtio]'
              AND  korjattu = ''
              AND tilino    in ('$yhtiorow[varasto]', '$yhtiorow[varastonmuutos]', '$yhtiorow[matkalla_olevat]', '$yhtiorow[raaka_ainevarasto]', '$yhtiorow[raaka_ainevarastonmuutos]', '$yhtiorow[varastonmuutos_inventointi]', '$yhtiorow[varastonmuutos_epakurantti]')";
    $result = pupe_query($query);

    // Tehd‰‰n varastotiliˆinnit
    if ($vienti != 'A' and $vienti != 'D' and $vienti != 'G' and $vienti != '') {

      $query = "UPDATE tiliointi
                SET korjattu = '$kukarow[kuka]',
                korjausaika   = now()
                WHERE ltunnus = '$tunnus'
                AND  yhtio    = '$kukarow[yhtio]'
                AND  korjattu = ''
                AND tilino    in ('{$yhtiorow['osto_rahti']}', '{$yhtiorow['osto_kulu']}', '{$yhtiorow['osto_rivi_kulu']}')";
      $result = pupe_query($query);

      // Etsit‰‰n kulutiliˆinnit
      $query = "SELECT tiliointi.*
                FROM tiliointi
                JOIN tili ON (tiliointi.yhtio = tili.yhtio and tiliointi.tilino = tili.tilino)
                LEFT JOIN taso ON (tili.yhtio = taso.yhtio and tili.ulkoinen_taso = taso.taso and taso.tyyppi = 'U')
                WHERE tiliointi.yhtio  = '$kukarow[yhtio]'
                AND tiliointi.ltunnus  = '$tunnus'
                AND tiliointi.tapvm    = '$trow[tapvm]'
                AND tiliointi.tilino   not in ('$yhtiorow[ostovelat]', '$yhtiorow[alv]', '$yhtiorow[konserniostovelat]', '$yhtiorow[matkalla_olevat]', '$yhtiorow[varasto]', '$yhtiorow[varastonmuutos]', '$yhtiorow[raaka_ainevarasto]', '$yhtiorow[raaka_ainevarastonmuutos]', '$yhtiorow[varastonmuutos_inventointi]', '$yhtiorow[varastonmuutos_epakurantti]'
                  {$not_in_tilit}
                )
                AND tiliointi.korjattu = ''
                AND (taso.kayttotarkoitus is null or taso.kayttotarkoitus  in ('','O'))";
      $result = pupe_query($query);

      while ($tiliointirow = mysql_fetch_assoc($result)) {

        $varastotili = $yhtiorow['varasto'];

        if ($vienti == 'C' or $vienti == 'F' or $vienti == 'I' or $vienti == 'J' or $vienti == 'K' or $vienti == 'L') {
          $varastotili = $yhtiorow['matkalla_olevat'];
        }

        $query = "INSERT into tiliointi set
                  yhtio            = '$kukarow[yhtio]',
                  ltunnus          = '$tunnus',
                  tilino           = '$varastotili',
                  kustp            = '$tiliointirow[kustp]',
                  kohde            = '$tiliointirow[kohde]',
                  projekti         = '$tiliointirow[projekti]',
                  tapvm            = '$trow[tapvm]',
                  summa            = $tiliointirow[summa],
                  summa_valuutassa = $tiliointirow[summa_valuutassa],
                  vero             = 0,
                  lukko            = '',
                  tosite           = '$tiliointirow[tosite]',
                  laatija          = '$kukarow[kuka]',
                  laadittu         = now()";
        $xresult = pupe_query($query);

        $varastonmuutostili = $yhtiorow["varastonmuutos"];

        if ($vienti == 'J' or $vienti == 'K' or $vienti == 'L') {
          $varastonmuutostili = $yhtiorow["raaka_ainevarastonmuutos"];
        }

        $query = "INSERT into tiliointi set
                  yhtio            = '$kukarow[yhtio]',
                  ltunnus          = '$tunnus',
                  tilino           = '$varastonmuutostili',
                  kustp            = '$tiliointirow[kustp]',
                  kohde            = '$tiliointirow[kohde]',
                  projekti         = '$tiliointirow[projekti]',
                  tapvm            = '$trow[tapvm]',
                  summa            = $tiliointirow[summa] * -1,
                  summa_valuutassa = $tiliointirow[summa_valuutassa] * -1,
                  vero             = 0,
                  lukko            = '',
                  tosite           = '$tiliointirow[tosite]',
                  laatija          = '$kukarow[kuka]',
                  laadittu         = now()";
        $xresult = pupe_query($query);
      }
    }
    else {

      // Etsit‰‰n kulutiliˆinnit
      $query = "SELECT tiliointi.tunnus, tiliointi.summa, tiliointi.vero, tiliointi.tilino
                FROM tiliointi
                JOIN tili ON (tiliointi.yhtio = tili.yhtio and tiliointi.tilino = tili.tilino)
                LEFT JOIN taso ON (tili.yhtio = taso.yhtio and tili.ulkoinen_taso = taso.taso and taso.tyyppi = 'U')
                WHERE tiliointi.yhtio  = '$kukarow[yhtio]'
                AND tiliointi.ltunnus  = '$tunnus'
                AND tiliointi.tapvm    = '$trow[tapvm]'
                AND tiliointi.tilino   not in ('$yhtiorow[ostovelat]', '$yhtiorow[alv]', '$yhtiorow[konserniostovelat]', '$yhtiorow[matkalla_olevat]', '$yhtiorow[varasto]', '$yhtiorow[varastonmuutos]', '$yhtiorow[raaka_ainevarasto]', '$yhtiorow[raaka_ainevarastonmuutos]', '$yhtiorow[varastonmuutos_inventointi]', '$yhtiorow[varastonmuutos_epakurantti]', '{$yhtiorow['osto_rahti']}', '{$yhtiorow['osto_kulu']}', '{$yhtiorow['osto_rivi_kulu']}' {$not_in_tilit})
                AND tiliointi.korjattu = ''
                AND (taso.kayttotarkoitus is null or taso.kayttotarkoitus  in ('','O'))";
      $result_x = pupe_query($query);

      $query = "SELECT GROUP_CONCAT(tunnus) AS tunnukset
                FROM tiliointi
                WHERE ltunnus = '{$tunnus}'
                AND  yhtio    = '{$kukarow['yhtio']}'
                AND  korjattu = ''
                AND tilino    IN ('{$yhtiorow['osto_rahti']}', '{$yhtiorow['osto_kulu']}', '{$yhtiorow['osto_rivi_kulu']}')";
      $tun_result = pupe_query($query);
      $tun_row = mysql_fetch_assoc($tun_result);

      $aputunnuslisa = $tun_row['tunnukset'] != '' ? " OR aputunnus IN ({$tun_row['tunnukset']})" : "";

      $lkm = mysql_num_rows($result_x);

      while ($tiliointirow = mysql_fetch_assoc($result_x)) {

        if ($trow['osto_rahti'] != 0 or $trow['osto_kulu'] != 0 or $trow['osto_rivi_kulu'] != 0) {
          $summa_kuuluva = etsi_kuuluvat_tilioinnit($tiliointirow['tunnus']);

          $query = "SELECT sum(summa) as summa
                    FROM tiliointi
                    WHERE ltunnus = '{$tunnus}'
                    AND  yhtio    = '{$kukarow['yhtio']}'
                    AND  korjattu = ''
                    AND (tilino IN ('{$yhtiorow['osto_rahti']}', '{$yhtiorow['osto_kulu']}', '{$yhtiorow['osto_rivi_kulu']}') {$aputunnuslisa})";
          $sum_result = pupe_query($query);
          $sum_row = mysql_fetch_assoc($sum_result);

          $summa_kulu = $sum_row['summa'];

          $summa_kulu = $summa_kulu != 0 ? $summa_kulu / $lkm : 0;

          $tiliointirow_summa = $tiliointirow['summa'] + $summa_kuuluva + $summa_kulu;

          $query = "UPDATE tiliointi SET
                    korjattu     = '{$kukarow['kuka']}',
                    korjausaika  = now()
                    WHERE tunnus = '{$tiliointirow['tunnus']}'
                    AND yhtio    = '{$kukarow['yhtio']}'";
          $result = pupe_query($query);

          $vero = $tiliointirow['vero'];
          $tili = $tiliointirow['tilino'];
          $summa = $tiliointirow_summa;

          list($kustp, $kohde, $projekti) = kustannuspaikka_kohde_projekti($tili);

          require "inc/teetiliointi.inc";
        }
      }

      $query = "UPDATE tiliointi
                SET korjattu = '$kukarow[kuka]',
                korjausaika   = now()
                WHERE ltunnus = '$tunnus'
                AND  yhtio    = '$kukarow[yhtio]'
                AND  korjattu = ''
                AND (tilino in ('{$yhtiorow['osto_rahti']}', '{$yhtiorow['osto_kulu']}', '{$yhtiorow['osto_rivi_kulu']}') {$aputunnuslisa})";
      $result = pupe_query($query);
    }

    // jos laskun tyyppi ei ole vaihto-omaisuus, nollataan kulut
    $updatelisa = ($vienti != 'C' and $vienti != 'F' and $vienti != 'I') ? " , osto_rahti = 0, osto_kulu = 0, osto_rivi_kulu = 0 " : "";
  }
  else {
    $updatelisa = "";
  }

  $query = "UPDATE lasku
            SET vienti = '{$vienti}'
            {$updatelisa}
            WHERE tunnus = '{$tunnus}'
            AND yhtio    = '{$kukarow['yhtio']}'";
  $result = pupe_query($query);

  $tee = 'E';
  $tila = 'X';
}

// Lis‰t‰‰n kommentti
if ($tila == 'K') {
  $query = "SELECT comments
            FROM lasku
            WHERE yhtio = '$kukarow[yhtio]' and tunnus='$tunnus'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) != 1) {
    echo t('lasku kateissa') . "$tunnus</font>";
    exit;
  }

  $trow = mysql_fetch_assoc($result);

  if ($komm != '') {
    $komm = "(" . $kukarow['nimi'] . "@" . date('Y-m-d') .") " . trim($komm) . "<br>" . $trow['comments'];
    $query = "UPDATE lasku set comments = '$komm' WHERE tunnus='$tunnus'";
    $result = pupe_query($query);
  }

  $tee  = 'E';
  $tila = 'X';
}

// P‰ivitet‰‰n alv_tili
if ($tila == 'KK') {
  if ($alv_tili != '') {
    $alv_tili = mysql_real_escape_string($alv_tili);
    $query = "UPDATE lasku SET alv_tili = '$alv_tili' WHERE tunnus = '$tunnus'";
    $result = pupe_query($query);
  }

  $tee = 'E';
  $tila = 'X';
}

if ($tila == "konversio_avaa") {

  // Tarkistetaan lasku
  $query1 = "SELECT *
             FROM lasku
             WHERE yhtio    = '{$kukarow["yhtio"]}'
             AND tunnus     = '$tunnus'
             AND laskuttaja = ''
             AND laatija    = 'konversio'
             AND tila       = 'U'
             AND alatila    = 'X'
             AND valkoodi   = 'EUR'";
  $result1 = pupe_query($query1);

  if (mysql_num_rows($result1) != 1) {
    echo t("Lasku katosi!");
    echo "<br><br>";
    $tee = 'M';
    $tila = '';
  }

  // Tarkistetaan summa
  if ($tila == "konversio_avaa") {
    $laskurow = mysql_fetch_assoc($result1);

    $syotetty_avoinsumma = (float) $syotetty_avoinsumma;
    $saldo_maksettu = $laskurow["summa"] - $syotetty_avoinsumma;

    if ($syotetty_avoinsumma > $laskurow["summa"] or $syotetty_avoinsumma <= 0) {
      echo t("Virheellinen summa!");
      echo "<br><br>";
      $tee = 'M';
      $tila = '';
    }
  }

  // Avataan lasku
  if ($tila == "konversio_avaa") {
    $query1 = "UPDATE lasku SET
               mapvm          = '0000-00-00',
               saldo_maksettu = $saldo_maksettu
               WHERE yhtio    = '{$kukarow["yhtio"]}'
               AND tunnus     = '{$laskurow["tunnus"]}'";
    $result1 = pupe_query($query1);

    // Lis‰t‰‰n myyntisaamiset-tiliˆinti
    $query1 = "INSERT INTO tiliointi SET
               yhtio            = '{$kukarow["yhtio"]}',
               laatija          = 'konversio',
               laadittu         = now(),
               ltunnus          = '{$laskurow["tunnus"]}',
               tilino           = '{$yhtiorow["myyntisaamiset"]}',
               tapvm            = '{$laskurow["tapvm"]}',
               summa            = $syotetty_avoinsumma,
               summa_valuutassa = $syotetty_avoinsumma,
               valkoodi         = '{$laskurow["valkoodi"]}',
               selite           = 'Lasku {$laskurow["nimi"]}',
               vero             = '0',
               lukko            = '1'";
    $result1 = pupe_query($query1);

    // Lis‰t‰‰n dummy vastatiliˆinti
    $query1 = "INSERT INTO tiliointi SET
               yhtio            = '{$kukarow["yhtio"]}',
               laatija          = 'konversio',
               laadittu         = now(),
               ltunnus          = '{$laskurow["tunnus"]}',
               tilino           = '{$yhtiorow["muutkulut"]}',
               tapvm            = '{$laskurow["tapvm"]}',
               summa            = $syotetty_avoinsumma * -1,
               summa_valuutassa = $syotetty_avoinsumma * -1,
               valkoodi         = '{$laskurow["valkoodi"]}',
               selite           = 'Lasku {$laskurow["nimi"]}',
               vero             = '0',
               lukko            = ''";
    $result1 = pupe_query($query1);
  }

  $tee = 'E';
  $tila = 'X';
}

if ($tila == 'jako') {
  require 'inc/jaaostolasku.inc';

  if (jaalasku($tunnus, $syotetty_jakosumma)) {
    $tee = 'E';
    $tila = 'X';
  }
  else {
    $tee = 'M';
    $tila = '';
  }
}

if ($tila == 'nollaahyvak') {

  if (!isset($nollaahyvak) or $nollaahyvak == "") {
    $nollaahyvak = "(" . $kukarow['nimi'] . "@" . date('Y-m-d') .") ".t("Palautti laskun hyv‰ksynt‰‰n")."<br>";
  }

  $query = "UPDATE lasku SET
            tila          = 'H',
            hyvaksyja_nyt = hyvak1,
            h1time        = '',
            h2time        = '',
            h3time        = '',
            h4time        = '',
            h5time        = '',
            comments      = trim(concat('$nollaahyvak', comments))
            WHERE yhtio   = '$kukarow[yhtio]'
            AND tunnus    = '$tunnus'";
  $result = pupe_query($query);

  // haetaan laskun tiedot uudestaan
  $query = "SELECT *
            FROM lasku
            WHERE yhtio = '$kukarow[yhtio]'
            and tunnus  = '$tunnus'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) != 1) {
    echo t("Lasku")." $tunnus ".t("ei lˆytynytk‰‰n")."!";
    exit;
  }

  $trow = mysql_fetch_assoc($result);
  $tee  = 'E';
  $tila = 'X';
}

if ($tila == 'vaihdalaskunumero') {

  $uusi_laskunumero = preg_replace("/[^0-9]/", "", $uusi_laskunumero);

  $query = "UPDATE lasku SET
            laskunro    = '$uusi_laskunumero'
            WHERE yhtio = '$kukarow[yhtio]'
            and tila    in ('H','Y','M','P','Q')
            and tunnus  = '$tunnus'";
  $result = pupe_query($query);

  // haetaan laskun tiedot uudestaan
  $query = "SELECT *
            FROM lasku
            WHERE yhtio = '$kukarow[yhtio]'
            and tunnus  = '$tunnus'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) != 1) {
    echo t("Lasku")." $tunnus ".t("ei lˆytynytk‰‰n")."!";
    exit;
  }

  $trow = mysql_fetch_assoc($result);
  $tee = 'E';
  $tila = 'X';
}

if ($tila == 'vaihdatoimipaikka' and isset($toimipaikka)) {

  $toimipaikka = mysql_escape_string($toimipaikka);

  $query = "UPDATE lasku SET
            yhtio_toimipaikka = '{$toimipaikka}'
            WHERE yhtio       = '{$kukarow['yhtio']}'
            and tila          in ('H','Y','M','P','Q')
            and tunnus        = '$tunnus'";
  $result = pupe_query($query);

  // haetaan laskun tiedot uudestaan
  $query = "SELECT *
            FROM lasku
            WHERE yhtio = '{$kukarow['yhtio']}'
            and tunnus  = '{$tunnus}'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) != 1) {
    echo t("Lasku")." {$tunnus} ".t("ei lˆytynytk‰‰n")."!";
    exit;
  }

  $trow = mysql_fetch_assoc($result);
  $tee = 'E';
  $tila = 'X';
}

if ($tila == 'viikorkomuuta') {
  $query = "UPDATE lasku SET
            viikorkopros = '$viikorkoprossa'
            WHERE yhtio  = '$kukarow[yhtio]'
            and tunnus   = '$tunnus'";
  $result = pupe_query($query);

  // haetaan laskun tiedot uudestaan
  $query = "SELECT *
            FROM lasku
            WHERE yhtio = '$kukarow[yhtio]'
            and tunnus  = '$tunnus'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) != 1) {
    echo t("Lasku")." $tunnus ".t("ei lˆytynytk‰‰n")."!";
    exit;
  }

  $trow = mysql_fetch_assoc($result);
  $tee = 'E';
  $tila = 'X';
}

if ($tila == 'vaihda_lapvm') {

  if ($vvv < 1000) $vvv += 2000;
  $val = checkdate($vkk, $vpp, $vvv);

  if (!$val) {
    echo "<font class='error'>".t("Virheellinen tapahtumapvm")." $vvv-$vkk-$vpp</font><br>";
    $tee = 'E';
    $tila = '';
  }
  else {
    $pvm = date("Y-m-d", mktime(0, 0, 0, $vkk, $vpp, $vvv));
    $query = "UPDATE lasku SET lapvm = '$pvm' WHERE tunnus = '$tunnus' and yhtio = '$kukarow[yhtio]'";
    $result = pupe_query($query);

    $tee = 'E';
    $tila = 'X';
  }
}

// t‰‰ll‰ tarkistetaan ostolaskun kulut
if ($tila == 'ostolasku_kulut') {

  if ($trow['tapvm'] < $yhtiorow['tilikausi_alku'] and $kukarow['taso'] != 3) {
    echo "<font class='error'>", t("Tapahtuma ei ole kuluvalla tilikaudella"), " {$trow['tapvm']}</font><br>";
    $tee = 'M';
    $tila = '';
  }

  // Tehd‰‰n pistemuutos vaan, jos on setattu
  // koska muuten setataan kyseiset muuttujan tyhjiksi
  // ja sit failataan myˆhemmin muuten.
  if (isset($osto_rahti)) {
    $osto_rahti = str_replace(",", ".", trim($osto_rahti));
  }

  if (isset($osto_kulu)) {
    $osto_kulu = str_replace(",", ".", trim($osto_kulu));
  }

  if (isset($osto_rivi_kulu)) {
    $osto_rivi_kulu = str_replace(",", ".", trim($osto_rivi_kulu));
  }

  if (isset($osto_rahti) and ((trim($osto_rahti) != '' and !is_numeric($osto_rahti)) or trim($osto_rahti) == '')) {
    echo "<font class='error'>", t("Vaihto-omaisuuslaskun rahtikulu ei ole numeerinen"), " {$osto_rahti}</font><br>";
    $tee = 'M';
    $tila = '';
  }
  else {
    $osto_rahti = (float) round($osto_rahti, 2);
  }

  if (isset($osto_kulu) and ((trim($osto_kulu) != '' and !is_numeric($osto_kulu)) or trim($osto_kulu) == '')) {
    echo "<font class='error'>", t("Vaihto-omaisuuslaskun kulut ei ole numeerinen"), " {$osto_kulu}</font><br>";
    $tee = 'M';
    $tila = '';
  }
  else {
    $osto_kulu = (float) round($osto_kulu, 2);
  }

  if (isset($osto_rivi_kulu) and ((trim($osto_rivi_kulu) != '' and !is_numeric($osto_rivi_kulu)) or trim($osto_rivi_kulu) == '')) {
    echo "<font class='error'>", t("Vaihto-omaisuuslaskun tuoterivikohtaiset kulut ei ole numeerinen"), " {$osto_rivi_kulu}</font><br>";
    $tee = 'M';
    $tila = '';
  }
  else {
    $osto_rivi_kulu = (float) round($osto_rivi_kulu, 2);
  }
}

// t‰‰ll‰ p‰ivitet‰‰n ostolaskun kulut
if ($tila == 'ostolasku_kulut') {

  $osto_rahti_alv = (float) $osto_rahti_alv;
  $osto_kulu_alv = (float) $osto_kulu_alv;
  $osto_rivi_kulu_alv = (float) $osto_rivi_kulu_alv;

  if (isset($osto_kulu_alv_verollisuus) and $osto_kulu_alv_verollisuus == 'X') {
    $osto_kulu = $osto_kulu * (1 + ($osto_kulu_alv / 100));
  }

  if (isset($osto_rahti_alv_verollisuus) and $osto_rahti_alv_verollisuus == 'X') {
    $osto_rahti = $osto_rahti * (1 + ($osto_rahti_alv / 100));
  }

  if (isset($osto_rivi_kulu_alv_verollisuus) and $osto_rivi_kulu_alv_verollisuus == 'X') {
    $osto_rivi_kulu = $osto_rivi_kulu * (1 + ($osto_rivi_kulu_alv / 100));
  }

  $query = "UPDATE lasku SET
            osto_rahti         = '{$osto_rahti}',
            osto_kulu          = '{$osto_kulu}',
            osto_rivi_kulu     = '{$osto_rivi_kulu}',
            osto_rahti_alv     = '{$osto_rahti_alv}',
            osto_kulu_alv      = '{$osto_kulu_alv}',
            osto_rivi_kulu_alv = '{$osto_rivi_kulu_alv}'
            WHERE yhtio        = '{$kukarow['yhtio']}'
            AND tunnus         = '{$tunnus}'";
  $upd_res = pupe_query($query);

  $query = "UPDATE lasku SET
            osto_rahti         = '{$osto_rahti}',
            osto_kulu          = '{$osto_kulu}',
            osto_rivi_kulu     = '{$osto_rivi_kulu}',
            osto_rahti_alv     = '{$osto_rahti_alv}',
            osto_kulu_alv      = '{$osto_kulu_alv}',
            osto_rivi_kulu_alv = '{$osto_rivi_kulu_alv}'
            WHERE yhtio        = '{$kukarow['yhtio']}'
            AND tila           = 'K'
            AND vanhatunnus    = '{$tunnus}'";
  $upd_res = pupe_query($query);

  // muutettu alkuper‰isi‰ arvoja
  if ($trow['osto_rahti'] != $osto_rahti or $trow['osto_kulu'] != $osto_kulu or $trow['osto_rivi_kulu'] != $osto_rivi_kulu or $osto_rahti_alv != $trow['osto_rahti_alv'] or $osto_kulu_alv != $trow['osto_kulu_alv'] or $osto_rivi_kulu_alv != $trow['osto_rivi_kulu_alv']) {

    $laskun_kulut = array(
      'osto_kulu' => 0,
      'osto_rahti' => 0,
      'osto_rivi_kulu' => 0
    );

    $laskun_kulut_ylikirjaus = array();

    $suljettu_tilikausi = false;
    $trow_tapvm = $trow['tapvm'];
    $tapvmlisa = "AND tiliointi.tapvm = '{$trow['tapvm']}'";

    if ($trow['tapvm'] < $yhtiorow['tilikausi_alku']) {
      $suljettu_tilikausi = true;
      $trow['tapvm'] = date('Y-m-d');
      $tapvmlisa = "";
    }

    // Etsit‰‰n kulutiliˆinnit
    $query = "SELECT tiliointi.*
              FROM tiliointi
              JOIN tili ON (tiliointi.yhtio = tili.yhtio and tiliointi.tilino = tili.tilino)
              LEFT JOIN taso ON (tili.yhtio = taso.yhtio and tili.ulkoinen_taso = taso.taso and taso.tyyppi = 'U')
              WHERE tiliointi.yhtio  = '$kukarow[yhtio]'
              AND tiliointi.ltunnus  = '$tunnus'
              {$tapvmlisa}
              AND tiliointi.tilino   not in ('$yhtiorow[ostovelat]', '$yhtiorow[alv]', '$yhtiorow[konserniostovelat]', '$yhtiorow[matkalla_olevat]', '$yhtiorow[varasto]', '$yhtiorow[varastonmuutos]', '$yhtiorow[raaka_ainevarasto]', '$yhtiorow[raaka_ainevarastonmuutos]', '$yhtiorow[varastonmuutos_inventointi]', '$yhtiorow[varastonmuutos_epakurantti]', '{$yhtiorow['osto_rahti']}', '{$yhtiorow['osto_kulu']}', '{$yhtiorow['osto_rivi_kulu']}'
                {$not_in_tilit}
              )
              AND tiliointi.korjattu = ''
              AND (taso.kayttotarkoitus is null or taso.kayttotarkoitus  in ('','O'))";
    $result_tiliointi = pupe_query($query);

    $varastotili = $yhtiorow['matkalla_olevat'];
    $varastonmuutostili = $yhtiorow["varastonmuutos"];

    $lkm = mysql_num_rows($result_tiliointi);

    while ($tiliointirow = mysql_fetch_assoc($result_tiliointi)) {

      if (($osto_rahti != 0 or $osto_kulu != 0 or $osto_rivi_kulu != 0) or ($osto_rahti == 0 and $osto_kulu == 0 and $osto_rivi_kulu == 0)) {

        $query = "INSERT into tiliointi set
                  yhtio            = '$kukarow[yhtio]',
                  ltunnus          = '$tunnus',
                  tilino           = '$varastotili',
                  kustp            = '$tiliointirow[kustp]',
                  kohde            = '$tiliointirow[kohde]',
                  projekti         = '$tiliointirow[projekti]',
                  tapvm            = '$trow[tapvm]',
                  summa            = $tiliointirow[summa] * -1,
                  summa_valuutassa = $tiliointirow[summa_valuutassa] * -1,
                  vero             = 0,
                  lukko            = '',
                  tosite           = '$tiliointirow[tosite]',
                  laatija          = '$kukarow[kuka]',
                  laadittu         = now()";
        $xresult = pupe_query($query);

        $query = "INSERT into tiliointi set
                  yhtio            = '$kukarow[yhtio]',
                  ltunnus          = '$tunnus',
                  tilino           = '$varastonmuutostili',
                  kustp            = '$tiliointirow[kustp]',
                  kohde            = '$tiliointirow[kohde]',
                  projekti         = '$tiliointirow[projekti]',
                  tapvm            = '$trow[tapvm]',
                  summa            = $tiliointirow[summa],
                  summa_valuutassa = $tiliointirow[summa_valuutassa],
                  vero             = 0,
                  lukko            = '',
                  tosite           = '$tiliointirow[tosite]',
                  laatija          = '$kukarow[kuka]',
                  laadittu         = now()";
        $xresult = pupe_query($query);

        $summa_kuuluva = etsi_kuuluvat_tilioinnit($tiliointirow['tunnus']);

        $query = "UPDATE tiliointi SET
                  korjattu     = '{$kukarow['kuka']}',
                  korjausaika  = now()
                  WHERE tunnus = '{$tiliointirow['tunnus']}'
                  AND yhtio    = '{$kukarow['yhtio']}'";
        $result = pupe_query($query);

        $summa_kulu = 0;
        $summa_kuuluva_kulu_alv_total = 0;

        if ($osto_rahti == 0 or $osto_kulu == 0 or $osto_rivi_kulu == 0 or
          (($osto_rahti != 0 and $osto_rahti != $trow['osto_rahti']) or ($osto_kulu != 0 and $osto_kulu != $trow['osto_kulu']) or ($osto_rivi_kulu != 0 and $osto_rivi_kulu != $trow['osto_rivi_kulu'])) or
          ($osto_rahti_alv != $trow['osto_rahti_alv'] or $osto_kulu_alv != $trow['osto_kulu_alv'] or $osto_rivi_kulu_alv != $trow['osto_rivi_kulu_alv'])) {

          $query = "SELECT tilino, sum(summa) summa, group_concat(tunnus) tunnukset
                    FROM tiliointi
                    WHERE ltunnus = '{$tunnus}'
                    AND yhtio     = '{$kukarow['yhtio']}'
                    AND korjattu  = ''
                    AND tilino    IN ('{$yhtiorow['osto_rahti']}', '{$yhtiorow['osto_kulu']}', '{$yhtiorow['osto_rivi_kulu']}')
                    GROUP BY 1";
          $sum_result = pupe_query($query);

          while ($sum_row = mysql_fetch_assoc($sum_result)) {
            $summa_kulu += $sum_row['summa'];

            $summa_kuuluva_kulu_alv = 0;

            foreach (explode(",", $sum_row['tunnukset']) as $tun) {

              $query = "SELECT vero, summa, tilino, kustp, kohde, projekti, selite
                        FROM tiliointi
                        WHERE yhtio  = '{$kukarow['yhtio']}'
                        AND ltunnus  = '{$tunnus}'
                        AND korjattu = ''
                        AND tunnus   = '{$tun}'";
              $vero_chk_res = pupe_query($query);
              $vero_chk_row = mysql_fetch_assoc($vero_chk_res);

              if (!isset($laskun_kulut_ylikirjaus['osto_rahti'][$vero_chk_row['vero']]['summa'])) $laskun_kulut_ylikirjaus['osto_rahti'][$vero_chk_row['vero']]['summa'] = 0;
              if (!isset($laskun_kulut_ylikirjaus['osto_kulu'][$vero_chk_row['vero']]['summa'])) $laskun_kulut_ylikirjaus['osto_kulu'][$vero_chk_row['vero']]['summa'] = 0;
              if (!isset($laskun_kulut_ylikirjaus['osto_rivi_kulu'][$vero_chk_row['vero']]['summa'])) $laskun_kulut_ylikirjaus['osto_rivi_kulu'][$vero_chk_row['vero']]['summa'] = 0;

              $summa_kuuluva_kulu_alv = etsi_kuuluvat_tilioinnit($tun, false);
              $summa_kuuluva_kulu_alv_total += $summa_kuuluva_kulu_alv;

              if ($sum_row['tilino'] == $yhtiorow['osto_rahti']) {
                $laskun_kulut_ylikirjaus['osto_rahti'][$vero_chk_row['vero']]['summa']     += $vero_chk_row['summa'] + $summa_kuuluva_kulu_alv;
                $laskun_kulut_ylikirjaus['osto_rahti'][$vero_chk_row['vero']]['kustp']     = $vero_chk_row['kustp'];
                $laskun_kulut_ylikirjaus['osto_rahti'][$vero_chk_row['vero']]['kohde']    = $vero_chk_row['kohde'];
                $laskun_kulut_ylikirjaus['osto_rahti'][$vero_chk_row['vero']]['projekti']  = $vero_chk_row['projekti'];
                $laskun_kulut_ylikirjaus['osto_rahti'][$vero_chk_row['vero']]['selite']    = $vero_chk_row['selite'];
              }
              if ($sum_row['tilino'] == $yhtiorow['osto_kulu']) {
                $laskun_kulut_ylikirjaus['osto_kulu'][$vero_chk_row['vero']]['summa']      += $vero_chk_row['summa'] + $summa_kuuluva_kulu_alv;
                $laskun_kulut_ylikirjaus['osto_kulu'][$vero_chk_row['vero']]['kustp']     = $vero_chk_row['kustp'];
                $laskun_kulut_ylikirjaus['osto_kulu'][$vero_chk_row['vero']]['kohde']    = $vero_chk_row['kohde'];
                $laskun_kulut_ylikirjaus['osto_kulu'][$vero_chk_row['vero']]['projekti']  = $vero_chk_row['projekti'];
                $laskun_kulut_ylikirjaus['osto_kulu'][$vero_chk_row['vero']]['selite']    = $vero_chk_row['selite'];
              }
              if ($sum_row['tilino'] == $yhtiorow['osto_rivi_kulu']) {
                $laskun_kulut_ylikirjaus['osto_rivi_kulu'][$vero_chk_row['vero']]['summa']      += $vero_chk_row['summa'] + $summa_kuuluva_kulu_alv;
                $laskun_kulut_ylikirjaus['osto_rivi_kulu'][$vero_chk_row['vero']]['kustp']     = $vero_chk_row['kustp'];
                $laskun_kulut_ylikirjaus['osto_rivi_kulu'][$vero_chk_row['vero']]['kohde']    = $vero_chk_row['kohde'];
                $laskun_kulut_ylikirjaus['osto_rivi_kulu'][$vero_chk_row['vero']]['projekti']  = $vero_chk_row['projekti'];
                $laskun_kulut_ylikirjaus['osto_rivi_kulu'][$vero_chk_row['vero']]['selite']    = $vero_chk_row['selite'];
              }
            }
          }

          $summa_kulu = $summa_kulu != 0 ? $summa_kulu / $lkm : 0;
          $summa_kuuluva_kulu_alv_total = $summa_kuuluva_kulu_alv_total != 0 ? $summa_kuuluva_kulu_alv_total / $lkm : 0;

          $tiliointirow_summa = $tiliointirow['summa'] + $summa_kuuluva + $summa_kulu + $summa_kuuluva_kulu_alv_total;

          if ($osto_rahti == 0 and $osto_kulu == 0 and $osto_rivi_kulu == 0) {
            $summa_kulu = 0;
          }
        }

        if ($osto_rahti != 0 or $osto_kulu != 0 or $osto_rivi_kulu != 0) {
          list($tiliointirow_summa, $_arr) = ostolaskun_kulujen_tiliointi($trow['summa'], $tiliointirow['summa'] + $summa_kuuluva + $summa_kulu + $summa_kuuluva_kulu_alv_total, $osto_rahti, $osto_kulu, $osto_rivi_kulu);
        }

        $vero  = $tiliointirow['vero'];
        $tili  = $tiliointirow['tilino'];
        $summa = $tiliointirow_summa;

        $kustp    = $tiliointirow['kustp'];
        $kohde    = $tiliointirow['kohde'];
        $projekti = $tiliointirow['projekti'];

        $selite = $tiliointirow['selite'];

        require "inc/teetiliointi.inc";

        $tiliointirow['summa'] = $yhtiorow['ostolaskun_kulutilit_kayttaytyminen'] == '' ? $summa : $tiliointirow['summa'];

        // Kulutilionnin kustp
        $kustp_kulutiliointi   = $kustp;
        $kohde_kulutiliointi    = $kohde;
        $projekti_kulutiliointi = $projekti;

        if ($osto_rahti != 0 or $osto_kulu != 0 or $osto_rivi_kulu != 0) {

          // $_arr on saatu ostolaskun_kulujen_tiliointi-funktiosta
          foreach ($_arr as $_k => $_v) {
            $laskun_kulut[$_k] += $_v;
          }
        }
      }

      $query = "INSERT into tiliointi set
                yhtio            = '$kukarow[yhtio]',
                ltunnus          = '$tunnus',
                tilino           = '$varastotili',
                kustp            = '$tiliointirow[kustp]',
                kohde            = '$tiliointirow[kohde]',
                projekti         = '$tiliointirow[projekti]',
                tapvm            = '$trow[tapvm]',
                summa            = $tiliointirow[summa],
                summa_valuutassa = $tiliointirow[summa_valuutassa],
                vero             = 0,
                lukko            = '',
                tosite           = '$tiliointirow[tosite]',
                laatija          = '$kukarow[kuka]',
                laadittu         = now()";
      $xresult = pupe_query($query);

      $query = "INSERT into tiliointi set
                yhtio            = '$kukarow[yhtio]',
                ltunnus          = '$tunnus',
                tilino           = '$varastonmuutostili',
                kustp            = '$tiliointirow[kustp]',
                kohde            = '$tiliointirow[kohde]',
                projekti         = '$tiliointirow[projekti]',
                tapvm            = '$trow[tapvm]',
                summa            = $tiliointirow[summa] * -1,
                summa_valuutassa = $tiliointirow[summa_valuutassa] * -1,
                vero             = 0,
                lukko            = '',
                tosite           = '$tiliointirow[tosite]',
                laatija          = '$kukarow[kuka]',
                laadittu         = now()";
      $xresult = pupe_query($query);
    }

    foreach ($laskun_kulut_ylikirjaus as $key => $arr) {

      foreach ($arr as $_key => $_value) {

        if ($_value['summa'] == 0) continue;

        $tili   = $yhtiorow[$key];
        $vero   = (float) $_key;
        $summa  = -abs($_value['summa'] / $lkm);
        $selite =  $_value["selite"];

        list($kustp, $kohde, $projekti) = kustannuspaikka_kohde_projekti($tili, $_value["kustp"], $_value["kohde"], $_value["projekti"]);

        require "inc/teetiliointi.inc";
      }
    }

    foreach ($laskun_kulut as $key => $value) {

      if ($value == 0) continue;

      $vero   = ${"{$key}_alv"};
      $tili   = $yhtiorow[$key];
      $summa  = $value;

      list($kustp, $kohde, $projekti) = kustannuspaikka_kohde_projekti($tili, $kustp_kulutiliointi, $kohde_kulutiliointi, $projekti_kulutiliointi);

      require "inc/teetiliointi.inc";
    }

    if (count($laskun_kulut)) {

      if (!isset($alv_tili) or $alv_tili == "" or !is_numeric($alv_tili)) {
        $alv_tili = $yhtiorow["alv"];
      }

      $query = "SELECT laadittu, sum(summa) summa
                from tiliointi
                where yhtio   = '{$kukarow['yhtio']}'
                and ltunnus   = '{$tunnus}'
                and tilino    = '{$alv_tili}'
                and korjattu != ''
                group by 1
                order by 1 asc";
      $old_alv_chk_res = pupe_query($query);
      $old_alv_chk_row = mysql_fetch_assoc($old_alv_chk_res);

      $query = "SELECT sum(summa) summa, group_concat(tunnus) tunnukset
                from tiliointi
                where yhtio  = '{$kukarow['yhtio']}'
                and ltunnus  = '{$tunnus}'
                and tilino   = '{$alv_tili}'
                and korjattu = ''";
      $new_alv_chk_res = pupe_query($query);
      $new_alv_chk_row = mysql_fetch_assoc($new_alv_chk_res);

      if (mysql_num_rows($old_alv_chk_res) > 0 and mysql_num_rows($new_alv_chk_res) > 0) {

        $old_alv = (int) ($old_alv_chk_row['summa'] * 10000);
        $new_alv = (int) ($new_alv_chk_row['summa'] * 10000);

        if ($old_alv != $new_alv) {
          $alvin_ero = $old_alv_chk_row['summa'] - $new_alv_chk_row['summa'];

          $query = "SELECT *
                    from tiliointi
                    where yhtio  = '{$kukarow['yhtio']}'
                    and ltunnus  = '{$tunnus}'
                    and tilino   IN ('{$yhtiorow['osto_rahti']}', '{$yhtiorow['osto_kulu']}', '{$yhtiorow['osto_rivi_kulu']}')
                    and korjattu = ''";
          $kulu_chk_res = pupe_query($query);
          $kulu_chk_row = mysql_fetch_assoc($kulu_chk_res);

          $kulu_chk_row['summa'] -= $alvin_ero;

          $query = "UPDATE tiliointi SET
                    summa       = '{$kulu_chk_row['summa']}'
                    where yhtio = '{$kukarow['yhtio']}'
                    and tunnus  = '{$kulu_chk_row['tunnus']}'";
          $upd_kulut = pupe_query($query);

          list($_tunnus, ) = explode(',', $new_alv_chk_row['tunnukset']);

          $query = "UPDATE tiliointi SET
                    summa        = summa + $alvin_ero
                    where yhtio  = '{$kukarow['yhtio']}'
                    and ltunnus  = '{$tunnus}'
                    and tilino   = '{$alv_tili}'
                    and korjattu = ''
                    and tunnus   = '{$_tunnus}'";
          $upd_kulut = pupe_query($query);
        }
      }
    }
  }
}

if ($tila == '') {
  // katotaan onko lasku liitetty saapumiseen
  $query = "SELECT lasku.laskunro, keikka.alatila
            FROM lasku
            JOIN lasku keikka ON (keikka.yhtio = lasku.yhtio and keikka.laskunro = lasku.laskunro and keikka.tila = 'K' and keikka.vanhatunnus = 0)
            WHERE lasku.yhtio     = '$kukarow[yhtio]'
            AND lasku.tila        = 'K'
            AND lasku.vanhatunnus = '$trow[tunnus]'";
  $keikres = pupe_query($query);
  $keikrow = mysql_fetch_assoc($keikres);

  $tila = 'E';
  echo "<table>";

  if ($trow['tila'] == 'X') {
    $pvm = erottele($trow['tapvm']);

    if ($trow['tapvm'] >= $yhtiorow['tilikausi_alku']) {

      echo "  <form method='post'>
          <input type = 'hidden' name = 'lopetus' value = '{$lopetus}'>
          <input type = 'hidden' name='tila' value='P'>
          <input type = 'hidden' name='tee'  value='M'>
          <input type = 'hidden' name='tunnus' value='{$tunnus}'>
          <tr>
          <th>".t("Tapahtumapvm")."</th>
          <td><input type = 'text' name = 'pp' maxlength='2' size='2' value = '$pvm[pp]'>
          <input type = 'text' name = 'kk' maxlength='2' size='2' value = '$pvm[kk]'>
          <input type = 'text' name = 'vv' maxlength='4' size='4' value = '$pvm[vv]'></td>
          <td class='back'><input type = 'submit' value = '".t("P‰ivit‰")."'></td>
          </tr>
          </form>";
    }
  }
  else {
    if ($trow['tapvm'] >= $yhtiorow['tilikausi_alku'] and ($kukarow['taso'] == 2 or $kukarow['taso'] == 3)) {
      $pvm = erottele($trow['tapvm']);
      $lapvm = erottele($trow['lapvm']);

      if ($yhtiorow['ostolaskujen_paivays'] == "1") {
        echo "<form method='post'>
            <input type = 'hidden' name='lopetus' value='{$lopetus}'>
            <input type = 'hidden' name='tila' value='vaihda_lapvm'>
            <input type = 'hidden' name='tee' value='M'>
            <input type = 'hidden' name='tunnus' value='{$tunnus}'>";
        echo "<tr><th>".t("Lapvm")."</th>";
        echo "<td><input type = 'text' name = 'vpp' maxlength='2' size='2' value = '$lapvm[pp]'>
            <input type = 'text' name = 'vkk' maxlength='2' size='2' value = '$lapvm[kk]'>
            <input type = 'text' name = 'vvv' maxlength='4' size='4' value = '$lapvm[vv]'></td>
            <td class='back'><input type = 'submit' value = '".t("P‰ivit‰")."'></td>
            </tr>";
        echo "</form>";
      }

      echo "<form method='post'>
          <input type = 'hidden' name = 'lopetus' value = '{$lopetus}'>
          <input type = 'hidden' name='tila' value='P'>
          <input type = 'hidden' name='tee' value='M'>
          <input type = 'hidden' name='tunnus' value='{$tunnus}'>
          <tr>
          <th>".t("Tapahtumapvm")."</th>
          <td><input type = 'text' name = 'pp' maxlength='2' size='2' value = '$pvm[pp]'>
          <input type = 'text' name = 'kk' maxlength='2' size='2' value = '$pvm[kk]'>
          <input type = 'text' name = 'vv' maxlength='4' size='4' value = '$pvm[vv]'></td>
          <td class='back'><input type = 'submit' value = '".t("P‰ivit‰")."'></td>
          </tr>
          </form>";
    }

    // Lasku on maksamatta tai suoraveloitus
    if ($trow['tila'] < 'N' or $trow['suoraveloitus'] != '') {

      // ei myyntitilaus
      if ($trow['tila'] != 'L' and $trow['tila'] != 'U' and mysql_num_rows($keikres) == 0) {

        if ($trow['tapvm'] >= $yhtiorow['tilikausi_alku']) {
          echo "<form method='post'>
            <input type = 'hidden' name = 'lopetus' value = '{$lopetus}'>
            <input type = 'hidden' name='tila' value='Q'>
            <input type = 'hidden' name='tee' value='M'>
            <input type = 'hidden' name='tunnus' value='{$tunnus}'>
            <tr>
            <th>".t("summa")."</th>
            <td><input type = 'text' name = 'summa' value='$trow[summa]'></td>
            <td class='back'><input type = 'submit' value = '".t("P‰ivit‰")."'></td>
            </tr>
            </form>";
        }
      }

      if ($trow['tila'] != 'L' and $trow['tila'] != 'U') {

        $pvm = erottele($trow['kapvm']);

        echo "<form method='post'>
            <input type = 'hidden' name = 'lopetus' value = '{$lopetus}'>
            <input type = 'hidden' name='tila' value='C'>
            <input type = 'hidden' name='tee' value='M'>
            <input type = 'hidden' name='tunnus' value='{$tunnus}'>
            <tr>
            <th>".t("kassa-ale ja/tai kapvm")."</th>
            <td><input type = 'text' name = 'summa' value='$trow[kasumma]'>
              <input type = 'text' name = 'pp' maxlength='2' size='2' value = '$pvm[pp]'>
              <input type = 'text' name = 'kk' maxlength='2' size='2' value = '$pvm[kk]'>
              <input type = 'text' name = 'vv' maxlength='4' size='4' value = '$pvm[vv]'></td>
            <td class='back'><input type = 'submit' value = '".t("P‰ivit‰")."'></td>
            </tr>
            </form>";
      }
    }

    // Lasku on maksamatta tai myyntilasku
    if (($trow['tila'] < 'N') or ($trow['tila'] == 'U' and $trow['mapvm']=='0000-00-00')) {
      $pvm = erottele($trow['erpcm']);

      echo "<form method='post'>
          <input type = 'hidden' name = 'lopetus' value = '{$lopetus}'>
          <input type = 'hidden' name='tila' value='R'>
          <input type = 'hidden' name='tee' value='M'>
          <input type = 'hidden' name='tunnus' value='{$tunnus}'>
          <tr>
          <th>".t("er‰pvm")."</th>
          <td><input type = 'text' name = 'pp' maxlength='2' size='2' value = '$pvm[pp]'>
          <input type = 'text' name = 'kk' maxlength='2' size='2' value = '$pvm[kk]'>
          <input type = 'text' name = 'vv' maxlength='4' size='4' value = '$pvm[vv]'></td>
          <td class='back'><input type = 'submit' value = '".t("P‰ivit‰")."'></td>
          </tr>
          </form>";

      if (($trow['kapvm'] != '0000-00-00') and ($trow['tila'] == 'U')) {
        $pvm = erottele($trow['kapvm']);

        echo "<form method='post'>
            <input type = 'hidden' name = 'lopetus' value = '{$lopetus}'>
            <input type = 'hidden' name='tila' value='B'>
            <input type = 'hidden' name='tee' value='M'>
            <input type = 'hidden' name='tunnus' value='{$tunnus}'>
            <tr>
            <th>".t("kassa-alepvm")."</th>
            <td><input type = 'text' name = 'pp' maxlength='2' size='2' value = '$pvm[pp]'>
            <input type = 'text' name = 'kk' maxlength='2' size='2' value = '$pvm[kk]'>
            <input type = 'text' name = 'vv' maxlength='4' size='4' value = '$pvm[vv]'></td>
            <td class='back'><input type = 'submit' value = '".t("P‰ivit‰")."'></td>
            </tr>
            </form>";
      }

      // voidaan vaihtaa myyntilaskun mapvm jos se ei ole viel‰ maksettu
      if ($trow['tila'] == 'U' and $trow['mapvm'] == '0000-00-00') {

        echo "<form method='post'>
            <input type = 'hidden' name = 'lopetus' value = '{$lopetus}'>
            <input type = 'hidden' name='tila' value='maksuehto'>
            <input type = 'hidden' name='tee' value='M'>
            <input type = 'hidden' name='tunnus' value='{$tunnus}'>
            <tr>
            <th>".t("Vaihda maksuehto")."</th>";
        echo "<td><select name='maksuehto'>";

        $query = "SELECT *
                  FROM maksuehto
                  WHERE yhtio  = '$kukarow[yhtio]'
                  and kaytossa = ''
                  and (sallitut_maat = '' or sallitut_maat like '%$trow[maa]%')
                  order by jarjestys, teksti";
        $mresult = pupe_query($query);

        while ($row = mysql_fetch_assoc($mresult)) {
          $sel = "";
          if ($trow["maksuehto"] == $row["tunnus"]) {
            $sel = 'selected';
          }
          echo "<option value='$row[tunnus]' $sel>".t_tunnus_avainsanat($row, "teksti", "MAKSUEHTOKV")."</option>";
        }
        echo "</select></td>";
        echo "<td class='back'><input type = 'submit' value = '".t("P‰ivit‰")."'></td>
            </tr>
            </form>";
      }
    }
  }

  // Lasku on suoritettu
  if ((($trow['tila'] == 'Y') or ($trow['tila'] == 'U')) and ($trow['mapvm'] != '0000-00-00')) {
    if ($trow['mapvm'] >= $yhtiorow['tilikausi_alku']) {
      $pvm = erottele($trow['mapvm']);
      echo "<form method='post'>
          <input type = 'hidden' name = 'lopetus' value = '{$lopetus}'>
        <input type = 'hidden' name='tila' value='S'>
        <input type = 'hidden' name='tee' value='M'>
          <input type = 'hidden' name='tunnus' value='{$tunnus}'>
        <tr>
        <th>".t("suorituspvm")."</th>
        <td><input type = 'text' name = 'pp' maxlength='2' size='2' value = '$pvm[pp]'>
        <input type = 'text' name = 'kk' maxlength='2' size='2' value = '$pvm[kk]'>
        <input type = 'text' name = 'vv' maxlength='4' size='4' value = '$pvm[vv]'></td>
        <td class='back'><input type = 'submit' value = '".t("P‰ivit‰")."'></td>
        </tr>
        </form>";
    }
  }

  if (($trow['tila'] == 'H' or $trow['tila'] == 'M' or $trow['tila'] == 'P' or $trow['tila'] == 'Q' or $trow['tila'] == 'Y') and mysql_num_rows($keikres) == 0) {

    $vientia = '';
    $vientib = '';
    $vientic = '';
    $vientid = '';
    $vientie = '';
    $vientif = '';
    $vientig = '';
    $vientih = '';
    $vientii = '';
    $vientij = '';
    $vientik = '';
    $vientil = '';

    if ($trow['vienti']=='A') $vientia = 'selected';
    if ($trow['vienti']=='B') $vientib = 'selected';
    if ($trow['vienti']=='C') $vientic = 'selected';
    if ($trow['vienti']=='D') $vientid = 'selected';
    if ($trow['vienti']=='E') $vientie = 'selected';
    if ($trow['vienti']=='F') $vientif = 'selected';
    if ($trow['vienti']=='G') $vientig = 'selected';
    if ($trow['vienti']=='H') $vientih = 'selected';
    if ($trow['vienti']=='I') $vientii = 'selected';
    if ($trow['vienti']=='J') $vientij = 'selected';
    if ($trow['vienti']=='K') $vientik = 'selected';
    if ($trow['vienti']=='L') $vientil = 'selected';

    echo "<form method='post'>
        <input type = 'hidden' name = 'lopetus' value = '{$lopetus}'>
        <input type = 'hidden' name='tila' value='W'>
        <input type = 'hidden' name='tee' value='M'>
        <input type = 'hidden' name='tunnus' value='{$tunnus}'>
        <tr>
        <th>".t("laskun tyyppi")."</th>";

    echo "
        <td>
          <select name='vienti'>
            <option value='A' $vientia>".t("Kotimaa")."</option>
            <option value='B' $vientib>".t("Kotimaa huolinta/rahti")."</option>
            <option value='C' $vientic>".t("Kotimaa vaihto-omaisuus")."</option>
            <option value='J' $vientij>".t("Kotimaa raaka-aine")."</option>

            <option value='D' $vientid>".t("EU")."</option>
            <option value='E' $vientie>".t("EU huolinta/rahti")."</option>
            <option value='F' $vientif>".t("EU vaihto-omaisuus")."</option>
            <option value='K' $vientik>".t("EU raaka-aine")."</option>

            <option value='G' $vientig>".t("ei-EU")."</option>
            <option value='H' $vientih>".t("ei-EU huolinta/rahti")."</option>
            <option value='I' $vientii>".t("ei-EU vaihto-omaisuus")."</option>
            <option value='L' $vientil>".t("ei-EU raaka-aine")."</option>
          </select>";

    if ($trow['tapvm'] < $yhtiorow['tilikausi_alku']) {
      echo "<br><br>".t("HUOM: Lasku on suljetulla kaudella. Tiliˆintej‰ ei muuteta")."!";
    }

    echo "</td>
        <td class='back'><input type = 'submit' value = '".t("P‰ivit‰")."'></td>
        </tr>
        </form>";
  }

  if (($trow['tila'] == 'M' or $trow['tila'] == 'H' or $trow['tila'] == 'Y' or $trow['tila'] == 'Q')
    and ($kukarow['taso'] == 2 or $kukarow['taso'] == 3)
    and (($trow['tapvm'] >= $yhtiorow['tilikausi_alku']) or ($trow['tapvm'] < $yhtiorow['tilikausi_alku'] and $kukarow['taso'] == 3))
    and ($trow['vienti'] == 'C' or $trow['vienti'] == 'F' or $trow['vienti'] == 'I')
    and $yhtiorow['ostolaskun_kulutilit'] != ''
    and ($yhtiorow['osto_rahti'] != '' or $yhtiorow['osto_kulu'] != '' or $yhtiorow['osto_rivi_kulu'] != '')
    and empty($keikrow)) {

    echo "<form action='' method='post'>";
    echo "<input type='hidden' name='lopetus' value='{$lopetus}' />";
    echo "<input type='hidden' name='tila' value='ostolasku_kulut' />";
    echo "<input type='hidden' name='tee' value='M' />";
    echo "<input type='hidden' name='tunnus' value='{$tunnus}' />";

    for ($i = 1; $i <= $yhtiorow['ostolaskun_kulutilit']; $i++) {
      echo "<tr>";

      switch ($i) {
      case '1':
        if ($yhtiorow['osto_rahti'] != '') {
          echo "<th>", t("Vaihto-omaisuuslaskun rahdit"), "</th>";
          echo "<td>";
          echo "<select name='osto_rahti_alv_verollisuus'>";
          echo "<option value=''>", t("Verollinen arvo"), "</option>";
          echo "<option value='X'>", t("Veroton arvo"), "</option>";
          echo "</select>";
          echo "<input type='text' name='osto_rahti' value='{$trow['osto_rahti']}' />";
          echo "&nbsp;".alv_popup('osto_rahti_alv', $trow['osto_rahti_alv'])."</td>";
        }
        break;
      case '2':
        if ($yhtiorow['osto_rahti'] != '' and $yhtiorow['osto_kulu'] != '') {
          echo "<th>", t("Vaihto-omaisuuslaskun kulut"), "</th>";
          echo "<td>";
          echo "<select name='osto_kulu_alv_verollisuus'>";
          echo "<option value=''>", t("Verollinen arvo"), "</option>";
          echo "<option value='X'>", t("Veroton arvo"), "</option>";
          echo "</select>";
          echo "<input type='text' name='osto_kulu' value='{$trow['osto_kulu']}' />";
          echo "&nbsp;".alv_popup('osto_kulu_alv', $trow['osto_kulu_alv'])."</td>";
        }
        break;
      case '3':
        if ($yhtiorow['osto_rahti'] != '' and $yhtiorow['osto_kulu'] != '' and $yhtiorow['osto_rivi_kulu'] != '') {
          echo "<th>", t("Vaihto-omaisuuslaskun tuoterivikohtaiset kulut"), "</th>";
          echo "<td>";
          echo "<select name='osto_rivi_kulu_alv_verollisuus'>";
          echo "<option value=''>", t("Verollinen arvo"), "</option>";
          echo "<option value='X'>", t("Veroton arvo"), "</option>";
          echo "</select>";
          echo "<input type='text' name='osto_rivi_kulu' value='{$trow['osto_rivi_kulu']}' />";
          echo "&nbsp;".alv_popup('osto_rivi_kulu_alv', $trow['osto_rivi_kulu_alv'])."</td>";
        }
        break;
      }

      if ($i == $yhtiorow['ostolaskun_kulutilit']) {
        echo "<td class='back'><input type='submit' value='", t("P‰ivit‰ kulut"), "' /></td>";
      }

      echo "</tr>";
    }
    echo "</form>";
  }

  if (($trow['tila'] == 'H' or $trow['tila'] == 'M' or $trow['tila'] == 'P' or $trow['tila'] == 'Q' or $trow['tila'] == 'Y') and ($kukarow['taso'] == 2 or $kukarow['taso'] == 3)) {
    echo "<form method='post'>";
    echo "<input type = 'hidden' name='lopetus' value='{$lopetus}'>";
    echo "<input type = 'hidden' name='tila' value='vaihdalaskunumero'>";
    echo "<input type = 'hidden' name='tee' value='M'>";
    echo "<input type = 'hidden' name='tunnus' value='{$tunnus}'>";

    echo "<tr>";
    echo "<th>".t("laskunumero")."</th>";
    echo "<td><input type='text' name='uusi_laskunumero' value='$trow[laskunro]'></td>";
    echo "<td class='back'><input type='submit' value='".t("P‰ivit‰")."'></td>";
    echo "</tr>";
    echo "</form>";

    $toimipaikat_res = hae_yhtion_toimipaikat($kukarow['yhtio']);

    if (mysql_num_rows($toimipaikat_res) != 0) {
      echo "<form method='post'>";
      echo "<input type = 'hidden' name='lopetus' value='{$lopetus}'>";
      echo "<input type = 'hidden' name='tila' value='vaihdatoimipaikka'>";
      echo "<input type = 'hidden' name='tee' value='M'>";
      echo "<input type = 'hidden' name='tunnus' value='{$tunnus}'>";

      echo "<tr>";
      echo "<th>", t("Toimipaikka"), "</th>";
      echo "<td>";
      echo "<select name='toimipaikka'>";
      echo "<option value='0'>", t("Ei toimipaikkaa"), "</option>";

      while ($toimipaikat_row = mysql_fetch_assoc($toimipaikat_res)) {

        $sel = '';

        if ($trow['yhtio_toimipaikka'] == $toimipaikat_row['tunnus']) {
          $sel = ' selected';
          $toimipaikka = $toimipaikat_row['tunnus'];
        }

        echo "<option value='{$toimipaikat_row['tunnus']}'{$sel}>{$toimipaikat_row['nimi']}</option>";
      }

      echo "</select>";
      echo "</td>";
      echo "<td class='back'><input type='submit' value='".t("P‰ivit‰")."'></td>";
      echo "</tr>";
      echo "</form>";
    }
  }

  if (($trow['tila'] == 'M' or $trow['tila'] == 'H' or $trow['tila'] == 'Y' or $trow['tila'] == 'Q') and ($kukarow['taso'] == 2 or $kukarow['taso'] == 3)) {
    echo "<form method='post'>
        <input type = 'hidden' name = 'lopetus' value = '{$lopetus}'>
        <input type = 'hidden' name='tila' value='jako'>
        <input type = 'hidden' name='tee' value='M'>
        <input type = 'hidden' name='tunnus' value='{$tunnus}'>
        <tr>
        <th>".t("Jaa ostolasku kahteen osaan").".<br>".t("Syˆt‰ uuden laskun summa")."</th>
        <td><input type='text' name='syotetty_jakosumma' value=''> $trow[valkoodi]</td>
        <td class='back'><input type = 'submit' value = '".t("Jaa")."'></td>
        </tr>
        </form>";
  }

  // jos meill‰ on suoraveloitus jolla ei ole maksutili‰ (eli tiliˆintej‰ ei ole tehty), se voidaan poistaa jos hyv‰ksytt‰v‰n‰, valmismaksatukseen tai odottaa suoritusta
  if ($trow['suoraveloitus'] != "" and $trow["maksu_tili"] == "" and in_array($trow['tila'], array('Q', 'M', 'H'))) {
    echo "  <form method='post'>
        <input type = 'hidden' name = 'lopetus' value = '{$lopetus}'>
        <input type = 'hidden' name='tee' value='M'>
        <input type = 'hidden' name='tila' value='A'>
        <input type = 'hidden' name='tunnus' value='{$tunnus}'>
        <tr>
        <th>".t("Poista suoraveloitus")."</th>
        <td>Lasku siirtyy takaisin hyv‰ksynt‰‰n</td>
        <td class='back'><input type = 'submit' value = '".t("Poista")."'></td>
        </tr>
        </form>";
  }

  // tutkitaan ollaanko jossain toimipaikassa alv-rekisterˆity
  $query = "SELECT *
            FROM yhtion_toimipaikat
            WHERE yhtio     = '$kukarow[yhtio]'
            and maa        != ''
            and vat_numero != ''
            and toim_alv   != ''";
  $alhire = pupe_query($query);

  // ollaan alv-rekisterˆity
  if (mysql_num_rows($alhire) >= 1) {

    if ($alv_tili == "") {
      $alv_tili = $trow["alv_tili"];
    }

    echo "<form method='post'>";
    echo "<input type = 'hidden' name = 'lopetus' value = '$lopetus'>";
    echo "<input type = 'hidden' name='tunnus' value='$tunnus'>";
    echo "<input type = 'hidden' name='tee' value='M'>";
    echo "<input type = 'hidden' name='tila' value='KK'>";
    echo "<tr>";
    echo "<th>".t("Alv tili")."</th>";
    echo "<td><select name='alv_tili'>";
    echo "<option value='$yhtiorow[alv]'>$yhtiorow[alv] - $yhtiorow[nimi], $yhtiorow[kotipaikka], $yhtiorow[maa]</option>";

    while ($vrow = mysql_fetch_assoc($alhire)) {
      $sel = "";
      if ($alv_tili == $vrow['toim_alv']) {
        $sel = "selected";
      }
      echo "<option value='$vrow[toim_alv]' $sel>$vrow[toim_alv] - $vrow[nimi], $vrow[kotipaikka], $vrow[maa]</option>";
    }

    echo "</select>";
    echo "</td>";
    echo "<td class='back'><input type = 'submit' value = '".t("Muuta")."'></td>";
    echo "</tr>";
    echo "</form>";
  }

  echo "<form method='post'>
      <input type = 'hidden' name='tee' value='M'>
      <input type = 'hidden' name='tila' value='K'>
      <input type = 'hidden' name='lopetus' value='{$lopetus}'>
      <input type = 'hidden' name='tunnus' value='{$tunnus}'>
      <tr>
      <th>".t("Lis‰‰ kommentti")."</th>
      <td><input type = 'text' name = 'komm' size='40' value = '{$komm}'></td>
      <td class='back'><input type = 'submit' value = '", t("Lis‰‰"), "'></td>
      </tr>
      </form>";

  if ($trow['tila'] == 'H' and ($kukarow['taso'] == 2 or $kukarow['taso'] == 3) and $keikrow["alatila"] != "X") {

    if (strtoupper($trow['maa']) == strtoupper($yhtiorow['maa'])) {
      echo "<form method='post'>
          <input type = 'hidden' name='tee' value='M'>
          <input type = 'hidden' name='tila' value='T'>
          <input type = 'hidden' name='lopetus' value='{$lopetus}'>
          <input type = 'hidden' name='tunnus' value='{$tunnus}'>
          <tr>
          <th>".t("Toimittajan tilinumero (kotimainen)")."</th>
          <td><input type = 'text' name = 'tili' size='40' value = '$trow[tilinumero]'></td>
          <td  class='back' rowspan='3'><input type = 'submit' value = '".t("P‰ivit‰")."'></td>
          <td class='back'>".t("Lasku siirtyy takaisin hyv‰ksynt‰‰n")."</td>
          </tr>
          <tr>
          <th>".t('Toimittajan IBAN')."</th>
          <td><input type = 'text' name = 'ultilno' size='40' value = '$trow[ultilno]'></td>
          </tr>
          <tr>
          <th>".t('SWIFT')." / ".t('BIC')."</th>
          <td><input type = 'text' name = 'swift' size='40' value = '$trow[swift]'></td>
          </tr>
          </form>";
    }
    else {
      echo "<form method='post'>
          <input type = 'hidden' name='tee' value='M'>
          <input type = 'hidden' name='tila' value='TU'>
          <input type = 'hidden' name='lopetus' value='{$lopetus}'>
          <input type = 'hidden' name='tunnus' value='{$tunnus}'>
          <tr>
          <th>".t('Pankkihaltija')."</th>
          <td><input type = 'text' name = 'pankki_haltija' size='40' value = '$trow[pankki_haltija]'></td>
          <td  class='back' rowspan='8'><input type = 'submit' value = '".t("P‰ivit‰")."'></td>
          <td class='back'>".t("Lasku siirtyy takaisin hyv‰ksynt‰‰n")."</td>
          </tr>
          <tr>
          <th>".t('Pankkitili 1')."</th>
          <td><input type = 'text' name = 'pankki1' size='40' value = '$trow[pankki1]'></td>
          </tr>
          <tr>
          <th>".t('Pankkitili 2')."</th>
          <td><input type = 'text' name = 'pankki2' size='40' value = '$trow[pankki2]'></td>
          </tr>
          <tr>
          <th>".t('Pankkitili 3')."</th>
          <td><input type = 'text' name = 'pankki3' size='40' value = '$trow[pankki3]'></td>
          </tr>
          <tr>
          <th>".t('Pankkitili 4')."</th>
          <td><input type = 'text' name = 'pankki4' size='40' value = '$trow[pankki4]'></td>
          </tr>
          <tr>
          <th>".t('Ohjeita Pankille')."</th>
          <td><textarea cols='35' rows='7' name='ohjeitapankille'>$trow[sisviesti1]</textarea></td>
          </tr>
          <tr>
          <th>".t('Toimittajan tilinumero / IBAN')."</th>
          <td><input type = 'text' name = 'ultilno' size='40' value = '$trow[ultilno]'></td>
          </tr>
          <tr>
          <th>".t('SWIFT')." / ".t('BIC')."</th>
          <td><input type = 'text' name = 'swift' size='40' value = '$trow[swift]'></td>
          </tr>
          </form>";
    }
  }

  if ($trow['tila'] == 'H' and ($kukarow['taso'] == 2 or $kukarow['taso'] == 3)) {
    echo "<form method='post'>
        <input type = 'hidden' name='tee' value='M'>
        <input type = 'hidden' name='tila' value='MVIESTI'>
        <input type = 'hidden' name='lopetus' value='{$lopetus}'>
        <input type = 'hidden' name='tunnus' value='{$tunnus}'>
        <tr>
        <th>".t('laskun viesti')."</th>
        <td><input type = 'text' name = 'viesti' size='40' value = '$trow[viesti]'></td>
        <td class='back'><input type = 'submit' value = '".t("P‰ivit‰")."'></td>
        </tr>
        <tr>
        <th>".t('laskun viite')."</th>
        <td><input type = 'text' name = 'viite' size='40' value = '$trow[viite]'></td>
        <td class='back'><input type = 'submit' value = '".t("P‰ivit‰")."'></td>
        </tr>
        </form>";
  }

  if ($trow["tila"] == "Q" and ($kukarow['taso'] == 2 or $kukarow['taso'] == 3)) {
    echo "<form method='post'>
        <input type = 'hidden' name='tee' value='M'>
        <input type = 'hidden' name='tila' value='MAKSATUKSEEN'>
        <input type = 'hidden' name='lopetus' value='{$lopetus}'>
        <input type = 'hidden' name='tunnus' value='{$tunnus}'>
        <tr>
        <th>".t("Palauta lasku maksatukseen")."</th>
        <td></td>
        <td class='back'><input type = 'submit' value = '".t("Siirr‰")."'></td>
        </tr>
        </form>";
  }

  if ($trow['tila'] == 'Y' and $trow['mapvm'] != '0000-00-00' and strtotime($trow['mapvm']) > strtotime($trow['tapvm']) and $trow['mapvm'] >= $yhtiorow['tilikausi_alku'] and ($kukarow['taso'] == 2 or $kukarow['taso'] == 3)) {

    $msg = t("Oletko varma, ett‰ haluat palauttaa maksetun laskun odottamaan suoritusta tilaan")."?";

    echo "<tr>
        <th>", t("Palauta odottamaan suoritusta"), "</th>
        <td></td>
        <td class='back'>
          <form method='post' onSubmit = 'return verify(\"$msg\");'>
          <input type = 'hidden' name='tee' value='M'>
          <input type = 'hidden' name='tila' value='ODOTTAMAAN_SUORITUSTA'>
          <input type = 'hidden' name='lopetus' value='{$lopetus}'>
          <input type = 'hidden' name='tunnus' value='{$tunnus}'>
          <input type = 'submit' value = '", t("Siirr‰"), "'>
          </form>
          </td>
        </tr>";
  }

  if (($trow['tila'] == 'M' or $trow['tila'] == 'H') and ($kukarow['taso'] == 1 or $kukarow['taso'] == 2 or $kukarow['taso'] == 3) and $trow["h1time"] != '0000-00-00 00:00:00' and $keikrow["alatila"] != "X") {
    echo "<form method='post'>
        <input type = 'hidden' name='tila' value='nollaahyvak'>
        <input type = 'hidden' name='tee' value='M'>
        <input type = 'hidden' name='lopetus' value='{$lopetus}'>
        <input type = 'hidden' name='tunnus' value='{$tunnus}'>
        <tr>
        <th>".t("Palauta lasku hyv‰ksynt‰kierrokselle")."</th>
        <td></td>
        <td class='back'><input type = 'submit' value = '".t("Siirr‰")."'></td>
        </tr>
        </form>";
  }

  if ($trow["tila"] == "U") {
    // katsotaan onko t‰st‰ laskusta tehty korkolasku
    $korko_query = "SELECT olmapvm, liitostunnus
                    FROM lasku
                    WHERE yhtio = '$kukarow[yhtio]'
                    AND tunnus  = '$trow[tunnus]'
                    AND olmapvm > '0000-00-00'";
    $korko_result = pupe_query($korko_query);

    // jos ei olla niin voidaan nollata viikorkoprossa
    if (mysql_num_rows($korko_result) == 0) {

      $selviikorko = "";

      if ($trow["viikorkopros"] > 0) {
        $selviikorko = "SELECTED";
      }

      echo "<form method='post'>
          <input type = 'hidden' name='tila' value='viikorkomuuta'>
          <input type = 'hidden' name='tee' value='M'>
          <input type = 'hidden' name='lopetus' value='{$lopetus}'>
          <input type = 'hidden' name='tunnus' value='{$tunnus}'>
          <tr>
          <th>".t("Viiv‰styskorkoprosentti")."</th>
          <td>
          <select name='viikorkoprossa'>
            <option value='0'>".t("Ei viiv‰styskorkoa")."</option>
            <option value='$yhtiorow[viivastyskorko]' $selviikorko>".t("Viiv‰styskorko")." $yhtiorow[viivastyskorko]%</option>
          </select>
          </td>
          <td class='back'><input type = 'submit' value = '".t("P‰ivit‰")."'></td>
          </tr>
          </form>";
    }
  }

  if ($trow['tila'] == 'U' and $trow['alatila'] == 'X' and $trow['laatija'] == 'konversio' and $trow['laskuttaja'] == '') {

    $konversio_check = "SELECT count(*) kpl from tiliointi where yhtio = '$kukarow[yhtio]' and ltunnus = '$trow[tunnus]'";
    $konversio_check_res = pupe_query($konversio_check);
    $konversio_check_row = mysql_fetch_assoc($konversio_check_res);

    if ($konversio_check_row["kpl"] == 0) {
      echo "  <form method='post'>
          <input type = 'hidden' name = 'lopetus' value = '{$lopetus}'>
          <input type = 'hidden' name='tila' value='konversio_avaa'>
          <input type = 'hidden' name='tee' value='M'>
          <input type = 'hidden' name='tunnus' value='{$tunnus}'>
          <tr>
          <th>".t("Muuta konversion lasku avoimeksi summalla")."</th>
          <td><input type='text' name='syotetty_avoinsumma' value=''> $trow[valkoodi]</td>
          <td class='back'><input type = 'submit' value = '".t("Avaa lasku")."'></td>
          </tr>
          </form>";
    }
  }

  echo "</table><br><br>";
}

$tee = '';

if ($kukarow['taso'] == 1 and $phpnimi == 'hyvak.php') {
  $tunnus = ''; // Estet‰‰n v‰‰r‰ k‰yttˆliittym‰
}

if ($phpnimi == 'muutosite.php') {
  $tee = 'E'; // Estet‰‰n v‰‰r‰ k‰yttˆliittym‰
}
