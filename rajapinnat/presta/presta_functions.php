<?php

function presta_hae_asiakkaat() {
  global $kukarow, $yhtiorow, $ajetaanko_kaikki, $datetime_checkpoint;

  if ($ajetaanko_kaikki == "NO") {
    $muutoslisa = " AND (yhteyshenkilo.muutospvm >= '{$datetime_checkpoint}'
      OR asiakas.muutospvm >= '{$datetime_checkpoint}') ";
  }
  else {
    $muutoslisa = "";
  }

  $query = "SELECT yhteyshenkilo.*,
            avainsana.selitetark_5 AS presta_customergroup_id
            FROM yhteyshenkilo
            INNER JOIN asiakas
            ON (asiakas.yhtio = yhteyshenkilo.yhtio
              AND asiakas.tunnus = yhteyshenkilo.liitostunnus )
            LEFT JOIN avainsana
            ON (avainsana.yhtio = asiakas.yhtio
              AND avainsana.selite = asiakas.ryhma
              AND avainsana.laji = 'ASIAKASRYHMA')
            WHERE yhteyshenkilo.yhtio = '{$kukarow['yhtio']}'
            AND yhteyshenkilo.rooli = 'Presta'
            {$muutoslisa}";
  $result = pupe_query($query);

  $asiakkaat = array();
  while ($asiakas = mysql_fetch_assoc($result)) {
    $asiakkaat[] = $asiakas;
  }

  return $asiakkaat;
}

function hae_yhteyshenkilon_asiakas_ulkoisella_asiakasnumerolla($asiakasnumero) {
  global $kukarow, $yhtiorow;

  if (empty($asiakasnumero)) {
    return null;
  }

  $query = "SELECT asiakas.*
            FROM yhteyshenkilo
            INNER JOIN asiakas
            ON (asiakas.yhtio = yhteyshenkilo.yhtio
              AND asiakas.tunnus = yhteyshenkilo.liitostunnus)
            WHERE yhteyshenkilo.yhtio = '{$kukarow['yhtio']}'
            AND yhteyshenkilo.ulkoinen_asiakasnumero = {$asiakasnumero}
            LIMIT 1";
  $result = pupe_query($query);

  if (mysql_num_rows($result) != 1) {
    return null;
  }

  return mysql_fetch_assoc($result);
}

function hae_asiakasryhmat() {
  global $kukarow, $yhtiorow;

  // HUOM. pakko hakea aina kaikki, koska muuten ei osata deletoida poistettuja
  $query = "SELECT avainsana.*,
            avainsana.selitetark_5 AS presta_customergroup_id
            FROM avainsana
            WHERE avainsana.yhtio = '{$kukarow['yhtio']}'
            AND laji = 'ASIAKASRYHMA'";
  $result = pupe_query($query);

  $ryhmat = array();
  while ($ryhma = mysql_fetch_assoc($result)) {
    $ryhmat[] = $ryhma;
  }

  return $ryhmat;
}

