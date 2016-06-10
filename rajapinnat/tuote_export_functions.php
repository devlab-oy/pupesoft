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

  if ($ajetaanko_kaikki == "NO") {
    $muuttuneet_tuotenumerot = $muuttuneet_tuoteryhmat = '';

    if (!empty($magento_asiakaskohtaiset_tuotehinnat)) {
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

    if (isset($tuotteiden_asiakashinnat_magentoon)) {
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
      'tuoteno'              => $row["tuoteno"],
      'nimi'                 => $row["nimitys"],
      'kuvaus'               => $row["kuvaus"],
      'lyhytkuvaus'          => $row["lyhytkuvaus"],
      'yksikko'              => $row["yksikko"],
      'muuta'                => $row["muuta"],
      'tuotemassa'           => $row["tuotemassa"],
      'tuotemerkki'          => $row["tuotemerkki"],
      'myyntihinta'          => $myyntihinta,
      'myyntihinta_veroton'  => $myyntihinta_veroton,
      'myymalahinta'         => $myymalahinta,
      'myymalahinta_veroton' => $myymalahinta_veroton,
      'kuluprosentti'        => $row['kuluprosentti'],
      'ean'                  => $row["eankoodi"],
      'osasto'               => $row["osasto"],
      'try'                  => $row["try"],
      'try_nimi'             => $row["try_nimi"],
      'alv'                  => $row["alv"],
      'nakyvyys'             => $row["nakyvyys"],
      'nimi_swe'             => $row["nimi_swe"],
      'nimi_eng'             => $row["nimi_eng"],
      'campaign_code'        => $row["campaign_code"],
      'target'               => $row["target"],
      'onsale'               => $row["onsale"],
      'tunnus'               => $row['tunnus'],
      'hinnastohinta'        => $hinnastoresult['hinta'],
      'asiakashinnat'        => $asiakashinnat,
      'tuotepuun_nodet'      => $tuotepuun_nodet,
      'tuotteen_parametrit'  => $tuotteen_parametrit,
      'mainosteksti'         => $row['mainosteksti'],
      'myynti_era'           => $row['myynti_era']
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
