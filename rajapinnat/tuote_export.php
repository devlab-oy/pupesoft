<?php

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die("Tätä scriptiä voi ajaa vain komentoriviltä!");
}

$pupe_root_polku = dirname(dirname(__FILE__));

ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.$pupe_root_polku);
ini_set("display_errors", 1);
ini_set("max_execution_time", 0); // unlimited execution time
ini_set("memory_limit", "2G");
error_reporting(E_ALL);
date_default_timezone_set('Europe/Helsinki');

require "inc/connect.inc";
require "inc/functions.inc";
require "rajapinnat/magento_client.php";
require "rajapinnat/tuote_export_functions.php";

if (empty($argv[1])) {
  die ("Et antanut yhtiötä.\n");
}

// ensimmäinen parametri yhtiö
$yhtio = mysql_real_escape_string($argv[1]);
$yhtiorow = hae_yhtion_parametrit($yhtio);

if (empty($yhtiorow)) {
  die("Yhtiö ei löydy.");
}

$kukarow = hae_kukarow('admin', $yhtio);

if (empty($kukarow)) {
  die("Admin -käyttäjä ei löydy.");
}

$verkkokauppatyyppi = isset($argv[2]) ? trim($argv[2]) : "";

if ($verkkokauppatyyppi != "magento" and $verkkokauppatyyppi != "anvia") {
  die("Et antanut verkkokaupan tyyppiä.\n");
}

if ($verkkokauppatyyppi == "magento") {
  // Varmistetaan, että kaikki muuttujat on kunnossa
  if (empty($magento_api_te_url) or empty($magento_api_te_usr) or empty($magento_api_te_pas) or empty($magento_tax_class_id)) {
    die("Magento parametrit puuttuu, päivitystä ei voida ajaa.");
  }
}

$ajetaanko_kaikki = empty($argv[3]) ? "NO" : "YES";

if (empty($verkkokauppa_saldo_varasto)) {
  $verkkokauppa_saldo_varasto = array();
}

if (!is_array($verkkokauppa_saldo_varasto)) {
  die("verkkokauppa_saldo_varasto pitää olla array!");
  exit;
}

// Haetaan timestamp
$datetime_checkpoint_res = t_avainsana("TUOTE_EXP_CRON");

if (mysql_num_rows($datetime_checkpoint_res) != 1) {
  die("VIRHE: Timestamp ei löydy avainsanoista!\n");
}

if (empty($magento_ajolista)) {
  $magento_ajolista = array(
    'tuotteet',
    'lajitelmatuotteet',
    'tuoteryhmat',
    'asiakkaat',
    'hinnastot',
    'saldot'
  );
}

$datetime_checkpoint_row = mysql_fetch_assoc($datetime_checkpoint_res);
$datetime_checkpoint = $datetime_checkpoint_row['selite']; // Mikä tilanne on jo käsitelty
$datetime_checkpoint_uusi = date('Y-m-d H:i:s'); // Timestamp nyt

// alustetaan arrayt
$dnsasiakas = array();
$dnshinnasto = array();
$dnslajitelma = array();
$dnsryhma = array();
$dnstuoteryhma = array();

$lock_params = array(
  "locktime" => 5400
);

// Logitetaan ajo
cron_log();

// Sallitaan vain yksi instanssi tästä skriptistä kerrallaan
pupesoft_flock($lock_params);

echo date("d.m.Y @ G:i:s")." - Aloitetaan tuote-export.\n";

if (in_array('tuotteet', $magento_ajolista)) {
  echo date("d.m.Y @ G:i:s")." - Haetaan tuotetiedot.\n";

  $params = array(
    "ajetaanko_kaikki"                     => $ajetaanko_kaikki,
    "datetime_checkpoint"                  => $datetime_checkpoint,
    "magento_asiakaskohtaiset_tuotehinnat" => $magento_asiakaskohtaiset_tuotehinnat,
    "tuotteiden_asiakashinnat_magentoon"   => $tuotteiden_asiakashinnat_magentoon,
    "verkkokauppatyyppi"                   => $verkkokauppatyyppi,
  );

  $dnstuote = tuote_export_hae_tuotetiedot($params);

  // Magentoa varten pitää hakea kaikki tuotteet, jotta voidaan poistaa ne jota ei ole olemassa
  if ($verkkokauppatyyppi == 'magento') {
    echo date("d.m.Y @ G:i:s")." - Haetaan poistettavat tuotteet.\n";

    $response = tuote_export_hae_poistettavat_tuotteet();
    $kaikki_tuotteet     = $response['kaikki'];
    $individual_tuotteet = $response['individual'];
  }
}

