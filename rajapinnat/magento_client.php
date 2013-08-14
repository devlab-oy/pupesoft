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
	 * Constructor
	 * @param string $url 	SOAP Web service URL
	 * @param string $user 	API User
	 * @param string $pass 	API Key
	 */
	function __construct($url, $user, $pass)
	{
		try {
			$this->_proxy = new SoapClient($url);
			$this->_session = $this->_proxy->login($user, $pass);
			$this->log("Magento päivitysskripti aloitettu " . date('d-m-y H:i:s'));
		} catch (Exception $e) {
			echo $e->getMessage();
			exit;
		}
	}

	/**
	 * Destructor
	 */
	function __destruct() {
		$this->log("Päivitysskripti päättyi " . date('d-m-y H:i:s') . "\n");
	}

	/**
	 * Lisää kaikki tai puuttuvat kategoriat Magento-verkkokauppaan.
	 *
	 * @param  array  $dnsryhma Pupesoftin tuote_exportin palauttama array
	 * @return int           	Lisättyjen kategorioiden määrä
	 */
	public function lisaa_kategoriat(array $dnsryhma) {

		$this->log("Lisätään kategoriat");

		$parent_id = 3; // Magento kategorian tunnus, jonka alle kaikki tuoteryhmät lisätään (pitää katsoa magentosta)

		try {
			$count = 0;

			// Loopataan osastot ja tuoteryhmat
			foreach($dnsryhma as $kategoria) {

				// Haetaan kategoriat joka kerta koska lisättäessä puu muuttuu
				$category_tree = $this->getCategories();

				// Kasotaan löytyykö tuoteryhmä
				if (!$this->findCategory($kategoria['try_fi'], $category_tree['children'])) {

					// Lisätään kategoria, jos ei löytynyt
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
				}
			}

			$this->_category_tree = $this->getCategories();
			$this->log("$count kategoriaa lisätty");

			return $count;
		}
		catch (Exception $e) {
			echo $e->getMessage();
		}
	}

	/**
	 * Lisää päivitettyjä Simple tuotteita Magento-verkkokauppaan.
	 *
	 * @param  array  $dnstuote 	Pupesoftin tuote_exportin palauttama tuote array
	 * @return int  	         	Lisättyjen tuotteiden määrä
	 */
	public function lisaa_simple_tuotteet(array $dnstuote) {

		$this->log("Lisätään tuotteita (simple)");

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
			$this->log("Virhe tuotteiden lisäyksessä", $e);
			exit();
		}

		// Lisätään tuotteet erissä
		foreach($dnstuote as $tuote) {

			// Lyhytkuvaus ei saa olla magentossa tyhjä.
			// Käytetään kuvaus kentän tietoja jos lyhytkuvaus on tyhjä.
			if ($tuote['lyhytkuvaus'] == '') {
				$tuote['lyhytkuvaus'] = '&nbsp;';
			}

			$tuote['kuluprosentti'] = ($tuote['kuluprosentti'] == 0) ? '' : $tuote['kuluprosentti'];

			// Etsitään kategoria_id tuoteryhmällä
			$category_id = $this->findCategory($tuote['try_nimi'], $category_tree['children']);

			// Lisätään tai päivitetään tuote
			try {
				
				$tuote_data = array(
						'categories'            => array($category_id),
						'websites'              => array($tuote['nakyvyys']),
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
						'featured'              => utf8_encode($tuote['featured']),
						'target'                => utf8_encode($tuote['target']),
					);
				
				// Jos tuotetta ei ole olemassa niin lisätään se
				if ( ! in_array($tuote['tuoteno'], $skus_in_store)) {
					// Jos tuotteen lisäys ei onnistu ei tuotekuviakaan lisätä.
					$product_id = $this->_proxy->call($this->_session, 'catalog_product.create',
						array(
							'simple',
							$this->_attributeSet['set_id'],
							$tuote['tuoteno'], # sku
							$tuote_data,
							)
						);
					$this->log("Tuote {$tuote['tuoteno']} lisätty." . print_r($tuote_data, true));
				}
				// Tuote on jo olemassa, päivitetään
				else {
					$product_id = $this->_proxy->call($this->_session, 'catalog_product.update',
						array(
							$tuote['tuoteno'], # sku
							$tuote_data,
							)
						);
					$this->log("Tuote {$tuote['tuoteno']} päivitetty." . print_r($tuote_data, true));
				}

				// Lisätään tuotekuvat
				if ($tuotekuvat = $this->hae_tuotekuvat($tuote['tunnus'])) {
					// Multicallilla kaikki kuvat yhdellä kertaa.
					$this->lisaa_tuotekuvat($product_id, $tuotekuvat);
				}

				// Lisätään tuote countteria
				$count++;

			}
			catch (Exception $e) {
				$this->log("Tuotteen {$tuote['tuoteno']} lisäys/päivitys epäonnistui." . print_r($tuote), $e);
			}
		}

		$this->log("$count tuotetta päivitetty");

		// Palautetaan pävitettyjen tuotteiden määrä
		return $count;
	}

	/**
	 * Lisää päivitettyjä Configurable tuotteita Magento-verkkokauppaan.
	 *
	 * @param  array  $dnslajitelma 	Pupesoftin tuote_exportin palauttama tuote array
	 * @return int  	         		Lisättyjen tuotteiden määrä
	 */
	public function lisaa_configurable_tuotteet(array $dnslajitelma)
	{
		$this->log("Lisätään tuotteet (configurable)");

		$count = 0;

		// Populoidaan attributeSet
		$this->_attributeSet = $this->getAttributeSet();

		// Haetaan storessa olevat tuotenumerot
		$skus_in_store = $this->getProductList(true);

		// Lisätään tuotteet
		foreach($dnslajitelma as $nimitys => $tuotteet) {
			try {

				/**
				 * Loopataan tuotteen (configurable) lapsituotteet (simple) läpi
				 * ja päivitetään niiden attribuutit kuten koko ja väri.
				 */
				foreach ($tuotteet as $tuote) {
					$koko = '';
					$vari = '';

					// Simple tuotteiden parametrit kuten koko ja väri
					foreach($tuote['parametrit'] as $parametri) {
						if ($parametri['nimi'] == "Koko") {
							$koko = $this->get_option_id('koko', $parametri['arvo']);
						}
						if ($parametri['nimi'] == "Väri") {
							$vari = $this->get_option_id('vari', $parametri['arvo']);
						}
					}

					// Päivitetään Simple tuote
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

				// Jos lyhytkuvaus on tyhjä, käytetään kuvausta?
				if ($tuotteet[0]['lyhytkuvaus'] == '') {
					$tuotteet[0]['lyhytkuvaus'] = '&nbsp';
				}

				// Erikoishinta
				$tuotteet[0]['kuluprosentti'] = ($tuotteet[0]['kuluprosentti'] == 0) ? '' : $tuotteet[0]['kuluprosentti'];

				// Etsitään kategoria mihin tuote lisätään
				$category_id = $this->findCategory($tuotteet[0]['try_nimi'], $this->_category_tree['children']);

				// Configurable tuotteen tiedot
				$configurable = array(
					'categories'			=> array($category_id),
					'websites'				=> array($tuote['nakyvyys']),
					'name'					=> $tuotteet[0]['nimitys'],
					'description'           => $tuotteet[0]['kuvaus'],
					'short_description'     => utf8_encode($tuotteet[0]['lyhytkuvaus']),
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

				// Jos configurable tuotetta ei löydy, niin lisätään uusi tuote.
				if ( ! in_array($nimitys, $skus_in_store)) {
					$product_id = $this->_proxy->call($this->_session, 'catalog_product.create',
						array(
							'configurable',
							$this->_attributeSet['set_id'],
							$nimitys, # sku
							$configurable
							)
						);
				}
				// Päivitetään olemassa olevaa configurablea
				else {
					$product_id = $this->_proxy->call($this->_session, 'catalog_product.update',
						array(
							$nimitys,
							$configurable
							)
						);
				}

				// Tarkistetaan onko lisätyllä tuotteella tuotekuvia ja lisätään ne
				// Multicallilla kaikki kuvat yhdellä kertaa.
				if ($tuotekuvat = $this->hae_tuotekuvat($tuotteet[0]['tunnus'])) {
					$this->lisaa_tuotekuvat($product_id, $tuotekuvat);
				}

				// Lisätään countteria
				$count++;

			} catch (Exception $e) {
				$this->log("Configurable tuotteen " . $tuotteet[0]['tuoteno'] ." lisäys epäonnistui. ", $e);
			}
		}

		$this->log("$count tuotetta päivitetty");

		// Palautetaan lisättyjen configurable tuotteiden määrä
		return $count;
	}

	/**
	 * Hakee maksetut tilaukset Magentosta ja luo editilaus tiedoston.
	 * Merkkaa haetut tilaukset noudetuksi.
	 *
	 * @param string $status 	Haettavien tilausten status, esim 'prorcessing'
	 * @return array 			Löydetyt tilaukset
	 */
	public function hae_tilaukset($status = 'processing')
	{
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
	 * @param array $dnstock 	Pupesoftin tuote_exportin array
	 * @param int 	$count
	 */
	public function paivita_saldot(array $dnstock)
	{
		$this->log("Päivitetään saldot");

		$count = 0;

		// Loopataan päivitettävät tuotteet läpi (aina simplejä)
		foreach ($dnstock as $tuote) {

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
			} catch (Exception $e) {
				$this->log("Saldojen päivityksessä " . $tuote['tuoteno'] . " päivitys epäonnistui.". $e);
			}

			$count++;
		}

		// Päivitä configurablet

		return $count;
	}

	/**
	 * Päivittää tuotteiden hinnat
	 *
	 * @param array 	$dnshinnasto	Tuotteiden päivitety hinnat
	 * @param int 		$count 			Päivitettyjen tuotteiden määrän
	 */
	public function paivita_hinnat(array $dnshinnasto)
	{
		$count = 0;
		$batch_count = 0;

		// Päivitetään tuotteen hinnastot
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
					$this->log("Hintojen päivityksessä tapahtui virhe " . $tuote['tuoteno'] . " ", $e);
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
	 * @param array $poistetut_tuotteet Poistettavat tuotteet
	 * @return   Poistettujen tuotteiden määrä
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
	private function getCategories()
	{
		try {
			if (empty($this->_category_tree)) {
				// Haetaan kaikki defaulttia suuremmat kategoriat (2)
				$this->_category_tree = $this->_proxy->call($this->_session, 'catalog_category.tree');
				#$this->_category_tree = $this->_category_tree['children'][0]; # Skipataan rootti categoria
			}

			return $this->_category_tree;
		} catch (Exception $e) {
			$this->log("Virhe kategorioiden hakemisessa", $e);
		}
	}

	/**
	 * Etsii kategoriaa nimeltä Magenton kategoria puusta.
	 */
	private function findCategory($name, $root) {

		$category_id = false;

		foreach($root as $i => $category) {

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

	/**
	 * Palauttaa attribuutin option id:n
	 *
	 * Esimerkiksi koko, S palauttaa jonkun numeron jolla tuotteen päivityksessä saadaan attribuutti
	 * oikein.
	 *
	 * @param  string $name  	Attribuutin nimi, koko tai vari
	 * @param  string $value 	Atrribuutin arvo, S, M, XL...
	 * @return int        	 	Options_id
	 */
	private function get_option_id($name, $value)
	{
		$attribute_list = $this->getAttributeList();
		$attribute_id = '';

		// Etsitään halutun attribuutin id
		foreach($attribute_list as $attribute) {
			if (strcasecmp($attribute['code'], $name) == 0) {
				$attribute_id = $attribute['attribute_id'];
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
		foreach($options as $option) {
			if (strcasecmp($option['label'], $value) == 0) {
				return $option['value'];
			}
		}

		// Mitään ei löytyny
		return 0;
	}

	/**
	 * Lisää tuotteen tuotekuvat
	 * @param  string 	$product_id Tuotteen tunnus
	 * @param  array 	$tuotekuvat Tuotteen kuvatiedostot
	 * @return array    			Tiedostonimet
	 */
	private function lisaa_tuotekuvat($product_id, $tuotekuvat)
	{

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

		// Lisätään tuotekuvat
		try {
			$filenames = $this->_proxy->multicall($this->_session, $calls);
		} catch (Exception $e) {
			echo $e->getMessage();
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
				$this->log("PDO yhteys on poikki. Yritetään uudelleen.", $e);
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
		} catch (Exception $e) {
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
		} catch (Exception $e) {
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
		} catch (Exception $e) {
			$this->log(__METHOD__, $e);
		}
	}

	/**
	 * Virhelogi
	 * @param string $message 		Virheviesti
	 * @param exception $exception 	Exception
	 */
	private function log($message, $exception = '')
	{
		if (self::LOGGING == true) {
			$timestamp = date('d.m.y H:i:s');

			if ($exception != '') {
				$message .= " (" . $exception->getMessage() . ") faultcode: " . $exception->faultcode;
			}

			$message .= "\n";
			error_log($timestamp . " " . $message, 3, '/tmp/magento_log.txt');
		}
	}
}