function presta_specific_prices() {
  global $kukarow, $yhtiorow;

  $specific_prices = array();
  $tuoterajaus = presta_tuoterajaus();

  // HUOM! yhteyshenkilo.liitostunnus = asiakashinta.asiakas tarkoittaa, ett‰ sama asiakashintarivi
  // voi tulla monta kertaa, koska asiakas has_many yhteyshenkilˆ.
  // N‰in pit‰‰kin, koska yhteyshenkilˆ on Prestassa asiakas.

  // HUOM! pakko hakea kaikki alennukset, koska asiakkaalta poistetaan aina kaikki alennukset.

  // Laitetaan hinnat ja alennukset samaan arrayseen, koska Prestassa niit‰ k‰sitell‰‰n samalla tavalla

  // HUOM! Haetaan from tuote, koska pit‰‰ saada kaikki tuotteet, jotka on menossa prestaan.
  // Vaikka ei olisi hintaa, koska muuten ei saada poistettua hintoja/alennuksia tuotteilta
  // vain ekassa queryss‰ pit‰‰ olla from tuote, koska silloin on kaikki tuotteet jo mukana arrayss‰

  // Asiakashinnat kaikille tuotetteilla
  $query = "SELECT
            tuote.tuoteno,
            asiakashinta.alkupvm,
            asiakashinta.loppupvm,
            asiakashinta.minkpl,
            asiakashinta.hinta,
            asiakashinta.valkoodi,
            avainsana.selitetark_5 AS presta_customergroup_id,
            yhteyshenkilo.ulkoinen_asiakasnumero AS presta_customer_id,
            'asiakashinta' AS tyyppi
            FROM tuote
            LEFT JOIN asiakashinta ON (asiakashinta.yhtio = tuote.yhtio
              AND asiakashinta.tuoteno = tuote.tuoteno
              AND asiakashinta.hinta > 0)
            LEFT JOIN avainsana ON (avainsana.yhtio = asiakashinta.yhtio
              AND avainsana.selite = asiakashinta.asiakas_ryhma
              AND avainsana.laji = 'ASIAKASRYHMA')
            LEFT JOIN yhteyshenkilo ON (yhteyshenkilo.yhtio = asiakashinta.yhtio
              AND yhteyshenkilo.liitostunnus = asiakashinta.asiakas)
            WHERE tuote.yhtio = '{$kukarow['yhtio']}'
            {$tuoterajaus}";
  $result = pupe_query($query);

  while ($asiakashinta = mysql_fetch_assoc($result)) {
    $specific_prices[] = $asiakashinta;
  }

  // Asiakasalennukset kaikille tuotteille
  // Ei tarvitse olla t‰ss‰ left joinia, koska ensimm‰isess‰ queryss‰ on jo.
  // Joten meill‰ on kaikki tuotteet arrayss‰ ja presta hanskaa homman
  $query = "SELECT
            tuote.tuoteno,
            asiakasalennus.alkupvm,
            asiakasalennus.loppupvm,
            asiakasalennus.minkpl,
            asiakasalennus.alennus,
            avainsana.selitetark_5 AS presta_customergroup_id,
            yhteyshenkilo.ulkoinen_asiakasnumero AS presta_customer_id,
            'asiakasalennus' AS tyyppi
            FROM tuote
            INNER JOIN asiakasalennus ON (asiakasalennus.yhtio = tuote.yhtio
              AND asiakasalennus.tuoteno = tuote.tuoteno
              AND asiakasalennus.alennus > 0)
            LEFT JOIN avainsana ON (avainsana.yhtio = asiakasalennus.yhtio
              AND avainsana.selite = asiakasalennus.asiakas_ryhma
              AND avainsana.laji = 'ASIAKASRYHMA')
            LEFT JOIN yhteyshenkilo ON (yhteyshenkilo.yhtio = asiakasalennus.yhtio
              AND yhteyshenkilo.liitostunnus = asiakasalennus.asiakas)
            WHERE tuote.yhtio = '{$kukarow['yhtio']}'
            {$tuoterajaus}";
  $result = pupe_query($query);

  while ($asiakasalennus = mysql_fetch_assoc($result)) {
    $specific_prices[] = $asiakasalennus;
  }

  // Tuotteen hinnastohinnat kaikille tuotteille. lajit:
  // '' Bruttohinta Myyntihinta
  // 'N' N-Nettohinta Myyntihinta
  // 'E' E-Nettohinta Myyntihinta
  //
  // Ei tarvitse olla t‰ss‰ left joinia, koska ensimm‰isess‰ queryss‰ on jo.
  // Joten meill‰ on kaikki tuotteet arrayss‰ ja presta hanskaa homman
  $query = "SELECT distinct hinnasto.tuoteno, hinnasto.valkoodi, hinnasto.maa
            FROM tuote
            INNER JOIN hinnasto ON (hinnasto.yhtio = tuote.yhtio
              AND hinnasto.tuoteno = tuote.tuoteno
              AND hinnasto.laji in ('', 'N', 'E')
              AND hinnasto.hinta > 0)
            WHERE tuote.yhtio = '{$kukarow['yhtio']}'
            {$tuoterajaus}";
  $result = pupe_query($query);

  while ($hintavalrow = mysql_fetch_assoc($result)) {
    // katotaan onko tuotteelle voimassa hinnastohintoja
    $query = "SELECT hinnasto.tuoteno,
              hinnasto.alkupvm,
              hinnasto.loppupvm,
              hinnasto.minkpl,
              hinnasto.hinta,
              hinnasto.valkoodi,
              hinnasto.maa,
              'hinnastohinta' AS tyyppi
              FROM hinnasto
              WHERE hinnasto.yhtio = '$kukarow[yhtio]'
              AND hinnasto.tuoteno = '$hintavalrow[tuoteno]'
              AND hinnasto.valkoodi = '$hintavalrow[valkoodi]'
              AND hinnasto.maa = '$hintavalrow[maa]'
              AND hinnasto.laji in ('', 'N', 'E')
              AND hinnasto.hinta > 0
              AND ((hinnasto.alkupvm <= current_date and if (hinnasto.loppupvm = '0000-00-00', '9999-12-31', hinnasto.loppupvm) >= current_date) or (hinnasto.alkupvm = '0000-00-00' and hinnasto.loppupvm = '0000-00-00'))
              ORDER BY ifnull(to_days(current_date) - to_days(hinnasto.alkupvm), 9999999999999)
              LIMIT 1";
    $hinnastoresult = pupe_query($query);

    while ($hinnasto = mysql_fetch_assoc($hinnastoresult)) {
      $specific_prices[] = $hinnasto;
    }
  }

  // TODO karsea kovakoodaus. pit‰‰ keksi‰ t‰h‰n dynaamisempi vaihtoehto.
  if ($kukarow['yhtio'] == 'audio') {
    // Kaikille tuotteille halutaan tuotteen myyntihinta Prestan Specific Price -listaan
    // Prestan asiakasryhm‰lle 3
    $query = "SELECT
              tuote.tuoteno,
              '0000-00-00' as alkupvm,
              '0000-00-00' as loppupvm,
              '' as minkpl,
              tuote.myyntihinta as hinta,
              '{$yhtiorow['valkoodi']}' as valkoodi,
              '3' AS presta_customergroup_id,
              '' AS presta_customer_id,
              'customhinta' AS tyyppi
              FROM tuote
              WHERE tuote.yhtio = '{$kukarow['yhtio']}'
              AND tuote.myyntihinta > 0
              {$tuoterajaus}";
    $result = pupe_query($query);

    while ($asiakashinta = mysql_fetch_assoc($result)) {
      $specific_prices[] = $asiakashinta;
    }
  }

  // sortataan array tuotej‰rjestykseen, silloin tuote ei ole ikin‰ kauaa ilman alennuksia
  // rajapinta dellaa aina aluksi tuotteen alennukset, sen j‰lkeen lis‰‰ kaikki takaisin
  sort_array_of_arrays($specific_prices, 'tuoteno');

  return $specific_prices;
}

