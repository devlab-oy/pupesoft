<?php
/**
 * SOAP-clientin wrapperi Magento-verkkokaupan päivitykseen
 *
 * Käytetään suoraan rajapinnat/tuote_export.php tiedostoa, jolla haetaan
 * tarvittavat tiedot pupesoftista.
 *
 * Lisää tai päivittää kategoriat, tuotteet ja saldot.
 * Hakee maksettuja tilauksia pupesoftiin.
 */

class MagentoClient {

  /**
   * Kutsujen määrä multicall kutsulla
   */
  const MULTICALL_BATCH_SIZE = 100;

  /**
   * Logging päällä/pois
   */
  const LOGGING = true;

  /**
   * Visibility
   */
  const NOT_VISIBLE_INDIVIDUALLY = 1;
  const CATALOG                  = 2;
  const SEARCH                   = 3;
  const CATALOG_SEARCH           = 4;

  /**
   * Status
   */
  const ENABLED  = 1;
  const DISABLED = 2;

  /**
   * Soap client
   */
  private $_proxy;

  /**
   * Soap clientin sessio
   */
  private $_session;

  /**
   * Magenton oletus attributeSet
   */
  private $_attributeSet;

  /**
   * Tuotekategoriat
   */
  private $_category_tree;

  /**
   * Verkkokaupan veroluokan tunnus
   */
  private $_tax_class_id = 0;

  /**
   * Verkkokaupan "root" kategorian tunnus, tämän alle lisätään kaikki tuoteryhmät
   */
  private $_parent_id = 3;

  /**
   * Verkkokaupan "hinta"-kenttä, joko myymalahinta tai myyntihinta
   */
  private $_hintakentta = "myymalahinta";

  /**
   * Verkkokaupassa käytettävät tuoteryhmät, default tuoteryhmä tai tuotepuu
   */
  private $_kategoriat = "tuoteryhma";

  /**
   * Onko "Category access control"-moduli on asennettu? Oletukena ei oo.
   */
  private $_categoryaccesscontrol = FALSE;

  /**
   * Configurable-tuotteella käytettävä nimityskenttä, oletuksena nimitys
   */
  private $_configurable_tuote_nimityskentta = "nimitys";

  /**
   * Tämän yhteyden aikana sattuneiden virheiden määrä
   */
  private $_error_count = 0;


  /**
   * Constructor
   *
   * @param string  $url  SOAP Web service URL
   * @param string  $user API User
   * @param string  $pass API Key
   */
  function __construct($url, $user, $pass) {

    try {
      $this->_proxy = new SoapClient($url);
      $this->_session = $this->_proxy->login($user, $pass);
      $this->log("Magento päivitysskripti aloitettu");
    }
    catch (Exception $e) {
      $this->_error_count++;
      $this->log("Virhe! Magento-class init failed", $e);
    }
  }


  /**
   * Destructor
   */
  function __destruct() {
    $this->log("Päivitysskripti päättyi\n");
  }

  /**
   * Lisää kaikki tai puuttuvat kategoriat Magento-verkkokauppaan.
   *
   * @param array   $dnsryhma Pupesoftin tuote_exportin palauttama array
   * @return int             Lisättyjen kategorioiden määrä
   */
  public function lisaa_kategoriat(array $dnsryhma) {

    $this->log("Lisätään kategoriat");

    $categoryaccesscontrol = $this->_categoryaccesscontrol;

    $selected_category = $this->_kategoriat;

    if ($selected_category != 'tuoteryhma') {
      $this->log("Ohitetaan kategorioiden luonti. Kategoriatyypiksi valittu tuotepuu.");
      return 0;
    }

    $parent_id = $this->_parent_id; // Magento kategorian tunnus, jonka alle kaikki tuoteryhmät lisätään (pitää katsoa magentosta)
    $count = 0;

    // Loopataan osastot ja tuoteryhmat
    foreach ($dnsryhma as $kategoria) {

      try {
        // Haetaan kategoriat joka kerta koska lisättäessä puu muuttuu
        $category_tree = $this->getCategories();

        $kategoria['try_fi'] = utf8_encode($kategoria['try_fi']);
        // Kasotaan löytyykö tuoteryhmä
        if (!$this->findCategory($kategoria['try_fi'], $category_tree['children'])) {

          // Lisätään kategoria, jos ei löytynyt
          $category_data = array(
            'name'                  => $kategoria['try_fi'],
            'is_active'             => 1,
            'position'              => 1,
            'default_sort_by'       => 'position',
            'available_sort_by'     => 'position',
            'include_in_menu'       => 1
          );

          if ($categoryaccesscontrol) {
            // HUOM: Vain jos "Category access control"-moduli on asennettu
            $category_data['accesscontrol_show_group'] = 0;
          }
          // Kutsutaan soap rajapintaa
          $category_id = $this->_proxy->call($this->_session, 'catalog_category.create',
            array($parent_id, $category_data)
          );

          $count++;

          $this->log("Lisättiin kategoria {$kategoria['try_fi']}");
        }
      }
      catch (Exception $e) {
        $this->_error_count++;
        $this->log("Virhe! Kategoriaa {$kategoria['try_fi']} ei voitu lisätä", $e);
      }
    }

    $this->_category_tree = $this->getCategories();
    $this->log("$count kategoriaa lisätty");

    return $count;
  }

