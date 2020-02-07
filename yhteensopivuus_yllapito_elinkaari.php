<?php

require "inc/parametrit.inc";
require "inc/tecdoc.inc";

echo "<font class='head'>", t("Elinkaaren hallinta"), "</font><hr>";

if (!isset($tunnus)) $tunnus = '';
if (!isset($merkki)) $merkki = '';
if (!isset($malli)) $malli = '';
if (!isset($table)) $table = '';
if (!isset($tee)) $tee = '';

$tyyppi = $table == 'CV' ? "RA" : "HA";
$type = $table == 'CV' ? 'cv' : 'pc';
$reksuodatus = $type == 'cv' ? false : true;

if ($tee == 'paivita') {

  foreach (array_keys($premium) as $tun) {

    $query = "SELECT *
              FROM autoid_lisatieto
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND typnr   = '{$tun}'";
    $chk_res = pupe_query($query);

    $lukittu[$tun] = $lukittu[$tun] != '' ? 1 : 0;

    if (mysql_num_rows($chk_res) == 1) {
      $query = "UPDATE autoid_lisatieto SET
                pre            = '{$premium[$tun]}',
                std            = '{$standard[$tun]}',
                eco            = '{$economy[$tun]}',
                autoid_lukittu = '{$lukittu[$tun]}',
                muuttaja       = '{$kukarow['kuka']}',
                muutospvm      = now()
                WHERE yhtio    = '{$kukarow['yhtio']}'
                AND typnr      = '{$tun}'";
      $upd_res = pupe_query($query);
    }
    else {
      $query = "INSERT INTO autoid_lisatieto SET
                tyyppi         = '{$type}',
                typnr          = '{$tun}',
                pre            = '{$premium[$tun]}',
                std            = '{$standard[$tun]}',
                eco            = '{$economy[$tun]}',
                autoid_lukittu = '{$lukittu[$tun]}',
                yhtio          = '{$kukarow['yhtio']}',
                laatija        = '{$kukarow['kuka']}',
                luontiaika     = now(),
                muuttaja       = '{$kukarow['kuka']}',
                muutospvm      = now()";
      $ins_res = pupe_query($query);
    }
  }
}

echo "<form method='post' action=''>";
echo "<table>";
echo "<tr>";
echo "<th>", t("Tunnus"), "</th>";
echo "<td><input type='text' name='tunnus' value='{$tunnus}'></td>";

if (trim($tunnus) != '') {

  $params = array(
    'tyyppi' => $type,
    'autoid' => $tunnus
  );

  $result = td_getversion($params);

  $merkkimallirow = mysql_fetch_assoc($result);

  if (isset($merkkimallirow['manuid']) and trim($merkkimallirow['manuid']) == '') {
    $merkkimallirow = mysql_fetch_assoc($result);
  }

  $merkki = $merkkimallirow['manuid'];
  $malli = $merkkimallirow['modelno'];
}

$params = array(
  'tyyppi' => $type,
  'reksuodatus' => $reksuodatus
);

$result = td_getbrands($params);

echo "<td><select name='merkki' onchange='submit()'>";
echo "<option value=''>", t("Valitse merkki"), "</option>";

while ($merkki_row = mysql_fetch_assoc($result)) {
  $sel = $merkki == $merkki_row['manuid'] ? ' SELECTED' : '';

  echo "<option value='{$merkki_row['manuid']}'{$sel}>{$merkki_row['name']}</option>";
}

echo "</select></td>";

echo "<td><select name='malli' onchange='submit()'>";
echo "<option value=''>", t("Valitse malli"), "</option>";

if ($merkki != '') {

  $params = array(
    'tyyppi' => $type,
    'merkkino' => $merkki
  );

  $result = td_getmodels($params);

  while ($malli_row = mysql_fetch_assoc($result)) {
    $sel = $malli == $malli_row['modelno'] ? ' SELECTED' : '';

    echo "<option value='{$malli_row['modelno']}'{$sel}>{$malli_row['modelname']} ";
    echo td_niceyear($malli_row['vma'], $malli_row['vml']);
    echo "</option>";
  }
}

echo "</select></td>";

