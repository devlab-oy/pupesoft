<?php

ini_set("memory_limit", "5G");
ini_set("max_execution_time", 0);
//86400 = 1day
set_time_limit(86400);

$compression = FALSE;

require "../../inc/parametrit.inc";
require_once "rajapinnat/presta/presta_products.php";
require_once "rajapinnat/presta/presta_categories.php";
require_once "rajapinnat/presta/presta_customers.php";
require_once "rajapinnat/presta/presta_customer_groups.php";
require_once "rajapinnat/presta/presta_sales_orders.php";
require_once 'rajapinnat/presta/presta_product_stocks.php';
require_once 'rajapinnat/presta/presta_shops.php';
require_once 'rajapinnat/presta/presta_specific_prices.php';
require_once 'rajapinnat/presta/presta_addresses.php';

if (!isset($action)) {
  $action = '';
}
if (!isset($synkronointi_tyyppi)) {
  $synkronointi_tyyppi = '';
}
if (!isset($presta_url)) {
  die('Presta url puuttuu');
}
if (!isset($presta_api_key)) {
  die('Presta api key puuttuu');
}
if (!isset($presta_edi_folderpath)) {
  die('Presta edi folder path puuttuu');
}
if (!isset($yhtiorow)) {
  die('Yhtiorow puuttuu');
}

$request = array(
    'action'              => $action,
    'synkronointi_tyyppi' => $synkronointi_tyyppi,
);

$request['synkronointi_tyypit'] = array(
    'kaikki'        => t('Kaikki'),
    'kategoriat'    => t('Kategoriat'),
    'tuotteet'      => t('Tuotteet ja tuotekuvat'),
    'asiakasryhmat' => t('Asiakasryhmät'),
    'asiakkaat'     => t('Asiakkaat'),
    'asiakashinnat' => t('Asiakashinnat'),
    'tilaukset'     => t('Tilauksien haku'),
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

  $ok = true;
  if ($ok and in_array('kategoriat', $synkronoi)) {
    $kategoriat = hae_kategoriat();
    $presta_categories = new PrestaCategories($presta_url, $presta_api_key);
    $ok = $presta_categories->sync_categories($kategoriat);
  }

  if ($ok and in_array('tuotteet', $synkronoi)) {
    $tuotteet = hae_tuotteet();
    $presta_products = new PrestaProducts($presta_url, $presta_api_key);
    if (isset($presta_ohita_tuoteparametrit) and count($presta_ohita_tuoteparametrit) > 0) {
      $presta_products->set_removable_fields($presta_ohita_tuoteparametrit);
    }
    $ok = $presta_products->sync_products($tuotteet);
  }

  if ($ok and in_array('asiakasryhmat', $synkronoi)) {
    $groups = hae_asiakasryhmat();
    $presta_customer_groups = new PrestaCustomerGroups($presta_url, $presta_api_key);
    $ok = $presta_customer_groups->sync_groups($groups);
  }

  if ($ok and in_array('asiakkaat', $synkronoi)) {
    $asiakkaat = hae_asiakkaat1();
    $presta_customer = new PrestaCustomers($presta_url, $presta_api_key);
    $ok = $presta_customer->sync_customers($asiakkaat);
  }

  if ($ok and in_array('asiakashinnat', $synkronoi)) {
    $hinnat = hae_asiakashinnat();
    $presta_prices = new PrestaSpecificPrices($presta_url, $presta_api_key);
    $presta_prices->sync_prices($hinnat);
  }

  if ($ok and in_array('tilaukset', $synkronoi)) {
    $presta_orders = new PrestaSalesOrders($presta_url, $presta_api_key);
    $presta_orders->set_edi_filepath($presta_edi_folderpath);
    $presta_orders->set_yhtiorow($yhtiorow);
    $presta_orders->transfer_orders_to_pupesoft();
  }
}
else {
  echo_kayttoliittyma($request);
}

require('inc/footer.inc');

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
  global $kukarow, $yhtiorow, $verkkokauppatyyppi;

  $query = "SELECT yhteyshenkilo.*,
            avainsana.selitetark_5 AS presta_customergroup_id
            FROM yhteyshenkilo
            JOIN asiakas
            ON ( asiakas.yhtio = yhteyshenkilo.yhtio
              AND asiakas.tunnus = yhteyshenkilo.liitostunnus )
            LEFT JOIN avainsana
            ON ( avainsana.yhtio = asiakas.yhtio
              AND avainsana.selite = asiakas.ryhma
              AND avainsana.laji = 'ASIAKASRYHMA' )
            WHERE yhteyshenkilo.yhtio = '{$kukarow['yhtio']}'
            AND yhteyshenkilo.rooli = 'Presta'";
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
              AND a.tunnus = y.liitostunnus )
            WHERE y.yhtio = '{$kukarow['yhtio']}'
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
            AND laji = 'ASIAKASRYHMA'";
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
            avainsana.selitetark_5 AS presta_customergroup_id,
            yhteyshenkilo.ulkoinen_asiakasnumero AS presta_customer_id
            FROM asiakashinta
            JOIN tuote
            ON ( tuote.yhtio = asiakashinta.yhtio
              AND tuote.tuoteno = asiakashinta.tuoteno )
            LEFT JOIN avainsana
            ON ( avainsana.yhtio = asiakashinta.yhtio
              AND avainsana.selite = asiakashinta.asiakas_ryhma
              AND avainsana.laji = 'ASIAKASRYHMA' )
            LEFT JOIN yhteyshenkilo
            ON ( yhteyshenkilo.yhtio = asiakashinta.yhtio
              AND yhteyshenkilo.liitostunnus = asiakashinta.asiakas )
            WHERE asiakashinta.yhtio = '{$kukarow['yhtio']}'";
  $result = pupe_query($query);

  $asiakashinnat = array();
  while ($asiakashinta = mysql_fetch_assoc($result)) {
    $asiakashinnat[] = $asiakashinta;
  }

  return $asiakashinnat;
}

function hae_kategoriat() {
  global $kukarow, $yhtiorow, $verkkokauppatyyppi;

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
  global $kukarow, $yhtiorow, $verkkokauppatyyppi;
  // Haetaan pupesta tuotteen tiedot
  $query = "SELECT
            tuote.*
            FROM tuote
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

    // Jos yhtiön hinnat eivät sisällä alv:tä
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
    if (!isset($hinnastoresult['hinta']))
      $hinnastoresult['hinta'] = '';

    list(,, $myytavissa) = saldo_myytavissa($row["tuoteno"]);

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
        'tuotteen_parametrit'  => $tuotteen_parametrit,
        'saldo'                => $myytavissa,
        'images'               => hae_tuotekuvat($row['tunnus']),
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
  global $kukarow, $yhtiorow;

  $query = "SELECT *
            FROM liitetiedostot
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND liitos = 'tuote'
            AND liitostunnus = '{$tuote_tunnus}'
            ORDER BY jarjestys ASC";
  $result = pupe_query($query);
  $tuotekuvat = array();
  while ($tuotekuva = mysql_fetch_assoc($result)) {
    $tuotekuvat[] = $tuotekuva;
  }

  return $tuotekuvat;
}
