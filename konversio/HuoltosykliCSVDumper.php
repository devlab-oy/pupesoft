<?php

require_once('CSVDumper.php');

class HuoltosykliCSVDumper extends CSVDumper {

	protected $unique_values = array();

	public function __construct($kukarow) {
		parent::__construct($kukarow);

		$konversio_array = array(
			'laite'		 => 'LAITE',
			'toimenpide' => 'TUOTENRO',
			'nimitys'	 => 'NIMIKE',
			'huoltovali' => 'VALI',
		);
		$required_fields = array(
			'laite',
			'toimenpide',
		);

		$this->setFilepath("/tmp/konversio/TUOTETARK.csv");
		$this->setSeparator(';#x#');
		$this->setKonversioArray($konversio_array);
		$this->setRequiredFields($required_fields);
		$this->setTable('huoltosykli');
	}

	protected function konvertoi_rivit() {
		$progressbar = new ProgressBar(t('Konvertoidaan rivit'));
		$progressbar->initialize(count($this->rivit));

		foreach ($this->rivit as $index => &$rivi) {
			$rivi = $this->konvertoi_rivi($rivi);
			$rivi = $this->lisaa_pakolliset_kentat($rivi);

			//index + 2, koska eka rivi on header ja laskenta alkaa riviltä 0
			$valid = $this->validoi_rivi($rivi, $index + 2);

			if (!$valid) {
				unset($this->rivit[$index]);
			}

			$progressbar->increase();
		}
	}

	protected function konvertoi_rivi($rivi) {
		$rivi_temp = array();

		foreach ($this->konversio_array as $konvertoitu_header => $csv_header) {
			if (array_key_exists($csv_header, $rivi)) {
				if ($konvertoitu_header == 'huoltovali') {
					$rivi_temp[$konvertoitu_header] = (int)$rivi[$csv_header] * 30;
				}
				else {
					$rivi_temp[$konvertoitu_header] = $rivi[$csv_header];
				}
			}
		}

		return $rivi_temp;
	}

	protected function validoi_rivi(&$rivi, $index) {
		$valid = true;
		foreach ($rivi as $key => $value) {
			if (in_array($key, $this->required_fields) and $value == '') {
				$this->errors[$index][] = t('Pakollinen kenttä')." <b>{$key}</b> ".t('puuttuu');
				$valid = false;
			}

			if ($key == 'laite') {
				$attrs = $this->hae_tuotteen_tyyppi_koko($rivi[$key]);

				if ($attrs == 1) {
					$this->errors[$index][] = t('Huoltosyklin laitteelle')." <b>{$rivi[$key]}</b> ".t('löytyi enemmän kuin 2 laitteen koko tai tyyppiä');
					$valid = false;
				}
				else if ($attrs == 0) {
					$loytyyko_laite_tuote = $this->loytyyko_tuote($rivi[$key]);
					if (!$loytyyko_laite_tuote) {
						list($tyyppi, $koko) = $this->luo_tuote($rivi['toimenpide'], $rivi['nimitys'], $rivi[$key]);
						$rivi['tyyppi'] = $tyyppi;
						$rivi['koko'] = $koko;

						$this->errors[$index][] = t('Laite tuote')." <b>{$rivi[$key]}</b> ".t('perustettiin');
					}
				}
				else {
					$rivi['tyyppi'] = $attrs['tyyppi'];
					$rivi['koko'] = $attrs['koko'];
				}
			}
			else if ($key == 'toimenpide') {
				$valid_temp = $this->loytyyko_tuote($rivi[$key]);
				if (!$valid_temp) {
					$nimitys = $this->luo_tuote($rivi[$key], $rivi['nimitys']);
					$this->errors[$index][] = t('Luotiin toimenpide tuote')." <b>{$nimitys}</b> ";
				}
			}
		}

		return $valid;
	}

