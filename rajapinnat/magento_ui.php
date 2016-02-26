<?php

ini_set("memory_limit", "5G");
ini_set("max_execution_time", 0);
//86400 = 1day
set_time_limit(86400);

$compression = FALSE;

$pupe_root_polku = dirname(dirname(__FILE__));

require "{$pupe_root_polku}/inc/connect.inc";
require "{$pupe_root_polku}/inc/parametrit.inc";
require "{$pupe_root_polku}/inc/functions.inc";
require "{$pupe_root_polku}/rajapinnat/magento_client.php";

if (!isset($action)) {
  $action = '';
}

if (!isset($synkronointi_tyyppi)) {
  $synkronointi_tyyppi = '';
}

// Varmistetaan, että kaikki muuttujat on kunnossa
if (empty($magento_api_te_url) or empty($magento_api_te_usr) or empty($magento_api_te_pas)
  or empty($magento_tax_class_id) or empty($verkkokauppa_saldo_varasto)) {
  die("Magento parametrit puuttuu, ohjelmaa ei voida ajaa.");
}

if (!isset($yhtiorow)) {
  die('Yhtiorow puuttuu');
}

$request = array(
  'action'              => $action,
  'synkronointi_tyyppi' => $synkronointi_tyyppi,
);

$request['synkronointi_tyypit'] = array(
  'kategoriat'          => t('Kategoriat'),
  'tuotteet'            => t('Tuotteet'),
  'lajitelmatuotteet'   => t('Lajitelmatuotteet'),
  'tuotekuvat'          => t('Tuotekuvat'),
  'saldot'              => t('Saldot'),
  'asiakkaat'           => t('Asiakkaat'),
  'asiakashinnat'       => t('Asiakashinnat'),
  'poista_ylimaaraiset' => t('Poista ylimääräiset tuotteet')
);

