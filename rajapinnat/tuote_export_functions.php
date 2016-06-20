<?php

function tuote_export_tee_querylisa_resultista($tyyppi, array $tulokset) {
  $poimitut = '';

  // $tulokset = array(
  //   [0] => array ("muuttuneet_tuotenumerot" => "'3','4'"),
  //   [1] => array ("muuttuneet_tuotenumerot" => "'12','24'"´),
  //   [2] => array ("muuttuneet_tuotenumerot" => "'5'" )
  // );
  // -> 3','4','12','24','5'

  if (isset($tulokset[0][$tyyppi]) and !empty($tulokset[0][$tyyppi])) {
    $poimitut = $tulokset[0][$tyyppi];
  }
  if (isset($tulokset[1][$tyyppi]) and !empty($tulokset[1][$tyyppi])) {
    if (empty($poimitut)) {
      $poimitut .= $tulokset[1][$tyyppi];
    }
    else {
      $poimitut .= ",{$tulokset[1][$tyyppi]}";
    }

  }
  if (isset($tulokset[2][$tyyppi]) and !empty($tulokset[2][$tyyppi])) {
    if (empty($poimitut)) {
      $poimitut = $tulokset[2][$tyyppi];
    }
    else {
      $poimitut .= ",{$tulokset[2][$tyyppi]}";
    }
  }

  if (!empty($poimitut)) {
    if ($tyyppi == 'muuttuneet_tuotenot') {
      $result = " AND tuote.tuoteno IN ($poimitut) ";
    }
    elseif ($tyyppi == 'muuttuneet_ryhmat') {
      $result = " AND tuote.aleryhma IN ($poimitut) ";
    }
    else {
      $result = '';
    }
  }

  return $result;
}

function tuote_export_hae_hintamuutoksia_sisaltavat_tuotenumerot($datetime_checkpoint) {
  global $kukarow, $yhtiorow;

  $kaikki_arvot = array();

  $query1 = "SELECT group_concat('\'',tuoteno,'\'') muuttuneet_tuotenot
             FROM asiakashinta
             WHERE yhtio   = '{$kukarow['yhtio']}'
             AND muutospvm >= '{$datetime_checkpoint}'
             AND tuoteno !=''";
  $result1 = pupe_query($query1);
  $row1 = mysql_fetch_assoc($result1);
  $kaikki_arvot[] = $row1;

  $query2 = "SELECT group_concat('\'',tuoteno,'\'') muuttuneet_tuotenot
             FROM asiakasalennus
             WHERE yhtio   = '{$kukarow['yhtio']}'
             AND muutospvm >= '{$datetime_checkpoint}'
             AND tuoteno !=''";
  $result2 = pupe_query($query2);
  $row2 = mysql_fetch_assoc($result2);
  $kaikki_arvot[] = $row2;

  $result = tuote_export_tee_querylisa_resultista('muuttuneet_tuotenot', $kaikki_arvot);

  return $result;
}

function tuote_export_hae_hintamuutoksia_sisaltavat_tuoteryhmat($datetime_checkpoint) {
  global $kukarow, $yhtiorow;

  $kaikki_arvot = array();

  $query3 = "SELECT group_concat('\'',ryhma,'\'') muuttuneet_ryhmat
             FROM asiakashinta
             WHERE yhtio   = '{$kukarow['yhtio']}'
             AND muutospvm >= '{$datetime_checkpoint}'
             AND ryhma !=''";
  $result3 = pupe_query($query3);
  $row3 = mysql_fetch_assoc($result3);
  $kaikki_arvot[] = $row3;

  $query4 = "SELECT group_concat('\'',ryhma,'\'') muuttuneet_ryhmat
             FROM asiakasalennus
             WHERE yhtio   = '{$kukarow['yhtio']}'
             AND muutospvm >= '{$datetime_checkpoint}'
             AND ryhma !=''";
  $result4 = pupe_query($query4);
  $row4 = mysql_fetch_assoc($result4);
  $kaikki_arvot[] = $row4;

  $query5 = "SELECT group_concat('\'',ryhma,'\'') muuttuneet_ryhmat
             FROM perusalennus
             WHERE yhtio   = '{$kukarow['yhtio']}'
             AND muutospvm >= '{$datetime_checkpoint}'
             AND ryhma !=''";
  $result5 = pupe_query($query5);
  $row5 = mysql_fetch_assoc($result5);
  $kaikki_arvot[] = $row5;

  $result = tuote_export_tee_querylisa_resultista('muuttuneet_ryhmat', $kaikki_arvot);

  return $result;
}

