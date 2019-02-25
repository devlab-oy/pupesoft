<?php

// Kutsutaanko CLI:stä
$php_cli = FALSE;

// Tämä vaatii paljon muistia
ini_set("memory_limit", "5G");

if (php_sapi_name() == 'cli') {
  $php_cli = TRUE;
}

if ($php_cli) {

  if (!isset($argv[1]) or $argv[1] == '') {
    echo "Anna yhtiö!!!\n";
    die;
  }

  $inc_path = dirname(__FILE__).PATH_SEPARATOR."/usr/share/pear";
  ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.$inc_path);
  error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
  ini_set("display_errors", 0);

  // otetaan tietokanta connect
  require "inc/connect.inc";
  require "inc/functions.inc";

  // Logitetaan ajo
  cron_log();

  // Haetaan yhtiörow ja kukarow
  $yhtiorow = hae_yhtion_parametrit($argv[1]);
  $kukarow  = hae_kukarow('admin', $yhtiorow['yhtio']);

  $aja = 'run';
}
else {
  require "inc/parametrit.inc";

  echo "<font class='head'>", t("Iltasiivo"), "</font><hr>";

  echo "<br><form method='post'>";
  echo "<input type='hidden' name='aja' value='run'>";
  echo "<input type='submit' value='", t("Aja iltasiivo"), "'>";
  echo "</form>";

  if ($aja != "run") {
    require 'inc/footer.inc';
    exit;
  }

  echo "<pre>";
}

function is_log($str) {
  global $php_cli;

  $str = date("d.m.Y @ G:i:s") . ": $str\n";

  if ($php_cli) echo $str;

  return $str;
}

// Ei query debuggia, vie turhaa muistia.
unset($pupe_query_debug);

$iltasiivo = is_log("Iltasiivo $yhtiorow[nimi]");
$laskuri   = 0;

// poistetaan kaikki tuotteen_toimittajat liitokset joiden toimittaja on poistettu
$query = "SELECT toimi.tunnus, tuotteen_toimittajat.tunnus toimtunnus
          FROM tuotteen_toimittajat
          LEFT JOIN toimi on (toimi.yhtio = tuotteen_toimittajat.yhtio
            AND toimi.tunnus               = tuotteen_toimittajat.liitostunnus)
          WHERE tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
          HAVING toimi.tunnus is null";
$result = pupe_query($query);

while ($row = mysql_fetch_assoc($result)) {
  $query = "DELETE from tuotteen_toimittajat where tunnus = '$row[toimtunnus]'";
  pupe_query($query);
  $laskuri++;
}

if ($laskuri > 0) {
  $iltasiivo .= is_log("Poistettiin $laskuri poistetun toimittajan tuoteliitosta.");
}

$laskuri = 0;

// poistetaan kaikki tuotteen_toimittajat liitokset joiden tuote on poistettu
$query = "SELECT tuote.tunnus, tuotteen_toimittajat.tunnus toimtunnus
          FROM tuotteen_toimittajat
          LEFT JOIN tuote on (tuote.yhtio = tuotteen_toimittajat.yhtio
            AND tuote.tuoteno              = tuotteen_toimittajat.tuoteno)
          WHERE tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
          HAVING tuote.tunnus is null";
$result = pupe_query($query);

while ($row = mysql_fetch_assoc($result)) {
  $query = "DELETE from tuotteen_toimittajat where tunnus = '$row[toimtunnus]'";
  pupe_query($query);
  $laskuri++;
}

if ($laskuri > 0) {
  $iltasiivo .= is_log("Poistettiin $laskuri poistetun tuotteen tuoteliitosta.");
}

$laskuri = 0;
$laskuri2 = 0;

// poistetaan kaikki JT-otsikot jolla ei ole enää rivejä
// ja extranet tilaukset joilla ei ole rivejä ja tietenkin myös ennakkootsikot joilla ei ole rivejä.
$query = "SELECT tilausrivi.tunnus, lasku.tunnus laskutunnus, lasku.tila, lasku.tunnusnippu
          FROM lasku
          LEFT JOIN tilausrivi on (tilausrivi.yhtio = lasku.yhtio
            AND tilausrivi.otunnus  = lasku.tunnus)
          WHERE lasku.yhtio         = '$kukarow[yhtio]'
          AND lasku.tila            in ('N','E','L', 'G')
          AND lasku.alatila        != 'X'
          AND tilausrivi.tunnus is null";
$result = pupe_query($query);

while ($row = mysql_fetch_assoc($result)) {
  $komm = "({$kukarow['kuka']}@".date('Y-m-d').") ".t("Mitätöi ohjelmassa iltasiivo.php")." (1)<br>";

  // Jos kyseessä on tunnusnippupaketti
  // Halutaan säilyttää linkki tästä tehtyihin tilauksiin, tilaus merkataan vain toimitetuksi
  if ($row["tunnusnippu"] > 0) {
    $query = "UPDATE lasku SET
              tila        = 'L',
              alatila     = 'X'
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  = '$row[laskutunnus]'";
    pupe_query($query);
    $laskuri2++;
  }
  else {
    $query = "UPDATE lasku SET
              alatila     = '$row[tila]',
              tila        = 'D',
              comments    = '$komm'
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  = '$row[laskutunnus]'";
    pupe_query($query);
    $laskuri++;
  }

  //poistetaan TIETENKIN kukarow[kesken] ettei voi syöttää extranetissä rivejä tälle
  $query = "UPDATE kuka SET kesken = ''
            WHERE yhtio = '$kukarow[yhtio]'
            AND kesken  = '$row[laskutunnus]'";
  pupe_query($query);
}