if (in_array('saldot', $magento_ajolista)) {
  echo date("d.m.Y @ G:i:s")." - Haetaan saldot.\n";

  $params = array(
    "ajetaanko_kaikki"           => $ajetaanko_kaikki,
    "datetime_checkpoint"        => $datetime_checkpoint,
    "verkkokauppa_saldo_varasto" => $verkkokauppa_saldo_varasto,
  );

  $dnstock = tuote_export_hae_saldot($params);
}

if (in_array('tuoteryhmat', $magento_ajolista)) {
  echo date("d.m.Y @ G:i:s")." - Haetaan osastot/tuoteryhmät.\n";

  $params = array(
    "ajetaanko_kaikki"    => $ajetaanko_kaikki,
    "datetime_checkpoint" => $datetime_checkpoint,
  );

  $response = tuote_export_hae_tuoteryhmat($params);
  $dnsryhma      = $response['dnsryhma'];
  $dnstuoteryhma = $response['dnstuoteryhma'];
}

if (in_array('asiakkaat', $magento_ajolista)) {

  echo date("d.m.Y @ G:i:s")." - Haetaan asiakkaat.\n";

  if ($ajetaanko_kaikki == "NO") {
    $muutoslisa = "AND asiakas.muutospvm >= '{$datetime_checkpoint}'";
  }
  else {
    $muutoslisa = "";
  }

  $asiakasselectlisa = $asiakasjoinilisa = $asiakaswherelisa = "";

  if (isset($magento_siirretaan_asiakkaat)) {
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
      $yhenk_sukunimi = end(explode(' ', $row['yhenk_nimi']));
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
}

if (in_array('hinnastot', $magento_ajolista)) {

  echo date("d.m.Y @ G:i:s")." - Haetaan hinnastot.\n";

  if ($ajetaanko_kaikki == "NO") {
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
        $hinta          = hintapyoristys($row["hinta"] * (1+($row["alv"]/100)));
      }
      else {
        $hinta          = $row["hinta"];
      }
      $hinta_veroton    = $row["hinta"];
    }
    else {
      $hinta            = $row["hinta"];
      $hinta_veroton    = hintapyoristys($row["hinta"] / (1+($row["alv"]/100)));
    }

    $dnshinnasto[] = array(  'tuoteno'        => $row["tuoteno"],
      'selite'        => $row["selite"],
      'alkupvm'        => $row["alkupvm"],
      'loppupvm'        => $row["loppupvm"],
      'hinta'          => $hinta,
      'hinta_veroton'      => $hinta_veroton,
    );
  }
}

if (in_array('lajitelmatuotteet', $magento_ajolista)) {

  echo date("d.m.Y @ G:i:s")." - Haetaan tuotteiden variaatiot.\n";

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

  if ($ajetaanko_kaikki == "NO") {
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
          "nimi" => $syvinrow["selitetark"],
          "option_name" => $syvinrow["option_name"],
          "arvo" => $syvinrow["selite"]
        );
      }

      // Jos yhtiön hinnat eivät sisällä alv:tä
      if ($yhtiorow["alv_kasittely"] != "") {

        // Anviassa myyntihintaan verot päälle
        if ($verkkokauppatyyppi == 'anvia') {
          $myyntihinta         = hintapyoristys($alirow["myyntihinta"] * (1+($alirow["alv"]/100)));
        }
        else {
          $myyntihinta         = $alirow["myyntihinta"];
        }
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
}

$tuote_export_error_count = 0;

echo date("d.m.Y @ G:i:s")." - Aloitetaan päivitys verkkokauppaan.\n";