echo "<input type='hidden' name='table' value='{$table}' />";
echo "<td><input type='submit' value='", t("Hae"), "'></td>";
echo "</tr>";
echo "</table>";

echo "</form>\n";

if (!isset($atunnus)) $atunnus = array();

if (trim($tunnus) != '') {
  if (!isset($atunnus) or !is_array($atunnus) or count($atunnus) == 0) {
    $atunnus = array($tunnus);
  }
}

if ((trim($merkki) != '' and trim($malli) != '') or count($atunnus) > 0) {

  $lisays = "merkki, malli, versio, alkuvuosi";

  $params = array(
    'tyyppi' => $type,
    'mallino' => $malli,
    'merkkino' => $merkki,
    'autoid' => $atunnus,
    'reksuodatus' => $reksuodatus
  );

  $result = td_getversion($params);

  if (mysql_num_rows($result) > 0 and mysql_num_rows($result) < 50 or $nayta_kaikkirivit == 1) {

    echo "<br /><br />";

    echo "  <script type='text/javascript'>

            $(function() {

              $('#economy, #standard, #premium').live('keyup', function() {

                var id = $(this).attr('id');
                var val = $(this).val();

                $('.'+id).each(function() {
                  var parentti = $(this).parent().parent();
                  if (!$(parentti).find('input.lukittu').is(':checked')) {
                    $(parentti).find('input.'+id).val(val);
                  }
                });
              });
            });

          </script>";

    echo "<form method='post'>
          <input type='hidden' name='toim' value='{$toim}'>
          <input type='hidden' name='tee' value='paivita'>
          <input type='hidden' name='merkki' value='{$merkki}'>
          <input type='hidden' name='malli' value='{$malli}'>
          <input type='hidden' name='table' value='{$table}' />";

    echo "<table>";
    echo "<tr>";
    echo "<th>", t("Mallitaso"), "</th>";
    echo "<th>", t("Premium"), "</th>";
    echo "<th>", t("Standard"), "</th>";
    echo "<th>", t("Economy"), "</th>";
    echo "<th></th>";
    echo "</tr>";

    echo "<tr>";
    echo "<td></td>";
    echo "<td><input type='text' name='mallitaso_premium' id='premium' value='' size='4' /></td>";
    echo "<td><input type='text' name='mallitaso_standard' id='standard' value='' size='4' /></td>";
    echo "<td><input type='text' name='mallitaso_economy' id='economy' value='' size='4' /></td>";
    echo "<td><input type='submit' value='", t("Tallenna"), "' /></td>";
    echo "</tr>";

    echo "</table>";

    echo "<br><br><table>";
    echo "<tr>";
    echo "<th>", t("Autoid"), "</th>";
    echo "<th>", t("Merkki ja malli"), "</th>";
    echo "<th>", t("tilavuus"), "</th>";
    echo "<th>", t("cc"), "</th>";
    echo "<th>", t("vm alku"), "</th>";
    echo "<th>", t("vm loppu"), "</th>";
    echo "<th>", t("kw"), "</th>";
    echo "<th>", t("hp"), "</th>";

    if ($type == 'pc') {
      echo "<th>", t("sylinterit"), "</th>";
      echo "<th>", t("venttiilit"), "</th>";
      echo "<th>", t("voimansiirto"), "</th>";
      echo "<th>", t("polttoaine"), "</th>";
      echo "<th>", t("moottorit"), "</th>";
    }
    else {
      echo "<th>", t("Korityyppi"), "</th>";
      echo "<th>", t("Moottorityyppi"), "</th>";
      echo "<th>", t("Tonnit"), "</th>";
      echo "<th>", t("Akselit"), "</th>";
    }

    echo "<th>", t("Moottorikoodit"), "</th>";

    echo "<th>", t("Rek. määrä"), "</th>";

    echo "<th>", t("Lukittu"), "</th>";
    echo "<th>", t("Premium"), "</th>";
    echo "<th>", t("Standard"), "</th>";
    echo "<th>", t("Economy"), "</th>";

    echo "</tr>";

    while ($row = mysql_fetch_assoc($result)) {
      echo "<tr>";
      echo "<td valing='top'>{$row['autoid']}</td>";
      echo "<td valing='top'>{$row['manu']} {$row['model']} {$row['version']}</td>";
      echo "<td valing='top'>{$row['capltr']}</td>";
      echo "<td valing='top'>{$row['cc']}</td>";
      echo "<td valing='top'>", td_cleanyear($row['vma']), "</td>";
      echo "<td valing='top'>", td_cleanyear($row['vml']), "</td>";

      if ($type == 'pc') {
        echo "<td valing='top'>{$row['kw']}</td>";
        echo "<td valing='top'>{$row['hp']}</td>";
      }
      else {
        if ($row['kwl'] == 0) {
          echo "<td valing='top'>{$row['kwa']}</td>";
        }
        else {
          echo "<td valing='top'>{$row['kwa']} - {$row['kwl']}</td>";
        }

        if ($row['hpl'] == 0) {
          echo "<td valing='top'>{$row['hpa']}</td>";
        }
        else {
          echo "<td valing='top'>{$row['hpa']} - {$row['hpl']}</td>";
        }
      }

      if ($type == 'pc') {
        echo "<td valing='top'>{$row['cyl']}</td>";
        echo "<td valing='top'>{$row['valves']}</td>";
        echo "<td valing='top'>{$row['drivetype']}</td>";
        echo "<td valing='top'>{$row['fueltype']}</td>";
        echo "<td valing='top'>{$row['enginetype']}</td>";
      }
      else {
        echo "<td valing='top'>{$row['bodytype']}</td>";
        echo "<td valing='top'>{$row['enginetype']}</td>";
        echo "<td valing='top'>{$row['tons']}</td>";
        echo "<td valing='top'>{$row['axles']}</td>";
      }

      echo "<td valing='top'>{$row['mcodes']}</td>";
      echo "<td valing='top'>{$row['rekmaara']}</td>";

      $query = "SELECT *, IF(pre = 0, '', pre) pre, IF(std = 0, '', std) std, IF(eco = 0, '', eco) eco
                FROM autoid_lisatieto
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND typnr   = '{$row['autoid']}'";
      $chk_lisatieto_res = pupe_query($query);
      $chk_lisatieto_row = mysql_fetch_assoc($chk_lisatieto_res);

      $chk = $chk_lisatieto_row['autoid_lukittu'] ? "checked" : "";

      echo "<td><input type='checkbox' class='lukittu' name='lukittu[{$row['autoid']}]' {$chk} /></td>";
      echo "<td><input type='text' class='premium' name='premium[{$row['autoid']}]' value='{$chk_lisatieto_row['pre']}' size='4' /></td>";
      echo "<td><input type='text' class='standard' name='standard[{$row['autoid']}]' value='{$chk_lisatieto_row['std']}' size='4' /></td>";
      echo "<td><input type='text' class='economy' name='economy[{$row['autoid']}]' value='{$chk_lisatieto_row['eco']}' size='4' /></td>";

      echo "</tr>";
    }

    echo "<tr>";
    echo "<td colspan='19' align='right'><input type='submit' value='", t("Tallenna"), "' /></td>";
    echo "</tr>";

    echo "</form></table>";

    // kelataan alkuun, käytetään uudestaan
    mysql_data_seek($result, 0);
  }
  else {
    echo "<form method='post'>";
    echo "<input type='hidden' name='toim' value='$toim'>";
    echo "<input type='hidden' name='merkki' value='$merkki'>";
    echo "<input type='hidden' name='malli' value='$malli'>";
    echo "<input type='hidden' name='osasto' value='$osasto'>";
    echo "<input type='hidden' name='tuoteryhma' value='$tuoteryhma'>";
    echo "<input type='hidden' name='nayta_kaikkirivit' value='1'>";
    echo "<input type='hidden' name='table' value='{$table}' />";
    echo "<br><br><font class='message'>", t("Haulla löytyi"), " ".mysql_num_rows($result)." ", t("ajoneuvoa"), ", ", t("tulosta ei näytetä"), ". </font> <input type='submit' value='", t("Näytä kaikki autot"), "'><br>";
    echo "</form>";
  }
}

require "inc/footer.inc";
