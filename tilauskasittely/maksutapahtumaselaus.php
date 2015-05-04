<?php

require "../inc/parametrit.inc";

echo "<h1 class='head'>" . t("Maksutapahtumaselaus") . "<h1><hr>";

piirra_hakuformi();

require "inc/footer.inc";

function piirra_hakuformi() {
  echo "<form name='hakuformi' id='hakuformi'>";
  echo "<input type='hidden' name='rajaus[limit]' value='50'>";
  echo "<table>";

  echo "<tr>";
  echo "<th><label for='rajaus_alku_paiva'>" . t("Syötä alkupäivämäärä") .
       " (Pp-Kk-Vvvv)</label></th>";
  echo "<td>";
  echo "<input type='number'
               name='rajaus[alku][paiva]'
               id='rajaus_alku_paiva'
               min='1'
               max='31'>";
  echo "<input type='number'
               name='rajaus[alku][kuukausi]'
               id='rajaus_alku_kuukausi'
               min='1'
               max='12'>";
  echo "<input type='number'
               name='rajaus[alku][vuosi]'
               id='rajaus_alku_vuosi'
               min='1'
               max='9999'>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th><label for='rajaus_loppu_paiva'>" . t("Syötä loppupäivämäärä") .
       " (Pp-Kk-Vvvv)</label></th>";
  echo "<td>";
  echo "<input type='number'
               name='rajaus[loppu][paiva]'
               id='rajaus_loppu_paiva'
               min='1'
               max='31'>";
  echo "<input type='number'
               name='rajaus[loppu][kuukausi]'
               id='rajaus_loppu_kuukausi'
               min='1'
               max='12'>";
  echo "<input type='number'
               name='rajaus[loppu][vuosi]'
               id='rajaus_loppu_vuosi'
               min='1'
               max='9999'>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th><label for='rajaus_limit'>" . t("Näytä %d uusinta tilausta", "", 500) . "</label></th>";
  echo "<td><input type='checkbox' name='rajaus[limit]' id='rajaus_limit' value='500'></td>";
  echo "<td class='back'>";
  echo "<input type='submit' value='Hae'>";
  echo "</td>";
  echo "</tr>";

  echo "</table>";
  echo "</form>";
}
