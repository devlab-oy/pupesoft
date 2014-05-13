<?php

  $ulos = "";
  $jatko = 1; // oletetaan normaali k‰sittely
  $tyyppi = 1; // oletetaan rivin n‰kyvyys

  if ($i == 0) { //yhtiˆt‰ ei n‰ytet‰
    $tyyppi = 0;
  }

  if (mysql_field_name($result, $i) == "automaattipaivitys") {

    $ulos = "<td><select name='$nimi' ".js_alasvetoMaxWidth($nimi, 400).">";

    $sel = array();
    $apu = $trow[$i];
    $sel[$apu] = "selected";

    $ulos .= "<option value = ''>".t("Valuutta p‰vitet‰‰n automaattisesti")."</option>";
    $ulos .= "<option value = 'E' $sel[E]>".t("Valuuttaa ei p‰vitet‰ automaattisesti")."</option>";
    $ulos .= "</select></td>";

    $jatko = 0;
  }