if ($request['action'] == 'sync') {
  echo_kayttoliittyma($request);
  echo "<br/>";

  $synkronoi = array();

  if ($request['synkronointi_tyyppi'] == 'kaikki') {
    foreach ($request['synkronointi_tyypit'] as $k => $s) {
      if ($k != 'kaikki') {
        $synkronoi[] = $k;
      }
    }
  }
  else {
    $synkronoi[] = $request['synkronointi_tyyppi'];
  }

  $time_start = microtime(true);

  /*$magento_client = new MagentoClient($magento_api_te_url, $magento_api_te_usr, $magento_api_te_pas);

  if ($magento_client->getErrorCount() > 0) {
    echo "Virhe Magentoclientin luomisessa!\n";
    exit;
  }*/

  if (in_array('kategoriat', $synkronoi)) {
    echo date("d.m.Y @ G:i:s")." - Päivitetään tuotekategoriat\n";
    $kategoriat = hae_kategoriat();
    exit;
    $count = 0;
    if (count($kategoriat) > 0) {
      $count = $magento_client->lisaa_kategoriat($kategoriat);
    }
    echo date("d.m.Y @ G:i:s")." - Päivitettiin $count kategoriaa\n";
  }

  if (in_array('tuotteet', $synkronoi)) {
    echo date("d.m.Y @ G:i:s")." - Päivitetään simple tuotteet\n";
    $tuotteet = hae_tuotteet();
    $tuotenumerot = $magento_client->hae_kaikki_tuotteet();
    $individual_tuotteet = $tuotenumerot[1];
    exit;
    $count = 0;
    if (count($tuotteet) > 0 and count($individual_tuotteet) > 0) {
      $count = $magento_client->lisaa_simple_tuotteet($tuotteet, $individual_tuotteet);
    }
    echo date("d.m.Y @ G:i:s")." - Päivitettiin $count tuotetta (simple)\n";
  }

  if (in_array('lajitelmatuotteet', $synkronoi)) {
    echo date("d.m.Y @ G:i:s")." - Päivitetään configurable tuotteet\n";
    $lajitelmatuotteet = hae_configurable_tuotteet();
    var_dump($lajitelmatuotteet);
    exit;
    $count = 0;
    if (count($lajitelmatuotteet) > 0) {
      $count = $magento_client->lisaa_configurable_tuotteet($lajitelmatuotteet);
    }
    echo date("d.m.Y @ G:i:s")." - Päivitettiin $count tuotetta (configurable)\n";
  }

  if (in_array('tuotekuvat', $synkronoi)) {
    echo date("d.m.Y @ G:i:s")." - Päivitetään tuotekuvat\n";
    // haetaan kaikki tuotteet
    $tuotteet = $magento_client->hae_kaikki_tuotteet();
    $kaikki_tuotteet = $tuotteet[0];
    exit;
    $magento_client->lisaa_tuotteiden_kuvat($kaikki_tuotteet);
    echo date("d.m.Y @ G:i:s")." - Päivitettiin tuotekuvat\n";
  }

  if (in_array('saldot', $synkronoi)) {
    echo date("d.m.Y @ G:i:s")." - Päivitetään tuotteiden saldot\n";
    $saldot = hae_saldot();
    exit;
    $count = $magento_client->paivita_saldot($saldot);
    echo date("d.m.Y @ G:i:s")." - Päivitettiin $count tuotteen saldot\n";
  }

  if (in_array('asiakkaat', $synkronoi)) {
    echo date("d.m.Y @ G:i:s")." - Päivitetään asiakkaat\n";
    $asiakkaat = hae_asiakkaat1();
    exit;
    $count = $magento_client->lisaa_asiakkaat($asiakkaat);
    echo date("d.m.Y @ G:i:s")." - Päivitettiin $count asiakkaan tiedot\n";
  }

  if (in_array('asiakashinnat', $synkronoi)) {
    $hinnat = hae_asiakashinnat();
    exit;
    // KISSA
  }

  if (in_array('poista_ylimaaraiset', $synkronoi)) {
    echo date("d.m.Y @ G:i:s")." - Poistetaan ylimääräiset tuotteet\n";
    $tuotenumerot = $magento_client->hae_kaikki_tuotteet();
    $kaikki_tuotteet = $tuotenumerot[0];
    exit;
    $count = $magento_client->poista_poistetut($kaikki_tuotteet, true);
    echo date("d.m.Y @ G:i:s")." - Poistettiin $count tuotetta\n";
  }

  //$tuote_export_error_count = $magento_client->getErrorCount();

  if ($tuote_export_error_count != 0) {
    echo date("d.m.Y @ G:i:s")." - Päivityksessä tapahtui {$tuote_export_error_count} virhettä!\n";
  }

  $time_end = microtime(true);
  $time = round($time_end - $time_start);

  echo date("d.m.Y @ G:i:s")." - Magento-siirto valmis! (Magento API {$time} sekuntia)\n";
}
else {
  echo_kayttoliittyma($request);
}

require 'inc/footer.inc';

function echo_kayttoliittyma($request) {
  global $kukarow, $yhtiorow;

  echo "<form action='' method='POST'>";
  echo "<input type='hidden' name='action' value='sync' />";
  echo "<table>";

  echo "<tr>";
  echo "<th>" . t('Synkronoi') . "</th>";
  echo "<td>";
  echo "<select name='synkronointi_tyyppi'>";
  foreach ($request['synkronointi_tyypit'] as $synkronointi_tyyppi => $selitys) {
    $sel = "";
    if ($request['synkronointi_tyyppi'] == $synkronointi_tyyppi) {
      $sel = "SELECTED";
    }
    echo "<option value='{$synkronointi_tyyppi}' {$sel}>{$selitys}</option>";
  }
  echo "</select>";
  echo "</td>";
  echo "</tr>";

  echo "</table>";

  echo "<input type='submit' value='" . t('Lähetä') . "' />";
  echo "</form>";
}

