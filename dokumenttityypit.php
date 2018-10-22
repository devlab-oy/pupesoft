<?php

require "inc/parametrit.inc";

enable_ajax();

echo "<font class='head'>".t('Dokumenttityypit')."</font><hr><br>";

if ($tee == "LISAA") {
  $tyyppi = trim($tyyppi);

  if (empty($tyyppi)) {
    echo "<font class='error'>".t("VIRHE: Tiedostotyyppi ei saa olla tyhjä")."!</font><br><br>";
    $tee = "SYOTA";
  }

  $lisa = "";
  if (!empty($mtunnus)) {
    $lisa = " and tunnus != {$mtunnus}";
  }

  $query = "SELECT *
            FROM hyvaksyttavat_dokumenttityypit
            WHERE yhtio = '$kukarow[yhtio]'
            and tyyppi = '{$tyyppi}'
            {$lisa}";
  $result = pupe_query($query);

  if (mysql_num_rows($result)) {
    echo "<font class='error'>".t("VIRHE: Dokumenttityyppi löytyy jo järjestelmästä")."!</font><br><br>";
    $tee = "SYOTA";
  }

  $hyvakok = FALSE;
  $sel_hyvaksyjat = array();
  $kala = 1;

  foreach($hyvaksyja as $hyvak) {
    if (!empty($hyvak) and !in_array($hyvak, $sel_hyvaksyjat)) {
      $hyvakok = TRUE;
      $sel_hyvaksyjat[$kala] = $hyvak;
      $kala++;
    }
  }

  if (!$hyvakok) {
    echo "<font class='error'>".t("VIRHE: Tiiminjäsenet puuttuu")."!</font><br><br>";
    $tee = "SYOTA";
  }
}

if ($tee == "LISAA" and !empty($mtunnus)) {
  $query = "UPDATE hyvaksyttavat_dokumenttityypit set
            tyyppi     = '{$tyyppi}',
            muuttaja   = '{$kukarow['kuka']}',
            muutospvm  = now()
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus  = {$mtunnus}";
  pupe_query($query);

  $query = "DELETE from hyvaksyttavat_dokumenttityypit_kayttajat
            WHERE yhtio        = '{$kukarow['yhtio']}'
            AND doku_tyyppi_tunnus = '{$mtunnus}'";
  pupe_query($query);

  foreach ($sel_hyvaksyjat as $hyvak) {
    $query = "INSERT into hyvaksyttavat_dokumenttityypit_kayttajat set
              yhtio              = '{$kukarow['yhtio']}',
              doku_tyyppi_tunnus = '{$mtunnus}',
              kuka               = '{$hyvak}',
              laatija            = '{$kukarow['kuka']}',
              luontiaika         = now(),
              muuttaja           = '{$kukarow['kuka']}',
              muutospvm          = now()";
    pupe_query($query);
  }

  $tee = "";
}
elseif ($tee == "LISAA") {
  $query = "INSERT into hyvaksyttavat_dokumenttityypit set
            yhtio      = '{$kukarow['yhtio']}',
            tyyppi     = '{$tyyppi}',
            laatija    = '{$kukarow['kuka']}',
            luontiaika = now(),
            muuttaja   = '{$kukarow['kuka']}',
            muutospvm  = now()";
  pupe_query($query);
  $tyyppiid = mysql_insert_id($GLOBALS["masterlink"]);

  foreach ($sel_hyvaksyjat as $hyvak) {
    $query = "INSERT into hyvaksyttavat_dokumenttityypit_kayttajat set
              yhtio              = '{$kukarow['yhtio']}',
              doku_tyyppi_tunnus = '{$tyyppiid}',
              kuka               = '{$hyvak}',
              laatija            = '{$kukarow['kuka']}',
              luontiaika         = now(),
              muuttaja           = '{$kukarow['kuka']}',
              muutospvm          = now()";
    pupe_query($query);
  }

  $tee = "";
}

if ($tee == "MUOKKAA") {
  $query = "SELECT *
            FROM hyvaksyttavat_dokumenttityypit
            WHERE yhtio = '$kukarow[yhtio]'
            AND tunnus = {$tunnus}";
  $result = pupe_query($query);
  $trow = mysql_fetch_assoc($result);

  $tyyppi = $trow['tyyppi'];

  $query = "SELECT kuka
            from hyvaksyttavat_dokumenttityypit_kayttajat
            where hyvaksyttavat_dokumenttityypit_kayttajat.doku_tyyppi_tunnus = '{$tunnus}'
            and hyvaksyttavat_dokumenttityypit_kayttajat.yhtio = '{$kukarow['yhtio']}'
            ORDER BY 1";
  $res = pupe_query($query);

  $sel_hyvaksyjat = array();
  $kala = 1;

  while ($row = mysql_fetch_assoc($res)) {
    $sel_hyvaksyjat[$kala] = $row['kuka'];
    $kala++;
  }

  $mtunnus = $tunnus;
  $tee = "SYOTA";
}

