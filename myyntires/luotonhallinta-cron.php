<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
}

require "../inc/connect.inc";
require "../inc/functions.inc";

// Tarvitaan 3 parametria
// 1 = Yhtio
// 2 = Luottorajaprosentti
// 3 = Sähkopostiosoite

if (!isset($argv[1]) or $argv[1] == '') {
  echo "Anna yhtiö!!!\n";
  die;
}

if (!isset($argv[2]) or $argv[2] == '') {
  echo "Anna luottorajaprosentti!!!\n";
  die;
}

if (!isset($argv[3]) or $argv[3] == '') {
  echo "Anna sähkopostiosoite!!!\n";
  die;
}

// Otetaan parametrit
$yhtiorow = hae_yhtion_parametrit($argv[1]);
$luottorajaprosentti = (float) $argv[2];
$email = trim($argv[3]);

// Meilinlähetyksen oletustiedot
$content_subject  = "Luotonhallintaraportti ".date("d.m.y");
$content_body    = "";
$ctype        = "html";
$kukarow["eposti"]  = $email;
$liite        = array();
$laskuri      = 0;


if ($yhtiorow["myyntitilaus_saatavat"] == "Y") {
  // käsitellään luottorajoja per ytunnus
  $kasittely_periaate = "asiakas.ytunnus";
}
else {
  // käsitellään luottorajoja per asiakas
  $kasittely_periaate = "asiakas.tunnus";
}

$query  = "SELECT $kasittely_periaate ytunnus,
           group_concat(distinct tunnus) liitostunnukset,
           group_concat(distinct nimi ORDER BY nimi SEPARATOR '<br>') nimi,
           group_concat(distinct toim_nimi ORDER BY nimi SEPARATOR '<br>') toim_nimi,
           min(luottoraja) luottoraja,
           min(myyntikielto) myyntikielto,
           min(ytunnus) tunniste
           FROM asiakas
           WHERE yhtio  = '$yhtiorow[yhtio]'
           AND laji    != 'P'
           GROUP BY 1
           HAVING luottoraja > 0";
$asiakasres = pupe_query($query);

$content_body .= "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01//EN\">";
$content_body .= "<html>";
$content_body .= "<head>";
$content_body .= "<meta http-equiv='Content-Type' content='text/html; charset=ISO-8859-15'>";
$content_body .= "<style type='text/css'>{$yhtiorow["css"]}</style>";
$content_body .= "<title>".htmlentities($content_subject)."</title>";
$content_body .= "</head>";
$content_body .= "<body>";
$content_body .= "<h3>".htmlentities("Asiakkaat, jotka ovat käyttäneet yli $luottorajaprosentti% luottorajastaan").":</h3>";

$content_body .= "<table summary='".htmlentities($content_subject)."'>";
$content_body .= "<tr>";
$content_body .= "<th>".t("Ytunnus")."</th>";
$content_body .= "<th>".t("Laskutusnimi")."<br>".t("Toimitusnimi")."</th>";
$content_body .= "<th>".t("Avoimet")."<br>".t("laskut")."</th>";
$content_body .= "<th>".t("Avoimet")."<br>".t("tilaukset")."</th>";
$content_body .= "<th>".t("Kaatotili")."</th>";
$content_body .= "<th>".t("Luottotilanne nyt")."</th>";
$content_body .= "<th>".t("Luottorajasta käytetty")."</th>";
$content_body .= "<th>".t("Luottoraja")."<br>$yhtiorow[valkoodi]</th>";
$content_body .= "<th>".t("Myyntikielto")."</th>";
$content_body .= "</tr>";

$query_alennuksia = generoi_alekentta('M');

while ($asiakasrow = mysql_fetch_array($asiakasres)) {

  // Avoimet laskut
  $query = "SELECT sum(lasku.summa - lasku.saldo_maksettu) laskuavoinsaldo
            FROM lasku use index (yhtio_tila_mapvm)
            WHERE lasku.yhtio      = '$yhtiorow[yhtio]'
            AND lasku.tila         = 'U'
            AND lasku.alatila      = 'X'
            AND lasku.mapvm        = '0000-00-00'
            AND lasku.liitostunnus IN ($asiakasrow[liitostunnukset])";
  $avoimetlaskutres = pupe_query($query);
  $avoimetlaskutrow = mysql_fetch_assoc($avoimetlaskutres);

  // Avoimet tilaukset
  $query = "SELECT
            round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_alennuksia}),2) tilausavoinsaldo
            FROM lasku
            JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi IN ('L','W'))
            WHERE lasku.yhtio      = '$yhtiorow[yhtio]'
            AND ((lasku.tila = 'L' and lasku.alatila in ('A','B','C','D','E','J','V'))  # Kaikki myyntitilaukset, paitsi laskutetut
              OR (lasku.tila = 'N' and lasku.alatila in ('','A','F'))          # Myyntitilaus kesken, tulostusjonossa tai odottaa hyväksyntää
              OR (lasku.tila = 'V' and lasku.alatila in ('','A','C','J','V'))      # Valmistukset
            )
            AND lasku.liitostunnus in ($asiakasrow[liitostunnukset])";
  $avoimettilauksetres = pupe_query($query);
  $avoimettilauksetrow = mysql_fetch_assoc($avoimettilauksetres);

  // Kaatotili
  $query = "SELECT
            sum(round(summa*if(kurssi=0, 1, kurssi),2)) summa
            FROM suoritus
            WHERE yhtio        = '$yhtiorow[yhtio]'
            and ltunnus        > 0
            and kohdpvm        = '0000-00-00'
            and asiakas_tunnus in ($asiakasrow[liitostunnukset])";
  $kaatotilires = pupe_query($query);
  $kaatotilirow = mysql_fetch_assoc($kaatotilires);

  // Lasketaan luottotilanne nyt
  $luottotilanne_nyt = round($asiakasrow["luottoraja"] - $avoimetlaskutrow["laskuavoinsaldo"] + $kaatotilirow["summa"] - $avoimettilauksetrow["tilausavoinsaldo"], 2);

  // Näytä vain asiakkaat, jotka ovat täyttäneet $luottorajaprosentti prosenttia luottorajasta tai sen yli
  if ((1-($luottotilanne_nyt / $asiakasrow["luottoraja"]))*100 < $luottorajaprosentti) {
    continue;
  }

  $content_body .= "<tr>";
  $content_body .= "<td>$asiakasrow[tunniste]</td>";
  $content_body .= "<td>$asiakasrow[nimi]<br>$asiakasrow[toim_nimi]</td>";
  $content_body .= "<td align='right'>$avoimetlaskutrow[laskuavoinsaldo]</td>";
  $content_body .= "<td align='right'>$avoimettilauksetrow[tilausavoinsaldo]</td>";
  $content_body .= "<td align='right'>$kaatotilirow[summa]</td>";
  $content_body .= "<td align='right'>$luottotilanne_nyt</td>";
  $content_body .= "<td align='right'>".round((1-($luottotilanne_nyt / $asiakasrow["luottoraja"]))*100)."%</td>";
  $content_body .= "<td align='right'>$asiakasrow[luottoraja]</td>";
  $content_body .= "<td align='right'>&nbsp;$asiakasrow[myyntikielto]</td>";
  $content_body .= "</tr>\r\n";

  $laskuri++;
}

$content_body .= "</table><br>------ Raportti valmis ------<br>";
$content_body .= "</body>";
$content_body .= "</html>";

if ($laskuri > 0) {
  require "../inc/sahkoposti.inc";
}
