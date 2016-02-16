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

  // HUOM! yhteyshenkilo.liitostunnus = asiakashinta.asiakas tarkoittaa, että sama asiakashintarivi
  // voi tulla monta kertaa, koska asiakas has_many yhteyshenkilö.
  // Näin pitääkin, koska yhteyshenkilö on Prestassa asiakas.

  // HUOM! pakko hakea kaikki alennukset, koska asiakkaalta poistetaan aina kaikki alennukset.

  // Laitetaan hinnat ja alennukset samaan arrayseen, koska Prestassa niitä käsitellään samalla tavalla

  // Haetaan from tuote, koska pitää saada kaikki tuotteet, jotka on menossa prestaan.
  // Vaikka ei olisi alennusta, koska muuten ei saada poistettua alennuksia tuotteilta

  // Asiakashinnat kaikille tuotetteilla
  $query = "SELECT
            tuote.tuoteno,
            asiakashinta.alkupvm,
            asiakashinta.loppupvm,
            asiakashinta.minkpl,
            asiakashinta.hinta,
            asiakashinta.valkoodi,
            avainsana.selitetark_5 AS presta_customergroup_id,
            yhteyshenkilo.ulkoinen_asiakasnumero AS presta_customer_id
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
  $query = "SELECT
            tuote.tuoteno,
            asiakasalennus.alkupvm,
            asiakasalennus.loppupvm,
            asiakasalennus.minkpl,
            asiakasalennus.alennus,
            avainsana.selitetark_5 AS presta_customergroup_id,
            yhteyshenkilo.ulkoinen_asiakasnumero AS presta_customer_id
            FROM tuote
            LEFT JOIN asiakasalennus ON (asiakasalennus.yhtio = tuote.yhtio
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

  return $specific_prices;
}

function hae_kategoriat() {
  global $kukarow, $yhtiorow;

  // haetaan kaikki kategoriat ja niiden parent_id
  // HUOM! pakko hakea aina kaikki, että osataan poistaa poistetut/siirretyt
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
  global $kukarow, $yhtiorow;

  $tuoterajaus = presta_tuoterajaus();

  // Haetaan kaikki siirrettävät tuotteet, tämä on poistettujen dellausta varten
  // query pitää olla sama kun hae_tuotteet (ilman muutospäivää)
  $query = "SELECT tuote.tuoteno
            FROM tuote
            WHERE tuote.yhtio = '{$kukarow['yhtio']}'
            {$tuoterajaus}";
  $res = pupe_query($query);

  $tuotteet = array();

  while ($row = mysql_fetch_array($res)) {
    $tuotteet[] = $row['tuoteno'];
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

  // Pyöräytetään muuttuneet tuotteet läpi
  while ($row = mysql_fetch_array($res)) {
    // Jos yhtiön hinnat eivät sisällä alv:tä
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

    // erikoistapaukset, jos hintoja halutaan siirtää vasten pupen alv_käsittelyä
    $myyntihinta_verot_pois    = hintapyoristys($row["myyntihinta"]  / (1 + ($row["alv"] / 100)), 6);
    $myyntihinta_verot_mukaan  = hintapyoristys($row["myyntihinta"]  * (1 + ($row["alv"] / 100)), 6);
    $myymalahinta_verot_pois   = hintapyoristys($row["myymalahinta"] / (1 + ($row["alv"] / 100)), 6);
    $myymalahinta_verot_mukaan = hintapyoristys($row["myymalahinta"] * (1 + ($row["alv"] / 100)), 6);

    // Haetaan tuotteen kaikki käännökset
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

    // Haetaan tuotteen myytävissä määrä (saldo)
    list(, , $myytavissa) = saldo_myytavissa($row["tuoteno"]);

    $dnstuote[] = array(
      'alv'                       => $row["alv"],
      'ean'                       => $row["eankoodi"],
      'kuluprosentti'             => $row['kuluprosentti'],
      'kuvaus'                    => $row["kuvaus"],
      'lyhytkuvaus'               => $row["lyhytkuvaus"],
      'nakyvyys'                  => $row["nakyvyys"],
      'nimi'                      => $row["nimitys"],
      'osasto'                    => $row["osasto"],
      'status'                    => $row["status"],
      'try'                       => $row["try"],
      'tunnus'                    => $row['tunnus'],
      'tuotemassa'                => $row["tuotemassa"],
      'tuotemerkki'               => $row["tuotemerkki"],
      'tuoteno'                   => $row["tuoteno"],
      'yksikko'                   => $row["yksikko"],
      'myymalahinta'              => $myymalahinta,
      'myymalahinta_verot_mukaan' => $myymalahinta_verot_mukaan,
      'myymalahinta_verot_pois'   => $myymalahinta_verot_pois,
      'myymalahinta_veroton'      => $myymalahinta_veroton,
      'myyntihinta'               => $myyntihinta,
      'myyntihinta_verot_mukaan'  => $myyntihinta_verot_mukaan,
      'myyntihinta_verot_pois'    => $myyntihinta_verot_pois,
      'myyntihinta_veroton'       => $myyntihinta_veroton,
      'saldo'                     => $myytavissa,
      'tuotepuun_tunnukset'       => $tuotepuun_tunnukset,
      'tuotteen_kaannokset'       => $tuotteen_kaannokset,
    );
  }

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