if ($tee == "SYOTA") {

  if (!empty($sel_hyvaksyjat) and count($sel_hyvaksyjat) > 5) {
    $maara = count($sel_hyvaksyjat)+1;
  }
  else {
    $maara = 5;
  }

  echo "  <script type='text/javascript'>
        $(function() {
          var maara = $('#maara').val();

          for (var i = 1; i < maara; i++) {
            $('#hyvaksyja_'+i).show();
          }

          $('#lisaa_uusi_hyvaksyja').on('click', function(event) {

            event.preventDefault();

            var maara = $('#maara').val();

            $('#hyvaksyja_'+maara).show();

            maara++;

            $('#maara').val(maara);
          });

        });
      </script>";


  echo "<form method='post'>";
  echo "<input type='hidden' name='tee' value='LISAA'>";
  echo "<input type='hidden' name='mtunnus' value='$mtunnus'>";

  echo "<table>";

  echo "<tr><th>".t("Dokumenttityyppi").":</th>
        <td><input name='tyyppi' type='text' size='30' value='$tyyppi'></td></tr>";

  $query = "SELECT DISTINCT kuka.kuka, kuka.nimi
            FROM kuka
            JOIN oikeu ON oikeu.yhtio = kuka.yhtio and oikeu.kuka = kuka.kuka and oikeu.nimi like '%dokumenttien_hyvaksynta.php'
            WHERE kuka.yhtio    = '$kukarow[yhtio]'
            AND kuka.aktiivinen = 1
            AND kuka.extranet   = ''
            ORDER BY kuka.nimi";
  $vresult = pupe_query($query);

  echo "</td></tr>";

  for ($i = 1; $i < 50; $i++) {
    echo "<tr id='hyvaksyja_{$i}' class='hyvaksyja' style='display:none;'>";

    echo "<th class='ptop'>".t("Hyväksyntä- ja käyttöoikeus dokumentteihin")." $i:</th>";
    echo "<td class='ptop'>";

    echo "<select name='hyvaksyja[$i]'>
          <option value = ''></option>";

    while ($vrow = mysql_fetch_assoc($vresult)) {
      $sel = "";
      if ($sel_hyvaksyjat[$i] == $vrow['kuka']) {
        $sel = "SELECTED";
      }
      echo "<option value ='$vrow[kuka]' $sel>$vrow[nimi]</option>";
    }

    // Käydään sama data läpi uudestaan
    mysql_data_seek($vresult, 0);

    echo "</select>";
    echo "</td></tr>";
  }

  echo "<tr><td colspan='4'><a href='#' id='lisaa_uusi_hyvaksyja'>", t("Lisää jäsen tiimiin"), "</a></td></tr>";
  echo "</table>";

  echo "<br><br>";
  echo "<input type = 'hidden' name = 'maara' id='maara' value = '$maara'>";
  echo "<input type='submit' value='".t("Tallenna")."'>";
  echo "</form><br><br>";
}
else {
  echo "<form method='post'>
      <input type='hidden' name='tee' value='SYOTA'>
      <input type='submit' value='".t("Lisää uusi dokumenttityyppi")."'>
      </form><br><br>";
}

if ($tee == "") {
  // Tällä ollaan, jos olemme vasta valitsemassa dokumenttia
  $query = "SELECT *
            FROM hyvaksyttavat_dokumenttityypit
            WHERE yhtio = '$kukarow[yhtio]'
            ORDER BY tyyppi";
  $result = pupe_query($query);

  echo "<table>";
  echo "<thead>";
  echo "<tr>";
  echo "<th>".t("Nimi")."</th>";
  echo "<th>".t("Hyväksyntä- ja käyttöoikeus dokumentteihin")."</th>";
  echo "</tr>";
  echo "</thead>";

  echo "<tbody>";

  while ($trow = mysql_fetch_assoc($result)) {
    echo "<tr class='aktiivi'>";
    echo "<td class='ptop'>{$trow['tyyppi']}</td>";

    $query = "SELECT kuka.nimi
              from hyvaksyttavat_dokumenttityypit_kayttajat
              JOIN kuka ON (kuka.yhtio = hyvaksyttavat_dokumenttityypit_kayttajat.yhtio AND kuka.kuka = hyvaksyttavat_dokumenttityypit_kayttajat.kuka)
              where hyvaksyttavat_dokumenttityypit_kayttajat.doku_tyyppi_tunnus = '{$trow['tunnus']}'
              and hyvaksyttavat_dokumenttityypit_kayttajat.yhtio = '{$kukarow['yhtio']}'
              ORDER BY 1";
    $res = pupe_query($query);

    echo "<td>";

    while ($row = mysql_fetch_assoc($res)) {
      echo "{$row["nimi"]}<br>";
    }

    echo "</td>";

    echo "<td class='back' class='ptop'>
          <form method='post'>
          <input type='hidden' name='tee' value='MUOKKAA'>
          <input type='hidden' name='tunnus' value='$trow[tunnus]'>
          <input type='hidden' name='lopetus' value='$lopetus'>
          <input type='submit' value='".t("Muokkaa")."'>
          </form>
      </td>";

    echo "</tr>";
  }

  echo "</tbody>";
  echo "</table>";
}

require "inc/footer.inc";