  /**
   * Lisää päivitettyjä Simple tuotteita Magento-verkkokauppaan.
   *
   * @param array   $dnstuote Pupesoftin tuote_exportin palauttama tuote array
   * @return int               Lisättyjen tuotteiden määrä
   */
  public function lisaa_simple_tuotteet(array $dnstuote, array $individual_tuotteet) {

    $this->log("Lisätään tuotteita (simple)");

    $hintakentta = $this->_hintakentta;

    $selected_category = $this->_kategoriat;

    // Tuote countteri
    $count = 0;

    try {
      // Tarvitaan kategoriat
      $category_tree = $this->getCategories();

      // Populoidaan attributeSet
      $this->_attributeSet = $this->getAttributeSet();

      // Haetaan storessa olevat tuotenumerot
      $skus_in_store = $this->getProductList(true);
    }
    catch (Exception $e) {
      $this->_error_count++;
      $this->log("Virhe! Tuotteiden lisäyksessä (simple)", $e);
      return;
    }

    // Lisätään tuotteet erissä
    foreach ($dnstuote as $tuote) {
      $tuote_clean = $tuote['tuoteno'];

      $category_ids = array ();

      if (is_numeric($tuote['tuoteno'])) $tuote['tuoteno'] = "SKU_".$tuote['tuoteno'];

      // Lyhytkuvaus ei saa olla magentossa tyhjä.
      // Käytetään kuvaus kentän tietoja jos lyhytkuvaus on tyhjä.
      if ($tuote['lyhytkuvaus'] == '') {
        $tuote['lyhytkuvaus'] = '&nbsp;';
      }

      $tuote['kuluprosentti'] = ($tuote['kuluprosentti'] == 0) ? '' : $tuote['kuluprosentti'];

      // Etsitään kategoria_id tuoteryhmällä
      if ($selected_category == 'tuoteryhma') {
        $category_ids[] = $this->findCategory(utf8_encode($tuote['try_nimi']), $category_tree['children']);
      }
      else {
        // Etsitään kategoria_id:t tuotepuun tuotepolulla
        $tuotepuun_nodet = $tuote['tuotepuun_nodet'];

        // Lisätään myös tuotepuun kategoriat
        if (isset($tuotepuun_nodet) and count($tuotepuun_nodet) > 0) {
          foreach ($tuotepuun_nodet as $tuotepolku) {
            $category_ids[] = $this->createCategoryTree($tuotepolku);
          }
        }
      }
      // Jos tuote ei oo osa configurable_grouppia, niin niitten kuuluu olla visibleja.
      if (isset($individual_tuotteet[$tuote_clean])) {
        $visibility = self::CATALOG_SEARCH;
      }
      else {
        $visibility = self::NOT_VISIBLE_INDIVIDUALLY;
      }

      $tuote_ryhmahinta_data = array ();

      if (isset($tuote['asiakashinnat']) and count($tuote['asiakashinnat'])> 0) {
        foreach ($tuote['asiakashinnat'] as $asiakashintarivi) {

          $asiakasryhma_nimi = $asiakashintarivi['asiakasryhma'];
          $asiakashinta = $asiakashintarivi['hinta'];

          $asiakasryhma_tunnus = $this->findCustomerGroup(utf8_encode($asiakasryhma_nimi));

          if ($asiakasryhma_tunnus != 0) {
            $tuote_ryhmahinta_data[] = array(
              'websites' => explode(" ", $tuote['nakyvyys']),
              'customer_group_id' => $asiakasryhma_tunnus,
              'qty' => 1,
              'price' => $asiakashinta
            );
          }
        }
      }

      $multi_data = array();

      // Simple tuotteiden parametrit kuten koko ja väri
      foreach($tuote['tuotteen_parametrit'] as $parametri) {
        $key = $parametri['option_name'];
        $multi_data[$key] = $this->get_option_id($key, $parametri['arvo']);
      }



      $tuote_data = array(
          'categories'            => $category_ids,
          'websites'              => explode(" ", $tuote['nakyvyys']),
          'name'                  => utf8_encode($tuote['nimi']),
          'description'           => utf8_encode($tuote['kuvaus']),
          'short_description'     => utf8_encode($tuote['lyhytkuvaus']),
          'weight'                => $tuote['tuotemassa'],
          'status'                => self::ENABLED,
          'visibility'            => $visibility,
          'price'                 => sprintf('%0.2f', $tuote[$hintakentta]),
          'special_price'         => $tuote['kuluprosentti'],
          'tax_class_id'          => $this->getTaxClassID(),
          'meta_title'            => '',
          'meta_keyword'          => '',
          'meta_description'      => '',
          'campaign_code'         => utf8_encode($tuote['campaign_code']),
          'onsale'                => utf8_encode($tuote['onsale']),
          'target'                => utf8_encode($tuote['target']),
          'tier_price'            => $tuote_ryhmahinta_data,
          'additional_attributes' => array('multi_data' => $multi_data),
          'name2'                 => utf8_encode("Secondary name"),
          'pickup_product'        => TRUE,
        );

      // Lisätään tai päivitetään tuote

      // Jos tuotetta ei ole olemassa niin lisätään se
      if (!in_array($tuote['tuoteno'], $skus_in_store)) {
        try {

          $product_id = $this->_proxy->call($this->_session, 'catalog_product.create',
            array(
              'simple',
              $this->_attributeSet['set_id'],
              $tuote['tuoteno'], // sku
              $tuote_data,
            )
          );
          $this->log("Tuote '{$tuote['tuoteno']}' lisätty (simple) " . print_r($tuote_data, true));

          // Pitää käydä tekemässä vielä stock.update kutsu, että saadaan Manage Stock: YES
          $stock_data = array(
            'qty'          => 0,
            'is_in_stock'  => 0,
            'manage_stock' => 1
          );

          $result = $this->_proxy->call(
            $this->_session,
            'product_stock.update',
            array(
              $tuote['tuoteno'], // sku
              $stock_data
            ));
        }
        catch (Exception $e) {
          $this->_error_count++;
          $this->log("Virhe! Tuotteen '{$tuote['tuoteno']}' lisäys epäonnistui (simple) " . print_r($tuote_data, true), $e);
        }
      }
      // Tuote on jo olemassa, päivitetään
      else {
        try {
          $this->_proxy->call($this->_session, 'catalog_product.update',

            array(
              $tuote['tuoteno'], // sku
              $tuote_data)
          );

          // Haetaan tuotteen Magenton ID
          $result = $this->_proxy->call($this->_session, 'catalog_product.info', $tuote['tuoteno']);
          $product_id = $result['product_id'];

          $this->log("Tuote '{$tuote['tuoteno']}' päivitetty (simple) " . print_r($tuote_data, true));

          // Update tier prices
          /*$result = $this->_proxy->call($this->_session, 'product_tier_price.update', array($tuote['tuoteno'], $tuote_ryhmahinta_data));

          $this->log("Tuotteen '{$tuote['tuoteno']}' erikoishinnasto $result päivitetty " . print_r($tuote_ryhmahinta_data, true));*/
        }
        catch (Exception $e) {
          $this->_error_count++;
          $this->log("Virhe! Tuotteen '{$tuote['tuoteno']}' päivitys epäonnistui (simple) " . print_r($tuote_data, true), $e);
        }
      }

      // Haetaan tuotekuvat Pupesoftista
      $tuotekuvat = $this->hae_tuotekuvat($tuote['tunnus']);

      // Lisätään kuvat Magentoon
      $this->lisaa_tuotekuvat($product_id, $tuotekuvat);

      // Lisätään tuote countteria
      $count++;

    }

    $this->log("$count tuotetta päivitetty (simple)");

    // Palautetaan pävitettyjen tuotteiden määrä
    return $count;
  }

