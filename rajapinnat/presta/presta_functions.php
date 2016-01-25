<?php

function hae_asiakkaat1() {
  global $kukarow, $yhtiorow, $verkkokauppatyyppi;

  $query = "SELECT yhteyshenkilo.*,
            avainsana.selitetark_5 AS presta_customergroup_id
            FROM yhteyshenkilo
            JOIN asiakas
            ON ( asiakas.yhtio = yhteyshenkilo.yhtio
              AND asiakas.tunnus      = yhteyshenkilo.liitostunnus )
            LEFT JOIN avainsana
            ON ( avainsana.yhtio = asiakas.yhtio
              AND avainsana.selite    = asiakas.ryhma
              AND avainsana.laji      = 'ASIAKASRYHMA' )
            WHERE yhteyshenkilo.yhtio = '{$kukarow['yhtio']}'
            AND yhteyshenkilo.rooli   = 'Presta'";
  $result = pupe_query($query);

  $asiakkaat = array();
  while ($asiakas = mysql_fetch_assoc($result)) {
    $asiakas['etunimi'] = '-';
    $asiakas['sukunimi'] = preg_replace('/[^a-zA-Z]/', '', $asiakas['nimi']);
    $asiakkaat[] = $asiakas;
  }

  return $asiakkaat;
}

function hae_yhteyshenkilon_asiakas_ulkoisella_asiakasnumerolla($asiakasnumero) {
  global $kukarow, $yhtiorow;

  if (empty($asiakasnumero)) {
    return false;
  }

  $query = "SELECT a.*
            FROM yhteyshenkilo AS y
            JOIN asiakas AS a
            ON ( a.yhtio = y.yhtio
              AND a.tunnus               = y.liitostunnus )
            WHERE y.yhtio                = '{$kukarow['yhtio']}'
            AND y.ulkoinen_asiakasnumero = {$asiakasnumero}";
  $result = pupe_query($query);

  return mysql_fetch_assoc($result);
}

function hae_asiakasryhmat() {
  global $kukarow, $yhtiorow;

  $query = "SELECT avainsana.*,
            avainsana.selitetark_5 AS presta_customergroup_id
            FROM avainsana
            WHERE avainsana.yhtio = '{$kukarow['yhtio']}'
            AND laji              = 'ASIAKASRYHMA'";
  $result = pupe_query($query);

  $ryhmat = array();
  while ($ryhma = mysql_fetch_assoc($result)) {
    $ryhmat[] = $ryhma;
  }

  return $ryhmat;
}

function presta_hae_asiakashinnat() {
  global $kukarow, $yhtiorow;

  //Huom! yhteyshenkilo.liitostunnus = asiakashinta.asiakas tarkoittaa ett� sama asiakashintarivi
  //voi tulla monta kertaa koska asiakas has_many yhteyshenkilo. N�in pit��kin koska yhteyshenkilo
  //on prestassa asiakas.
  $query = "SELECT asiakashinta.*,
            asiakashinta.hinta customer_price,
            (tuote.myyntihinta - asiakashinta.hinta) AS hinta_muutos,
            avainsana.selitetark_5 AS presta_customergroup_id,
            yhteyshenkilo.ulkoinen_asiakasnumero AS presta_customer_id
            FROM asiakashinta
            JOIN tuote
            ON ( tuote.yhtio = asiakashinta.yhtio
              AND tuote.tuoteno              = asiakashinta.tuoteno )
            LEFT JOIN avainsana
            ON ( avainsana.yhtio = asiakashinta.yhtio
              AND avainsana.selite           = asiakashinta.asiakas_ryhma
              AND avainsana.laji             = 'ASIAKASRYHMA' )
            LEFT JOIN yhteyshenkilo
            ON ( yhteyshenkilo.yhtio = asiakashinta.yhtio
              AND yhteyshenkilo.liitostunnus = asiakashinta.asiakas )
            WHERE asiakashinta.yhtio         = '{$kukarow['yhtio']}'";
  $result = pupe_query($query);

  $asiakashinnat = array();
  while ($asiakashinta = mysql_fetch_assoc($result)) {
    $asiakashinnat[] = $asiakashinta;
  }

  return $asiakashinnat;
}

