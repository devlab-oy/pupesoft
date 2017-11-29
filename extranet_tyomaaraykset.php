<?php

$pupe_DataTables = "tyomaaraystable";

if (strpos($_SERVER['SCRIPT_NAME'], "extranet_tyomaaraykset.php") !== FALSE) {
  require "parametrit.inc";
}

if (!empty($_POST['ajax_toiminto']) and $_POST['ajax_toiminto'] == 'hae_tyomaarays_sarjanumerolla') {
  // Onko tälle sarjanumerolle avoimia työmääräyksiä:
  $query = "SELECT
            lasku.tunnus
            FROM lasku
            JOIN tyomaarays ON (tyomaarays.yhtio=lasku.yhtio and tyomaarays.otunnus=lasku.tunnus )
            JOIN avainsana a1 ON (a1.yhtio=tyomaarays.yhtio and a1.laji='TYOM_TYOJONO' and a1.selite=tyomaarays.tyojono and a1.selite = 1)
            LEFT JOIN laite ON (laite.yhtio = lasku.yhtio and laite.sarjanro = tyomaarays.valmnro)
            WHERE lasku.yhtio = '{$kukarow['yhtio']}'
            AND lasku.tila in ('A','L','N','S','C')
            AND lasku.alatila != 'X'
            AND lasku.liitostunnus = '{$kukarow['oletus_asiakas']}'
            AND tyomaarays.valmnro = '{$sarjanumero}'
            ORDER BY lasku.tunnus DESC
            LIMIT 1";
  $result = pupe_query($query);

  if ($row = mysql_fetch_assoc($result)) {
    echo $row["tunnus"];
  }
  else {
    echo 0;
  }

  exit;
}

if ($kukarow['extranet'] == '') die(t("Käyttäjän parametrit - Tämä ominaisuus toimii vain extranetissä"));

enable_ajax();

if (isset($livesearch_tee) and $livesearch_tee == "LAITEHAKU") {
  livesearch_laitehaku();
  exit;
}

$tyom_parametrit = array(
  'valmnro' => isset($_REQUEST['valmnro']) ? $_REQUEST['valmnro'] : '',
  'valmistaja' => isset($_REQUEST['valmistaja']) ? $_REQUEST['valmistaja'] : '',
  'malli' => isset($_REQUEST['malli']) ? $_REQUEST['malli'] : '',
  'valmnro' => isset($_REQUEST['valmnro']) ? $_REQUEST['valmnro'] : '',
  'tuotenro' => isset($_REQUEST['tuotenro']) ? $_REQUEST['tuotenro'] : '',
  'sla' => isset($_REQUEST['sla']) ? $_REQUEST['sla'] : '',
  'komm1' => isset($_REQUEST['komm1']) ? $_REQUEST['komm1'] : '',
);

$osoite_parametrit = array(
  'toim_nimi' => isset($_REQUEST['toim_nimi']) ? $_REQUEST['toim_nimi'] : '',
  'toim_nimitark' => isset($_REQUEST['toim_nimitark']) ? $_REQUEST['toim_nimitark'] : '',
  'toim_osoite' => isset($_REQUEST['toim_osoite']) ? $_REQUEST['toim_osoite'] : '',
  'toim_postitp' => isset($_REQUEST['toim_postitp']) ? $_REQUEST['toim_postitp'] : '',
  'toim_postino' => isset($_REQUEST['toim_postino']) ? $_REQUEST['toim_postino'] : '',
  'toim_maa' => isset($_REQUEST['toim_maa']) ? $_REQUEST['toim_maa'] : '',

  'laskutus_nimi' => isset($_REQUEST['laskutus_nimi']) ? $_REQUEST['laskutus_nimi'] : '',
  'laskutus_nimitark' => isset($_REQUEST['laskutus_nimitark']) ? $_REQUEST['laskutus_nimitark'] : '',
  'laskutus_osoite' => isset($_REQUEST['laskutus_osoite']) ? $_REQUEST['laskutus_osoite'] : '',
  'laskutus_postitp' => isset($_REQUEST['laskutus_postitp']) ? $_REQUEST['laskutus_postitp'] : '',
  'laskutus_postino' => isset($_REQUEST['laskutus_postino']) ? $_REQUEST['laskutus_postino'] : '',
  'laskutus_maa' => isset($_REQUEST['laskutus_maa']) ? $_REQUEST['laskutus_maa'] : '',

  'tilausyhteyshenkilo' => isset($_REQUEST['tilausyhteyshenkilo']) ? $_REQUEST['tilausyhteyshenkilo'] : '',
);

