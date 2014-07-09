<?php

const SEPA_OSOITE = "https://sepa.devlab.fi/api/";
const ACCESS_TOKEN = "Bexvxb10H1XBT36x42Lv8jEEKnA6";

require("inc/parametrit.inc");

echo "<font class='head'>" . t('SEPA-pankkiyhteys admin') . "</font>";
echo "<hr>";

if (!isset($_SERVER["HTTPS"]) or $_SERVER["HTTPS"] != 'on') {
  echo "<font class='error'>";
  echo t("Voit k‰ytt‰‰ pankkiyhteytt‰ vain salatulla yhteydell‰!");
  echo "</font>";
  exit;
}

$tee = isset($tee) ? $tee : '';
