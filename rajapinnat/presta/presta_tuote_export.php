<?php

// Kutsutaanko CLI:stä
$php_cli = FALSE;

if (php_sapi_name() == 'cli') {
  $php_cli = TRUE;
}

date_default_timezone_set('Europe/Helsinki');

// Kutsutaanko CLI:stä
if (!$php_cli) {
  die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
}

$pupe_root_polku=dirname(dirname(dirname(__FILE__)));
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.$pupe_root_polku.PATH_SEPARATOR."/usr/share/pear");
error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
ini_set("display_errors", 0);

require "inc/connect.inc";
require "inc/functions.inc";

$lock_params = array(
  "locktime" => 5400
);

// Logitetaan ajo
cron_log();

// Sallitaan vain yksi instanssi tästä skriptistä kerrallaan
pupesoft_flock($lock_params);

require_once "{$pupe_root_polku}/rajapinnat/presta/presta_products.php";
require_once "{$pupe_root_polku}/rajapinnat/presta/presta_categories.php";
require_once "{$pupe_root_polku}/rajapinnat/presta/presta_customers.php";
require_once "{$pupe_root_polku}/rajapinnat/presta/presta_customer_groups.php";
require_once "{$pupe_root_polku}/rajapinnat/presta/presta_sales_orders.php";
require_once "{$pupe_root_polku}/rajapinnat/presta/presta_product_stocks.php";
require_once "{$pupe_root_polku}/rajapinnat/presta/presta_shops.php";
require_once "{$pupe_root_polku}/rajapinnat/presta/presta_specific_prices.php";

// Laitetaan unlimited execution time
ini_set("max_execution_time", 0);

if (trim($argv[1]) != '') {
  $yhtio = mysql_real_escape_string($argv[1]);
  $yhtiorow = hae_yhtion_parametrit($yhtio);
  $kukarow = hae_kukarow('admin', $yhtio);

  if ($kukarow === null) {
    die ("\n");
  }
}
else {
  die ("Et antanut yhtiötä.\n");
}

$verkkokauppatyyppi = isset($argv[2]) ? trim($argv[2]) : "";

if ($verkkokauppatyyppi != "presta") {
  die ("Et antanut verkkokaupan tyyppiä.\n");
}

$ajetaanko_kaikki = (isset($argv[3]) and trim($argv[3]) != '') ? "YES" : "NO";
if (!isset($verkkokauppa_saldo_varasto)) $verkkokauppa_saldo_varasto = array();

if (!is_array($verkkokauppa_saldo_varasto)) {
  echo "verkkokauppa_saldo_varasto pitää olla array!";
  exit;
}

// Haetaan timestamp
$datetime_checkpoint_res = t_avainsana("TUOTE_EXP_CRON");

if (mysql_num_rows($datetime_checkpoint_res) != 1) {
  exit("VIRHE: Timestamp ei löydy avainsanoista!\n");
}

$datetime_checkpoint_row = mysql_fetch_assoc($datetime_checkpoint_res);
$datetime_checkpoint = $datetime_checkpoint_row['selite']; // Mikä tilanne on jo käsitelty
$datetime_checkpoint_uusi = date('Y-m-d H:i:s'); // Timestamp nyt

// alustetaan arrayt
$dnstuote = $dnsryhma = $dnstuoteryhma = $dnstock = $dnsasiakas = $dnshinnasto = $dnslajitelma =
  $kaikki_tuotteet = $individual_tuotteet = array();

if ($ajetaanko_kaikki == "NO") {
  $muutoslisa = "AND (tuote.muutospvm >= '{$datetime_checkpoint}'
            OR ta_nimitys_se.muutospvm >= '{$datetime_checkpoint}'
            OR ta_nimitys_en.muutospvm >= '{$datetime_checkpoint}'
            )";
}
else {
  $muutoslisa = "";
}

echo date("d.m.Y @ G:i:s")." - Aloitetaan tuote-export.\n";
echo date("d.m.Y @ G:i:s")." - Haetaan tuotetiedot.\n";

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
    $myyntihinta         = $row["myyntihinta"];
    $myyntihinta_veroton   = $row["myyntihinta"];
  }
  else {
    $myyntihinta           = $row["myyntihinta"];
    $myyntihinta_veroton   = hintapyoristys($row["myyntihinta"] / (1+($row["alv"]/100)));
  }

  $myymalahinta          = $row["myymalahinta"];
  $myymalahinta_veroton  = hintapyoristys($row["myymalahinta"] / (1+($row["alv"]/100)));

  $asiakashinnat = array ();
  if (isset($tuotteiden_asiakashinnat_verkkokauppaan)) {

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
  // Nollataan tämä jos query lyö tyhjää, muuten vanhentunut tarjoushinta ei ylikirjoitu
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

echo date("d.m.Y @ G:i:s")." - Aloitetaan päivitys verkkokauppaan.\n";

if (isset($verkkokauppatyyppi) and $verkkokauppatyyppi == "presta") {
  $presta_products = new PrestaProducts($presta_url, $presta_api_key);
  $ok = $presta_products->sync_products($dnstuote);
}
else {
  die ("Ei näin");
}

// Otetaan tietokantayhteys uudestaan (voi olla timeoutannu)
unset($link);
$link = mysql_connect($dbhost, $dbuser, $dbpass, true) or die ("Ongelma tietokantapalvelimessa $dbhost (tuote_export)");
mysql_select_db($dbkanta, $link) or die ("Tietokantaa $dbkanta ei löydy palvelimelta $dbhost! (tuote_export)");
mysql_set_charset("latin1", $link);
mysql_query("set group_concat_max_len=1000000", $link);

// Kun kaikki onnistui, päivitetään lopuksi timestamppi talteen
$query = "UPDATE avainsana SET
          selite      = '{$datetime_checkpoint_uusi}'
          WHERE yhtio = '{$kukarow['yhtio']}'
          AND laji    = 'TUOTE_EXP_CRON'";
pupe_query($query);

if (mysql_affected_rows() != 1) {
  echo "Timestamp päivitys epäonnistui!\n";
}
