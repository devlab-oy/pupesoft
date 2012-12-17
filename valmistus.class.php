<?php

/**
* Valmistus
*/
class Valmistus {

	// Pakolliset kentät
	private $yhtio;
	private $tunnus;

	// Valmistus voi olla valmistuslinjalla
	private $valmistuslinja;

	// Valmistuksen tila
	private $tila;

	// Valmistuksen tuotteet
	private $tuotteet = array();

	// Valmistuksen kesto, tuotteiden kestot summattuna
	private $kesto;

	// Valmistuksen tilat
	const ODOTTAA 				= 'OV';
	const VALMISTUKSESSA 		= 'VA';
	const KESKEYTETTY 			= 'TK';
	const VALMIS_TARKASTUKSEEN 	= 'VT';
	const TARKASTETTU 			= 'TA';

	function __construct() {}

	function valmistuslinja() {
		return $this->valmistuslinja;
	}

	/** Hakee valmistuksella olevat tuotteet, eli tilausrivit joiden tyyppi='W'
	*/
	function tuotteet() {

		if (empty($this->tuotteet)) {
			$query = "SELECT *
						FROM tilausrivi
						WHERE yhtio='$this->yhtio'
						AND otunnus=$this->tunnus
						AND tyyppi IN ('W', 'M')";
			$result = pupe_query($query);

			while($tuote = mysql_fetch_assoc($result)) {
				$this->tuotteet[] = $tuote;
			}
		}

		// Palautetaan tuotteet array
		return $this->tuotteet;
	}

	function raaka_aineet() {
		global $kukarow;

		$query = "SELECT * FROM tilausrivi WHERE yhtio='{$kukarow['yhtio']}' AND otunnus=$this->tunnus";
		$result = pupe_query($query);

		$raaka_aineet = array();
		while ($row = mysql_fetch_assoc($result)) {
			$raaka_aineet[] = $row;
		}

		return $raaka_aineet;
	}

	/**
	 * Laskee valmistuksen raaka-aineiden saldot ja palauttaa riittämättämien tuotteiden tuotenumeron
	 * ja saldot. Huomio muiden valmistusten varaamat saldot ja mahdolliset ostotilaukset jotka saapuvat
	 * ennen kyseisen valmistuksen aloitushetkeä.
	 *
	 * @return array puutteet Puuttuvat raaka-aineet ja niiden saldot.
	*/
	function puutteet() {
		global $kukarow;

		$aloitus_pvm = $this->alkupvm();

		// Haetaan raaka-aineet
		$query = "SELECT *
		            FROM tilausrivi
		            WHERE yhtio='{$kukarow['yhtio']}'
		            AND otunnus='{$this->tunnus}'
		            AND tuoteno!='TYÖ'
		            AND tyyppi='V'";
		$result = pupe_query($query);

		$puutteet = array();

		// Tarkistetaan kaikkien raaka-aineiden saldot
		while ($raaka_aine = mysql_fetch_assoc($result)) {
			$saldo = array();
			list($saldo['saldo'], $saldo['hyllyssa'], $saldo['myytavissa']) = saldo_myytavissa($raaka_aine['tuoteno'], '', '', '', '', '', '', '', '', $aloitus_pvm);

			// Varatut kappaleet valmistuksilta jotka ovat jo valmistuslinjalla.
			// Valmistuslinjalla olevat valmistukset varaavat saldoa ja uuden valmistuksen on
			// tarkistettava paljon ne vähentävät raaka-aineiden saldoa.
			$muut_query = "SELECT tilausrivi.otunnus, COALESCE(sum(tilausrivi.varattu), 0) AS varattu
			            FROM kalenteri
			                JOIN lasku ON (kalenteri.yhtio=lasku.yhtio AND kalenteri.otunnus=lasku.tunnus)
			                JOIN tilausrivi ON (lasku.yhtio=tilausrivi.yhtio AND lasku.tunnus=tilausrivi.otunnus)
			            WHERE kalenteri.yhtio='{$kukarow['yhtio']}'
			                AND kalenteri.tyyppi='valmistus'
			                AND tilausrivi.tyyppi='V'
			                AND tilausrivi.tuoteno='{$raaka_aine['tuoteno']}'
			                AND kalenteri.pvmalku < '$aloitus_pvm'";
			$muut_valmistukset_result = pupe_query($muut_query);
			$muut_valmistukset = mysql_fetch_assoc($muut_valmistukset_result);

			error_log("Tuoteno: " . $raaka_aine['tuoteno']);
			error_log("Muut valmistukset: " . $muut_valmistukset['varattu']);

			// Haetaan raaka-aineen ostotilauksia, jotka vaikuttavat valmistuksen aloitukseen
			$query = "SELECT COALESCE(sum(varattu), 0) AS varattu
			            FROM tilausrivi
			            WHERE yhtio='{$kukarow['yhtio']}'
			            AND tuoteno='{$raaka_aine['tuoteno']}'
			            AND tyyppi='O'
			            #AND kerattyaika != '0000-00-00 00:00:00'
			            AND kerattyaika < '$aloitus_pvm'";
			$ostotilaukset_result = pupe_query($query);
			$ostotilaukset = mysql_fetch_assoc($ostotilaukset_result);

			error_log("Ostotilaukset: " . $ostotilaukset['varattu']);

			$_saldo = $saldo['myytavissa'];

			if ($_saldo <= $raaka_aine['varattu']) {
				$puutteet[$raaka_aine['tuoteno']] = $_saldo;
			}
		}
		return $puutteet;
	}