function hae_asiakkaat1() {
  global $kukarow, $yhtiorow;

  $query = "SELECT yhteyshenkilo.*,
            avainsana.selitetark_5 AS customergroup_id
            FROM yhteyshenkilo
            JOIN asiakas
            ON ( asiakas.yhtio = yhteyshenkilo.yhtio
              AND asiakas.tunnus      = yhteyshenkilo.liitostunnus )
            LEFT JOIN avainsana
            ON ( avainsana.yhtio = asiakas.yhtio
              AND avainsana.selite    = asiakas.ryhma
              AND avainsana.laji      = 'ASIAKASRYHMA' )
            WHERE yhteyshenkilo.yhtio = '{$kukarow['yhtio']}'
            AND yhteyshenkilo.rooli   = 'Magento'";
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
            avainsana.selitetark_5 AS customergroup_id
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

function hae_asiakashinnat() {
  global $kukarow, $yhtiorow;

  //Huom! yhteyshenkilo.liitostunnus = asiakashinta.asiakas tarkoittaa että sama asiakashintarivi
  //voi tulla monta kertaa koska asiakas has_many yhteyshenkilo. Näin pitääkin koska yhteyshenkilo
  //on prestassa asiakas.
  $query = "SELECT asiakashinta.*,
            asiakashinta.hinta customer_price,
            (tuote.myyntihinta - asiakashinta.hinta) AS hinta_muutos,
            avainsana.selitetark_5 AS customergroup_id,
            yhteyshenkilo.ulkoinen_asiakasnumero AS customer_id
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
  global $kukarow, $yhtiorow;

  $query = "SELECT
            node.lft AS lft,
            node.rgt AS rgt,
            node.nimi AS node_nimi,
            node.koodi AS node_koodi,
            node.tunnus AS node_tunnus,
            node.syvyys as node_syvyys,
            (COUNT(node.tunnus) - 1) AS syvyys
            FROM dynaaminen_puu AS node
            JOIN dynaaminen_puu AS parent ON node.yhtio=parent.yhtio and node.laji=parent.laji AND node.lft BETWEEN parent.lft AND parent.rgt
            WHERE node.yhtio = '{$kukarow['yhtio']}'
            AND node.laji    = 'tuote'
            GROUP BY node.lft
            ORDER BY node.lft";

  $result = pupe_query($query);

  $kategoriat = array();
  while ($kategoria = mysql_fetch_assoc($result)) {
    $kategoriat[] = $kategoria;
  }

  return $kategoriat;
}

function hae_tuotteet() {
  global $kukarow, $yhtiorow;

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
            ORDER BY tuote.tuoteno";
  $res = pupe_query($query);
  $dnstuote = array();

  // Pyöräytetään muuttuneet tuotteet läpi
  while ($row = mysql_fetch_array($res)) {

    $myyntihinta = $row["myyntihinta"];
    $myyntihinta_veroton = hintapyoristys($row["myyntihinta"] / (1 + ($row["alv"] / 100)));

    $myymalahinta = $row["myymalahinta"];
    $myymalahinta_veroton = hintapyoristys($row["myymalahinta"] / (1 + ($row["alv"] / 100)));

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

    $tuotepuun_nodet = array();

    while ($tuotepuurow = mysql_fetch_assoc($result_tp)) {
      $breadcrumbs = empty($tuotepuurow['ancestors']) ? array() : explode("\n", $tuotepuurow['ancestors']);
      $breadcrumbs[] = $tuotepuurow['node'];
      if (count($breadcrumbs) > 1)
        array_shift($breadcrumbs);
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
    // Nollataan tämä jos query lyö tyhjää, muuten vanhentunut tarjoushinta ei ylikirjoitu magentossa
    if (!isset($hinnastoresult['hinta'])) $hinnastoresult['hinta'] = '';

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
      'tuotepuun_nodet'      => $tuotepuun_nodet,
      'tuotteen_parametrit'  => $tuotteen_parametrit
    );

    if (isset($lukitut_tuotekentat) and !empty($lukitut_tuotekentat)) {
      foreach ($lukitut_tuotekentat as $lukittu_kentta) {
        unset($dnstuote[$lukittu_kentta]);
      }
    }
  }

  return $dnstuote;
}

function hae_configurable_tuotteet() {
  global $kukarow, $yhtiorow;

  echo date("d.m.Y @ G:i:s")." - Haetaan tuotteiden variaatiot.\n";
  $dnslajitelma = array();

  // haetaan kaikki tuotteen variaatiot, jotka on menossa verkkokauppaan
  $query = "SELECT DISTINCT tuotteen_avainsanat.selite selite
            FROM tuotteen_avainsanat
            JOIN tuote ON (tuote.yhtio = tuotteen_avainsanat.yhtio
            AND tuote.tuoteno                = tuotteen_avainsanat.tuoteno
            AND tuote.status                != 'P'
            AND tuote.tuotetyyppi            NOT IN ('A','B')
            AND tuote.tuoteno               != ''
            AND tuote.nakyvyys              != '')
            WHERE tuotteen_avainsanat.yhtio  = '{$kukarow['yhtio']}'
            AND tuotteen_avainsanat.laji     = 'parametri_variaatio'
            AND trim(tuotteen_avainsanat.selite) != ''";
  $resselite = pupe_query($query);

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
                    AND tuote.nakyvyys            != '')
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
          "nimi" => $syvinrow["selitetark"],
          "option_name" => $syvinrow["option_name"],
          "arvo" => $syvinrow["selite"]
        );
      }

      // Jos yhtiön hinnat eivät sisällä alv:tä
      if ($yhtiorow["alv_kasittely"] != "") {

        $myyntihinta         = $alirow["myyntihinta"];
        $myyntihinta_veroton   = $alirow["myyntihinta"];
      }
      else {
        $myyntihinta           = $alirow["myyntihinta"];
        $myyntihinta_veroton   = hintapyoristys($row["myyntihinta"] / (1+($alirow["alv"]/100)));
      }

      $myymalahinta          = $alirow["myymalahinta"];
      $myymalahinta_veroton  = hintapyoristys($alirow["myymalahinta"] / (1+($alirow["alv"]/100)));

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

      $tuotepuun_nodet = array ();

      while ($tuotepuurow = mysql_fetch_assoc($result_tp)) {
        $breadcrumbs = empty($tuotepuurow['ancestors']) ? array () : explode("\n", $tuotepuurow['ancestors']);
        $breadcrumbs[] = $tuotepuurow['node'];
        if (count($breadcrumbs) > 1) array_shift($breadcrumbs);
        $tuotepuun_nodet[] = $breadcrumbs;
      }

      // Katsotaan onko tuotteelle voimassaolevaa hinnastohintaa
      $query = "SELECT
                *
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
        'tuoteno'               => $alirow["tuoteno"],
        'tunnus'                => $alirow["tunnus"],
        'nimitys'               => $alirow["nimitys"],
        'kuvaus'                => $alirow["kuvaus"],
        'lyhytkuvaus'           => $alirow["lyhytkuvaus"],
        'tuotemassa'            => $alirow["tuotemassa"],
        'nakyvyys'              => $alirow["nakyvyys"],
        'try_nimi'              => $alirow["try_nimi"],
        'nimi_swe'              => $alirow["nimi_swe"],
        'nimi_eng'              => $alirow["nimi_eng"],
        'campaign_code'         => $alirow["campaign_code"],
        'target'                => $alirow["target"],
        'onsale'                => $alirow["onsale"],
        'jarjestys'             => $alirow["jarjestys"],
        'myyntihinta'           => $myyntihinta,
        'myyntihinta_veroton'   => $myyntihinta_veroton,
        'myymalahinta'          => $myymalahinta,
        'myymalahinta_veroton'  => $myymalahinta_veroton,
        'hinnastohinta'         => $hinnastoresult['hinta'],
        'kuluprosentti'         => $alirow['kuluprosentti'],
        'ean'                   => $alirow["eankoodi"],
        'muuta'                 => $alirow['muuta'],
        'tuotemerkki'           => $alirow['tuotemerkki'],
        'parametrit'            => $properties,
        'tuotepuun_nodet'       => $tuotepuun_nodet
      );
    }
  }

  return $dnslajitelma;
}

function hae_saldot() {
  global $kukarow, $yhtiorow, $verkkokauppa_saldo_varasto;

  $dnstock = array();

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
              WHERE tapahtuma.yhtio    = '{$kukarow["yhtio"]}')

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
              WHERE tilausrivi.yhtio   = '{$kukarow["yhtio"]}')

              UNION

              (SELECT tuote.tuoteno,
              tuote.eankoodi
              FROM tuote
              WHERE tuote.yhtio        = '{$kukarow["yhtio"]}'
              AND tuote.status        != 'P'
              AND tuote.tuotetyyppi    NOT in ('A','B')
              AND tuote.tuoteno       != ''
              AND tuote.nakyvyys      != '')
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
