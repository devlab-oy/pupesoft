<?php

require ("../inc/parametrit.inc");

if ($ajax_request == 1 and $kateisoton_luonne == 1) {
    $luonteet = hae_kateisoton_luonteet();
    array_walk_recursive($luonteet, 'array_utf8_encode');

    echo json_encode($luonteet,false);
    exit;
}
if ($ajax_request == 1 and $alv == 1) {
    echo json_encode(hae_kaikki_alvit(),false);
    exit;
}
?>
<script language='javascript' type='text/javascript'>

    $(document).ready(function(){
        bind_uusi_rivi();
    bind_poista_rivi();
    });

    function bind_uusi_rivi() {
        $('#uusi_tyyppi').click(function(event){
            event.preventDefault();
            lisaa_uusi_tyyppi_rivi();
        });
    }

  function bind_poista_rivi() {
    $('.poista_rivi').live('click',function(event){
            event.preventDefault();
            $(this).parent().parent().remove();
        });
  }

    function lisaa_uusi_tyyppi_rivi() {
        var kateisotto_rivi = $('#kateisotto_rivi_template').clone();
        var current_child_index = hae_taman_hetkinen_child_index();
        current_child_index += 1;

        $(kateisotto_rivi).find('input.child_index').val(current_child_index);
        $(kateisotto_rivi).find('input.summa').attr('name', 'kateisotto_rivi['+current_child_index+'][summa]');
        $(kateisotto_rivi).find('select.kateisoton_luonne').attr('name', 'kateisotto_rivi['+current_child_index+'][kateisoton_luonne]');
        $(kateisotto_rivi).find('select.alv').attr('name', 'kateisotto_rivi['+current_child_index+'][alv]');

        populoi_kateis_oton_luonteet($(kateisotto_rivi).find('select.kateisoton_luonne'));
        populoi_alvit($(kateisotto_rivi).find('select.alv'));

    $(kateisotto_rivi).attr('id', '');
        $(kateisotto_rivi).css('display','');

        $('#kommentti_tr').before(kateisotto_rivi);
    }

    function hae_taman_hetkinen_child_index() {
        var values = $('input.child_index').map(function(){
            return isNaN(this.value) ? [] : +this.value;
        }).get();

        return Math.max.apply(null, values);
    }

    function populoi_kateis_oton_luonteet(kateisoton_luonne_select) {
        $.ajax({
            type: 'POST',
            url: 'kateisotto.php?ajax_request=1&kateisoton_luonne=1&no_head=yes',
            dataType: 'JSON',
            async:true
        }).done(function(data) {
            $.each(data, function(index, value){
                var dropdown_text = value.selitetark + ' - ' + value.tilino;
                var option = new Option(dropdown_text, value.tunnus);
                $(kateisoton_luonne_select).append(option);
            });
        });
    }

    function populoi_alvit(alv_select) {
        $.ajax({
            type: 'POST',
            url: 'kateisotto.php?ajax_request=1&alv=1&no_head=yes',
            dataType: 'JSON',
            async:true
        }).done(function(data) {
            $.each(data, function(index, value){
                var option = new Option(value['selite'], value['selite']);
                $(alv_select).append(option);
            });
        });
    }

  function tarkista() {
    var ok = true;
    if ($('#kassalipas').val() == '') {
      ok = false;
    }

    $.each($('#kateisotto_table .summa'), function(index,value) {
      if ($(value).val() == '') {
        ok = false;
      }
    });

    $.each($('#kateisotto_table .kateisoton_luonne'), function(index,value) {
      if ($(value).val() == '') {
        ok = false;
      }
    });

    if ($('#userfile').val() != '' && $('#kuvaselite').val() == '') {
      alert($('#kuva_selite_alert').html());
      ok = false;
    }

    if (!ok) {
      alert($('#tarvittavia_tietoja').html());
    }
    return ok;
  }
</script>
<?php
echo "<font class='head'>".t("K�teisotto kassalippaasta")."</font><hr>";
echo "<div id='tarvittavia_tietoja'style='display:none;'>".t("Tarvittavia tietoja puuttuu")."</div>";
echo "<div id='kuva_selite_alert'style='display:none;'>".t("Anna liitteelle selite")."</div>";