if ($laskuri > 0) {
  $iltasiivo .= is_log("Poistettiin $laskuri rivitöntä tilausta.");
}

if ($laskuri2 > 0) {
  $iltasiivo .= is_log("Merkattiin toimitetuksi $laskuri2 rivitöntä tilausta.");
}

$laskuri = 0;

// Merkitään laskut mitätöidyksi joilla on pelkästään mitätöityjä rivejä / pelkästään puuterivejä.
$query = "SELECT lasku.tunnus laskutunnus,
          lasku.tila,
          count(*) kaikki,
          sum(if (tilausrivi.tyyppi='D' or tilausrivi.var='P', 1, 0)) dellatut,
          asiakas.kerayserat keraysera
          FROM lasku
          LEFT JOIN asiakas on asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus
          JOIN tilausrivi on tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
          WHERE lasku.yhtio  = '$kukarow[yhtio]'
          AND lasku.tila     in ('N','E','L','G')
          AND lasku.alatila != 'X'
          GROUP BY 1,2
          HAVING dellatut > 0 and kaikki = dellatut  and keraysera != 'H'";
$result = pupe_query($query);

while ($row = mysql_fetch_assoc($result)) {
  $komm = "({$kukarow['kuka']}@".date('Y-m-d').") ".t("Mitätöi ohjelmassa iltasiivo.php")." (2)<br>";

  $query = "UPDATE lasku set
            alatila     = '$row[tila]',
            tila        = 'D',
            comments    = '$komm'
            WHERE yhtio = '$kukarow[yhtio]'
            AND tunnus  = '$row[laskutunnus]'";
  pupe_query($query);
  $laskuri++;

  //poistetaan TIETENKIN kukarow[kesken] ettei voi syöttää extranetissä rivejä tälle
  $query = "UPDATE kuka set
            kesken      = ''
            WHERE yhtio = '$kukarow[yhtio]'
            AND kesken  = '$row[laskutunnus]'";
  pupe_query($query);
}

if ($laskuri > 0) {
  $iltasiivo .= is_log("Mitätöitiin $laskuri tilausta joilla oli pelkkiä mitätöityjä rivejä.");
}

$laskuri = 0;

// Merkitään rivit mitätöidyksi joiden otsikot on mitätöity
// ei mitätöidä puuterivejä, eikä suoraan saapumiseen lisättyjä ostorivejä lasku.alatila != 'K'
$query = "SELECT lasku.tunnus laskutunnus
          FROM lasku
          JOIN tilausrivi on (tilausrivi.yhtio = lasku.yhtio
            AND tilausrivi.otunnus  = lasku.tunnus
            AND tilausrivi.tyyppi  != 'D'
            AND tilausrivi.var     != 'P')
          WHERE lasku.yhtio         = '$kukarow[yhtio]'
          AND lasku.tila            = 'D'
          AND lasku.alatila        != 'K'
          GROUP BY 1";
$result = pupe_query($query);

while ($row = mysql_fetch_assoc($result)) {
  $komm = "({$kukarow['kuka']}@".date('Y-m-d').")".t("Mitätöi ohjelmassa iltasiivo.php")." (3)<br>";

  $query = "UPDATE tilausrivi SET
            tyyppi       = 'D'
            WHERE yhtio  = '$kukarow[yhtio]'
            AND otunnus  = '$row[laskutunnus]'
            AND var     != 'P'";
  pupe_query($query);
  $laskuri++;
}

if ($laskuri > 0) {
  $iltasiivo .= is_log("Mitätöitiin $laskuri mitätöidyn tilauksen rivit.");
}

$laskuri = 0;

// Arkistoidaan tulostetut ostotilaukset joilla ei ole yhtään tulossa olevaa kamaa
$query = "SELECT distinct lasku.tunnus laskutunnus
          FROM lasku
          LEFT JOIN tilausrivi on (tilausrivi.yhtio = lasku.yhtio
            AND tilausrivi.otunnus  = lasku.tunnus
            AND tilausrivi.tyyppi   = 'O'
            AND tilausrivi.varattu != 0)
          WHERE lasku.yhtio         = '$kukarow[yhtio]'
          AND lasku.tila            = 'O'
          AND lasku.alatila         = 'A'
          AND tilausrivi.tunnus is null";
$result = pupe_query($query);

while ($row = mysql_fetch_assoc($result)) {
  $query = "UPDATE lasku
            SET alatila = 'X'
            WHERE yhtio = '$kukarow[yhtio]'
            AND tunnus  = '$row[laskutunnus]'";
  pupe_query($query);
  $laskuri++;
}

