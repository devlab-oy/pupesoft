<?php

require "inc/parametrit.inc";
require 'inc/edifact_functions.inc';

$errors = array();
$error = '';

if (isset($task) and ($task == 'lisaa_toimittaja' or $task == 'edit_vahvista')) {

  if (empty($nimi)) {
    $errors['nimi'] = t("Syötä nimi");
  }
  if (empty($ytunnus)) {
    $errors['ytunnus'] = t("Syötä Y-tunnus");
  }
  if (empty($peruste)) {
    $errors['peruste'] = t("Valitse maksuperuste");
  }

  if (count($errors) > 0) {
    if ($task == 'edit_vahvista') {
      unset($task);
      $error = t("Tarkista tiedot");
    }
  }
  else {

    $nimi = mysql_real_escape_string($nimi);
    $ytunnus = mysql_real_escape_string($ytunnus);

    if ($task == 'edit_vahvista') {

      $query = "UPDATE toimi SET
                nimi = '{$nimi}',
                ytunnus = '{$ytunnus}'
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus = '{$toimittajatunnus}'";
      pupe_query($query);

      $query = "UPDATE asiakas SET
                nimi = '{$nimi}',
                ytunnus = '{$ytunnus}',
                toimitusehto = '{$peruste}'
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND asiakasnro = '{$toimittajatunnus}'";
      pupe_query($query);

      unset($toimittajatunnus);
    }
    else {

      $query = "INSERT INTO toimi SET
                yhtio = '{$kukarow['yhtio']}',
                nimi = '{$nimi}',
                ytunnus = '{$ytunnus}',
                tyyppi_tieto = 'tulli',
                oletus_valkoodi = 'EUR',
                oletus_vienti = 'A',
                maa_lahetys = 'FI'";
      pupe_query($query);

      $asiakasnumero = mysql_insert_id($GLOBALS["masterlink"]);

      $query = "INSERT INTO asiakas SET
                yhtio = '{$kukarow['yhtio']}',
                nimi = '{$nimi}',
                ytunnus = '{$ytunnus}',
                asiakasnro = '{$asiakasnumero}',
                toimitusehto = '{$peruste}',
                toimitustapa = 'Nouto'";
      pupe_query($query);

    }
    unset($task);
  }
}

if (isset($task) and $task == 'uusi_toimittaja') {
  $view = 'toimittajan_lisays';
}

if (!isset($task)) {

  //haetaan tullitoimittajat
  $query = "SELECT toimi.nimi, asiakas.toimitusehto, toimi.tunnus, toimi.ytunnus
            FROM toimi
            JOIN asiakas
              ON asiakas.yhtio = toimi.yhtio
              AND asiakas.asiakasnro = toimi.tunnus
            WHERE toimi.yhtio = '{$kukarow['yhtio']}'
            AND toimi.tyyppi_tieto = 'tulli'";
  $result = pupe_query($query);

  $toimittajat = array();

  while ($toimittaja = mysql_fetch_assoc($result)) {

    switch ($toimittaja['toimitusehto']) {
      case 'T':
        $peruste = t("Tonnit");
        break;

      case 'P':
        $peruste = t("Palletit");
        break;

      case 'K':
        $peruste = t("Kuutiot");
        break;

      default:
        $peruste = '';
        break;
    }

    $toimittaja['peruste'] = $peruste;
    $toimittajat[] = $toimittaja;
  }

  $view = 'perus';
}




//
//
//



echo "<font class='head'>".t("Tavaran toimittajat")."</font><hr><br>";

if (isset($view) and $view == "toimittajan_lisays") {

  echo "
  <form method='post'>
  <input type='hidden' name='task' value='lisaa_toimittaja' />
  <input type='hidden' name='' value='' />
  <table>

  <tr>
    <th>" . t("Nimi") . "</th>
    <td><input type='text' name='nimi' value='{$nimitys}' /></td>
    <td class='back error'>{$errors['nimi']}</td>
  </tr>

  <tr>
    <th>" . t("Y-tunnus") . "</th>
    <td><input type='text' name='ytunnus' value='{$hinta}' /></td>
    <td class='back error'>{$errors['ytunnus']}</td>
  </tr>

  <tr>
    <th>" . t("Laskutusperuste") . "</th>
    <td>
      <select name='peruste'>
       <option selected disabled>". t("Valitse") ."</option>
        <option value='K'>". t("Kuutiot") ."</option>
        <option value='T'>". t("Tonnit") ."</option>
        <option value='P'>". t("Palletit") ."</option>
      </select></td><td class='back error'>{$errors['peruste']}</td>
  </tr>

  <tr>
    <th></th>
    <td align='right'><input type='submit' value='". t("Lisää") ."' /></td>
    <td class='back'></td>
  </tr>
  </table>
  </form>";

}

if (isset($view) and $view == "perus") {

  echo "<table>";
  echo "<tr>";
  echo "<th>".t("Nimi")."</th>";
  echo "<th>".t("Y-tunnus")."</th>";
  echo "<th>".t("Laskutusperuste")."</th>";
  echo "<td class='back' colspan='2'></td>";
  echo "</tr>";

  foreach ($toimittajat as $toimittaja) {

    echo "<tr>";

    if (isset($toimittajatunnus) and $toimittaja['tunnus'] == $toimittajatunnus) {

      echo "<form method='post'>";
      echo "<td>";
      echo "<input type='text' name='nimi' value='{$toimittaja['nimi']}' />";
      echo "</td>";
      echo "<td>";
      echo "<input type='text' name='ytunnus' value='{$toimittaja['ytunnus']}' />";
      echo "</td>";
      echo "<td>";

      $valittu = array('K' => '', 'T' => '', 'P' => '');
      $valittu[$toimittaja['toimitusehto']] = 'SELECTED';

      echo "
      <select name='peruste'>
      <option selected disabled>". t("Valitse") ."</option>
      <option value='K' {$valittu['K']}>". t("Kuutiot") ."</option>
      <option value='T' {$valittu['T']}>". t("Tonnit") ."</option>
      <option value='P' {$valittu['P']}>". t("Palletit") ."</option>
      </select>";
      echo "</td>";
      echo "<td class='back'>";
      echo "<input type='hidden' name='task' value='edit_vahvista' />";
      echo "<input type='hidden' name='toimittajatunnus' value='{$toimittaja['tunnus']}' />";
      echo "<input  type='submit' value='". t("Vahvista") ."' />";
      echo "</td>";
      echo "<td class='back error'>{$error}</td>";
      echo "</form>";

    }
    else {

      echo "<td>";
      echo $toimittaja['nimi'];
      echo "</td>";
      echo "<td>";
      echo $toimittaja['ytunnus'];
      echo "</td>";
      echo "<td align='center'>";
      echo $toimittaja['peruste'];
      echo "</td>";
      echo "<td class='back'>";
      echo "<form method='post'>";
      echo "<input type='hidden' name='toimittajatunnus' value='{$toimittaja['tunnus']}' />";
      echo "<input type='submit' value='". t("Muokkaa") ."' />";
      echo "</form>";
      echo "</td>";

    }
    echo "</td>";
    echo "</tr>";
  }

  echo "</table>";

  echo "
    <br><form>
    <input type='hidden' name='task' value='uusi_toimittaja' />
    <input type='submit' value='". t("Lisää uusi toimittaja") . "' />
    </form><br>";

}

echo $task;

require "inc/footer.inc";
