$kassalippaat = hae_kassalippaat();
$kateisoton_luonteeet = hae_kateisoton_luonteet();
$alvit = hae_kaikki_alvit();

$request_params = array(
  'kassalipas' => $kassalipas_tunnus,
  'kateisotto_rivi'=> $kateisotto_rivi,
  'yleinen_kommentti' => $yleinen_kommentti,
  'userfile' => $userfile,
  'kuvaselite' => $kuvaselite,
  'pp' => $pp,
  'kk' => $kk,
  'vv' => $vv,
  'date' => "{$vv}-{$kk}-{$pp}",
);

if ($tee == 'kateisotto') {

  $date = "{$vv}-{$kk}-{$pp}";

  $validoi_date = validoi_tapahtumapaiva($pp, $kk, $vv);

  $voiko_kateisoton_tehda = $validoi_date ? tarkista_saako_laskua_muuttaa($date) : false;

  if ($voiko_kateisoton_tehda) {

    $voiko_kateisoton_tehda = validoi_liitetiedosto($_FILES);

    if ($voiko_kateisoton_tehda and count($request_params['kateisotto_rivi']) == 0) {
      echo "<br><font class='error'>".t("K�teisotolla ei ollut yht��n rivi�")."!</font><br><br>";
      $voiko_kateisoton_tehda = false;
    }

    if ($voiko_kateisoton_tehda) {
      //tehd��n k�teisotto
      //
      //haetaan kassalipas row
      $kassalipas = hae_kassalipas($kassalipas_tunnus);

      //tarkistetaan, onko kassalipas jo t�sm�ytetty
      $kassalippaan_tasmaytys = tarkista_kassalippaan_tasmaytys($kassalipas['tunnus'], $date);

      if ($kassalippaan_tasmaytys['ltunnukset'] != '' and $kassalippaan_tasmaytys['selite'] != '') {
        $voiko_kateisoton_tehda = vapauta_kateistasmaytys($kassalipas, $date);
      }

      if ($voiko_kateisoton_tehda) {
        $lasku_tunnus = tee_kateisotto($kassalipas, $request_params);
        echo "<br><font class='message'>".t("K�teisotto tehtiin onnistuneesti")."!</font><br><br>";

        if (!empty($lasku_tunnus) and is_uploaded_file($_FILES['userfile']['tmp_name'])) {
          tallenna_liite("userfile", "lasku", $lasku_tunnus, $request_params['kuvaselite'], "", 0, 0, "");
        }
      }
    }
  }
  else {
    echo $validoi_date ? "<font class='error'>".t("VIRHE: Tilikausi on p��ttynyt %s. Et voi merkit� laskua maksetuksi p�iv�lle %s", "", $yhtiorow['tilikausi_alku'], "{$vv}-{$kk}-{$pp}")."!</font>" : "";
  }

  echo_kateisotto_form($kassalippaat, $kateisoton_luonteeet, $alvit, $request_params);
}
else {
  echo_kateisotto_form($kassalippaat, $kateisoton_luonteeet, $alvit, $request_params);
}

function tarkista_kassalippaan_tasmaytys($kassalipas_tunnus, $date) {
  global $kukarow;

  $query = "  SELECT group_concat(distinct lasku.tunnus) ltunnukset,
        group_concat(distinct tiliointi.selite) selite
        FROM lasku
        JOIN kassalipas ON ( kassalipas.yhtio = lasku.yhtio AND kassalipas.tunnus = {$kassalipas_tunnus} )
        JOIN tiliointi ON (tiliointi.yhtio = lasku.yhtio AND tiliointi.ltunnus = lasku.tunnus AND tiliointi.selite LIKE concat(kassalipas.nimi,' %') AND tiliointi.korjattu = '')
        WHERE lasku.yhtio = '{$kukarow['yhtio']}'
        AND lasku.tila     = 'X'
        AND lasku.alatila = 'K'
        AND lasku.tapvm   = '{$date}'";
  $result = pupe_query($query);

  return mysql_fetch_assoc($result);
}