if ($laskuri > 0) $iltasiivo .= is_log("Arkistoitiin $laskuri ostotilausta.");

$laskuri = 0;

// Vapautetaan holdissa olevat tilaukset, jos niillä on maksupositioita ja ennakkolaskut ovat maksettu
// Holdissa olevat tilaukset ovat tilassa N B
$query = "SELECT DISTINCT jaksotettu
          FROM lasku
          WHERE yhtio = '{$kukarow['yhtio']}'
          AND tila    = 'N'
          AND alatila = 'B'";
$pos_chk_result = pupe_query($query);

while ($pos_chk_row = mysql_fetch_assoc($pos_chk_result)) {
  vapauta_maksusopimus($pos_chk_row['jaksotettu']);
  $laskuri++;
}

if ($laskuri > 0) {
  $iltasiivo .= is_log("Vapautettiin {$laskuri} myyntitilausta tulostusjonoon.");
}

$laskuri = 0;

// Arkistoidaan saapumiset joilla ei ole yhtään liitettyä riviä eikä yhtään laskuja liitetty
$query = "SELECT distinct lasku.tunnus laskutunnus
          FROM lasku
          LEFT JOIN lasku liitosotsikko ON (liitosotsikko.yhtio = lasku.yhtio
            AND liitosotsikko.tila=lasku.tila
            AND liitosotsikko.laskunro    = lasku.laskunro
            AND liitosotsikko.vanhatunnus > 0)
          LEFT JOIN tilausrivi on (tilausrivi.yhtio = lasku.yhtio
            AND tilausrivi.uusiotunnus    = lasku.tunnus
            AND tilausrivi.tyyppi         = 'O')
          LEFT JOIN tilausrivi suoraan_keikalle on (suoraan_keikalle.yhtio = lasku.yhtio
            AND suoraan_keikalle.otunnus  = lasku.tunnus
            AND suoraan_keikalle.tyyppi   = 'O')
          WHERE lasku.yhtio               = '$kukarow[yhtio]'
          AND lasku.tila                  = 'K'
          AND lasku.mapvm                 = '0000-00-00'
          AND lasku.vanhatunnus           = 0
          AND tilausrivi.tunnus is null
          AND suoraan_keikalle.tunnus is null
          AND liitosotsikko.tunnus is null";
$result = pupe_query($query);

while ($row = mysql_fetch_assoc($result)) {
  $komm = "({$kukarow['kuka']}@".date('Y-m-d').")".t("Mitätöi ohjelmassa iltasiivo.php")."<br>";

  $query = "UPDATE lasku SET
            alatila     = tila,
            tila        = 'D',
            comments    = '$komm'
            WHERE yhtio = '$kukarow[yhtio]'
            AND tunnus  = '$row[laskutunnus]'";
  pupe_query($query);
  $laskuri++;
}

if ($laskuri > 0) {
  $iltasiivo .= is_log("Mitätöitiin $laskuri tyhjää saapumista.");
}

// tässä tehdään isittömistä perheistä ei-perheitä ja myös perheistä joissa ei ole lapsia eli nollataan perheid
$lask = 0;
$lask2 = 0;

$query = "SELECT perheid, count(*) koko
          FROM tilausrivi
          WHERE yhtio         = '$kukarow[yhtio]'
          AND tyyppi          = 'L'
          AND laskutettuaika  = '0000-00-00'
          AND perheid        != '0'
          GROUP BY perheid";
$result = pupe_query($query);

while ($row = mysql_fetch_assoc($result)) {
  $query = "SELECT perheid
            FROM tilausrivi
            WHERE yhtio        = '$kukarow[yhtio]'
            AND tyyppi         = 'L'
            AND laskutettuaika = '0000-00-00'
            AND tunnus         = '$row[perheid]'";
  $result2 = pupe_query($query);

  if (mysql_num_rows($result2) == 0) {
    $lask++;
    $query = "UPDATE tilausrivi SET perheid = 0
              WHERE yhtio        = '$kukarow[yhtio]'
              AND tyyppi         = 'L'
              AND laskutettuaika = '0000-00-00'
              AND perheid        = '$row[perheid]'
              ORDER BY tunnus";
    pupe_query($query);
  }
  else {
    if ($row['koko'] == 1) {
      $lask2++;
      $query = "UPDATE tilausrivi SET perheid = 0
                WHERE yhtio        = '$kukarow[yhtio]'
                AND tyyppi         = 'L'
                AND laskutettuaika = '0000-00-00'
                AND perheid        = '$row[perheid]'
                ORDER BY tunnus";
      pupe_query($query);
    }
  }
}

if ($lask > 0) {
  $iltasiivo .= is_log("Korjattiin $lask tilausriviä joissa tuoteperheen lapsituotteelta puuttui isätuote");
}

if ($lask2 > 0) {
  $iltasiivo .= is_log("Korjattiin $lask2 tilausriviä joissa tuoteperheen isätuotteella ei ollut lapsituotteita");
}

$lasktuote = 0;
$laskpois = 0;
$poistetaankpl = 0;

$query = "SELECT tuoteno, liitostunnus, count(tunnus) countti
          FROM tuotteen_toimittajat
          WHERE yhtio = '$kukarow[yhtio]'
          GROUP BY 1,2
          HAVING countti > 1";
