<?php

// Kutsutaanko CLI:stä
$php_cli = FALSE;

if (php_sapi_name() == 'cli') {
  $php_cli = TRUE;
}

if ($php_cli) {

  if (!isset($argv[1]) or $argv[1] == '') {
    echo "Anna yhtiö!!!\n";
    die;
  }

  if (!isset($argv[2]) or $argv[2] == '') {
    echo "Anna tiedosto!!!\n";
    die;
  }

  date_default_timezone_set('Europe/Helsinki');

  // otetaan includepath aina rootista
  ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(dirname(__FILE__))).PATH_SEPARATOR."/usr/share/pear");
  error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
  ini_set("display_errors", 0);

  require "inc/connect.inc";
  require "inc/functions.inc";
  require "inc/luo_ostotilausotsikko.inc";

  $kukarow['yhtio'] = $argv[1];
  $filu             = $argv[2];

  // Pupeasennuksen root
  $pupe_root_polku = dirname(dirname(dirname(__FILE__)));

  $yhtiorow = hae_yhtion_parametrit($kukarow["yhtio"]);

  if ($yhtiorow["yhtio"] == "") {
    die ("Yhtiö $kukarow[yhtio] ei löydy!");
  }

  $kukarow = hae_kukarow('admin', $yhtiorow['yhtio']);

  $tee = 'aja';
}
else {
  require "../../inc/parametrit.inc";
  require "inc/luo_ostotilausotsikko.inc";

  echo "<font class='head'>".t("Relex-ostoehdotuksen sisäänluku")."</font><hr>";

  if (isset($tee) and trim($tee) == 'aja') {
    if (is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE) {
      if ($_FILES['userfile']['size'] == 0) {
        die ("<font class='error'><br>".t("Tiedosto on tyhjä")."!</font>");
      }
      else {
        $filu = $_FILES['userfile']['tmp_name'];
      }
    }
  }
}