function tee_kateisotto($kassalipas, $request_params) {
  global $kukarow, $yhtiorow;

    $summa = 0;
    foreach($request_params['kateisotto_rivi'] as $kateisotto_rivi) {
        $summa += $kateisotto_rivi['summa'];
    }

  $lasku_tunnus = tee_laskuotsikko($kassalipas, $summa, $request_params['yleinen_kommentti'], $request_params['date']);

    foreach($request_params['kateisotto_rivi'] as $kateisotto_rivi) {
    $kateisotto_rivi['summa'] = str_replace(',', '.', $kateisotto_rivi['summa']);
        if ($kateisotto_rivi['alv'] > 0) {
            //jos k�teisotto rivin alv on muuta kuin nolla niin pit�� laskea tili�innin alvittomat hinnat tehd� my�s alv tili�inti
            $alviton_summa = $kateisotto_rivi['summa'] / (1 + ($kateisotto_rivi['alv'] / 100));
            $alv_maara = $kateisotto_rivi['summa'] - $alviton_summa;

      $params = array(
        'lasku_tunnus' => $lasku_tunnus,
        'kassalipas' => $kassalipas,
        'kateisoton_luonne' => $kateisotto_rivi['kateisoton_luonne'],
        'alv' => $kateisotto_rivi['alv'],
        'summa' => -1*$kateisotto_rivi['summa'],
        'date' => $request_params['date'],
      );

      //tehd��n k�teisotto tili�inti
            tee_tiliointi($params);

            //tehd��n alvittoman summan tili�inti eli kulutili�inti
      $params['summa'] = $alviton_summa;
            $kulu_tiliointi_tunnus = tee_tiliointi($params, true);

            //tehd��n alv tili�inti
      $params['summa'] = $alv_maara;
      $params['kulu_tiliointi_tunnus'] = $kulu_tiliointi_tunnus;
            tee_tiliointi($params, false, true);
        }
        else {
      $params = array(
        'lasku_tunnus' => $lasku_tunnus,
        'kassalipas' => $kassalipas,
        'kateisoton_luonne' => $kateisotto_rivi['kateisoton_luonne'],
        'summa' => $kateisotto_rivi['summa'],
        'alv' => 0,
        'date' => $request_params['date'],
      );

      //tehd��n kulutili�inti
      tee_tiliointi($params, true);

      //tehd��n k�teisottotili�inti
      $params['summa'] = -1*$kateisotto_rivi['summa'];
      $params['alv'] = 0;
      tee_tiliointi($params);
        }
    }

  return $lasku_tunnus;
}

function tee_laskuotsikko($kassalipas, $summa, $yleinen_kommentti, $date) {
  global $kukarow;

  $query = "  INSERT INTO lasku
        SET yhtio    = '{$kukarow['yhtio']}',
        summa      = '$summa',
        comments    = '{$yleinen_kommentti}',
        tila      = 'X',
        alatila    = 'O',
        laatija    = '{$kukarow['kuka']}',
        luontiaika    = NOW(),
        tapvm      = '{$date}',
        kassalipas   = '{$kassalipas['tunnus']}',
        nimi      = '".t("K�teisotto kassalippaasta").": {$kassalipas['nimi']}'";
  pupe_query($query);

  return mysql_insert_id();
}