  /**
   * Lisää päivitettyjä Configurable tuotteita Magento-verkkokauppaan.
   *
   * @param array   $dnslajitelma Pupesoftin tuote_exportin palauttama tuote array
   * @return int                 Lisättyjen tuotteiden määrä
   */
  public function lisaa_configurable_tuotteet(array $dnslajitelma) {

    $this->log("Lisätään tuotteet (configurable)");

    $count = 0;

    // Populoidaan attributeSet
    $this->_attributeSet = $this->getAttributeSet();

    // Haetaan storessa olevat tuotenumerot
    $skus_in_store = $this->getProductList(true);

    // Tarvitaan kategoriat
    $category_tree = $this->getCategories();

    $hintakentta = $this->_hintakentta;

    // Mitä kenttää käytetään configurable_tuotteen nimenä
    $configurable_tuote_nimityskentta = $this->_configurable_tuote_nimityskentta;

    $selected_category = $this->_kategoriat;

    // Lisätään tuotteet
    foreach ($dnslajitelma as $nimitys => $tuotteet) {

      $category_ids = array ();

      // Jos lyhytkuvaus on tyhjä, käytetään kuvausta?
      if ($tuotteet[0]['lyhytkuvaus'] == '') {
        $tuotteet[0]['lyhytkuvaus'] = '&nbsp';
      }

      // Erikoishinta
      $tuotteet[0]['kuluprosentti'] = ($tuotteet[0]['kuluprosentti'] == 0) ? '' : $tuotteet[0]['kuluprosentti'];

      // Etsitään kategoria_id tuoteryhmällä
      if ($selected_category == 'tuoteryhma') {
         $category_ids[] = $this->findCategory($tuotteet[0]['try_nimi'], $category_tree['children']);
      }
      else {
        // Etsitään kategoria_id:t tuotepuun tuotepolulla
        $tuotepuun_nodet = $tuotteet[0]['tuotepuun_nodet'];

        // Lisätään myös tuotepuun kategoriat
        if (isset($tuotepuun_nodet) and count($tuotepuun_nodet) > 0) {
          foreach ($tuotepuun_nodet as $tuotepolku) {
            $category_ids[] = $this->createCategoryTree($tuotepolku);
          }
        }
      }
      // Tehdään 'associated_skus' -kenttä
      // Vaatii, että Magentoon asennetaan 'magento-improve-api' -moduli: https://github.com/jreinke/magento-improve-api
      $lapsituotteet_array = array();

      foreach ($tuotteet as $tuote) {
        if (is_numeric($tuote['tuoteno'])) $tuote['tuoteno'] = "SKU_".$tuote['tuoteno'];

        $lapsituotteet_array[] = $tuote['tuoteno'];
      }

      // Configurable tuotteen tiedot
      $configurable = array(
        'categories'            => $category_ids,
        'websites'              => explode(" ", $tuotteet[0]['nakyvyys']),
        'name'                  => utf8_encode($tuotteet[0][$configurable_tuote_nimityskentta]),
        'description'           => utf8_encode($tuotteet[0]['kuvaus']),
        'short_description'     => utf8_encode($tuotteet[0]['lyhytkuvaus']),
        'campaign_code'         => utf8_encode($tuotteet[0]['campaign_code']),
        'onsale'                => utf8_encode($tuotteet[0]['onsale']),
        'target'                => utf8_encode($tuotteet[0]['target']),
        'featured_priority'     => utf8_encode($tuotteet[0]['jarjestys']),
        'weight'                => $tuotteet[0]['tuotemassa'],
        'status'                => self::ENABLED,
        'visibility'            => self::CATALOG_SEARCH, # Configurablet nakyy kaikkialla
        'price'                 => $tuotteet[0][$hintakentta],
        'special_price'         => $tuotteet[0]['kuluprosentti'],
        'tax_class_id'          => $this->getTaxClassID(), # 24%
        'meta_title'            => '',
        'meta_keyword'          => '',
        'meta_description'      => '',
        'color'                 => "Magenta",
        'associated_skus'       => $lapsituotteet_array,
        );

      try {

        /**
         * Loopataan tuotteen (configurable) lapsituotteet (simple) läpi
         * ja päivitetään niiden attribuutit kuten koko ja väri.
         */
        foreach ($tuotteet as $tuote) {
          if (is_numeric($tuote['tuoteno'])) $tuote['tuoteno'] = "SKU_".$tuote['tuoteno'];

          $multi_data = array();

          // Simple tuotteiden parametrit kuten koko ja väri
          foreach ($tuote['parametrit'] as $parametri) {
            $key = $parametri['option_name'];
            $multi_data[$key] = $this->get_option_id($key, $parametri['arvo']);
          }

          $simple_tuote_data = array(
            'price'                 => $tuote[$hintakentta],
            'short_description'     => utf8_encode($tuote['lyhytkuvaus']),
            'featured_priority'     => utf8_encode($tuote['jarjestys']),
            'visibility'            => self::NOT_VISIBLE_INDIVIDUALLY,
            'additional_attributes' => array('multi_data' => $multi_data),
          );

          // Päivitetään Simple tuote
          $result = $this->_proxy->call(  $this->_session,
            'catalog_product.update',
            array($tuote['tuoteno'], $simple_tuote_data));

          $this->log("Päivitetään '{$nimitys}' tuotteen lapsituote '{$tuote['tuoteno']}' " . print_r($simple_tuote_data, true));
        }

        // Jos configurable tuotetta ei löydy, niin lisätään uusi tuote.
        if (!in_array($nimitys, $skus_in_store)) {
          $product_id = $this->_proxy->call($this->_session, 'catalog_product.create',
            array(
              'configurable',
              $this->_attributeSet['set_id'],
              $nimitys, // sku
              $configurable
            )
          );
          $this->log("Tuote '{$nimitys}' lisätty (configurable) " . print_r($configurable, true));
        }
        // Päivitetään olemassa olevaa configurablea
        else {
          $product_id = $this->_proxy->call($this->_session, 'catalog_product.update',
            array(
              $nimitys,
              $configurable
            )
          );
          $this->log("Tuote '{$nimitys}' päivitetty (configurable) " . print_r($configurable, true));

          // Haetaan tuotteen Magenton ID
          $result = $this->_proxy->call($this->_session, 'catalog_product.info', $nimitys);
          $product_id = $result['product_id'];
        }

        // Pitää käydä tekemässä vielä stock.update kutsu, että saadaan Manage Stock: YES
        $stock_data = array(
          'is_in_stock'  => 1,
          'manage_stock' => 1,
        );

        $result = $this->_proxy->call(
          $this->_session,
          'product_stock.update',
          array(  $nimitys, // sku
            $stock_data));

        // Haetaan tuotekuvat Pupesoftista
        $tuotekuvat = $this->hae_tuotekuvat($tuotteet[0]['tunnus']);

        // Lisätään kuvat Magentoon
        $this->lisaa_tuotekuvat($product_id, $tuotekuvat);

        // Lisätään countteria
        $count++;

      }
      catch (Exception $e) {
        $this->_error_count++;
        $this->log("Virhe! Configurable tuotteen '{$nimitys}' lisäys/päivitys epäonnistui (configurable) " . print_r($configurable, true), $e);
      }
    }

    $this->log("$count tuotetta päivitetty (configurable)");

    // Palautetaan lisättyjen configurable tuotteiden määrä
    return $count;
  }

