<?php

require "../inc/parametrit.inc";

// tehd‰‰n tiedostolle uniikki nimi
$filename = "$pupe_root_polku/datain/$tyyppi-order-".md5(uniqid(rand(), true)).".txt";

echo "<script type='text/javascript'>
    $(document).ready(function() {

      $('#valinta').attr('selectedIndex', -1);

      $('#valinta').change(function()
      {
        keijo = ($('#valinta option:selected').val());

        if (keijo == 'multi' || keijo == 'multi_asiakasnro') {
          $('#keijo').show();
        }
        else {
          $('#keijo').hide();
        }
      });
    });
</script>";

if (move_uploaded_file($_FILES['userfile']['tmp_name'], $filename)) {

  $path_parts = pathinfo($filename);

  if ($path_parts["extension"] != "" and strtoupper($path_parts["extension"]) != "TXT") {
    echo "<font class='error'>".t("Tiedosto")." $filename ".t("ei tunnu aineistolta")."!<br>".t("Pit‰‰ olla .txt (nyt")." $path_parts[extension]).<br>".t("Tarkista tiedosto")."!<br></font>";
    exit;
  }

  // Muutetaan oikeaan merkistˆˆn
  $encoding = exec("file -b --mime-encoding '$filename'");

  if (!PUPE_UNICODE and $encoding != "" and mb_strtoupper($encoding) != 'ISO-8859-1') {
    exec("recode $encoding..ISO-8859-1 '$filename'");
  }
  elseif (PUPE_UNICODE and $encoding != "" and mb_strtoupper($encoding) != 'UTF-8') {
    exec("recode $encoding..UTF8 '$filename'");
  }

  echo "<font class='message'>".t("K‰sittelen")." $tyyppi ".t("tiedoston")."</font><br><br>";

  if ($tyyppi == 'multi' or $tyyppi == 'multi_asiakasnro') {
    // tarvitaan $filename
    require "inc/tilaus_in_multi.inc";
  }

  if ($tyyppi == 'pos') {
    // tarvitaan $filename
    require "inc/tilaus_in.inc";
  }

  if ($tyyppi == 'edi') {
    // tarvitaan $filename
    echo "<pre>";
    require "editilaus_in.inc";
    echo "</pre>";
  }

  if ($tyyppi == 'futursoft') {
    // tarvitaan $filename
    echo "<pre>";
    $edi_tyyppi = "futursoft";
    require "editilaus_in.inc";
    echo "</pre>";
  }

  if ($tyyppi == 'magento' or $tyyppi == 'presta' or $tyyppi == 'ahkio' or $tyyppi == 'woo') {
    // tarvitaan $filename
    echo "<pre>";
    $edi_tyyppi = $tyyppi;
    require "editilaus_in.inc";
    echo "</pre>";
  }

  if ($tyyppi == 'finvoice') {
    // tarvitaan $filename
    echo "<pre>";
    $edi_tyyppi = "magento";
    $tilauksen_lahde = "finvoice";
    require "editilaus_in.inc";
    echo "</pre>";
  }

  if ($tyyppi == 'edifact911') {
    // tarvitaan $filename
    echo "<pre>";
    $edi_tyyppi = "edifact911";
    require "editilaus_in.inc";
    echo "</pre>";
  }

  if ($tyyppi == 'yct') {
    // tarvitaan $filename
    require "inc/tilaus_in.inc";
  }

  if ($tyyppi == 'asnui') {

    if (copy($filename, $teccomkansio.'/'.$path_parts["basename"])) {
      require "sisaanlue_teccom_asn.php";
    }
    else {
      echo "Kopiointi ep‰onnistui!";
    }
  }
}

else {

  echo "<font class='head'>".t("Tilausten sis‰‰nluku")."</font><hr>";

  echo "<form enctype='multipart/form-data' name='sendfile' method='post'>

    <table>
    <tr>
      <th>".t("Valitse tiedosto")."</th>
      <td><input type='file' name='userfile'></td>
    </tr>
    <tr>
      <th>".t("Tiedoston tyyppi")."</th>
      <td><select name='tyyppi' id='valinta'>
         <option value='edi'>".t("Editilaus")."</option>
         <option value='ahkio'>Ahkio</option>
         <option value='futursoft'>Futursoft</option>
         <option value='magento'>Magento</option>
         <option value='finvoice'>Finvoice</option>
         <option value='presta'>PrestaShop</option>
         <option value='woo'>WooCommerce</option>
         <option value='pos'>".t("Kassap‰‰te")."</option>
         <option value='yct'>Yamaha Center</option>
         <option value='edifact911'>Orders 91.1</option>
         <option value='multi'>".t("Useita asiakkaita")."</option>
         <option value='multi_asiakasnro'>".t("Useita asiakkaita asiakasnumerolla")."</option>
         <option value='asnui'>".t("ASN-sanoma")."</option>
        </select>";
  echo "<div id='keijo' style='display: none;'>
      <br>".t("Tilaukset suoraan valmis-tilaan")." <input type='checkbox' name='tilaus_valmiiksi' >
      <br>".t("Tilauksesta oma lasku")." <input type='checkbox' name='tilaus_ketjutus' >
      <br>".t("Ei tilausvahvistusta")." <input type='checkbox' name='tilaus_novahvistus' ></div>";
  echo "</td>";
  echo "</tr>";
  echo "</table>";

  echo "<br><input type='submit' value='".t("K‰sittele tiedosto")."'>";
  echo "</form>";

}

require "inc/footer.inc";