	protected function dump_data() {
		$progress_bar = new ProgressBar(t('Ajetaan rivit tietokantaan').' : '.count($this->rivit));
		$progress_bar->initialize(count($this->rivit));
		foreach ($this->rivit as $rivi) {
			$nimitys_temp = $rivi['nimitys'];
			$laite_tuoteno_temp = $rivi['laite'];
			unset($rivi['nimitys']);
			unset($rivi['laite']);

			$query = "	INSERT INTO {$this->table}
						(".implode(", ", array_keys($rivi)).")
						VALUES
						('".implode("', '", array_values($rivi))."')";

			//Purkka fix
			$query = str_replace("'now()'", 'now()', $query);
			pupe_query($query);

			$huoltosykli_tunnus_sisalla = mysql_insert_id();
			$huoltosykli_huoltovali_sisalla = $rivi['huoltovali'];

			$rivi['olosuhde'] = 'X';

			if (stristr($nimitys_temp, 'tarkastus')) {
				$rivi['huoltovali'] = $rivi['huoltovali'] / 2;
			}

			$query = "	INSERT INTO {$this->table}
						(".implode(", ", array_keys($rivi)).")
						VALUES
						('".implode("', '", array_values($rivi))."')";

			//Purkka fix
			$query = str_replace("'now()'", 'now()', $query);
			pupe_query($query);

			$huoltosykli_tunnus_ulkona = mysql_insert_id();
			$huoltosykli_huoltovali_ulkona = $rivi['huoltovali'];

			$this->liita_laitteet_huoltosykliin($laite_tuoteno_temp, $huoltosykli_huoltovali_sisalla, $huoltosykli_huoltovali_ulkona, $huoltosykli_tunnus_sisalla, $huoltosykli_tunnus_ulkona);

			$progress_bar->increase();
		}
	}

	protected function lisaa_pakolliset_kentat($rivi) {
		$rivi = parent::lisaa_pakolliset_kentat($rivi);
		$rivi['pakollisuus'] = '1';
		$rivi['olosuhde'] = 'A'; //Olosuhde sisällä

		return $rivi;
	}

	private function hae_tuotteen_tyyppi_koko($tuoteno) {
		$query = "	SELECT tuotteen_avainsanat.laji,
					tuotteen_avainsanat.selite
					FROM tuotteen_avainsanat
					WHERE tuotteen_avainsanat.yhtio = '{$this->kukarow['yhtio']}'
					AND tuotteen_avainsanat.laji IN ('sammutin_koko','sammutin_tyyppi')
					AND tuotteen_avainsanat.tuoteno = '{$tuoteno}'";
		$result = pupe_query($query);
		$attrs = array();

		if (mysql_num_rows($result) > 2) {
			return 1;
		}

		if (mysql_num_rows($result) == 0) {
			return 0;
		}

		while ($attr = mysql_fetch_assoc($result)) {
			$attrs[str_replace('sammutin_', '', $attr['laji'])] = $attr['selite'];
		}

		return $attrs;
	}

	private function loytyyko_tuote($tuoteno) {
		$query = "	SELECT *
					FROM tuote
					WHERE tuote.yhtio = '{$this->kukarow['yhtio']}'
					AND tuote.tuoteno = '{$tuoteno}'";
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 0) {
			return false;
		}

