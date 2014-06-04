<?php

require ("inc/parametrit.inc");

echo "<font class='head'>".t("Lue tuotepaikkakohtaisia h�lytysrajoja ja tilausm��ri�")."</font><hr>";

if ($korjataan == '') $id = 0;

if (is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE and $korjataan == ''){

  $path_parts = pathinfo($_FILES['userfile']['name']);
  $name  = strtoupper($path_parts['filename']);
  $ext  = strtoupper($path_parts['extension']);

  if ($ext != "TXT" and $ext != "CSV") {
    die ("<font class='error'><br>".t("Ainoastaan .txt ja .cvs tiedostot sallittuja")."!</font>");
  }

  if ($_FILES['userfile']['size']==0) {
    die ("<font class='error'><br>".t("Tiedosto on tyhj�")."!</font>");
  }

  $file = fopen($_FILES['userfile']['tmp_name'],"r") or die (t("Tiedoston avaus ep�onnistui")."!");

  echo "<font class='message'>".t("Tutkaillaan mit� olet l�hett�nyt").".<br></font>";

   while ($rivi = fgets($file)) {
    // luetaan rivi tiedostosta..
    $poista    = array("'", "\\","\"");
    $rivi    = str_replace($poista,"",$rivi);
    $rivi    = explode("\t", trim($rivi));

    if ((trim($rivi[0]) != '') and ((trim($rivi[1]) != '') or (trim($rivi[2]) != ''))) {
      $tuoteno[$id] = trim($rivi[0]);
      $halytysraja[$id] = trim($rivi[1]);
      $tilattava[$id] = trim($rivi[2]);
      $id++;
    }
  }

  $korjataan = 'eka';
  fclose($file);

  if ($tuvarasto== '') {
    $korjataan = '';
    echo "<font class='error'>".t("Et ole valinnut varastoa")."!<br><br></font>";
  }
}