$request = array(
  'tyom_toiminto' => isset($_REQUEST['tyom_toiminto']) ? $_REQUEST['tyom_toiminto'] : '',
  'laite_tunnus' => isset($_REQUEST['laite_tunnus']) ? $_REQUEST['laite_tunnus'] : '',
  'tyom_tunnus' => isset($_REQUEST['tyom_tunnus']) ? $_REQUEST['tyom_tunnus'] : '',
  'nayta_poistetut' => isset($_REQUEST['nayta_poistetut']) ? $_REQUEST['nayta_poistetut'] : '',
  'tyom_parametrit' => $tyom_parametrit,
  'osoite_parametrit' => $osoite_parametrit
);

?>
<style>
  .tr_border_top {
    border-top: 1px solid;
  }
  .text_align_right {
    text-align: right;
  }
</style>
<script>

$(function() {

  function confirmation(question) {
      var defer = $.Deferred();
      $('<div></div>')
          .html(question)
          .dialog({
              autoOpen: true,
              modal: true,
              title: '<?php echo t("Vahvistus"); ?>',
              buttons: {
                  "<?php echo t("Avaa"); ?>": function () {
                      defer.resolve(true);
            $(this).dialog("close");
                  },
                  "<?php echo t("Peruuta"); ?>": function () {
                      defer.resolve(false);
                      $(this).dialog("close");
                  }
              }
          });
      return defer.promise();
  };

  $('#avaa_tyomaarays_nappi').on('click', function() {
    var onkoviesti1 = $('#viesti1').val();
    var onkoviesti2 = $('#viesti2').val();
    $('#tarkistusmuuttuja').val('JOO');
    if (onkoviesti1.length > 0) {
        var question = "<?php
echo t("Laitetta ei löydy laiterekisteristä");
echo "<br>";
echo t("Haluatko silti avata huoltopyynnön?");
?>";
        confirmation(question).then(function (answer) {
            if(answer){
              $('#tyomaarays_form').submit();
            }
        });
    }
    else if (onkoviesti2.length > 0) {
      var question = "<?php
echo t("Laitetta ei löydy sopimukselta");
echo "<br>";
echo t("Haluatko silti avata huoltopyynnön?");
?>";
      confirmation(question).then(function (answer) {
          if(answer){
            $('#tyomaarays_form').submit();
          }
      });
    }
    else {

      var sarjanumero = $("#tyomaarays_form input[name=valmnro]").val();

      $.ajax({
        async: false,
        type: 'POST',
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
        }
        else {
          $('#tyomaarays_form').submit();
        }
      });
    }
  });
});

</script>
<?php

if ($request['tyom_toiminto'] == '' and $_REQUEST["tee"] != 'NAYTATILAUS') {
  pupe_DataTables(array(array($pupe_DataTables, 8, 9, true, true)));
}

$avataanko_tyomaarays = false;
$virheviesti1 = '';
$virheviesti2 = '';

if (isset($valmnro) and !empty($valmnro)) {
  // Jos on valittu sarjanumero niin yritetään täyttää muut laitekentät
  $query = "SELECT *
            FROM laite
            WHERE yhtio  = '{$kukarow['yhtio']}'
            AND sarjanro = '{$valmnro}'
            LIMIT 1";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 1) {
    $laiterow = mysql_fetch_assoc($result);
    $laitetiedot = hae_laitteen_parametrit($laiterow['tunnus']);

    $request['tyom_parametrit']['tuotenro'] = $laitetiedot['tuotenro'];
    $request['tyom_parametrit']['valmistaja'] = $laitetiedot['valmistaja'];
  }
  $avataanko_tyomaarays = true;
}