function tuote_export_hae_tuotetiedot($params) {
  global $kukarow, $yhtiorow;

  $ajetaanko_kaikki                     = $params['ajetaanko_kaikki'];
  $datetime_checkpoint                  = $params['datetime_checkpoint'];
  $magento_asiakaskohtaiset_tuotehinnat = $params['magento_asiakaskohtaiset_tuotehinnat'];
  $tuotteiden_asiakashinnat_magentoon   = $params['tuotteiden_asiakashinnat_magentoon'];
  $verkkokauppatyyppi                   = $params['verkkokauppatyyppi'];

  $dnstuote = array();

  if ($ajetaanko_kaikki === false) {
    $muuttuneet_tuotenumerot = $muuttuneet_tuoteryhmat = '';

    if ($magento_asiakaskohtaiset_tuotehinnat !== false) {
      $muuttuneet_tuotenumerot = tuote_export_hae_hintamuutoksia_sisaltavat_tuotenumerot($datetime_checkpoint);
      $muuttuneet_tuoteryhmat = tuote_export_hae_hintamuutoksia_sisaltavat_tuoteryhmat($datetime_checkpoint);
    }

    $muutoslisa = "AND (tuote.muutospvm >= '{$datetime_checkpoint}'
              OR ta_nimitys_se.muutospvm >= '{$datetime_checkpoint}'
              OR ta_nimitys_en.muutospvm >= '{$datetime_checkpoint}'
              {$muuttuneet_tuotenumerot}
              {$muuttuneet_tuoteryhmat}
              )";
  }
  else {
    $muutoslisa = "";
  }

  // Haetaan pupesta tuotteen tiedot
  $query = "SELECT
            tuote.*,
            tuote.mallitarkenne campaign_code,
            tuote.malli target,
            tuote.leimahduspiste onsale,
            ta_nimitys_se.selite nimi_swe,
            ta_nimitys_en.selite nimi_eng,
            try_fi.selitetark try_nimi
            FROM tuote
            LEFT JOIN avainsana as try_fi ON (try_fi.yhtio = tuote.yhtio
              and try_fi.selite        = tuote.try
              and try_fi.laji          = 'try'
              and try_fi.kieli         = 'fi')
            LEFT JOIN tuotteen_avainsanat as ta_nimitys_se on tuote.yhtio = ta_nimitys_se.yhtio
              and tuote.tuoteno        = ta_nimitys_se.tuoteno
              and ta_nimitys_se.laji   = 'nimitys'
              and ta_nimitys_se.kieli  = 'se'
            LEFT JOIN tuotteen_avainsanat as ta_nimitys_en on tuote.yhtio = ta_nimitys_en.yhtio
              and tuote.tuoteno        = ta_nimitys_en.tuoteno
              and ta_nimitys_en.laji   = 'nimitys'
              and ta_nimitys_en.kieli  = 'en'
            WHERE tuote.yhtio          = '{$kukarow["yhtio"]}'
              AND tuote.status        != 'P'
              AND tuote.tuotetyyppi    NOT in ('A','B')
              AND tuote.tuoteno       != ''
              AND tuote.nakyvyys      != ''
              $muutoslisa
            ORDER BY tuote.tuoteno";
  $res = pupe_query($query);

  // Pyöräytetään muuttuneet tuotteet läpi
  while ($row = mysql_fetch_array($res)) {
    // Jos yhtiön hinnat eivät sisällä alv:tä
    if ($yhtiorow["alv_kasittely"] != "") {
      // Anviassa myyntihintaan verot päälle
      if ($verkkokauppatyyppi == 'anvia') {
        $myyntihinta = hintapyoristys($row["myyntihinta"] * (1+($row["alv"]/100)));
      }
      else {
        $myyntihinta = $row["myyntihinta"];
      }
      $myyntihinta_veroton = $row["myyntihinta"];
    }
    else {
      $myyntihinta = $row["myyntihinta"];
      $myyntihinta_veroton = hintapyoristys($row["myyntihinta"] / (1+($row["alv"]/100)));
    }

    $myymalahinta = $row["myymalahinta"];
    $myymalahinta_veroton = hintapyoristys($row["myymalahinta"] / (1+($row["alv"]/100)));

    $asiakashinnat = array();

    if ($tuotteiden_asiakashinnat_magentoon !== false) {
      $query = "SELECT
                avainsana.selitetark AS asiakasryhma,
                asiakashinta.tuoteno,
                asiakashinta.hinta
                FROM asiakas
                JOIN avainsana ON (avainsana.yhtio = asiakas.yhtio
                  AND avainsana.selite           = asiakas.ryhma AND avainsana.laji = 'asiakasryhma')
                JOIN asiakashinta ON (asiakashinta.yhtio = asiakas.yhtio
                  AND asiakashinta.asiakas_ryhma = asiakas.ryhma)
                WHERE asiakas.yhtio              = '{$kukarow['yhtio']}'
                  AND asiakashinta.tuoteno ='{$row['tuoteno']}'
                GROUP BY 1,2,3";
      $asiakashintares = pupe_query($query);

      while ($asiakashintarow = mysql_fetch_assoc($asiakashintares)) {
        $asiakashinnat[] = array(
          'asiakasryhma' => $asiakashintarow['asiakasryhma'],
          'tuoteno'      => $asiakashintarow['tuoteno'],
          'hinta'        => $asiakashintarow['hinta'],
        );
      }
    }

    // Haetaan kaikki tuotteen atribuutit
    $parametritquery = "SELECT
                        tuotteen_avainsanat.selite,
                        avainsana.selitetark,
                        avainsana.selite option_name
                        FROM tuotteen_avainsanat USE INDEX (yhtio_tuoteno)
                        JOIN avainsana USE INDEX (yhtio_laji_selite) ON (avainsana.yhtio = tuotteen_avainsanat.yhtio
                          AND avainsana.laji             = 'PARAMETRI'
                          AND avainsana.selite           = SUBSTRING(tuotteen_avainsanat.laji, 11))
                        WHERE tuotteen_avainsanat.yhtio='{$kukarow['yhtio']}'
                        AND tuotteen_avainsanat.laji    != 'parametri_variaatio'
                        AND tuotteen_avainsanat.laji    != 'parametri_variaatio_jako'
                        AND tuotteen_avainsanat.laji     like 'parametri_%'
                        AND tuotteen_avainsanat.tuoteno  = '{$row['tuoteno']}'
                        AND tuotteen_avainsanat.kieli    = 'fi'
                        ORDER by tuotteen_avainsanat.jarjestys, tuotteen_avainsanat.laji";
    $parametritres = pupe_query($parametritquery);
    $tuotteen_parametrit = array();

    // Jos tuote kuuluu tuotepuuhun niin etsitään kategoria_idt myös kaikille tuotepuun kategorioille
    $query = "SELECT t0.nimi node, t0.lft,
              tuote.tuoteno,
              GROUP_CONCAT(t5.nimi SEPARATOR '\n') children,
              (SELECT GROUP_CONCAT(t6.nimi SEPARATOR '\n')
               FROM dynaaminen_puu t6
               WHERE t6.lft<t0.lft AND t6.rgt>t0.rgt
               AND t6.laji      = 'tuote'
               ORDER BY t6.lft) ancestors
              FROM dynaaminen_puu t0
              LEFT JOIN
              (SELECT *
               FROM (SELECT t1.lft node,
               MAX(t2.lft) nodeparent
               FROM dynaaminen_puu t1
               INNER JOIN
               dynaaminen_puu t2 ON t1.lft>t2.lft AND t1.rgt<t2.rgt
               GROUP BY t1.lft) t3
               LEFT JOIN
               dynaaminen_puu t4 ON t3.node=t4.lft) t5 ON t0.lft=t5.nodeparent
              LEFT JOIN puun_alkio ON puun_alkio.puun_tunnus = t0.tunnus AND puun_alkio.yhtio = t0.yhtio
               JOIN tuote ON tuote.tuoteno = puun_alkio.liitos AND tuote.yhtio = puun_alkio.yhtio
              WHERE t0.yhtio ='{$kukarow['yhtio']}'
              AND t0.laji       = 'tuote'
              AND tuote.tuoteno = '{$row['tuoteno']}'
              GROUP BY t0.nimi
              ORDER BY t0.lft";
    $result_tp = pupe_query($query);

    $tuotepuun_nodet = array ();

    while ($tuotepuurow = mysql_fetch_assoc($result_tp)) {
      $breadcrumbs = empty($tuotepuurow['ancestors']) ? array () : explode("\n", $tuotepuurow['ancestors']);
      $breadcrumbs[] = $tuotepuurow['node'];
      if (count($breadcrumbs) > 1) array_shift($breadcrumbs);
      $tuotepuun_nodet[] = $breadcrumbs;
    }

    while ($parametrirow = mysql_fetch_assoc($parametritres)) {
      $tuotteen_parametrit[] = array(
        "nimi"        => $parametrirow["selitetark"],
        "option_name" => $parametrirow["option_name"],
        "arvo"        => $parametrirow["selite"]
      );
    }

    // Katsotaan onko tuotteelle voimassaolevaa hinnastohintaa
    $query = "SELECT *
              FROM hinnasto
              WHERE yhtio   = '{$kukarow['yhtio']}'
                AND tuoteno = '{$row['tuoteno']}'
                AND maa     = '{$yhtiorow['maa']}'
                AND laji    = ''
                AND ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
              ORDER BY ifnull(to_days(current_date)-to_days(alkupvm),9999999999999)
              LIMIT 1";
    $hinnastoq = pupe_query($query);
    $hinnastoresult = mysql_fetch_assoc($hinnastoq);

    // Nollataan tämä jos query lyö tyhjää, muuten vanhentunut tarjoushinta ei ylikirjoitu magentossa
    if (!isset($hinnastoresult['hinta'])) $hinnastoresult['hinta'] = '';

    $dnstuote[] = array(
      'alv'                  => $row["alv"],
      'asiakashinnat'        => $asiakashinnat,
      'campaign_code'        => $row["campaign_code"],
      'ean'                  => $row["eankoodi"],
      'hinnastohinta'        => $hinnastoresult['hinta'],
      'korkeus'              => $row['tuotekorkeus'],
      'kuluprosentti'        => $row['kuluprosentti'],
      'kuvaus'               => $row["kuvaus"],
      'leveys'               => $row['tuoteleveys'],
      'lyhytkuvaus'          => $row["lyhytkuvaus"],
      'mainosteksti'         => $row['mainosteksti'],
      'malli'                => $row["malli"],
      'muuta'                => $row["muuta"],
      'myymalahinta'         => $myymalahinta,
      'myymalahinta_veroton' => $myymalahinta_veroton,
      'myynti_era'           => $row['myynti_era'],
      'myyntihinta'          => $myyntihinta,
      'myyntihinta_veroton'  => $myyntihinta_veroton,
      'nakyvyys'             => strtolower($row["nakyvyys"]),
      'nimi'                 => $row["nimitys"],
      'nimi_eng'             => $row["nimi_eng"],
      'nimi_swe'             => $row["nimi_swe"],
      'nimitys'              => $row["nimitys"],
      'onsale'               => $row["onsale"],
      'osasto'               => $row["osasto"],
      'paino'                => $row['tuotemassa'],
      'syvyys'               => $row['tuotesyvyys'],
      'target'               => $row["target"],
      'try'                  => $row["try"],
      'try_nimi'             => $row["try_nimi"],
      'tunnus'               => $row['tunnus'],
      'tuotemerkki'          => $row["tuotemerkki"],
      'tuoteno'              => $row["tuoteno"],
      'tuotepuun_nodet'      => $tuotepuun_nodet,
      'tuotteen_parametrit'  => $tuotteen_parametrit,
      'yksikko'              => $row["yksikko"],
    );
  }

  return $dnstuote;
}