$result = pupe_query($query);

while ($row = mysql_fetch_assoc($result)) {
  $lasktuote++;
  $poistetaankpl = $row['countti']-1;

  $poisquery = "DELETE FROM tuotteen_toimittajat
                WHERE yhtio      = '$kukarow[yhtio]'
                AND tuoteno      = '$row[tuoteno]'
                AND liitostunnus = '$row[liitostunnus]'
                ORDER BY tunnus DESC
                LIMIT $poistetaankpl";
  pupe_query($poisquery);
  $laskpois += mysql_affected_rows();
}

if ($lasktuote > 0) {
  $iltasiivo .= is_log("Poistettiin $lasktuote tuotteelta yhteensä $laskpois duplikaattia toimittajaa");
}

$kukaquery = "UPDATE kuka
              SET taso = '2'
              WHERE taso   = '3'
              AND extranet = ''";
pupe_query($kukaquery);

if (mysql_affected_rows() > 0) {
  $iltasiivo .= date("d.m.Y @ G:i:s").": Päivitettiin ".mysql_affected_rows()." käyttäjän taso 3 --> 2\n";
}

// mitätöidään keskenolevia extranet-tilauksia, jos ne on liian vanhoja ja yhtiön parametri on päällä
if ($yhtiorow['iltasiivo_mitatoi_ext_tilauksia'] != '') {

  $laskuri = 0;
  $aikaraja = (int) $yhtiorow['iltasiivo_mitatoi_ext_tilauksia'];

  $query = "SELECT lasku.tunnus laskutunnus
            FROM lasku
            JOIN kuka ON (kuka.yhtio = lasku.yhtio
                AND kuka.kuka       = lasku.laatija
                AND kuka.extranet  != '')
            WHERE lasku.yhtio       = '{$kukarow['yhtio']}'
            AND lasku.tila          = 'N'
            AND lasku.alatila       = ''
            AND lasku.tilaustyyppi != 'H'
            AND lasku.clearing      NOT IN ('EXTENNAKKO','EXTTARJOUS')
            AND lasku.luontiaika    < DATE_SUB(now(), INTERVAL $aikaraja HOUR)";
  $result = pupe_query($query);

  while ($row = mysql_fetch_assoc($result)) {
    // laitetaan kaikki poimitut extranet jt-rivit takaisin omille vanhoille tilauksille
    $query = "SELECT tilausrivi.tunnus, tilausrivin_lisatiedot.vanha_otunnus
              FROM tilausrivi
              JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
                AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus
                AND tilausrivin_lisatiedot.positio          = 'JT')
              WHERE tilausrivi.yhtio                        = '{$kukarow['yhtio']}'
              AND tilausrivi.otunnus                        = '{$row['laskutunnus']}'";
    $jt_rivien_muisti_res = pupe_query($query);

    if (mysql_num_rows($jt_rivien_muisti_res) > 0) {
      $jt_saldo_lisa = $yhtiorow["varaako_jt_saldoa"] == "" ? ", jt = varattu, varattu = 0 " : '';

      while ($jt_rivien_muisti_row = mysql_fetch_assoc($jt_rivien_muisti_res)) {
        $query = "UPDATE tilausrivi SET
                  otunnus     = '{$jt_rivien_muisti_row['vanha_otunnus']}',
                  var         = 'J'
                  $jt_saldo_lisa
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tunnus  = '{$jt_rivien_muisti_row['tunnus']}'";
        pupe_query($query);
      }
    }

    $komm = "({$kukarow['kuka']}@".date('Y-m-d').")".t("Mitätöi ohjelmassa iltasiivo.php")." (4)<br>";

    $query = "UPDATE lasku SET
              alatila     = 'N',
              tila        = 'D',
              comments    = '$komm'
              WHERE yhtio = '{$kukarow['yhtio']}'
              and tunnus  = '{$row['laskutunnus']}'";
    pupe_query($query);

    $query = "UPDATE tilausrivi SET
              tyyppi       = 'D'
              WHERE yhtio  = '{$kukarow['yhtio']}'
              AND otunnus  = '{$row['laskutunnus']}'
              and var     != 'P'";
    pupe_query($query);

    //poistetaan TIETENKIN kukarow[kesken] ettei voi syöttää extranetissä rivejä tälle
    $query = "UPDATE kuka SET
              kesken      = ''
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND kesken  = '{$row['laskutunnus']}'";
    pupe_query($query);

    $laskuri++;
  }

  if ($laskuri > 0) {
    $iltasiivo .= is_log("Mitätöitiin $laskuri extranet-tilausta, jotka olivat $aikaraja tuntia vanhoja.");
  }
}

if (table_exists('suorituskykyloki')) {
  $query = "DELETE FROM suorituskykyloki
            WHERE yhtio    = '{$kukarow['yhtio']}'
            AND luontiaika < date_sub(now(), INTERVAL 1 YEAR)";
  pupe_query($query);

  $laskuri = mysql_affected_rows();
  if ($laskuri > 0) $iltasiivo .= is_log("Poistettiin $laskuri riviä suorituskykylokista.");
}

