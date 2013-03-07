<?php

/**
 * SOAP-clientin wrapperi Magento-verkkokaupan p�ivitykseen
 *
 * K�ytet��n suoraan rajapinnat/tuote_export.php tiedostosta, jolla haetaan
 * tarvittavat tiedot pupesoftista.
 */

class MagentoClient {

	// Soap-clientin sessio
	private $_session;
	// Oletus attribuutti setti
	private $_attributeSet;
	// SOAP-clientin proxy
	private $_proxy;

	function __construct($url, $user, $pass)
	{
		try {
			$this->_proxy = new SoapClient($url);
			$this->_session = $this->_proxy->login($user, $pass);
			$attributeSets = $this->_proxy->call($this->_session, 'product_attribute_set.list');
			$this->_attributeSet = current($attributeSets);
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}

	/**
	 * Lis�� kaikki tai puuttuvat kategoriat Magento-verkkokauppaan.
	 *
	 * @param  array  $dnsryhma Pupesoftin tuote_exportin palauttama array
	 * @return int           	Lis�ttyjen kategorioiden m��r�
	 */
	public function lisaa_kategoriat(array $dnsryhma)
	{
		try {
			$count = 0;

			// Loopataan osastot ja tuoteryhmat
			foreach($dnsryhma as $osasto => $tuoteryhmat) {

				foreach ($tuoteryhmat as $kategoria) {

					// Haetaan kategoriat joka kerta koska lis�tt�ess� puu muuttuu
					$category_tree = $this->get_category_tree();

					// Jos osastoa ei l�ydy magenton category_treest�, niin lis�t��n se
					$parent_id = $this->find_category($kategoria['osasto_fi'], $category_tree['children']);

					// Jos kategoria l�ytyi, lis�t��n tuoteryhm� sen alle
					if ($parent_id) {

						// Tarkastetaan ettei tuoteryhm�� ole jo lis�tty
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
					// Muuten lis�t��n ensin osasto ja sen alle tuoteryhm�.
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

						// Lis�t��n tuoteryhm� lis�tyn osaston alle
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

			// Palautetaan monta kategoriaa lis�ttiin.
			return $count;

		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}

	/**
	 * Lis�� p�ivitettyj� Simple tuotteita Magento-verkkokauppaan.
	 *
	 * @param  array  $dnstuote 	Pupesoftin tuote_exportin palauttama tuote array
	 * @return int  	         	Lis�ttyjen tuotteiden m��r�
	 */
	public function lisaa_simple_tuotteet(array $dnstuote)
	{
		$count = 1;

		$calls = array();

		// Lis�t��n tuotteet eriss�
		foreach($dnstuote as $tuote) {

			try {
				// Lis�t��n tai p�ivitet��n tuote

				// Lyhytkuvaus ei saa olla tyhj�.
				// K�ytet��n kuvaus kent�n tietoja jos lyhytkuvaus on tyhj�.
				if ($tuote['lyhytkuvaus'] == '') {
					$tuote['lyhytkuvaus'] = $tuote['kuvaus'];
				}

				// Etsit��n kategoria mihin tuote lis�t��n
				$category_id = $this->find_category($tuote['try_nimi'], $this->_category_tree['children']);

				// Lis�t��n tuote
				// Jos tuotteen lis�ys ei onnistu ei tuotekuviakaan lis�t�.
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

				// Lis�t��n tuotekuvat
				// Haetaan tuotekuvat
				if ($tuotekuvat = $this->hae_tuotekuvat($tuote['tunnus'])) {
					// Multicallilla kaikki kuvat yhdell� kertaa.
					foreach($tuotekuvat as $tuotekuva) {

						$types = array('image', 'small_image', 'thumbnail');

						$calls[] = array('catalog_product_attribute_media.create',
							array($product_id,
								array(
									'file'     => $tuotekuva,
									'label'    => 'tuotekuva_teksti',
									'position' => 0,
									'types'    => $types,
									'exclude'  => 0
								)
							)
						);
					}

					// Lis�t��n tuotekuvat
					try {
						$filenames = $this->_proxy->multicall($this->_session, $calls);
						$calls = array();

					} catch (Exception $e) {
						echo $e->getMessage();
					}
				}

				// Lis�t��n tuote countteria
				$count++;

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
						$count++;
						echo "tuote p�ivitetty\n";
					} catch (Exception $e) {
						echo $e->getMessage();
					}

				}
			}
		}
	}

	/**
	 * Lis�� p�ivitettyj� Configurable tuotteita Magento-verkkokauppaan.
	 *
	 * @param  array  $dnstuote 	Pupesoftin tuote_exportin palauttama tuote array
	 * @return int  	         	Lis�ttyjen tuotteiden m��r�
	 */
	public function lisaa_configurable_tuotteet(array $dnslajitelma)
	{
		$count = 0;

		// Lis�t��n tuotteet
		foreach($dnslajitelma as $nimitys => $tuotteet) {
			try {

				// Lis�t��n tuote
				// Loopataan tuotteen (configurable) lapsituotteet (simple) l�pi
				foreach ($tuotteet as $tuote) {
					$koko = '';
					$vari = '';

					// Etsit��n kategoria mihin tuote lis�t��n
					$category_id = $this->find_category($tuotteet[0]['try_nimi'], $this->_category_tree['children']);

					// Simple tuotteiden parametrit kuten koko ja v�ri
					foreach($tuote['parametrit'] as $parametri) {
						if ($parametri['nimi'] == "Koko") {
							$koko = $this->get_option_id('koko', $parametri['arvo']);
						}
						if ($parametri['nimi'] == "V�ri") {
							$vari = $this->get_option_id('vari', $parametri['arvo']);
						}
					}

					// P�ivitet��n Simple tuote
					$result = $this->_proxy->call($this->_session, 'catalog_product.update', array($tuote['tuoteno'],
						array(
								'short_description' => "koko: $koko, vari: $vari",
								'visibility'		=> 0,
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

				// Jos lyhytkuvaus on tyhj�, k�ytet��n kuvausta?
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
					'visibility'            => '1', #nakyvyys
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

				// Lis�� tuotekuva configurablelle
				// Multicallilla kaikki kuvat yhdell� kertaa.
				if ($tuotekuvat = $this->hae_tuotekuvat($tuotteet[0]['tunnus'])) {
					foreach($tuotekuvat as $tuotekuva) {

						$types = array('image', 'small_image', 'thumbnail');

						$calls[] = array('catalog_product_attribute_media.create',
							array($product_id,
								array(
									'file'     => $tuotekuva,
									'label'    => 'tuotekuva_teksti',
									'position' => 0,
									'types'    => $types,
									'exclude'  => 0
								)
							)
						);
					}

					// Multi call kutsu, jonka j�lkeen nollataan calls muuttuja
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
		$orders = array();

		// Haetaan lista maksetuista laskuista
		$filter = array(array('status' => array('eq' => $status)));
		$orders_list = $this->_proxy->call($this->_session, 'order.list', $filter);

		// Haetaan lasku tarkemmat tiedot
		foreach ($orders_list as $order) {
			$orders[] = $this->_proxy->call($this->_session, 'sales_order.info', $order['increment_id']);

		}

		return $orders;
	}

	/**
	 * P�ivitt�� tuotteiden saldot
	 *
	 * @param array $dnstock 	Pupesoftin tuote_exportin array
	 * @param int 	$count
	 */
	public function paivita_saldot(array $dnstock)
	{
		$count = 0;

		// Loopataan p�ivitett�v�t tuotteet l�pi (simplet)
		foreach ($dnstock as $tuote) {

			// $dnstock sis�lt�� tuotenoon ja myyt�viss� m��r�n
			$product_sku = $tuote['tuoteno'];
			$qty = $tuote['myytavissa'];
			$is_in_stock = ($qty > 0) ? 1 : 0;

			try {
				$stock_data = array(
				    'qty' => $qty,
				    'is_in_stock ' => $is_in_stock,
				    'manage_stock ' => 1
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

		// P�ivit� configurablet

		return $count;
	}

	/**
	 * P�ivitt�� tuotteiden hinnat
	 *
	 * @param array 	$dnshinnasto	Tuotteiden p�ivitety hinnat
	 * @param int 		$count 			P�ivitettyjen tuotteiden m��r�n
	 */
	public function paivita_hinnat(array $dnshinnasto)
	{
		$count = 0;

		// P�ivitet��n tuotteen hinnastot
		foreach($dnshinnasto as $tuote) {
			// $tuote['tuoteno']
			// $tuote['hinta']
			// $tuote['hinta_veroton']
			$calls[] = array('catalog_product.update', array($tuote['tuoteno'], array('price' => $tuote['hinta'])));

			$count++;
			if ($count > 10) break;
		}

		$calls[] = array('catalog_product.update', array('ei oo tuotetta', array('price' => $tuote['hinta'])));

		try {
			$result = $this->_proxy->multicall($this->_session, $calls);
			var_dump($result);
		} catch (Exception $e) {
			echo $e->getMessage()."\n";
		}

		return $count;
	}

	/**
	 * Etsii kategoriaa nimelt� Magenton kategoria puusta.
	 *
	 * @param 	string 	$name          	Etsitt�v�n kategorian nimi
	 * @param  	array  	$category_tree 	Magenton kategoria puu
	 * @return 	mixed                 	L�ydetty category_id tai false
	 */
	private function find_category($name, array $category_tree)
	{
		// Etsit��n ensin default_categoryn alta
		foreach ($category_tree as $category) {

			// Jos kategoria l�ytyy p��kategorian alta
			if ($category['name'] == $name) {
				return $category['category_id'];
			}

			// Jos kategorialla on lapsia, etsit��n niiden alta
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
	 * Esimerkiksi koko, S palauttaa jonkun numeron jolla tuotteen p�ivityksess� saadaan attribuutti
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

			// Etsit��n halutun attribuutin id
			foreach($this->_attribute_list as $attribute) {
				if (strtolower($attribute['code']) == strtolower($name)) {
					$attribute_id = $attribute['attribute_id'];
					break;
				}
				// ei l�ytynyt
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

			// Etit��n optionsin value
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
	 *
	 */
	private function get_category_tree()
	{
		try {
			$category_tree = $this->_proxy->call($this->_session, 'catalog_category.tree', 2);
			return $category_tree;
		} catch(Exception $e) {
			$e->getMessage();
			return 0;
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
