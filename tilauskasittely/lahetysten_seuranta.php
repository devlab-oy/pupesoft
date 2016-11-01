<?php

require "../inc/parametrit.inc";

echo "  <script type='text/javascript' language='JavaScript'>

      $(document).ready(function() {

        $('a.linkki').click(function(event) {
          event.stopPropagation();
        });

        $('td.sscc').click(function() {
          $(this).toggleClass('tumma');
          var sscc = $(this).attr('id');

          if ($('tr.'+sscc).is(':visible')) {
            $('tr.'+sscc).hide();
          }
          else {
            $('tr.'+sscc).show();
          }
        });
      });

    </script>";

echo "<font class='head'>", t("L‰hetysten seuranta"), "</font><hr>";

if ((isset($ppalku) and trim($ppalku) == '') or (isset($kkalku) and trim($kkalku) == '') or (isset($vvalku) and trim($vvalku) == '')) {
  echo "<font class='error'>", t("VIRHE: Alkup‰iv‰m‰‰r‰ on virheellinen"), "!</font><br /><br />";
  $tee = "";
}

if ((isset($pploppu) and trim($pploppu) == '') or (isset($kkloppu) and trim($kkloppu) == '') or (isset($vvloppu) and trim($vvloppu) == '')) {
  echo "<font class='error'>", t("VIRHE: Loppup‰iv‰m‰‰r‰ on virheellinen"), "!</font><br /><br />";
  $tee = "";
}

if ($tee != "" and isset($asiakas) and trim($asiakas) == "" and isset($paikkakunta) and trim($paikkakunta) == "" and isset($tilausnumero) and trim($tilausnumero == "") and isset($sscc) and trim($sscc == "")) {
  echo "<font class='error'>", t("VIRHE: Ainakin yksi hakuehto syˆtett‰v‰"), "!</font><br /><br />";
  $tee = "";
}