// Poistetaan poistettujen käyttäjien oikeudet
$query = "DELETE oikeu
          FROM oikeu
          LEFT JOIN kuka ON (oikeu.yhtio = kuka.yhtio AND oikeu.kuka = kuka.kuka)
          WHERE oikeu.yhtio   = '{$kukarow['yhtio']}'
          AND oikeu.kuka     != ''
          AND oikeu.profiili  = ''
          AND kuka.tunnus is null";
pupe_query($query);
$del = mysql_affected_rows();

$iltasiivo .= is_log("Poistettiin $del poistettujen käyttäjien käyttöoikeuksia.");

// Dellataan rogue oikeudet
$query = "DELETE o1.*
          FROM oikeu o1
          LEFT JOIN oikeu o2 ON (o1.yhtio = o2.yhtio
            AND o1.sovellus  = o2.sovellus
            AND o1.nimi      = o2.nimi
            AND o1.alanimi   = o2.alanimi
            AND o2.kuka      = '')
          WHERE o1.yhtio     = '{$kukarow['yhtio']}'
          AND o1.kuka       != ''
          AND o2.tunnus is null";
pupe_query($query);
$del = mysql_affected_rows();

$iltasiivo .= is_log("Poistettiin $del poistettujen ohjelmien käyttöoikeuksia.");

// Merkataan myyntitilit valmiiksi, jos niillä ei ole yhtään käsittelemättömiä rivejä
$query = "SELECT lasku.tunnus,
          sum(if(tilausrivi.kpl != 0, 1, 0)) ei_valmis
          FROM lasku
          JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio
            AND tilausrivi.otunnus  = lasku.tunnus
            AND tilausrivi.tyyppi  != 'D')
          WHERE lasku.yhtio         = '$kukarow[yhtio]'
          AND lasku.tila            = 'G'
          AND lasku.tilaustyyppi    = 'M'
          AND lasku.alatila         = 'V'
          GROUP BY lasku.tunnus
          HAVING ei_valmis = 0";
$result = pupe_query($query);

$myyntitili = 0;

while ($laskurow = mysql_fetch_assoc($result)) {
  $query = "UPDATE lasku
            SET alatila = 'X'
            WHERE lasku.yhtio = '{$kukarow["yhtio"]}'
            AND lasku.tunnus  = '{$laskurow["tunnus"]}'";
  pupe_query($query);
  $myyntitili++;
}

if ($myyntitili > 0) {
  $iltasiivo .= is_log("Merkattiin $myyntitili myyntitiliä valmiiksi.");
}

// Poistetaan kaikki yhden tuotteen korvaavuusketjut
$query = "SELECT group_concat(tunnus) as tunnus, id
          FROM korvaavat
          WHERE yhtio='{$kukarow['yhtio']}'
          GROUP BY id
          HAVING count(id) = 1";
$result = pupe_query($query);

$laskuri = 0;

while ($row = mysql_fetch_assoc($result)) {
  $query = "DELETE FROM korvaavat WHERE tunnus='$row[tunnus]'";
  if ($delete_result = pupe_query($query)) {
    $laskuri++;
  }
}

if ($laskuri > 0) $iltasiivo .= is_log("Poistettiin $laskuri yhden tuotteen korvaavuusketjua.");
$laskuri = 0;

// Poistetaan kaikki yhden tuotteen vastaavuusketjut
$query = "SELECT group_concat(tunnus) as tunnus, id
          FROM vastaavat
          WHERE yhtio='{$kukarow['yhtio']}'
          GROUP BY id
          HAVING count(id) = 1";
$result = pupe_query($query);

$laskuri = 0;

while ($row = mysql_fetch_assoc($result)) {
  $query = "DELETE FROM vastaavat WHERE tunnus='$row[tunnus]'";
  if ($delete_result = pupe_query($query)) {
    $laskuri++;
  }
}

if ($laskuri > 0) {
  $iltasiivo .= is_log("Poistettiin $laskuri yhden tuotteen vastaavuusketjua.");
}

$laskuri = 0;

// Tsekataan jos joku valmistus tai valmistusmyynti on jäänyt alatila = K tilaan
$query = "SELECT distinct tunnus, tila
          FROM lasku
          WHERE yhtio = '$kukarow[yhtio]'
          and  tila   in ('L','V')
          and alatila = 'K'";
$result = pupe_query($query);

$valmkorj = 0;

while ($row = mysql_fetch_assoc($result)) {
  if ($row["tila"] == "L") {
    $kalatila = "X";
  }
  else {
    $kalatila = "V";
  }

  $query = "UPDATE lasku
            SET alatila  = '$kalatila'
            WHERE yhtio = '$kukarow[yhtio]'
            and tunnus  = '$row[tunnus]'
            and tila    = '$row[tila]'
            and alatila = 'K'";
  pupe_query($query);
  $valmkorj++;
}

if ($valmkorj > 0) {
  $iltasiivo .= is_log("Merkattiin $valmkorj valmistustilausta takaisin alkuperäisille alatiloille.");
}

