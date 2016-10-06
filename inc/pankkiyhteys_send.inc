<?php

$tuetut_pankit = tuetut_pankit();
$dataout       = "{$pupe_root_polku}/dataout";

echo "<h2>" . t('Aineistojen lähetys pankkiin') . "</h2>";

if ($tee == 'laheta') {
  $pankkiyhteys = hae_pankkiyhteys($pankkiyhteys_tunnus);
  $pankki       = $tuetut_pankit[$pankkiyhteys['pankki']];

  switch ($file_type) {
  case 'LAHLASKUT':
    $polku = "{$dataout}/{$pankki['lyhyt_nimi']}_error";
    $files = glob("{$polku}/laskutus*");

    $onnistuneet = 0;
    foreach ($files as $file) {
      $data = base64_encode(file_get_contents($file));

      $params = array(
        "pankkiyhteys_tunnus"   => $_COOKIE[$cookie_tunnus],
        "pankkiyhteys_salasana" => $_COOKIE[$cookie_secret],
        "file_type"             => $file_type,
        "maksuaineisto"         => $data,
      );

      $response = sepa_upload_file($params);

      if ($response) {
        $onnistuneet++;

        rename($file, "{$dataout}/" . basename($file));
      }
      else {
        echo "<div class='error'>";
        echo t("Tiedoston %s käsittelyssä tapahtui virhe", '', "{$polku}/{$file}");
        echo "</div>";
      }
    }

    echo "<div>";
    echo t("%s/%s tiedostoa lähetetty onnistuneesti pankkiin", '', $onnistuneet, count($files));
    echo "</div>";

    break;
  default:
    echo "<div class='error'>";
    echo t("Tuntematon tiedostotyyppi");
    echo "</div>";
    break;
  }
}

if ($tee == 'valitse') {
  $pankkiyhteys = hae_pankkiyhteys($pankkiyhteys_tunnus);
  $pankki       = $tuetut_pankit[$pankkiyhteys['pankki']];
  $files        = glob("{$dataout}/{$pankki['lyhyt_nimi']}_error/laskutus*");

  echo "<div>";
  echo t("Pankkiin lähetettäviä laskuja on %s kappaletta", '', count($files));
  echo "</div>";

  echo "<div>";
  echo "<form>";
  echo "<input type='hidden' name='toim' value='{$toim}'>";
  echo "<input type='hidden' name='tee' value='laheta'>";
  echo "<input type='hidden' name='file_type' value='LAHLASKUT'>";
  echo "<input type='hidden' name='pankkiyhteys_tunnus' value='{$pankkiyhteys_tunnus}'>";

  $disabled = count($files) < 1 ? ' disabled' : '';
  echo "<input type='submit' value='" . t("Lähetä") . "'{$disabled}>";

  echo "</form>";
  echo "</div>";
}
