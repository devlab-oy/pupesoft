<?php

$ostotilaus = hae_lasku($laskurow['tunnus']);
$ostotilaus['tilausrivit'] = hae_laskun_tilausrivit($laskurow['tunnus'], array('O'), generoi_sorttauskentta($yhtiorow["ostotilauksen_jarjestys"]), $yhtiorow['ostotilauksen_jarjestys_suunta']);

if (empty($ostotilaus['tilausrivit'])) {
  echo "<font class='error'>".t("Lähetettäviä tilausrivejä ei löydy")."</font><br/>";
}
else {
  $xml_editilaus = $pupe_root_polku."/dataout/xml-order-$laskurow[tunnus].xml";

  $xml = new DOMDocument();
  $order = $xml->createElement('Order');

  $order_type = $xml->createElement('orderType');
  $order_type->nodeValue = "Purchase order";
  $order->appendChild($order_type);

  $BuyerPartyName = $xml->createElement('BuyerPartyName');
  $BuyerPartyName->nodeValue = $yhtiorow["nimi"];
  $order->appendChild($BuyerPartyName);

  $BuyerPartyIdentification = $xml->createElement('BuyerPartyIdentification');
  $BuyerPartyIdentification->nodeValue = $yhtiorow["ovttunnus"];
  $order->appendChild($BuyerPartyIdentification);

  $sales_order = $xml->createElement('OrderID');
  $sales_order->nodeValue = $ostotilaus['tunnus'];
  $order->appendChild($sales_order);

  $IssueDate = $xml->createElement('IssueDate');
  $IssueDate->nodeValue = $ostotilaus['toimaika'];
  $order->appendChild($IssueDate);

  $SellerSupplierPartyName = $xml->createElement('SellerSupplierPartyName');
  $SellerSupplierPartyName->nodeValue = $toimirow["nimi"];
  $order->appendChild($SellerSupplierPartyName);

  $SellerSupplierPartyIdentification = $xml->createElement('SellerSupplierPartyIdentification');
  $SellerSupplierPartyIdentification->nodeValue = $toimirow["ovttunnus"];
  $order->appendChild($SellerSupplierPartyIdentification);

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

    //  Tarkastetaan olisiko toimittajalla tuotenumero!
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

  // tarvitaan  $ftphost $ftpuser $ftppass $ftppath $ftpfile
  // palautetaan $palautus ja $syy

  $ftphost = $toimirow['edi_palvelin'];
  $ftpuser = $toimirow['edi_kayttaja'];
  $ftppass = $toimirow['edi_salasana'];
  $ftppath = $toimirow['edi_polku'];
  $ftpfile = $xml_editilaus;

  // Portti
  if (isset($toimirow['edi_portti'])) $ftpport = $toimirow['edi_portti'];

  require "inc/sftp-send.inc";

  if ($palautus == 0) {
    echo "<font class='message'>".t("Tilaus lähetettiin onnistuneesti")."!</font><br>";

    $sqlquery = "UPDATE lasku
                 SET kirjoitin = 'edi'
                 WHERE yhtio = '$kukarow[yhtio]'
                 AND tunnus  = '$laskurow[tunnus]'";
    $result = pupe_query($sqlquery);
  }
  else {
    echo "<font class='error'>".t("Tilauksen lähetys epäonnistui")."!</font><br>";
  }
}

// Nollataan tarkoituksella lopetusmuuttuja
$lopetus = "";