function hae_kategoriat() {
  global $kukarow, $yhtiorow;

  // haetaan kaikki kategoriat ja niiden parent_id
  // HUOM! pakko hakea aina kaikki, ett‰ osataan poistaa poistetut/siirretyt
  $query = "SELECT node.nimi,
            node.koodi,
            node.tunnus AS node_tunnus,
            (SELECT parent.tunnus
             FROM dynaaminen_puu AS parent
             WHERE parent.yhtio = node.yhtio
             AND parent.laji = node.laji
             AND parent.lft < node.lft
             AND parent.rgt > node.rgt
             ORDER by parent.lft DESC
             LIMIT 1) as parent_tunnus
            FROM dynaaminen_puu AS node
            WHERE node.yhtio = '{$kukarow['yhtio']}'
            AND node.laji = 'tuote'
            ORDER BY node.syvyys, node.lft";
  $result = pupe_query($query);

  $kategoriat = array();

  while ($kategoria = mysql_fetch_assoc($result)) {
    $kategoriat[] = $kategoria;
  }

  return $kategoriat;
}

function hae_kaikki_tuotteet() {
  global $kukarow, $yhtiorow, $presta_varastot;

  $tuoterajaus = presta_tuoterajaus();

  if (!is_array($presta_varastot)) {
    die('Presta varastot ei ole array!');
  }

  // Haetaan kaikki siirrett‰v‰t tuotteet, t‰m‰ on poistettujen dellausta varten
  // query pit‰‰ olla sama kun hae_tuotteet (ilman muutosp‰iv‰‰)
  $query = "SELECT tuote.tuoteno, tuote.ei_saldoa
            FROM tuote
            WHERE tuote.yhtio = '{$kukarow['yhtio']}'
            {$tuoterajaus}";
  $res = pupe_query($query);

  $tuotteet = array();

  while ($row = mysql_fetch_array($res)) {
    $tuoteno = $row['tuoteno'];

    if ($row['ei_saldoa'] != '') {
      // saldottomille tuoteteilla null, jotta presta tiet‰‰ olla lis‰‰m‰tt‰ t‰t‰ saldoa
      $myytavissa = null;
    }
    else {
      // Katsotaan onko t‰m‰ is‰tuote
      $query = "SELECT tunnus
                FROM tuoteperhe
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND isatuoteno = '{$tuoteno}'
                AND tyyppi = 'P'
                LIMIT 1";
      $tr_result = pupe_query($query);

      if (mysql_num_rows($tr_result) == 1) {
        // is‰tuote
        $isa_saldot = tuoteperhe_myytavissa($tuoteno, 'KAIKKI', '', $presta_varastot);
        $myytavissa = 0;

        foreach ($isa_saldot as $isa_varasto => $isa_saldo) {
          $myytavissa += $isa_saldo;
        }
      }
      else {
        // normituote
        list(, , $myytavissa) = saldo_myytavissa($tuoteno, '', $presta_varastot);
      }
    }

    // tuoteno avaimena, saldo arvona
    $tuotteet[$tuoteno] = $myytavissa;
  }

  return $tuotteet;
}

