<?php
/**
 * SOAP-clientin wrapperi Magento-verkkokaupan p‰ivitykseen
 *
 * K‰ytet‰‰n suoraan rajapinnat/tuote_export.php tiedostoa, jolla haetaan
 * tarvittavat tiedot pupesoftista.
 *
 * Lis‰‰ tai p‰ivitt‰‰ kategoriat, tuotteet ja saldot.
 * Hakee tilauksia pupesoftiin.
 */

require_once "rajapinnat/edi.php";

class MagentoClient {

  // Kutsujen m‰‰r‰ multicall kutsulla
  const MULTICALL_BATCH_SIZE = 100;

  // Product visibility
  const NOT_VISIBLE_INDIVIDUALLY = 1;
  const CATALOG                  = 2;
  const SEARCH                   = 3;
  const CATALOG_SEARCH           = 4;

  // Product Status
  const ENABLED  = 1;
  const DISABLED = 2;

  // Soap client
  private $_proxy;

  // Soap clientin sessio
  private $_session;

  // Lokitetaanko debug infoa
  private $debug_logging = false;

  // Magenton oletus attributeSet
  private $_attributeSet;

  // Tuotekategoriat
  private $_category_tree;

  // Verkkokaupan veroluokan tunnus
  private $_tax_class_id = 0;

  // Verkkokaupan "root" kategorian tunnus, t‰m‰n alle lis‰t‰‰n kaikki tuoteryhm‰t
  private $_parent_id = 3;

  // Verkkokaupan "hinta"-kentt‰, joko myymalahinta tai myyntihinta
  private $_hintakentta = "myymalahinta";

  // Verkkokaupassa k‰ytett‰v‰t tuoteryhm‰t, default tuoteryhm‰ tai tuotepuu
  private $_kategoriat = "tuoteryhma";

  // Onko "Category access control"-moduli on asennettu?
  private $_categoryaccesscontrol = false;

  // Tuotteella k‰ytett‰v‰ nimityskentt‰
  private $_configurable_tuote_nimityskentta = "nimitys";
  private $magento_simple_tuote_nimityskentta = "nimitys";

  // Miten configurable-tuotteen lapsituotteet n‰ytet‰‰n verkkokaupassa
  private $_configurable_lapsituote_nakyvyys = 'NOT_VISIBLE_INDIVIDUALLY';

  // Tuotteen erikoisparametrit, jotka tulevat jostain muualta kuin dynaamisista parametreist‰
  private $_verkkokauppatuotteet_erikoisparametrit = array();

  // Asiakkaan erikoisparametrit, joilla ylikirjoitetaan arvoja asiakas- ja osoitetiedoista
  private $_asiakkaat_erikoisparametrit = array();

  // Magentossa k‰sin hallitut kategoria id:t, joita ei poisteta tuotteelta tuotep‰ivityksess‰
  private $_sticky_kategoriat = array();

  // Estet‰‰nkˆ tilauksen sis‰‰nluku, jos se on jo kerran merkattu "processing_pupesoft"-tilaan
  private $_sisaanluvun_esto = "YES";

  // Asetetaan uusille tuotteille aina sama t‰m‰ tuoteryhm‰, p‰ivityksess‰ ei kosketa tuoteryhmiin
  private $_universal_tuoteryhma = "";

  // Aktivoidaanko uusi asiakas Magentoon
  private $_asiakkaan_aktivointi = false;

  // Siirret‰‰nkˆ asiakaskohtaiset tuotehinnat Magentoon
  private $_asiakaskohtaiset_tuotehinnat = false;

  // Lista Magenton default-tuoteparametreist‰, jota ei ikin‰ p‰ivitet‰
  private $_magento_poistadefaultit = array();

  // Lista Magenton default-asiakasparametreist‰, jota ei ikin‰ p‰ivitet‰
  private $_magento_poista_asiakasdefaultit = array();

  // Url-keyn luontia varten k‰ytett‰v‰t parametrit
  private $_magento_url_key_attributes = array();

  // T‰m‰n yhteyden aikana sattuneiden virheiden m‰‰r‰
  private $_error_count = 0;

  // Poistetaanko tuotteita oletuksena
  private $_magento_poista_tuotteita = false;

  // K‰sitell‰‰nkˆ tuotekuvia magentossa
  private $magento_lisaa_tuotekuvat = true;

  // Miss‰ tilassa olevia tilauksia haetaan
  private $magento_fetch_order_status = 'Processing';

  // Perusteaanko tuotteet aina 'disabled' -tilassa
  private $magento_perusta_disabled = false;

  // Lis‰t‰‰nkˆ lapsituotteiden nimeen kaikki variaatioiden arvot
  private $magento_nimitykseen_parametrien_arvot = false;

  // Ovt tunnus, kenelle EDI-tilaukset tehd‰‰n (yhtio.ovttunnus)
  private $ovt_tunnus = null;

  // Mik‰ on EDI-tilauksen tilaustyyppi
  private $pupesoft_tilaustyyppi = '';

  // Mik‰ on EDI-tilauksella rahtikulutuotteen nimitys
  private $rahtikulu_nimitys = 'L‰hetyskulut';

  // Mik‰ on EDI-tilauksella rahtikulutuotteen tuotenumero (yhtio.rahti_tuotenumero)
  private $rahtikulu_tuoteno = null;

  // Mik‰ on EDI-tilauksella asiakasnumero, jolle tilaus tehd‰‰n
  private $verkkokauppa_asiakasnro = null;

  // Minne hakemistoon EDI-tilaus tallennetaan
  private $edi_polku = '/tmp';

  // Korvaavia Maksuehtoja Magenton maksuehdoille
  private $magento_maksuehto_ohjaus = array();

  // Vaihoehtoisia OVT-tunnuksia EDI-tilaukselle
  private $magento_erikoiskasittely = array();

  function __construct($url, $user, $pass, $client_options = array(), $debug = false) {
    try {
      $this->_proxy = new SoapClient($url, $client_options);
      $this->_session = $this->_proxy->login($user, $pass);
      $this->debug_logging = $debug;
      $this->log("Magento p‰ivitysskripti aloitettu");
    }
    catch (Exception $e) {
      $this->_error_count++;
      $this->log("Virhe! Magento-class init failed", $e);
    }
  }

  function __destruct() {
    $this->log("P‰ivitysskripti p‰‰ttyi\n");
  }

  // Lis‰‰ kaikki tai puuttuvat kategoriat Magento-verkkokauppaan.
  public function lisaa_kategoriat(array $dnsryhma) {
    $this->log("Lis‰t‰‰n kategoriat");

    $categoryaccesscontrol = $this->_categoryaccesscontrol;

    $selected_category = $this->_kategoriat;

    if ($selected_category != 'tuoteryhma') {
      $this->log("Ohitetaan kategorioiden luonti. Kategoriatyypiksi valittu tuotepuu.");
      return 0;
    }

    $parent_id = $this->_parent_id; // Magento kategorian tunnus, jonka alle kaikki tuoteryhm‰t lis‰t‰‰n (pit‰‰ katsoa magentosta)
    $count = 0;

    // Loopataan osastot ja tuoteryhmat
    foreach ($dnsryhma as $kategoria) {

      try {
        // Haetaan kategoriat joka kerta koska lis‰tt‰ess‰ puu muuttuu
        $category_tree = $this->getCategories();

        $kategoria['try_fi'] = utf8_encode($kategoria['try_fi']);
        // Kasotaan lˆytyykˆ tuoteryhm‰
        if (!$this->findCategory($kategoria['try_fi'], $category_tree['children'])) {

          // Lis‰t‰‰n kategoria, jos ei lˆytynyt
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

          $this->log("Lis‰ttiin kategoria {$kategoria['try_fi']}");
        }
      }
      catch (Exception $e) {
        $this->_error_count++;
        $this->log("Virhe! Kategoriaa {$kategoria['try_fi']} ei voitu lis‰t‰", $e);
      }
    }

    $this->_category_tree = $this->getCategories();
    $this->log("$count kategoriaa lis‰tty");

    return $count;
  }