$laskuri = 0;
// Poistetaan kaikki laitteen_sopimukset -rivit, joille ei löydy enää tilausriviä sopimuksilta
$query = "SELECT laitteen_sopimukset.tunnus
          FROM laitteen_sopimukset
	        LEFT JOIN tilausrivi ON (tilausrivi.yhtio = laitteen_sopimukset.yhtio
            AND tilausrivi.tunnus = laitteen_sopimukset.sopimusrivin_tunnus)
	        WHERE laitteen_sopimukset.yhtio = '$kukarow[yhtio]'
	        AND tilausrivi.tunnus IS NULL";
$result = pupe_query($query);

while ($row = mysql_fetch_assoc($result)) {
  $query = "DELETE FROM laitteen_sopimukset WHERE tunnus='$row[tunnus]'";
  if ($delete_result = pupe_query($query)) {
    $laskuri++;
  }
}

if ($laskuri > 0) {
  $iltasiivo .= is_log("Poistettiin $laskuri laitteen sopimusriviä (laitteen_sopimukset), joita ei löydy sopimuksilta.");
}

$laskuri = 0;
// Poistetaan kaikki myyntitili-varastopaikat, jos niiden saldo on nolla
$query = "SELECT tunnus, tuoteno
          FROM tuotepaikat
          WHERE tuotepaikat.yhtio   = '{$kukarow["yhtio"]}'
          AND tuotepaikat.hyllyalue = '!!M'
          AND tuotepaikat.oletus    = ''
          AND tuotepaikat.saldo     = 0";
$iltatuotepaikatresult = pupe_query($query);

$myyntitili = 0;

while ($iltatuotepaikatrow = mysql_fetch_assoc($iltatuotepaikatresult)) {
  $tee = "MUUTA";
  $tuoteno = $iltatuotepaikatrow["tuoteno"];
  $poista = array($iltatuotepaikatrow["tunnus"]);
  $halyraja2 = array();
  $tilausmaara2 = array();
  $kutsuja = "vastaanota.php";
  require "muuvarastopaikka.php";
  $myyntitili++;
}

if ($myyntitili > 0) {
  $iltasiivo .= is_log("Poistettiin $myyntitili tyhjää myyntitilin varastopaikkaa.");
}

// Poistetaan tuotepaikat joiden saldo on 0 ja ne on määritelty reservipaikoiksi
// Ei poisteta kuitenkaan jos se on oletuspaikka
if ($yhtiorow['kerayserat'] == 'K') {
  $poistettu = 0;

  $query = "SELECT tuotepaikat.tunnus
            FROM tuotepaikat
            JOIN varaston_hyllypaikat ON (varaston_hyllypaikat.yhtio = tuotepaikat.yhtio
              AND varaston_hyllypaikat.hyllyalue          = tuotepaikat.hyllyalue
              AND varaston_hyllypaikat.hyllynro           = tuotepaikat.hyllynro
              AND varaston_hyllypaikat.hyllyvali          = tuotepaikat.hyllyvali
              AND varaston_hyllypaikat.hyllytaso          = tuotepaikat.hyllytaso
              AND varaston_hyllypaikat.reservipaikka      = 'K')
            LEFT JOIN inventointilistarivi ON (inventointilistarivi.yhtio = tuotepaikat.yhtio
              AND inventointilistarivi.tuotepaikkatunnus  = tuotepaikat.tunnus
              AND inventointilistarivi.tila               = 'A')
            WHERE tuotepaikat.yhtio                       = '{$kukarow['yhtio']}'
            AND tuotepaikat.saldo                         = 0
            AND tuotepaikat.oletus                        = ''
            AND tuotepaikat.poistettava                  != 'D'
            AND inventointilistarivi.tunnus IS NULL";
  $tuotepaikat = pupe_query($query);

  // Poistetaan löydetyt rivit ja tehdään tapahtuma
  while ($tuotepaikkarow = mysql_fetch_assoc($tuotepaikat)) {
    // Merkataan paikka poistettavaksi
    $query = "UPDATE tuotepaikat
              SET poistettava = 'D'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$tuotepaikkarow['tunnus']}'
              AND saldo   = 0";
    pupe_query($query);
    $poistettu++;
  }

  if ($poistettu > 0) {
    $iltasiivo .= is_log("Merkattiin $poistettu reservi-tuotepaikkaa poistetuksi.");
  }
}