	/** Valimstuksen alkupvm */
	function alkupvm() {
		$query = "SELECT pvmalku FROM kalenteri WHERE yhtio='$this->yhtio' AND otunnus=$this->tunnus";
		$result = pupe_query($query);
		$valmistus = mysql_fetch_assoc($result);

		return $valmistus['pvmalku'];
	}

	/** Valmistuksen loppupvm */
	function loppupvm() {
		$query = "SELECT pvmloppu FROM kalenteri WHERE yhtio='$this->yhtio' AND otunnus=$this->tunnus";
		$result = pupe_query($query);
		$valmistus = mysql_fetch_assoc($result);

		return $valmistus['pvmloppu'];
	}

	/** Hakee valmistuksen keston */
	function kesto() {
		if (empty($this->kesto)) {
			$query = "SELECT sum(varattu) as kesto
						FROM tilausrivi
						WHERE yhtio='$this->yhtio'
						AND otunnus=$this->tunnus
						AND yksikko='H'";
			$result = pupe_query($query);
			$valmistus = mysql_fetch_assoc($result);

			$this->kesto = $valmistus['kesto'];
		}

		return $this->kesto;
	}

	/**
	 * Valmistukseen jo käytetyt tunnit
	 */
	function kaytetty() {
		$query = "SELECT kentta03 as kaytetyttunnit
				 	FROM kalenteri
					WHERE yhtio='$this->yhtio'
					AND otunnus=$this->tunnus";
		$result = pupe_query($query);
		$valmistus = mysql_fetch_assoc($result);
		return $valmistus['kaytetyttunnit'];
	}

	function tunnus() {
		return $this->tunnus;
	}

	function getTila() {
		return $this->tila;
	}

	/**
	 * Keskeyttää valmistuksen
	 *
	 */
	function keskeyta() {
		global $kukarow;

		// Voidaan keskeyttää vain jos työ on valmistuksessa
		if($this->tila !== Valmistus::VALMISTUKSESSA) {
			throw new Exception("Työtä ei voida keskeyttää");
		}

		if ($this->kaytetyttunnit > $this->kesto()) {
			throw new Exception("Käytetty tunteja enemmän kuin valmistuksen kesto");
		}

		// Keskeytetään työ
		// Merkataan kalenteriin ylityotunnit, kommentit ja kaytetyttunnit
		$query = "UPDATE kalenteri SET
					kentta01='{$this->ylityotunnit}',
					kentta02='{$this->kommentti}',
					kentta03='{$this->kaytetyttunnit}'
					WHERE yhtio='{$kukarow['yhtio']}'
					AND otunnus='{$this->tunnus}'";
		$result = pupe_query($query);

		$this->setTila(Valmistus::KESKEYTETTY);
	}