if (!empty($tarkistusmuuttuja) and (!empty($request['tyom_parametrit']['tuotenro']) or !empty($request['tyom_parametrit']['valmnro']))
  and !empty($request['tyom_parametrit']['komm1']) and !empty($request['osoite_parametrit']['tilausyhteyshenkilo'])) {
  // Tallennetaan työmääräys järjestelmään jos kaikki järkevät tiedot syötetty
  tallenna_tyomaarays($request);
  $tyom_toiminto = '';
  $request['tyom_toiminto'] = '';
  unset($request['tyom_parametrit']);
}
elseif (!empty($tarkistusmuuttuja)) {
  if ((!empty($request['tyom_parametrit']['valmnro']) or !empty($request['tyom_parametrit']['tuotenro'])) and puuttuuko_laite_jarjestelmasta($request['tyom_parametrit']['valmnro'], $request['tyom_parametrit']['tuotenro'])) {
    $virheviesti1 = t("HUOM: Laitetta ei löydy laiterekisteristä");
    echo "<font class='error'>{$virheviesti1}</font><br>";
  }
  elseif ((!empty($request['tyom_parametrit']['valmnro']) or !empty($request['tyom_parametrit']['tuotenro'])) and puuttuuko_laitteelta_sopimus($request['tyom_parametrit']['valmnro'], $request['tyom_parametrit']['tuotenro'])) {
    $virheviesti2 = t("HUOM: Laite löytyy, mutta sillä ei ole sopimusta");
    echo "<font class='error'>${virheviesti2}</font><br>";
  }
  if (empty($request['tyom_parametrit']['valmnro']) and empty($request['tyom_parametrit']['tuotenro'])) echo "<font class='error'>".t("VIRHE: Sarjanumero tai malli on pakollinen tieto")."</font><br>";
  if (empty($request['tyom_parametrit']['komm1'])) echo "<font class='error'>".t("VIRHE: Viankuvaus on pakollinen tieto")."</font><br>";
  if (empty($request['osoite_parametrit']['tilausyhteyshenkilo'])) echo "<font class='error'>".t("VIRHE: Yhteyshenkilö on pakollinen tieto")."</font><br>";

  $request['tyom_toiminto'] = 'UUSI';
}

require "asiakasvalinta.inc";

if ($request['tyom_toiminto'] == '') {
  piirra_kayttajan_tyomaaraykset();
}
elseif ($request['tyom_toiminto'] == 'UUSI') {
  uusi_tyomaarays_formi($laite_tunnus);
}
elseif ($request['tyom_toiminto'] == 'EMAIL_KOPIO') {
  email_tyomaarayskopio($request);
  piirra_kayttajan_tyomaaraykset();
}

function piirra_kayttajan_tyomaaraykset() {
  global $pupe_DataTables, $request;

  echo "<font class='head'>".t("Huoltopyynnöt")."</font><hr>";
  piirra_nayta_aktiiviset_poistetut();
  $naytettavat_tyomaaraykset = hae_kayttajan_tyomaaraykset();
  if (count($naytettavat_tyomaaraykset) > 0) {
    echo "<form name ='tyomaaraysformi'>";
    echo "<table class='display dataTable' id='$pupe_DataTables'>";
    echo "<thead>";
    echo "<tr>";
    piirra_tyomaaraysheaderit();
    echo "</tr>";

    echo "<tr>";
    piirra_hakuboksit();
    echo "</tr>";
    echo "</thead>";

    foreach ($naytettavat_tyomaaraykset as $tyomaarays) {
      piirra_tyomaaraysrivi($tyomaarays);
    }

    echo "</table>";
    echo "</form>";
  }
  else {
    echo "<br><font class='message'>".t('Ei avoimia huoltopyyntöjä')."!</font><br/>";
  }

  piirra_luo_tyomaarays();
}