function tuote_export_hae_poistettavat_tuotteet() {
  global $kukarow, $yhtiorow;

  $kaikki_tuotteet = array();
  $individual_tuotteet = array();

  // Haetaan pupesta kaikki tuotteet (ja configurable-tuotteet), jotka pitää olla Magentossa
  $query = "SELECT DISTINCT tuote.tuoteno, tuotteen_avainsanat.selite configurable_tuoteno
            FROM tuote
            LEFT JOIN tuotteen_avainsanat ON (tuote.yhtio = tuotteen_avainsanat.yhtio
            AND tuote.tuoteno             = tuotteen_avainsanat.tuoteno
            AND tuotteen_avainsanat.laji  = 'parametri_variaatio'
            AND trim(tuotteen_avainsanat.selite) != '')
            WHERE tuote.yhtio             = '{$kukarow["yhtio"]}'
            AND tuote.status             != 'P'
            AND tuote.tuotetyyppi         NOT in ('A','B')
            AND tuote.tuoteno            != ''
            AND tuote.nakyvyys           != ''";
  $res = pupe_query($query);

  // Kaikki tuotenumerot arrayseen
  while ($row = mysql_fetch_array($res)) {
    $kaikki_tuotteet[] = $row['tuoteno'];

    if ($row['configurable_tuoteno'] == "") $individual_tuotteet[$row['tuoteno']] = $row['tuoteno'];
    if ($row['configurable_tuoteno'] != "") $kaikki_tuotteet[] = $row['configurable_tuoteno'];
  }

  $kaikki_tuotteet = array_unique($kaikki_tuotteet);

  $response = array(
    "kaikki"     => $kaikki_tuotteet,
    "individual" => $individual_tuotteet,
  );

  return $response;
}