  /**
   * Hakee maksetut tilaukset Magentosta ja luo editilaus tiedoston.
   * Merkkaa haetut tilaukset noudetuksi.
   *
   * @param string  $status Haettavien tilausten status, esim 'prorcessing'
   * @return array       Löydetyt tilaukset
   */
  public function hae_tilaukset($status = 'processing') {

    $this->log("Haetaan tilauksia");

    $orders = array();

    // Toimii ordersilla
    $filter = array(array('status' => array('eq' => $status)));

    // Uusia voi hakea? state => 'new'
    //$filter = array(array('state' => array('eq' => 'new')));

    // Näin voi hakea yhden tilauksen tiedot
    //return array($this->_proxy->call($this->_session, 'sales_order.info', '100019914'));

    // Haetaan tilaukset (orders.status = 'processing')
    $fetched_orders = $this->_proxy->call($this->_session, 'sales_order.list', $filter);

    // HUOM: invoicella on state ja orderilla on status
    // Invoicen statet 'pending' => 1, 'paid' => 2, 'canceled' => 3
    // Invoicella on state
    // $filter = array(array('state' => array('eq' => 'paid')));
    // Haetaan laskut (invoices.state = 'paid')

    foreach ($fetched_orders as $order) {

      $this->log("Haetaan tilaus {$order['increment_id']}");

      // Haetaan tilauksen tiedot (orders)
      $orders[] = $this->_proxy->call($this->_session, 'sales_order.info', $order['increment_id']);

      // Päivitetään tilauksen tila että se on noudettu pupesoftiin
      $this->_proxy->call($this->_session, 'sales_order.addComment', array('orderIncrementId' => $order['increment_id'], 'status' => 'processing_pupesoft', 'Tilaus noudettu Pupesoftiin'));
    }

    $this->log(count($orders) . " tilausta haettu");

    // Palautetaan löydetyt tilaukset
    return $orders;
  }

  /**
   * Päivittää tuotteiden saldot
   *
   * @param array   $dnstock Pupesoftin tuote_exportin array
   * @param int     $count
   */
  public function paivita_saldot(array $dnstock) {

    $this->log("Päivitetään saldot");
    $count = 0;

    // Loopataan päivitettävät tuotteet läpi (aina simplejä)
    foreach ($dnstock as $tuote) {
      if (is_numeric($tuote['tuoteno'])) $tuote['tuoteno'] = "SKU_".$tuote['tuoteno'];

      // $tuote muuttuja sisältää tuotenumeron ja myytävissä määrän
      $product_sku = $tuote['tuoteno'];
      $qty         = $tuote['myytavissa'];

      // Out of stock jos määrä on tuotteella ei ole myytavissa saldoa
      $is_in_stock = ($qty > 0) ? 1 : 0;

      // Päivitetään saldo
      try {
        $stock_data = array(
          'qty'          => $qty,
          'is_in_stock'  => $is_in_stock,
          'manage_stock' => 1
        );

        $result = $this->_proxy->call(
          $this->_session,
          'product_stock.update',
          array(
            $product_sku,
            $stock_data
          )
        );
        $this->log("Päivitetty tuotteen {$product_sku} saldo {$qty}.");
      }
      catch (Exception $e) {
        $this->_error_count++;
        $this->log("Virhe! Saldopäivitys epäonnistui. Tuote {$product_sku} saldo {$qty}.". $e);
      }

      $count++;
    }

    $this->log("$count saldoa päivitetty");

    return $count;
  }

  /**
   * Päivittää tuotteiden hinnat
   *
   * @param array   $dnshinnasto Tuotteiden päivitety hinnat
   * @param int     $count       Päivitettyjen tuotteiden määrän
   */
  public function paivita_hinnat(array $dnshinnasto) {

    $count = 0;
    $batch_count = 0;

    // Päivitetään tuotteen hinnastot
    foreach ($dnshinnasto as $tuote) {
      if (is_numeric($tuote['tuoteno'])) $tuote['tuoteno'] = "SKU_".$tuote['tuoteno'];

      // Batch calls
      $calls[] = array('catalog_product.update', array($tuote['tuoteno'], array('price' => $tuote['hinta'])));

      $batch_count++;
      if ($batch_count > self::MULTICALL_BATCH_SIZE) {

        try {
          $result = $this->_proxy->multicall($this->_session, $calls);
          var_dump($result);
        }
        catch (Exception $e) {
          $this->_error_count++;
          $this->log("Virhe! Hintojen päivitys epäonnistui {$tuote['tuoteno']}", $e);
        }

        $batch_count = 0;
        $calls = array();
      }
    }

    // Päivitettyjen tuotteiden määrä
    return $count;
  }