function hae_kayttajan_tyomaaraykset() {
  global $kukarow, $request;

  $tyomaaraykset = array();

  if ($kukarow['oletus_asiakas'] == '') {
    return $tyomaaraykset;
  }
  $alatila = " AND lasku.alatila != 'X' ";
  if (!empty($request['nayta_poistetut'])) {
    $alatila = " AND lasku.alatila = 'X' ";
  }

  $query = "SELECT
            lasku.tunnus,
            lasku.viesti,
            lasku.nimi,
            lasku.tila,
            lasku.alatila,
            lasku.tilaustyyppi,
            lasku.ytunnus,
            lasku.toimaika,
            tyomaarays.komm1,
            tyomaarays.komm2,
            tyomaarays.tyojono,
            tyomaarays.tyostatus,
            kuka.nimi myyja,
            a1.selite tyojonokoodi,
            a1.selitetark tyojono,
            a2.selitetark_5 tyostatus,
            a2.selitetark_4 tyostatusvari,
            yhtio.nimi yhtio,
            yhtio.yhtio yhtioyhtio,
            a3.nimi suorittajanimi,
            a5.selitetark tyom_prioriteetti,
            lasku.luontiaika,
            group_concat(a4.selitetark_2) asekalsuorittajanimi,
            group_concat(concat(left(kalenteri.pvmalku,16), '##', left(kalenteri.pvmloppu,16), '##', if(a4.selitetark_2 is null or a4.selitetark_2 = '', kalenteri.kuka, a4.selitetark_2), '##', kalenteri.tunnus, '##', a4.selitetark, '##', timestampdiff(SECOND, kalenteri.pvmalku, kalenteri.pvmloppu))) asennuskalenteri,
            tyomaarays.valmnro,
            tyomaarays.mallivari,
            tyomaarays.merkki,
            tyomaarays.luvattu,
            laite.sla,
            a6.selitetark valmistaja,
            tuote.tuotemerkki malli
            FROM lasku
            JOIN yhtio ON (lasku.yhtio=yhtio.yhtio)
            JOIN tyomaarays ON (tyomaarays.yhtio=lasku.yhtio and tyomaarays.otunnus=lasku.tunnus )
            LEFT JOIN laskun_lisatiedot ON (lasku.yhtio=laskun_lisatiedot.yhtio and lasku.tunnus=laskun_lisatiedot.otunnus)
            LEFT JOIN kuka ON (kuka.yhtio=lasku.yhtio and kuka.tunnus=lasku.myyja)
            JOIN avainsana a1 ON (a1.yhtio=tyomaarays.yhtio and a1.laji='TYOM_TYOJONO'   and a1.selite=tyomaarays.tyojono and a1.selite = 1)
            LEFT JOIN avainsana a2 ON (a2.yhtio=tyomaarays.yhtio and a2.laji='TYOM_TYOSTATUS' and a2.selite=tyomaarays.tyostatus and a2.kieli = '{$kukarow['kieli']}')
            LEFT JOIN kuka a3 ON (a3.yhtio=tyomaarays.yhtio and a3.kuka=tyomaarays.suorittaja)
            LEFT JOIN kalenteri ON (kalenteri.yhtio = lasku.yhtio and kalenteri.tyyppi = 'asennuskalenteri' and kalenteri.liitostunnus = lasku.tunnus)
            LEFT JOIN avainsana a4 ON (a4.yhtio=kalenteri.yhtio and a4.laji='TYOM_TYOLINJA'  and a4.selitetark=kalenteri.kuka)
            LEFT JOIN avainsana a5 ON (a5.yhtio=tyomaarays.yhtio and a5.laji='TYOM_PRIORIT' and a5.selite=tyomaarays.prioriteetti)
            LEFT JOIN laite ON (laite.yhtio = lasku.yhtio and laite.sarjanro = tyomaarays.valmnro)
            LEFT JOIN tuote ON (tuote.yhtio = laite.yhtio and tuote.tuoteno = laite.tuoteno)
            LEFT JOIN avainsana a6 ON (a6.yhtio = tuote.yhtio and a6.laji = 'TRY' and a6.selite = tuote.try)
            WHERE lasku.yhtio      = '{$kukarow['yhtio']}'
            AND lasku.tila         in ('A','L','N','S','C')
            {$alatila}
            AND lasku.liitostunnus = '{$kukarow['oletus_asiakas']}'
            GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22
            ORDER BY lasku.tunnus";
  $result = pupe_query($query);

  while ($row = mysql_fetch_assoc($result)) {
    $historiaquery = "SELECT count(*) tapahtumahistoria_count
                      FROM tyomaarayksen_tapahtumat
                      WHERE yhtio           = '{$kukarow['yhtio']}'
                      AND tyomaarays_tunnus = '{$row['tunnus']}'";
    $historiaresult = pupe_query($historiaquery);
    $historiarow = mysql_fetch_assoc($historiaresult);
    $row['tapahtumahistoria_count'] = $historiarow['tapahtumahistoria_count'];
    $tyomaaraykset[] = $row;
  }

  return $tyomaaraykset;
}

function piirra_tyomaaraysheaderit($rajattu = false) {
  $headers = array(
    t('Huoltopyyntö') => true,
    t('Luontiaika') => true,
    t('Valmistaja') => false,
    t('Malli') => false,
    t('Sarjanumero') => false,
    t('Työstatus') => true,
    t('Viankuvaus') => false,
    t('Työn toimenpiteet') => true
  );

  foreach ($headers as $header => $rajataan) {
    if ($rajattu and $rajataan) continue;

    echo "<th>$header</th>";
  }
}

function piirra_hakuboksit() {
  $headers = array(
    'tunnus',
    'luontiaika',
    'valmistaja',
    'mallivari',
    'valmnro',
    'tyostatus',
    'komm1',
    'komm2'
   );
  foreach ($headers as $header) {
    echo "<td><input type='text' class='search_field' name='search_{$header}'/></td>";
  }
  // Huoltpyyntökopi hidden search
  echo "<td style ='display:none'><input type='hidden' class='search_field' name='search_hidden'/></td>";
}