function tee_tiliointi($params, $kulu_tiliointi = false, $alv_tiliointi = false) {
  global $kukarow, $yhtiorow;

  if ($kulu_tiliointi) {
    $kateisoton_luonne_row = hae_kateisoton_luonne($params['kateisoton_luonne']);
    $kateisoton_luonne_row['kustp'] = $params['kassalipas']['kustp'];
    $selite = t("K�teisotto kassalippaasta").": " . $params['kassalipas']['nimi'];
    $vero = $params['alv'];
    $kulu_tiliointi_linkki = "";
    $lukko = "lukko = '',";
  }
  elseif ($alv_tiliointi) {
    $kateisoton_luonne_row['tilino'] = $yhtiorow['alv'];
        $kateisoton_luonne_row['kustp'] = 0;
        $selite = t("K�teisotton vero kassalippaasta").": " . $params['kassalipas']['nimi'];
        $vero = 0;
    $kulu_tiliointi_linkki = "aputunnus = {$params['kulu_tiliointi_tunnus']},";
    $lukko = "lukko = '1',";
  }
  else {
    $kateisoton_luonne_row['tilino'] = $params['kassalipas']['kassa'];
    $kateisoton_luonne_row['kustp'] = $params['kassalipas']['kustp'];
    $selite = t("K�teisotto kassalippaasta").": " . $params['kassalipas']['nimi'];
    $vero = 0;
    $kulu_tiliointi_linkki = "";
    $lukko = "lukko = '',";
  }

  $date = $params['date'];

  $query = "  INSERT INTO tiliointi
                SET yhtio   = '{$kukarow['yhtio']}',
                laatija   = '{$kukarow['kuka']}',
                laadittu   = NOW(),
                ltunnus   = '{$params['lasku_tunnus']}',
                tilino     = '{$kateisoton_luonne_row['tilino']}',
                kustp     = '{$kateisoton_luonne_row['kustp']}',
                tapvm     = '{$date}',
                summa     = {$params['summa']},
                summa_valuutassa = {$params['summa']},
                valkoodi   = '{$yhtiorow['valkoodi']}',
                selite     = '{$selite}',
        {$kulu_tiliointi_linkki}
        {$lukko}
                vero     = {$vero}";

    pupe_query($query);

  return mysql_insert_id();
}

function tarkista_saako_laskua_muuttaa($tapahtumapaiva) {
  global $yhtiorow;

  if (strtotime($yhtiorow['tilikausi_alku']) < strtotime($tapahtumapaiva) and strtotime($yhtiorow['tilikausi_loppu']) > strtotime($tapahtumapaiva)) {
    return true;
  }
  else {
    return false;
  }

}

function validoi_tapahtumapaiva($pp, $kk, $vv) {

  $errormsg = false;
  $return = true;

  $date = "$vv-$kk-$pp";

  // Huom. preg_match tarkoituksella "== false", koska se palauttaa virheess� 0 tai false
  if (preg_match("/\d{4}-\d{2}-\d{2}/", $date) == false or strtotime($date) === false) {
    $errormsg = t("P�iv�m��r� on virheellinen");
    $return = false;
  }

  if (strtotime($date) > strtotime(date('Y-m-d'))) {
    $errormsg = t("P�iv�m��r� ei saa olla tulevaisuudessa");
    $return = false;
  }

  echo $errormsg ? "<font class='error'>{$errormsg}</font><br />" : '';

  return $return;
}

function validoi_liitetiedosto($_FILES) {
  $return = true;
  if (!empty($_FILES['userfile'])) {
    switch ($_FILES['userfile']['error']) {
      case 1:
      case 2:
        $errormsg .= t("Kuva on liian suuri, suurin sallittu koko on")." ".ini_get('post_max_size'). '<br/>';
        $return = false;
        break;
      case 3:
        $errormsg .= t("Kuvan lataus keskeytyi")."!<br/>";
        $return = false;
        break;
      case 6:
      case 7:
      case 8:
        $errormsg .= t("Tallennus ep�onnistui")."!<br/>";
        $return = false;
        break;
      case 0:
        //  OK tallennetaan
        $return = true;
        break;
    }

    $query = "SHOW variables like 'max_allowed_packet'";
    $result = pupe_query($query);
    $varirow = mysql_fetch_row($result);
    if ($_FILES['userfile']['size'] > $varirow[1]) {
      $errormsg .= t("Liitetiedosto on liian suuri")."! ($varirow[1]) <br/>";
      $return = false;
    }

  }

  echo "<font class='error'>". $errormsg . "</font>";

  return $return;
}