// Poistetaan tuotepaikat jotka ovat varaston ensimmäisellä paikalla (esim. A-0-0-0) ja joilla
// ei ole saldoa eikä hälytysrajaa. Koska nämä ovat yleensä generoituja paikkoja. (ei poisteta oletuspaikkaa)
if ($yhtiorow['kerayserat'] == 'K') {
  $poistettu = 0;

  $query = "SELECT tuotepaikat.tunnus
            FROM tuotepaikat
            JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
            AND varastopaikat.alkuhyllyalue  = tuotepaikat.hyllyalue
            AND varastopaikat.alkuhyllynro   = tuotepaikat.hyllynro)
            WHERE tuotepaikat.yhtio          = '{$kukarow['yhtio']}'
            AND tuotepaikat.saldo            = 0
            AND tuotepaikat.hyllytaso        = 0
            AND tuotepaikat.hyllyvali        = 0
            AND tuotepaikat.oletus           = ''
            AND tuotepaikat.halytysraja      = 0
            AND tuotepaikat.poistettava     != 'D'
            GROUP BY 1";
  $result = pupe_query($query);

  while ($poistettava_tuotepaikka = mysql_fetch_assoc($result)) {
    // Merkataan paikka poistettavaksi
    $query = "UPDATE tuotepaikat
              SET poistettava = 'D'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = {$poistettava_tuotepaikka['tunnus']}
              AND saldo   = 0";
    pupe_query($query);
    $poistettu++;
  }

  if ($poistettu > 0) {
    $iltasiivo .= is_log("Merkattiin $poistettu 'generoitua varaston alkupaikkaa' poistettavaksi.");
  }
}

/**
 * Poistetaan poistettavaksi merkatut tuotepaikat joilla ei ole saldoa
 * tuotepaikat.poistettava = 'D' ja tuotepaikat.saldo=0
 * Ei poisteta oletuspaikkaa
 */


$query = "SELECT tunnus,
          tuoteno,
          hyllyalue,
          hyllynro,
          hyllytaso,
          hyllyvali,
          LOWER(CONCAT(tuoteno, hyllyalue, hyllynro, hyllytaso, hyllyvali)) AS id
          FROM tuotepaikat
          WHERE yhtio     = '{$kukarow['yhtio']}'
          AND poistettava = 'D'
          AND saldo       = 0
          AND oletus      = ''";
$poistettavat_tuotepaikat = pupe_query($query);

$poistettu = 0;
$avoimet_rivit = array();

// Jos on poistettavia, haetaan avoimet
if (mysql_num_rows($poistettavat_tuotepaikat) > 0) {

  // Haetaan avoimet tilausrivit arrayseen (myynti & osto)
  $query = "SELECT LOWER(CONCAT(tuoteno, hyllyalue, hyllynro, hyllytaso, hyllyvali)) AS id
            FROM tilausrivi
            WHERE yhtio         = '{$kukarow['yhtio']}'
            AND laskutettuaika  = '0000-00-00'
            AND tyyppi          IN ('L','O')
            AND var            != 'P'";
  $avoinrivi_result = pupe_query($query);

  while ($avoinrivi = mysql_fetch_assoc($avoinrivi_result)) {
    $avoimet_rivit[] = $avoinrivi['id'];
  }

  // Haetaan avoimet tilausrivit arrayseen (valmistukset & siirtolistat)
  $query = "SELECT LOWER(CONCAT(tuoteno, hyllyalue, hyllynro, hyllytaso, hyllyvali)) AS id
            FROM tilausrivi
            WHERE yhtio         = '{$kukarow['yhtio']}'
            AND toimitettuaika  = '0000-00-00 00:00:00'
            AND tyyppi          IN ('V','W','M','G')
            AND var            != 'P'";
  $avoinrivi_result = pupe_query($query);

  while ($avoinrivi = mysql_fetch_assoc($avoinrivi_result)) {
    $avoimet_rivit[] = $avoinrivi['id'];
  }

  // Haetaan inventointilistalla olevat käsittelemättömät rivit
  $query = "SELECT LOWER(CONCAT(tuoteno, hyllyalue, hyllynro, hyllytaso, hyllyvali)) AS id
            FROM inventointilistarivi
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tila = 'A'
            AND aika IS NULL";
  $avoinrivi_result = pupe_query($query);

  while ($avoinrivi = mysql_fetch_assoc($avoinrivi_result)) {
    $avoimet_rivit[] = $avoinrivi['id'];
  }

  // Haetaan avoimet tilausrivit arrayseen (siirtolistojen kohdepaikka)
  $query = "SELECT LOWER(CONCAT(tilausrivi.tuoteno, tilausrivin_lisatiedot.kohde_hyllyalue, tilausrivin_lisatiedot.kohde_hyllynro, tilausrivin_lisatiedot.kohde_hyllytaso, tilausrivin_lisatiedot.kohde_hyllyvali)) AS id
            FROM tilausrivi
            JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus AND tilausrivin_lisatiedot.kohde_hyllyalue != '')
            WHERE tilausrivi.yhtio         = '{$kukarow['yhtio']}'
            AND tilausrivi.toimitettuaika  = '0000-00-00 00:00:00'
            AND tilausrivi.tyyppi          = 'G'
            AND var                       != 'P'";
  $avoinrivi_result = pupe_query($query);

  while ($avoinrivi = mysql_fetch_assoc($avoinrivi_result)) {
    $avoimet_rivit[] = $avoinrivi['id'];
  }
}