		return true;
	}

	private function liita_laitteet_huoltosykliin($laite_tuoteno, $huoltosykli_huoltovali_sisalla, $huoltosykli_huoltovali_ulkona, $huoltosykli_tunnus_sisalla, $huoltosykli_tunnus_ulkona) {
		$query = "	SELECT laite.tunnus,
					paikka.olosuhde
					FROM laite
					JOIN paikka
					ON ( paikka.yhtio = laite.yhtio
						AND paikka.tunnus = laite.paikka )
					WHERE laite.yhtio = '{$this->kukarow['yhtio']}'
					AND laite.tuoteno = '{$laite_tuoteno}'";
		$result = pupe_query($query);

		while ($laite = mysql_fetch_assoc($result)) {
			if ($laite['olosuhde'] == 'A') {
				$query = "	INSERT INTO huoltosyklit_laitteet
							SET yhtio = '{$this->kukarow['yhtio']}',
							huoltosykli_tunnus = '{$huoltosykli_tunnus_sisalla}',
							laite_tunnus = '{$laite['tunnus']}',
							huoltovali = '{$huoltosykli_huoltovali_sisalla}',
							pakollisuus = '1',
							laatija = 'import',
							luontiaika = NOW()";
			}
			else if ($laite['olosuhde'] == 'X') {
				$query = "	INSERT INTO huoltosyklit_laitteet
							SET yhtio = '{$this->kukarow['yhtio']}',
							huoltosykli_tunnus = '{$huoltosykli_tunnus_ulkona}',
							laite_tunnus = '{$laite['tunnus']}',
							huoltovali = '{$huoltosykli_huoltovali_ulkona}',
							pakollisuus = '1',
							laatija = 'import',
							luontiaika = NOW()";
			}
			else {
				//JOS ONGELMIA LAITETAAN ULOS
				$query = "	INSERT INTO huoltosyklit_laitteet
							SET yhtio = '{$this->kukarow['yhtio']}',
							huoltosykli_tunnus = '{$huoltosykli_tunnus_ulkona}',
							laite_tunnus = '{$laite['tunnus']}',
							huoltovali = '{$huoltosykli_huoltovali_ulkona}',
							pakollisuus = '1',
							laatija = 'import',
							luontiaika = NOW()";
			}
			pupe_query($query);
		}
	}

	private function luo_tuote($toimenpide_tuoteno, $nimitys, $laite_tuoteno = '') {
		$nimitys = strtolower($nimitys);


		//Yritetään päätellä tuotenumerosta koko ja tyyppi
		$koko = preg_replace('/[^1-9]/', '', $toimenpide_tuoteno);

		//Tuotenumeron nimestä voidaan ottaa huollon tyyppi (2 merkkiä) sekä huollon kohteen koko (2 merkkiä) alusta ja lopusta pois
		$tuoteno_temp = substr($toimenpide_tuoteno, 2);
		$tuoteno_temp = substr($tuoteno_temp, 0, -2);

		//Jos tuoteno_temp on 4 merkkiä kyseessä on paineellinen huoltosykli
		if (strlen($tuoteno_temp) == 4) {
			//Tyyppi on viimeiset 2
			$tyyppi = substr($tuoteno_temp, 2, 4);
			if ($tyyppi == 'ja') {
				$tyyppi = 'jauhesammutin';
			}
			$tyyppi2 = substr($tuoteno_temp, 0, 2);
			if ($tyyppi2 == 'pa') {
				$tyyppi2 = 'paineellinen';
			}
			else if ($tyyppi2 == 'ep') {
				$tyyppi2 = 'paineeton';
			}
		}
		else {
			if ($tuoteno_temp == 'co') {
				$tyyppi = 'hiilidioksidisammutin';
			}
		}

		if (!empty($tyyppi2)) {
			$nimitys = $nimitys.' '.$tyyppi2.' '.$tyyppi.' '.$koko;
		}
		else {
			$nimitys = $nimitys.' '.$tyyppi.' '.$koko;
		}

		if (!empty($laite_tuoteno)) {
			$this->luo_laite_tuote($laite_tuoteno, $laite_tuoteno, $koko, $tyyppi);

			return array($tyyppi, $koko);
		}
		else {
			if ($nimitys == 'tarkastus') {
				$tarkastustyyppi = 'tarkastus';
				$prioriteetti = 3;
			}
			else if ($nimitys == 'huolto') {
				$tarkastustyyppi = 'huolto';
				$prioriteetti = 2;
			}
			else {
				$tarkastustyyppi = 'koeponnistus';
				$prioriteetti = 1;
			}

			$this->luo_toimenpide_tuote($toimenpide_tuoteno, $nimitys, $tarkastustyyppi, $prioriteetti);

			return $nimitys;
		}
	}

	private function luo_laite_tuote($tuoteno, $nimitys, $koko, $tyyppi) {
		$query = "	INSERT INTO tuote
					SET yhtio = '{$this->kukarow['yhtio']}',
					tuoteno = '{$tuoteno}',
					nimitys = '{$nimitys}',
					try = '80',
					tuotetyyppi = '',
					ei_saldoa = '',
					laatija = 'import',
					luontiaika = NOW()";
		pupe_query($query);

		$query = '	INSERT INTO tuotteen_avainsanat
						(
							yhtio,
							tuoteno,
							kieli,
							laji,
							selite,
							laatija,
							luontiaika
						)
						VALUES
						(
							"'.$this->kukarow['yhtio'].'",
							"'.$tuoteno.'",
							"fi",
							"sammutin_tyyppi",
							"'.$tyyppi.'",
							"import",
							NOW()
						)';
		pupe_query($query);

		$query = '	INSERT INTO tuotteen_avainsanat
						(
							yhtio,
							tuoteno,
							kieli,
							laji,
							selite,
							laatija,
							luontiaika
						)
						VALUES
						(
							"'.$this->kukarow['yhtio'].'",
							"'.$tuoteno.'",
							"fi",
							"sammutin_koko",
							"'.$koko.'",
							"import",
							NOW()
						)';
		pupe_query($query);

		return $nimitys;
	}

	private function luo_toimenpide_tuote($tuoteno, $nimitys, $tyyppi, $prioriteetti) {
		$query = "	INSERT INTO tuote
					SET yhtio = '{$this->kukarow['yhtio']}',
					tuoteno = '{$tuoteno}',
					nimitys = '{$nimitys}',
					try = '10',
					tuotetyyppi = 'K',
					ei_saldoa = 'o',
					laatija = 'import',
					luontiaika = NOW()";
		pupe_query($query);

		$query = '	INSERT INTO tuotteen_avainsanat
						(
							yhtio,
							tuoteno,
							kieli,
							laji,
							selite,
							selitetark,
							laatija,
							luontiaika
						)
						VALUES
						(
							"'.$this->kukarow['yhtio'].'",
							"'.$tuoteno.'",
							"fi",
							"tyomaarayksen_ryhmittely",
							"'.$tyyppi.'",
							"'.$prioriteetti.'",
							"import",
							NOW()
						)';
		pupe_query($query);

		return $nimitys;
	}

	protected function tarkistukset() {
		$query = "	SELECT laite.tunnus,
					COUNT(*) AS kpl
					FROM   laite
					JOIN tuote
					ON ( tuote.yhtio = laite.yhtio
						AND tuote.tuoteno = laite.tuoteno
						AND tuote.try IN ( '21', '23', '30', '70', '80' ) )
					JOIN huoltosyklit_laitteet
					ON ( huoltosyklit_laitteet.yhtio = laite.yhtio
						AND huoltosyklit_laitteet.laite_tunnus = laite.tunnus )
					WHERE  laite.yhtio = '{$this->kukarow['yhtio']}'
					GROUP  BY laite.tunnus
					HAVING kpl != 3";
		$result = pupe_query($query);
		$kpl = mysql_num_rows($result);
		echo "Sammuttimia joilla on enemmän tai vähemmän kuin 3 huoltosykliä liitettynä {$kpl}";

		echo "<br/>";

		$query = "	SELECT laite.tunnus
					FROM laite
					WHERE yhtio = '{$this->kukarow['yhtio']}'
					AND laite.tuoteno != 'A990001'";
		$result = pupe_query($query);
		$laitetta = mysql_num_rows($result);

		$query = "	SELECT DISTINCT laite_tunnus
					FROM   huoltosyklit_laitteet
					WHERE  yhtio = '{$this->kukarow['yhtio']}'";
		$result = pupe_query($query);
		$laitetta_joilla_huoltosykli = mysql_num_rows($result);

		$kpl = $laitetta - $laitetta_joilla_huoltosykli;
		echo "Sammuttimia joilla ei ole yhtään huoltosykliä liitettynä {$kpl}";

		$query = "	SELECT tuote.tuoteno,
					tuote.nimitys
					FROM   tuote
					LEFT JOIN tuotteen_avainsanat
					ON ( tuotteen_avainsanat.yhtio = tuote.yhtio
						AND tuotteen_avainsanat.tuoteno = tuote.tuoteno )
					WHERE  tuote.yhtio = '{$this->kukarow['yhtio']}'
					AND tuote.ei_saldoa = 'o'
					AND tuotteen_avainsanat.tuoteno IS NULL";
		$result = pupe_query($query);

		echo "Seuraavilta toimenpide tuotteilta puuttuu tuotteen_avainsana ".mysql_num_rows($result)."<br/>";
		while($toimenpide_tuote = mysql_fetch_assoc($result)) {
			echo $toimenpide_tuote['tuoteno'].' - '.$toimenpide_tuote['nimitys'].'<br/>';
		}

		$query = "	SELECT tuote.tuoteno,
					tuote.nimitys
					FROM   tuote
					LEFT JOIN tuotteen_avainsanat
					ON ( tuotteen_avainsanat.yhtio = tuote.yhtio
						AND tuotteen_avainsanat.tuoteno = tuote.tuoteno )
					WHERE  tuote.yhtio = '{$this->kukarow['yhtio']}'
					AND tuote.ei_saldoa = ''
					AND tuotteen_avainsanat.tuoteno IS NULL";
		$result = pupe_query($query);

		echo "Seuraavilta normi tuotteilta puuttuu tuotteen_avainsana ".mysql_num_rows($result)."<br/>";
		while($toimenpide_tuote = mysql_fetch_assoc($result)) {
			echo $toimenpide_tuote['tuoteno'].' - '.$toimenpide_tuote['nimitys'].'<br/>';
		}
	}

}