function hae_tuotteet() {
  global $kukarow, $yhtiorow, $datetime_checkpoint, $ajetaanko_kaikki;

  $tuoterajaus = presta_tuoterajaus();

  if ($ajetaanko_kaikki == "NO") {
    $tuoterajaus .= " AND tuote.muutospvm >= '{$datetime_checkpoint}' ";
  }

  // Haetaan pupesta tuotteen tiedot
  $query = "SELECT tuote.*
            FROM tuote
            WHERE tuote.yhtio = '{$kukarow['yhtio']}'
            {$tuoterajaus}";
  $res = pupe_query($query);
  $dnstuote = array();

  // Pyˆr‰ytet‰‰n muuttuneet tuotteet l‰pi
  while ($row = mysql_fetch_array($res)) {
    // Jos yhtiˆn hinnat eiv‰t sis‰ll‰ alv:t‰
    if ($yhtiorow["alv_kasittely"] != "") {
      $myyntihinta = hintapyoristys($row["myyntihinta"] * (1 + ($row["alv"] / 100)));
      $myyntihinta_veroton = $row["myyntihinta"];

      $myymalahinta = hintapyoristys($row["myymalahinta"] * (1 + ($row["alv"] / 100)));
      $myymalahinta_veroton = $row["myymalahinta"];
    }
    else {
      $myyntihinta = $row["myyntihinta"];
      $myyntihinta_veroton = hintapyoristys($row["myyntihinta"] / (1 + ($row["alv"] / 100)));

      $myymalahinta = $row["myymalahinta"];
      $myymalahinta_veroton = hintapyoristys($row["myymalahinta"] / (1 + ($row["alv"] / 100)));
    }

    // erikoistapaukset, jos hintoja halutaan siirt‰‰ vasten pupen alv_k‰sittely‰
    $myyntihinta_verot_pois    = hintapyoristys($row["myyntihinta"]  / (1 + ($row["alv"] / 100)), 6);
    $myyntihinta_verot_mukaan  = hintapyoristys($row["myyntihinta"]  * (1 + ($row["alv"] / 100)), 6);
    $myymalahinta_verot_pois   = hintapyoristys($row["myymalahinta"] / (1 + ($row["alv"] / 100)), 6);
    $myymalahinta_verot_mukaan = hintapyoristys($row["myymalahinta"] * (1 + ($row["alv"] / 100)), 6);

    // Haetaan tuotteen kaikki k‰‰nnˆkset
    $query = "SELECT kieli, laji, selite
              FROM tuotteen_avainsanat
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tuoteno = '{$row['tuoteno']}'
              AND laji IN ('nimitys', 'kuvaus', 'lyhytkuvaus')";
    $tr_result = pupe_query($query);
    $tuotteen_kaannokset = array();

    while ($tr_row = mysql_fetch_assoc($tr_result)) {
      $tuotteen_kaannokset[] = array(
        "kieli"  => $tr_row['kieli'],
        "kentta" => $tr_row['laji'],
        "teksti" => $tr_row['selite']
      );
    }

    // Haetaan tuotteen lapsituotteet, jos t‰m‰ on is‰tuote
    $query = "SELECT tuoteno, kerroin, hintakerroin, alekerroin
              FROM tuoteperhe
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND isatuoteno = '{$row['tuoteno']}'
              AND tyyppi = 'P'";
    $tr_result = pupe_query($query);
    $tuotteen_lapsituotteet = array();

    // t‰m‰ on is‰tuote
    if (mysql_num_rows($tr_result) > 0) {
      while ($tr_row = mysql_fetch_assoc($tr_result)) {
        $tuotteen_lapsituotteet[] = array(
          "alekerroin"   => $tr_row['alekerroin'],
          "hintakerroin" => $tr_row['hintakerroin'],
          "kerroin"      => $tr_row['kerroin'],
          "tuoteno"      => $tr_row['tuoteno'],
        );
      }
    }

    // Jos tuote kuuluu tuotepuuhun niin haetaan kategoria_idt
    $query = "SELECT puun_tunnus
              FROM puun_alkio
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND laji = 'tuote'
              AND liitos = '{$row['tuoteno']}'";
    $result_tp = pupe_query($query);
    $tuotepuun_tunnukset = array();

    while ($tuotepuurow = mysql_fetch_assoc($result_tp)) {
      $tuotepuun_tunnukset[] = $tuotepuurow['puun_tunnus'];
    }

    $dnstuote[] = array(
      'alv'                       => $row["alv"],
      'ean'                       => $row["eankoodi"],
      'ei_saldoa'                 => $row["ei_saldoa"],
      'kuluprosentti'             => $row['kuluprosentti'],
      'kuvaus'                    => $row["kuvaus"],
      'lyhytkuvaus'               => $row["lyhytkuvaus"],
      'mainosteksti'              => $row['mainosteksti'],
      'mallitarkenne'             => $row['mallitarkenne'],
      'myynti_era'                => $row['myynti_era'],
      'nakyvyys'                  => $row["nakyvyys"],
      'nimi'                      => $row["nimitys"],
      'osasto'                    => $row["osasto"],
      'status'                    => $row["status"],
      'try'                       => $row["try"],
      'tunnus'                    => $row['tunnus'],
      'tuotekorkeus'              => $row['tuotekorkeus'],
      'tuoteleveys'               => $row['tuoteleveys'],
      'tuotemassa'                => $row['tuotemassa'],
      'tuotemerkki'               => $row["tuotemerkki"],
      'tuoteno'                   => $row["tuoteno"],
      'tuotesyvyys'               => $row['tuotesyvyys'],
      'valmistuslinja'            => $row['valmistuslinja'],
      'yksikko'                   => $row["yksikko"],
      'myymalahinta'              => $myymalahinta,
      'myymalahinta_verot_mukaan' => $myymalahinta_verot_mukaan,
      'myymalahinta_verot_pois'   => $myymalahinta_verot_pois,
      'myymalahinta_veroton'      => $myymalahinta_veroton,
      'myyntihinta'               => $myyntihinta,
      'myyntihinta_verot_mukaan'  => $myyntihinta_verot_mukaan,
      'myyntihinta_verot_pois'    => $myyntihinta_verot_pois,
      'myyntihinta_veroton'       => $myyntihinta_veroton,
      'tuotepuun_tunnukset'       => $tuotepuun_tunnukset,
      'tuotteen_kaannokset'       => $tuotteen_kaannokset,
      'tuotteen_lapsien_maara'    => count($tuotteen_lapsituotteet),
      'tuotteen_lapsituotteet'    => $tuotteen_lapsituotteet,
    );
  }

  // pit‰‰ sortata array siten, ett‰ is‰tuotteet ovat lopussa.
  // muuten lapsia ei v‰ltt‰m‰tt‰ ole perustettu, kun yritet‰‰n rakentaa perhett‰
  sort_array_of_arrays($dnstuote, 'tuotteen_lapsien_maara');

  return $dnstuote;
}

function presta_tuoterajaus() {
  $presta_tuotekasittely = $GLOBALS["presta_tuotekasittely"];

  $tuoterajaus = " AND tuote.tuoteno != ''
                   AND tuote.tuotetyyppi NOT in ('A','B') ";

  if ($presta_tuotekasittely == 2) {
    $tuoterajaus .= "";
  }
  else {
    $tuoterajaus .= " AND tuote.status != 'P'
                      AND tuote.nakyvyys != '' ";
  }

  return $tuoterajaus;
}
