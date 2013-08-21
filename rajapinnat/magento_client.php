<?php
/**
 * SOAP-clientin wrapperi Magento-verkkokaupan p‰ivitykseen
 *
 * K‰ytet‰‰n suoraan rajapinnat/tuote_export.php tiedostoa, jolla haetaan
 * tarvittavat tiedot pupesoftista.
 *
 * Lis‰‰ tai p‰ivitt‰‰ kategoriat, tuotteet ja saldot.
 * Hakee maksettuja tilauksia pupesoftiin.
 */

class MagentoClient {

	/**
	 * Kutsujen m‰‰r‰ multicall kutsulla
	 */
	const MULTICALL_BATCH_SIZE = 100;

	/**
	 * Logging p‰‰ll‰/pois
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
	 * T‰m‰n yhteyden aikana sattuneiden virheiden m‰‰r‰
	 */
	private $_error_count = 0;

	/**
	 * Constructor
	 * @param string $url 	SOAP Web service URL
	 * @param string $user 	API User
	 * @param string $pass 	API Key
	 */
	function __construct($url, $user, $pass) {

		try {
			$this->_proxy = new SoapClient($url);
			$this->_session = $this->_proxy->login($user, $pass);
			$this->log("Magento p‰ivitysskripti aloitettu");
		}
		catch (Exception $e) {
			$this->_error_count++;
			$this->log("Virhe! Magento-class init failed", $e);
			return null;
		}
	}

	/**
	 * Destructor
	 */
	function __destruct() {
		$this->log("P‰ivitysskripti p‰‰ttyi\n");
	}