function piirra_tyomaaraysrivi($tyomaarays) {
  global $palvelin2;

  echo "<tr style='background-color: {$tyomaarays['tyostatusvari']};'>";

  echo "<td>{$tyomaarays['tunnus']}";
  if ($tyomaarays['tapahtumahistoria_count'] > 0) {
    echo "<div align='right'>";
    echo "<a href='nayta_tyomaarayksen_tapahtumat.php?tyomaaraystunnus=$tyomaarays[tunnus]' title='".t('Avaa työmääräyksen tapahtumat uuteen välilehteen')."' target='_blank'>";
    echo "<img src='{$palvelin2}/pics/lullacons/info.png'>";
    echo "</a></div>";
  }
  echo "</td>";
  echo "<td>{$tyomaarays['luontiaika']}</td>";
  $valmistajatieto = !empty($tyomaarays['valmistaja']) ? $tyomaarays['valmistaja'] : $tyomaarays['merkki'];
  echo "<td>{$valmistajatieto}</td>";
  echo "<td>{$tyomaarays['mallivari']}</td>";
  echo "<td>{$tyomaarays['valmnro']}</td>";
  echo "<td>{$tyomaarays['tyostatus']}</td>";
  echo "<td>{$tyomaarays['komm1']}</td>";
  echo "<td>{$tyomaarays['komm2']}</td>";
  echo "<td class='back'>";
  echo "<a href='extranet_tyomaaraykset.php?tyom_tunnus={$tyomaarays['tunnus']}&tyom_toiminto=EMAIL_KOPIO'>".t('Huoltopyyntökopio sähköpostiin')."</a>";
  echo "</td>";
  echo "</tr>";
}

function piirra_luo_tyomaarays() {
  echo "<br><br>";
  echo "<form name='uusi_tyomaarays_button'>";
  echo "<input type='hidden' name='tyom_toiminto' value='UUSI'>";
  echo "<input type='submit' value='".t('Uusi huoltopyyntö')."'>";
  echo "</form>";
}

function piirra_nayta_aktiiviset_poistetut() {
  global $request;
  echo "<br>";
  echo "<form name='uusi_tyomaarays_button'>";
  if (!empty($request['nayta_poistetut'])) {
    echo "<input type='hidden' name='nayta_poistetut' value=''>";
    echo "<input type='submit' value='".t('Näytä aktiiviset')."'>";
  }
  else {
    echo "<input type='hidden' name='nayta_poistetut' value='JOO'>";
    echo "<input type='submit' value='".t('Näytä suljetut')."'>";
  }
  echo "</form>";
  echo "<br><br>";
}

function uusi_tyomaarays_formi($laite_tunnus) {
  global $request;

  echo "<font class='head'>".t("Uusi huoltopyyntö")."</font><hr>";
  // Jos ollaan tultu laiterekisteristä ja halutaan tehdä työmääräys tietylle laitteelle
  if (!empty($laite_tunnus)) {
    $request['tyom_parametrit'] = hae_laitteen_parametrit($laite_tunnus);
  }

  $asiakasdata = hae_asiakasdata();
  echo "<form name ='uusi_tyomaarays_form' id='tyomaarays_form' method='post' action=''>";
  echo "<table>";
  echo "<tr>";
  piirra_tyomaaraysheaderit(true);
  echo "</tr>";
  echo "<tr>";
  piirra_edit_tyomaaraysrivi($request, true);
  echo "</tr>";
  echo "</table>";
  echo "<br>";
  piirra_yhteyshenkilontiedot_taulu();
  piirra_toimitusosoite_taulu($asiakasdata);
  $virhe1 = '';
  $virhe2 = '';
  if (!empty($request['tyom_parametrit']['tuotenro']) or !empty($request['tyom_parametrit']['valmnro'])) {
    if (puuttuuko_laite_jarjestelmasta($request['tyom_parametrit']['valmnro'], $request['tyom_parametrit']['tuotenro'])) {
      $virhe1 = 'JOO';
    }
    elseif (puuttuuko_laitteelta_sopimus($request['tyom_parametrit']['valmnro'], $request['tyom_parametrit']['tuotenro'])) {
      $virhe2 = 'JOO';
    }
  }
  echo "<input type='hidden' id='viesti1' name='viesti1' value='{$virhe1}'>";
  echo "<input type='hidden' id='viesti2' name='viesti2' value='{$virhe2}'>";
  echo "<input type='hidden' id='tarkistusmuuttuja' name='tarkistusmuuttuja' value=''>";
  echo "<input type='hidden' name='tee' value='NAYTATILAUS'>";
  echo "<div style='display: none;'>";
  echo "<input type='submit'>";
  echo "</div>";
  echo "<input type='button' id='avaa_tyomaarays_nappi' name='avaa_tyomaarays_nappi' value='".t('Avaa huoltopyyntö')."'/>";
  echo "</form>";
}