function tuote_export_hae_saldot($params) {
  global $kukarow, $yhtiorow;

  $ajetaanko_kaikki           = $params['ajetaanko_kaikki'];
  $datetime_checkpoint        = $params['datetime_checkpoint'];
  $verkkokauppa_saldo_varasto = $params['verkkokauppa_saldo_varasto'];

  if (!is_array($verkkokauppa_saldo_varasto)) {
    die("verkkokauppa_saldo_varasto pitää olla array!");
  }

  $dnstock = array();

  if ($ajetaanko_kaikki === false) {
    $muutoslisa1 = "AND tapahtuma.laadittu  >= '{$datetime_checkpoint}'";
    $muutoslisa2 = "AND tilausrivi.laadittu >= '{$datetime_checkpoint}'";
    $muutoslisa3 = "AND tuote.muutospvm     >= '{$datetime_checkpoint}'";
  }
  else {
    $muutoslisa1 = "";
    $muutoslisa2 = "";
    $muutoslisa3 = "";
  }

  // Haetaan saldot tuotteille, joille on tehty tunnin sisällä tilausrivi tai tapahtuma
  $query =  "(SELECT tapahtuma.tuoteno,
              tuote.eankoodi
              FROM tapahtuma
              JOIN tuote ON (tuote.yhtio = tapahtuma.yhtio
                AND tuote.tuoteno      = tapahtuma.tuoteno
                AND tuote.status      != 'P'
                AND tuote.tuotetyyppi  NOT in ('A','B')
                AND tuote.tuoteno     != ''
                AND tuote.nakyvyys    != '')
              WHERE tapahtuma.yhtio    = '{$kukarow["yhtio"]}'
              $muutoslisa1)

              UNION

              (SELECT tilausrivi.tuoteno,
              tuote.eankoodi
              FROM tilausrivi
              JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio
                AND tuote.tuoteno      = tilausrivi.tuoteno
                AND tuote.status      != 'P'
                AND tuote.tuotetyyppi  NOT in ('A','B')
                AND tuote.tuoteno     != ''
                AND tuote.nakyvyys    != '')
              WHERE tilausrivi.yhtio   = '{$kukarow["yhtio"]}'
              $muutoslisa2)

              UNION

              (SELECT tuote.tuoteno,
              tuote.eankoodi
              FROM tuote
              WHERE tuote.yhtio        = '{$kukarow["yhtio"]}'
              AND tuote.status        != 'P'
              AND tuote.tuotetyyppi    NOT in ('A','B')
              AND tuote.tuoteno       != ''
              AND tuote.nakyvyys      != ''
              $muutoslisa3)

              ORDER BY 1";
  $result = pupe_query($query);

  while ($row = mysql_fetch_assoc($result)) {
    list(, , $myytavissa) = saldo_myytavissa($row["tuoteno"], '', $verkkokauppa_saldo_varasto);

    $dnstock[] = array(
      'tuoteno'     => $row["tuoteno"],
      'ean'         => $row["eankoodi"],
      'myytavissa'  => $myytavissa,
    );
  }

  return $dnstock;
}

