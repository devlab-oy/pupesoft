<?php

$tamascripti = FALSE;

if (strpos($_SERVER['SCRIPT_NAME'], "lahetamuistutus.php")  !== FALSE) {
  $tamascripti = TRUE;
}

//käyttöliittymä
if ($tamascripti) {

  //* Tämä skripti käyttää slave-tietokantapalvelinta *//
  $useslave = 1;

  require "inc/parametrit.inc";

  echo "<font class='head'>".t("Muistuta käyttäjiä hyväksynnässä olevista ostolaskuista")."</font><hr>";
}

if (!$tamascripti or $tee == "LAHETA") {

  $maara   = 0;
  $laskuja = 0;
  $veposti = "";
  $meili   = "";

  if ($tamascripti) {
    echo "<br>".t("Lähetetään käyttäjille muistutukset hyväksynnästä")."...<br>";
  }

  $query = "SELECT concat_ws(' ',lasku.nimi, nimitark) nimi, tapvm, erpcm, round(summa * valuu.kurssi,2) summa, kuka.eposti
            FROM lasku, valuu, kuka
            WHERE lasku.yhtio='$kukarow[yhtio]' and valuu.yhtio=lasku.yhtio and
            kuka.yhtio=lasku.yhtio and lasku.valkoodi=valuu.nimi and
            lasku.hyvaksyja_nyt=kuka.kuka and kuka.eposti <> '' and
            lasku.tila = 'H' and lasku.alatila != 'M'
            ORDER BY kuka.eposti, tapvm";
  $result = pupe_query($query);

  while ($trow = mysql_fetch_array($result)) {
    $laskuja++;

    if ($trow['eposti'] != $veposti) {
      if ($veposti != '') {
        $meili = t("Sinulla on hyväksyttävänä seuraavat laskut").":\n\n" . $meili;
        $tulos = mail($veposti, mb_encode_mimeheader(t("Muistutus laskujen hyväksynnästä"), "ISO-8859-1", "Q"), $meili, "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");
        $maara++;
      }
      $meili = '';
      $veposti = $trow['eposti'];
    }

    $meili .= "Laskuttaja: " . $trow['nimi'] . "\n";
    $meili .= "Laskutuspäivä: " . $trow['tapvm'] . "\n";
    $meili .= "Eräpäivä: " . $trow['erpcm'] . "\n";
    $meili .= "Summa: " .$yhtiorow["valkoodi"]." ".$trow['summa'] . "\n\n";
  }

  if ($meili != '') {
    $meili = t("Sinulla on hyväksyttävänä seuraavat laskut").":\n\n" . $meili;
    $tulos = mail($veposti, mb_encode_mimeheader(t("Muistutus laskujen hyväksynnästä"), "ISO-8859-1", "Q"), $meili, "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");
    $maara++;
  }

  if ($tamascripti) {
    echo "<br><br><font class='message'>".t("Lähetettiin")." $maara ".t("muistutusta. Muistutettuja laskuja")." $laskuja ".t("kappaletta").".</font><hr>";
  }
}

//käyttöliittymä
if ($tamascripti) {

  echo "  <br><br>
      <form method='post'>
      <input type='hidden' name='tee' value='LAHETA'>
      <input type='submit' value='".t("Lähetä muistutukset hyväksynnässä olevista ostolaskuista")."'>
      </form>";


  require "inc/footer.inc";
}
