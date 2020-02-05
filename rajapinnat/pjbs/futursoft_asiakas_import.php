<?php

// Tehdään SSH-tunneli Örskan palvelimelle
// ssh -f -L 4444:193.185.248.70:4444 -N devlab@193.185.248.70

require_once 'PJBS.php';

if (!isset($require)) {
  $pupe_root_polku = dirname(dirname(dirname(__FILE__)));

  //  //for debug
  //  $pupe_root_polku = "/Users/joonas/Dropbox/Sites/pupesoft";
  //  $pupe_root_polku = "/Users/satu/Sites/pupesoft";
  ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.$pupe_root_polku.PATH_SEPARATOR."/usr/share/pear");
  error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE);
  ini_set("display_errors", 0);

  if (php_sapi_name() == 'cli') {
    // Pupetti includet
    require "inc/connect.inc";
    require "inc/functions.inc";

    // Logitetaan ajo
    cron_log();

    $tee = 'hae_asiakkaat';
    $php_cli = true;
  }
  else {
    require "inc/parametrit.inc";

    echo "<font class='head'>".t('Futursoft asiakkaiden päivitys')."</font><hr>";

    echo_kayttoliittyma();
    $php_cli = false;
  }
}
if ($tee == 'hae_asiakkaat') {
  if ($php_cli) {

    $asnrot = array();
    if ($argv[1] != "") {
      $asnrot = explode(',', $argv[1]);
    }
  }
  else {
    $asnrot = array();
    if (!empty($asiakasnumerot)) {
      $asnrot = explode(',', $asiakasnumerot);
    }
  }
  tarkista_asiakas_futursoftista_ja_tuo_pupesoftiin($asnrot);
}


if (isset($php_cli) and !$php_cli) {
  require "inc/footer.inc";
}

function echo_kayttoliittyma() {
  echo "<form method='POST' action=''>";
  echo "<input type='hidden' name='tee' value='hae_asiakkaat' />";
  echo t("Asiakasnumerot pilkulla eroteltuna").':';
  echo "<input type='text' name='asiakasnumerot' />";
  echo "<br/>";
  echo "<br/>";
  echo "<input type='submit' value='".t("Aja")."' />";
  echo "</form>";
}


