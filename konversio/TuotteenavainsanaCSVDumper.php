<?php

require_once('CSVDumper.php');

class TuotteenavainsanaCSVDumper extends CSVDumper {

	protected $unique_values = array();

	public function __construct($kukarow) {
		parent::__construct($kukarow);

		$konversio_array = array(
			'tuoteno'	 => 'MALLI',
			'tyyppi'	 => 'TYYPPI',
			'paino'		 => 'PAINO',
		);
		$required_fields = array(
			'tuoteno',
			'tyyppi',
			'paino',
		);

		$this->setFilepath("/tmp/laite.csv");
		$this->setSeparator(';');
		$this->setKonversioArray($konversio_array);
		$this->setRequiredFields($required_fields);
		$this->setTable('tuotteen_avainsanat');
	}

	protected function konvertoi_rivit() {
		$progressbar = new ProgressBar(t('Konvertoidaan rivit'));
		$progressbar->initialize(count($this->rivit));

		foreach ($this->rivit as $index => &$rivi) {
			$rivi = $this->decode_to_utf8($rivi);
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
				if ($konvertoitu_header == 'tyyppi') {
					$rivi_temp[$konvertoitu_header] = $rivi[$csv_header].'sammutin';
				}
				else if ($konvertoitu_header == 'paino') {
					$rivi_temp[$konvertoitu_header] = $rivi[$csv_header].'kg';
				}
				else if ($konvertoitu_header == 'tuoteno') {
					$rivi_temp[$konvertoitu_header] = strtoupper($rivi[$csv_header]);
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

			if ($key == 'tyyppi') {
				if (isset($this->unique_values[$rivi['tuoteno']]['tyypit']) and in_array($value, $this->unique_values[$rivi['tuoteno']]['tyypit'])) {
					$valid = false;
				}
				else {
					$this->unique_values[$rivi['tuoteno']]['tyypit'][$index] = $value;
				}
			}
			else if ($key == 'paino') {
				if (isset($this->unique_values[$rivi['tuoteno']]['painot']) and in_array($value, $this->unique_values[$rivi['tuoteno']]['painot'])) {
					$valid = false;
				}
				else {
					$this->unique_values[$rivi['tuoteno']]['painot'][$index] = $value;
				}
			}
		}

		return $valid;
	}

	protected function lisaa_pakolliset_kentat($rivi) {
		$rivi = parent::lisaa_pakolliset_kentat($rivi);
		$rivi['kieli'] = 'fi';

		return $rivi;
	}

	protected function dump_data() {
		$progress_bar = new ProgressBar(t('Ajetaan rivit tietokantaan'));
		$progress_bar->initialize(count($this->rivit));
		foreach ($this->rivit as $rivi) {
			$query = '	INSERT INTO '.$this->table.'
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
							"'.$rivi['yhtio'].'",
							"'.$rivi['tuoteno'].'",
							"'.$rivi['kieli'].'",
							"sammutin_tyyppi",
							"'.$rivi['tyyppi'].'",
							"'.$rivi['laatija'].'",
							"'.$rivi['luontiaika'].'"
						)';
			pupe_query($query);

			$query = '	INSERT INTO '.$this->table.'
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
							"'.$rivi['yhtio'].'",
							"'.$rivi['tuoteno'].'",
							"'.$rivi['kieli'].'",
							"sammutin_koko",
							"'.$rivi['paino'].'",
							"'.$rivi['laatija'].'",
							"'.$rivi['luontiaika'].'"
						)';
			pupe_query($query);

			$progress_bar->increase();
		}
	}

}