	/**
	 * Lis‰‰ kaikki tai puuttuvat kategoriat Magento-verkkokauppaan.
	 *
	 * @param  array  $dnsryhma Pupesoftin tuote_exportin palauttama array
	 * @return int           	Lis‰ttyjen kategorioiden m‰‰r‰
	 */
	public function lisaa_kategoriat(array $dnsryhma) {

		$this->log("Lis‰t‰‰n kategoriat");

		$parent_id = 3; // Magento kategorian tunnus, jonka alle kaikki tuoteryhm‰t lis‰t‰‰n (pit‰‰ katsoa magentosta)

		$count = 0;

		// Loopataan osastot ja tuoteryhmat
		foreach ($dnsryhma as $kategoria) {

			try {
				// Haetaan kategoriat joka kerta koska lis‰tt‰ess‰ puu muuttuu
				$category_tree = $this->getCategories();

				// Kasotaan lˆytyykˆ tuoteryhm‰
				if (!$this->findCategory($kategoria['try_fi'], $category_tree['children'])) {

					// Lis‰t‰‰n kategoria, jos ei lˆytynyt
					$category_data = array(
						'name'              => $kategoria['try_fi'],
						'is_active'         => 1,
						'position'          => 1,
						'default_sort_by'   => 'position',
						'available_sort_by' => 'position',
						'include_in_menu'   => 1
					);

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

	/**
	 * Lis‰‰ p‰ivitettyj‰ Simple tuotteita Magento-verkkokauppaan.
	 *
	 * @param  array  $dnstuote 	Pupesoftin tuote_exportin palauttama tuote array
	 * @return int  	         	Lis‰ttyjen tuotteiden m‰‰r‰
	 */
	public function lisaa_simple_tuotteet(array $dnstuote) {

		$this->log("Lis‰t‰‰n tuotteita (simple)");

		// Tuote countteri
		$count = 0;

		try {
			// Tarvitaan kategoriat
			$category_tree = $this->getCategories();

			// Populoidaan attributeSet
			$this->_attributeSet = $this->getAttributeSet();

			// Haetaan storessa olevat tuotenumerot
			$skus_in_store = $this->getProductList(true);
<<<<<<< HEAD
		}
		catch (Exception $e) {
			$this->log("Virhe tuotteiden lis‰yksess‰", $e);
			exit();
		}
=======
		}
		catch (Exception $e) {
			$this->_error_count++;
			$this->log("Virhe! Tuotteiden lis‰yksess‰ (simple)", $e);
			return;
		}
>>>>>>> master

		// Lis‰t‰‰n tuotteet eriss‰
		foreach ($dnstuote as $tuote) {

			// Lyhytkuvaus ei saa olla magentossa tyhj‰.
			// K‰ytet‰‰n kuvaus kent‰n tietoja jos lyhytkuvaus on tyhj‰.
			if ($tuote['lyhytkuvaus'] == '') {
				$tuote['lyhytkuvaus'] = '&nbsp;';
			}

			$tuote['kuluprosentti'] = ($tuote['kuluprosentti'] == 0) ? '' : $tuote['kuluprosentti'];

			// Etsit‰‰n kategoria_id tuoteryhm‰ll‰
			$category_id = $this->findCategory($tuote['try_nimi'], $category_tree['children']);

			$tuote_data = array(
					'categories'            => array($category_id),
					'websites'              => explode(" ", $tuote['nakyvyys']),
					'name'                  => utf8_encode($tuote['nimi']),
					'description'           => utf8_encode($tuote['kuvaus']),
					'short_description'     => utf8_encode($tuote['lyhytkuvaus']),
					'weight'                => $tuote['tuotemassa'],
					'status'                => self::ENABLED,
					'visibility'            => self::NOT_VISIBLE_INDIVIDUALLY,
					'price'                 => $tuote['myymalahinta'],
					'special_price'         => $tuote['kuluprosentti'],
					'tax_class_id'          => $this->getTaxClassID(),
					'meta_title'            => '',
					'meta_keyword'          => '',
					'meta_description'      => '',
					'campaign_code'         => utf8_encode($tuote['campaign_code']),
					'onsale'                => utf8_encode($tuote['onsale']),
					'target'                => utf8_encode($tuote['target']),
				);

			// Lis‰t‰‰n tai p‰ivitet‰‰n tuote
			try {

				// Jos tuotetta ei ole olemassa niin lis‰t‰‰n se
				if ( ! in_array($tuote['tuoteno'], $skus_in_store)) {
					// Jos tuotteen lis‰ys ei onnistu ei tuotekuviakaan lis‰t‰.
					$product_id = $this->_proxy->call($this->_session, 'catalog_product.create',
						array(
							'simple',
							$this->_attributeSet['set_id'],
							$tuote['tuoteno'], # sku
<<<<<<< HEAD
							array(
									'categories'            => array($category_id),
									'websites'              => array($tuote['nakyvyys']),
									'name'                  => $tuote['nimi'],
									'description'           => $tuote['kuvaus'],
									'short_description'     => utf8_encode($tuote['lyhytkuvaus']),
									'weight'                => $tuote['tuotemassa'],
									'status'                => self::ENABLED,
									'visibility'            => self::NOT_VISIBLE_INDIVIDUALLY,
									'price'                 => $tuote['myymalahinta'],
									'special_price'         => $tuote['kuluprosentti'],
									'tax_class_id'          => $this->getTaxClassID(),
									'meta_title'            => '',
									'meta_keyword'          => '',
									'meta_description'      => '',
									'campaign_code'         => utf8_encode($tuote['campaign_code']),
									'featured'              => utf8_encode($tuote['featured']),
									'target'                => utf8_encode($tuote['target']),
								)
							)
						);
					$this->log("Tuote {$tuote['tuoteno']} lis‰tty." . print_r($tuote));
=======
							$tuote_data,
							)
						);
					$this->log("Tuote {$tuote['tuoteno']} lis‰tty (simple) " . print_r($tuote_data, true));
>>>>>>> master
				}
				// Tuote on jo olemassa, p‰ivitet‰‰n
				else {
					$product_id = $this->_proxy->call($this->_session, 'catalog_product.update',
						array(
							$tuote['tuoteno'], # sku
<<<<<<< HEAD
							array(
									'categories'            => array($category_id),
									'websites'              => array($tuote['nakyvyys']),
									'name'                  => $tuote['nimi'],
									'description'           => $tuote['kuvaus'],
									'short_description'     => utf8_encode($tuote['lyhytkuvaus']),
									'weight'                => $tuote['tuotemassa'],
									'status'                => self::ENABLED,
									'visibility'            => self::NOT_VISIBLE_INDIVIDUALLY,
									'price'                 => $tuote['myymalahinta'],
									'special_price'         => $tuote['kuluprosentti'],
									'tax_class_id'          => $this->getTaxClassID(),
									'meta_title'            => '',
									'meta_keyword'          => '',
									'meta_description'      => '',
									'campaign_code'         => utf8_encode($tuote['campaign_code']),
									'featured'              => utf8_encode($tuote['featured']),
									'target'                => utf8_encode($tuote['target']),
								)
							)
						);
					$this->log("Tuote {$tuote['tuoteno']} p‰ivitetty." . print_r($tuote));
=======
							$tuote_data,
							)
						);
					$this->log("Tuote {$tuote['tuoteno']} p‰ivitetty (simple) " . print_r($tuote_data, true));
>>>>>>> master
				}

				// Lis‰t‰‰n tuotekuvat
				if ($tuotekuvat = $this->hae_tuotekuvat($tuote['tunnus'])) {
					// Multicallilla kaikki kuvat yhdell‰ kertaa.
					$this->lisaa_tuotekuvat($product_id, $tuotekuvat);
				}

				// Lis‰t‰‰n tuote countteria
				$count++;

			}
			catch (Exception $e) {
<<<<<<< HEAD
				$this->log("Tuotteen {$tuote['tuoteno']} lis‰ys/p‰ivitys ep‰onnistui." . print_r($tuote), $e);
=======
				$this->_error_count++;
				$this->log("Virhe! Tuotteen {$tuote['tuoteno']} lis‰ys/p‰ivitys ep‰onnistui (simple) " . print_r($tuote_data, true), $e);
>>>>>>> master
			}
		}

