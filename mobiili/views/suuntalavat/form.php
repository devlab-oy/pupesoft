<!-- form.php, uuden tekemiseen ja-->
<div class='header'>
  <button onclick='window.location.href="suuntalavat.php"' class='button left'><img src='back2.png'></button>
  <h1><?php echo $title ?></h1>
</div>

<div class='main'>
<form action='' method='post' onsubmit='check_disabled();'>
<!-- _form.php -->
  <table>
    <tr>
      <?php if(!isset($muokkaa)): ?>
      <th>Valitse tulostin</th>
      <td>
        <select name='tulostin'>
          <?php
            foreach($kirjoittimet as $kirjoitin) {
              $sel = (isset($tulostin) and $tulostin == $kirjoitin['tunnus']) ? " selected" : "";
              echo "<option value='{$kirjoitin['tunnus']}' size='5'{$sel}>{$kirjoitin['kirjoitin']}</option>";
            }
          ?>
        </select>
      </td>
      <?php endif ?>
    </tr>
    <tr>
      <th>Tyyppi</th>
      <td>
        <select name='tyyppi'>
          <?php
            foreach($pakkaukset as $pakkaus) {

              if (isset($suuntalava)) {
                $sel = ($pakkaus['tunnus'] == $suuntalava['ptunnus']) ? ' selected' : '';
              }
              elseif (isset($tyyppi) and !empty($tyyppi)) {
                $sel = $tyyppi == $pakkaus['tunnus'] ? ' selected' : '';
              }
              else {
                $sel = "";
              }

              echo "<option value='{$pakkaus['tunnus']}'{$sel}>";
              echo t_tunnus_avainsanat($pakkaus, "pakkaus", "PAKKAUSKV")." ".t_tunnus_avainsanat($pakkaus, "pakkauskuvaus", "PAKKAUSKV");
              echo "</option>";
            }
          ?>
        </select>
      </td>
    </tr>
    <tr>
      <th>Keräysvyöhyke</th>
      <td>
        <select name='keraysvyohyke'>
          <?php
            foreach($keraysvyohykkeet as $vyohyke) {

              if (isset($suuntalava)) {
                $sel = ($vyohyke['tunnus'] == $suuntalava['keraysvyohyke']) ? ' selected' : '';
              }
              elseif (isset($keraysvyohyke) and !empty($keraysvyohyke)) {
                $sel = $keraysvyohyke == $vyohyke['tunnus'] ? ' selected' : '';
              }
              else {
                $sel = '';
              }

              echo "<option class='keraysvyohyke' value='{$vyohyke['tunnus']}' {$sel} {$disabled}>";
              echo $vyohyke['nimitys'];
              echo "</option>";
            }
          ?>
        </select>
      </td>
    </tr>
    <tr>
      <th>Alkuhylly</th>

      <?php

        $variables = array(
          'alkuhyllyalue',
          'alkuhyllynro',
          'alkuhyllyvali',
          'alkuhyllytaso',
          'loppuhyllyalue',
          'loppuhyllynro',
          'loppuhyllyvali',
          'loppuhyllytaso',
        );

        foreach ($variables as $variable) {

          if (isset($suuntalava)) {
            ${"_{$variable}"} = $suuntalava[$variable];
          }
          elseif (isset(${$variable})) {
            ${"_{$variable}"} = ${$variable};
          }
          else {
            ${"_{$variable}"} = '';
          }

        }

      ?>

      <td>
        <input type='text' class='hylly' name='alkuhyllyalue' size='3' maxlength='5' value='<?php echo $_alkuhyllyalue ?>' <?php echo $disabled ?> >
        <input type='text' class='hylly' name='alkuhyllynro' size='3' maxlength='5' value='<?php echo $_alkuhyllynro ?>' <?php echo $disabled ?> >
        <input type='text' class='hylly' name='alkuhyllyvali' size='3' maxlength='5' value='<?php echo $_alkuhyllyvali ?>' <?php echo $disabled ?> >
        <input type='text' class='hylly' name='alkuhyllytaso' size='3' maxlength='5' value='<?php echo $_alkuhyllytaso ?>' <?php echo $disabled ?> >
      </td>
    </tr>
    <tr>
      <th>Loppuhylly</th>

      <td>
        <input type='text' class='hylly' name='loppuhyllyalue' size='3' maxlength='5' value='<?php echo $_loppuhyllyalue ?>' <?php echo $disabled ?> >
        <input type='text' class='hylly' name='loppuhyllynro' size='3' maxlength='5' value='<?php echo $_loppuhyllynro ?>' <?php echo $disabled ?> >
        <input type='text' class='hylly' name='loppuhyllyvali' size='3' maxlength='5' value='<?php echo $_loppuhyllyvali ?>' <?php echo $disabled ?> >
        <input type='text' class='hylly' name='loppuhyllytaso' size='3' maxlength='5' value='<?php echo $_loppuhyllytaso ?>' <?php echo $disabled ?> ></td>
      </tr>
    </tr>
    <tr>
      <th>Käytettävyys</th>
      <?php

        $checked = array('Y' => '', 'L' => '');

        if (isset($suuntalava) and !empty($suuntalava['kaytettavyys'])) {
          $checked[$suuntalava['kaytettavyys']] = 'checked';
        }
        elseif (isset($kaytettavyys) and !empty($kaytettavyys)) {
          $checked[$kaytettavyys] = 'checked';
        }

        echo "<td><input type='radio' name='kaytettavyys' id='yksityinen' value='Y' {$checked['Y']} /><label for='yksityinen'>Yksityinen</label>";
        echo "<input type='radio' name='kaytettavyys' id='yleinen' value='L' {$checked['L']} /><label for='yleinen'>Yleinen</label></td>";
      ?>
    </tr>
    <tr><th>Terminaalialue</th>
      <?php

        $checked = array('Lähettämö' => '', 'Pakkaamo' => '');

        if (isset($suuntalava) and !empty($suuntalava['terminaalialue'])) {
          $checked[$suuntalava['terminaalialue']] = 'checked';
        }
        elseif (isset($terminaalialue) and !empty($terminaalialue)) {
          $checked[$terminaalialue] = 'checked';
        }

        echo "<td><input type='radio' name='terminaalialue' id='lahettamo' value='Lähettämö' {$checked['Lähettämö']} /><label for='lahettamo'>Lähettämö</label>";
        echo "<input type='radio' name='terminaalialue' id='pakkaamo' value='Pakkaamo' {$checked['Pakkaamo']} /><label for='pakkaamo'>Pakkaamo</label></td>";
      ?>
    </tr>
    <tr><th>Sallitaanko</th>
      <?php

        $checked = array('E' => '', 'K' => '');

        if (isset($suuntalava)) {
          $suuntalava['usea_keraysvyohyke'] == 'K' ? $checked['K'] = 'checked' : $checked['E'] = 'checked';
        }
        elseif (isset($sallitaanko)) {
          $sallitaanko == 'K' ? $checked['K'] = 'checked' : $checked['E'] = 'checked';
        }

        echo "<td><input type='radio' name='sallitaanko' id='kylla' value='K' {$checked['K']} /><label for='kylla'>Kyllä</label>";
        echo "<input type='radio' name='sallitaanko' id='ei' value='' {$checked['E']} /><label for='ei'>Ei</label></td>";
      ?>
    </tr>
  </table>
