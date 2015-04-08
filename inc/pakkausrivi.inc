<?php

$ulos = "";
$jatko = 1; // oletetaan normaali k‰sittely
$tyyppi = 1; // oletetaan rivin n‰kyvyys

if (mysql_field_name($result, $i) == "tunnus" or
  mysql_field_name($result, $i) == "yhtio") {
  $tyyppi = 0;
}

if (mysql_field_name($result, $i) == "erikoispakkaus" or mysql_field_name($result, $i) == "yksin_eraan") {
  $sel = $trow[$i] == 'K' ? ' selected' : '';

  $ulos = "<td><select name='{$nimi}'>";
  $ulos .= "<option value=''>".t("Ei")."</option>";
  $ulos .= "<option value='K' {$sel}>".t("Kyll‰")."</option>";
  $ulos .= "</select></td>";

  $jatko = 0;
}

if (mysql_field_name($result, $i) == "rahtivapaa_veloitus") {
  $sel = $trow[$i] == "E" ? "selected" : "";

  $ulos = "<td>
          <select name='{$nimi}'>
            <option value='' {$sel}>" .
          t("Tehd‰‰n lavaveloitus, vaikka tilaus olisi merkitty rahtivapaaksi") . "</option>
            <option value='E' {$sel}>" .
          t("Ei tehd‰ lavaveloitusta, jos tilaus on merkitty rahtivapaaksi") . "</option>
          </select>
          </td>";

  $jatko = 0;
}