function hae_kassalipas($kassalipas_tunnus) {
  global $kukarow, $yhtiorow;

  $query = "  SELECT *
        FROM kassalipas
        WHERE yhtio = '{$kukarow['yhtio']}'
        AND tunnus = '{$kassalipas_tunnus}'";
  $result = pupe_query($query);

  //tarkistetaan, ett� kassalippaan takaa l�ytyy tilinumerot, jos ei l�ydy niin laitetaan yhtiorown takaa defut
  $kassalipas = mysql_fetch_assoc($result);

  if ($kassalipas['kassa'] == '') {
    $kassalipas['kassa'] = $yhtiorow['kassa'];
  }
  if ($kassalipas['pankkikortti'] == '') {
    $kassalipas['pankkikortti'] = $yhtiorow['pankkikortti'];
  }
  if ($kassalipas['luottokortti'] == '') {
    $kassalipas['luottokortti'] = $yhtiorow['luottokortti'];
  }

  return $kassalipas;
}

//Haetaan kaikki kassalippaat, joihin k�ytt�j�ll� on oikeus
//$kukarow['kassalipas'] pit�� sis�ll��n kassalippaan joihin oikeus. Jos tyhj� --> k�ytt�j�ll� oikeus kaikkiin kassalippaisiin
function hae_kassalippaat() {
  global $kukarow;

  if ($kukarow['kassalipas_otto'] != '') {
    $sallitut_kassalipppaat = "AND tunnus IN ({$kukarow['kassalipas_otto']})";
  }

  $query = "  SELECT *
        FROM kassalipas
        WHERE yhtio = '{$kukarow['yhtio']}'
        {$sallitut_kassalipppaat}";
  $result = pupe_query($query);

  $kassalippaat = array();
  while ($kassalipas = mysql_fetch_assoc($result)) {
    $kassalippaat[] = $kassalipas;
  }

  return $kassalippaat;
}

function hae_kateisoton_luonne($avainsana_tunnus) {
  global $kukarow;

  $query = "  SELECT avainsana.tunnus,
        avainsana.selite,
        avainsana.selitetark,
        tili.tilino,
        tili.kustp
        FROM avainsana
        JOIN tili
        ON ( tili.yhtio = avainsana.yhtio AND tili.tunnus = avainsana.selite )
        WHERE avainsana.yhtio = '{$kukarow['yhtio']}'
        AND avainsana.laji='KATEISOTTO'
        and avainsana.tunnus = '{$avainsana_tunnus}'";
  $result = pupe_query($query);

  return mysql_fetch_assoc($result);
}

function hae_kateisoton_luonteet() {
  global $kukarow;

  $query = "  SELECT avainsana.tunnus,
        avainsana.selite,
        avainsana.selitetark,
        tili.tilino
        FROM avainsana
        JOIN tili
        ON ( tili.yhtio = avainsana.yhtio AND tili.tunnus = avainsana.selite )
        WHERE avainsana.yhtio = '{$kukarow['yhtio']}'
        AND avainsana.laji='KATEISOTTO'";
  $result = pupe_query($query);

  $kateisoton_luonteet = array();
  while ($kateisoton_luonne = mysql_fetch_assoc($result)) {
    $kateisoton_luonteet[] = $kateisoton_luonne;
  }

  return $kateisoton_luonteet;
}