		$this->log("$count tuotetta p‰ivitetty (simple)");

		// Palautetaan p‰vitettyjen tuotteiden m‰‰r‰
		return $count;
	}

	/**
	 * Lis‰‰ p‰ivitettyj‰ Configurable tuotteita Magento-verkkokauppaan.
	 *
	 * @param  array  $dnslajitelma 	Pupesoftin tuote_exportin palauttama tuote array
	 * @return int  	         		Lis‰ttyjen tuotteiden m‰‰r‰
	 */
	public function lisaa_configurable_tuotteet(array $dnslajitelma) {

		$this->log("Lis‰t‰‰n tuotteet (configurable)");

		$count = 0;

		// Populoidaan attributeSet
		$this->_attributeSet = $this->getAttributeSet();

		// Haetaan storessa olevat tuotenumerot
		$skus_in_store = $this->getProductList(true);

		// Tarvitaan kategoriat
		$category_tree = $this->getCategories();

		// Lis‰t‰‰n tuotteet
		foreach ($dnslajitelma as $nimitys => $tuotteet) {

			// Jos lyhytkuvaus on tyhj‰, k‰ytet‰‰n kuvausta?
			if ($tuotteet[0]['lyhytkuvaus'] == '') {
				$tuotteet[0]['lyhytkuvaus'] = '&nbsp';
			}

			// Erikoishinta
			$tuotteet[0]['kuluprosentti'] = ($tuotteet[0]['kuluprosentti'] == 0) ? '' : $tuotteet[0]['kuluprosentti'];

			// Etsit‰‰n kategoria mihin tuote lis‰t‰‰n
			$category_id = $this->findCategory($tuotteet[0]['try_nimi'], $category_tree['children']);

			// Configurable tuotteen tiedot
			$configurable = array(
				'categories'			=> array($category_id),
				'websites'				=> explode(" ", $tuotteet[0]['nakyvyys']),
				'name'					=> utf8_encode($tuotteet[0]['nimitys']),
				'description'           => utf8_encode($tuotteet[0]['kuvaus']),
				'short_description'     => utf8_encode($tuotteet[0]['lyhytkuvaus']),
				'campaign_code'         => utf8_encode($tuotteet[0]['campaign_code']),
				'onsale'                => utf8_encode($tuotteet[0]['onsale']),
				'target'                => utf8_encode($tuotteet[0]['target']),
				'weight'                => $tuotteet[0]['tuotemassa'],
				'status'                => self::ENABLED,
				'visibility'            => self::CATALOG_SEARCH, # Configurablet nakyy kaikkialla
				'price'                 => $tuotteet[0]['myymalahinta'],
				'special_price'			=> $tuotteet[0]['kuluprosentti'],
				'tax_class_id'          => $this->getTaxClassID(), # 24%
				'meta_title'            => '',
				'meta_keyword'          => '',
				'meta_description'      => '',
			);

			try {

				/**
				 * Loopataan tuotteen (configurable) lapsituotteet (simple) l‰pi
				 * ja p‰ivitet‰‰n niiden attribuutit kuten koko ja v‰ri.
				 */
				foreach ($tuotteet as $tuote) {
					$koko = '';
					$vari = '';

					// Simple tuotteiden parametrit kuten koko ja v‰ri
					foreach($tuote['parametrit'] as $parametri) {
						if ($parametri['nimi'] == "Koko") {
							$koko = $this->get_option_id('koko', $parametri['arvo']);
						}
						if ($parametri['nimi'] == "V‰ri") {
							$vari = $this->get_option_id('vari', $parametri['arvo']);
						}
					}

					// P‰ivitet‰‰n Simple tuote
					$result = $this->_proxy->call($this->_session, 'catalog_product.update', array($tuote['tuoteno'],
						array(
								'price'				=> $tuote['myymalahinta'],
								'short_description' => utf8_encode($tuote['lyhytkuvaus']),
								'visibility'		=> self::NOT_VISIBLE_INDIVIDUALLY,
								'additional_attributes' => array(
									'multi_data' => array(
										'koko' => $koko,
										'vari' => $vari
									)
								)
							)
						)
					);
				}

				// Jos configurable tuotetta ei lˆydy, niin lis‰t‰‰n uusi tuote.
				if (!in_array($nimitys, $skus_in_store)) {
					$product_id = $this->_proxy->call($this->_session, 'catalog_product.create',
						array(
							'configurable',
							$this->_attributeSet['set_id'],
							$nimitys, # sku
							$configurable
							)
						);
					$this->log("Tuote {$nimitys} lis‰tty (configurable) " . print_r($configurable, true));
				}
				// P‰ivitet‰‰n olemassa olevaa configurablea
				else {
					$product_id = $this->_proxy->call($this->_session, 'catalog_product.update',
						array(
							$nimitys,
							$configurable
							)
						);
					$this->log("Tuote {$nimitys} p‰ivitetty (configurable) " . print_r($configurable, true));
				}

				// Tarkistetaan onko lis‰tyll‰ tuotteella tuotekuvia ja lis‰t‰‰n ne
				// Multicallilla kaikki kuvat yhdell‰ kertaa.
				if ($tuotekuvat = $this->hae_tuotekuvat($tuotteet[0]['tunnus'])) {
					$this->lisaa_tuotekuvat($product_id, $tuotekuvat);
				}

				// Lis‰t‰‰n countteria
				$count++;

			}
			catch (Exception $e) {
				$this->_error_count++;
				$this->log("Virhe! Configurable tuotteen {$nimitys} lis‰ys/p‰ivitys ep‰onnistui (configurable) " . print_r($configurable, true), $e);
			}
		}

		$this->log("$count tuotetta p‰ivitetty (configurable)");

		// Palautetaan lis‰ttyjen configurable tuotteiden m‰‰r‰
		return $count;
	}

	/**
	 * Hakee maksetut tilaukset Magentosta ja luo editilaus tiedoston.
	 * Merkkaa haetut tilaukset noudetuksi.
	 *
	 * @param string $status 	Haettavien tilausten status, esim 'prorcessing'
	 * @return array 			Lˆydetyt tilaukset
	 */
	public function hae_tilaukset($status = 'processing') {

		$this->log("Haetaan tilauksia");

		$orders = array();

		// Toimii ordersilla
		$filter = array(array('status' => array('eq' => $status)));

		// Uusia voi hakea? state => 'new'
		#$filter = array(array('state' => array('eq' => 'new')));

		// Haetaan tilaukset (orders.status = 'processing')
		$fetched_orders = $this->_proxy->call($this->_session, 'sales_order.list', $filter);

		// HUOM: invoicella on state ja orderilla on status
		// Invoicen statet 'pending' => 1, 'paid' => 2, 'canceled' => 3
		// Invoicella on state
		# $filter = array(array('state' => array('eq' => 'paid')));
		// Haetaan laskut (invoices.state = 'paid')

		foreach ($fetched_orders as $order) {

			$this->log("Haetaan tilaus {$order['increment_id']}");

			// Haetaan tilauksen tiedot (orders)
			$orders[] = $this->_proxy->call($this->_session, 'sales_order.info', $order['increment_id']);

			// P‰ivitet‰‰n tilauksen tila ett‰ se on noudettu pupesoftiin
			$this->_proxy->call($this->_session, 'sales_order.addComment', array('orderIncrementId' => $order['increment_id'], 'status' => 'processing_pupesoft', 'Tilaus noudettu Pupesoftiin'));
		}

		$this->log(count($orders) . " tilausta haettu");

		// Palautetaan lˆydetyt tilaukset
		return $orders;
	}

	/**
	 * P‰ivitt‰‰ tuotteiden saldot
	 *
	 * @param array $dnstock 	Pupesoftin tuote_exportin array
	 * @param int 	$count
	 */
	public function paivita_saldot(array $dnstock) {

		$this->log("P‰ivitet‰‰n saldot");
		$count = 0;

		// Loopataan p‰ivitett‰v‰t tuotteet l‰pi (aina simplej‰)
		foreach ($dnstock as $tuote) {

			// $tuote muuttuja sis‰lt‰‰ tuotenumeron ja myyt‰viss‰ m‰‰r‰n
			$product_sku = $tuote['tuoteno'];
			$qty         = $tuote['myytavissa'];

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
				$this->log("P‰ivitetty tuotteen {$product_sku} saldo {$qty}.");
			}
			catch (Exception $e) {
				$this->_error_count++;
				$this->log("Virhe! Saldop‰ivitys ep‰onnistui. Tuote {$product_sku} saldo {$qty}.". $e);
			}

			$count++;
		}

		return $count;
	}

	/**
	 * P‰ivitt‰‰ tuotteiden hinnat
	 *
	 * @param array 	$dnshinnasto	Tuotteiden p‰ivitety hinnat
	 * @param int 		$count 			P‰ivitettyjen tuotteiden m‰‰r‰n
	 */
	public function paivita_hinnat(array $dnshinnasto) {

		$count = 0;
		$batch_count = 0;

		// P‰ivitet‰‰n tuotteen hinnastot
		foreach($dnshinnasto as $tuote) {

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
					$this->log("Virhe! Hintojen p‰ivitys ep‰onnistui {$tuote['tuoteno']}", $e);
				}

				$batch_count = 0;
				$calls = array();
			}
		}

		// P‰ivitettyjen tuotteiden m‰‰r‰
		return $count;
	}

	/**
	 * Poistaa magentosta tuotteita
	 *
	 * @param array $poistetut_tuotteet Poistettavat tuotteet
	 * @return   Poistettujen tuotteiden m‰‰r‰
	 */
	public function poista_poistetut(array $poistetut_tuotteet) {

		$count = 0;

		foreach($poistetut_tuotteet as $tuote) {
			#$result = $client->call($session, 'catalog_product.delete', $tuote['tuoteno']);
			$count++;
		}

		echo date("H:i:s").": Poistettiin Magentosta $count tuotetta.\n\n";
		return $count;
	}

	/// Private functions ///

	/**
	 * Hakee oletus attribuuttisetin
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
	 * @return   	Kaikki attribuutit
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
				#$this->_category_tree = $this->_category_tree['children'][0]; # Skipataan rootti categoria
			}

			return $this->_category_tree;
		}
		catch (Exception $e) {
			$this->_error_count++;
			$this->log("Virhe! Kategorioiden hakemisessa", $e);
		}
	}

	/**
	 * Etsii kategoriaa nimelt‰ Magenton kategoria puusta.
	 */
	private function findCategory($name, $root) {

		$category_id = false;

		foreach($root as $i => $category) {

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

	/**
	 * Palauttaa attribuutin option id:n
	 *
	 * Esimerkiksi koko, S palauttaa jonkun numeron jolla tuotteen p‰ivityksess‰ saadaan attribuutti
	 * oikein.
	 *
	 * @param  string $name  	Attribuutin nimi, koko tai vari
	 * @param  string $value 	Atrribuutin arvo, S, M, XL...
	 * @return int        	 	Options_id
	 */
	private function get_option_id($name, $value) {

		$attribute_list = $this->getAttributeList();
		$attribute_id = '';

		// Etsit‰‰n halutun attribuutin id
		foreach($attribute_list as $attribute) {
			if (strcasecmp($attribute['code'], $name) == 0) {
				$attribute_id = $attribute['attribute_id'];
				break;
			}
		}

		// Jos attribuuttia ei lˆytynyt niin turha etti‰ option valuea
		if (empty($attribute_id)) return 0;

		// Haetaan kaikki attribuutin optionssit
		$options = $this->_proxy->call(
		    $this->_session,
		    "product_attribute.options",
		    array(
		         $attribute_id
		    )
		);

		// Etit‰‰n optionsin value
		foreach($options as $option) {
			if (strcasecmp($option['label'], $value) == 0) {
				return $option['value'];
			}
		}

		// Mit‰‰n ei lˆytyny
		return 0;
	}

	/**
	 * Lis‰‰ tuotteen tuotekuvat
	 * @param  string 	$product_id Tuotteen tunnus
	 * @param  array 	$tuotekuvat Tuotteen kuvatiedostot
	 * @return array    			Tiedostonimet
	 */
	private function lisaa_tuotekuvat($product_id, $tuotekuvat) {

		// Multicall array
		$calls = array();
		$filenames = '';

		foreach($tuotekuvat as $kuva) {
			$types = array('image', 'small', 'thumbnail');

			$calls[] = array(
				'catalog_product_attribute_media.create',
				array($product_id,
					array(	'file' 		=> $kuva,
							'label'		=> '',
							'position' 	=> 0,
							'types' 	=> $types,
							'exclude' 	=> 0
						)
					)
			);
		}

		// Lis‰t‰‰n tuotekuvat
		try {
			$filenames = $this->_proxy->multicall($this->_session, $calls);
		}
		catch (Exception $e) {
			$this->_error_count++;
			$this->log("Virhe! Kuvan lis‰ys ep‰onnistui", $e);
		}

		return $filenames;
	}

	/**
	 * Hakee tuotteen tuotekuvat
	 *
	 * @param  int 		$tunnus 		Tuoteen tunnus (tuote.tunnus)
	 * @return array 	$tuotekuvat 	Palauttaa arrayn joka kelpaa magenton soap clientille suoraan
	 */
	private function hae_tuotekuvat($tunnus) {
		global $kukarow, $dbhost, $dbuser, $dbpass, $dbkanta;

			try {
				// Tietokantayhteys
				$db = new PDO("mysql:host=$dbhost;dbname=$dbkanta", $dbuser, $dbpass);

				// Tuotekuva query
				$stmt = $db->prepare("
					SELECT liitetiedostot.kayttotarkoitus, liitetiedostot.filename, liitetiedostot.data, liitetiedostot.filetype, liitetiedostot.jarjestys, t1.selite
					FROM liitetiedostot
					JOIN tuote ON (tuote.tunnus=liitetiedostot.liitostunnus)
					LEFT JOIN tuotteen_avainsanat t1 ON (tuote.yhtio=t1.yhtio AND tuote.tuoteno=t1.tuoteno AND t1.laji='parametri_vari' AND t1.kieli='fi')
					WHERE liitetiedostot.yhtio=?
					AND liitetiedostot.liitostunnus=?
					AND liitetiedostot.liitos='tuote'
					AND liitetiedostot.kayttotarkoitus='TK'
					ORDER BY liitetiedostot.jarjestys DESC, liitetiedostot.tunnus DESC;
				");
				$stmt->execute(array($kukarow['yhtio'], $tunnus));

				// Populoidaan tuotekuvat array
				$tuotekuvat = array();
				while($liite = $stmt->fetch(PDO::FETCH_ASSOC)) {
					$file = array(
						'content' 	=> base64_encode($liite['data']),
						'mime'		=> $liite['filetype'],
						'name'		=> $liite['filename']
					);

					$tuotekuvat[] = $file;
				}

				$db = null;

				// Palautetaan tuotekuvat
				return $tuotekuvat;
			}
			catch (Exception $e) {
				$this->_error_count++;
				$this->log("Virhe! PDO yhteys on poikki. Yritet‰‰n uudelleen.", $e);
				$db = null;
				return false;
			}
	}

	/**
	 * Asettaa tax_class_id:n
	 * Oletus 0
	 *
	 * @param int $tax_clas_id Veroluokan tunnus
	 */
	public function setTaxClassID($tax_class_id) {
		$this->_tax_class_id = $tax_class_id;
	}

	/**
	 * Hakee tax_class_id:n
	 * @return int 	Veroluokan tunnus
	 */
	private function getTaxClassID() {
		return $this->_tax_class_id;
	}

	/**
	 * Hakee error_countin:n
	 * @return int	virheiden m‰‰r‰
	 */
	public function getErrorCount() {
		return $this->_error_count;
	}

	/**
	 * Hakee verkkokaupan tuotteet
	 *
	 * @param boolean $only_skus 	Palauttaa vain tuotenumerot (true)
	 * @return array
	 */
	private function getProductList($only_skus = false) {

		try {
			$result = $this->_proxy->call($this->_session, 'catalog_product.list');

			if ($only_skus == true) {
				$skus = array();

				foreach($result as $product) {
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
	 * @param string $message 		Virheviesti
	 * @param exception $exception 	Exception
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