if (!isset($sscc)) $sscc = "";
if (!isset($asiakas)) $asiakas = "";
if (!isset($paikkakunta)) $paikkakunta = "";
if (!isset($tilausnumero)) $tilausnumero = "";
if (!isset($tuotesnumero)) $tuotesnumero = "";
if (!isset($ppalku)) $ppalku = date("d", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
if (!isset($kkalku)) $kkalku = date("m", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
if (!isset($vvalku)) $vvalku = date("Y", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
if (!isset($pploppu)) $pploppu = date("d", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
if (!isset($kkloppu)) $kkloppu = date("m", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
if (!isset($vvloppu)) $vvloppu = date("Y", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
if (!isset($tee)) $tee = "";
if (!isset($varasto)) $varasto = "";

echo "<form method='post'>";
echo "<table>";
echo "<tr><th>", t("Asiakas"), "</th><td><input type='text' name='asiakas' value='{$asiakas}' />&nbsp;</td>";
echo "<th>", t("Paikkakunta"), "</th><td><input type='text' name='paikkakunta' value='{$paikkakunta}' /></td></tr>";
echo "<tr><th>", t("Tilausnumero"), "</th><td><input type='text' name='tilausnumero' value='{$tilausnumero}' /></td>";
echo "<th>", t("SSCC"), "</th><td><input type='text' name='sscc' value='{$sscc}' /></td></tr>";
echo "<tr><th>", t("Tuotenumero"), "</th><td><input type='text' name='tuotenumero' value='{$tuotenumero}' /></td>";

echo "<th>", t("Varasto"), "</th>";
echo "<td><select name='varasto'>";
echo "<option value=''>", t("Valitse"), "</option>";

$query = "SELECT *
          FROM varastopaikat
          WHERE yhtio = '{$kukarow['yhtio']}' AND tyyppi != 'P'
          ORDER BY nimitys";
$varasto_res = pupe_query($query);

while ($varasto_row = mysql_fetch_assoc($varasto_res)) {
  $sel = $varasto == $varasto_row['tunnus'] ? " selected" : "";
  echo "<option value='{$varasto_row['tunnus']}'{$sel}>{$varasto_row['nimitys']}</option>";
}

echo "</select></td></tr>";

echo "<tr><th>", t("P‰iv‰m‰‰r‰"), "</th><td style='text-align:right; vertical-align:middle;'>";
echo "<input type='text' name='ppalku' value='{$ppalku}' size='3' />&nbsp;";
echo "<input type='text' name='kkalku' value='{$kkalku}' size='3' />&nbsp;";
echo "<input type='text' name='vvalku' value='{$vvalku}' size='5' />&nbsp;-</td><td colspan='2' >&nbsp;";
echo "<input type='text' name='pploppu' value='{$pploppu}' size='3' />&nbsp;";
echo "<input type='text' name='kkloppu' value='{$kkloppu}' size='3' />&nbsp;";
echo "<input type='text' name='vvloppu' value='{$vvloppu}' size='5' />";
echo "<input type='hidden' name='tee' value='hae' />";
echo "</td></tr>";
echo "</table><br>";

echo "<input type='submit' value='", t("Hae"), "' />";
echo "</form>";

if ($tee == 'hae') {

  $ppalku = (int) $ppalku;
  $kkalku = (int) $kkalku;
  $vvalku = (int) $vvalku;

  $pploppu = (int) $pploppu;
  $kkloppu = (int) $kkloppu;
  $vvloppu = (int) $vvloppu;

  $pvmlisa = "AND lahdot.pvm >= '{$vvalku}-{$kkalku}-{$ppalku}' AND lahdot.pvm <= '{$vvloppu}-{$kkloppu}-{$pploppu}'";


  $nimilisa = trim($asiakas) != "" ? " AND (lasku.nimi LIKE ('%".mysql_real_escape_string($asiakas)."%') OR lasku.toim_nimi LIKE ('%".mysql_real_escape_string($asiakas)."%'))" : "";
  $postitplisa = trim($paikkakunta) != "" ? " AND (lasku.postitp LIKE ('%".mysql_real_escape_string($paikkakunta)."%') OR lasku.toim_postitp LIKE ('%".mysql_real_escape_string($paikkakunta)."%'))" : "";
  $tilauslisa = trim($tilausnumero) != "" ? " AND kerayserat.otunnus = '".mysql_real_escape_string($tilausnumero)."'" : "";
  $tuotelisa = trim($tuotenumero) != "" ? " AND tuote.tuoteno = '".mysql_real_escape_string($tuotenumero)."'" : "";
  $sscclisa = trim($sscc) != "" ? " AND (kerayserat.sscc LIKE ('%".mysql_real_escape_string($sscc)."%') OR kerayserat.sscc_ulkoinen LIKE ('%".mysql_real_escape_string($sscc)."%'))" : "";
  $varastolisa = trim($varasto) != "" ? " AND vh.varasto = '".(int) $varasto."' " : "";

  if ($tilauslisa != "" or $sscclisa != "") {
    $pvmlisa = "";
  }

  $query = "SELECT lahdot.pvm,
            TRIM(CONCAT(lasku.nimi, ' ', lasku.nimitark)) AS nimi,
            toimitustapa.selite AS toimitustapa,
            group_concat(DISTINCT kerayserat.sscc) AS sscc
            FROM kerayserat
            JOIN lasku ON (lasku.yhtio = kerayserat.yhtio AND lasku.tunnus = kerayserat.otunnus {$nimilisa} {$postitplisa})
            JOIN lahdot ON (lahdot.yhtio = kerayserat.yhtio AND lahdot.tunnus = lasku.toimitustavan_lahto AND lahdot.aktiivi = 'S' $pvmlisa)
            JOIN toimitustapa ON (toimitustapa.yhtio = lahdot.yhtio AND toimitustapa.tunnus = lahdot.liitostunnus)
            WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
            AND kerayserat.tila    = 'R'
            {$tilauslisa}
            {$sscclisa}
            GROUP BY 1,2,3
            ORDER BY 1,2,3 ";
  $res = pupe_query($query);

  if (mysql_num_rows($res) > 0) {

    echo "<br /><br />";
    echo "<table>";

    while ($row = mysql_fetch_assoc($res)) {

      if ($row['sscc'] == "") continue;

      $query = "SELECT
                kerayserat.nro,
                kerayserat.sscc,
                kerayserat.sscc_ulkoinen,
                kerayserat.otunnus,
                IFNULL(pakkaus.pakkauskuvaus, 'MUU KOLLI') pakkauskuvaus,
                lasku.ohjausmerkki,
                CONCAT(TRIM(CONCAT(lasku.toim_nimi, ' ', lasku.toim_nimitark)), ' ', lasku.toim_osoite, ' ', lasku.toim_postino, ' ', lasku.toim_postitp) AS osoite,
                ROUND((SUM(tuote.tuotemassa * kerayserat.kpl_keratty) + IFNULL(pakkaus.oma_paino, 0)), 1) AS kg
                FROM kerayserat
                JOIN lasku ON (lasku.yhtio = kerayserat.yhtio AND lasku.tunnus = kerayserat.otunnus)
                LEFT JOIN pakkaus ON (pakkaus.yhtio = kerayserat.yhtio AND pakkaus.tunnus = kerayserat.pakkaus)
                JOIN tilausrivi ON (tilausrivi.yhtio = kerayserat.yhtio AND tilausrivi.tunnus = kerayserat.tilausrivi)
                JOIN varaston_hyllypaikat vh ON (vh.yhtio = tilausrivi.yhtio
                  {$varastolisa}
                  AND vh.hyllyalue     = tilausrivi.hyllyalue
                  AND vh.hyllynro      = tilausrivi.hyllynro
                  AND vh.hyllyvali     = tilausrivi.hyllyvali
                  AND vh.hyllytaso     = tilausrivi.hyllytaso)
                JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno {$tuotelisa})
                WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
                AND kerayserat.sscc    IN ({$row['sscc']})
                GROUP BY 1,2,3,4,5,6,7
                ORDER BY 1,2";
      $era_res = pupe_query($query);

      if (mysql_num_rows($era_res) > 0) {

        echo "<tr>";
        echo "<td class='back' colspan='6'><font class='message'>", tv1dateconv($row['pvm']), "</font></td>";
        echo "</tr>";

        echo "<tr>";
        echo "<th>", t("Status"), "</th>";
        echo "<th>{$row['nimi']}</th>";
        echo "<th>{$row['toimitustapa']}</th>";
        echo "<th>", t("Kg"), "</th>";
        echo "<th>", t("Ohjausmerkki"), "</th>";
        echo "<th>", t("Toim.osoite"), "</th>";
        echo "</tr>";

        while ($era_row = mysql_fetch_assoc($era_res)) {
          echo "<tr>";
          echo "<td>", t("L‰hetetty"), "</td>";

          echo "<td class='sscc' id='{$era_row['sscc']}'>";

          if ((is_numeric($era_row['sscc_ulkoinen']) and (int) $era_row['sscc_ulkoinen'] > 0) or (!is_numeric($era_row['sscc_ulkoinen']) and (string) $era_row['sscc_ulkoinen'] != "")) {

            // shipment_unique_id algoritmi vaihdettu.....
            if ($era_row['nro'] <= 3281) {
              echo "<a class='linkki' href='http://www.unifaunonline.se/ext.uo.fi.track?key={$unifaun_url_key}&order={$era_row['otunnus']}_{$era_row['sscc']}' target='_blank'>{$era_row['sscc_ulkoinen']}</a>";
            }
            else {
              echo "<a class='linkki' href='http://www.unifaunonline.se/ext.uo.fi.track?key={$unifaun_url_key}&order={$era_row['nro']}_{$era_row['sscc']}' target='_blank'>{$era_row['sscc_ulkoinen']}</a>";
            }

            if (substr($era_row['sscc_ulkoinen'], 0, 4) == "JJFI") {
              echo " / <a class='linkki' target=newikkuna href='http://www.posti.fi/henkiloasiakkaat/seuranta/#/lahetys/{$era_row['sscc_ulkoinen']'>Posti</a>";
            }

            if (substr($era_row['sscc_ulkoinen'], 0, 2) == "MA") {
              echo " / <a class='linkki' target=newikkuna href='http://mhhkiweb1.matkahuolto.fi/scripts/loginetyleinen.wsc/002tapahtuma_new?spacketnum={$era_row['sscc_ulkoinen']}'>Matkahuolto</a>";
            }
          }
          else {
            echo "{$era_row['sscc']}";
          }

          echo "&nbsp;&nbsp;&nbsp;&nbsp;<img title='", t("N‰yt‰ kollin sis‰ltˆ"), "' alt='", t("N‰yt‰ kollin sis‰ltˆ"), "' src='{$palvelin2}pics/lullacons/go-down.png' style='float:right;' /></td>";

          echo "<td>{$era_row['pakkauskuvaus']}</td>";
          echo "<td>{$era_row['kg']}</td>";
          echo "<td>{$era_row['ohjausmerkki']}</td>";
          echo "<td>{$era_row['osoite']}</td>";
          echo "</tr>";

          $query = "SELECT kerayserat.otunnus, tilausrivi.tuoteno, tilausrivi.nimitys, kuka.nimi AS keraaja, ROUND(kerayserat.kpl_keratty, 0) kpl_keratty
                    FROM kerayserat
                    JOIN tilausrivi ON (tilausrivi.yhtio = kerayserat.yhtio AND tilausrivi.tunnus = kerayserat.tilausrivi)
                    JOIN kuka ON (kuka.yhtio = kerayserat.yhtio AND kuka.kuka = kerayserat.laatija)
                    WHERE kerayserat.yhtio = '{$kukarow['yhtio']}'
                    AND (kerayserat.sscc = '{$era_row['sscc']}' OR kerayserat.sscc_ulkoinen = '{$era_row['sscc']}')";
          $sscc_res = pupe_query($query);

          echo "<tr class='{$era_row['sscc']}' style='display:none'>";
          echo "<td class='back' colspan='6'>&nbsp;</td>";
          echo "</tr>";

          echo "<tr class='{$era_row['sscc']}' style='display:none'>";
          echo "<th></th>";
          echo "<th>", t("Tilausnumero"), "</th>";
          echo "<th>", t("Tuotenumero"), "</th>";
          echo "<th>", t("Nimitys"), "</th>";
          echo "<th>", t("Kpl ker‰tty"), "</th>";
          echo "<th>", t("Ker‰‰j‰"), "</th>";
          echo "</tr>";

          while ($sscc_row = mysql_fetch_assoc($sscc_res)) {
            echo "<tr class='{$era_row['sscc']}' style='display:none'>";
            echo "<td></td>";
            echo "<td>{$sscc_row['otunnus']}</td>";
            echo "<td>{$sscc_row['tuoteno']}</td>";
            echo "<td>{$sscc_row['nimitys']}</td>";
            echo "<td>{$sscc_row['kpl_keratty']}</td>";
            echo "<td>{$sscc_row['keraaja']}</td>";
            echo "</tr>";
          }

          echo "<tr class='{$era_row['sscc']}' style='display:none'>";
          echo "<td class='back' colspan='6'>&nbsp;</td>";
          echo "</tr>";
        }

        echo "<tr><td class='back' colspan='6'>&nbsp;</td></tr>";
      }
    }

    echo "</table>";
  }
}

require "inc/footer.inc";
