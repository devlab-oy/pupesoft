<?php

ini_set("memory_limit", "5G");
ini_set("max_execution_time", 0);
//86400 = 1day
set_time_limit(86400);

$compression = FALSE;

require "../../inc/parametrit.inc";
require "rajapinnat/presta/presta_products.php";
require "rajapinnat/presta/presta_categories.php";
require "rajapinnat/presta/presta_customers.php";
require "rajapinnat/presta/presta_sales_orders.php";

if (!isset($action)) {
  $action = '';
}
if (!isset($synkronointi_tyyppi)) {
  $synkronointi_tyyppi = '';
}

$request = array(
    'action'              => $action,
    'synkronointi_tyyppi' => $synkronointi_tyyppi,
);


$request['synkronointi_tyypit'] = array(
    'kaikki'     => t('Kaikki'),
    'kategoriat' => t('Kategoriat'),
    'tuotteet'   => t('Tuotteet ja tuotekuvat'),
    'asiakkaat'  => t('Asiakkaat'),
    'tilaukset'  => t('Tilauksien haku'),
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

  if (in_array('kategoriat', $synkronoi)) {
    $kategoriat = hae_kategoriat();
    $presta_categories = new PrestaCategories($presta_url, $presta_api_key);
    $presta_categories->sync_categories($kategoriat);
  }

  if (in_array('tuotteet', $synkronoi)) {
    $tuotteet = hae_tuotteet();
    $presta_products = new PrestaProducts($presta_url, $presta_api_key);
    $presta_products->sync_products($tuotteet);
  }

  if (in_array('asiakkaat', $synkronoi)) {
    $asiakkaat = hae_asiakkaat1();
    $presta_customer = new PrestaCustomers($presta_url, $presta_api_key);
    $presta_customer->sync_customers($asiakkaat);
  }

  if (in_array('tilaukset', $synkronoi)) {
    $presta_orders = new PrestaSalesOrders($presta_url, $presta_api_key);
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

  $query = "SELECT *
            FROM asiakas
            WHERE yhtio = '{$kukarow['yhtio']}'";
  $result = pupe_query($query);

  $asiakkaat = array();
  while ($asiakas = mysql_fetch_assoc($result)) {
    $asiakkaat[] = $asiakas;
  }

  return $asiakkaat;
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
        'images'               => hae_tuotekuvat($row['tunnus']),
    );
  }

  return $dnstuote;
}

function hae_tuotekuvat($tuote_tunnus) {
  global $kukarow, $yhtiorow;

  $query = "SELECT *
            FROM liitetiedostot
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND liitos = 'tuote'
            AND liitostunnus = '{$tuote_tunnus}'";
  $result = pupe_query($query);
  $tuotekuvat = array();
  while ($tuotekuva = mysql_fetch_assoc($result)) {
    $tuotekuvat[] = $tuotekuva;
  }

  return $tuotekuvat;
}