  // lis‰‰ Simple -tuotteet Magentoon
  public function lisaa_simple_tuotteet(array $dnstuote, array $individual_tuotteet) {
    $this->log("Lis‰t‰‰n tuotteita (simple)");

    $hintakentta = $this->_hintakentta;
    $selected_category = $this->_kategoriat;
    $verkkokauppatuotteet_erikoisparametrit = $this->_verkkokauppatuotteet_erikoisparametrit;

    // Tuote countteri
    $count = 0;
    $total_count = count($dnstuote);

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
      $this->log("Virhe! Tuotteiden lis‰yksess‰ (simple)", $e);
      return;
    }

    // Lis‰t‰‰n tuotteet eriss‰
    foreach ($dnstuote as $tuote) {
      $count++;
      $this->log("[{$count}/{$total_count}] K‰sitell‰‰‰n tuote '{$tuote['tuoteno']}' (simple)");

      $tuote_clean = $tuote['tuoteno'];

      $category_ids = array();

      if (is_numeric($tuote['tuoteno'])) $tuote['tuoteno'] = "SKU_".$tuote['tuoteno'];

      // Lyhytkuvaus ei saa olla magentossa tyhj‰.
      // K‰ytet‰‰n kuvaus kent‰n tietoja jos lyhytkuvaus on tyhj‰.
      if ($tuote['lyhytkuvaus'] == '') {
        $tuote['lyhytkuvaus'] = '&nbsp;';
      }

      $tuote['kuluprosentti'] = ($tuote['kuluprosentti'] == 0) ? '' : $tuote['kuluprosentti'];
      $tuoteryhmayliajo = $this->_universal_tuoteryhma;
      $tuoteryhmanimi   = $tuote['try_nimi'];

      // Yliajetaan tuoteryhm‰n nimi jos muuttuja on asetettu
      if (!empty($tuoteryhmayliajo)) {
        $tuoteryhmanimi = $tuoteryhmayliajo;
      }

      // Etsit‰‰n kategoria_id tuoteryhm‰ll‰
      if ($selected_category == 'tuoteryhma') {
        $category_ids[] = $this->findCategory(utf8_encode($tuoteryhmanimi), $category_tree['children']);
      }
      else {
        // Etsit‰‰n kategoria_id:t tuotepuun tuotepolulla
        $tuotepuun_nodet = $tuote['tuotepuun_nodet'];

        // Lis‰t‰‰n myˆs tuotepuun kategoriat
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

      $tuote_ryhmahinta_data = array();

      foreach ($tuote['asiakashinnat'] as $asiakashintarivi) {
        $asiakasryhma_nimi = $asiakashintarivi['asiakasryhma'];
        $asiakashinta = $asiakashintarivi['hinta'];
        $asiakasryhma_tunnus = $this->findCustomerGroup(utf8_encode($asiakasryhma_nimi));

        if ($asiakasryhma_tunnus != 0) {
          $tuote_ryhmahinta_data[] = array(
            'customer_group_id' => $asiakasryhma_tunnus,
            'price'             => $asiakashinta,
            'qty'               => 1,
            'websites'          => explode(" ", $tuote['nakyvyys']),
          );
        }
      }

      $multi_data = array();
      $tuetut_kieliversiot = array();
      $kauppakohtaiset_hinnat = array();
      $kauppakohtaiset_verokannat = array();

      $_key = $this->magento_simple_tuote_nimityskentta;
      $tuotteen_nimitys = $tuote[$_key];

      // Simple tuotteiden parametrit kuten koko ja v‰ri
      foreach ($tuote['tuotteen_parametrit'] as $parametri) {
        $key = $parametri['option_name'];
        $multi_data[$key] = $this->get_option_id($key, $parametri['arvo']);

        // Lis‰t‰‰n lapsituotteen nimeen variaatioiden arvot
        if ($this->magento_nimitykseen_parametrien_arvot === true) {
          $tuotteen_nimitys .= " - {$parametri['arvo']}";
        }
      }

      foreach ($verkkokauppatuotteet_erikoisparametrit as $erikoisparametri) {
        $key = $erikoisparametri['nimi'];

        // Kieliversiot ja kauppakohtaiset_hinnat sek‰ kauppakohtaiset_verokannat
        // poimitaan talteen koska niit‰ k‰ytet‰‰n toisaalla
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

      $tuote_data = array(
        'categories'            => $category_ids,
        'websites'              => explode(" ", $tuote['nakyvyys']),
        'name'                  => utf8_encode($tuotteen_nimitys),
        'description'           => utf8_encode($tuote['kuvaus']),
        'short_description'     => utf8_encode($tuote['lyhytkuvaus']),
        'weight'                => $tuote['paino'],
        'status'                => self::ENABLED,
        'visibility'            => $visibility,
        'price'                 => sprintf('%0.2f', $tuote[$hintakentta]),
        'tax_class_id'          => $this->_tax_class_id,
        'meta_title'            => '',
        'meta_keyword'          => '',
        'meta_description'      => '',
        'campaign_code'         => utf8_encode($tuote['campaign_code']),
        'onsale'                => utf8_encode($tuote['onsale']),
        'target'                => utf8_encode($tuote['target']),
        'tier_price'            => $tuote_ryhmahinta_data,
        'additional_attributes' => array('multi_data' => $multi_data),
      );

      // Asetetaan tuotteen url_key mik‰li parametrit m‰‰ritelty
      if (count($this->_magento_url_key_attributes) > 0) {
        $tuote_data['url_key'] = $this->getUrlKeyForProduct($tuote);
      }

      $poista_defaultit = $this->_magento_poistadefaultit;

      // Voidaan yliajaa Magenton defaultparameja jos niit‰ ei haluta
      // tai jos ne halutaan korvata additional_attributesin mukana
      foreach ($poista_defaultit as $poistettava_key) {
        unset($tuote_data[$poistettava_key]);
      }

      // Lis‰t‰‰n tai p‰ivitet‰‰n tuote

      // Jos tuotetta ei ole olemassa niin lis‰t‰‰n se
      if (!in_array($tuote['tuoteno'], $skus_in_store)) {
        try {
          // jos halutaan perustaa tuote disabled tilassa, muutetaan status
          if ($this->magento_perusta_disabled === true) {
            $tuote_data['status'] = self::DISABLED;
            $this->log("Asetetaan tuote Disabled -tilaan");
          }

          $product_id = $this->_proxy->call($this->_session, 'catalog_product.create',
            array(
              'simple',
              $this->_attributeSet['set_id'],
              $tuote['tuoteno'], // sku
              $tuote_data,
            )
          );

          $this->log("Tuote lis‰tty");
          $this->debug($tuote_data);

          // Pit‰‰ k‰yd‰ tekem‰ss‰ viel‰ stock.update kutsu, ett‰ saadaan Manage Stock: YES
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
          $this->log("Virhe! Tuotteen lis‰ys ep‰onnistui", $e);
          $this->debug($tuote_data);
        }
      }
      // Tuote on jo olemassa, p‰ivitet‰‰n
      else {
        try {

          $sticky_kategoriat = $this->_sticky_kategoriat;
          $tuoteryhmayliajo = $this->_universal_tuoteryhma;

          // Haetaan tuotteen Magenton ID ja nykyiset kategoriat
          $result = $this->_proxy->call($this->_session, 'catalog_product.info', $tuote['tuoteno']);
          $product_id = $result['product_id'];
          $current_categories = $result['categories'];

          // Jos tuotteelta lˆytyy n‰it‰ kategoriatunnuksia ennen updatea ne lis‰t‰‰n takaisin
          if (count($sticky_kategoriat) > 0 and count($current_categories) > 0) {
            foreach ($sticky_kategoriat as $stick) {
              if (in_array($stick, $current_categories)) {
                $tuote_data['categories'][] = $stick;
              }
            }
          }

          // Ei muuteta tuoteryhmi‰ jos yliajo on p‰‰ll‰
          if (!empty($tuoteryhmayliajo)) {
            $tuote_data['categories'] = $current_categories;
          }

          $this->_proxy->call($this->_session, 'catalog_product.update',
            array(
              $tuote['tuoteno'], // sku
              $tuote_data)
          );

          $this->log("Tuotetiedot p‰ivitetty");
          $this->debug($tuote_data);

          // Update tier prices
          /*$result = $this->_proxy->call($this->_session, 'product_tier_price.update', array($tuote['tuoteno'], $tuote_ryhmahinta_data));

          $this->log("Tuotteen '{$tuote['tuoteno']}' erikoishinnasto $result p‰ivitetty " . print_r($tuote_ryhmahinta_data, true));*/
        }
        catch (Exception $e) {
          $this->_error_count++;
          $this->log("Virhe! Tuotteen lis‰ys/p‰ivitys ep‰onnistui", $e);
          $this->debug($tuote_data);
        }
      }

      // P‰ivitet‰‰n tuotteen kieliversiot kauppan‰kym‰kohtaisesti
      // jos n‰m‰ on asetettu konffissa
      if (isset($tuetut_kieliversiot) and count($tuetut_kieliversiot) > 0) {
        try {
          // Kieliversiot-magentoerikoisparametrin tulee sis‰lt‰‰ array jossa m‰‰ritell‰‰n mik‰ kieliversio
          // siirret‰‰n mihinkin kauppatunnukseen

          // Esim. array("en" => array('4','13'), "se" => array('9'));
          $kieliversio_data = $this->hae_kieliversiot($tuote_clean);

          foreach ($tuetut_kieliversiot as $kieli => $kauppatunnukset) {
            if (empty($kieliversio_data[$kieli])) continue;

            $kaannokset = $kieliversio_data[$kieli];

            // P‰ivitet‰‰n jokaiseen kauppatunnukseen haluttu k‰‰nnˆs
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

          $this->log("Kieliversiot p‰ivitetty");
          $this->debug($kieliversio_data);
        }
        catch (Exception $e) {
          $this->log("Virhe! Kieliversioiden p‰ivitys ep‰onnistui", $e);
          $this->debug($kieliversio_data);
          $this->_error_count++;
        }
      }

      // P‰ivitet‰‰n tuotteen kauppan‰kym‰kohtaiset hinnat
      if (isset($kauppakohtaiset_hinnat) and count($kauppakohtaiset_hinnat) > 0) {
        try {
          foreach ($kauppakohtaiset_hinnat as $key => $kauppakohtainen_hinta) {
            foreach ($kauppakohtainen_hinta as $tuotekentta => $kauppatunnukset) {
              $tuotteen_kauppakohtainen_data = array();

              foreach ($kauppatunnukset as $kauppatunnus) {
                // Jos asetettu hintakentt‰ on 0 tai '' niin skipataan, t‰m‰
                // sit‰varten ett‰ voidaan antaa "default"-arvoja(myyntihinta) jotka yliajetaan esimerkiksi
                // hinnastohinnalla, mutta vain jos sellainen lˆytyy ja on voimassa
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

              $this->log("Kauppakohtainen hinta p‰ivitetty");
              $this->debug($tuotteen_kauppakohtainen_data);
            }
          }
        }
        catch (Exception $e) {
          $this->log("Virhe! Kauppakohtaisen hinnan p‰ivitys ep‰onnistui", $e);
          $this->debug($tuotteen_kauppakohtainen_data);
          $this->_error_count++;
        }
      }

      // Haetaan tuotekuvat Pupesoftista
      $tuotekuvat = $this->hae_tuotekuvat($tuote['tunnus']);

      // Lis‰t‰‰n kuvat Magentoon
      $this->lisaa_tuotekuvat($product_id, $tuotekuvat);

      // Lis‰t‰‰n tuotteen asiakaskohtaiset tuotehinnat
      if ($this->_asiakaskohtaiset_tuotehinnat) {
        $this->lisaaAsiakaskohtaisetTuotehinnat($tuote_clean, $tuote['tuoteno']);
      }
    }

    $this->log("$count tuotetta p‰ivitetty (simple)");

    // Palautetaan p‰vitettyjen tuotteiden m‰‰r‰
    return $count;
  }

  // Lis‰‰ Configurable -tuotteet Magentoon
  public function lisaa_configurable_tuotteet(array $dnslajitelma) {
    $this->log("Lis‰t‰‰n tuotteet (configurable)");

    $count = 0;
    $total_count = count($dnslajitelma);

    // Populoidaan attributeSet
    $this->_attributeSet = $this->getAttributeSet();

    // Haetaan storessa olevat tuotenumerot
    $skus_in_store = $this->getProductList(true);

    // Tarvitaan kategoriat
    $category_tree = $this->getCategories();

    $hintakentta = $this->_hintakentta;

    // Erikoisparametrit
    $verkkokauppatuotteet_erikoisparametrit = $this->_verkkokauppatuotteet_erikoisparametrit;

    // Mit‰ kentt‰‰ k‰ytet‰‰n configurable_tuotteen nimen‰
    $configurable_tuote_nimityskentta = $this->_configurable_tuote_nimityskentta;

    $selected_category = $this->_kategoriat;

    // Lis‰t‰‰n tuotteet
    foreach ($dnslajitelma as $nimitys => $tuotteet) {
      if (is_numeric($nimitys)) $nimitys = "SKU_{$nimitys}";

      $count++;
      $this->log("[{$count}/{$total_count}] K‰sittell‰‰n tuote {$nimitys} (configurable)");

      $category_ids = array();

      // Jos lyhytkuvaus on tyhj‰, k‰ytet‰‰n kuvausta?
      if ($tuotteet[0]['lyhytkuvaus'] == '') {
        $tuotteet[0]['lyhytkuvaus'] = '&nbsp';
      }

      // Erikoishinta
      $tuotteet[0]['kuluprosentti'] = ($tuotteet[0]['kuluprosentti'] == 0) ? '' : $tuotteet[0]['kuluprosentti'];
      $tuoteryhmayliajo = $this->_universal_tuoteryhma;
      $tuoteryhmanimi = $tuotteet[0]['try_nimi'];

      // Yliajetaan tuoteryhm‰n nimi jos muuttuja on asetettu
      if (!empty($tuoteryhmayliajo)) {
        $tuoteryhmanimi = $tuoteryhmayliajo;

        $this->log("Asetetaan tuote vakiokategoriaan '{$tuoteryhmayliajo}'");
      }

      // Etsit‰‰n kategoria_id tuoteryhm‰ll‰
      if ($selected_category == 'tuoteryhma') {
        $category_ids[] = $this->findCategory($tuoteryhmanimi, $category_tree['children']);
      }
      else {
        // Etsit‰‰n kategoria_id:t tuotepuun tuotepolulla
        $tuotepuun_nodet = $tuotteet[0]['tuotepuun_nodet'];

        // Lis‰t‰‰n myˆs tuotepuun kategoriat
        if (isset($tuotepuun_nodet) and count($tuotepuun_nodet) > 0) {
          foreach ($tuotepuun_nodet as $tuotepolku) {
            $category_ids[] = $this->createCategoryTree($tuotepolku);
          }
        }
      }

      // Tehd‰‰n 'associated_skus' -kentt‰
      // Vaatii, ett‰ Magentoon asennetaan 'magento-improve-api' -moduli: https://github.com/jreinke/magento-improve-api
      $lapsituotteet_array = array();

      foreach ($tuotteet as $tuote) {
        if (is_numeric($tuote['tuoteno'])) $tuote['tuoteno'] = "SKU_".$tuote['tuoteno'];

        $lapsituotteet_array[] = $tuote['tuoteno'];
      }

      // Configurable-tuotteelle myˆs ensimm‰isen lapsen parametrit
      $configurable_multi_data = array();

      foreach ($tuotteet[0]['parametrit'] as $parametri) {
        $key = $parametri['option_name'];
        $configurable_multi_data[$key] = $this->get_option_id($key, $parametri['arvo']);
      }

      $kauppakohtaiset_hinnat = array();
      $kauppakohtaiset_verokannat = array();

      // Configurable-tuotteelle myˆs ensimm‰isen lapsen erikoisparametrit
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
        'weight'                => $tuotteet[0]['paino'],
        'status'                => self::ENABLED,
        'visibility'            => self::CATALOG_SEARCH, // Configurablet nakyy kaikkialla
        'price'                 => $tuotteet[0][$hintakentta],
        'tax_class_id'          => $this->_tax_class_id,
        'meta_title'            => '',
        'meta_keyword'          => '',
        'meta_description'      => '',
        'additional_attributes' => array('multi_data' => $configurable_multi_data),
        'associated_skus'       => $lapsituotteet_array,
      );

      // Asetetaan configurable-tuotteen url_key mik‰li parametrit m‰‰ritelty
      if (count($this->_magento_url_key_attributes) > 0) {
        $configurable['url_key'] = utf8_encode($this->sanitize_link_rewrite($nimitys));
      }

      $poista_defaultit = $this->_magento_poistadefaultit;

      // Voidaan yliajaa Magenton defaultparameja jos niit‰ ei haluta
      // tai jos ne halutaan korvata additional_attributesin mukana
      foreach ($poista_defaultit as $poistettava_key) {
        unset($configurable[$poistettava_key]);
      }

      try {
        /**
         * Loopataan tuotteen (configurable) lapsituotteet (simple) l‰pi
         * ja p‰ivitet‰‰n niiden attribuutit kuten koko ja v‰ri.
         */
        foreach ($tuotteet as $tuote) {
          if (is_numeric($tuote['tuoteno'])) $tuote['tuoteno'] = "SKU_".$tuote['tuoteno'];

          $multi_data = array();

          // Simple tuotteiden parametrit kuten koko ja v‰ri
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

          // P‰ivitet‰‰n Simple tuote
          $result = $this->_proxy->call(
            $this->_session,
            'catalog_product.update',
            array($tuote['tuoteno'], $simple_tuote_data)
          );

          $this->log("P‰ivitet‰‰n lapsituote '{$tuote['tuoteno']}'");
          $this->debug($simple_tuote_data);
        }

        // Jos configurable tuotetta ei lˆydy, niin lis‰t‰‰n uusi tuote.
        if (!in_array($nimitys, $skus_in_store)) {
          // jos halutaan perustaa tuote disabled tilassa, muutetaan status
          if ($this->magento_perusta_disabled === true) {
            $configurable['status'] = self::DISABLED;
          }

          $product_id = $this->_proxy->call(
            $this->_session,
            'catalog_product.create',
            array(
              'configurable',
              $this->_attributeSet['set_id'],
              $nimitys, // sku
              $configurable
            )
          );

          $this->log("Tuote lis‰tty");
          $this->debug($configurable);
        }
        // P‰ivitet‰‰n olemassa olevaa configurablea
        else {
          $sticky_kategoriat = $this->_sticky_kategoriat;

          // Haetaan tuotteen Magenton ID ja nykyiset kategoriat
          $result = $this->_proxy->call($this->_session, 'catalog_product.info', $nimitys);
          $product_id = $result['product_id'];
          $current_categories = $result['categories'];

          // Jos tuotteelta lˆytyy n‰it‰ kategoriatunnuksia ennen updatea ne lis‰t‰‰n takaisin
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

          $this->log("Tuotetiedot p‰ivitetty");
          $this->debug($configurable);
        }

        // Pit‰‰ k‰yd‰ tekem‰ss‰ viel‰ stock.update kutsu, ett‰ saadaan Manage Stock: YES
        $stock_data = array(
          'is_in_stock'  => 1,
          'manage_stock' => 1,
        );

        $result = $this->_proxy->call(
          $this->_session,
          'product_stock.update',
          array(
            $nimitys, // sku
            $stock_data
          )
        );

        // P‰ivitet‰‰n configurable-tuotteen kauppan‰kym‰kohtaiset hinnat
        if (isset($kauppakohtaiset_hinnat) and count($kauppakohtaiset_hinnat) > 0) {
          try {
            foreach ($kauppakohtaiset_hinnat as $key => $kauppakohtainen_hinta) {
              foreach ($kauppakohtainen_hinta as $tuotekentta => $kauppatunnukset) {
                $tuotteen_kauppakohtainen_data = array();

                foreach ($kauppatunnukset as $kauppatunnus) {
                  // Jos asetettu hintakentt‰ on 0, 0.0 tai '' niin skipataan, t‰m‰
                  // sit‰varten ett‰ voidaan antaa "default"-arvoja(myyntihinta) jotka yliajetaan esimerkiksi
                  // hinnastohinnalla, mutta vain jos sellainen lˆytyy ja on voimassa
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

                $this->log("Kauppakohtainen hinta p‰ivitetty");
                $this->debug($tuotteen_kauppakohtainen_data);
              }
            }
          }
          catch (Exception $e) {
            $this->_error_count++;
            $this->log("Virhe! Kauppakohtaisen hinnan p‰ivitys ep‰onnistui", $e);
            $this->debug($tuotteen_kauppakohtainen_data);
          }
        }

        // Haetaan tuotekuvat Pupesoftista
        $tuotekuvat = $this->hae_tuotekuvat($tuotteet[0]['tunnus']);

        // Lis‰t‰‰n kuvat Magentoon
        $this->lisaa_tuotekuvat($product_id, $tuotekuvat);
      }
      catch (Exception $e) {
        $this->_error_count++;
        $this->log("Virhe! Tuotteen lis‰ys/p‰ivitys ep‰onnistui", $e);
        $this->debug($configurable);
      }
    }

    $this->log("$count tuotetta p‰ivitetty (configurable)");

    // Palautetaan lis‰ttyjen configurable tuotteiden m‰‰r‰
    return $count;
  }

  // Hakee kaikki tilaukset Magentosta ja tallentaa ne edi_tilauksiksi
  public function tallenna_tilaukset() {
    // status, mit‰ tilauksia haetaan
    $status = $this->magento_fetch_order_status;

    // EDI-tilauksen luontiin tarvittavat parametrit
    $options = array(
      'edi_polku'          => $this->edi_polku,
      'ovt_tunnus'         => $this->ovt_tunnus,
      'rahtikulu_nimitys'  => $this->rahtikulu_nimitys,
      'rahtikulu_tuoteno'  => $this->rahtikulu_tuoteno,
      'tilaustyyppi'       => $this->pupesoft_tilaustyyppi,
      'asiakasnro'         => $this->verkkokauppa_asiakasnro,
      'maksuehto_ohjaus'   => $this->magento_maksuehto_ohjaus,
      'erikoiskasittely'   => $this->magento_erikoiskasittely,
    );

    // Haetaan tilaukset magentosta
    try {
      $tilaukset = $this->hae_tilaukset($status);
    }
    catch (Exception $e) {
      $this->log("Tilausten haku ep‰onnistui", $e, "order");
      exit;
    }

    // Tallennetaan EDI-tilauksina
    foreach ($tilaukset as $tilaus) {
      $filename = Edi::create($tilaus, $options);

      $this->log("Tallennettiin tilaus '{$filename}'", null, "order");
      $this->debug($tilaus, null, "order");
    }
  }

  // P‰ivitet‰‰n sadot
  public function paivita_saldot(array $dnstock) {
    $this->log("P‰ivitet‰‰n saldot");

    $count = 0;
    $total_count = count($dnstock);

    // Loopataan p‰ivitett‰v‰t tuotteet l‰pi (aina simplej‰)
    foreach ($dnstock as $tuote) {
      if (is_numeric($tuote['tuoteno'])) $tuote['tuoteno'] = "SKU_".$tuote['tuoteno'];

      // $tuote muuttuja sis‰lt‰‰ tuotenumeron ja myyt‰viss‰ m‰‰r‰n
      $product_sku = $tuote['tuoteno'];
      $qty         = $tuote['myytavissa'];

      $count++;
      $this->log("[{$count}/{$total_count}] P‰ivitet‰‰n tuotteen {$product_sku} saldo {$qty}");

      // Out of stock jos m‰‰r‰ on tuotteella ei ole myytavissa saldoa
      $is_in_stock = ($qty > 0) ? 1 : 0;

      // P‰ivitet‰‰n saldo
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
      }
      catch (Exception $e) {
        $this->_error_count++;
        $this->log("Virhe! Saldop‰ivitys ep‰onnistui!". $e);
      }
    }

    $this->log("$count saldoa p‰ivitetty");

    return $count;
  }

  // Poistaa magentosta tuotteita
  // HUOM, t‰h‰n passataan aina **KAIKKI** verkkokauppatuotteet.
  public function poista_poistetut(array $kaikki_tuotteet, $exclude_giftcards = false) {
    if ($this->_magento_poista_tuotteita !== true) {
      $this->log("Tuoteiden poisto kytketty pois p‰‰lt‰.");

      return 0;
    }

    $skus = $this->getProductList(true, $exclude_giftcards);

    // Loopataan $kaikki_tuotteet-l‰pi ja tehd‰‰n numericmuutos
    foreach ($kaikki_tuotteet as &$tuote) {
      if (is_numeric($tuote)) $tuote = "SKU_".$tuote;
    }

    // Poistetaan tuottee jotka lˆytyv‰t arraysta $kaikki_tuotteet arrayst‰ $skus
    $poistettavat_tuotteet = array_diff($skus, $kaikki_tuotteet);

    $poistettu = 0;
    $count = 0;
    $total_count = count($poistettavat_tuotteet);

    // N‰m‰ kaikki tuotteet pit‰‰ poistaa Magentosta
    foreach ($poistettavat_tuotteet as $tuote) {
      $count++;
      $this->log("[{$count}/{$total_count}] Poistetaan tuote '$tuote'");

      try {
        // T‰ss‰ kutsu, jos tuote oikeasti halutaan poistaa
        $this->_proxy->call($this->_session, 'catalog_product.delete', $tuote, 'SKU');
        $poistettu++;
      }
      catch (Exception $e) {
        $this->_error_count++;
        $this->log("Virhe! Poisto ep‰onnistui!", $e);
      }
    }

    $this->log("$poistettu tuotetta poistettu");

    return $poistettu;
  }

  // Lis‰‰ asiakkaita Magento-verkkokauppaan.
  public function lisaa_asiakkaat(array $dnsasiakas) {
    $this->log("Lis‰t‰‰n asiakkaita");
    // Asiakas countteri
    $count = 0;
    $total_count = count($dnsasiakas);

    // Asiakkaiden erikoisparametrit
    $asiakkaat_erikoisparametrit = $this->_asiakkaat_erikoisparametrit;

    // Lis‰t‰‰n asiakkaat ja osoitteet eriss‰
    foreach ($dnsasiakas as $asiakas) {
      $count++;
      $this->log("[{$count}/{$total_count}] Asiakas '{$asiakas['nimi']}'");

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

          // Jos value lˆytyy asiakas-arraysta, k‰ytet‰‰n sit‰
          if (isset($asiakas[$value])) {
            $asiakas_data[$key] = utf8_encode($asiakas[$value]);
            $laskutus_osoite_data[$key] = utf8_encode($asiakas[$value]);
            $toimitus_osoite_data[$key] = utf8_encode($asiakas[$value]);
          }
        }
      }

      // Lis‰t‰‰n tai p‰ivitet‰‰n asiakas

      // Jos asiakasta ei ole olemassa (sill‰ ei ole pupessa magento_tunnus:ta) niin lis‰t‰‰n se
      if (empty($asiakas['magento_tunnus'])) {
        try {
          $result = $this->_proxy->call(
            $this->_session,
            'customer.create',
            array(
              $asiakas_data
            )
          );

          $this->log("Asiakas lis‰tty ({$result})");
          $this->debug($asiakas_data);
          $asiakas['magento_tunnus'] = $result;

          // P‰ivitet‰‰n magento_tunnus pupeen
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
          $this->log("Virhe! Asiakkaan lis‰ys ep‰onnistui", $e);
          $this->debug($asiakas_data);
        }
      }
      // Asiakas on jo olemassa, p‰ivitet‰‰n
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
            )
          );