if ($korjataan != '') {

  $countti = count($tuoteno);
  $korj = 0;
  $paikkasyot = 0;

  echo " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
      <!--
      $(document).ready(function(){
        var taytasarake = function() {

          var sarake_id = $(this).attr('id').replace('taytasarake_', '');
          var teksti = $(this).val();

          $('input[id^='+sarake_id+']').each(
            function() {
              $(this).val(teksti);
              $(this).trigger('change');
            }
          );
        };

        $('input[id^=taytasarake_]').on('keyup change blur', taytasarake);
      });
      //-->
      </script>";

  echo "<form method='post'><table>";

  for ($id = 0; $id < $countti; $id++) {
    $error = '';

    if ($uusipaikka[$id] == 'uusi' and $hyllyalue[$id] != '') {

      $hyllyalue[$id] = strtoupper($hyllyalue[$id]);
      if ($hyllynro[$id]  == '') $hyllynro[$id]  = '0';
      if ($hyllyvali[$id] == '') $hyllyvali[$id] = '0';
      if ($hyllytaso[$id] == '') $hyllytaso[$id] = '0';

      $kuuluuko = kuuluukovarastoon($hyllyalue[$id], $hyllynro[$id], $tuvarasto);

      if ($kuuluuko > 0) {

        $query = "SELECT *
                  FROM tuotepaikat
                  WHERE yhtio  = '$kukarow[yhtio]'
                  and tuoteno  = '$tuoteno[$id]'
                  and oletus  != ''";
        $oleresult = pupe_query($query);

        if (mysql_num_rows($oleresult) == 0) {
          $oletus = 'X';
        }
        else {
          $oletus = '';
        }

        $query = "SELECT tunnus
                  FROM tuotepaikat
                  WHERE yhtio   = '$kukarow[yhtio]'
                  AND tuoteno   = '$tuoteno[$id]'
                  AND hyllyalue = '$hyllyalue[$id]'
                  AND hyllynro  = '$hyllynro[$id]'
                  AND hyllyvali = '$hyllyvali[$id]'
                  AND hyllytaso = '$hyllytaso[$id]'";
        $loytyykoresult = pupe_query($query);

        if (mysql_num_rows($loytyykoresult) == 0) {
          $query = "INSERT INTO tuotepaikat SET
                    yhtio       = '$kukarow[yhtio]',
                    tuoteno     = '$tuoteno[$id]',
                    hyllyalue   = '$hyllyalue[$id]',
                    hyllynro    = '$hyllynro[$id]',
                    hyllyvali   = '$hyllyvali[$id]',
                    hyllytaso   = '$hyllytaso[$id]',
                    oletus      = '$oletus',
                    halytysraja = '$halytysraja[$id]',
                    tilausmaara = '$tilattava[$id]',
                    laatija     = '$kukarow[kuka]',
                    luontiaika  = now()";
          $result = pupe_query($query);

          // tehd��n tapahtuma
          $query = "INSERT into tapahtuma set
                    yhtio     = '$kukarow[yhtio]',
                    tuoteno   = '$tuoteno[$id]',
                    kpl       = '0',
                    kplhinta  = '0',
                    hinta     = '0',
                    laji      = 'uusipaikka',
                    hyllyalue = '$hyllyalue[$id]',
                    hyllynro  = '$hyllynro[$id]',
                    hyllyvali = '$hyllyvali[$id]',
                    hyllytaso = '$hyllytaso[$id]',
                    selite    = '".t("Lis�ttiin tuotepaikka")." $hyllyalue[$id] $hyllynro[$id] $hyllyvali[$id] $hyllytaso[$id]',
                    laatija   = '$kukarow[kuka]',
                    laadittu  = now()";
          $korjres = pupe_query($query);
        }
      }
      else {
        $error = "<font class='error'>".t("Antamasi varastopaikka ei ole k�sitelt�v�ss� varastossa")."</font>";
      }
    }

    $query = "SELECT tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso,
              concat_ws('-',tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso) hyllypaikka,
              tuote.tuoteno, tuote.nimitys, varastopaikat.tunnus, tuotepaikat.oletus, tuotepaikat.halytysraja, tuotepaikat.tilausmaara, tuotepaikat.tunnus,
              concat(rpad(upper(tuotepaikat.hyllyalue) ,5,' '),lpad(tuotepaikat.hyllynro ,5,' ')) ihmepaikka
              FROM tuotepaikat, varastopaikat, tuote
              WHERE tuotepaikat.yhtio  = varastopaikat.yhtio and tuotepaikat.yhtio = tuote.yhtio
              and concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0')) >= concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0'))
              and concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0')) <= concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0'))
              and tuotepaikat.tuoteno  = tuote.tuoteno
              and tuotepaikat.yhtio    = '$kukarow[yhtio]'
              and tuotepaikat.tuoteno  = '$tuoteno[$id]'
              and varastopaikat.tunnus = '$tuvarasto'
              order by 1";
    $result2 = pupe_query($query);

    if (mysql_num_rows($result2) == 0) {
      $korj++;

      if ($korj== 1) {
        echo "<tr><th>".t("Tuoteno")."</th><th>".t("Nimitys")."</th><th>".t("H�lytysraja")."</th><th>".t("Tilausm��r�")."</th><th>".t("Huomautus")."</th><th>".t("Paikka")."</th></tr>";
      }

      echo "<tr><td>$id $tuoteno[$id]</td>";

      $query = "SELECT tuoteno, nimitys
                FROM tuote
                WHERE yhtio = '$kukarow[yhtio]'
                and tuoteno = '$tuoteno[$id]'";
      $nimresult = pupe_query($query);

      if (mysql_num_rows($nimresult) == 1) {
        $nimrow = mysql_fetch_assoc($nimresult);

        if ($error == '') {
          $error = t("Paikkaa ei l�ytynyt t�st� varastosta, anna uusi paikka");
        }

        echo "  <td>".t_tuotteen_avainsanat($nimrow, 'nimitys')."</td>
            <td align='right'>$halytysraja[$id]</td>
            <td align='right'>$tilattava[$id]</td>
            <td>$error</td>
            <td nowrap>
            <input type='hidden' name='tuoteno[$id]' value='$tuoteno[$id]'>
            <input type='hidden' name='halytysraja[$id]' value='$halytysraja[$id]'>
            <input type='hidden' name='tilattava[$id]' value='$tilattava[$id]'>
            <input type='hidden' name='rivipaikka[$id]' value=''>
            <input type='hidden' name='uusipaikka[$id]' value='uusi'>
            ",hyllyalue("hyllyalue[$id]", $hyllyalue[$id]),"
            <input type='text' id='hyllynro[$id]'  name='hyllynro[$id]'  value='$hyllynro[$id]'  maxlength='2' size='2'>
            <input type='text' id='hyllyvali[$id]' name='hyllyvali[$id]' value='$hyllyvali[$id]' maxlength='2' size='2'>
            <input type='text' id='hyllytaso[$id]' name='hyllytaso[$id]' value='$hyllytaso[$id]' maxlength='2' size='2'></td>";

        $paikkasyot++;
      }
      else {
        echo "<td></td><td></td><td></td><td>".t("TUOTENUMERO EI L�YDY")."!!!</td><td></td>";
      }

      echo "</tr>";
    }
    elseif (mysql_num_rows($result2) > 1) {
      if ($rivipaikka[$id]== '') {
        $korj++;

        if ($korj == 1) {
          echo "<tr><th>".t("Tuoteno")."</th><th>".t("Nimitys")."</th><th>".t("H�lytysraja")."</th><th>".t("Tilausm��r�")."</th><th>".t("Huomautus")."</th><th>".t("Paikka")."</th></tr>";
        }

        echo "<tr><td>$id $tuoteno[$id]</td>";

        $query = "SELECT tuoteno, nimitys
                  FROM tuote
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tuoteno = '$tuoteno[$id]'
                  LIMIT 1";
        $nimresult = pupe_query($query);

        if (mysql_num_rows($nimresult) == 1) {
          $nimrow = mysql_fetch_assoc($nimresult);

          echo "<td>".t_tuotteen_avainsanat($nimrow, 'nimitys')."</td>
              <td align='right'>$halytysraja[$id]</td>
              <td align='right'>$tilattava[$id]</td>
              <td>".t("Valitse paikka jota haluat p�ivitt��")."</td>";

          echo "<td><select name='rivipaikka[$id]'><option value=''>".t("Ei Valintaa");

          while ($varow = mysql_fetch_assoc($result2)) {
            $sel='';
            if ($varow['tunnus'] == $rivipaikka[$id]) $sel = 'selected';

            echo "<option value='$varow[tunnus]' $sel>$varow[hyllyalue] $varow[hyllynro] $varow[hyllyvali] $varow[hyllytaso]</option>";
          }
          echo "</select></td>";
        }
        else {
          echo "<td></td><td></td><td>".t("TUOTENUMERO EI L�YDY VAIKKA TUOTEPAIKKA ON")."!!!</td><td></td>";
        }

        echo "</tr>";
      }
      else {
        echo "<input type='hidden' name='rivipaikka[$id]' value='$rivipaikka[$id]'>";

        $query = "UPDATE tuotepaikat
                  SET halytysraja = '$halytysraja[$id]',
                  tilausmaara = '$tilattava[$id]'
                  WHERE yhtio = '$kukarow[yhtio]'
                  AND tunnus  = '$rivipaikka[$id]'";
        $updresult = pupe_query($query);
      }
      echo "  <input type='hidden' name='tuoteno[$id]' value='$tuoteno[$id]'>
          <input type='hidden' name='halytysraja[$id]' value='$halytysraja[$id]'>
          <input type='hidden' name='tilattava[$id]' value='$tilattava[$id]'>
          <input type='hidden' name='uusipaikka[$id]' value=''>";
    }
    elseif (mysql_num_rows($result2) == 1) {
      $varow = mysql_fetch_assoc($result2);

      echo "  <input type='hidden' name='tuoteno[$id]' value='$tuoteno[$id]'>
          <input type='hidden' name='halytysraja[$id]' value='$halytysraja[$id]'>
          <input type='hidden' name='tilattava[$id]' value='$tilattava[$id]'>
          <input type='hidden' name='uusipaikka[$id]' value=''>";

      $query = "UPDATE tuotepaikat
                SET halytysraja = '$halytysraja[$id]',
                tilausmaara = '$tilattava[$id]'
                WHERE yhtio = '$kukarow[yhtio]'
                AND tunnus  = '$varow[tunnus]'";
      $updresult = pupe_query($query);
    }
  }

  if ($korj > 0) {
    if ($paikkasyot > 0) {
      echo "<tr>
          <td colspan='5' class='spec' align='right'>".t("Sy�t� paikat kaikille riveille")."</td><td>
          <input type='text' id='taytasarake_hyllyalue' size='6'>
          <input type='text' id='taytasarake_hyllynro'  size='2'>
          <input type='text' id='taytasarake_hyllyvali' size='2'>
          <input type='text' id='taytasarake_hyllytaso' size='2'></td></tr>";

    }

    echo "</table><br>";

    echo "<input type='hidden' name='korjataan' value='ok'>";
    echo "<input type='hidden' name='tuvarasto' value='$tuvarasto'>";
    echo "<input type='submit' value='".t("Jatka")."'>";
    echo "</form><br><br><br>";
  }
  else {
    echo "<font class='message'>".t("Valmista tuli, kaikki rivit ajettu")."<br><br></font>";
    $korjataan = '';
  }
}
else {
  echo "<font class='message'>".t("Tiedostomuoto").":</font><br>
      <table>
      <tr><th colspan='3'>".t("Sarkaineroteltu tekstitiedosto").".</th></tr>
      <tr><td>".t("Tuoteno")."</td><td>".t("H�lytysraja")."</td><td>".t("Tilausm��r�")."</td></tr>
      </table>
      <br>";

  echo "<form method='post' name='sendfile' enctype='multipart/form-data'> <table>";
      echo "<tr><th>".t("Valitse varasto:")."</th>
        <td><select name='tuvarasto'>";

  $query = "SELECT tunnus, nimitys
            FROM varastopaikat
            WHERE yhtio = '$kukarow[yhtio]' AND tyyppi != 'P'
            ORDER BY tyyppi, nimitys";
  $result = pupe_query($query);

  echo "<option value=''>".t("Ei valittu")."</option>";

  while ($varselrow = mysql_fetch_assoc($result)){
    $sel = '';

    if (($varselrow["tunnus"] == $tuvarasto) or ((isset($kukarow["varasto"]) and (int) $kukarow["varasto"] > 0 and in_array($varselrow["tunnus"], explode(",", $kukarow['varasto']))) and $tuvarasto=='')) {
      $sel = 'selected';
      $tuvarasto = $varselrow["tunnus"];
    }

    echo "<option value='$varselrow[tunnus]' $sel>$varselrow[nimitys]</option>";
  }

  echo "</select></td></tr>";

  echo "<input type='hidden' name='tee' value='file'>

  <tr><th>".t("Valitse tiedosto").":</th>
    <td><input name='userfile' type='file'></td>
    <td class='back'><input type='submit' value='".t("L�het�")."'></td>
  </tr>
  </table>
  </form>";
}

require ("inc/footer.inc");