function tarkista_asiakas_futursoftista_ja_tuo_pupesoftiin($asiakasnumerot) {
  // kerrotaan iconville merkistöt, pupesoft on latin1 / iso-8859-1 ja Futurin db on myös latin charset, tällä pitäisi korjaantua asiakastietojen ääkkösongelmat
  $drv = new PJBS('ISO-8859-1', 'ISO-8859-1');

  //$con = $drv->connect('jdbc:solid://mergs014:2000/pupesoft/pupesoft', 'pupesoft', 'pupesoft');
  $con = $drv->connect('jdbc:solid://palvelin1:2100/pupesoft/mG1289R!', 'pupesoft', 'mG1289R!');

  if ($con === false) {
    // jdbc ei nappaa
    echo "jdbc ei nappaa\n";
  }
  $ajopaiva = 1;   // Ajetaan jälkeen puolen yön

  $edellinen_paiva = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d")-$ajopaiva, date("Y")));
  $asiakaslisa = "";
  $yhtio = 'atarv';
  $debug = 0;
  $laatija = 'konversio';

  $laskutusviikonpaivat = array(
    1   => "2",
    3   => "-1",
    4   => "-4",
    7   => "2"
  );

  $futurmaksuehdot = array(
    1   => "1551",
    2   => "1551",
    3   => "1551",
    6   => "1551",
    104   => "1551",
    108   => "1551",
    110   => "1551",
    4   => "1535",
    100   => "1520",
    101   => "1515",
    102   => "1513",
    103   => "2019",
    106   => "1551",
    107   => "1514",
    116   => "1494",
    111   => "1525",
    112   => "1500",
    113   => "1496",
    115   => "1538"
  );

  if (!empty($asiakasnumerot)) {
    $asiakaslisa = "AP_ASIAKASNRO IN (".implode(",", $asiakasnumerot).")";
    $res = $drv->exec("SELECT * FROM ASIAKPER WHERE $asiakaslisa AND AP_AKTIIVINEN = 1");

    foreach ($asiakasnumerot as $asiakasnumero) {
      $res = $drv->exec("SELECT * FROM ASIAKPER WHERE AP_ASIAKASNRO = {$asiakasnumero} AND AP_AKTIIVINEN = 1");
      $row = $drv->fetch_array($res);
      if (empty($row)) {
        $res = $drv->exec("SELECT * FROM ASIAKPER WHERE AP_ASIAKASNRO = {$asiakasnumero} AND AP_AKTIIVINEN = 0");
        $row = $drv->fetch_array($res);
        if (!empty($row) and php_sapi_name() != 'cli') {
          if (php_sapi_name() == 'cli') echo "\n";
          else echo "<br/>";
          echo t("Asiakas on").' '.$asiakasnumero.' AP_AKTIIVINEN = 0 ' . t("tilassa joten sitä ei tuotu pupeen");
        }
      }
    }

    $res = $drv->exec("SELECT * FROM ASIAKPER WHERE $asiakaslisa AND AP_AKTIIVINEN = 1");
  }
  else {
    if (php_sapi_name() == 'cli') {
      $res = $drv->exec("SELECT * FROM ASIAKPER WHERE AP_AKTIIVINEN = 1 AND AP_MUUTETTU > '$edellinen_paiva'");
    }
    else {
      echo "ERROR: ".t("Anna asiakasnumero");
      return false;
    }
  }

  $i = 1;

  if (php_sapi_name() == 'cli') echo "\n";
  else echo "<br/>";

  $pupesoft_asiakasnumerot = array();
  $pupesoft_ytunnukset = array();
  while ($row = $drv->fetch_array($res)) {

    echo "{$i}: {$row['AP_ASIAKASNRO']} ";
    if ($debug == 1) {
      print_r($row);
      break;
      echo "<br/>".$row['AP_TOIMITUSOSOITE']."<br/>";

      // Poikkeava toimitusosoite, 1 -> laskutusosoite ja 0 -> toimitusosoite
      list($_nimi, $_nimitark, $_osoite, $_postinopostitp) = explode("\n", $row['AP_TOIMITUSOSOITE']);
      list($_postino, $_postitp) = explode("  ", $_postinopostitp);
      if ($_postinopostitp == "") {
        list($_postino, $_postitp) = explode("  ", $_osoite);
        $_osoite = $_nimitark;
        $_nimitark = "";
        // 2437
        // 2834573
      }

      echo "Nimi: " . $_nimi . "<br/>";
      echo "Nimitark: " . $_nimitark . "<br/>";
      echo "Osoite: " . $_osoite . "<br/>";
      echo "Postino: " . $_postino . "<br/>";
      echo "Postitp: " . $_postitp . "<br/>";


    }
    else {
      // Tehää tänne logiikat datan käsittelyyn
      // Ytunnus ja Ovttunnus
      $ytunnus_poistot = array('-', ' ');
      $ytunnus = str_replace($ytunnus_poistot, '', $row['AP_LY_TUNNUS']);

      if (strlen($ytunnus) == 8) {
        $ovttunnus = '0037'.$ytunnus;
      }
      elseif (strlen($ytunnus) == 7) {
        $ovttunnus = '00370'.$ytunnus;
      }
      else {
        $ovttunnus = $ytunnus;
      }

      if (trim($ytunnus) == "") $ytunnus = $row['AP_ASIAKASNRO'];

      $ytunnus = "FI".$ytunnus;

      $pupesoft_asiakasnumerot[] = $row['AP_ASIAKASNRO'];
      $pupesoft_ytunnukset[$ytunnus] = $ytunnus;

      if (strtolower(substr($ytunnus, 0, 2)) == 'fi') {
        $alv = '24.00';
        $vienti = '';
        $kieli = 'fi';
        $valuutta = 'EUR';
        $maa = 'FI';
      }
      else {
        echo "Maa ei ole FI! ei tehdä mitään asiakasnumerolle $row[AP_ASIAKASNRO]\n";
        continue;
      }

      // chn, vaihtoehtoja 0 ja 1 ja 1 = verkkolasku
      if ($row['AP_FINVOICE'] == 1) {
        $chn = '010'; //Verkkolasku
        $verkkotunnus = $row['AP_OVT_TUNNUS'];
        if (trim($verkkotunnus) == "") $verkkotunnus = $row['AP_IBAN_TILI'];
      }
      else {
        $chn = '100'; //Verkkolasku tulostuspalvelu
        $verkkotunnus = "";
      }

      // Laskutusviikonpv
      $laskutusvkp = $laskutusviikonpaivat[$row['AP_LASKUTUSRYHMA']];
      if ($laskutusvkp == "") {
        $laskutusvkp = 0;
      }

      // Maksuehdot
      $maksuehto = $futurmaksuehdot[$row['AP_MAKSUTAPANRO']];
      if ($maksuehto == "") {
        $maksuehto = 1551; //käteinen
      }

      // Poikkeava toimitusosoite, 1 -> laskutusosoite ja 0 -> toimitusosoite
      list($_nimi, $_nimitark, $_osoite, $_postinopostitp) = explode("\n", $row['AP_TOIMITUSOSOITE']);
      list($_postino, $_postitp) = explode("  ", $_postinopostitp);
      if ($_postinopostitp == "") {
        list($_postino, $_postitp) = explode("  ", $_osoite);
        $_osoite = $_nimitark;
        $_nimitark = "";
      }

      $_nimi = pupesoft_cleanstring($_nimi);
      $_nimitark = pupesoft_cleanstring($_nimitark);
      $_osoite = pupesoft_cleanstring($_osoite);
      $_postino = pupesoft_cleanstring($_postino);
      $_postitp = pupesoft_cleanstring($_postitp);
      $row['AP_NIMI'] = pupesoft_cleanstring($row['AP_NIMI']);
      $row['AP_KATUOSOITE'] = pupesoft_cleanstring($row['AP_KATUOSOITE']);
      $row['AP_POSTINUMERO'] = pupesoft_cleanstring($row['AP_POSTINUMERO']);
      $row['AP_POSTITOIMIPAIKKA'] = pupesoft_cleanstring($row['AP_POSTITOIMIPAIKKA']);

      if ($row['AP_POIKLASKOS'] == 1) {
        $laskutus_nimi = $_nimi;
        $laskutus_nimitark = $_nimitark;
        $laskutus_osoite = $_osoite;
        $laskutus_postino = $_postino;
        $laskutus_postitp = $_postitp;
        $laskutus_maa = $maa;

        $toim_nimi = '';
        $toim_nimitark = '';
        $toim_osoite = '';
        $toim_postino = '';
        $toim_postitp = '';
        $toim_maa = $maa;
      }
      else {
        $laskutus_nimi = '';
        $laskutus_nimitark = '';
        $laskutus_osoite = '';
        $laskutus_postino = '';
        $laskutus_postitp = '';
        $laskutus_maa = $maa;

        $toim_nimi = $_nimi;
        $toim_nimitark = $_nimitark;
        $toim_osoite = $_osoite;
        $toim_postino = $_postino;
        $toim_postitp = $_postitp;
        $toim_maa = $maa;
      }

      // Sähköinen lähete (1 = sähkäposti, tosin tää on fiksu laittaa yhtiön oletukseksi)
      if ($row['AP_SAHKOINEN_LAHETE'] == 1) {
        $lahetetyyppi = '';
      }
      else {
        $lahetetyyppi = 'E';
      }

      // Puhelinnumero, siistitään välilyönti ja väliviiva pois
      $puhnopoistot = array('-', ' ');
      $row['AP_PUHELIN1'] = $row['AP_PUH1SUUNTA'] . str_replace($puhnopoistot, '', $row['AP_PUHELIN1']);

      // Fax, siistitään kuten puhno
      $row['AP_FAX'] = $row['AP_FAXSUUNTA'] . str_replace($puhnopoistot, '', $row['AP_FAX']);

      // Gsm, siistitään kuten puhno
      $row['AP_GSM'] = $row['AP_GSMSUUNTA'] . str_replace($puhnopoistot, '', $row['AP_GSM']);

      // Myyntikielto (luottokielto)
      if ($row['AP_LUOTTOKIELTO'] == 1) {
        $myyntikielto = 'K';
      }
      else {
        $myyntikielto = '';
      }

      // Toimitusehto
      $toimitusehto_query = "SELECT *
                             FROM avainsana
                             WHERE yhtio      = '$yhtio'
                             and laji         = 'TOIMEHTO'
                             and selitetark_2 = '$row[AP_TOIMITUSEHTO]'";
      $toimitusehto_result = mysql_query($toimitusehto_query) or pupe_error($toimitusehto_query);

      if (mysql_num_rows($toimitusehto_result) == 1) {
        $toimitusehto_row = mysql_fetch_assoc($toimitusehto_result);
        $toimitusehto = $toimitusehto_row['selite']." ".$toimitusehto_row['selitetark'];
      }
      else {
        $toimitusehto = '';  //jos jotai falskaa toimitusehdoissa ni laitetaa tyhjä
      }

      $sarakkeet = "  laji               = '',
              tila               = '',
              ytunnus             = '$ytunnus',
              ovttunnus             = '$ovttunnus',
              nimi               = '$row[AP_NIMI]',
              nimitark             = '',
              osoite               = '$row[AP_KATUOSOITE]',
              postino             = '$row[AP_POSTINUMERO]',
              postitp             = '$row[AP_POSTITOIMIPAIKKA]',
              kunta               = '',
              laani               = '',
              maa               = '$maa',
              kansalaisuus           = '$maa',
              tyonantaja             = '',
              ammatti             = '',
              email               = '$row[AP_SAHKOPOSTI]',
              lasku_email           = '',
              puhelin             = '$row[AP_PUHELIN1]',
              gsm               = '$row[AP_GSM]',
              tyopuhelin             = '',
              fax               = '$row[AP_FAX]',
              toim_ovttunnus           = '',
              toim_nimi             = '$toim_nimi',
              toim_nimitark           = '$toim_nimitark',
              toim_osoite           = '$toim_osoite',
              toim_postino           = '$toim_postino',
              toim_postitp           = '$toim_postitp',
              toim_maa             = '$toim_maa',
              kolm_ovttunnus           = '',
              kolm_nimi             = '',
              kolm_nimitark           = '',
              kolm_osoite           = '',
              kolm_postino           = '',
              kolm_postitp           = '',
              kolm_maa             = '$maa',
              laskutus_nimi           = '$laskutus_nimi',
              laskutus_nimitark         = '$laskutus_nimitark',
              laskutus_osoite         = '$laskutus_osoite',
              laskutus_postino         = '$laskutus_postino',
              laskutus_postitp         = '$laskutus_postitp',
              laskutus_maa           = '$laskutus_maa',
              maksukehotuksen_osoitetiedot   = '',
              konserni             = '',
              asiakasnro             = '$row[AP_ASIAKASNRO]',
              piiri               = '',
              ryhma               = '',
              osasto               = '',
              verkkotunnus           = '$verkkotunnus',
              kieli               = '$kieli',
              chn               = '$chn',
              konserniyhtio           = '',
              fakta               = '$row[AP_LISATIEDOT]',
              sisviesti1             = '',
              myynti_kommentti1         = '',
              kuljetusohje           = '',
              selaus               = '$row[AP_NIMI]',
              alv               = '$alv',
              valkoodi             = '$valuutta',
              maksuehto             = '$maksuehto',
              toimitustapa           = 'Oletus',
              rahtivapaa             = '',
              rahtivapaa_alarajasumma     = '',
              kuljetusvakuutus_tyyppi     = '',
              toimitusehto           = '',
              tilausvahvistus         = '',
              tilausvahvistus_jttoimituksista = '',
              toimitusvahvistus         = '',
              kerayspoikkeama         = '',
              keraysvahvistus_lahetys     = '',
              keraysvahvistus_email       = '',
              kerayserat             = '',
              lahetetyyppi           = '$lahetetyyppi',
              lahetteen_jarjestys       = '',
              lahetteen_jarjestys_suunta     = '',
              laskutyyppi           = '-9',
              laskutusvkopv           = '$laskutusvkp',
              maksusopimus_toimitus       = '',
              laskun_jarjestys         = '',
              laskun_jarjestys_suunta     = '',
              extranet_tilaus_varaa_saldoa   = '',
              vienti               = '$vienti',
              ketjutus             = '',
              luokka               = '',
              jtkielto             = '',
              jtrivit             = '',
              myyjanro             = '',
              erikoisale             = '',
              myyntikielto           = '$myyntikielto',
              myynninseuranta         = '',
              luottoraja             = '$row[AP_LUOTTORAJA]',
              luottovakuutettu         = '',
              kuluprosentti           = '',
              tuntihinta             = '',
              tuntikerroin           = '',
              hintakerroin           = '',
              pientarvikelisa         = '',
              laskunsummapyoristys       = '',
              laskutuslisa           = '',
              panttitili             = '',
              tilino               = '',
              tilino_eu             = '',
              tilino_ei_eu           = '',
              tilino_kaanteinen         = '',
              tilino_marginaali         = '',
              tilino_osto_marginaali       = '',
              tilino_triang           = '',
              kustannuspaikka         = '',
              kohde               = '',
              projekti             = '',
              muutospvm             = now(),
              muuttaja             = '$laatija',
              flag_1               = '',
              flag_2               = '',
              flag_3               = '',
              flag_4               = '',
              maa_maara             = '',
              sisamaan_kuljetus         = '',
              sisamaan_kuljetus_kansallisuus   = '',
              sisamaan_kuljetusmuoto       = '',
              kontti               = '',
              aktiivinen_kuljetus       = '',
              aktiivinen_kuljetus_kansallisuus = '',
              kauppatapahtuman_luonne     = '',
              kuljetusmuoto           = '',
              poistumistoimipaikka_koodi     = '',
              herminator             = '',
              herminator1           = '',
              herminator2           = '',
              herminator3           = '',
              herminator4           = ''";

      $query = "SELECT tunnus
                FROM asiakas
                WHERE yhtio    = '$yhtio'
                AND asiakasnro = '$row[AP_ASIAKASNRO]'";
      $result = pupe_query($query);

      $tunnus = '';

      if (mysql_num_rows($result) == 1) {
        $as_row = mysql_fetch_assoc($result);
        $tunnus = $as_row['tunnus'];
        $sarakkeet_upd = " nimi               = '$row[AP_NIMI]',
                ytunnus             = '$ytunnus',
                ovttunnus             = '$ovttunnus',
                osoite               = '$row[AP_KATUOSOITE]',
                postino             = '$row[AP_POSTINUMERO]',
                postitp             = '$row[AP_POSTITOIMIPAIKKA]',
                maa               = '$maa',
                kansalaisuus           = '$maa',
                email               = '$row[AP_SAHKOPOSTI]',
                puhelin             = '$row[AP_PUHELIN1]',
                gsm               = '$row[AP_GSM]',
                fax               = '$row[AP_FAX]',
                toim_nimi             = '$toim_nimi',
                toim_nimitark           = '$toim_nimitark',
                toim_osoite           = '$toim_osoite',
                toim_postino           = '$toim_postino',
                toim_postitp           = '$toim_postitp',
                toim_maa             = '$toim_maa',
                laskutus_nimi           = '$laskutus_nimi',
                laskutus_nimitark         = '$laskutus_nimitark',
                laskutus_osoite         = '$laskutus_osoite',
                laskutus_postino         = '$laskutus_postino',
                laskutus_postitp         = '$laskutus_postitp',
                laskutus_maa           = '$laskutus_maa',
                verkkotunnus           = '$verkkotunnus',
                kieli               = '$kieli',
                chn               = '$chn',
                fakta               = '$row[AP_LISATIEDOT]',
                selaus               = '$row[AP_NIMI]',
                alv               = '$alv',
                valkoodi             = '$valuutta',
                laskutusvkopv           = '$laskutusvkp',
                vienti               = '$vienti',
                muutospvm             = now(),
                muuttaja             = '$laatija'";
        $query = "UPDATE asiakas SET
                  $sarakkeet_upd
                  WHERE yhtio    = '$yhtio'
                  AND asiakasnro = '$row[AP_ASIAKASNRO]'";
        pupe_query($query);

        if (php_sapi_name() == 'cli') {
          echo "Päivitettiin asiakas $row[AP_ASIAKASNRO] onnistuneesti\n";
        }
        else {
          echo "Päivitettiin asiakas $row[AP_ASIAKASNRO] onnistuneesti<br/>";
        }

      }
      elseif (mysql_num_rows($result) == 0) {

        $query = "INSERT INTO asiakas SET
                  yhtio      = '$yhtio',
                  laatija    = '$laatija',
                  luontiaika = now(),
                  $sarakkeet";
        pupe_query($query);
        $tunnus = mysql_insert_id();

        if (php_sapi_name() == 'cli') {
          echo "Luotiin asiakas $row[AP_ASIAKASNRO] onnistuneesti\n";
        }
        else {
          echo "Luotiin asiakas $row[AP_ASIAKASNRO] onnistuneesti<br/>";
        }
      }
      else {
        if (php_sapi_name() == 'cli') {
          echo "Asiakasnumerolla $row[AP_ASIAKASNRO] löytyy ".mysql_num_rows($result)." osumaa, ei tehdä mitään\n";
        }
        else {
          echo "Asiakasnumerolla $row[AP_ASIAKASNRO] löytyy ".mysql_num_rows($result)." osumaa, ei tehdä mitään<br/>";
        }
      }

      // jos vapaateksti löytyy, tehdään siitä ohjausmerkki asiakkaalle
      if ($row['AP_VAPAA_TEKSTI'] != '' and $tunnus != '') {
        $query = "SELECT tarkenne
                  FROM asiakkaan_avainsanat
                  WHERE yhtio      = '$yhtio'
                  AND laji         = 'OHJAUSMERKKI'
                  AND liitostunnus = '$tunnus'
                  AND avainsana    = 'Oletus'";
        $ohjausmerkki_check_res = pupe_query($query);
        if (mysql_num_rows($ohjausmerkki_check_res) == 0) {
          $insert = "INSERT INTO asiakkaan_avainsanat SET
                     tarkenne     = '{$row['AP_VAPAA_TEKSTI']}',
                     yhtio        = '$yhtio',
                     laji         = 'OHJAUSMERKKI',
                     liitostunnus = '$tunnus',
                     avainsana    = 'Oletus'";
          $insert_result = mysql_query($insert) or pupe_error($insert);
        }
      }

    }

    $i++;
  }

  $drv->free_result($res);

  //laitetaan toim_ovttunnukset nätisti juoksemaan lopuksi, ei updateta ikinä mikäli toim_ovt löytyy!
  // HUOM funtsi viel tarviiko alla rajaa kahes ekas querys laatija/muuttaja tjsp??
  // Jos $asiakasnumerot niin vain yksi asiakas ja silloin ytunnus on kys. asiakkaan ytunnus fiksattuna Pupe-muotoon, joten käytetään sitä tässä performanssin vuoksi
  $ytunnuslisa = "";
  if (!empty($pupesoft_ytunnukset)) {
    $ytunnuslisa = " AND ytunnus IN ('".implode("','", $pupesoft_ytunnukset)."')";
  }

  $query = "SELECT ytunnus, count(*) kpl
            FROM asiakas
            WHERE yhtio = '$yhtio'
            $ytunnuslisa
            GROUP BY ytunnus
            HAVING kpl > 1";
  $query_result = mysql_query($query) or pupe_error($query);

  while ($query_row = mysql_fetch_assoc($query_result)) {

    $query = "SELECT *
              FROM asiakas use index (ytunnus_index)
              WHERE yhtio = '$yhtio'
              AND ytunnus = '$query_row[ytunnus]'
              ORDER BY selaus, nimi";
    $query2_result = mysql_query($query) or pupe_error($query);

    while ($as_row = mysql_fetch_assoc($query2_result)) {
      if ($as_row['toim_ovttunnus'] == '') {
        for ($i = 1; $i < 1000; $i++) {
          $toim_ovt_apu = $as_row['ovttunnus'].str_pad($i, 3, 0, STR_PAD_LEFT);

          //laatija-rajaus pitäis varmaanki poistaa tästä alta, jos taustalla on toimiva yhtiö (ettei tulis samalle toim_ovt:lle jos asiakas on olemassa jo!) JEP!
          $query = "SELECT *
                    FROM asiakas use index (ytunnus_index)
                    WHERE yhtio        = '$yhtio'
                    AND ytunnus        = '$as_row[ytunnus]'
                    AND toim_ovttunnus = '$toim_ovt_apu'";
          $query3_result = mysql_query($query) or pupe_error($query);

          if (mysql_num_rows($query3_result) == 0) {
            $query = "UPDATE asiakas SET
                      toim_ovttunnus = '$toim_ovt_apu'
                      WHERE yhtio    = '$yhtio'
                      AND tunnus     = '$as_row[tunnus]'";
            $result = mysql_query($query) or pupe_error($query);
            break;
          }
        }
      }
    }
  }

  return $pupesoft_asiakasnumerot;
}