          $this->log("Asiakas p‰ivitetty ({$asiakas['magento_tunnus']})");
          $this->debug($asiakas_data);

          // L‰hetet‰‰n aktivointiviesti Magentoon jos ominaisuus on p‰‰ll‰ sek‰ yhteyshenkilˆlle
          // on merkattu magentokuittaus
          if ($this->_asiakkaan_aktivointi and $this->aktivoidaankoAsiakas($asiakas['tunnus'], $asiakas['magento_tunnus'])) {
            $result = $this->asiakkaanAktivointi($asiakas['yhtio'], $asiakas['yhenk_tunnus']);

            if ($result) {
              $this->log("Yhteyshenkilˆn: '{$asiakas['yhenk_tunnus']}' Magentoasiakas: {$asiakas['magento_tunnus']} aktivoitu");
              $this->debug($asiakas_data);
            }
          }
        }
        catch (Exception $e) {
          $this->_error_count++;
          $this->log("Virhe! Asiakkaan p‰ivitys ep‰onnistui", $e);
          $this->debug($asiakas_data);
        }
      }

      try {
        // Haetaan ensin asiakkaan laskutus- ja toimitusosoitteet
        $address_array = $this->_proxy->call(
          $this->_session,
          'customer_address.list',
          $asiakas['magento_tunnus']
        );

        // Ja poistetaan ne
        if (count($address_array) > 0) {
          foreach ($address_array as $address) {
            $result = $this->_proxy->call(
              $this->_session, 'customer_address.delete', $address['customer_address_id']
            );
          }
        }

      }
      catch (Exception $e) {
        $this->log("Virhe! Asiakkaan '{$asiakas['tunnus']}' osoitteiden haku ep‰onnistui, Magento tunnus {$asiakas['magento_tunnus']}", $e);
        $this->_error_count++;
      }

      if (isset($laskutus_osoite_data['firstname']) and !empty($laskutus_osoite_data['firstname'])) {
        try {
          // Lis‰t‰‰n laskutusosoite
          $result = $this->_proxy->call(
            $this->_session,
            'customer_address.create',
            array(
              'customerId'  => $asiakas['magento_tunnus'],
              'addressdata' => ($laskutus_osoite_data)
            )
          );
        }
        catch (Exception $e) {
          $this->log("Virhe! Asiakkaan laskutusosoitteen p‰ivitys ep‰onnistui", $e);
          $this->debug($laskutus_osoite_data);
          $this->_error_count++;
        }
      }

      if (isset($toimitus_osoite_data['firstname']) and !empty($toimitus_osoite_data['firstname'])) {
        try {
          // Lis‰t‰‰n toimitusosoite
          $result = $this->_proxy->call(
            $this->_session,
            'customer_address.create',
            array(
              'customerId' => $asiakas['magento_tunnus'],
              'addressdata' => ($toimitus_osoite_data)
            )
          );
        }
        catch (Exception $e) {
          $this->log("Virhe! Asiakkaan toimitusosoitteen p‰ivitys ep‰onnistui", $e);
          $this->debug($toimitus_osoite_data);
          $this->_error_count++;
        }
      }
    }

    $this->log("$count asiakasta p‰ivitetty");

    // Palautetaan p‰vitettyjen asiakkaiden m‰‰r‰
    return $count;
  }

  public function setTaxClassID($tax_class_id) {
    $this->_tax_class_id = $tax_class_id;
  }

  public function setParentID($parent_id) {
    $this->_parent_id = $parent_id;
  }

  public function setHintakentta($hintakentta) {
    $this->_hintakentta = $hintakentta;
  }

  public function setKategoriat($magento_kategoriat) {
    $this->_kategoriat = $magento_kategoriat;
  }

  public function setCategoryaccesscontrol($categoryaccesscontrol) {
    $this->_categoryaccesscontrol = $categoryaccesscontrol;
  }

  public function setConfigurableNimityskentta($configurable_tuote_nimityskentta) {
    $this->_configurable_tuote_nimityskentta = $configurable_tuote_nimityskentta;
  }

  public function setConfigurableLapsituoteNakyvyys($configurable_lapsituote_nakyvyys) {
    $this->_configurable_lapsituote_nakyvyys = $configurable_lapsituote_nakyvyys;
  }

  public function setVerkkokauppatuotteetErikoisparametrit($verkkokauppatuotteet_erikoisparametrit) {
    $this->_verkkokauppatuotteet_erikoisparametrit = $verkkokauppatuotteet_erikoisparametrit;
  }

  public function setAsiakkaatErikoisparametrit($asiakkaat_erikoisparametrit) {
    $this->_asiakkaat_erikoisparametrit = $asiakkaat_erikoisparametrit;
  }

  public function setStickyKategoriat($magento_sticky_kategoriat) {
    $this->_sticky_kategoriat = $magento_sticky_kategoriat;
  }

  public function setSisaanluvunEsto($sisaanluvun_esto) {
    $this->_sisaanluvun_esto = $sisaanluvun_esto;
  }

  public function setUniversalTuoteryhma($universal_tuoteryhma) {
    $this->_universal_tuoteryhma = $universal_tuoteryhma;
  }

  public function setAsiakasAktivointi($asiakas_aktivointi) {
    $tila = $asiakas_aktivointi ? $asiakas_aktivointi : false;
    $this->_asiakkaan_aktivointi = $tila;
  }

  public function setAsiakaskohtaisetTuotehinnat($asiakaskohtaiset_tuotehinnat) {
    $tila = $asiakaskohtaiset_tuotehinnat ? $asiakaskohtaiset_tuotehinnat : false;
    $this->_asiakaskohtaiset_tuotehinnat = $tila;
  }

  public function setPoistaDefaultTuoteparametrit(array $poistettavat) {
    $this->_magento_poistadefaultit = $poistettavat;
  }

  public function setPoistaDefaultAsiakasparametrit(array $poistettavat_asiakasparamit) {
    $this->_magento_poista_asiakasdefaultit = $poistettavat_asiakasparamit;
  }

  public function setUrlKeyAttributes(array $url_key_attributes) {
    $this->_magento_url_key_attributes = $url_key_attributes;
  }

  public function setRemoveProducts($value) {
    $this->_magento_poista_tuotteita = $value;
  }

  public function set_magento_lisaa_tuotekuvat($value) {
    $this->magento_lisaa_tuotekuvat = $value;
  }

  public function set_magento_fetch_order_status($value) {
    $this->magento_fetch_order_status = $value;
  }

  public function set_magento_perusta_disabled($value) {
    $this->magento_perusta_disabled = $value;
  }

  public function set_magento_nimitykseen_parametrien_arvot($value) {
    $this->magento_nimitykseen_parametrien_arvot = $value;
  }

  public function set_magento_simple_tuote_nimityskentta($value) {
    $this->magento_simple_tuote_nimityskentta = $value;
  }

  public function set_ovt_tunnus($value) {
    $this->ovt_tunnus = $value;
  }

  public function set_pupesoft_tilaustyyppi($value) {
    $this->pupesoft_tilaustyyppi = $value;
  }

  public function set_rahtikulu_nimitys($value) {
    $this->rahtikulu_nimitys = $value;
  }

  public function set_rahtikulu_tuoteno($value) {
    $this->rahtikulu_tuoteno = $value;
  }

  public function set_verkkokauppa_asiakasnro($value) {
    $this->verkkokauppa_asiakasnro = $value;
  }

  public function set_edi_polku($value) {
    if (!is_writable($value)) {
      throw new Exception("EDI -hakemistoon ei voida kirjoittaa");
    }

    $this->edi_polku = $value;
  }

  public function set_magento_maksuehto_ohjaus($value) {
    $this->magento_maksuehto_ohjaus = $value;
  }

  public function set_magento_erikoiskasittely($value) {
    $this->magento_erikoiskasittely = $value;
  }

  // Hakee error_countin:n
  public function getErrorCount() {
    return $this->_error_count;
  }

  // Kuittaa asiakkaan aktivoiduksi Magentossa
  // HUOM! Vaatii r‰‰t‰lˆidyn Magenton
  private function asiakkaanAktivointi($yhtio, $yhteyshenkilon_tunnus) {
    $reply = false;

    // Haetaan yhteyshenkilˆn tiedot
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
        array(
          $yhenkrow['email'],
          $this->_asiakkaan_aktivointi
        )
      );

      // Merkataan aktivointikuittaus tehdyksi
      $putsausquery = "UPDATE yhteyshenkilo
                       SET aktivointikuittaus = ''
                       WHERE yhtio = '{$yhtio}'
                       AND tunnus  = '{$yhteyshenkilon_tunnus}'";
      pupe_query($putsausquery);
    }
    catch (Exception $e) {
      $this->_error_count++;
      $this->log("Virhe! Asiakkaan aktivointi ep‰onnistui.", $e);
    }

    return $reply;
  }

  // Hakee ja siirt‰‰ tuotteen asiakaskohtaiset hinnat Magentoon
  // HUOM! Vaatii r‰‰t‰lˆidyn Magenton
  private function lisaaAsiakaskohtaisetTuotehinnat($tuotenumero, $magento_tuotenumero) {
    global $kukarow;

    $reply = false;
    $asiakaskohtainenhintadata = array();

    try {
      // Haetaan Pupesta kaikki Magento-asiakkaat ja n‰iden yhteyshenkilˆt
      $asiakkaat_per_yhteyshenkilo = $this->hae_magentoasiakkaat_ja_yhteyshenkilot($kukarow['yhtio']);

      if (count($asiakkaat_per_yhteyshenkilo) < 1) {
        return false;
      }

      // Ensin poistetaan tuotteen asiakashinnat Magentosta
      $this->poista_tuotteen_asiakaskohtaiset_hinnat($asiakkaat_per_yhteyshenkilo, $magento_tuotenumero);

      // Sitten haetaan asiakaskohtainen hintadata Pupesta
      $asiakaskohtainenhintadata = $this->hae_tuotteen_asiakaskohtaiset_hinnat($asiakkaat_per_yhteyshenkilo, $tuotenumero);

      // Lopuksi siirret‰‰n tuotteen kaikki asiakaskohtaiset hinnat Magentoon
      if (count($asiakaskohtainenhintadata) > 0) {
        $reply = $this->_proxy->call(
          $this->_session,
          'price_per_customer.setPriceForCustomersPerProduct',
          array($magento_tuotenumero, $asiakaskohtainenhintadata)
        );

        $this->log("Tuotteen {$magento_tuotenumero} asiakaskohtaiset hinnat lis‰tty");
        $this->debug($asiakaskohtainenhintadata);
      }
    }
    catch (Exception $e) {
      $this->_error_count++;
      $this->log("Virhe!", $e);
    }

    return $reply;
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
    // Poistetaan kaikkien asiakkaiden hinta t‰lt‰ tuotteelta
    $toiminto = false;
    try {
      $asiakashinnat = array();

      foreach ($asiakkaat_per_yhteyshenkilo as $asiakas) {
        $asiakashinnat[] = array(
          'customerEmail' => $asiakas['asiakas_email'],
          'websiteCode' => $this->_asiakaskohtaiset_tuotehinnat,
          'delete' => 1
        );
      }

      $toiminto = $this->_proxy->call(
        $this->_session,
        'price_per_customer.setPriceForCustomersPerProduct',
        array($magento_tuotenumero, $asiakashinnat)
      );

      $this->log("Tuotteen {$magento_tuotenumero} asiakaskohtaiset hinnat poistettu");
      $this->debug($asiakaskohtainenhintadata);
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
      // Tuotteen hinta t‰lle asiakkaalle
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
        // lˆydetty asiakashinta
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
          'websiteCode'   => $this->_asiakaskohtaiset_tuotehinnat,
          'price'         => $hinta,
          'delete'        => 0
        );
      }
    }

    return $asiakaskohtaiset_hinnat_data;
  }

  // Tarkistaa onko t‰m‰ asiakkaan yhteyshenkilˆ merkattu kuitattavaksi
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

  // Hakee verkkokaupan tuotteet
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

  // Rakentaa url_key:n tuotteelle
  private function getUrlKeyForProduct($tuotedata) {
    $halutut_array = $this->_magento_url_key_attributes;
    $url_key = $this->sanitize_link_rewrite($tuotedata['nimi']);
    $parametrit = array();

    foreach ($tuotedata['tuotteen_parametrit'] as $parametri) {
      $key = $parametri['option_name'];
      $value = $parametri['arvo'];
      $parametrit[$key] = $value;
    }

    foreach ($halutut_array as $key => $value) {
      if (!empty($parametrit[$value])) {
        $safe_part1 = $this->sanitize_link_rewrite($value);
        $safe_part2 = $this->sanitize_link_rewrite($parametrit[$value]);
        $url_key .= "-{$safe_part1}-{$safe_part2}";
      }
    }

    return utf8_encode($url_key);
  }

  // Sanitizes string for magento url_key column
  private function sanitize_link_rewrite($string) {
    return preg_replace('/[^a-zA-Z0-9_]/', '', $string);
  }

  // debug level logging
  private function debug($string, $exception = "", $type = 'product') {
    if ($this->debug_logging === false) {
      return;
    }

    $string = print_r($string, true);

    $this->log($string, $exception, $type);
  }

  // Palauttaa syvimm‰n kategoria id:n annetusta tuotteen koko tuotepolusta
  private function createCategoryTree($ancestors) {
    $cat_id = $this->_parent_id;

    foreach ($ancestors as $nimi) {
      $cat_id = $this->createSubCategory($nimi, $cat_id);
    }

    return $cat_id;
  }

  // Lis‰‰ tuotepuun kategorian annettun category_id:n alle, jos sellaista ei ole jo olemassa
  private function createSubCategory($name, $parent_cat_id) {
    // otetaan koko tuotepuu, valitaan siit‰ eka solu idn perusteella
    // sen lapsista etsit‰‰n nime‰, jos ei lˆydy, luodaan viimeisimm‰n idn alle
    // lopuksi palautetaan id
    $name = utf8_encode($name);
    $categoryaccesscontrol = $this->_categoryaccesscontrol;
    $magento_tree = $this->getCategories();
    $results = $this->getParentArray($magento_tree, "$parent_cat_id");

    // Etsit‰‰n kategoriaa
    foreach ($results[0]['children'] as $k => $v) {
      if (strcasecmp($name, $v['name']) == 0) {
        return $v['category_id'];
      }
    }

    // Lis‰t‰‰n kategoria, jos ei lˆytynyt
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

    $this->log("Lis‰ttiin tuotepuun kategoria:$name tunnuksella: $category_id");

    unset($this->_category_tree);

    return $category_id;
  }

  // Etsii arraysta key->value paria, ja jos lˆytyy niin palauttaa sen
  private function getParentArray($tree, $parent_cat_id) {
    //etsit‰‰n keyt‰ "category_id" valuella isatunnus ja return sen lapset
    return search_array_key_for_value_recursive($tree, 'category_id', $parent_cat_id);
  }

  // Hakee oletus attribuuttisetin Magentosta
  private function getAttributeSet() {
    if (empty($this->_attributeSet)) {
      $attributeSets = $this->_proxy->call($this->_session, 'product_attribute_set.list');
      $this->_attributeSet = current($attributeSets);
    }

    return $this->_attributeSet;
  }

  // Hakee kaikki attribuutit magentosta
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

  // Hakee kaikki kategoriat
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

  // Etsii kategoriaa nimelt‰ Magenton kategoria puusta.
  private function findCategory($name, $root) {
    $category_id = false;

    foreach ($root as $i => $category) {

      // Jos lˆytyy t‰st‰ tasosta nii palautetaan id
      if (strcasecmp($name, $category['name']) == 0) {

        // Jos kyseisen kategorian alla on saman niminen kategoria,
        // palautetaan sen id nykyisen sijasta (osasto ja try voivat olla saman niminis‰).
        if (!empty($category['children']) and strcasecmp($category['children'][0]['name'], $name) == 0) {
          return $category['children'][0]['category_id'];
        }

        return $category_id = $category['category_id'];
      }

      // Muuten jatketaan ettimist‰
      $r = $this->findCategory($name, $category['children']);

      if ($r != null) {
        return $r;
      }
    }

    // Mit‰‰n ei lˆytyny
    return $category_id;
  }

  // Etsii asiakasryhm‰‰ nimen perusteella Magentosta, palauttaa id:n
  private function findCustomerGroup($name) {
    $customer_groups = $this->_proxy->call(
      $this->_session,
      'customer_group.list'
    );

    $id = 0;

    foreach ($customer_groups as $asryhma) {
      if (strcasecmp($asryhma['customer_group_code'], $name) == 0) {
        $id = $asryhma['customer_group_id'];
        break;
      }
    }

    return $id;
  }

  // Palauttaa attribuutin option id:n annetulle atribuutille ja arvolle
  private function get_option_id($name, $value) {
    $name = utf8_encode($name);
    $value = utf8_encode($value);
    $attribute_list = $this->getAttributeList();
    $attribute_id = '';

    // Etsit‰‰n halutun attribuutin id
    foreach ($attribute_list as $attribute) {
      if (strcasecmp($attribute['code'], $name) == 0) {
        $attribute_id = $attribute['attribute_id'];
        $attribute_type = $attribute['type'];
        break;
      }
    }

    // Jos attribuuttia ei lˆytynyt niin turha etti‰ option valuea
    if (empty($attribute_id)) return 0;

    // Jos dynaaminen parametri on matkalla teksti- tai hintakentt‰‰n niin idt‰ ei tarvita, palautetaan vaan arvo
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

    // Etit‰‰n optionsin value
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

      // Etit‰‰n optionsin value uudestaan..
      foreach ($options as $option) {
        if (strcasecmp($option['label'], $value) == 0) {
          return $option['value'];
        }
      }
    }

    // Mit‰‰n ei lˆytyny
    return 0;
  }

  // Hakee $status -tilassa olevat tilaukset Magentosta ja merkkaa ne noudetuksi.
  // Palauttaa arrayn tilauksista
  private function hae_tilaukset($status = 'processing') {
    $this->log("Haetaan tilauksia", '', $type = 'order');

    $orders = array();

    // Toimii ordersilla
    $filter = array(array('status' => array('eq' => $status)));

    // Uusia voi hakea? state => 'new'
    //$filter = array(array('state' => array('eq' => 'new')));

    // N‰in voi hakea yhden tilauksen tiedot
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
        // Jos tilaus on ollut kerran jo processing_pupesoft, ei haeta sit‰ en‰‰
        $_status = $historia['status'];

        if ($_status == "processing_pupesoft" and $this->_sisaanluvun_esto == "YES") {
          $this->log("Tilausta on k‰sitelty {$_status} tilassa, ohitetaan sis‰‰nluku", '', $type = 'order');
          // Skipataan t‰m‰ $order
          continue 2;
        }
      }

      $orders[] = $temp_order;

      // P‰ivitet‰‰n tilauksen tila ett‰ se on noudettu pupesoftiin
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

    // Palautetaan lˆydetyt tilaukset
    return $orders;
  }

  // Tapahtumaloki
  private function log($message, $exception = '', $type = 'product') {
    if ($exception != '') {
      $message .= " (" . $exception->getMessage() . ") faultcode: " . $exception->faultcode;
    }

    $log_name = $type == 'product' ? 'magento_export' : 'magento_orders';

    pupesoft_log($log_name, $message);
  }

  // Poistaa tuotteen kaikki kuvat ja lis‰‰ ne takaisin
  private function lisaa_tuotekuvat($product_id, $tuotekuvat) {
    if (count($tuotekuvat) == 0 or empty($product_id)) {
      return;
    }

    $types = array('image', 'small_image', 'thumbnail');

    // Pit‰‰ ensin poistaa kaikki tuotteen kuvat Magentosta
    $magento_pictures = $this->listaa_tuotekuvat($product_id);

    // Poistetaan kuvat
    foreach ($magento_pictures as $file) {
      $this->poista_tuotekuva($product_id, $file);
    }

    // Loopataan tuotteen kaikki kuvat
    foreach ($tuotekuvat as $kuva) {

      // Lis‰t‰‰n tuotekuva kerrallaan
      try {
        $data = array(
          $product_id,
          array(
            'file'     => $kuva,
            'label'    => '',
            'position' => 0,
            'types'    => $types,
            'exclude'  => 0
          ),
        );

        $return = $this->_proxy->call(
          $this->_session,
          'catalog_product_attribute_media.create',
          $data
        );

        $this->log("Lis‰tty kuva '{$kuva['name']}'");
        $this->debug($return);
      }
      catch (Exception $e) {
        // Nollataan base-encoodattu kuva, ett‰ logi ei tuu isoks
        $data[1]["file"]["content"] = '...content poistettu logista...';

        $this->log("Virhe! Kuvan lis‰ys ep‰onnistui", $e);
        $this->debug($data);
        $this->_error_count++;
      }
    }
  }

  // Hakee tuotteen tuotekuvat Magentosta
  private function listaa_tuotekuvat($product_id) {
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
      $this->log("Virhe! Kuvalistauksen ep‰onnistui", $e);
      $this->_error_count++;
    }

    foreach ($pictures as $picture) {
      $return[] = $picture['file'];
    }

    return $return;
  }

  // Poistaa tuotteen tuotekuvan Magentosta
  private function poista_tuotekuva($product_id, $filename) {
    // Jos ei haluta k‰sitell‰ tuotekuvia, ei poisteta niit‰ magentosta
    if ($this->magento_lisaa_tuotekuvat === false) {
      return;
    }

    $return = false;

    // Poistetaan tuotteen kuva
    try {
      $return = $this->_proxy->call(
        $this->_session,
        'catalog_product_attribute_media.remove',
        array(
          'product' => $product_id,
          'file'    => $filename
        )
      );

      $this->log("Poistetaan '{$filename}'");
    }
    catch (Exception $e) {
      $this->log("Virhe! Kuvan poisto ep‰onnistui '{$filename}'", $e);
      $this->_error_count++;

      return false;
    }

    return $return;
  }

  // Hakee tuotteen tuotekuvat Pupesoftista
  private function hae_tuotekuvat($tunnus) {
    global $kukarow;

    // Jos ei haluta k‰sitell‰ tuotekuvia, palautetaan tyhj‰ array
    if ($this->magento_lisaa_tuotekuvat === false) {
      return array();
    }

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
          'content' => base64_encode($liite['data']),
          'mime'    => $liite['filetype'],
          'name'    => $liite['filename']
        );

        $tuotekuvat[] = $file;
      }
    }
    catch (Exception $e) {
      $this->_error_count++;
      $this->log("Virhe! Tietokantayhteys on poikki. Yritet‰‰n uudelleen.", $e);
    }

    // Palautetaan tuotekuvat
    return $tuotekuvat;
  }

  // Hakee tuotteen kieliversiot(tuotenimitys, tuotekuvaus) Pupesoftista
  private function hae_kieliversiot($tuotenumero) {
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

        // J‰sennell‰‰n tuotteen avainsanat kieliversioittain
        $kieliversiot_data[$kieli][$laji] = $selite;
      }
    }
    catch (Exception $e) {
      $this->_error_count++;
      $this->log("Virhe! Tietokantayhteys on poikki. Yritet‰‰n uudelleen.", $e);
    }

    // Palautetaan kieliversiot
    return $kieliversiot_data;
  }
}