<!-- end_form -->

</div>

<div class='controls'>
  <input type='submit' name='post' value='OK' class='button left' />
  </form>
    <?php if(isset($muokkaa)): ?>
    <button onclick='window.location.href="suuntalavat.php?tee=poista&suuntalava=<?php echo $suuntalava['tunnus'] ?>"' class='button left' <?php echo $disable_poista ?>>Poista</button>
    <button onclick='window.location.href="suuntalavat.php?tee=siirtovalmis&suuntalava=<?php echo $suuntalava['tunnus'] ?>"' class='button right' <?php echo $disable_siirtovalmis ?>>Siirtovalmis (normaali)</button>
    <button onclick='window.location.href="suuntalavat.php?tee=suoraan_hyllyyn&suuntalava=<?php echo $suuntalava['tunnus'] ?>"' class='button right' <?php echo $disable_siirtovalmis ?>>Siirtovalmis (suoraan hyllyyn)</button>

    <?php endif ?>
</div>

<script type='text/javascript'>
  function check_disabled() {
    hyllyt = document.getElementsByClassName('hylly');

    for(i=0; i < hyllyt.length; i++) {
      hyllyt[i].disabled = false;
    }

    keraysvyohykkeet = document.getElementsByClassName('keraysvyohyke');

    for(i=0; i < keraysvyohykkeet.length; i++) {
      keraysvyohykkeet[i].disabled = false;
    }
  }
</script>