function piirra_edit_tyomaaraysrivi($request, $piilota = false) {
  if (!$piilota) echo "<td></td>";
  if (!$piilota) echo "<td></td>";
  echo "<td><input type='text' name='valmistaja' value='{$request['tyom_parametrit']['valmistaja']}'></td>";
  echo "<td><input type='text' name='tuotenro' value='{$request['tyom_parametrit']['tuotenro']}'></td>";

  echo "<td>";
  echo livesearch_kentta("tyomaarays_form", "LAITEHAKU", "valmnro", 140, $request['tyom_parametrit']['valmnro'], '', '', '', 'ei_break_all');
  echo "</td>";

  if (!$piilota) echo "<td></td>";
  echo "<td><textarea cols='40' rows='5' name='komm1'>{$request['tyom_parametrit']['komm1']}</textarea></td>";
  if (!$piilota) echo "<td></td>";
}

function piirra_yhteyshenkilontiedot_taulu() {
  global $kukarow, $request;

  $tilausyhteyshenkilo .= $kukarow['nimi']." \n";
  $tilausyhteyshenkilo .= $kukarow['eposti']." \n";
  $tilausyhteyshenkilo .= $kukarow['puhno'];

  if (!empty($request['osoite_parametrit']['tilausyhteyshenkilo'])) {
    $tilausyhteyshenkilo = $request['osoite_parametrit']['tilausyhteyshenkilo'];
  }

  echo "<br>";
  echo "<table>";
  echo "<tr><th colspan='4'>".t('Yhteyshenkilö')."</th><td><textarea cols='40' rows='5' name='tilausyhteyshenkilo'>{$tilausyhteyshenkilo}</textarea></td></tr>";
  echo "</table>";
}

function piirra_toimitusosoite_taulu($asiakas) {
  echo "<br>";
  echo "<table>";
  echo "<tr><th colspan='2'>".t('Toimitusosoite')."</th></tr>";
  echo "<tr><th>".t('Nimi')."</th>";
  echo "<td><input type='text' name='toim_nimi' value='{$asiakas['toim_nimi']}'></td></tr>";

  echo "<tr><th>".t('Osoite')."</th>";
  echo "<td><input type='text' name='toim_osoite' value='{$asiakas['toim_osoite']}'></td></tr>";

  echo "<tr><th>".t('Postinumero')."</th>";
  echo "<td><input type='text' name='toim_postino' value='{$asiakas['toim_postino']}'></td></tr>";

  echo "<tr><th>".t('Postitoimipaikka')."</th>";
  echo "<td><input type='text' name='toim_postitp' value='{$asiakas['toim_postitp']}'></td></tr>";

  echo "<tr><th>".t('Maa')."</th>";
  echo "<td><input type='text' name='toim_maa' value='{$asiakas['toim_maa']}'></td></tr>";
  echo "</table>";

  echo "<input type='hidden' name='laskutus_nimi' value='{$asiakas['laskutus_nimi']}'>";
  echo "<input type='hidden' name='laskutus_osoite' value='{$asiakas['laskutus_osoite']}'>";
  echo "<input type='hidden' name='laskutus_postino' value='{$asiakas['laskutus_postino']}'>";
  echo "<input type='hidden' name='laskutus_postitp' value='{$asiakas['laskutus_postitp']}'>";
}