if (isset($verkkokauppatyyppi) and $verkkokauppatyyppi == "magento") {

  $time_start = microtime(true);

  $magento_client = new MagentoClient($magento_api_te_url, $magento_api_te_usr, $magento_api_te_pas);

  if ($magento_client->getErrorCount() > 0) {
    exit;
  }

  // tax_class_id, magenton API ei anna hakea tätä mistään. Pitää käydä katsomassa magentosta
  $magento_client->setTaxClassID($magento_tax_class_id);

  // Verkkokaupan "root" kategorian tunnus, magenton API ei anna hakea tätä mistään. Pitää käydä katsomassa magentosta
  if (isset($magento_parent_id)) $magento_client->setParentID($magento_parent_id);

  // Verkkokaupanhintakenttä, joko myyntihinta tai myymalahinta
  if (isset($magento_hintakentta)) $magento_client->setHintakentta($magento_hintakentta);

  // Käytetäänkö tuoteryhminä tuoteryhmiä(default) vai tuotepuuta
  if (isset($magento_kategoriat)) $magento_client->setKategoriat($magento_kategoriat);

  // Onko "Category access control"-moduli on asennettu
  if (isset($categoryaccesscontrol)) $magento_client->setCategoryaccesscontrol($categoryaccesscontrol);

  // Mitä tuotteen kenttää käytetään configurable-tuotteen nimityksenä
  if (isset($magento_configurable_tuote_nimityskentta) and !empty($magento_configurable_tuote_nimityskentta)) {
    $magento_client->setConfigurableNimityskentta($magento_configurable_tuote_nimityskentta);
  }

  // Miten configurable-tuotteen lapsituotteet näkyvät verkkokaupassa.
  // Vaihtoehdot: NOT_VISIBLE_INDIVIDUALLY, CATALOG, SEARCH, CATALOG_SEARCH
  // Default on NOT_VISIBLE_INDIVIDUALLY
  if (isset($magento_configurable_lapsituote_nakyvyys) and !empty($magento_configurable_lapsituote_nakyvyys)) {
    $magento_configurable_lapsituote_nakyvyys = strtoupper($magento_configurable_lapsituote_nakyvyys);
    $magento_client->setConfigurableLapsituoteNakyvyys($magento_configurable_lapsituote_nakyvyys);
  }

  // Asetetaan custom simple-tuotekentät jotka eivät tule dynaamisista parametreistä. Array joka sisältää jokaiselle erikoisparametrille
  // array ('nimi' =>'magento_parametrin_nimi', 'arvo' = 'tuotteen_kentän_nimi_mistä_arvo_halutaan') esim. array ('nimi' => 'manufacturer', 'arvo' => 'tuotemerkki')
  if (isset($verkkokauppatuotteet_erikoisparametrit) and count($verkkokauppatuotteet_erikoisparametrit) > 0) {
    $magento_client->setVerkkokauppatuotteetErikoisparametrit($verkkokauppatuotteet_erikoisparametrit);
  }
  // Asetetaan custom asiakaskentät. Array joka sisältää jokaiselle erikoisparametrille
  // array ('nimi' =>'magento_parametrin_nimi', 'arvo' = 'asiakkaan_kentän_nimi_mistä arvo_halutaan') esim. array ('nimi' => 'lastname', 'arvo' => 'yhenk_sukunimi')
  // näillä arvoilla ylikirjoitetaan asiakkaan tiedot sekä laskutus/toimitusosoitetiedot
  if (isset($asiakkaat_erikoisparametrit) and count($asiakkaat_erikoisparametrit) > 0) {
    $magento_client->setAsiakkaatErikoisparametrit($asiakkaat_erikoisparametrit);
  }
  // Magentossa käsin hallitut kategoriat jotka säilytetään aina tuotepäivityksessä
  if (isset($magento_sticky_kategoriat) and count($magento_sticky_kategoriat) > 0) {
    $magento_client->setStickyKategoriat($magento_sticky_kategoriat);
  }
  // Halutaanko estää tilausten tuplasisäänluku, eli jos tilaushistoriasta löytyy käsittely
  // 'processing_pupesoft'-tilassa niin tilausta ei lueta sisään jos sisäänluvun esto on päällä
  // Default on: YES
  if (isset($magento_sisaanluvun_esto) and !empty($magento_sisaanluvun_esto)) {
    $magento_client->setSisaanluvunEsto($magento_sisaanluvun_esto);
  }

  // Halutaanko merkata kaikki uudet tuotteet aina samaan tuoteryhmään ja
  // estää tuoteryhmän yliajo tuotepäivityksessä
  if (isset($magento_universal_tuoteryhma) and !empty($magento_universal_tuoteryhma)) {
    $magento_client->setUniversalTuoteryhma($magento_universal_tuoteryhma);
  }

  // Aktivoidaanko asiakas luonnin yhteydessä Magentoon
  //   HUOM! Vaatii Magenton customointia
  if (isset($magento_asiakas_aktivointi) and !empty($magento_asiakas_aktivointi)) {
    $magento_client->setAsiakasAktivointi($magento_asiakas_aktivointi);
  }

  // Aktivoidaanko asiakaskohtaiset tuotehinnat
  //   HUOM! Vaatii Magenton customointia
  if (isset($magento_asiakaskohtaiset_tuotehinnat) and !empty($magento_asiakaskohtaiset_tuotehinnat)) {
    $magento_client->setAsiakaskohtaisetTuotehinnat($magento_asiakaskohtaiset_tuotehinnat);
  }

  // Poistetaanko/yliajetaanko Magenton default-tuoteparametrejä
  if (isset($magento_poista_defaultit) and !empty($magento_poista_defaultit)) {
    $magento_client->setPoistaDefaultTuoteparametrit($magento_poista_defaultit);
  }

  // Poistetaanko/yliajetaanko Magenton default-asiakasparametrejä
  if (isset($magento_poista_asiakasdefaultit) and !empty($magento_poista_asiakasdefaultit)) {
    $magento_client->setPoistaDefaultAsiakasparametrit($magento_poista_asiakasdefaultit);
  }

  // Tuoteparametrit, joita käytetään url_key:nä. url_key generoidaan tuotteen nimityksestä
  // sekä annetuista parametreistä ja niiden arvoista.
  //
  // $magento_url_key_attributes = array('vari', 'koko');
  // => "T-PAITA-vari-BLACK-koko-XL"
  if (isset($magento_url_key_attributes) and !empty($magento_url_key_attributes)) {
    $magento_client->setUrlKeyAttributes($magento_url_key_attributes);
  }

  // lisaa_kategoriat
  if (count($dnstuoteryhma) > 0) {
    echo date("d.m.Y @ G:i:s")." - Päivitetään tuotekategoriat\n";
    $count = $magento_client->lisaa_kategoriat($dnstuoteryhma);
    echo date("d.m.Y @ G:i:s")." - Päivitettiin $count kategoriaa\n";
  }

  // Päivitetaan magento-asiakkaat ja osoitetiedot kauppaan
  if (count($dnsasiakas) > 0 and isset($magento_siirretaan_asiakkaat)) {
    echo date("d.m.Y @ G:i:s")." - Päivitetään asiakkaat\n";
    $count = $magento_client->lisaa_asiakkaat($dnsasiakas);
    echo date("d.m.Y @ G:i:s")." - Päivitettiin $count asiakkaan tiedot\n";
  }

  // Tuotteet (Simple)
  if (count($dnstuote) > 0) {
    echo date("d.m.Y @ G:i:s")." - Päivitetään simple tuotteet\n";
    $count = $magento_client->lisaa_simple_tuotteet($dnstuote, $individual_tuotteet);
    echo date("d.m.Y @ G:i:s")." - Päivitettiin $count tuotetta (simple)\n";
  }

  // Tuotteet (Configurable)
  if (count($dnslajitelma) > 0) {
    echo date("d.m.Y @ G:i:s")." - Päivitetään configurable tuotteet\n";
    $count = $magento_client->lisaa_configurable_tuotteet($dnslajitelma);
    echo date("d.m.Y @ G:i:s")." - Päivitettiin $count tuotetta (configurable)\n";
  }

  // Saldot
  if (count($dnstock) > 0) {
    echo date("d.m.Y @ G:i:s")." - Päivitetään tuotteiden saldot\n";
    $count = $magento_client->paivita_saldot($dnstock);
    echo date("d.m.Y @ G:i:s")." - Päivitettiin $count tuotteen saldot\n";
  }

  // Poistetaan tuotteet jota ei ole kaupassa
  if (count($kaikki_tuotteet) > 0 and !isset($magento_esta_tuotepoistot)) {
    echo date("d.m.Y @ G:i:s")." - Poistetaan ylimääräiset tuotteet\n";
    // HUOM, tähän passataan **KAIKKI** verkkokauppatuotteet, methodi katsoo että kaikki nämä on kaupassa, muut paitsi gifcard-tuotteet dellataan!
    $count = $magento_client->poista_poistetut($kaikki_tuotteet, true);
    echo date("d.m.Y @ G:i:s")." - Poistettiin $count tuotetta\n";
  }

  $tuote_export_error_count = $magento_client->getErrorCount();

  if ($tuote_export_error_count != 0) {
    echo date("d.m.Y @ G:i:s")." - Päivityksessä tapahtui {$tuote_export_error_count} virhettä!\n";
  }

  $time_end = microtime(true);
  $time = round($time_end - $time_start);

  echo date("d.m.Y @ G:i:s")." - Tuote-export valmis! (Magento API {$time} sekuntia)\n";
}
elseif (isset($verkkokauppatyyppi) and $verkkokauppatyyppi == "anvia") {

  if (isset($anvia_ftphost, $anvia_ftpuser, $anvia_ftppass, $anvia_ftppath)) {
    $ftphost = $anvia_ftphost;
    $ftpuser = $anvia_ftpuser;
    $ftppass = $anvia_ftppass;
    $ftppath = $anvia_ftppath;
  }
  else {
    $ftphost = "";
    $ftpuser = "";
    $ftppass = "";
    $ftppath = "";
  }

  $tulos_ulos = "";

  if (count($dnstuote) > 0) {
    require "{$pupe_root_polku}/rajapinnat/tuotexml.inc";
  }

  if (count($dnstock) > 0) {
    require "{$pupe_root_polku}/rajapinnat/varastoxml.inc";
  }

  if (count($dnsryhma) > 0) {
    require "{$pupe_root_polku}/rajapinnat/ryhmaxml.inc";
  }

  if (count($dnsasiakas) > 0) {
    require "{$pupe_root_polku}/rajapinnat/asiakasxml.inc";
  }

  if (count($dnshinnasto) > 0) {
    require "{$pupe_root_polku}/rajapinnat/hinnastoxml.inc";
  }

  if (count($dnslajitelma) > 0) {
    require "{$pupe_root_polku}/rajapinnat/lajitelmaxml.inc";
  }
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
