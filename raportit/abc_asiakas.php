<?php

if (isset($_POST["exceltee"])) {
  if ($_POST["exceltee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
  if ($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
}

require "../inc/parametrit.inc";

if (isset($exceltee) and $exceltee == "lataa_tiedosto") {
  readfile("/tmp/".$tmpfilenimi);
  exit;
}
else {
  // tämä skripti käyttää slave-tietokantapalvelinta
  $useslave = 1;

  if ($toim == "") {
    $toim = "myynti";
  }

  if ($toim == "kate") {
    $abcwhat = "kate";
    $abcchar = "AK";
  }
  elseif ($toim == "kpl") {
    $abcwhat = "kpl";
    $abcchar = "AP";
  }
  elseif ($toim == "rivia") {
    $abcwhat = "rivia";
    $abcchar = "AR";
  }
  else {
    $abcwhat = "summa";
    $abcchar = "AM";
  }

  if ($tee == '') {
    echo "<font class='head'>".t("ABC-Analysointia asiakkaille")."<hr></font>";
    echo "<font class='message'>";

    if ($toim == "myynti") {
      echo t("ABC-luokat myynnin mukaan");
    }
    elseif ($toim == "kpl") {
      echo t("ABC-luokat kappaleiden mukaan");
    }
    elseif ($toim == "rivia") {
      echo t("ABC-luokat myyntirivien mukaan");
    }
    else {
      echo t("ABC-luokat katteen mukaan");
    }

    echo "<br><br>".t("Valitse toiminto").":<br><br>";
    echo "</font>";
    echo "<li><a class='menu' href='$PHP_SELF?tee=YHTEENVETO&toim=$toim'         >".t("ABC-luokkayhteenveto")."</a><br>";
    echo "<li><a class='menu' href='$PHP_SELF?tee=OSASTOTRY&toim=$toim'          >".t("Asiakasosaston tai asiakasryhmän luokat")."</a><br>";
    echo "<li><a class='menu' href='$PHP_SELF?tee=LUOKKA&toim=$toim'             >".t("Luokan asiakkaat")."</a><br>";
    echo "<li><a class='menu' href='$PHP_SELF?tee=PITKALISTA&toim=$toim'         >".t("Kaikki tiedot Excel-tiedostoon")."</a><br>";
  }

  $asiakasanalyysi = TRUE;

  list($ryhmanimet, $ryhmaprossat, $kiertonopeus_tavoite, $palvelutaso_tavoite, $varmuusvarasto_pv, $toimittajan_toimitusaika_pv) = hae_ryhmanimet($abcchar);

  // jos kaikki tarvittavat tiedot löytyy mennään queryyn
  if ($tee == 'YHTEENVETO') {
    require "abc_yhteenveto.php";
  }

  if ($tee == 'OSASTOTRY') {
    require "abc_osastotry.php";
  }

  if ($tee == 'LUOKKA') {
    require "abc_luokka.php";
  }

  if ($tee == 'PITKALISTA') {
    require "abc_kaikki_taullask.php";
  }

  require "../inc/footer.inc";
}
