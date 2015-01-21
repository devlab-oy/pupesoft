<?php
require "inc/parametrit.inc";

$errors = array();

if (isset($task) and $task == 'uusi_nimike') {

  if (empty($nimikedata['nimitys'])) {
    $errors["nimitys"] = t("Syötä nimitys");
  }

  if (empty($nimikedata['myyntihinta'])) {
    $errors["hinta"] = t("Syötä hinta");
  }

  if (empty($nimikedata['yksikko'])) {
    $errors["yksikko"] = t("Valitse yksikkö");
  }

  if (count($errors) > 0) {
    unset($task);
  }
  else {

    if ($nimikedata['yksikko'] == 'KG') {
      $nimikedata['myyntihinta'] = $nimikedata['myyntihinta'] / 1000;
    }

    $query = "SELECT column_name
              FROM information_schema.columns
              WHERE table_name = 'tuote'
              AND table_schema = '{$dbkanta}'";
    $result = pupe_query($query);

    while ($column = mysql_fetch_assoc($result)) {
      $columns[] = $column;
    }

    $t = array();

    $automaattiset = array("yhtio","laatija","luontiaika","muuttaja","muutospvm","tunnus");

    foreach ($columns as $key => $column) {

      $koodi = "RP-".uniqid();

      if (in_array($column['column_name'], $automaattiset)) {
        continue;
      }
      elseif ($column['column_name'] == 'mallitarkenne') {
        $t[$key] = 'varastointinimike';
      }
      elseif ($column['column_name'] == 'tuoteno') {
        $t[$key] = $koodi;
      }
      elseif (array_key_exists($column['column_name'], $nimikedata)) {
        $t[$key] = $nimikedata[$column['column_name']];
      }
      else {
        $t[$key] = '';
      }

    }

    $toim = "tuote";
    $upd = "1";
    $uusi = "1";
    $no_head = "yes";

    ob_start();
    require "yllapito.php";
    $data = ob_get_clean();
    ob_end_clean();

    header("Location: varastointinimikkeet.php");
    exit;
  }
}

if (!isset($task)) {
  $otsikko = t("Perustetut varastointinimikkeet");
}
else {
  $otsikko = t("Varastointinimikkeen perustaminen");
}

echo "<font class='head'>{$otsikko}</font><hr><br>";

if (isset($task) and $task == 'aloita_perustus') {

  echo "
  <form method='post'>
  <input type='hidden' name='task' value='uusi_nimike' />
  <input type='hidden' name='' value='' />
  <table>

  <tr>
    <th>" . t("Nimitys") . "</th>
    <td><input type='text' name='nimikedata[nimitys]' value='{$nimitys}' /></td>
    <td class='back error'>{$errors['nimitys']}</td>
  </tr>

  <tr>
    <th>" . t("Hinta") . "</th>
    <td><input type='text' name='nimikedata[myyntihinta]' value='{$hinta}' /></td>
    <td class='back error'>{$errors['hinta']}</td>
  </tr>

  <tr>
    <th>" . t("Yksikkö") . "</th>
    <td>
      <select name='nimikedata[yksikko]'>
        <option value='KG'>". t("Tonni") ."</option>
        <option value='KPL'>". t("Kpl") ."</option>
        <option value='H'>". t("Tunti") ."</option>
      </select></td><td class='back error'>{$errors['yksikko']}</td>
  </tr>

  <tr>
    <th></th>
    <td align='right'><input type='submit' value='". t("Jatka") ."' /></td>
    <td class='back'></td>
  </tr>
  </table>
  </form>";

}

if (isset($task) and $task == 'edit_nimike_vahvista') {

  if (!empty($uusi_nimitys) and !empty($uusi_hinta)) {

    $nimitys = mysql_real_escape_string($uusi_nimitys);
    $hinta = mysql_real_escape_string($uusi_hinta);

    $query = "UPDATE tuote SET
              nimitys = '{$nimitys}',
              myyntihinta = '{$hinta}',
              yksikko = '{$uusi_yksikko}'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus = '{$edit_tunnus_vahvista}'";
    pupe_query($query);
  }

unset($task);
}

if (!isset($task)) {

  $query = "SELECT *
            FROM tuote
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND mallitarkenne = 'varastointinimike'";
  $result = pupe_query($query);

  $tulot = array();
  $tuotteet = array();

  echo "<table>";
  echo "<tr>";
  echo "<th>".t("Nimitys")."</th>";
  echo "<th>".t("myyntihinta")."</th>";
  echo "<th>".t("yksikkö")."</th>";
  echo "<th class='back'>d</th>";
  echo "</tr>";

  while ($tuote = mysql_fetch_assoc($result)) {

    if ($tuote['yksikko'] == 'KG') {
      $tuote['yksikko'] = "TONNI";
      $hinta = $tuote['myyntihinta'] * 1000;
    }
    else {
      $hinta = $tuote['myyntihinta'];
    }

    if (isset($edit_tunnus) and $tuote['tunnus'] == $edit_tunnus) {

      echo "<tr>";
      echo "<form method='post'>";
      echo "<td valign='top'>";
      echo "<input type='text' name='uusi_nimitys' value='{$tuote['nimitys']}' />";
      echo "</td>";

      echo "<td valign='top' align='right'>";
      echo "<input type='text' name='uusi_hinta' size='5' value='{$hinta}' />";
      echo " €</td>";

      echo "<td valign='top'>";

      $valittu = array('KG' => '', 'KPL' => '', 'H' => '');

      $valittu[$tuote['yksikko']] = 'SELECTED';

      echo "
        <select name='uusi_yksikko'>
          <option value='KG' {$valittu['KG']}>". t("Tonni") ."</option>
          <option value='KPL' {$valittu['KPL']}>". t("Kpl") ."</option>
          <option value='H' {$valittu['H']}>". t("Tunti") ."</option>
        </select>";

      echo "</td>";

      echo "<td class='back'>";
      echo "<input type='hidden' name='task' value='edit_nimike_vahvista' />";
      echo "<input type='hidden' name='edit_tunnus_vahvista' value='{$tuote['tunnus']}' />";
      echo "<input  type='submit' value='". t("Vahvista") ."' />";
      echo "</td>";
      echo "</form>";
      echo "</tr>";

    }
    else {

      echo "<tr>";

      echo "<td valign='top'>";
      echo $tuote['nimitys'];
      echo "</td>";

      echo "<td valign='top' align='right'>";
      echo number_format((float) $hinta, 2, '.', '');
      echo " €</td>";

      echo "<td valign='top'>";
      echo $tuote['yksikko'];
      echo "</td>";

      echo "<td class='back'>";
      echo "<form method='post'>";
      echo "<input type='hidden' name='edit_tunnus' value='{$tuote['tunnus']}' />";
      echo "<input type='submit' value='". t("Muokkaa") ."' />";
      echo "</form>";
      echo "</td>";

      echo "</tr>";

    }
  }
  echo "</table>";

  echo "
    <br><form>
    <input type='hidden' name='task' value='aloita_perustus' />
    <input type='submit' value='". t("Perusta uusi nimike") . "' />
    </form><br>";

}

require "inc/footer.inc";
