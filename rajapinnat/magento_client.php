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
			$this->log("Magento päivitysskripti aloitettu " . date('d-m-y H:i:s') . "\n");
		} catch (Exception $e) {
			echo $e->getMessage();
			exit;
		}
	}

	/**
	 * Destructor
	 */
	function __destruct() {
		$this->log("\nPäivitysskripti päättyi " . date('d-m-y H:i:s') . "\n");
	}

	/**
	 * Lisää kaikki tai puuttuvat kategoriat Magento-verkkokauppaan.
	 *
	 * @param  array  $dnsryhma Pupesoftin tuote_exportin palauttama array
	 * @return int           	Lisättyjen kategorioiden määrä
	 */
	public function lisaa_kategoriat(array $dnsryhma)
	{
		$this->log("Lisätään kategoriat");

		try {
			$count = 0;

			// Loopataan osastot ja tuoteryhmat
			foreach($dnsryhma as $osasto => $tuoteryhmat) {

				foreach ($tuoteryhmat as $kategoria) {

					// Haetaan kategoriat joka kerta koska lisättäessä puu muuttuu
					$category_tree = $this->getCategories();

					// Jos osastoa ei löydy magenton category_treestä, niin lisätään se
					$parent_id = $this->find_category($kategoria['osasto_fi'], $category_tree['children']);

					// Jos kategoria löytyi, lisätään tuoteryhmä sen alle
					if ($parent_id) {

						// Tarkastetaan ettei tuoteryhmää ole jo lisätty
						if (!$this->find_category($kategoria['try_fi'], $category_tree['children'])) {
							$category_data = array(
								'name' => $kategoria['try_fi'],
								'is_active' => 1,
								'position' => 1,
								'default_sort_by' => 'position',
								'available_sort_by' => 'position',
								'include_in_menu' => 1
							);

							// Kutsutaan soap rajapintaa
							$category_id = $this->_proxy->call($this->_session, 'catalog_category.create',
								array(
									$parent_id,
									$category_data
									)
							);
							$count++;
						}
					}
					// Muuten lisätään ensin osasto ja sen alle tuoteryhmä.
					else {
						$category_data = array(
							'name' => $kategoria['osasto_fi'],
							'is_active' => 1,
							'position' => 1,
							'default_sort_by' => 'position',
							'available_sort_by' => 'position',
							'include_in_menu' => 0
						);

						// Kutsutaan soap rajapintaa
						$category_id = $this->_proxy->call($this->_session, 'catalog_category.create',
							array(
								2,
								$category_data
								)
						);
						$count++;

						// Lisätään tuoteryhmä lisätyn osaston alle
						$category_data = array(
							'name' => $kategoria['try_fi'],
							'is_active' => 1,
							'position' => 1,
							'default_sort_by' => 'position',
							'available_sort_by' => 'position',
							'include_in_menu' => 1
						);

						// Kutsutaan soap rajapintaa
						$category_id = $this->_proxy->call($this->_session, 'catalog_category.create',
							array(
								$category_id,
								$category_data
								)
						);
						$count++;
					}
				}
			}

			$this->_category_tree = $this->getCategories();

			$this->log("$count kategoriaa lisätty");

			// Palautetaan monta kategoriaa lisättiin.
			return $count;

		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}

	/**
	 * Lisää päivitettyjä Simple tuotteita Magento-verkkokauppaan.
	 *
	 * @param  array  $dnstuote 	Pupesoftin tuote_exportin palauttama tuote array
	 * @return int  	         	Lisättyjen tuotteiden määrä
	 */
	public function lisaa_simple_tuotteet(array $dnstuote)
	{
		$this->log("Lisätään tuotteita (simple)");

		// Tuote countteri
		$count = 1;

		// Tarvitaan kategoriat
		$category_tree = $this->getCategories();

		// Populoidaan attributeSet
		$this->_attributeSet = $this->getAttributeSet();

		// Haetaan storessa olevat tuotenumerot
		$skus_in_store = $this->getProductList(true);

		// Lisätään tuotteet erissä
		foreach($dnstuote as $tuote) {

			// Lyhytkuvaus ei saa olla magentossa tyhjä.
			// Käytetään kuvaus kentän tietoja jos lyhytkuvaus on tyhjä.
			if ($tuote['lyhytkuvaus'] == '') {
				$tuote['lyhytkuvaus'] = '&nbsp;';
			}

			// Etsitään kategoria_id tuoteryhmällä
			$category_id = $this->find_category($tuote['try_nimi'], $category_tree['children']);

			// Lisätään tai päivitetään tuote
			try {

				// Jos tuotetta ei ole olemassa niin lisätään se
				if ( ! in_array($tuote['tuoteno'], $skus_in_store)) {
					// Jos tuotteen lisäys ei onnistu ei tuotekuviakaan lisätä.
					$product_id = $this->_proxy->call($this->_session, 'catalog_product.create',
						array(
							'simple',
							$this->_attributeSet['set_id'],
							$tuote['tuoteno'], # sku
							array(
									'categories'            => array($category_id),
									'websites'              => array($tuote['nakyvyys']),
									'name'                  => $tuote['nimi'],
									'description'           => $tuote['kuvaus'],
									'short_description'     => utf8_encode($tuote['lyhytkuvaus']),
									'weight'                => $tuote['tuotemassa'],
									'status'                => '1',
									'visibility'            => '1',
									'price'                 => $tuote['myymalahinta'],
									'tax_class_id'          => $this->getTaxClassID(),
									'meta_title'            => '',
									'meta_keyword'          => '',
									'meta_description'      => ''
								)
							)
						);
				}
				// Tuote on jo olemassa, päivitetään
				else {
					$product_id = $this->_proxy->call($this->_session, 'catalog_product.update',
						array(
							$tuote['tuoteno'], # sku
							array(
									'categories'            => array($category_id),
									'websites'              => array($tuote['nakyvyys']),
									'name'                  => $tuote['nimi'],
									'description'           => $tuote['kuvaus'],
									'short_description'     => utf8_encode($tuote['lyhytkuvaus']),
									'weight'                => $tuote['tuotemassa'],
									'status'                => '1',
									'visibility'            => '1',
									'price'                 => $tuote['myymalahinta'],
									'tax_class_id'          => $this->getTaxClassID(),
									'meta_title'            => '',
									'meta_keyword'          => '',
									'meta_description'      => ''
								)
							)
						);

				}

				// Lisätään tuotekuvat
				if ($tuotekuvat = $this->hae_tuotekuvat($tuote['tunnus'])) {
					// Multicallilla kaikki kuvat yhdellä kertaa.
					$this->lisaa_tuotekuvat($product_id, $tuotekuvat);
				}

				// Lisätään tuote countteria
				$count++;

			} catch (Exception $e) {
				$this->log("Tuotteen (" . $tuote['tuoteno'] . ") lisäys/päivitys epäonnistui.", $e);
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
								'visibility'		=> 1,
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

				// Etsitään kategoria mihin tuote lisätään
				$category_id = $this->find_category($tuotteet[0]['try_nimi'], $this->_category_tree['children']);

				// Configurable tuotteen tiedot
				$configurable = array(
					'categories'			=> array($category_id),
					'websites'				=> array($tuote['nakyvyys']),
					'name'					=> $tuotteet[0]['nimitys'],
					'description'           => $tuotteet[0]['kuvaus'],
					'short_description'     => utf8_encode($tuotteet[0]['lyhytkuvaus']),
					'weight'                => $tuotteet[0]['tuotemassa'],
					'status'                => 1,
					'visibility'            => '4', # Configurablet nakyy kaikkialla
					'price'                 => $tuotteet[0]['myymalahinta'],
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

		// HUOM: invoicella on state ja orderilla on status
		// Invoicen statet 'pending' => 1, 'paid' => 2, 'canceled' => 3
		$orders = array();

		// Toimii ordersilla
		$filter = array(array('status' => array('eq' => $status)));

		// Invoicella on state
		# $filter = array(array('state' => array('eq' => 'paid')));

		// Haetaan tilaukset (orders.status = 'processing')
		$fetched_orders = $this->_proxy->call($this->_session, 'sales_order.list', $filter);

		// Haetaan laskut (invoices.state = 'paid')

		foreach ($fetched_orders as $order) {
			// Haetaan tilauksen tiedot (orders)
			$orders[] = $this->_proxy->call($this->_session, 'sales_order.info', $order['increment_id']);

			// Päivitetään tilauksen tila että se on noudettu pupesoftiin
			$this->_proxy->call($this->_session, 'sales_order.addComment', array('orderIncrementId' => $order['increment_id'], 'status' => 'processing_pupesoft'));
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
	 * Etsii kategoriaa nimeltä Magenton kategoria puusta.
	 *
	 * @param 	string 	$name          	Etsittävän kategorian nimi
	 * @param  	array  	$category_tree 	Magenton kategoria puu
	 * @return 	mixed                 	Löydetty category_id tai false
	 */
	private function find_category($name, array $category_tree)
	{
		// Etsitään ensin default_categoryn alta
		foreach ($category_tree as $category) {

			// Jos kategoria löytyy pääkategorian alta
			if ($category['name'] == $name) {
				return $category['category_id'];
			}

			// Jos kategorialla on lapsia, etsitään niiden alta
			if (!empty($category['children'])) {

				foreach ($category['children'] as $sub_category) {
					if ($sub_category['name'] == $name) {
						return $sub_category['category_id'];
					}
				}
			}
		}

		return false;
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
	 * Hakee kaikki kategoriat
	 */
	private function getCategories()
	{
		if (empty($this->_category_tree)) {
			// Haetaan kaikki defaulttia suuremmat kategoriat (2)
			$this->_category_tree = $this->_proxy->call($this->_session, 'catalog_category.tree', 2);
		}

		return $this->_category_tree;
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
			error_log($timestamp . " " . $message, 3, 'magento_log.txt');
		}
	}
}