function tuote_export_hae_tuoteryhmat($params) {
  global $kukarow, $yhtiorow;

  $ajetaanko_kaikki           = $params['ajetaanko_kaikki'];
  $datetime_checkpoint        = $params['datetime_checkpoint'];

  $dnsryhma = array();
  $dnstuoteryhma = array();

  if ($ajetaanko_kaikki === false) {
    $muutoslisa = "AND (try_fi.muutospvm    >= '{$datetime_checkpoint}'
                     OR try_se.muutospvm    >= '{$datetime_checkpoint}'
                     OR try_en.muutospvm    >= '{$datetime_checkpoint}'
                     OR osasto_fi.muutospvm >= '{$datetime_checkpoint}'
                     OR osasto_se.muutospvm >= '{$datetime_checkpoint}'
                     OR osasto_en.muutospvm >= '{$datetime_checkpoint}')";
  }
  else {
    $muutoslisa = "";
  }

  // Haetaan kaikki TRY ja OSASTO:t, niiden muutokset.
  $query = "SELECT DISTINCT  tuote.osasto,
            tuote.try,
            try_fi.selitetark try_fi_nimi,
            try_se.selitetark try_se_nimi,
            try_en.selitetark try_en_nimi,
            osasto_fi.selitetark osasto_fi_nimi,
            osasto_se.selitetark osasto_se_nimi,
            osasto_en.selitetark osasto_en_nimi
            FROM tuote
            LEFT JOIN avainsana as try_fi ON (try_fi.yhtio = tuote.yhtio
              and try_fi.selite     = tuote.try
              and try_fi.laji       = 'try'
              and try_fi.kieli      = 'fi')
            LEFT JOIN avainsana as try_se ON (try_se.yhtio = tuote.yhtio
              and try_se.selite     = tuote.try
              and try_se.laji       = 'try'
              and try_se.kieli      = 'se')
            LEFT JOIN avainsana as try_en ON (try_en.yhtio = tuote.yhtio
              and try_en.selite     = tuote.try
              and try_en.laji       = 'try'
              and try_en.kieli      = 'en')
            LEFT JOIN avainsana as osasto_fi ON (osasto_fi.yhtio = tuote.yhtio
              and osasto_fi.selite  = tuote.osasto
              and osasto_fi.laji    = 'osasto'
              and osasto_fi.kieli   = 'fi')
            LEFT JOIN avainsana as osasto_se ON (osasto_se.yhtio = tuote.yhtio
              and osasto_se.selite  = tuote.osasto
              and osasto_se.laji    = 'osasto'
              and osasto_se.kieli   = 'se')
            LEFT JOIN avainsana as osasto_en ON (osasto_en.yhtio = tuote.yhtio
              and osasto_en.selite  = tuote.osasto
              and osasto_en.laji    = 'osasto'
              and osasto_en.kieli   = 'en')
            WHERE tuote.yhtio       = '{$kukarow["yhtio"]}'
            AND tuote.status       != 'P'
            AND tuote.tuotetyyppi   NOT in ('A','B')
            AND tuote.tuoteno      != ''
            AND tuote.nakyvyys     != ''
            $muutoslisa
            ORDER BY 1, 2";
  $try_result = pupe_query($query);

  while ($row = mysql_fetch_assoc($try_result)) {
    // Osasto/tuoteryhmä array
    $dnsryhma[$row["osasto"]][$row["try"]] = array(  'osasto'  => $row["osasto"],
      'try'       => $row["try"],
      'osasto_fi' => $row["osasto_fi_nimi"],
      'try_fi'    => $row["try_fi_nimi"],
      'osasto_se' => $row["osasto_se_nimi"],
      'try_se'    => $row["try_se_nimi"],
      'osasto_en' => $row["osasto_en_nimi"],
      'try_en'    => $row["try_en_nimi"],
    );

    // Kerätään myös pelkät tuotenumerot Magentoa varten
    $dnstuoteryhma[$row["try"]] = array(  'try'    => $row["try"],
      'try_fi'  => $row["try_fi_nimi"],
      'try_se'  => $row["try_se_nimi"],
      'try_en'  => $row["try_en_nimi"],
    );
  }

  $response = array(
    "dnsryhma"      => $dnsryhma,
    "dnstuoteryhma" => $dnstuoteryhma,
  );

  return $response;
}

function tuote_export_hae_asiakkaat($params) {
  global $kukarow, $yhtiorow;

  $ajetaanko_kaikki     = $params['ajetaanko_kaikki'];
  $datetime_checkpoint  = $params['datetime_checkpoint'];
  $magento_website_id   = $params['magento_website_id'];
  $verkkokauppatyyppi   = $params['verkkokauppatyyppi'];

  $dnsasiakas = array();

  if ($ajetaanko_kaikki === false) {
    $muutoslisa = "AND asiakas.muutospvm >= '{$datetime_checkpoint}'";
  }
  else {
    $muutoslisa = "";
  }

  $asiakasselectlisa = $asiakasjoinilisa = $asiakaswherelisa = "";

  if ($verkkokauppatyyppi == "magento") {
    $asiakasselectlisa = " avainsana.selitetark as asiakasryhma,
                           yhteyshenkilo.ulkoinen_asiakasnumero magento_tunnus,
                           yhteyshenkilo.tunnus yhenk_tunnus,
                           yhteyshenkilo.nimi yhenk_nimi,
                           yhteyshenkilo.email yhenk_email,
                           yhteyshenkilo.puh yhenk_puh,";

    $asiakasjoinilisa = " JOIN yhteyshenkilo ON (yhteyshenkilo.yhtio = asiakas.yhtio AND yhteyshenkilo.liitostunnus = asiakas.tunnus AND yhteyshenkilo.rooli = 'magento')
                          LEFT JOIN avainsana ON (avainsana.yhtio = asiakas.yhtio AND avainsana.selite = asiakas.ryhma AND avainsana.laji = 'asiakasryhma')";

    $asiakaswherelisa = " AND yhteyshenkilo.rooli  = 'magento'
                          AND yhteyshenkilo.email != ''";

    if (!empty($muutoslisa)) {
      $muutoslisa .= " OR yhteyshenkilo.muutospvm >= '{$datetime_checkpoint}'";
    }
  }

  // Haetaan kaikki asiakkaat
  // Asiakassiirtoa varten poimitaan myös lisäkenttiä yhteyshenkilo-tauluista
  $query = "SELECT
            asiakas.*,
            $asiakasselectlisa
            asiakas.yhtio ayhtio
            FROM asiakas
            $asiakasjoinilisa
            WHERE asiakas.yhtio  = '{$kukarow["yhtio"]}'
            AND asiakas.laji    != 'P'
            $asiakaswherelisa
            $muutoslisa";
  $res = pupe_query($query);

  // pyöräytetään asiakkaat läpi
  while ($row = mysql_fetch_array($res)) {
    // Osoite laskutusosoitteeksi jos tyhjä
    if (empty($row['laskutus_nimi'])) {
      $row["laskutus_nimi"]    = $row['nimi'];
      $row["laskutus_osoite"]  = $row['osoite'];
      $row["laskutus_postino"] = $row['postino'];
      $row["laskutus_postitp"] = $row['postitp'];
    }
    // Osoite toimitusosoitteeksi jos tyhjä
    if (empty($row['toim_nimi'])) {
      $row['toim_nimi']    = $row['nimi'];
      $row["toim_osoite"]  = $row['osoite'];
      $row["toim_postino"] = $row['postino'];
      $row["toim_postitp"] = $row['postitp'];
    }
    // Yhteyshenkilön nimestä otetaan etunimi ja sukunimi
    if (!empty($row["yhenk_nimi"])) {
      // Viimeinen osa nimestä on sukunimi
      $_last = explode(' ', $row['yhenk_nimi']);
      $yhenk_sukunimi = end($_last);

      // Ensimmäiset osat etunimiä
      $yhenk_etunimi = explode(' ', $row['yhenk_nimi']);

      array_pop($yhenk_etunimi);
      $yhenk_etunimi = implode(' ', $yhenk_etunimi);
    }

    $dnsasiakas[] = array(
      'nimi'               => $row["nimi"],
      'osoite'             => $row["osoite"],
      'postino'            => $row["postino"],
      'postitp'            => $row["postitp"],
      'email'              => $row["email"],
      'aleryhma'           => $row["ryhma"],
      'asiakasnro'         => $row["asiakasnro"],
      'ytunnus'            => $row["ytunnus"],
      'tunnus'             => $row["tunnus"],
      'maa'                => $row["maa"],
      'yhtio'              => $row["ayhtio"],
      'magento_website_id' => $magento_website_id,
      'toimitus_nimi'      => $row["toim_nimi"],
      'toimitus_osoite'    => $row["toim_osoite"],
      'toimitus_postino'   => $row["toim_postino"],
      'toimitus_postitp'   => $row["toim_postitp"],
      'laskutus_nimi'      => $row["laskutus_nimi"],
      'laskutus_osoite'    => $row["laskutus_osoite"],
      'laskutus_postino'   => $row["laskutus_postino"],
      'laskutus_postitp'   => $row["laskutus_postitp"],
      'yhenk_nimi'         => $row["yhenk_nimi"],
      'yhenk_etunimi'      => $yhenk_etunimi,
      'yhenk_sukunimi'     => $yhenk_sukunimi,
      'yhenk_email'        => $row["yhenk_email"],
      'yhenk_puh'          => $row["yhenk_puh"],
      'yhenk_tunnus'       => $row["yhenk_tunnus"],
      'magento_tunnus'     => $row["magento_tunnus"],
      'asiakasryhma'       => $row['asiakasryhma']
    );
  }

  return $dnsasiakas;
}

