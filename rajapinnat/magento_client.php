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
   * Miten configurable-tuotteen lapsituotteet näytetään verkkokaupassa, oletuksena NOT_VISIBLE_INDIVIDUALLY
   */
  private $_configurable_lapsituote_nakyvyys = 'NOT_VISIBLE_INDIVIDUALLY';

  /**
   * Tuotteen erikoisparametrit jotka tulevat jostain muualta kuin dynaamisista parametreistä
   */
  private $_verkkokauppatuotteet_erikoisparametrit = array ();

  /**
   * Asiakkaan erikoisparametrit joilla ylikirjoitetaan arvoja asiakas- ja osoitetiedoista
   */
  private $_asiakkaat_erikoisparametrit = array ();

  /**
   * Magentossa käsin hallitut kategoria id:t joita ei poisteta tuotteelta tuotepäivityksessä
   */
  private $_sticky_kategoriat = array ();

  /**
   * Estetäänkö tilauksen sisäänluku, jos se on jo kerran merkattu "processing_pupesoft"-tilaan
   */
  private $_sisaanluvun_esto = "YES";

  /**
   * Asetetaanko uusille tuotteille aina sama tuoteryhmä ja poistetaan try tuotepäivityksestä
   */
  private $_universal_tuoteryhma = "";

  /**
   * Aktivoidaanko asiakas luonnin yhteydessä Magentoon
   */
  private $_asiakkaan_aktivointi = false;

  /**
   * Siirretäänkö asiakaskohtaiset tuotehinnat Magentoon
   */
  private $_asiakaskohtaiset_tuotehinnat = false;

  /**
   * Magenton default-tuoteparametrien yliajo
   */
  private $_magento_poistadefaultit = array();

  /**
   * Magenton default-tuoteparametrien yliajo
   */
  private $_magento_poista_asiakasdefaultit = array();

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

    $verkkokauppatuotteet_erikoisparametrit = $this->_verkkokauppatuotteet_erikoisparametrit;

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

      $tuoteryhmayliajo = $this->_universal_tuoteryhma;
      $tuoteryhmanimi   = $tuote['try_nimi'];

      // Yliajetaan tuoteryhmän nimi jos muuttuja on asetettu
      if (isset($tuoteryhmayliajo) and !empty($tuoteryhmayliajo)) {
        $tuoteryhmanimi = $tuoteryhmayliajo;
      }

      // Etsitään kategoria_id tuoteryhmällä
      if ($selected_category == 'tuoteryhma') {
        $category_ids[] = $this->findCategory(utf8_encode($tuoteryhmanimi), $category_tree['children']);
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

      $tuetut_kieliversiot = array();
      $kauppakohtaiset_hinnat = array();
      $kauppakohtaiset_verokannat = array();

      // Simple tuotteiden parametrit kuten koko ja väri
      foreach ($tuote['tuotteen_parametrit'] as $parametri) {
        $key = $parametri['option_name'];
        $multi_data[$key] = $this->get_option_id($key, $parametri['arvo']);
      }

      if (count($verkkokauppatuotteet_erikoisparametrit) > 0) {
        foreach ($verkkokauppatuotteet_erikoisparametrit as $erikoisparametri) {
          $key = $erikoisparametri['nimi'];
          // Kieliversiot ja kauppakohtaiset_hinnat sekä kauppakohtaiset_verokannat
          // poimitaan talteen koska niitä käytetään toisaalla
          if ($key == 'kieliversiot') {
            $tuetut_kieliversiot = $erikoisparametri['arvo'];
            continue;
          }
          elseif ($key == 'kauppakohtaiset_hinnat') {
            $kauppakohtaiset_hinnat[] = $erikoisparametri['arvo'];
            continue;
          }
          if ($key == 'kauppakohtaiset_verokannat') {
            $kauppakohtaiset_verokannat = $erikoisparametri['arvo'];
            continue;
          }

          if (isset($tuote[$erikoisparametri['arvo']])) {
            $multi_data[$key] = $this->get_option_id($key, $tuote[$erikoisparametri['arvo']]);
          }
        }
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
        'tax_class_id'          => $this->getTaxClassID(),
        'meta_title'            => '',
        'meta_keyword'          => '',
        'meta_description'      => '',
        'campaign_code'         => utf8_encode($tuote['campaign_code']),
        'onsale'                => utf8_encode($tuote['onsale']),
        'target'                => utf8_encode($tuote['target']),
        'tier_price'            => $tuote_ryhmahinta_data,
        'additional_attributes' => array('multi_data' => $multi_data),
      );

      $poista_defaultit = $this->_magento_poistadefaultit;

      // Voidaan yliajaa Magenton defaultparameja jos niitä ei haluta
      // tai jos ne halutaan korvata additional_attributesin mukana
      if (count($poista_defaultit) > 0) {
        foreach ($poista_defaultit as $poistettava_key) {
          unset($tuote_data[$poistettava_key]);
        }
      }

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

          $sticky_kategoriat = $this->_sticky_kategoriat;
          $tuoteryhmayliajo = $this->_universal_tuoteryhma;

          // Haetaan tuotteen Magenton ID ja nykyiset kategoriat
          $result = $this->_proxy->call($this->_session, 'catalog_product.info', $tuote['tuoteno']);
          $product_id = $result['product_id'];
          $current_categories = $result['categories'];

          // Jos tuotteelta löytyy näitä kategoriatunnuksia ennen updatea ne lisätään takaisin
          if (count($sticky_kategoriat) > 0 and count($current_categories) > 0) {
            foreach ($sticky_kategoriat as $stick) {
              if (in_array($stick, $current_categories)) {
                $tuote_data['categories'][] = $stick;
              }
            }
          }

          // Ei muuteta tuoteryhmiä jos yliajo on päällä
          if (isset($tuoteryhmayliajo) and !empty($tuoteryhmayliajo)) {
            $tuote_data['categories'] = $current_categories;
          }

          $this->_proxy->call($this->_session, 'catalog_product.update',
            array(
              $tuote['tuoteno'], // sku
              $tuote_data)
          );

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

      // Päivitetään tuotteen kieliversiot kauppanäkymäkohtaisesti
      // jos nämä on asetettu konffissa

      if (isset($tuetut_kieliversiot)
        and count($tuetut_kieliversiot) > 0) {

        try {

          // Kieliversiot-magentoerikoisparametrin tulee sisältää array jossa määritellään mikä kieliversio
          // siirretään mihinkin kauppatunnukseen

          // Esim. array("en" => array('4','13'), "se" => array('9'));
          $kieliversio_data = $this->hae_kieliversiot($tuote_clean);

          foreach ($tuetut_kieliversiot as $kieli => $kauppatunnukset) {
            $kaannokset = $kieliversio_data[$kieli];
            if (empty($kaannokset)) continue;

            // Päivitetään jokaiseen kauppatunnukseen haluttu käännös
            foreach ($kauppatunnukset as $kauppatunnus) {
              $tuotteen_kauppakohtainen_data = array(
                'description' => $kaannokset['kuvaus'],
                'name'        => $kaannokset['nimitys'],
                'unit'        => $kaannokset['yksikko']
              );

              $this->_proxy->call($this->_session, 'catalog_product.update',
                array(
                  $tuote['tuoteno'],
                  $tuotteen_kauppakohtainen_data,
                  $kauppatunnus
                )
              );
            }
          }

          $this->log("Tuotteen '{$tuote['tuoteno']}' kieliversiot päivitetty (simple) " . print_r($kieliversio_data, true));
        }
        catch (Exception $e) {
          $this->log("Virhe! Tuotteen '{$tuote['tuoteno']}' kieliversioiden päivitys epäonnistui (simple) " . print_r($kieliversio_data, true), $e);
        }
      }

      // Päivitetään tuotteen kauppanäkymäkohtaiset hinnat
      if (isset($kauppakohtaiset_hinnat) and count($kauppakohtaiset_hinnat) > 0) {
        try {
          foreach ($kauppakohtaiset_hinnat as $key => $kauppakohtainen_hinta) {
            foreach ($kauppakohtainen_hinta as $tuotekentta => $kauppatunnukset) {
              foreach ($kauppatunnukset as $kauppatunnus) {
                // Jos asetettu hintakenttä on 0 tai '' niin skipataan, tämä
                // sitävarten että voidaan antaa "default"-arvoja(myyntihinta) jotka yliajetaan esimerkiksi
                // hinnastohinnalla, mutta vain jos sellainen löytyy ja on voimassa
                if (empty($tuote[$tuotekentta])) continue;
                $tuotteen_kauppakohtainen_data = array(
                  'price' => $tuote[$tuotekentta]
                );

                if (!empty($kauppakohtaiset_verokannat[$kauppatunnus])) {
                  $tuotteen_kauppakohtainen_data['tax_class_id'] = $kauppakohtaiset_verokannat[$kauppatunnus];
                }

                $this->_proxy->call($this->_session, 'catalog_product.update',
                  array(
                    $tuote['tuoteno'],
                    $tuotteen_kauppakohtainen_data,
                    $kauppatunnus
                    )
                );
              }
              $this->log("Tuotteen '{$tuote['tuoteno']}' kauppakohtainen hinta päivitetty (simple) " . print_r($tuotteen_kauppakohtainen_data, true));
            }
          }
        }
        catch (Exception $e) {
          $this->log("Virhe! Tuotteen '{$tuote['tuoteno']}' kauppakohtaisen hinnan päivitys epäonnistui (simple) " . print_r($tuotteen_kauppakohtainen_data, true), $e);
        }
      }

      // Haetaan tuotekuvat Pupesoftista
      $tuotekuvat = $this->hae_tuotekuvat($tuote['tunnus']);

      // Lisätään kuvat Magentoon
      $this->lisaa_tuotekuvat($product_id, $tuotekuvat);

      // Lisätään tuotteen asiakaskohtaiset tuotehinnat
      if ($this->_asiakaskohtaiset_tuotehinnat) {
        $this->lisaaAsiakaskohtaisetTuotehinnat($tuote_clean, $tuote['tuoteno']);
      }

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

    // Erikoisparametrit
    $verkkokauppatuotteet_erikoisparametrit = $this->_verkkokauppatuotteet_erikoisparametrit;

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

      $tuoteryhmayliajo = $this->_universal_tuoteryhma;
      $tuoteryhmanimi = $tuotteet[0]['try_nimi'];

      // Yliajetaan tuoteryhmän nimi jos muuttuja on asetettu
      if (isset($tuoteryhmayliajo) and !empty($tuoteryhmayliajo)) {
        $tuoteryhmanimi = $tuoteryhmayliajo;
      }

      // Etsitään kategoria_id tuoteryhmällä
      if ($selected_category == 'tuoteryhma') {
        $category_ids[] = $this->findCategory($tuoteryhmanimi, $category_tree['children']);
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
      // Configurable-tuotteelle myös ensimmäisen lapsen parametrit
      $configurable_multi_data = array();
      foreach ($tuotteet[0]['parametrit'] as $parametri) {
        $key = $parametri['option_name'];
        $configurable_multi_data[$key] = $this->get_option_id($key, $parametri['arvo']);
      }

      $kauppakohtaiset_hinnat = array();
      $kauppakohtaiset_verokannat = array();
      // Configurable-tuotteelle myös ensimmäisen lapsen erikoisparametrit
      if (count($verkkokauppatuotteet_erikoisparametrit) > 0) {
        foreach ($verkkokauppatuotteet_erikoisparametrit as $erikoisparametri) {
          $key = $erikoisparametri['nimi'];
          if ($key == 'kieliversiot') continue;
          if ($key == 'kauppakohtaiset_hinnat') {
            $kauppakohtaiset_hinnat[] = $erikoisparametri['arvo'];
            continue;
          }
          if ($key == 'kauppakohtaiset_verokannat') {
            $kauppakohtaiset_verokannat = $erikoisparametri['arvo'];
            continue;
          }
          if (isset($tuotteet[0][$erikoisparametri['arvo']])) {
            $configurable_multi_data[$key] = $this->get_option_id($key, $tuotteet[0][$erikoisparametri['arvo']]);
          }
        }
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
        'visibility'            => self::CATALOG_SEARCH, // Configurablet nakyy kaikkialla
        'price'                 => $tuotteet[0][$hintakentta],
        'tax_class_id'          => $this->getTaxClassID(), // 24%
        'meta_title'            => '',
        'meta_keyword'          => '',
        'meta_description'      => '',
        'additional_attributes' => array('multi_data' => $configurable_multi_data),
        'associated_skus'       => $lapsituotteet_array,
      );

      $poista_defaultit = $this->_magento_poistadefaultit;

      // Voidaan yliajaa Magenton defaultparameja jos niitä ei haluta
      // tai jos ne halutaan korvata additional_attributesin mukana
      if (count($poista_defaultit) > 0) {
        foreach ($poista_defaultit as $poistettava_key) {
          unset($configurable[$poistettava_key]);
        }
      }

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
            'visibility'            => constant("MagentoClient::{$this->_configurable_lapsituote_nakyvyys}"),
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

          $sticky_kategoriat = $this->_sticky_kategoriat;

          // Haetaan tuotteen Magenton ID ja nykyiset kategoriat
          $result = $this->_proxy->call($this->_session, 'catalog_product.info', $nimitys);
          $product_id = $result['product_id'];
          $current_categories = $result['categories'];

          // Jos tuotteelta löytyy näitä kategoriatunnuksia ennen updatea ne lisätään takaisin
          if (count($sticky_kategoriat) > 0 and count($current_categories) > 0) {
            foreach ($sticky_kategoriat as $stick) {
              if (in_array($stick, $current_categories)) {
                $configurable['categories'][] = $stick;
              }
            }
          }

          $this->_proxy->call($this->_session, 'catalog_product.update',
            array(
              $nimitys,
              $configurable
            )
          );
          $this->log("Tuote '{$nimitys}' päivitetty (configurable) " . print_r($configurable, true));
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

        // Päivitetään configurable-tuotteen kauppanäkymäkohtaiset hinnat
        if (isset($kauppakohtaiset_hinnat) and count($kauppakohtaiset_hinnat) > 0) {
          try {
            foreach ($kauppakohtaiset_hinnat as $key => $kauppakohtainen_hinta) {
              foreach ($kauppakohtainen_hinta as $tuotekentta => $kauppatunnukset) {
                foreach ($kauppatunnukset as $kauppatunnus) {
                  // Jos asetettu hintakenttä on 0, 0.0 tai '' niin skipataan, tämä
                  // sitävarten että voidaan antaa "default"-arvoja(myyntihinta) jotka yliajetaan esimerkiksi
                  // hinnastohinnalla, mutta vain jos sellainen löytyy ja on voimassa
                  if (empty($tuotteet[0][$tuotekentta])) continue;
                  $tuotteen_kauppakohtainen_data = array(
                    'price' => $tuotteet[0][$tuotekentta]
                  );

                  if (!empty($kauppakohtaiset_verokannat[$kauppatunnus])) {
                    $tuotteen_kauppakohtainen_data['tax_class_id'] = $kauppakohtaiset_verokannat[$kauppatunnus];
                  }

                  $this->_proxy->call($this->_session, 'catalog_product.update',
                    array(
                      $nimitys,
                      $tuotteen_kauppakohtainen_data,
                      $kauppatunnus
                      )
                  );
                }
                $this->log("Tuotteen '{$nimitys}' kauppakohtainen hinta päivitetty (configurable) " . print_r($tuotteen_kauppakohtainen_data, true));
              }
            }
          }
          catch (Exception $e) {
            $this->log("Virhe! Tuotteen '{$nimitys}' kauppakohtaisen hinnan päivitys epäonnistui (configurable) " . print_r($tuotteen_kauppakohtainen_data, true), $e);
          }
        }

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

    $this->log("Haetaan tilauksia", '', $type = 'order');

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
      $this->log("Haetaan tilaus {$order['increment_id']}", '', $type = 'order');

      // Haetaan tilauksen tiedot (orders)
      $temp_order = $this->_proxy->call($this->_session, 'sales_order.info', $order['increment_id']);

      // Looptaan tilauksen statukset
      foreach ($temp_order['status_history'] as $historia) {
        // Jos tilaus on ollut kerran jo processing_pupesoft, ei haeta sitä enää
        $_status = $historia['status'];

        if ($_status == "processing_pupesoft" and $this->_sisaanluvun_esto == "YES") {
          $this->log("Tilausta on käsitelty {$_status} tilassa, ohitetaan sisäänluku", '', $type = 'order');
          // Skipataan tämä $order
          continue 2;
        }
      }

      $orders[] = $temp_order;

      // Päivitetään tilauksen tila että se on noudettu pupesoftiin
      $_data = array(
        'orderIncrementId' => $order['increment_id'],
        'status' => 'processing_pupesoft',
        'Tilaus noudettu Pupesoftiin',
      );

      $this->_proxy->call($this->_session, 'sales_order.addComment', $_data);
    }

    // Kirjataan kumpaankin logiin
    $_count = count($orders);

    $this->log("{$_count} tilausta haettu", '', $type = 'order');
    $this->log("{$_count} tilausta haettu");

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

  /**
   * Virhelogi
   *
   * @param string  $message   Virheviesti
   * @param exception $exception Exception
   * @param string  $type      Kirjataanko tuote vai tilauslogiin
   */
  public function log($message, $exception = '', $type = 'product') {
    if (self::LOGGING === false) {
      return;
    }

    if ($exception != '') {
      $message .= " (" . $exception->getMessage() . ") faultcode: " . $exception->faultcode;
    }

    $log_name = $type == 'product' ? 'magento_export' : 'magento_orders';

    pupesoft_log($log_name, $message);
  }

  /// Private functions ///

  /**
   * Parametrinä tulee yhden tuotteen koko tuotepolku
   * eli tuotepuun tuoteryhmät järjestyksessä rootista lähtien
   *
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
   *
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

    $name = utf8_encode($name);
    $value = utf8_encode($value);
    $attribute_list = $this->getAttributeList();
    $attribute_id = '';

    // Etsitään halutun attribuutin id
    foreach ($attribute_list as $attribute) {
      if (strcasecmp($attribute['code'], $name) == 0) {
        $attribute_id = $attribute['attribute_id'];
        $attribute_type = $attribute['type'];
        break;
      }
    }

    // Jos attribuuttia ei löytynyt niin turha ettiä option valuea
    if (empty($attribute_id)) return 0;

    // Jos dynaaminen parametri on matkalla teksti- tai hintakenttään niin idtä ei tarvita, palautetaan vaan arvo
    if ($attribute_type == 'text' or $attribute_type == 'textarea' or $attribute_type == 'price') {
      return $value;
    }

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
    if ($attribute_type == "select" or $attribute_type == "multiselect") {
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
      $this->log("Luotiin uusi attribuutti $value optioid $attribute_id");

      // Haetaan kaikki attribuutin optionssit uudestaan..
      $options = $this->_proxy->call(
        $this->_session,
        "product_attribute.options",
        array(
          $attribute_id
        )
      );
      // Etitään optionsin value uudestaan..
      foreach ($options as $option) {
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
    global $kukarow;

    // Populoidaan tuotekuvat array
    $tuotekuvat = array();

    try {

      $query = "SELECT
                liitetiedostot.data,
                liitetiedostot.filetype,
                liitetiedostot.filename
                FROM liitetiedostot
                WHERE liitetiedostot.yhtio         = '{$kukarow['yhtio']}'
                AND liitetiedostot.liitostunnus    = '{$tunnus}'
                AND liitetiedostot.liitos          = 'tuote'
                AND liitetiedostot.kayttotarkoitus = 'TK'
                ORDER BY liitetiedostot.jarjestys DESC,
                liitetiedostot.tunnus DESC";
      $result = pupe_query($query);

      while ($liite = mysql_fetch_assoc($result)) {
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
      $this->log("Virhe! Tietokantayhteys on poikki. Yritetään uudelleen.", $e);
    }

    // Palautetaan tuotekuvat
    return $tuotekuvat;
  }

  /**
   * Lisää päivitettyjä asiakkaita Magento-verkkokauppaan.
   *
   * @param array   $dnsasiakas Pupesoftin tuote_exportin palauttama asiakas array
   * @return int               Lisättyjen asiakkaiden määrä
   */
  public function lisaa_asiakkaat(array $dnsasiakas) {

    $this->log("Lisätään asiakkaita");
    // Asiakas countteri
    $count = 0;

    // Asiakkaiden erikoisparametrit
    $asiakkaat_erikoisparametrit = $this->_asiakkaat_erikoisparametrit;

    // Lisätään asiakkaat ja osoitteet erissä
    foreach ($dnsasiakas as $asiakas) {

      $asiakasryhma_id = $this->findCustomerGroup(utf8_encode($asiakas['asiakasryhma']));

      $asiakas_data = array(
        'email'       => utf8_encode($asiakas['yhenk_email']),
        'firstname'   => utf8_encode($asiakas['nimi']),
        'lastname'    => utf8_encode($asiakas['nimi']),
        'website_id'  => utf8_encode($asiakas['magento_website_id']),
        'taxvat'      => $asiakas['ytunnus'],
        'external_id' => $asiakas['asiakasnro'],
        'group_id'    => $asiakasryhma_id,
      );

      $laskutus_osoite_data = array(
        'firstname'  => utf8_encode($asiakas['laskutus_nimi']),
        'lastname'   => utf8_encode($asiakas['laskutus_nimi']),
        'street'     => array(utf8_encode($asiakas['laskutus_osoite'])),
        'postcode'   => utf8_encode($asiakas['laskutus_postino']),
        'city'       => utf8_encode($asiakas['laskutus_postitp']),
        'country_id' => utf8_encode($asiakas['maa']),
        'telephone'  => utf8_encode($asiakas['yhenk_puh']),
        'company'    => utf8_encode($asiakas['nimi']),
        'is_default_billing'    => true,
      );

      $toimitus_osoite_data = array(
        'firstname'  => utf8_encode($asiakas['toimitus_nimi']),
        'lastname'   => utf8_encode($asiakas['toimitus_nimi']),
        'street'     => array(utf8_encode($asiakas['toimitus_osoite'])),
        'postcode'   => utf8_encode($asiakas['toimitus_postino']),
        'city'       => utf8_encode($asiakas['toimitus_postitp']),
        'country_id' => utf8_encode($asiakas['maa']),
        'telephone'  => utf8_encode($asiakas['yhenk_puh']),
        'company'    => utf8_encode($asiakas['nimi']),
        'is_default_shipping' => true
      );

      if (count($asiakkaat_erikoisparametrit) > 0) {
        foreach ($asiakkaat_erikoisparametrit as $erikoisparametri) {
          $key = $erikoisparametri['nimi'];
          $value = $erikoisparametri['arvo'];
          // Jos value löytyy asiakas-arraysta, käytetään sitä
          if (isset($asiakas[$value])) {
            $asiakas_data[$key] = utf8_encode($asiakas[$value]);
            $laskutus_osoite_data[$key] = utf8_encode($asiakas[$value]);
            $toimitus_osoite_data[$key] = utf8_encode($asiakas[$value]);
          }
        }
      }

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

          $this->log("Asiakas '{$asiakas['tunnus']}' / '{$asiakas['yhenk_tunnus']}' / {$result} lisätty " . print_r($asiakas_data, true));
          $asiakas['magento_tunnus'] = $result;

          // Päivitetään magento_tunnus pupeen
          $query = "UPDATE yhteyshenkilo
                    SET ulkoinen_asiakasnumero = '{$asiakas['magento_tunnus']}'
                    WHERE yhtio      = '{$asiakas['yhtio']}'
                    AND liitostunnus = '{$asiakas['tunnus']}'
                    AND rooli        = 'Magento'
                    AND tunnus       = '{$asiakas['yhenk_tunnus']}'";
          pupe_query($query);

        }
        catch (Exception $e) {
          $this->_error_count++;
          $this->log("Virhe! Asiakkaan '{$asiakas['tunnus']}' / '{$asiakas['yhenk_tunnus']}' lisäys epäonnistui " . print_r($asiakas_data, true), $e);
        }
      }
      // Asiakas on jo olemassa, päivitetään
      else {
        try {

          $poista_asiakas_defaultit = $this->_magento_poista_asiakasdefaultit;

          // Jos halutaan ohittaa asiakasparametreja, poistetaan ne ennen paivitysta
          if (count($poista_asiakas_defaultit) > 0) {
            foreach ($poista_asiakas_defaultit as $poistettava_key) {
              unset($asiakas_data[$poistettava_key]);
            }
          }

          $result = $this->_proxy->call(
            $this->_session,
            'customer.update',
            array(
              $asiakas['magento_tunnus'],
              $asiakas_data
            ));

          $this->log("Asiakas '{$asiakas['tunnus']}' / '{$asiakas['yhenk_tunnus']}' / {$asiakas['magento_tunnus']} päivitetty " . print_r($asiakas_data, true));

          // Lähetetään aktivointiviesti Magentoon jos ominaisuus on päällä sekä yhteyshenkilölle
          // on merkattu magentokuittaus
          if ($this->_asiakkaan_aktivointi and $this->aktivoidaankoAsiakas($asiakas['tunnus'], $asiakas['magento_tunnus'])) {
            $result = $this->asiakkaanAktivointi($asiakas['yhtio'], $asiakas['yhenk_tunnus']);
            if ($result) {
              $this->log("Yhteyshenkilön: '{$asiakas['yhenk_tunnus']}' Magentoasiakas: {$asiakas['magento_tunnus']} aktivoitu " . print_r($asiakas_data, true));
            }
          }
        }
        catch (Exception $e) {
          $this->_error_count++;
          $this->log("Virhe! Asiakkaan '{$asiakas['tunnus']}' / '{$asiakas['yhenk_tunnus']}' päivitys epäonnistui " . print_r($asiakas_data, true), $e);
        }
      }

      try {
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

      }
      catch (Exception $e) {
        $this->log("Virhe! Asiakkaan '{$asiakas['tunnus']}' osoitteiden haku epäonnistui " . print_r("Asiakkaan magento_tunnus: {$asiakas['magento_tunnus']}", true), $e);
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
   * Hakee tuotteen kieliversiot(tuotenimitys, tuotekuvaus) Pupesoftista
   *
   * @param string  $tuotenumero Tuotteen tuotenumero (tuote.tuoteno)
   * @return array   $kieliversiot_data   Palauttaa arrayn joka on valmiiksi utf8-enkoodattu
   *
   * Esim.
   * $kieliversiot_data['en'] = array(
   *   'nimitys' => 'ADAPTOR',
   *   'kuvaus' => 'ADAPTOR circular IP44- 2 components'
   * );
   *
   */
  public function hae_kieliversiot($tuotenumero) {
    global $kukarow;

    $kieliversiot_data = array();

    try {
      $query = "SELECT
                kieli, laji, selite
                FROM
                tuotteen_avainsanat
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tuoteno = '{$tuotenumero}'
                AND laji    IN ('nimitys','kuvaus', 'yksikko')";
      $result = pupe_query($query);

      while ($avainsana = mysql_fetch_assoc($result)) {
        $kieli  = $avainsana['kieli'];
        $laji   = utf8_encode($avainsana['laji']);
        $selite = utf8_encode($avainsana['selite']);

        // Jäsennellään tuotteen avainsanat kieliversioittain
        $kieliversiot_data[$kieli][$laji] = $selite;
      }
    }
    catch (Exception $e) {
      $this->_error_count++;
      $this->log("Virhe! Tietokantayhteys on poikki. Yritetään uudelleen.", $e);
    }

    // Palautetaan kieliversiot
    return $kieliversiot_data;
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
   * @param string  $magento_kategoriat
   */
  public function setKategoriat($magento_kategoriat) {
    $this->_kategoriat = $magento_kategoriat;
  }

  /**
   * Asettaa categoryaccesscontrol-muuttujan
   * Oletus FALSE
   *
   * @param string  $categoryaccesscontrol BOOLEAN
   */
  public function setCategoryaccesscontrol($categoryaccesscontrol) {
    $this->_categoryaccesscontrol = $categoryaccesscontrol;
  }

  /**
   * Asettaa configurable_nimityskentta-muuttujan
   * Oletus 'nimitys'
   *
   * @param string  $configurable_nimityskentta
   */
  public function setConfigurableNimityskentta($configurable_tuote_nimityskentta) {
    $this->_configurable_tuote_nimityskentta = $configurable_tuote_nimityskentta;
  }

  /**
   * Asettaa configurable_lapsituote_nakyvyys-muuttujan
   * Oletus 'NOT_VISIBLE_INDIVIDUALLY'
   *
   * @param string  $configurable_lapsituote_nakyvyys
   */
  public function setConfigurableLapsituoteNakyvyys($configurable_lapsituote_nakyvyys) {
    $this->_configurable_lapsituote_nakyvyys = $configurable_lapsituote_nakyvyys;
  }

  /**
   * Asettaa verkkokauppatuotteiden erikoisparametrit
   *
   * @param array   $verkkokauppatuotteet_erikoisparametrit
   */
  public function setVerkkokauppatuotteetErikoisparametrit($verkkokauppatuotteet_erikoisparametrit) {
    $this->_verkkokauppatuotteet_erikoisparametrit = $verkkokauppatuotteet_erikoisparametrit;
  }

  /**
   * Asettaa verkkokauppa-asiakkaiden erikoisparametrit
   *
   * @param array   $asiakkaat_erikoisparametrit
   */
  public function setAsiakkaatErikoisparametrit($asiakkaat_erikoisparametrit) {
    $this->_asiakkaat_erikoisparametrit = $asiakkaat_erikoisparametrit;
  }

  /**
   * Magentossa käsin hallitut kategoriat joita ei poisteta tuotteelta tuotepäivityksessä
   */
  public function setStickyKategoriat($magento_sticky_kategoriat) {
    $this->_sticky_kategoriat = $magento_sticky_kategoriat;
  }

  /**
   * Estetäänkö tilauksen sisäänluku jos sitä on jo historian aikana käsitelty tilassa
   * 'processing_pupesoft' Oletus YES
   */
  public function setSisaanluvunEsto($sisaanluvun_esto) {
    $this->_sisaanluvun_esto = $sisaanluvun_esto;
  }

  /**
   * Asetetaanko uudet tuotteet aina samaan kategoriaan
   * ja estetään tuotepäivityksessä tuoteryhmän päivitys
   * Oletus tyhja
   */
  public function setUniversalTuoteryhma($universal_tuoteryhma) {
    $this->_universal_tuoteryhma = $universal_tuoteryhma;
  }

  /**
   * Aktivoidaanko asiakas luonnin yhteydessä Magentoon
   * Oletus false
   */
  public function setAsiakasAktivointi($asiakas_aktivointi) {
    $tila = $asiakas_aktivointi ? $asiakas_aktivointi : false;
    $this->_asiakkaan_aktivointi = $tila;
  }

  /**
   * Siirretäänkö asiakaskohtaiset tuotehinnat Magentoon
   * Oletus false
   */
  public function setAsiakaskohtaisetTuotehinnat($asiakaskohtaiset_tuotehinnat) {
    $tila = $asiakaskohtaiset_tuotehinnat ? $asiakaskohtaiset_tuotehinnat : false;
    $this->_asiakaskohtaiset_tuotehinnat = $tila;
  }

  /**
   * Poistetaanko/yliajetaanko Magenton default-tuoteparametrejä
   * Oletus tyhja array
   */
  public function setPoistaDefaultTuoteparametrit(array $poistettavat) {
    $this->_magento_poistadefaultit = $poistettavat;
  }

  /**
   * Poistetaanko/yliajetaanko Magenton asiakkaan default-parametrejä
   * Oletus tyhja array
   */
  public function setPoistaDefaultAsiakasparametrit(array $poistettavat_asiakasparamit) {
    $this->_magento_poista_asiakasdefaultit = $poistettavat_asiakasparamit;
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
   * Kuittaa asiakkaan aktivoiduksi Magentossa
   *   HUOM! Vaatii räätälöidyn Magenton
   *
   * @param yhtio,  yhteyshenkilön tunnus
   * @return boolean reply (onnistuiko toiminto)
   */
  public function asiakkaanAktivointi($yhtio, $yhteyshenkilon_tunnus) {
    $reply = false;

    // Haetaan yhteyshenkilön tiedot
    try {
      $query = "SELECT
                email,
                ulkoinen_asiakasnumero id
                FROM
                yhteyshenkilo
                WHERE yhtio                 = '{$yhtio}'
                AND rooli                   = 'Magento'
                AND tunnus                  = '{$yhteyshenkilon_tunnus}'
                AND email                  != ''
                AND ulkoinen_asiakasnumero != ''
                LIMIT 1";
      $result = pupe_query($query);
      $yhenkrow = mysql_fetch_assoc($result);

      // Aktivoidaan asiakas Magentoon
      $reply = $this->_proxy->call(
        $this->_session,
        'activate_customer.activateBusinessCustomer',
        array($yhenkrow['email'], $this->_asiakkaan_aktivointi));

      // Merkataan aktivointikuittaus tehdyksi
      $putsausquery = "UPDATE yhteyshenkilo
                       SET aktivointikuittaus = ''
                       WHERE yhtio = '{$yhtio}'
                       AND tunnus  = '{$yhteyshenkilon_tunnus}'";
      pupe_query($putsausquery);
    }
    catch (Exception $e) {
      $this->_error_count++;
      $this->log("Virhe! Asiakkaan aktivointi epäonnistui.", $e);
    }

    return $reply;
  }

  /**
   * Hakee ja siirtää tuotteen asiakaskohtaiset hinnat Magentoon
   *   HUOM! Vaatii räätälöidyn Magenton
   *
   * @param tuotenumero, magenton tuotenumero
   * @return true/false
   */
  public function lisaaAsiakaskohtaisetTuotehinnat($tuotenumero, $magento_tuotenumero) {
    global $kukarow;

    $reply = false;
    $asiakaskohtainenhintadata = array();

    try {
      // Haetaan Pupesta kaikki Magento-asiakkaat ja näiden yhteyshenkilöt
      $asiakkaat_per_yhteyshenkilo = $this->hae_magentoasiakkaat_ja_yhteyshenkilot($kukarow['yhtio']);

      if (count($asiakkaat_per_yhteyshenkilo) < 1) {
        return false;
      }

      // Ensin poistetaan tuotteen asiakashinnat Magentosta
      $this->poista_tuotteen_asiakaskohtaiset_hinnat($asiakkaat_per_yhteyshenkilo, $magento_tuotenumero);

      // Sitten haetaan asiakaskohtainen hintadata Pupesta
      $asiakaskohtainenhintadata = $this->hae_tuotteen_asiakaskohtaiset_hinnat($asiakkaat_per_yhteyshenkilo, $tuotenumero);

      // Lopuksi siirretään tuotteen kaikki asiakaskohtaiset hinnat Magentoon
      if (count($asiakaskohtainenhintadata) > 0) {

        $reply = $this->_proxy->call($this->_session, 'price_per_customer.setPriceForCustomersPerProduct',
          array($magento_tuotenumero, $asiakaskohtainenhintadata));
        $this->log("Tuotteen {$magento_tuotenumero} asiakaskohtaiset hinnat lisätty " . print_r($asiakaskohtainenhintadata, true));
      }
    }
    catch (Exception $e) {
      $this->_error_count++;
      $this->log("Virhe!", $e);
    }

    return $reply;
  }

  /**
   * Hakee ja siirtää tuotteiden kuvat Magentoon
   *
   * @param array   tuotteet
   */
  public function lisaa_tuotteiden_kuvat(array $tuotteet) {
    global $kukarow, $yhtiorow;

    foreach ($tuotteet as $tuote) {
      // numeerisesta sku_+N
      if (is_numeric($tuote['tuoteno'])) $tuote['tuoteno'] = "SKU_".$tuote['tuoteno'];

      // Haetaan tuotteen tunnus Magentosta
      $result = $this->_proxy->call($this->_session, 'catalog_product.info', $tuote['tuoteno']);
      $product_id = $result['product_id'];

      // Haetaan tuotteen kuvat Pupesta
      $tuotekuvat = $this->hae_tuotekuvat($tuote['tunnus']);

      if (count($tuotekuvat) > 0 and !empty($product_id)) {
        // Lisataan tuotteen kuvat Magentoon
        $this->lisaa_tuotekuvat($product_id, $tuotekuvat);
      }
    }
  }

  /**
   * Hakee verkkokauppatuotteet Pupesta
   *
   * @return array(0 => array kaikki_tuotenumerot, 1 => array individual_tuotenumerot)
   */
  public function hae_kaikki_tuotteet() {
    global $kukarow, $yhtiorow;

    $individual_tuotteet = array();
    $kaikki_tuotteet = array();

    // Haetaan pupesta kaikki tuotteet (ja configurable-tuotteet), jotka pitää olla Magentossa
    $query = "SELECT DISTINCT tuote.tuoteno, tuotteen_avainsanat.selite configurable_tuoteno
              FROM tuote
              LEFT JOIN tuotteen_avainsanat ON (tuote.yhtio = tuotteen_avainsanat.yhtio
              AND tuote.tuoteno             = tuotteen_avainsanat.tuoteno
              AND tuotteen_avainsanat.laji  = 'parametri_variaatio'
              AND trim(tuotteen_avainsanat.selite) != '')
              WHERE tuote.yhtio             = '{$kukarow["yhtio"]}'
              AND tuote.status             != 'P'
              AND tuote.tuotetyyppi         NOT in ('A','B')
              AND tuote.tuoteno            != ''
              AND tuote.nakyvyys           != ''";
    $res = pupe_query($query);

    // Kaikki tuotenumerot arrayseen
    while ($row = mysql_fetch_array($res)) {
      $kaikki_tuotteet[] = $row['tuoteno'];

      if ($row['configurable_tuoteno'] == "") $individual_tuotteet[$row['tuoteno']] = $row['tuoteno'];
      if ($row['configurable_tuoteno'] != "") $kaikki_tuotteet[] = $row['configurable_tuoteno'];
    }

    $kaikki_tuotteet = array_unique($kaikki_tuotteet);

    return array($kaikki_tuotteet, $individual_tuotteet);
  }

  private function hae_magentoasiakkaat_ja_yhteyshenkilot($yhtio) {
    $asiakkaat_per_yhteyshenkilo = array();

    $query = "SELECT asiakas.tunnus asiakastunnus,
              asiakas.ytunnus,
              yhteyshenkilo.email asiakas_email,
              yhteyshenkilo.ulkoinen_asiakasnumero
              FROM yhteyshenkilo
              JOIN asiakas ON (yhteyshenkilo.yhtio = asiakas.yhtio
                AND yhteyshenkilo.liitostunnus            = asiakas.tunnus)
              WHERE yhteyshenkilo.yhtio                   = '{$yhtio}'
                AND yhteyshenkilo.rooli                   = 'Magento'
                AND yhteyshenkilo.email                  != ''
                AND yhteyshenkilo.ulkoinen_asiakasnumero != ''";
    $result = pupe_query($query);

    while ($rivi = mysql_fetch_assoc($result)) {
      $asiakasdata = array(
        'asiakastunnus'         => $rivi['asiakastunnus'],
        'asiakas_email'         => $rivi['asiakas_email'],
        'magento_asiakastunnus' => $rivi['ulkoinen_asiakasnumero'],
        'ytunnus'               => $rivi['ytunnus']
      );
      $asiakkaat_per_yhteyshenkilo[] = $asiakasdata;
    }

    return $asiakkaat_per_yhteyshenkilo;
  }

  private function poista_tuotteen_asiakaskohtaiset_hinnat($asiakkaat_per_yhteyshenkilo, $magento_tuotenumero) {
    // Poistetaan kaikkien asiakkaiden hinta tältä tuotteelta
    $toiminto = false;
    try {
      $asiakashinnat = array();

      foreach ($asiakkaat_per_yhteyshenkilo as $asiakas) {
        $asiakashinnat[] = array(
          'customerEmail' => $asiakas['asiakas_email'],
          'websiteCode' => $this->_asiakaskohtaiset_tuotehinnat,
          'delete' => 1);
      }

      $toiminto = $this->_proxy->call($this->_session, 'price_per_customer.setPriceForCustomersPerProduct',
        array($magento_tuotenumero, $asiakashinnat));
      $this->log("Tuotteen {$magento_tuotenumero} asiakaskohtaiset hinnat poistettu " . print_r($asiakaskohtainenhintadata, true));
    }
    catch (Exception $e) {
      $this->_error_count++;
      $this->log("Virhe asiakaskohtaisten hintojen poistossa!", $e);
    }

    return $toiminto;
  }

  private function hae_tuotteen_asiakaskohtaiset_hinnat($asiakkaat_per_yhteyshenkilo, $tuotenumero) {
    global $kukarow;
    // Haetaan annettujen Magentoasiakkaiden hinnat annetulle tuotteelle
    $asiakaskohtaiset_hinnat_data = array();

    // Haetaan tuotteen tiedot
    $query = "SELECT *
              FROM tuote
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tuoteno = '{$tuotenumero}'";
    $result = pupe_query($query);
    $tuoterow = mysql_fetch_assoc($result);

    $tuotteen_vertailuhinta = $tuoterow['myyntihinta'];

    $asiakashinnat = array();

    $query = "SELECT ";
    foreach ($asiakkaat_per_yhteyshenkilo as $asiakas) {
      // Tuotteen hinta tälle asiakkaalle
      $hinta = 0;
      $kpl = 1;
      unset($row);

      $query = "(SELECT '1' prio, hinta, laji, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, minkpl, valkoodi, tunnus
                 FROM asiakashinta ashin1 USE INDEX (yhtio_asiakas_tuoteno)
                 WHERE yhtio  = '$kukarow[yhtio]'
                 and asiakas  = '$asiakas[asiakastunnus]'
                 and asiakas  > 0
                 and tuoteno  = '$tuoterow[tuoteno]'
                 and tuoteno != ''
                 and (minkpl <= $kpl or minkpl = 0)
                 and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00')))
                 UNION
                 (SELECT '2' prio, hinta, laji, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, minkpl, valkoodi, tunnus
                 FROM asiakashinta ashin2 USE INDEX (yhtio_ytunnus_tuoteno)
                 WHERE yhtio  = '$kukarow[yhtio]'
                 and ytunnus  = '$asiakas[ytunnus]'
                 and ytunnus != ''
                 and tuoteno  = '$tuoterow[tuoteno]'
                 and tuoteno != ''
                 and (minkpl <= $kpl or minkpl = 0)
                 and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00')))
                 ORDER BY prio, minkpl desc, aika, valkoodi DESC, tunnus desc
                 LIMIT 1";
      $result = pupe_query($query);

      if (mysql_num_rows($result) > 0) {
        $row = mysql_fetch_assoc($result);
      }

      if (!isset($row)) {
        $query = "(SELECT '1' prio, hinta, laji, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, minkpl, valkoodi, tunnus
                   FROM asiakashinta ashin1 USE INDEX (yhtio_asiakas_ryhma)
                   WHERE yhtio  = '$kukarow[yhtio]'
                   and asiakas  = '$asiakas[asiakastunnus]'
                   and asiakas  > 0
                   and ryhma    = '$tuoterow[aleryhma]'
                   and ryhma   != ''
                   and (minkpl <= $kpl or minkpl = 0)
                   and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00')))
                   UNION
                   (SELECT '2' prio, hinta, laji, IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999) aika, minkpl, valkoodi, tunnus
                   FROM asiakashinta ashin2 USE INDEX (yhtio_ytunnus_ryhma)
                   WHERE yhtio  = '$kukarow[yhtio]'
                   and ytunnus  = '$asiakas[ytunnus]'
                   and ytunnus != ''
                   and ryhma    = '$tuoterow[aleryhma]'
                   and ryhma   != ''
                   and (minkpl <= $kpl or minkpl = 0)
                   and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00')))
                   ORDER BY prio, minkpl desc, aika, valkoodi DESC, tunnus desc
                   LIMIT 1";

        $result = pupe_query($query);

        if (mysql_num_rows($result) > 0) {
          $row = mysql_fetch_assoc($result);
        }
      }

      if (!isset($row)) {
        $query = "(SELECT '1' prio, alennus, alennuslaji, minkpl, IFNULL(TO_DAYS(CURRENT_DATE)-TO_DAYS(alkupvm),9999999999999) aika, tunnus
                   FROM asiakasalennus asale1 USE INDEX (yhtio_asiakas_tuoteno)
                   WHERE yhtio  = '$kukarow[yhtio]'
                   AND asiakas  = '$asiakas[asiakastunnus]'
                   AND asiakas  > 0
                   AND tuoteno  = '$tuoterow[tuoteno]'
                   AND tuoteno != ''
                   AND (minkpl = 0 OR (minkpl <= $kpl AND monikerta = '') OR (MOD($kpl, minkpl) = 0 AND monikerta != ''))
                   AND ((alkupvm <= CURRENT_DATE AND IF (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= CURRENT_DATE) OR (alkupvm='0000-00-00' AND loppupvm='0000-00-00'))
                   AND alennus  >= 0
                   AND alennus  <= 100)
                   UNION
                   (SELECT '2' prio, alennus, alennuslaji, minkpl, IFNULL(TO_DAYS(CURRENT_DATE)-TO_DAYS(alkupvm),9999999999999) aika, tunnus
                   FROM asiakasalennus asale2 USE INDEX (yhtio_ytunnus_tuoteno)
                   WHERE yhtio  = '$kukarow[yhtio]'
                   AND ytunnus  = '$asiakas[ytunnus]'
                   AND ytunnus != ''
                   AND tuoteno  = '$tuoterow[tuoteno]'
                   AND tuoteno != ''
                   AND (minkpl = 0 OR (minkpl <= $kpl AND monikerta = '') OR (MOD($kpl, minkpl) = 0 AND monikerta != ''))
                   AND ((alkupvm <= CURRENT_DATE AND IF (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= CURRENT_DATE) OR (alkupvm='0000-00-00' AND loppupvm='0000-00-00'))
                   AND alennus  >= 0
                   AND alennus  <= 100)
                   ORDER BY alennuslaji, prio, minkpl DESC, aika, alennus DESC, tunnus DESC
                   LIMIT 1";

        $result = pupe_query($query);

        if (mysql_num_rows($result) > 0) {
          $row = mysql_fetch_assoc($result);
        }
      }

      if (!isset($row)) {
        $query = "(SELECT '1' prio, alennus, alennuslaji, minkpl, IFNULL(TO_DAYS(CURRENT_DATE)-TO_DAYS(alkupvm),9999999999999) aika, tunnus
                   FROM asiakasalennus asale1 USE INDEX (yhtio_asiakas_ryhma)
                   WHERE yhtio  = '$kukarow[yhtio]'
                   AND asiakas  = '$asiakas[asiakastunnus]'
                   AND asiakas  > 0
                   AND ryhma    = '$tuoterow[aleryhma]'
                   AND ryhma   != ''
                   AND (minkpl = 0 OR (minkpl <= $kpl AND monikerta = '') OR (MOD($kpl, minkpl) = 0 AND monikerta != ''))
                   AND ((alkupvm <= CURRENT_DATE AND IF (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= CURRENT_DATE) OR (alkupvm='0000-00-00' AND loppupvm='0000-00-00'))
                   AND alennus  >= 0
                   AND alennus  <= 100)
                   UNION
                   (SELECT '2' prio, alennus, alennuslaji, minkpl, IFNULL(TO_DAYS(CURRENT_DATE)-TO_DAYS(alkupvm),9999999999999) aika, tunnus
                   FROM asiakasalennus asale2 USE INDEX (yhtio_ytunnus_ryhma)
                   WHERE yhtio  = '$kukarow[yhtio]'
                   AND ytunnus  = '$asiakas[ytunnus]'
                   AND ytunnus != ''
                   AND ryhma    = '$tuoterow[aleryhma]'
                   AND ryhma   != ''
                   AND (minkpl = 0 OR (minkpl <= $kpl AND monikerta = '') OR (MOD($kpl, minkpl) = 0 AND monikerta != ''))
                   AND ((alkupvm <= CURRENT_DATE AND IF (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= CURRENT_DATE) OR (alkupvm='0000-00-00' AND loppupvm='0000-00-00'))
                   AND alennus  >= 0
                   AND alennus  <= 100)
                   ORDER BY alennuslaji, prio, minkpl DESC, aika, alennus DESC, tunnus desc
                   LIMIT 1";
        $result = pupe_query($query);

        if (mysql_num_rows($result) > 0) {
          $row = mysql_fetch_assoc($result);
        }
      }

      // Asetetaan hintamuuttujaan joko:
      if (isset($row)) {
        // löydetty asiakashinta
        if (isset($row['hinta'])) {
          $hinta = $row['hinta'];
        }
        // tai lasketaan alennus pois myyntihinnasta
        if (isset($row['alennus'])) {
          $hinta = $tuoterow['myyntihinta'];
          $kokonaisale = (1 - $row['alennus'] / 100);
          $hinta = round(($hinta * $kokonaisale), 2);
        }
      }

      if ($hinta > 0 and $hinta <> $tuotteen_vertailuhinta) {
        $asiakaskohtaiset_hinnat_data[] = array(
          'customerEmail' => $asiakas['asiakas_email'],
          'websiteCode' => $this->_asiakaskohtaiset_tuotehinnat,
          'price' => $hinta,
          'delete' => 0);
      }
    }

    return $asiakaskohtaiset_hinnat_data;
  }

  /**
   * Tarkistaa onko tämä asiakkaan yhteyshenkilö merkattu kuitattavaksi
   *
   * @param asiakastunnus, asiakkaan magentotunnus(yhteyshenkilo.ulkoinen_asiakasnumero)
   * @return true/false
   */
  private function aktivoidaankoAsiakas($asiakastunnus, $asiakkaan_magentotunnus) {
    global $kukarow;

    $query = "SELECT yhteyshenkilo.aktivointikuittaus tieto
              FROM yhteyshenkilo
              JOIN asiakas ON (yhteyshenkilo.yhtio = asiakas.yhtio
                AND yhteyshenkilo.liitostunnus           = asiakas.tunnus
                AND asiakas.tunnus                       = '{$asiakastunnus}')
              WHERE yhteyshenkilo.yhtio                  = '{$kukarow['yhtio']}'
                AND yhteyshenkilo.ulkoinen_asiakasnumero = '{$asiakkaan_magentotunnus}'";
    $result = pupe_query($query);
    $vastausrivi = mysql_fetch_assoc($result);

    $vastaus = !empty($vastausrivi['tieto']);
    return $vastaus;
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
}