if (isset($tee) and trim($tee) == 'aja') {
  /* Tiedostomuoto:
    COLUMN        TYPE  COMMENT
    order_number  INT    Row level unique order number
    product_code  TXT    Identifier of the product/item
    quantity      FLOAT  Number of products to be ordered
    order_type    TXT    NORMAL, EXTRA ORDER, ALLOCATION, ORDER IN ADVANCE, INITIAL REPLENISHMENT
    location_code  TXT    Identifier for location/warehouse
    supplier_code  TXT    Supplier code for proposed item
    delivery_date  DATE  Expected delivery date for order
    comment_1      TXT    Comment or identifier set in RELEX user interface
    comment_2      TXT    Comment or identifier set in RELEX user interface
    comment_3      TXT    Comment or identifier set in RELEX user interface
    comment_4      TXT    Comment or identifier set in RELEX user interface
    comment_5      TXT    Comment or identifier set in RELEX user interface
    order_date    DATE  When should the item be ordered
  */

  $rivit = file($filu);

  // Heitetään eka rivi roskiin
  array_shift($rivit);

  foreach ($rivit as $line) {
    $fields = explode(";", $line);

    $order_number  = pupesoft_cleanstring($fields[0]);
    $product_code  = pupesoft_cleanstring($fields[1]);
    $quantity      = pupesoft_cleannumber($fields[2]);
    $order_type    = pupesoft_cleanstring($fields[3]);
    $location_code = pupesoft_cleanstring($fields[4]);
    $supplier_code = pupesoft_cleanstring($fields[5]);
    $delivery_date = pupesoft_cleanstring($fields[6]);
    $comment_1     = pupesoft_cleanstring($fields[7]);
    $comment_2     = pupesoft_cleanstring($fields[8]);
    $comment_3     = pupesoft_cleanstring($fields[9]);
    $comment_4     = pupesoft_cleanstring($fields[10]);
    $comment_5     = pupesoft_cleanstring($fields[11]);
    $order_date    = pupesoft_cleanstring($fields[12]);

    // Poistetaan maa-etuliitteet
    $product_code  = substr($product_code, 3);
    $location_code = substr($location_code, 3);
    $supplier_code = substr($supplier_code, 3);

    // Normaali tilaus
    $tilaustyyppi  = 2;

    // Haetaan tuotteen tiedot
    $query = "SELECT *
              FROM tuote
              WHERE yhtio = '$kukarow[yhtio]'
              AND tuoteno = '{$product_code}'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) == 1) {
      $tuote = mysql_fetch_assoc($result);
    }
    else {
      echo "Tuotetta '$product_code' ei löydy. Ohitetaan rivi!<br>";
      continue;
    }

    // Haetaan toimittajan tiedot
    $query = "SELECT *
              FROM toimi
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  = '{$supplier_code}'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) == 1) {
      $toimittaja = mysql_fetch_assoc($result);
    }
    else {
      echo "Toimittajaa '$supplier_code' ei löydy. Ohitetaan rivi!<br>";
      continue;
    }

    // Haetaan varaston tiedot
    $query = "SELECT *
              FROM varastopaikat
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  = '{$location_code}'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) == 1) {
      $varasto = mysql_fetch_assoc($result);
    }
    else {
      echo "Varastoa '$location_code' ei löydy. Ohitetaan rivi!<br>";
      continue;
    }

    // Haetaan varaston tiedot
    $query = "SELECT *
              FROM tuotteen_toimittajat
              WHERE yhtio      = '$kukarow[yhtio]'
              AND tuoteno      = '{$tuote["tuoteno"]}'
              AND liitostunnus = '{$toimittaja["tunnus"]}'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) == 1) {
      $tuotteentoimittaja = mysql_fetch_assoc($result);
    }
    else {
      echo "Tuotteen toimittajatietoa ei löydy. Ohitetaan rivi!<br>";
      continue;
    }

    if (strtotime($order_date) < time()) {
      $ehdotus_pvm = date("Y-m-d");
    }
    else {
      $ehdotus_pvm = $order_date;
    }

    // Lasketaan rivin arvioitu toimitusaika
    if ($tuotteentoimittaja["toimitusaika"] != 0 and $tuotteentoimittaja["toimitusaika"] != '') {
      $ehdotus_pvm = date('Y-m-d', strtotime($ehdotus_pvm) + $tuotteentoimittaja["toimitusaika"] * 24 * 60 * 60);
    }
    elseif ($toimittaja["oletus_toimaika"] != 0 and $toimittaja["oletus_toimaika"] != '') {
      $ehdotus_pvm = date('Y-m-d', strtotime($ehdotus_pvm) + $toimittaja["oletus_toimaika"] * 24 * 60 * 60);
    }

    // Löytyykö sopiva tilaus?
    $query = "SELECT *
              FROM lasku
              WHERE yhtio       = '$kukarow[yhtio]'
              AND tila          = 'O'
              AND alatila       = ''
              AND chn           = 'GEN'
              AND liitostunnus  = '{$toimittaja["tunnus"]}'
              and toim_nimi     = '{$varasto["nimi"]}'
              AND toim_nimitark = '{$varasto["nimitark"]}'
              AND toim_osoite   = '{$varasto["osoite"]}'
              AND toim_postino  = '{$varasto["postino"]}'
              AND toim_postitp  = '{$varasto["postitp"]}'
              AND toim_maa      = '{$varasto["maa"]}'
              AND varasto       = '{$varasto["tunnus"]}'
              AND tilaustyyppi  = '{$tilaustyyppi}'";
    $result = pupe_query($query);

    // Ei löydy, tehdään uus tilaus
    if (mysql_num_rows($result) == 0) {

      $params = array(
        'liitostunnus'            => $toimittaja["tunnus"],
        'nimi'                    => $varasto['nimi'],
        'nimitark'                => $varasto['nimitark'],
        'osoite'                  => $varasto['osoite'],
        'postino'                 => $varasto['postino'],
        'postitp'                 => $varasto['postitp'],
        'maa'                     => $varasto['maa'],
        'varasto'                 => $varasto['tunnus'],
        'myytil_toimaika'         => $ehdotus_pvm,
        'tilaustyyppi'            => $tilaustyyppi,
        'myytil_viesti'           => t("Relex-ostotilaus"),
        'ostotilauksen_kasittely' => "GEN", // tällä erotellaan generoidut ja käsin tehdyt ostotilaukset
      );

      $laskurow = luo_ostotilausotsikko($params);
      $kukarow['kesken'] = $laskurow['tunnus'];
    }
    else {
      $laskurow = mysql_fetch_assoc($result);
      $kukarow['kesken'] = $laskurow['tunnus'];
    }

    $params = array(
      "trow"      => $tuote,
      "laskurow"  => $laskurow,
      "kpl"       => $quantity,
      "tuoteno"   => $tuote["tuoteno"],
      "hinta"     => 0,
      "varasto"   => $varasto['tunnus'],
      "kommentti" => "",
      "toimaika"  => $ehdotus_pvm,
      "kerayspvm" => $ehdotus_pvm,
      "toim"      => "OSTO",
    );

    lisaa_rivi($params);

    echo "Lisätään tuote {$tuote["tuoteno"]} $quantity {$tuote["yksikko"]} tilaukselle {$laskurow["tunnus"]}.<br>";
  }
}

if (!$php_cli) {
  echo "<form method='post' name='sendfile' enctype='multipart/form-data'>";
  echo "<input type='hidden' name='tee' value='aja'>";
  echo "<br><table>
        <tr><th>".t("Valitse tiedosto").":</th>
        <td><input name='userfile' type='file'></td>
        <td class='back'><input type='submit' value='".t("Käsittele")."'></td>
      </tr>
      </table>
      </form>";

  require "inc/footer.inc";
}