function tuote_export_hae_hinnastot($params) {
  global $kukarow, $yhtiorow;

  $ajetaanko_kaikki    = $params['ajetaanko_kaikki'];
  $datetime_checkpoint = $params['datetime_checkpoint'];
  $verkkokauppatyyppi  = $params['verkkokauppatyyppi'];

  $dnshinnasto = array();

  if ($ajetaanko_kaikki === false) {
    $muutoslisa = "AND hinnasto.muutospvm >= '{$datetime_checkpoint}'";
  }
  else {
    $muutoslisa = "";
  }

  // Haetaan kaikki hinnastot ja alv
  $query = "SELECT hinnasto.tuoteno,
            hinnasto.selite,
            hinnasto.alkupvm,
            hinnasto.loppupvm,
            hinnasto.hinta,
            tuote.alv
            FROM hinnasto
            JOIN tuote on (tuote.yhtio = hinnasto.yhtio
              AND tuote.tuoteno      = hinnasto.tuoteno
              AND tuote.status      != 'P'
              AND tuote.tuotetyyppi  NOT in ('A','B')
              AND tuote.tuoteno     != ''
              AND tuote.nakyvyys    != '')
            WHERE hinnasto.yhtio     = '{$kukarow["yhtio"]}'
            AND (hinnasto.minkpl     = 0 AND hinnasto.maxkpl = 0)
            AND hinnasto.laji       != 'O'
            AND hinnasto.maa         IN ('FI', '')
            AND hinnasto.valkoodi    in ('EUR', '')
            $muutoslisa";
  $res = pupe_query($query);

  // Tehdään hinnastot läpi
  while ($row = mysql_fetch_array($res)) {
    // Jos yhtiön hinnat eivät sisällä alv:tä
    if ($yhtiorow["alv_kasittely"] != "") {
      // Anviassa myyntihintaan verot päälle
      if ($verkkokauppatyyppi == 'anvia') {
        $hinta = hintapyoristys($row["hinta"] * (1+($row["alv"]/100)));
      }
      else {
        $hinta = $row["hinta"];
      }

      $hinta_veroton = $row["hinta"];
    }
    else {
      $hinta = $row["hinta"];
      $hinta_veroton = hintapyoristys($row["hinta"] / (1+($row["alv"]/100)));
    }

    $dnshinnasto[] = array(
      'tuoteno'       => $row["tuoteno"],
      'selite'        => $row["selite"],
      'alkupvm'       => $row["alkupvm"],
      'loppupvm'      => $row["loppupvm"],
      'hinta'         => $hinta,
      'hinta_veroton' => $hinta_veroton,
    );
  }

  return $dnshinnasto;
}

