<?php

require_once '../inc/pupeExcel.inc';
require_once '../inc/functions.inc';

class vahvistamattomat_ostorivit_excel {

	protected $excel;
	protected $excel_rivi = 0;
	protected $excel_sarake = 0;
	protected $toimittaja = array();
	protected $rivit = array();
	protected $yhtiorow = array();
	protected $kieli;

	function __construct() {
		$this->excel = new pupeExcel();
	}

	public function generoi() {
		$this->alku();

		$this->move_seuraavalle_riville();
		$this->move_seuraavalle_riville();
		$this->move_seuraavalle_riville();

		$this->rivit();

		$this->move_seuraavalle_riville();
		$this->move_seuraavalle_riville();

		$this->loppu();

		return $this->excel->close();
	}

	private function alku() {
		$this->write_solu($this->toimittaja['nimi']);
		$this->move_seuraavalle_riville();
		$this->write_solu($this->toimittaja['nimitark']);
		$this->move_seuraavalle_riville();
		$this->write_solu($this->toimittaja['osoite']);
		$this->move_seuraavalle_riville();
		$this->write_solu($this->toimittaja['postino'].' '.$this->toimittaja['postitp']);
		$this->move_seuraavalle_riville();
		$this->write_solu($this->toimittaja['maa']);
		$this->move_seuraavalle_riville();
	}

	private function rivit() {
		$this->print_headers();
		$this->move_seuraavalle_riville();
		foreach ($this->rivit as $rivi) {
			//countti tehdään sen takia, että arrayssä on myös total_rivihinta
			if(count($rivi) > 2) {
				$this->print_rivi($rivi);
				$this->move_seuraavalle_riville();
			}
		}
	}

	private function print_headers() {
		$this->write_solu(t('Tilausnumero', $this->kieli));
		$this->write_solu(t('Tuotenumero', $this->kieli));
		$this->write_solu(t('Toimitusaika', $this->kieli));
		$this->write_solu(t('Määrä', $this->kieli));
		$this->write_solu(t('Hinta', $this->kieli));
		$this->write_solu(t('Ale', $this->kieli));
		$this->write_solu(t('Yhteensä', $this->kieli));
	}

	private function print_rivi($rivi) {
		$this->write_solu($rivi['otunnus']);
		$this->write_solu($rivi['toim_tuoteno'] . ' (' . $rivi['tuoteno'] . ')');
		$this->write_aika_solu($rivi['toimaika']);
		$this->write_solu($rivi['tilkpl'] . '/' . $rivi['ulkkpl'] . ' ' . $rivi['omyks'] . '/' . $rivi['toyks']);
		$this->write_solu($rivi['hinta']);
		if($rivi['ale1'] != 0) {
			$this->write_solu($rivi['ale1'] * 1);
		}
		else {
			$this->write_numero_solu(0);
		}
		$this->write_solu($rivi['rivihinta']);
	}

	private function loppu() {
		$this->write_solu(t('Yhteensä', $this->kieli));
		$this->write_solu($this->rivit['total_rivihinta']);
	}

	private function write_solu($data) {
		//tämä funktio kirjoittaa tämän hetkiselle riville soluun datan ja siirtyy seuraavaan soluun samalla rivillä
		$this->excel->write($this->excel_rivi, $this->excel_sarake, $data);
		$this->excel_sarake++;
	}

	private function write_numero_solu($numero) {
		$this->excel->writeNumber($this->excel_rivi, $this->excel_sarake, $numero);
		$this->excel_sarake++;
	}

	private function write_aika_solu($aika) {
		$this->excel->writeDate($this->excel_rivi, $this->excel_sarake, $aika);
		$this->excel_sarake++;
	}

	private function add_tyhja_kolumni($kolumni_numero = 1) {
		$this->excel_sarake += $kolumni_numero;
	}

	private function add_tyhja_rivi($rivi_numero = 1) {
		$this->excel_rivi += $rivi_numero;
		$this->excel_sarake = 0;
	}

	private function move_kolumniin($kolumni_numero) {
		$this->excel_sarake = $kolumni_numero;
	}

	private function move_seuraavalle_riville() {
		$this->excel_rivi++;
		$this->excel_sarake = 0;
	}

	public function get_toimittaja() {
		return $this->toimittaja;
	}

	public function set_toimittaja(array $toimittaja) {
		$this->toimittaja = $toimittaja;
	}

	public function get_rivit() {
		return $this->rivit;
	}

	public function set_rivit(array $rivit) {
		$this->rivit = $rivit;
	}

	public function get_yhtiorow() {
		return $this->yhtiorow;
	}

	public function set_yhtiorow(array $yhtiorow) {
		$this->yhtiorow = $yhtiorow;
	}

	public function get_kieli() {
		return $this->kieli;
	}

	public function set_kieli($kieli) {
		$this->kieli = $kieli;
	}

}

?>