	/**
	 * Asettaa valmistuksen tilan
	 * @param String $tila
	 */
	function setTila($tila) {
		global $kukarow;

		/** Sallitut tilat ja niiden mahdolliset vaihtoehdot*/
		$states = array(
			Valmistus::ODOTTAA 				=> array(Valmistus::VALMISTUKSESSA, Valmistus::ODOTTAA),
			Valmistus::VALMISTUKSESSA 		=> array(Valmistus::KESKEYTETTY, Valmistus::VALMIS_TARKASTUKSEEN),
			Valmistus::KESKEYTETTY 			=> array(Valmistus::VALMISTUKSESSA, Valmistus::ODOTTAA),
			Valmistus::VALMIS_TARKASTUKSEEN => array(Valmistus::TARKASTETTU)
			);

		// Voidaanko uuteen tilaan vaihtaa,
		// eli löytyykö nykyisen tilan vaihtoehdoista haluttu tila
		if (in_array($tila, $states[$this->tila])) {

			// Mihin tilaan vaihdetaan
			switch ($tila) {

				// Odottaa valmistusta
				case Valmistus::ODOTTAA:

					// Jos työ on keskeytetty ei sitä poisteta kalenterista!
					if ($this->getTila() == 'OV') {
						// Poistetaan kalenterista vain tilassa ODOTTAA olevia valmistuksia (poistaa valmistuksen kalenteri taulusta)
						$query = "DELETE FROM kalenteri WHERE yhtio='{$kukarow['yhtio']}' AND otunnus={$this->tunnus}";

						if (! pupe_query($query)) {
							throw new Exception("Kalenteri merkintää ei poistettu");
						}
					}
					elseif($this->getTila() == 'TK') {
						// Jos työ on keskeytetty ja siirretään takaisin parkkiin
						// nollataan kalenterista valmistuslinja (kalenteri.henkilo)

						$query = "UPDATE kalenteri SET
									henkilo=''
									WHERE yhtio='{$kukarow['yhtio']}'
									AND otunnus='{$this->tunnus}'";
						pupe_query($query);
					}

					break;

				// Valmistukseen
				case Valmistus::VALMISTUKSESSA:
					// Valmistuslinjalla voi olla vain yksi valmistus VALMISTUKSESSA tilassa kerrallaan
					$query = "SELECT kalenteri.kuka, otunnus, valmistuksen_tila
								FROM kalenteri
								JOIN lasku on (kalenteri.yhtio=lasku.yhtio AND kalenteri.otunnus=lasku.tunnus)
								WHERE kalenteri.yhtio='{$kukarow['yhtio']}'
								AND kalenteri.henkilo='{$this->valmistuslinja}'
								AND valmistuksen_tila = 'VA'";
					$result = pupe_query($query);

					// Jos keskeneräinen valmistus löytyy
					if (mysql_num_rows($result) > 0) {
						throw new Exception("Valmistuslinjalla on keskeneräinen valmistus");
					}

					// Pyöristetään aloitusaika (aloitusaikana aikana nykyhetki)
					$pvmalku = round_time(strtotime('now'));
					$kesto = valmistuksen_kesto(array('tunnus' => $this->tunnus));
					$pvmloppu = laske_loppuaika($pvmalku, $kesto*60, $this->valmistuslinja);

					// Päivämäärät oikeaan muotoon
					$pvmalku = date('Y-m-d H:i:s', $pvmalku);
					$pvmloppu = date('Y-m-d H:i:s', $pvmloppu);

					// Päivitetään valmistuksen uudet ajat
					$query = "UPDATE kalenteri
								SET pvmalku='{$pvmalku}', pvmloppu='{$pvmloppu}'
								WHERE yhtio='{$kukarow['yhtio']}'
								AND otunnus='{$this->tunnus}'";

					// Päivitetään laskun ja tilausrivin keräyspäivät?

					if (! pupe_query($query)) {
						throw new Exception("Valmistuksen aikoja ei päivitetty");
					}

					break;

				// Valmistus keskeytetty
				case Valmistus::KESKEYTETTY:
					break;

				// Valmis tarkastukseen
				case Valmistus::VALMIS_TARKASTUKSEEN:
					#echo "valmistus valmis tarkastukseen";
					break;

				// Tarkastettu
				case Valmistus::TARKASTETTU:
					#echo "valmistus merkattu tarkastetuksi!";
					break;

				// Muut
				default:
					throw new Exception("Valmistusta yritettiin muuttaa tuntemattomaan tilaan");
					break;
			}

			// Jos kaikki on ok, päivitetään valmistuksen tila
			$query = "UPDATE lasku
						SET valmistuksen_tila='$tila'
						WHERE yhtio='{$kukarow['yhtio']}'
						AND tunnus=$this->tunnus";
			$result = pupe_query($query);
		}
		else {
			throw new Exception("Ei voida muuttaa tilasta '$this->tila' tilaan '$tila'");
		}
	}

