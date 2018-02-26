<?php

$ostotilaus = hae_lasku($laskurow['tunnus']);
$ostotilaus['tilausrivit'] = hae_laskun_tilausrivit($laskurow['tunnus'], array('O'), generoi_sorttauskentta($yhtiorow["ostotilauksen_jarjestys"]), $yhtiorow['ostotilauksen_jarjestys_suunta']);

if (empty($ostotilaus['tilausrivit'])) {
  echo "<font class='error'>".t("Lähetettäviä tilausrivejä ei löydy")."</font><br/>";
}
else {
  $xml_editilaus = $pupe_root_polku."/dataout/xml-order-BuyerCustomerParty.xml";

  $xml = new DOMDocument();
  $order = $xml->createElement('Order');

  $order_type = $xml->createElement('orderType');
  $order_type->nodeValue = "Purchase order";
  $order->appendChild($order_type);

  $PartyName = $xml->createElement('PartyName');
  $PartyName->nodeValue = $yhtiorow["nimi"];
  $order->appendChild($PartyName);

  $PartyIdentification = $xml->createElement('PartyIdentification');
  $PartyIdentification->nodeValue = $yhtiorow["ovttunnus"];
  $order->appendChild($PartyIdentification);

  $sales_order = $xml->createElement('OrderID');
  $sales_order->nodeValue = $ostotilaus['tunnus'];
  $order->appendChild($sales_order);

  $IssueDate = $xml->createElement('IssueDate');
  $IssueDate->nodeValue = $ostotilaus['toimaika'];
  $order->appendChild($IssueDate);

  $sales_order = $xml->createElement('PartyIdentificationID');
  $sales_order->nodeValue = $yhtiorow['ovttunnus'];
  $order->appendChild($sales_order);

  $delivery = $xml->createElement('Delivery');

  $delivery_name = $xml->createElement('Name');
  $delivery_name->nodeValue = ($ostotilaus['toim_nimi']);
  $delivery->appendChild($delivery_name);

  $delivery_street = $xml->createElement('Street');
  $delivery_street->nodeValue = ($ostotilaus['toim_osoite']);
  $delivery->appendChild($delivery_street);

  $delivery_zip = $xml->createElement('Zip');
  $delivery_zip->nodeValue = ($ostotilaus['toim_postino']);
  $delivery->appendChild($delivery_zip);

  $delivery_city = $xml->createElement('City');
  $delivery_city->nodeValue = ($ostotilaus['toim_postitp']);
  $delivery->appendChild($delivery_city);

  $order->appendChild($delivery);

  $positions = $xml->createElement('Positions');

  foreach ($ostotilaus['tilausrivit'] as $tilausrivi) {
    $article = $xml->createElement('article');

    //  Tarkastetaan olisiko toimittajalla yksikkö!
    $query = "SELECT toim_tuoteno
              FROM tuotteen_toimittajat
              WHERE yhtio      = '{$kukarow['yhtio']}'
              AND tuoteno      = '{$tilausrivi['tuoteno']}'
              AND liitostunnus = '{$ostotilaus['liitostunnus']}'";
    $rarres = pupe_query($query);
    $rarrow   = mysql_fetch_assoc($rarres);

    $articleno = $xml->createElement('articleNo');

    if (!empty($rarrow['toim_tuoteno'])) {
      $articleno->nodeValue = $rarrow['toim_tuoteno'];
    }
    else {
      $articleno->nodeValue = $tilausrivi['tuoteno'];
    }

    $article_quantity = $xml->createElement('articleQuantity');
    $article_quantity->nodeValue = $tilausrivi['tilkpl'];

    $article->appendChild($articleno);
    $article->appendChild($article_quantity);

    $positions->appendChild($article);
  }

  $order->appendChild($positions);

  $xml->appendChild($order);

  $xml->save($xml_editilaus);

  echo "<table>";
  echo "<tr><th>".t("Tallenna ostotilaus").":</th>";
  echo "<form method='post' action='{$palvelin2}tilauskasittely/tilaus_osto.php'>";
  echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
  echo "<input type='hidden' name='kaunisnimi' value='".t('Osto')."_{$laskurow["tunnus"]}.xml'>";
  echo "<input type='hidden' name='filenimi' value='$xml_editilaus'>";
  echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
  echo "</table><br>";
}

// Nollataan tarkoituksella lopetusmuuttuja
$lopetus = "";
