<?php

/**
 * SOAP-clientin wrapperi Magento-verkkokaupan päivitykseen
 *
 * Käytetään suoraan rajapinnat/tuote_export.php tiedostosta, jolla haetaan
 * tarvittavat tiedot pupesoftista.
 */

class MagentoClient {

	// Kutsujen määrä multicall kutsulla
	const MULTICALL_BATCH_SIZE = 100;

	// Soap-clientin sessio
	private $_session;

	// Oletus attribuutti setti
	private $_attributeSet;

	// SOAP-clientin proxy
	private $_proxy;

	// Kategoriat puu
	private $_category_tree;

	/**
	 * Magento client
	 */
	function __construct($url, $user, $pass)
	{
		try {
			$this->_proxy = new SoapClient($url);
			$this->_session = $this->_proxy->login($user, $pass);
			$attributeSets = $this->_proxy->call($this->_session, 'product_attribute_set.list');
			$this->_attributeSet = current($attributeSets);
		} catch (Exception $e) {
			echo $e->getMessage();
			exit;
		}
	}

	/**
	 * Lisää kaikki tai puuttuvat kategoriat Magento-verkkokauppaan.
	 *
	 * @param  array  $dnsryhma Pupesoftin tuote_exportin palauttama array
	 * @return int           	Lisättyjen kategorioiden määrä
	 */
	public function lisaa_kategoriat(array $dnsryhma)
	{
		try {
			$count = 0;

			// Loopataan osastot ja tuoteryhmat
			foreach($dnsryhma as $osasto => $tuoteryhmat) {

				foreach ($tuoteryhmat as $kategoria) {

					// Haetaan kategoriat joka kerta koska lisättäessä puu muuttuu
					$category_tree = $this->get_category_tree();

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

			$this->_category_tree = $this->get_category_tree();

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
		// Tuote countteri
		$tuote_count = 1;

		// Array multicall kutsuille
		$calls = array();

		$category_tree = $this->get_category_tree();

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
				// Lisätään tuote
				// Jos tuotteen lisäys ei onnistu ei tuotekuviakaan lisätä.
				$product_id = $this->_proxy->call($this->_session, 'catalog_product.create',
					array(
						'simple',
						$this->_attributeSet['set_id'],
						$tuote['tuoteno'], # sku
						array(
								'categories'            => array($category_id),
								'websites'              => 'default',
								'name'                  => $tuote['nimi'],
								'description'           => $tuote['kuvaus'],
								'short_description'     => $tuote['lyhytkuvaus'],
								'weight'                => $tuote['tuotemassa'],
								'status'                => '1',
								'visibility'            => '1',
								'price'                 => $tuote['myymalahinta'],
								'tax_class_id'          => '4',
								'meta_title'            => '',
								'meta_keyword'          => '',
								'meta_description'      => ''
							)
						)
					);

				// Haetaan tuotekuvat
				if ($tuotekuvat = $this->hae_tuotekuvat($tuote['tunnus'])) {
					// Multicallilla kaikki kuvat yhdellä kertaa.
					foreach($tuotekuvat as $tuotekuva) {

						$types = array('image', 'small_image', 'thumbnail');

						$calls[] = array('catalog_product_attribute_media.create',
							array($product_id,
								array(
									'file'     => $tuotekuva,
									'label'    => '',
									'position' => 0,
									'types'    => $types,
									'exclude'  => 0
								)
							)
						);
					}

					// Lisätään tuotekuvat
					try {
						$filenames = $this->_proxy->multicall($this->_session, $calls);
						$calls = array();

					} catch (Exception $e) {
						echo $e->getMessage();
					}
				}

				// Lisätään tuote countteria
				$tuote_count++;

			} catch (Exception $e) {
				echo $e->getMessage()."\n";

				// Jos tuote on jo olemassa
				if(strstr($e->getMessage(), "must be unique")) {
					try {
						$product_id = $this->_proxy->call($this->_session, 'catalog_product.update',
							array(
								$tuote['tuoteno'], # sku
								array(
										'categories'            => array($category_id),
										'websites'              => 'default',
										'name'                  => $tuote['nimi'],
										'description'           => $tuote['kuvaus'],
										'short_description'     => $tuote['lyhytkuvaus'],
										'weight'                => $tuote['tuotemassa'],
										'status'                => '1',
										'visibility'            => '1',
										'price'                 => $tuote['myymalahinta'],
										'tax_class_id'          => '4',
										'meta_title'            => '',
										'meta_keyword'          => '',
										'meta_description'      => ''
									)
								)
							);

						if ($tuotekuvat = $this->hae_tuotekuvat($tuote['tunnus'])) {
							// Multicallilla kaikki kuvat yhdellä kertaa.
							foreach($tuotekuvat as $tuotekuva) {
								$types = array('image', 'small_image', 'thumbnail');

								$calls[] = array('catalog_product_attribute_media.create',
									array($product_id,
										array(
											'file'     => $tuotekuva,
											'label'    => '',
											'position' => 0,
											'types'    => $types,
											'exclude'  => 0
										)
									)
								);
							}

							// Lisätään tuotekuvat
							try {
								$filenames = $this->_proxy->multicall($this->_session, $calls);
								$calls = array();

							} catch (Exception $e) {
								echo $e->getMessage();
							}
						}
						// Lisätään tuote countteria
						$tuote_count++;
						echo "tuote päivitetty\n";
					} catch (Exception $e) {
						echo $e->getMessage();
					}
				}
			}
		}
	}

	/**
	 * Lisää päivitettyjä Configurable tuotteita Magento-verkkokauppaan.
	 *
	 * @param  array  $dnstuote 	Pupesoftin tuote_exportin palauttama tuote array
	 * @return int  	         	Lisättyjen tuotteiden määrä
	 */
	public function lisaa_configurable_tuotteet(array $dnslajitelma)
	{
		$count = 0;

		// Lisätään tuotteet
		foreach($dnslajitelma as $nimitys => $tuotteet) {
			try {

				// Lisätään tuote
				// Loopataan tuotteen (configurable) lapsituotteet (simple) läpi
				foreach ($tuotteet as $tuote) {
					$koko = '';
					$vari = '';

					// Etsitään kategoria mihin tuote lisätään
					$category_id = $this->find_category($tuotteet[0]['try_nimi'], $this->_category_tree['children']);

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
								'short_description' => "$tuote[lyhytkuvaus]",
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
					$tuotteet[0]['lyhytkuvaus'] = $tuotteet[0]['kuvaus'];
				}

				$configurable = array(
					'categories'			=> array($category_id),
					'websites'				=> 'default',
					'name'					=> $tuotteet[0]['nimitys'],
					'description'           => $tuotteet[0]['kuvaus'],
					'short_description'     => $tuotteet[0]['lyhytkuvaus'],
					'weight'                => $tuotteet[0]['tuotemassa'],
					'status'                => 1,
					'visibility'            => '4', #nakyvyys
					'price'                 => $tuotteet[0]['myymalahinta'],
					'tax_class_id'          => '4', # 24%
					'meta_title'            => 'meta_title',
					'meta_keyword'          => 'meta_keyword',
					'meta_description'      => 'meta_description',
				);

				// Luodaan configurable
				// Configurablen jonku lapsituotteen tietoja
				$product_id = $this->_proxy->call($this->_session, 'catalog_product.create',
					array(
						'configurable',
						$this->_attributeSet['set_id'],
						$nimitys, # sku
						$configurable
						)
					);

				// Lisää tuotekuva configurablelle
				// Multicallilla kaikki kuvat yhdellä kertaa.
				if ($tuotekuvat = $this->hae_tuotekuvat($tuotteet[0]['tunnus'])) {
					foreach($tuotekuvat as $tuotekuva) {

						$types = array('image', 'small_image', 'thumbnail');

						$calls[] = array('catalog_product_attribute_media.create',
							array($product_id,
								array(
									'file'     => $tuotekuva,
									'label'    => '',
									'position' => 0,
									'types'    => $types,
									'exclude'  => 0
								)
							)
						);
					}

					// Multi call kutsu, jonka jälkeen nollataan calls muuttuja
					try {
						$filenames = $this->_proxy->multicall($this->_session, $calls);
						$calls = array();
					} catch (Exception $e) {
						echo $e->getMessage();
					}
				}
				$count++;

			} catch (Exception $e) {
				echo $e->getMessage()."\n";
			}
		}

		return $count;
	}

	/**
	 * Hakee maksetut tilaukset Magentosta ja luo editilaus tiedoston.
	 *
	 * @return array Palauttaa arrayn tilauksista
	 */
	public function hae_tilaukset($status)
	{
		// HUOM: invoicella on state ja orderilla on status
		// Invoicen statet 'pending' => 1, 'paid' => 2, 'canceled' => 3
		$orders = array();

		// Toimii ordersilla
		$filter = array(array('status' => array('eq' => $status)));

		$fetched_orders = $this->_proxy->call($this->_session, 'sales_order.list', $filter);

		foreach ($fetched_orders as $order) {
			// Haetaan tilauksen tiedot (orders)
			$orders[] = $this->_proxy->call($this->_session, 'sales_order.info', $order['increment_id']);

			// Päivitetään tilaus tilaan processing
			$this->_proxy->call($this->_session, 'sales_order.addComment', array('orderIncrementId' => $order['increment_id'], 'status' => 'processing'));
		}

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
		$count = 0;

		// Loopataan päivitettävät tuotteet läpi (simplet)
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
				echo $e->getMessage()."\n";
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
			// $tuote['tuoteno']
			// $tuote['hinta']
			// $tuote['hinta_veroton']
			$calls[] = array('catalog_product.update', array($tuote['tuoteno'], array('price' => $tuote['hinta'])));

			$batch_count++;
			if ($batch_count > self::MULTICALL_BATCH_SIZE) {

				try {
					$result = $this->_proxy->multicall($this->_session, $calls);
					var_dump($result);
				}
				catch (Exception $e) {
					echo $e->getMessage()."\n";
				}

				$batch_count = 0;
				$calls = array();
			}
		}

		return $count;
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
		try {
			// Haetaan lista attribuuteista vain tarvittaessa
			if (empty($this->_attribute_list)) {
				$this->_attribute_list = $this->_proxy->call(
				    $this->_session,
				    "product_attribute.list",
				    array(
				         $this->_attributeSet['set_id']
				    )
				);
			}

			// Etsitään halutun attribuutin id
			foreach($this->_attribute_list as $attribute) {
				if (strtolower($attribute['code']) == strtolower($name)) {
					$attribute_id = $attribute['attribute_id'];
					break;
				}
				// ei löytynyt
				return 0;
			}

			// Haetaan halutun attribuutin optionssit
			$options = $this->_proxy->call(
			    $this->_session,
			    "product_attribute.options",
			    array(
			         $attribute_id
			    )
			);

			// Etitään optionsin value
			foreach($options as $option) {
				if (strtolower($option['label']) == strtolower($value)) {
					return $option['value'];
				}
			}

			return 0;

		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}

	/**
	 * Hakee magenton kategoriat
	 *
	 */
	private function get_category_tree()
	{
		if (empty($this->_category_tree)) {
			try {
				$this->_category_tree = $this->_proxy->call($this->_session, 'catalog_category.tree', 2);
				return $this->_category_tree;
			} catch(Exception $e) {
				$e->getMessage();
				return 0;
			}
		}
		else {
			return $this->_category_tree;
		}
	}

	/**
	 * Hakee tuotteen tuotekuvat
	 *
	 * @param  int 		$tunnus 		Tuoteen tunnus (tuote.tunnus)
	 * @return array 	$tuotekuvat 	Palauttaa arrayn joka kelpaa magenton soap clientille suoraan
	 */
	private function hae_tuotekuvat($tunnus) {
		global $kukarow, $dbhost, $dbuser, $dbpass, $dbkanta;

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

		$tuotekuvat = array();

		$stmt->execute(array($kukarow['yhtio'], $tunnus));

		while($liite = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$file = array(
				'content' 	=> base64_encode($liite['data']),
				'mime'		=> $liite['filetype'],
				'name'		=> $liite['filename']
			);
			$tuotekuvat[] = $file;
		}

		return $tuotekuvat;
	}
}