	/**
	 * Hakee kaikki valmistukset
	 * @return Array $valmistukset
	 */
	static function all() {
		global $kukarow;

		// Hakee kaikki keskeneräiset valmistukset (lasku/kalenteri)
		// Vain valmistukset joiden tila on kerätty
		$query = "SELECT
						lasku.yhtio,
						lasku.tunnus,
						lasku.valmistuksen_tila as tila,
						kalenteri.henkilo as valmistuslinja
					FROM lasku
					LEFT JOIN kalenteri ON (lasku.yhtio=kalenteri.yhtio AND lasku.tunnus=kalenteri.otunnus)
					WHERE lasku.yhtio='{$kukarow['yhtio']}'
					AND lasku.valmistuksen_tila in ('OV', 'TK')
					AND lasku.tila='V'
					AND lasku.alatila in ('J', 'C')
					ORDER by pvmalku";
		$result = pupe_query($query);

		$valmistukset = array();
		while($valmistus = mysql_fetch_object($result, 'valmistus')) {
			$valmistukset[] = $valmistus;
		}

		return $valmistukset;
	}

	/** Hakee yksittäisen valmistuksen */
	static function find($tunnus) {
		global $kukarow;

		$query = "SELECT
						lasku.yhtio,
						lasku.nimi,
						lasku.ytunnus,
						lasku.tunnus,
						lasku.valmistuksen_tila as tila,
						kalenteri.pvmalku,
						kalenteri.pvmloppu,
						kalenteri.henkilo as valmistuslinja,
						kalenteri.kentta01 as ylityotunnit,
						kalenteri.kentta02 as kommentti,
						kalenteri.kentta03 as kaytetyttunnit
					FROM lasku
					LEFT JOIN kalenteri on (lasku.yhtio=kalenteri.yhtio AND lasku.tunnus=kalenteri.otunnus)
					WHERE lasku.yhtio='{$kukarow['yhtio']}'
					AND lasku.tunnus=$tunnus LIMIT 1";
		$result = pupe_query($query);

		if ($valmistus = mysql_fetch_object($result, 'valmistus')) {
			return $valmistus;
		}
		else {
			return false;
		}
	}

	/** hakee valmistukset tilan mukaan */
	static function find_by_tila($tila) {
		global $kukarow;

		$query = "SELECT
						lasku.valmistuksen_tila as tila,
						lasku.yhtio,
						lasku.tunnus,
						kalenteri.kentta01 as ylityotunnit,
						kalenteri.kentta02 as kommentti
					FROM lasku
					LEFT JOIN kalenteri ON (lasku.yhtio=kalenteri.yhtio AND lasku.tunnus=kalenteri.otunnus)
					WHERE lasku.yhtio='{$kukarow['yhtio']}'
					AND lasku.tila='V'
					AND lasku.valmistuksen_tila='VT'";

		$result = pupe_query($query);

		$valmistukset = array();
		while ($valmistus = mysql_fetch_object($result, 'valmistus')) {
			$valmistukset[] = $valmistus;
		}

		return $valmistukset;
	}
}