  /**
   * Poistaa magentosta tuotteita
   *
   * @param array   $kaikki_tuotteet Kaikki tuotteet, jotka pitää LÖYTYÄ Magentosta
   * @return   Poistettujen tuotteiden määrä
   */
  public function poista_poistetut(array $kaikki_tuotteet, $exclude_giftcards = false) {

    $count = 0;
    $skus = $this->getProductList(true, $exclude_giftcards);

    // Loopataan $kaikki_tuotteet-läpi ja tehdään numericmuutos
    foreach ($kaikki_tuotteet as &$tuote) {
      if (is_numeric($tuote)) $tuote = "SKU_".$tuote;
    }

    // Poistetaan tuottee jotka löytyvät arraysta $kaikki_tuotteet arraystä $skus
    $poistettavat_tuotteet = array_diff($skus, $kaikki_tuotteet);

    // Nämä kaikki tuotteet pitää poistaa Magentosta
    foreach ($poistettavat_tuotteet as $tuote) {

      $this->log("Poistetaan tuote $tuote");

      try {
        // Tässä kutsu, jos tuote oikeasti halutaan poistaa
        $this->_proxy->call($this->_session, 'catalog_product.delete', $tuote, 'SKU');
        $count++;
      }
      catch (Exception $e) {
        $this->_error_count++;
        $this->log("Virhe! Tuotteen poisto epäonnistui!", $e);
      }
    }

    $this->log("$count tuotetta poistettu");

    return $count;
  }

  /**
   * Poistaa magentosta kategorioita
   *
   * @param array   $kaikki_kategoriat Kaikki kategoriat jotka pitää löytyä Magentosta
   * @return   Poistettujen tuotteiden määrä
   */
  public function poista_kategorioita(array $kaikki_kategoriat) {

    // Work in progress, don't use :)
    return;

    $count = 0;
    $parent_id = $this->_parent_id; // Magento kategorian tunnus, jonka alle kaikki tuoteryhmät lisätään (pitää katsoa magentosta)

    // Haetaan kaikki kategoriat, joiden parent_id on parent id
    $magento_kategoriat = $this->_proxy->call($this->_session, 'catalog_category.level',
      array(
        null, // website
        null, // storeview
        $parent_id,
      )
    );

    var_dump($magento_kategoriat);

    $this->log("$count kategoriaa poistettu");

    return $count;
  }

  /// Private functions ///

  /**
   * Parametrinä tulee yhden tuotteen koko tuotepolku
   * eli tuotepuun tuoteryhmät järjestyksessä rootista lähtien
   * @return syvimmän kategorian id
   */
  private function createCategoryTree($ancestors) {

    $cat_id = $this->_parent_id;

    foreach ($ancestors as $nimi) {
      $cat_id = $this->createSubCategory($nimi, $cat_id);
    }

    return $cat_id;
  }

  /**
   * Lisää tuotepuun kategorian annettun category_id:n alle
   * jos sellaista ei ole jo olemassa
   * @return luodun tai löydetyn kategorian id
   */
  private function createSubCategory($name, $parent_cat_id) {

    // otetaan koko tuotepuu, valitaan siitä eka solu idn perusteella
    // sen lapsista etsitään nimeä, jos ei löydy, luodaan viimeisimmän idn alle
    // lopuksi palautetaan id
    $name = utf8_encode($name);
    $categoryaccesscontrol = $this->_categoryaccesscontrol;
    $magento_tree = $this->getCategories();
    $results = $this->getParentArray($magento_tree, "$parent_cat_id");

    // Etsitään kategoriaa
    foreach ($results[0]['children'] as $k => $v) {
      if (strcasecmp($name, $v['name']) == 0) {
        return $v['category_id'];
      }
    }

    // Lisätään kategoria, jos ei löytynyt
    $category_data = array(
                           'name'                  => $name,
                           'is_active'             => 1,
                           'position'              => 1,
                           'default_sort_by'       => 'position',
                           'available_sort_by'     => 'position',
                           'include_in_menu'       => 1,
                           'is_anchor'             => 1
                           );

    if ($categoryaccesscontrol) {
      // HUOM: Vain jos "Category access control"-moduli on asennettu
      $category_data['accesscontrol_show_group'] = 0;
    }

    // Kutsutaan soap rajapintaa
    $category_id = $this->_proxy->call($this->_session, 'catalog_category.create',
      array($parent_cat_id, $category_data)
    );
    $this->log("Lisättiin tuotepuun kategoria:$name tunnuksella: $category_id");

    unset($this->_category_tree);

    return $category_id;
  }

  /**
   *  Tonkii arraysta key->value pairia ja jos löytyy nii palauttaa sen
   */
  private function getParentArray($tree, $parent_cat_id) {
    //etsitään keytä "category_id" valuella isatunnus ja return sen lapset
    return search_array_key_for_value_recursive($tree, 'category_id', $parent_cat_id);

  }

  /**
   * Hakee oletus attribuuttisetin
   *
   * @return AttributeSet
   */
  private function getAttributeSet() {

    if (empty($this->_attributeSet)) {
      $attributeSets = $this->_proxy->call($this->_session, 'product_attribute_set.list');
      $this->_attributeSet = current($attributeSets);
    }

    return $this->_attributeSet;
  }

  /**
   * Hakee kaikki attribuutit magentosta
   *
   * @return     Kaikki attribuutit
   */
  private function getAttributeList() {

    if (empty($this->_attribute_list)) {
      $this->_attribute_list = $this->_proxy->call(
        $this->_session,
        "product_attribute.list",
        array($this->_attributeSet['set_id'])
      );
    }

    return $this->_attribute_list;
  }

  /**
   * Hakee kaikki kategoriat
   */
  private function getCategories() {

    try {
      if (empty($this->_category_tree)) {
        // Haetaan kaikki defaulttia suuremmat kategoriat (2)
        $this->_category_tree = $this->_proxy->call($this->_session, 'catalog_category.tree');
        //$this->_category_tree = $this->_category_tree['children'][0]; # Skipataan rootti categoria
      }

      return $this->_category_tree;
    }
    catch (Exception $e) {
      $this->_error_count++;
      $this->log("Virhe! Kategorioiden hakemisessa", $e);
    }
  }

