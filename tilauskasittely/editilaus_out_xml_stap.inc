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
  $order_type->nodeValue = "Ostotilaus";
  $order->appendChild($order_type);

  $shipping = $xml->createElement('shipping');

  $shipping_street = $xml->createElement('street');
  $shipping_street->nodeValue = 5555;
  $shipping->appendChild($shipping_street);

  $shipping_name = $xml->createElement('nimi');
  $shipping_name->nodeValue = 123;
  $shipping->appendChild($shipping_name);

  $order->appendChild($shipping);

  $sales_order = $xml->createElement('ID');
  $sales_order->nodeValue = $ostotilaus['tunnus'];
  $order->appendChild($sales_order);

  $IssueDate = $xml->createElement('IssueDate');
  $IssueDate->nodeValue = $ostotilaus['toimaika'];
  $order->appendChild($IssueDate);

  $PartyIdentification = $xml->createElement('PartyIdentification');
  $PartyIdentification->nodeValue = $yhtiorow["ovttunnus"];
  $order->appendChild($PartyIdentification);

  $PartyName = $xml->createElement('PartyName');
  $PartyName->nodeValue = $yhtiorow["nimi"];
  $order->appendChild($PartyName);

  $Delivery = $xml->createElement('Delivery');
  $Delivery2 = $xml->createElement('Delivery_2');

  $Delivery->nodeValue = $yhtiorow["nimi"];
  $order->appendChild($Delivery);

  $address_for_shipment = $xml->createElement('addressForShipment');
  $address_for_shipment->nodeValue = "802005050 100002184";
  $order->appendChild($address_for_shipment);

  $positions = $xml->createElement('Positions');

  foreach ($ostotilaus['tilausrivit'] as $tilausrivi) {
    $article = $xml->createElement('article');

    $articleno = $xml->createElement('articleNo');
    $articleno->nodeValue = $tilausrivi['tuoteno'];

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
