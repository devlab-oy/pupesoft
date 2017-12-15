<?php

// DataTables päälle
$pupe_DataTables = "laitetable";

if (strpos($_SERVER['SCRIPT_NAME'], "extranet_laiterekisteri.php") !== FALSE) {
  require "parametrit.inc";
}

$request = array();

if ($kukarow['extranet'] == '') die(t("Käyttäjän parametrit - Tämä ominaisuus toimii vain extranetissä"));

require "asiakasvalinta.inc";

// Enabloidaan ajax kikkare
enable_ajax();

?>
<script>

$(function() {
  $('.avaa_tyomaarays_linkki').on('click', function(e) {
      var clicked_id = $(this).attr("id")
      var sarjanumero = $("#sarjanumero_"+clicked_id).html();

      $.ajax({
        async: false,
        type: 'POST',
        url: "extranet_tyomaaraykset.php",
        data: {
          sarjanumero: sarjanumero,
          ajax_toiminto: 'hae_tyomaarays_sarjanumerolla',
          no_head: 'yes',
          ohje: 'off'
        }
      }).done(function(tyomaarays) {
        if (tyomaarays > 0) {
          var viesti = '<?php echo t("Laitteelle ei voida avata uutta huoltopyyntöä, koska laite löytyy jo avoimelta huoltopyynnöltä"); ?>: '+tyomaarays
          alert(viesti);

          // Dont process link click
          e.preventDefault();
        }
      });
  });
});

</script>
<?php




pupe_DataTables(array(array($pupe_DataTables, 5, 4, true, true)));
piirra_kayttajan_laitteet();

function piirra_kayttajan_laitteet() {
  global $pupe_DataTables;

  echo "<font class='head'>".t("Laiterekisteri")."</font><hr>";

  $naytettavat_laitteet = hae_kayttajan_laitteet();
  if (count($naytettavat_laitteet) > 0) {
    echo "<form name ='laiteformi'>";
    echo "<table class='display dataTable' id='$pupe_DataTables'>";

    echo "<thead>";

    echo "<tr>";
    piirra_headerit();
    echo "</tr>";

    echo "<tr>";
    piirra_hakuboksit();
    echo "</tr>";

    echo "</thead>";

    foreach ($naytettavat_laitteet as $laite) {
      piirra_laiterivi($laite);
    }
    echo "</table>";
    echo "</form>";
  }
  else {
    echo "<br><font class='error'>".t('Laiterekisteristä ei löydy yhtään laitetta tai niillä ei ole sopimuksia')."!</font><br/>";
  }
}

function hae_kayttajan_laitteet() {
  global $kukarow, $asiakasvalinta;

  $laitteet = array();

  $toimipistetunnukset = hae_kayttajan_toimipistetunnukset();

  if ($kukarow['oletus_asiakas'] == '' and empty($toimipistetunnukset)) {
    return $laitteet;
  }

  $toimipistelisa = '';
  if ($asiakasvalinta != '') {
    $toimipistelisa = " AND laite.toimipiste = {$asiakasvalinta} ";
  }
  else if (!empty($toimipistetunnukset)) {
    $toimipistelisa = " AND laite.toimipiste IN ({$toimipistetunnukset}) ";
  }

  $query = "SELECT
            laite.*,
            avainsana.selitetark valmistaja,
            tuote.tuotemerkki malli
            FROM laite
            LEFT JOIN tuote ON (tuote.yhtio = laite.yhtio
            AND tuote.tuoteno                           = laite.tuoteno)
            LEFT JOIN avainsana ON (avainsana.yhtio = tuote.yhtio
            AND avainsana.laji                          = 'TRY'
            AND avainsana.selite                        = tuote.try)
            JOIN laitteen_sopimukset ON (laitteen_sopimukset.laitteen_tunnus = laite.tunnus)
            JOIN tilausrivi ON (laitteen_sopimukset.yhtio = tilausrivi.yhtio
            AND laitteen_sopimukset.sopimusrivin_tunnus = tilausrivi.tunnus)
            JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio AND lasku.tunnus = tilausrivi.otunnus)
            WHERE laite.yhtio                           = '{$kukarow['yhtio']}'
            {$toimipistelisa}
            GROUP BY laite.sarjanro,laite.tuoteno";
  $result = pupe_query($query);

  while ($row = mysql_fetch_assoc($result)) {
    $laitteet[] = $row;
  }
  return $laitteet;
}

function piirra_headerit() {
  $headerit = array(
    t("Valmistaja"),
    t("Malli"),
    t("Sarjanumero"),
    t("Valmistajan sopimuksen päättymispäivä")
  );
  foreach ($headerit as $header) {
    echo "<th>{$header}</th>";
  }
}

function piirra_hakuboksit() {
  $headerit = array(
    'valmistaja',
    'tuotenumero',
    'sarjanumero',
    'valmistajan_sopimus_paattymispaiva'
  );
  foreach ($headerit as $header) {
    echo "<td><input type='text' class='search_field' name='search_{$header}'/></td>";
  }
  // Huoltopyyntölinkin hidden search
  echo "<td style ='display:none'><input type='hidden' class='search_field' name='search_hidden'/></td>";
}

function piirra_laiterivi($laite) {
  global $palvelin2;

  echo "<tr>";
  echo "<td>{$laite['valmistaja']}</td>";
  echo "<td>{$laite['tuoteno']}</td>";
  echo "<td id='sarjanumero_{$laite['tunnus']}'>{$laite['sarjanro']}</td>";
  echo "<td>{$laite['valmistajan_sopimus_paattymispaiva']}</td>";
  echo "<td class='back' nowrap><a class='avaa_tyomaarays_linkki' id='{$laite['tunnus']}' href='{$palvelin2}extranet_tyomaaraykset.php?tyom_toiminto=UUSI&laite_tunnus={$laite['tunnus']}'>".t('Uusi huoltopyyntö')."</a></td>";
  echo "</tr>";
}