function echo_kateisotto_form($kassalippaat, $kateisoton_luonteet, $alvit, $request_params = array()) {
  echo "<form name='kateisotto' method='POST' enctype='multipart/form-data'>";
  echo "<input type='hidden' name='tee' value='kateisotto'/>";
  echo "<table id='kateisotto_table'>";

  echo "<tr>";
  echo "<th>".t("Kassalipas")."</th>";
  echo "<td>";
  echo "<select name='kassalipas_tunnus' id='kassalipas'>";
  echo "<option value=''>".t("Valitse kassalipas")."</option>";
  $sel = "";
  foreach ($kassalippaat as $kassalipas) {
    if ($kassalipas['tunnus'] == $request_params['kassalipas']) {
      $sel = "SELECTED";
    }
    echo "<option value='{$kassalipas['tunnus']}' $sel>{$kassalipas['nimi']}</option>";
    $sel = "";
  }
  echo "</select>";
  echo "</td>";
  echo "</tr>";

    echo "<tr>";
    echo "<th>";
    echo t("Lis�� uusi rivi");
    echo "</th>";
    echo "<td>";
    echo "<button id='uusi_tyyppi'>".t("Uusi k�teisotto rivi")."</button>";
    echo "</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<th>";
    echo t("K�teisotto rivi");
    echo "</th>";
    echo "<td>";
    echo "<input type='hidden' class='child_index' value='0' />";
    echo "<button class='poista_rivi' onclick='bind_poista_rivi();'>".t("Poista rivi")."</button>";
    echo "<br/>";

    echo t("Summa") . ':';
    echo "<br/>";
    echo "<input type='text' name='kateisotto_rivi[0][summa]' class='summa' value='{$request_params['summa']}'>";
    echo "<br/>";

    echo t("Mihin tarkoitukseen k�teisotto tehd��n");
    echo "<br/>";
    echo "<select name='kateisotto_rivi[0][kateisoton_luonne]' class='kateisoton_luonne'>";
  echo "<option value=''>".t("Valitse tarkoitus")."</option>";
  $sel = "";
  foreach ($kateisoton_luonteet as $luonne) {
    if ($luonne['tunnus'] == $request_params['kateisoton_luonne']) {
      $sel = "SELECTED";
    }
    echo "<option value='{$luonne['tunnus']}' $sel>{$luonne['selitetark']} - {$luonne['tilino']}</option>";
    $sel = "";
  }
  echo "</select>";
    echo "<br/>";

    echo t("Alv");
    echo "<br/>";
    echo "<select name='kateisotto_rivi[0][alv]' class='alv'>";
    foreach($alvit as $alv) {
        echo "<option value='{$alv['selite']}'>{$alv['selite']}</option>";
    }
    echo "</select>";

    echo "</td>";
    echo "</tr>";

  echo "<tr id='kommentti_tr'>";
  echo "<th>".t("Yleinen kommentti")."</th>";
  echo "<td><input type='text' name='yleinen_kommentti' id='yleinen_kommentti' value='{$request_params['yleinen_kommentti']}'></td>";
  echo "</tr>";

  $pp = isset($request_params['pp']) ? $request_params['pp'] : date('d');
  $kk = isset($request_params['kk']) ? $request_params['kk'] : date('m');
  $vv = isset($request_params['vv']) ? $request_params['vv'] : date('Y');

  echo "<tr>";
  echo "<th>",t("Tapahtumap�iv�"),"</th>";
  echo "<td>";
  echo "<input type='text' id='pp' name='pp' value='{$pp}' size='3' maxlength='2' />&nbsp;";
  echo "<input type='text' id='kk' name='kk' value='{$kk}' size='3' maxlength='2' />&nbsp;";
  echo "<input type='text' id='vv' name='vv' value='{$vv}' size='5' maxlength='4' />";
  echo "</td>";
  echo "</tr>";

  echo "  <tr>
        <th>".t("Valitse tiedosto")."</th>
        <td><input id='userfile' name='userfile' type='file'></td>
        </tr>
        <th>".t("Liitteen kuvaus")."</th>
        <td><input id='kuvaselite' type='text' size='40' name='kuvaselite'></td>
    </tr>";

  echo "</table>";

  echo "<td class='back'><input name='submit' type='submit' value='".t("L�het�")."' onClick='return tarkista();'></td>";

  echo "</form>";

    echo "<table>";
    echo '  <tr id="kateisotto_rivi_template" style="display:none;">
                <th>'.t("K�teisotto rivi").'</th>
                <td>
                    <input type="hidden" value="" class="child_index">
                    <button class="poista_rivi" onclick="bind_poista_rivi();">'.t("Poista rivi").'</button>
                    <br/>
                    '.t("Summa").':
                    <br>
                    <input type="text" value="" class="summa" name="">
                    <br><br>
                    '.t("Mihin tarkoitukseen k�teisotto tehd��n").':
                    <br>
                    <select class="kateisoton_luonne" name="">
                    <option value="">'.t("Valitse tarkoitus").'</option>
                    </select>
                    <br><br>
                    '.t("Alv").':
                    <br>
                    <select class="alv">
                    </select>
                </td>
           </tr>';
    echo "</table>";
}

function array_utf8_encode(&$item, $key) {
    $item = utf8_encode($item);
}

require("../inc/footer.inc");