function tallenna_tyomaarays($request) {
  global $kukarow;

  $asiakastiedot = hae_asiakasdata();
  // Luodaan uusi lasku
  $query  = "INSERT INTO lasku
             SET yhtio = '{$kukarow['yhtio']}',
             luontiaika          = now(),
             laatija             = '{$kukarow['kuka']}',
             nimi                = '{$asiakastiedot['nimi']}',
             nimitark            = '{$asiakastiedot['nimitark']}',
             osoite              = '{$asiakastiedot['osoite']}',
             postino             = '{$asiakastiedot['postino']}',
             postitp             = '{$asiakastiedot['postitp']}',
             maa                 = '{$asiakastiedot['maa']}',
             toim_nimi           = '{$request['osoite_parametrit']['toim_nimi']}',
             toim_nimitark       = '{$request['osoite_parametrit']['toim_nimitark']}',
             toim_osoite         = '{$request['osoite_parametrit']['toim_osoite']}',
             toim_postino        = '{$request['osoite_parametrit']['toim_postino']}',
             toim_postitp        = '{$request['osoite_parametrit']['toim_postitp']}',
             toim_maa            = '{$request['osoite_parametrit']['toim_maa']}',
             ytunnus             = '{$asiakastiedot['ytunnus']}',
             liitostunnus        = '{$kukarow['oletus_asiakas']}',
             tilaustyyppi        = 'A',
             tila                = 'A',
             tilausyhteyshenkilo = '{$request['osoite_parametrit']['tilausyhteyshenkilo']}'";
  $result = pupe_query($query);
  $utunnus = mysql_insert_id($GLOBALS["masterlink"]);

  $query = "INSERT INTO laskun_lisatiedot SET
            laskutus_nimi     = '{$request['osoite_parametrit']['laskutus_nimi']}',
            laskutus_nimitark = '{$request['osoite_parametrit']['laskutus_nimitark']}',
            laskutus_osoite   = '{$request['osoite_parametrit']['laskutus_osoite']}',
            laskutus_postino  = '{$request['osoite_parametrit']['laskutus_postino']}',
            laskutus_postitp  = '{$request['osoite_parametrit']['laskutus_postitp']}',
            laskutus_maa      = '{$request['osoite_parametrit']['laskutus_maa']}',
            yhtio             = '{$kukarow['yhtio']}',
            otunnus           = '{$utunnus}',
            laatija           = '{$kukarow['kuka']}',
            luontiaika        = now()";
  $lisatiedot_result = pupe_query($query);

  // Luodaan uusi työmääräys
  $query  = "INSERT INTO tyomaarays
             SET yhtio = '{$kukarow['yhtio']}',
             luontiaika   = now(),
             otunnus      = '{$utunnus}',
             laatija      = '{$kukarow['kuka']}',
             tyojono      = '1',
             tyostatus    = 'O',
             prioriteetti = '3',
             hyvaksy      = 'Kyllä',
             komm1        = '{$request['tyom_parametrit']['komm1']}',
             sla          = '{$request['tyom_parametrit']['sla']}',
             mallivari    = '{$request['tyom_parametrit']['tuotenro']}',
             valmnro      = '{$request['tyom_parametrit']['valmnro']}',
             merkki       = '{$request['tyom_parametrit']['valmistaja']}'";
  $result  = pupe_query($query);

  $request['tyom_tunnus'] = $utunnus;
  $request['pdf_ruudulle'] = true;
  email_tyomaarayskopio($request);
}

function hae_laitteen_parametrit($laite_tunnus) {
  global $kukarow;

  $laiteparametrit = array();
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
            LEFT JOIN laitteen_sopimukset ON (laitteen_sopimukset.laitteen_tunnus = laite.tunnus)
            LEFT JOIN tilausrivi ON (laitteen_sopimukset.yhtio = tilausrivi.yhtio
            AND laitteen_sopimukset.sopimusrivin_tunnus = tilausrivi.tunnus)
            LEFT JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio AND lasku.tunnus = tilausrivi.otunnus)
            WHERE laite.yhtio                           = '{$kukarow['yhtio']}'
            AND laite.tunnus                            = '{$laite_tunnus}'";

  $result = pupe_query($query);
  $row = mysql_fetch_assoc($result);

  $laiteparametrit['valmistaja'] = $row['valmistaja'];
  $laiteparametrit['malli'] = $row['malli'];
  $laiteparametrit['valmnro'] = $row['sarjanro'];
  $laiteparametrit['tuotenro'] = $row['tuoteno'];
  $laiteparametrit['sla'] = $row['sla'];

  return $laiteparametrit;
}

function hae_asiakasdata() {
  global $kukarow;

  // Haetaan oletusasiakkuus
  $query = "SELECT asiakas.*,
            IF(laskutus_nimi = '', nimi, laskutus_nimi) laskutus_nimi,
            IF(laskutus_osoite = '', osoite, laskutus_osoite) laskutus_osoite,
            IF(laskutus_postino = '', postino, laskutus_postino) laskutus_postino,
            IF(laskutus_postitp = '', postitp, laskutus_postitp) laskutus_postitp,
            IF(laskutus_maa = '', maa, laskutus_maa) laskutus_maa,
            IF(toim_nimi = '', nimi, toim_nimi) toim_nimi,
            IF(toim_osoite = '', osoite, toim_osoite) toim_osoite,
            IF(toim_postino = '', postino, toim_postino) toim_postino,
            IF(toim_postitp = '', postitp, toim_postitp) toim_postitp,
            IF(toim_maa = '', maa, toim_maa) toim_maa
            FROM asiakas
            WHERE asiakas.yhtio = '{$kukarow['yhtio']}'
            AND asiakas.tunnus  = '{$kukarow['oletus_asiakas']}'";
  $result = pupe_query($query);
  $asiakasdata = mysql_fetch_assoc($result);
  // Haetaan ext käyttäjän osoitteet
  $query = "SELECT selitetark
            FROM extranet_kayttajan_lisatiedot
            WHERE extranet_kayttajan_lisatiedot.yhtio = '{$kukarow['yhtio']}'
            AND extranet_kayttajan_lisatiedot.laji = 'TOIMITUSOSOITE'
            AND extranet_kayttajan_lisatiedot.liitostunnus = '{$kukarow['tunnus']}'";
  $result2 = pupe_query($query);
  if (mysql_num_rows($result2) == 1) {

    $jokurow = mysql_fetch_assoc($result2);
    // Otetaan selitetark:ista toimitusosoitteet tiedot, eroteltuna ###
    list($toimnimi, $toimkatu, $toimpostino, $toimpostitp, $toimmaa) = explode("###", $jokurow['selitetark']);
    $asiakasdata['toim_nimi'] = $toimnimi;
    $asiakasdata['toim_osoite'] = $toimkatu;
    $asiakasdata['toim_postino'] = $toimpostino;
    $asiakasdata['toim_postitp'] = $toimpostitp;
    $asiakasdata['toim_maa'] = $toimmaa;
  }
  return $asiakasdata;
}