  /**
   * Etsii kategoriaa nimeltä Magenton kategoria puusta.
   */
  private function findCategory($name, $root) {
    $category_id = false;

    foreach ($root as $i => $category) {

      // Jos löytyy tästä tasosta nii palautetaan id
      if (strcasecmp($name, $category['name']) == 0) {

        // Jos kyseisen kategorian alla on saman niminen kategoria,
        // palautetaan sen id nykyisen sijasta (osasto ja try voivat olla saman niminisä).
        if (!empty($category['children']) and strcasecmp($category['children'][0]['name'], $name) == 0) {
          return $category['children'][0]['category_id'];
        }

        return $category_id = $category['category_id'];
      }

      // Muuten jatketaan ettimistä
      $r = $this->findCategory($name, $category['children']);
      if ($r != null) {
        return $r;
      }
    }

    // Mitään ei löytyny
    return $category_id;
  }

  // Etsii asiakasryhmää nimen perusteella Magentosta, palauttaa id:n
  private function findCustomerGroup($name) {

    $customer_groups = $this->_proxy->call(
      $this->_session,
      'customer_group.list');

      $id = 0;
      foreach ($customer_groups as $asryhma) {
        if (strcasecmp($asryhma['customer_group_code'], $name) == 0) {
          $id = $asryhma['customer_group_id'];
          break;
        }
      }

      return $id;
  }

  /**
   * Palauttaa attribuutin option id:n
   *
   * Esimerkiksi koko, S palauttaa jonkun numeron jolla tuotteen päivityksessä saadaan attribuutti
   * oikein.
   *
   * @param string  $name  Attribuutin nimi, koko tai vari
   * @param string  $value Atrribuutin arvo, S, M, XL...
   * @return int             Options_id
   */
  private function get_option_id($name, $value) {

    $attribute_list = $this->getAttributeList();
    $attribute_id = '';

    // Etsitään halutun attribuutin id
    foreach($attribute_list as $attribute) {
      if (strcasecmp($attribute['code'], $name) == 0) {
        $attribute_id = $attribute['attribute_id'];
        $attribute_type = $attribute['type'];
        break;
      }
    }

    // Jos attribuuttia ei löytynyt niin turha ettiä option valuea
    if (empty($attribute_id)) return 0;

    // Haetaan kaikki attribuutin optionssit
    $options = $this->_proxy->call(
      $this->_session,
      "product_attribute.options",
      array(
        $attribute_id
      )
    );
    // Etitään optionsin value
    foreach ($options as $option) {
      if (strcasecmp($option['label'], $value) == 0) {
        return $option['value'];
      }
    }

    // Jos optionssia ei ole mutta tyyppi on select niin luodaan se
    if ($attribute_type == "select") {
      $optionToAdd = array(
          "label" => array(
              array(
                  "store_id" => 0,
                  "value" => $value
              )
          ),
          "is_default" => 0
      );
      $this->_proxy->call($this->_session,
                          "product_attribute.addOption",
                          array(
                                $attribute_id,
                                $optionToAdd
                               )
                         );
      echo "Luotiin uusi attribuutti $value optioid $attribute_id";

      // Haetaan kaikki attribuutin optionssit uudestaan..
      $options = $this->_proxy->call(
          $this->_session,
          "product_attribute.options",
          array(
               $attribute_id
          )
      );
      // Etitään optionsin value uudestaan..
      foreach($options as $option) {
        if (strcasecmp($option['label'], $value) == 0) {
          return $option['value'];
        }
      }
    }

    // Mitään ei löytyny
    return 0;
  }

  /**
   * Lisää tuotteen tuotekuvat
   *
   * @param string  $product_id Tuotteen tunnus
   * @param array   $tuotekuvat Tuotteen kuvatiedostot
   * @return array          Tiedostonimet
   */
  public function lisaa_tuotekuvat($product_id, $tuotekuvat) {

    $types = array('image', 'small_image', 'thumbnail');

    // Pitää ensin poistaa kaikki tuotteen kuvat Magentosta
    $magento_pictures = $this->listaa_tuotekuvat($product_id);

    // Poistetaan kuvat
    foreach ($magento_pictures as $file) {
      $this->poista_tuotekuva($product_id, $file);
    }

    // Loopataan tuotteen kaikki kuvat
    foreach ($tuotekuvat as $kuva) {

      // Lisätään tuotekuva kerrallaan
      try {
        $data = array(  $product_id,
          array(  'file'     => $kuva,
            'label'    => '',
            'position'   => 0,
            'types'   => $types,
            'exclude'   => 0
          ),
        );

        $return = $this->_proxy->call(
          $this->_session,
          'catalog_product_attribute_media.create',
          $data
        );

        $this->log("Lisätty kuva " . print_r($return, true));
      }
      catch (Exception $e) {
        // Nollataan base-encoodattu kuva, että logi ei tuu isoks
        $data[1]["file"]["content"] = '...content poistettu logista...';

        $this->log("Virhe! Kuvan lisäys epäonnistui ". print_r($data, true), $e);
        $this->_error_count++;
      }
    }
  }

  /**
   * Hakee tuotteen tuotekuvat Magentosta
   *
   * @param int     $product_id Tuoteen tunnus (Magento ID)
   * @return array   $return     Palauttaa arrayn, jossa tuotekuvien filenamet
   */
  public function listaa_tuotekuvat($product_id) {

    $pictures = array();
    $return = array();

    // Haetaan tuotteen kuvat
    try {
      $pictures = $this->_proxy->call(
        $this->_session,
        'catalog_product_attribute_media.list',
        $product_id);
    }
    catch (Exception $e) {
      $this->log("Virhe! Kuvalistauksen haku '{$product_id}' epäonnistui", $e);
      $this->_error_count++;
    }

    foreach ($pictures as $picture) {
      $return[] = $picture['file'];
    }

    return $return;
  }

  /**
   * Poistaa tuotteen tuotekuvan Magentosta
   *
   * @param int     $product_id Tuoteen tunnus (Magento ID)
   * @return bool   $return     Palauttaa boolean
   */
  public function poista_tuotekuva($product_id, $filename) {

    $return = false;

    // Poistetaan tuotteen kuva
    try {
      $return = $this->_proxy->call(
        $this->_session,
        'catalog_product_attribute_media.remove',
        array(  'product' => $product_id,
          'file' => $filename));
      $this->log("Poistetaan tuotteen '{$product_id}' kuva '{$filename}'");
    }
    catch (Exception $e) {
      $this->log("Virhe! Kuvan poisto epäonnistui '{$product_id}' kuva '{$filename}'", $e);
      $this->_error_count++;
      return false;
    }

    return $return;
  }