function hae_kategoriat() {
  global $kukarow, $yhtiorow, $verkkokauppatyyppi;

  // haetaan kaikki kategoriat ja niiden parent_id
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

function hae_tuotteet() {
  global $kukarow, $yhtiorow, $verkkokauppatyyppi, $datetime_checkpoint, $ajetaanko_kaikki, $presta_ohita_tuotekuvat;

  if ($ajetaanko_kaikki == "NO") {
    $muutoslisa = " AND tuote.muutospvm >= '{$datetime_checkpoint}' ";
  }
  else {
    $muutoslisa = "";
  }

  // Haetaan pupesta tuotteen tiedot
  $query = "SELECT tuote.*
            FROM tuote
            WHERE tuote.yhtio        = '{$kukarow['yhtio']}'
              AND tuote.status      != 'P'
              AND tuote.tuotetyyppi  NOT in ('A','B')
              AND tuote.tuoteno     != ''
              AND tuote.nakyvyys    != ''
              $muutoslisa
            ORDER BY tuote.tuoteno";
  $res = pupe_query($query);
  $dnstuote = array();

  // Py�r�ytet��n muuttuneet tuotteet l�pi
  while ($row = mysql_fetch_array($res)) {
    // Jos yhti�n hinnat eiv�t sis�ll� alv:t�
    if ($yhtiorow["alv_kasittely"] != "" and $verkkokauppatyyppi != 'magento') {
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

    $asiakashinnat = array();

    if (isset($tuotteiden_asiakashinnat_magentoon)) {
      $query = "SELECT
                avainsana.selitetark AS asiakasryhma,
                asiakashinta.tuoteno,
                asiakashinta.hinta
                FROM asiakas
                JOIN avainsana ON (avainsana.yhtio = asiakas.yhtio
                  AND avainsana.selite = asiakas.ryhma
                  AND avainsana.laji = 'asiakasryhma')
                JOIN asiakashinta ON (asiakashinta.yhtio = asiakas.yhtio
                  AND asiakashinta.asiakas_ryhma = asiakas.ryhma)
                WHERE asiakas.yhtio = '{$kukarow['yhtio']}'
                AND asiakashinta.tuoteno ='{$row['tuoteno']}'
                GROUP BY 1, 2, 3";
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
                          AND avainsana.laji = 'PARAMETRI'
                          AND avainsana.selite = SUBSTRING(tuotteen_avainsanat.laji, 11))
                        WHERE tuotteen_avainsanat.yhtio = '{$kukarow['yhtio']}'
                        AND tuotteen_avainsanat.laji != 'parametri_variaatio'
                        AND tuotteen_avainsanat.laji != 'parametri_variaatio_jako'
                        AND tuotteen_avainsanat.laji like 'parametri_%'
                        AND tuotteen_avainsanat.tuoteno = '{$row['tuoteno']}'
                        AND tuotteen_avainsanat.kieli = 'fi'
                        ORDER by tuotteen_avainsanat.jarjestys, tuotteen_avainsanat.laji";
    $parametritres = pupe_query($parametritquery);
    $tuotteen_parametrit = array();

    while ($parametrirow = mysql_fetch_assoc($parametritres)) {
      $tuotteen_parametrit[] = array(
        "nimi"        => $parametrirow["selitetark"],
        "option_name" => $parametrirow["option_name"],
        "arvo"        => $parametrirow["selite"]
      );
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

    // Katsotaan onko tuotteelle voimassaolevaa hinnastohintaa
    $query = "SELECT
              *
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

    // Nollataan t�m� jos query ly� tyhj��, muuten vanhentunut tarjoushinta ei ylikirjoitu magentossa
    if (!isset($hinnastoresult['hinta'])) {
      $hinnastoresult['hinta'] = '';
    }

    list(, , $myytavissa) = saldo_myytavissa($row["tuoteno"]);

    $dnstuote[] = array(
      'tuoteno'              => $row["tuoteno"],
      'nimi'                 => $row["nimitys"],
      'kuvaus'               => $row["kuvaus"],
      'lyhytkuvaus'          => $row["lyhytkuvaus"],
      'yksikko'              => $row["yksikko"],
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
      'tuotepuun_tunnukset'  => $tuotepuun_tunnukset,
      'tuotteen_parametrit'  => $tuotteen_parametrit,
      'saldo'                => $myytavissa,
    );

    if (isset($lukitut_tuotekentat) and !empty($lukitut_tuotekentat)) {
      foreach ($lukitut_tuotekentat as $lukittu_kentta) {
        unset($dnstuote[$lukittu_kentta]);
      }
    }
  }

  return $dnstuote;
}

function hae_tuotekuvat($tuote_tunnus) {
  global $kukarow, $yhtiorow, $presta_ohita_tuotekuvat;

  if (isset($presta_ohita_tuotekuvat) and !empty($presta_ohita_tuotekuvat)) {
    return array();
  }

  $query = "SELECT *
            FROM liitetiedostot
            WHERE yhtio      = '{$kukarow['yhtio']}'
            AND liitos       = 'tuote'
            AND liitostunnus = '{$tuote_tunnus}'
            ORDER BY jarjestys ASC";
  $result = pupe_query($query);
  $tuotekuvat = array();

  while ($tuotekuva = mysql_fetch_assoc($result)) {
    $tuotekuvat[] = $tuotekuva;
  }

  return $tuotekuvat;
}