function email_tyomaarayskopio($request) {
  global $kukarow, $yhtiorow;
  $tyom_tunnus = $request['tyom_tunnus'];

  require_once "huoltopyynto_pdf.inc";

  $huolto_email = t_avainsana("HUOLTOP_EMAIL", '', '', '', '', "selite");

  if ($request['tyom_toiminto'] == 'EMAIL_KOPIO') {
    $mihin_maili_lahetetaan = $kukarow['eposti'];
  }
  else {
    $mihin_maili_lahetetaan = $huolto_email;
  }

  $body = t("Tämä on automaattinen viesti. Tähän sähköpostiin ei tarvitse vastata.")."\n\n";
  $body .= t("Huoltopyyntönumero").": {$tyom_tunnus}";

  // Sähköpostin lähetykseen parametrit
  $parametrit = array(
    "to"       => $mihin_maili_lahetetaan,
    "cc"       => "",
    "subject"    => t('Huoltopyyntö')." {$tyom_tunnus}",
    "ctype"      => "text",
    "body"      => $body,
    "attachements"  => array(0   => array(
        "filename"    => $pdffilenimi,
        "ctype"      => "pdf"),
    )
  );

  pupesoft_sahkoposti($parametrit);

  // Avataan pdf ruudulle
  if ($request['pdf_ruudulle']) {

    js_openFormInNewWindow();
    echo "<br><form id='tulostakopioform_{$tyom_tunnus}' name='tulostakopioform_{$tyom_tunnus}' method='post' action='{$palvelin2}tulostakopio.php' autocomplete='off'>
          <input type='hidden' name='otunnus' value='{$tyom_tunnus}'>
          <input type='hidden' name='tyom_tunnus' value='{$tyom_tunnus}'>
          <input type='hidden' name='pdffilenimi' value='{$pdffilenimi}'>
          <input type='hidden' name='toim' value='HUOLTOPYYNTOKOPIO'>
          <input type='hidden' name='tee' value='NAYTATILAUS'>
          <input type='submit' value='".t("Avaa huoltopyyntö").": {$tyom_tunnus}' onClick=\"js_openFormInNewWindow('tulostakopioform_{$tyom_tunnus}', ''); return false;\"></form><br><br>";
  }
}

function puuttuuko_laite_jarjestelmasta($sarjanumero, $tuotenumero) {
  global $kukarow;

  $puuttuu = true;
  $query = "SELECT *
            FROM laite
            WHERE yhtio  = '{$kukarow['yhtio']}'
            AND tuoteno  = '{$tuotenumero}'
            AND sarjanro = '{$sarjanumero}'";
  $result = pupe_query($query);
  if (mysql_num_rows($result) != 0) {
    $puuttuu = false;
  }
  return $puuttuu;
}

function puuttuuko_laitteelta_sopimus($sarjanumero, $tuotenumero) {
  global $kukarow;

  $puuttuu = true;
  $query = "SELECT laite.tunnus
            FROM laite
            JOIN laitteen_sopimukset ON laite.yhtio = laitteen_sopimukset.yhtio
              AND laite.tunnus = laitteen_sopimukset.laitteen_tunnus
            WHERE laite.yhtio  = '{$kukarow['yhtio']}'
            AND laite.tuoteno  = '{$tuotenumero}'
            AND laite.sarjanro = '{$sarjanumero}'";
  $result = pupe_query($query);
  if (mysql_num_rows($result) != 0) {
    $puuttuu = false;
  }
  return $puuttuu;
}