  /**
   * Hakee tuotteen tuotekuvat Pupesoftista
   *
   * @param int     $tunnus Tuoteen tunnus (tuote.tunnus)
   * @return array   $tuotekuvat   Palauttaa arrayn joka kelpaa magenton soap clientille suoraan
   */
  public function hae_tuotekuvat($tunnus) {
    global $kukarow, $dbhost, $dbuser, $dbpass, $dbkanta;

    // Populoidaan tuotekuvat array
    $tuotekuvat = array();

    try {
      // Tietokantayhteys
      $db = new PDO("mysql:host=$dbhost;dbname=$dbkanta", $dbuser, $dbpass);

      // Tuotekuva query
      $stmt = $db->prepare("  SELECT
                  liitetiedostot.data,
                  liitetiedostot.filetype,
                  liitetiedostot.filename
                  FROM liitetiedostot
                  WHERE liitetiedostot.yhtio = ?
                  AND liitetiedostot.liitostunnus = ?
                  AND liitetiedostot.liitos = 'tuote'
                  AND liitetiedostot.kayttotarkoitus = 'TK'
                  ORDER BY liitetiedostot.jarjestys DESC,
                  liitetiedostot.tunnus DESC");
      $stmt->execute(array($kukarow['yhtio'], $tunnus));

      while ($liite = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $file = array(
          'content'   => base64_encode($liite['data']),
          'mime'    => $liite['filetype'],
          'name'    => $liite['filename']
        );
        $tuotekuvat[] = $file;
      }
    }
    catch (Exception $e) {
      $this->_error_count++;
      $this->log("Virhe! PDO yhteys on poikki. Yritetään uudelleen.", $e);
    }

    $db = null;

    // Palautetaan tuotekuvat
    return $tuotekuvat;
  }

  /**
   * Lisää päivitettyjä asiakkaita Magento-verkkokauppaan.
   *
   * @param  array  $dnsasiakas   Pupesoftin tuote_exportin palauttama asiakas array
   * @return int               Lisättyjen asiakkaiden määrä
  */
  public function lisaa_asiakkaat(array $dnsasiakas) {

    $this->log("Lisätään asiakkaita");
    // Asiakas countteri
    $count = 0;

    // Lisätään asiakkaat ja osoitteet erissä
    foreach ($dnsasiakas as $asiakas) {


      $asiakasryhma_id = $this->findCustomerGroup(utf8_encode($asiakas['asiakasryhma']));

      $asiakas_data = array(
          'email'          => $asiakas['yhenk_email'],
          'firstname'        => $asiakas['nimi'],
          'lastname'        => $asiakas['nimi'],
          'website_id'      => $asiakas['magento_website_id'],
          'taxvat'        => $asiakas['ytunnus'],
          'external_id'      => $asiakas['asiakasnro'],
          'group_id'        => $asiakasryhma_id,
      );

      $laskutus_osoite_data = array(
          'firstname'        => $asiakas['laskutus_nimi'],
          'lastname'        => $asiakas['laskutus_nimi'],
          'street'        => array($asiakas['laskutus_osoite']),
          'postcode'        => $asiakas['laskutus_postino'],
          'city'          => $asiakas['laskutus_postitp'],
          'country_id'      => $asiakas['maa'],
          'telephone'        => $asiakas['yhenk_puh'],
          'company'        => $asiakas['nimi'],
          'is_default_billing'    => true,
      );

      $toimitus_osoite_data = array(
          'firstname'        => $asiakas['toimitus_nimi'],
          'lastname'        => $asiakas['toimitus_nimi'],
          'street'        => array($asiakas['toimitus_osoite']),
          'postcode'        => $asiakas['toimitus_postino'],
          'city'          => $asiakas['toimitus_postitp'],
          'country_id'      => $asiakas['maa'],
          'telephone'        => $asiakas['yhenk_puh'],
          'company'        => $asiakas['nimi'],
          'is_default_shipping' => true
      );

      // Lisätään tai päivitetään asiakas

      // Jos asiakasta ei ole olemassa (sillä ei ole pupessa magento_tunnus:ta) niin lisätään se
      if (empty($asiakas['magento_tunnus'])) {
        try {
          $result = $this->_proxy->call(
            $this->_session,
            'customer.create',
             array(
                    $asiakas_data
            ));

          $this->log("Asiakas '{$asiakas['tunnus']}' / {$result} lisätty " . print_r($asiakas_data, true));
          $asiakas['magento_tunnus'] = $result;

          // Päivitetään magento_tunnus pupeen
          $tarksql = "SELECT *
                      FROM asiakkaan_avainsanat
                      WHERE yhtio      = '{$asiakas['yhtio']}'
                      AND liitostunnus = '{$asiakas['tunnus']}'
                      AND avainsana    = 'magento_tunnus'";
          $tarkesult = pupe_query($tarksql);
          $ahy = mysql_num_rows($tarkesult);

          if ($ahy == 0) {
            $ahinsert = "INSERT INTO asiakkaan_avainsanat SET
                         yhtio        = '{$asiakas['yhtio']}',
                         liitostunnus = '{$asiakas['tunnus']}',
                         tarkenne     = '{$asiakas['magento_tunnus']}',
                         avainsana    = 'magento_tunnus',
                         laatija      = 'Magento',
                         luontiaika   = now(),
                         muutospvm    = now()";
            pupe_query($ahinsert);
          }
          else {
            $query = "UPDATE asiakkaan_avainsanat
                      SET tarkenne = '{$asiakas['magento_tunnus']}'
                      WHERE yhtio      = '{$asiakas['yhtio']}'
                      AND liitostunnus = '{$asiakas['tunnus']}'
                      AND avainsana    = 'magento_tunnus'";
            pupe_query($query);
          }
        }
        catch (Exception $e) {
          $this->_error_count++;
          $this->log("Virhe! Asiakkaan '{$asiakas['tunnus']}' lisäys epäonnistui " . print_r($asiakas_data, true), $e);
        }
      }
      // Asiakas on jo olemassa, päivitetään
      else {
        try {
          $result = $this->_proxy->call(
            $this->_session,
            'customer.update',
             array(
                    $asiakas['magento_tunnus'],
                    $asiakas_data
          ));

          $this->log("Asiakas '{$asiakas['tunnus']}' / {$asiakas['magento_tunnus']} päivitetty " . print_r($asiakas_data, true));
        }
        catch (Exception $e) {
          $this->_error_count++;
          $this->log("Virhe! Asiakkaan '{$asiakas['tunnus']}' päivitys epäonnistui " . print_r($asiakas_data, true), $e);
        }
      }

      // Haetaan ensin asiakkaan laskutus- ja toimitusosoitteet
      $address_array = $this->_proxy->call(
        $this->_session,
        'customer_address.list',
         $asiakas['magento_tunnus']);
      // Ja poistetaan ne
      if (count($address_array) > 0) {
        foreach ($address_array as $address) {
          $result = $this->_proxy->call(
            $this->_session, 'customer_address.delete', $address['customer_address_id']);
        }
      }

      if (isset($laskutus_osoite_data['firstname']) and !empty($laskutus_osoite_data['firstname'])) {
        try {
          // Lisätään laskutusosoite
          $result = $this->_proxy->call(
            $this->_session,
            'customer_address.create',
            array('customerId' => $asiakas['magento_tunnus'], 'addressdata' => ($laskutus_osoite_data)));
        }
        catch (Exception $e) {
          $this->log("Virhe! Asiakkaan '{$asiakas['tunnus']}' laskutusosoitteen päivitys epäonnistui " . print_r($laskutus_osoite_data, true), $e);
        }
      }

      if (isset($toimitus_osoite_data['firstname']) and !empty($toimitus_osoite_data['firstname'])) {
        try {
          // Lisätään toimitusosoite
          $result = $this->_proxy->call(
            $this->_session,
            'customer_address.create',
            array('customerId' => $asiakas['magento_tunnus'], 'addressdata' => ($toimitus_osoite_data)));
        }
        catch (Exception $e) {
          $this->log("Virhe! Asiakkaan '{$asiakas['tunnus']}' toimitusosoitteen päivitys epäonnistui " . print_r($toimitus_osoite_data, true), $e);
        }
      }
      // Lisätään asiakas countteria
      $count++;

    }

    $this->log("$count asiakasta päivitetty");

    // Palautetaan pävitettyjen asiakkaiden määrä
    return $count;
  }
  /**
   * Asettaa tax_class_id:n
   * Oletus 0
   *
   * @param int     $tax_clas_id Veroluokan tunnus
   */
  public function setTaxClassID($tax_class_id) {
    $this->_tax_class_id = $tax_class_id;
  }

  /**
   * Asettaa parent_id:n
   * Oletus 3
   *
   * @param int     $parent_id Root kategorian tunnus
   */
  public function setParentID($parent_id) {
    $this->_parent_id = $parent_id;
  }

  /**
   * Asettaa hinta-kentän
   * Oletus myymalahinta
   *
   * @param string  $hintakentta joko myyntihinta tai myymalahinta
   */
  public function setHintakentta($hintakentta) {
    $this->_hintakentta = $hintakentta;
  }

  /**
      * Asettaa _kategoriat-muuttujan, parametri säätelee
      * perustetaanko magenton tuoteryhmärakenne tuoteryhmien vai tuotepuun pohjalta
      * Oletus 'tuoteryhma', vaihtoehtoisesti tuotepuu
      *
      * @param string $magento_kategoriat
      */
    public function setKategoriat($magento_kategoriat) {
      $this->_kategoriat = $magento_kategoriat;
    }

  /**
   * Asettaa categoryaccesscontrol-muuttujan
   * Oletus FALSE
   *
   * @param string $categoryaccesscontrol BOOLEAN
   */
  public function setCategoryaccesscontrol($categoryaccesscontrol) {
    $this->_categoryaccesscontrol = $categoryaccesscontrol;
  }

  /**
    * Asettaa configurable_nimityskentta-muuttujan
    * Oletus 'nimitys'
    *
    * @param string $configurable_nimityskentta
    */
  public function setConfigurableNimityskentta($configurable_tuote_nimityskentta) {
    $this->_configurable_tuote_nimityskentta = $configurable_tuote_nimityskentta;
  }

  /**
   * Hakee tax_class_id:n
   *
   * @return int   Veroluokan tunnus
   */
  private function getTaxClassID() {
    return $this->_tax_class_id;
  }

  /**
   * Hakee error_countin:n
   *
   * @return int  virheiden määrä
   */
  public function getErrorCount() {
    return $this->_error_count;
  }

  /**
   * Hakee verkkokaupan tuotteet
   *
   * @param boolean $only_skus Palauttaa vain tuotenumerot (true)
   * @return array
   */
  private function getProductList($only_skus = false, $exclude_giftcards = false) {

    try {
      $result = $this->_proxy->call($this->_session, 'catalog_product.list');

      if ($exclude_giftcards) {
        foreach ($result as $index => $product) {
          if ($product['type'] == 'giftcards') {
            unset($result[$index]);
          }
        }
      }

      if ($only_skus == true) {
        $skus = array();

        foreach ($result as $product) {
          $skus[] = $product['sku'];
        }
        return $skus;
      }

      return $result;
    }
    catch (Exception $e) {
      $this->_error_count++;
      $this->log("Virhe! Tuotelistan hakemisessa", $e);
      return null;
    }
  }

  /**
   * Hakee storen tiedot
   */
  private function getStoreInfo($store_id = 1) {

    try {
      $result = $this->_proxy->call($this->_session, 'store.info', $store_id);
      return $result;
    }
    catch (Exception $e) {
      $this->_error_count++;
      $this->log("Virhe! Storetietojen hakemisessa", $e);
      $this->log(__METHOD__, $e);
    }
  }

  /**
   * Verkkokaupan lista luoduista storeista
   */
  private function getStoreList() {

    try {
      $result = $this->_proxy->call($this->_session, 'store.list');
      return $result;
    }
    catch (Exception $e) {
      $this->_error_count++;
      $this->log("Virhe! Storelistan hakemisessa", $e);
      $this->log(__METHOD__, $e);
    }
  }

  /**
   * Virhelogi
   *
   * @param string  $message   Virheviesti
   * @param exception $exception Exception
   */
  private function log($message, $exception = '') {

    if (self::LOGGING == true) {
      $timestamp = date('d.m.y H:i:s');
      $message = utf8_encode($message);

      if ($exception != '') {
        $message .= " (" . $exception->getMessage() . ") faultcode: " . $exception->faultcode;
      }

      $message .= "\n";
      error_log("{$timestamp}: {$message}", 3, '/tmp/magento_log.txt');
    }
  }
}