// Loopataan poistettavat tuotepaikat läpi
while ($tuotepaikka = mysql_fetch_assoc($poistettavat_tuotepaikat)) {

  // Ei poisteta jos avoimia
  if (in_array($tuotepaikka['id'], $avoimet_rivit)) {
    continue;
  }

  // Poistetaan tuotepaikka
  $query = "DELETE FROM tuotepaikat
            WHERE yhtio     = '{$kukarow['yhtio']}'
            AND poistettava = 'D'
            AND saldo       = 0
            AND tunnus      = {$tuotepaikka['tunnus']}";
  pupe_query($query);
  $poistettu++;

  $selite = t("Poistettiin tuotepaikka");
  $selite .= " {$tuotepaikka["hyllyalue"]}";
  $selite .= " {$tuotepaikka["hyllynro"]}";
  $selite .= " {$tuotepaikka["hyllyvali"]}";
  $selite .= " {$tuotepaikka["hyllytaso"]}";

  // Luodaan tapahtuma
  $tapahtuma_query = "INSERT INTO tapahtuma SET
                      yhtio     = '$kukarow[yhtio]',
                      tuoteno   = '$tuotepaikka[tuoteno]',
                      kpl       = '0',
                      kplhinta  = '0',
                      hinta     = '0',
                      hyllyalue = '$tuotepaikka[hyllyalue]',
                      hyllynro  = '$tuotepaikka[hyllynro]',
                      hyllyvali = '$tuotepaikka[hyllyvali]',
                      hyllytaso = '$tuotepaikka[hyllytaso]',
                      laji      = 'poistettupaikka',
                      selite    = '$selite',
                      laatija   = '$kukarow[kuka]',
                      laadittu  = now()";
  pupe_query($tapahtuma_query);
}

if ($poistettu > 0) {
  $iltasiivo .= is_log("Poistettiin $poistettu poistettavaksi merkattua tuotepaikkaa.");
}

if ($php_cli) {

  $argv[2] = 'CLI_TUOTTEETTOMAT';

  require "varastopaikkojen_siivous.php";

  if ($poistettu > 0) {
    $iltasiivo .= is_log("Poistettiin $poistettu tuotepaikkaa jonka tuotetta ei enää ole.");
  }
}

// Tarkistetaan milloin pankkiyhteyden sertifikaatti menee vanhaksi
// ja mikäli se on menossa seuraavan kuukauden sisällä vanhaksi niin lähetetään muistutusmaili.
// Sen jälkeen kun sertifikaatti on mennyt vanhaksi niin ei enää tätä meiliä lähetetä.
$pankkiyhteydet = hae_pankkiyhteydet();

foreach ($pankkiyhteydet as $pankkiyhteys) {

  $tanaan = date("Ymd");

  $kolmekymmentapaivaa_ennen = date("Ymd", strtotime("-30 days", strtotime($pankkiyhteys["signing_certificate_valid_to"])));

  $serti_vanhenemispaiva = date("d.m.Y", strtotime($pankkiyhteys["signing_certificate_valid_to"]));
  $serti_vanhenemispaiva_vertailuun = date("Ymd", strtotime($pankkiyhteys["signing_certificate_valid_to"]));

  if ($tanaan >= $kolmekymmentapaivaa_ennen and $tanaan <= $serti_vanhenemispaiva_vertailuun) {
    // Rakennetaan sähköpostiin lähetettävä muistutusviesti
    $sepayhteysmuistutus = t("SEPA-yheyden sertifikaatti vanhenemassa %s pankista", "", $pankkiyhteys["pankin_nimi"])."!\n\n";
    $sepayhteysmuistutus .= t("Sertifikaatti vanhenee").": {$serti_vanhenemispaiva} \n\n";
    $sepayhteysmuistutus .= t("Sertifikaatti on uusittava ennenkuin se vanhenee. Uusiminen tapahtuu Kirjapito -> Ylläpito -> Pankkiyhteydet-ohjelman kautta.")."\n\n";

    // Laitetaan sähköposti admin osoitteeseen siinä tapauksessa,
    // jos talhal tai alert email osoitteita ei ole kumpaakaan setattu
    $error_email = $yhtiorow["admin_email"];

    if (isset($yhtiorow["talhal_email"]) and $yhtiorow["talhal_email"] != "") {
      $error_email = $yhtiorow["talhal_email"];
    }
    elseif (isset($yhtiorow["alert_email"]) and $yhtiorow["alert_email"] != "") {
      $error_email = $yhtiorow["alert_email"];
    }

    $params = array(
      "to"      => $error_email,
      "subject" => t("SEPA-yheyden sertifikaatti vanhenemassa %s pankista", "", $pankkiyhteys["pankin_nimi"])."!",
      "ctype"   => "text",
      "body"    => $sepayhteysmuistutus
    );

      pupesoft_sahkoposti($params);
  }
}

/**
 * Synkataan uusimmat mysqlaliakset
 */
require "synkronoi_mysqlaliakset.php";

$iltasiivo .= is_log("Iltasiivo $yhtiorow[nimi]. Done!");

if ($iltasiivo != "" and isset($iltasiivo_email) and $iltasiivo_email == 1) {
  $params = array(
    "to" => $yhtiorow["admin_email"],
    "subject" => "Iltasiivo yhtiölle '{$yhtiorow["yhtio"]}'",
    "ctype" => "text",
    "body"=> $iltasiivo
  );

  pupesoft_sahkoposti($params);
}

if (!$php_cli) {
  echo $iltasiivo;
  echo "</pre>";
  require 'inc/footer.inc';
}