function tuote_export_hae_lajitelmatuotteet($params) {
  global $kukarow, $yhtiorow;

  $ajetaanko_kaikki    = $params['ajetaanko_kaikki'];
  $datetime_checkpoint = $params['datetime_checkpoint'];
  $verkkokauppatyyppi  = $params['verkkokauppatyyppi'];

  $dnslajitelma = array();

  // Magentoon vain tuotteet joiden näkyvyys != ''
  $nakyvyys_lisa = ($verkkokauppatyyppi == 'magento') ? "AND tuote.nakyvyys != ''" : "";

  // haetaan kaikki tuotteen variaatiot, jotka on menossa verkkokauppaan
  $query = "SELECT DISTINCT tuotteen_avainsanat.selite selite
            FROM tuotteen_avainsanat
            JOIN tuote ON (tuote.yhtio = tuotteen_avainsanat.yhtio
            AND tuote.tuoteno                = tuotteen_avainsanat.tuoteno
            AND tuote.status                != 'P'
            AND tuote.tuotetyyppi            NOT IN ('A','B')
            AND tuote.tuoteno               != ''
            $nakyvyys_lisa)
            WHERE tuotteen_avainsanat.yhtio  = '{$kukarow['yhtio']}'
            AND tuotteen_avainsanat.laji     = 'parametri_variaatio'
            AND trim(tuotteen_avainsanat.selite) != ''";
  $resselite = pupe_query($query);

  if ($ajetaanko_kaikki === false) {
    $muutoslisa = " AND (tuotteen_avainsanat.muutospvm >= '{$datetime_checkpoint}'
              OR try_fi.muutospvm  >= '{$datetime_checkpoint}'
              OR ta_nimitys_se.muutospvm >= '{$datetime_checkpoint}'
              OR ta_nimitys_en.muutospvm >= '{$datetime_checkpoint}'
              OR tuote.muutospvm  >= '{$datetime_checkpoint}')";
  }
  else {
    $muutoslisa = "";
  }

  // loopataan variaatio-nimitykset
  while ($rowselite = mysql_fetch_assoc($resselite)) {
    // Haetaan kaikki tuotteet, jotka kuuluu tähän variaatioon ja on muuttunut
    $aliselect = "SELECT
                  tuote.*,
                  tuotteen_avainsanat.tuoteno,
                  tuotteen_avainsanat.jarjestys,
                  ta_nimitys_se.selite nimi_swe,
                  ta_nimitys_en.selite nimi_eng,
                  tuote.mallitarkenne campaign_code,
                  tuote.malli target,
                  tuote.leimahduspiste onsale,
                  try_fi.selitetark try_nimi
                  FROM tuotteen_avainsanat
                  JOIN tuote on (tuote.yhtio = tuotteen_avainsanat.yhtio
                    AND tuote.tuoteno              = tuotteen_avainsanat.tuoteno
                    AND tuote.status              != 'P'
                    AND tuote.tuotetyyppi          NOT in ('A','B')
                    AND tuote.tuoteno             != ''
                    $nakyvyys_lisa)
                  LEFT JOIN avainsana as try_fi ON (try_fi.yhtio = tuote.yhtio
                    and try_fi.selite              = tuote.try
                    and try_fi.laji                = 'try'
                    and try_fi.kieli               = 'fi')
                  LEFT JOIN tuotteen_avainsanat as ta_nimitys_se on (tuote.yhtio = ta_nimitys_se.yhtio
                    and tuote.tuoteno              = ta_nimitys_se.tuoteno
                    and ta_nimitys_se.laji         = 'nimitys'
                    and ta_nimitys_se.kieli        = 'se')
                  LEFT JOIN tuotteen_avainsanat as ta_nimitys_en on (tuote.yhtio = ta_nimitys_en.yhtio
                    and tuote.tuoteno              = ta_nimitys_en.tuoteno
                    and ta_nimitys_en.laji         = 'nimitys'
                    and ta_nimitys_en.kieli        = 'en')
                  WHERE tuotteen_avainsanat.yhtio  = '{$kukarow['yhtio']}'
                  AND tuotteen_avainsanat.laji     = 'parametri_variaatio'
                  AND tuotteen_avainsanat.selite   = '{$rowselite['selite']}'
                  {$muutoslisa}
                  ORDER BY tuote.tuoteno";
    $alires = pupe_query($aliselect);

    while ($alirow = mysql_fetch_assoc($alires)) {
      // Haetaan kaikki tuotteen atribuutit
      $alinselect = "SELECT
                     tuotteen_avainsanat.selite,
                     avainsana.selitetark,
                     avainsana.selite option_name
                     FROM tuotteen_avainsanat USE INDEX (yhtio_tuoteno)
                     JOIN avainsana USE INDEX (yhtio_laji_selite) ON (avainsana.yhtio = tuotteen_avainsanat.yhtio
                       AND avainsana.laji             = 'PARAMETRI'
                       AND avainsana.selite           = SUBSTRING(tuotteen_avainsanat.laji, 11))
                     WHERE tuotteen_avainsanat.yhtio ='{$kukarow['yhtio']}'
                     AND tuotteen_avainsanat.laji    != 'parametri_variaatio'
                     AND tuotteen_avainsanat.laji    != 'parametri_variaatio_jako'
                     AND tuotteen_avainsanat.laji     like 'parametri_%'
                     AND tuotteen_avainsanat.tuoteno  = '{$alirow['tuoteno']}'
                     AND tuotteen_avainsanat.kieli    = 'fi'
                     ORDER by tuotteen_avainsanat.jarjestys, tuotteen_avainsanat.laji";
      $alinres = pupe_query($alinselect);
      $properties = array();

      while ($syvinrow = mysql_fetch_assoc($alinres)) {
        $properties[] = array(
          "nimi"        => $syvinrow["selitetark"],
          "option_name" => $syvinrow["option_name"],
          "arvo"        => $syvinrow["selite"],
        );
      }

      // Jos yhtiön hinnat eivät sisällä alv:tä
      if ($yhtiorow["alv_kasittely"] != "") {

        // Anviassa myyntihintaan verot päälle
        if ($verkkokauppatyyppi == 'anvia') {
          $myyntihinta = hintapyoristys($alirow["myyntihinta"] * (1+($alirow["alv"]/100)));
        }
        else {
          $myyntihinta = $alirow["myyntihinta"];
        }

        $myyntihinta_veroton = $alirow["myyntihinta"];
      }
      else {
        $myyntihinta = $alirow["myyntihinta"];
        $myyntihinta_veroton = hintapyoristys($alirow["myyntihinta"] / (1+($alirow["alv"]/100)));
      }

      $myymalahinta = $alirow["myymalahinta"];
      $myymalahinta_veroton = hintapyoristys($alirow["myymalahinta"] / (1+($alirow["alv"]/100)));

      // Jos tuote kuuluu tuotepuuhun niin etsitään kategoria_idt myös kaikille tuotepuun kategorioille
      $query = "SELECT t0.nimi node, t0.lft,
                tuote.tuoteno,
                GROUP_CONCAT(t5.nimi SEPARATOR '\n') children,
                (SELECT GROUP_CONCAT(t6.nimi SEPARATOR '\n')
                 FROM dynaaminen_puu t6
                 WHERE t6.lft<t0.lft AND t6.rgt>t0.rgt
                 AND t6.laji      = 'tuote'
                 ORDER BY t6.lft) ancestors
                FROM dynaaminen_puu t0
                LEFT JOIN
                (SELECT *
                 FROM (SELECT t1.lft node,
                 MAX(t2.lft) nodeparent
                 FROM dynaaminen_puu t1
                 INNER JOIN
                 dynaaminen_puu t2 ON t1.lft>t2.lft AND t1.rgt<t2.rgt
                 GROUP BY t1.lft) t3
                 LEFT JOIN
                 dynaaminen_puu t4 ON t3.node=t4.lft) t5 ON t0.lft=t5.nodeparent
                LEFT JOIN puun_alkio ON puun_alkio.puun_tunnus = t0.tunnus AND puun_alkio.yhtio = t0.yhtio
                 JOIN tuote ON tuote.tuoteno = puun_alkio.liitos AND tuote.yhtio = puun_alkio.yhtio
                WHERE t0.yhtio ='{$kukarow['yhtio']}'
                AND t0.laji       = 'tuote'
                AND tuote.tuoteno = '{$alirow['tuoteno']}'
                GROUP BY t0.nimi
                ORDER BY t0.lft";
      $result_tp = pupe_query($query);

      $tuotepuun_nodet = array();

      while ($tuotepuurow = mysql_fetch_assoc($result_tp)) {
        $breadcrumbs = empty($tuotepuurow['ancestors']) ? array () : explode("\n", $tuotepuurow['ancestors']);
        $breadcrumbs[] = $tuotepuurow['node'];

        if (count($breadcrumbs) > 1) {
          array_shift($breadcrumbs);
        }

        $tuotepuun_nodet[] = $breadcrumbs;
      }

      // Katsotaan onko tuotteelle voimassaolevaa hinnastohintaa
      $query = "SELECT *
                FROM hinnasto
                WHERE yhtio   = '{$kukarow['yhtio']}'
                  AND tuoteno = '{$alirow['tuoteno']}'
                  AND maa     = '{$yhtiorow['maa']}'
                  AND laji    = ''
                  AND ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
                ORDER BY ifnull(to_days(current_date)-to_days(alkupvm),9999999999999)
                LIMIT 1";
      $hinnastoq = pupe_query($query);
      $hinnastoresult = mysql_fetch_assoc($hinnastoq);

      // Nollataan tämä jos query lyö tyhjää, muuten vanhentunut tarjoushinta ei ylikirjoitu magentossa
      if (!isset($hinnastoresult['hinta'])) $hinnastoresult['hinta'] = '';

      $dnslajitelma[$rowselite["selite"]][] = array(
        'campaign_code'         => $alirow["campaign_code"],
        'ean'                   => $alirow["eankoodi"],
        'hinnastohinta'         => $hinnastoresult['hinta'],
        'jarjestys'             => $alirow["jarjestys"],
        'korkeus'               => $alirow['tuotekorkeus'],
        'kuluprosentti'         => $alirow['kuluprosentti'],
        'kuvaus'                => $alirow["kuvaus"],
        'leveys'                => $alirow['tuoteleveys'],
        'lyhytkuvaus'           => $alirow["lyhytkuvaus"],
        'malli'                 => $alirow['malli'],
        'muuta'                 => $alirow['muuta'],
        'myymalahinta'          => $myymalahinta,
        'myymalahinta_veroton'  => $myymalahinta_veroton,
        'myyntihinta'           => $myyntihinta,
        'myyntihinta_veroton'   => $myyntihinta_veroton,
        'nakyvyys'              => strtolower($alirow["nakyvyys"]),
        'nimi'                  => $alirow["nimitys"],
        'nimi_eng'              => $alirow["nimi_eng"],
        'nimi_swe'              => $alirow["nimi_swe"],
        'nimitys'               => $alirow["nimitys"],
        'onsale'                => $alirow["onsale"],
        'paino'                 => $alirow["tuotemassa"],
        'parametrit'            => $properties,
        'syvyys'                => $alirow['tuotesyvyys'],
        'target'                => $alirow["target"],
        'try_nimi'              => $alirow["try_nimi"],
        'tunnus'                => $alirow["tunnus"],
        'tuotemerkki'           => $alirow['tuotemerkki'],
        'tuoteno'               => $alirow["tuoteno"],
        'tuotepuun_nodet'       => $tuotepuun_nodet,
      );
    }
  }

  return $dnslajitelma;
}

function tuote_export_paivita_avainsana($timestamp) {
  global $kukarow, $yhtiorow;

  // Otetaan tietokantayhteys uudestaan (voi olla timeoutannu)
  require 'inc/connect.inc';

  // Päivitetään timestamp avainsanaan
  $query = "UPDATE avainsana SET
            selite = '{$timestamp}'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND laji = 'TUOTE_EXP_CRON'";
  pupe_query($query);

  if (mysql_affected_rows() != 1) {
    echo "Timestamp päivitys epäonnistui!\n";
  }
}

function tuote_export_echo($string) {
  echo date("d.m.Y @ G:i:s")." - {$string}\n";
}
