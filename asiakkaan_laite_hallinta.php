<?php

if (isset($_POST["tee"])) {
	if ($_POST["tee"] == 'lataa_tiedosto') {
		$lataa_tiedosto = 1;
	}
	if (isset($_POST["kaunisnimi"]) and $_POST["kaunisnimi"] != '') {
		$_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
	}
}

require ("inc/parametrit.inc");

if ($tee == 'lataa_tiedosto') {
	$filepath = "/tmp/".$tmpfilenimi;
	if (file_exists($filepath)) {
		readfile($filepath);
		unlink($filepath);
	}
	else {
		echo "<font class='error'>".t("Tiedostoa ei ole olemassa")."</font>";
	}
	exit;
}

//Tänne tämän tiedoston ajax requestit
if ($ajax_request) {

}

echo "<font class='head'>".t("Laitehallinta")."</font><hr>";
?>
<style>
	.paikka_tr_hidden {
		display: none;
	}

	.laitteet_table_hidden {
		display: none;
	}

	.paikka_tr_not_hidden {
	}

	.laiteet_table_not_hidden {
	}
</style>
<script>
	$(document).ready(function() {
		bind_kohde_tr_click();
		bind_paikka_tr_click();

		bind_poista_kohde_button();
		bind_poista_paikka_button();
		bind_poista_laite_button();
	});

	function bind_kohde_tr_click() {
		$('.kohde_tr').bind('click', function(event) {
			if (event.target.nodeName === 'TD' || event.target.nodeName === 'IMG') {
				var paikat = '.paikat_' + $(this).find('.kohde_tunnus').val();

				if ($(this).hasClass('hidden')) {
					//TODO yhteinäistä bind_tr_click logiikka. ei yhdistä vaan _yhtenäistä_
					$(this).parent().find(paikat).removeClass('paikka_tr_hidden');
					$(this).parent().find(paikat).addClass('paikka_tr_not_hidden');

					$(this).addClass('not_hidden');
					$(this).removeClass('hidden');

					$(this).find('.porautumis_img').attr('src', $('#right_arrow').val());
				}
				else {
					$(this).parent().find(paikat).removeClass('paikka_tr_not_hidden');
					$(this).parent().find(paikat).addClass('paikka_tr_hidden');

					$(this).addClass('hidden');
					$(this).removeClass('not_hidden');

					$(this).find('.porautumis_img').attr('src', $('#down_arrow').val());
				}
			}


		});
	}

	function bind_paikka_tr_click() {
		$('.paikat_tr').bind('click', function(event) {
			//paikat_tr sisällä on buttoni uuden laitteen luomiseen paikalle. jos sitä klikataan, emme halua triggeröidä tätä.
			if (event.target.nodeName === 'TD' || event.target.nodeName === 'IMG') {
				if ($(this).children().find('.laitteet_table_hidden').length > 0) {
					$(this).children().find('.laitteet_table_hidden').addClass('laitteet_table_not_hidden');
					$(this).children().find('.laitteet_table_hidden').removeClass('laitteet_table_hidden');

					$(this).find('.porautumis_img').attr('src', $('#right_arrow').val());
				}
				else {
					$(this).children().find('.laitteet_table_not_hidden').addClass('laitteet_table_hidden');
					$(this).children().find('.laitteet_table_hidden').removeClass('laitteet_table_not_hidden');

					$(this).find('.porautumis_img').attr('src', $('#down_arrow').val());
				}
			}

		});
	}

	function bind_poista_kohde_button() {
		$('.poista_kohde').click(function() {
			var ok = confirm($('#oletko_varma_confirm_message').val());
			if (ok) {
				var button = $(this);
				var kohde_tunnus = button.parent().find('.kohde_tunnus').val();
				$.ajax({
					async: true,
					type: 'GET',
					url: 'yllapito.php?toim=kohde&del=1&del_relaatiot=1&tunnus=' + kohde_tunnus
				}).done(function() {
					if (console && console.log) {
						console.log('Kohteen poisto onnistui');
					}
					//poistetaan kohde_tr:n paikka_tr:t
					button.parent().parent().parent().find('.paikat_' + kohde_tunnus).remove();

					//poistetaan itse kohde_tr
					button.parent().parent().remove();

				}).fail(function() {
					if (console && console.log) {
						console.log('Kohteen poisto EPÄONNISTUI');
					}
					alert($('#poisto_epaonnistui_message').val());
				});
			}
		});
	}

	function bind_poista_paikka_button() {
		$('.poista_paikka').click(function() {
			var ok = confirm($('#oletko_varma_confirm_message').val());
			if (ok) {
				var button = $(this);
				var paikka_tunnus = button.parent().parent().find('.paikka_tunnus').val();
				$.ajax({
					async: true,
					type: 'GET',
					url: 'yllapito.php?toim=paikka&del=1&del_relaatiot=1&tunnus=' + paikka_tunnus
				}).done(function() {
					if (console && console.log) {
						console.log('Paikan poisto onnistui');
					}
					button.parent().parent().remove();

				}).fail(function() {
					if (console && console.log) {
						console.log('Paikan poisto EPÄONNISTUI');
					}
					alert($('#poisto_epaonnistui_message').val());
				});
			}
		});
	}

	function bind_poista_laite_button() {
		$('.poista_laite').click(function() {
			var ok = confirm($('#oletko_varma_confirm_message').val());
			if (ok) {
				var button = $(this);
				var laite_tunnus = button.parent().find('.laite_tunnus').val();
				$.ajax({
					async: true,
					type: 'GET',
					url: 'yllapito.php?toim=laite&del=1&del_relaatiot=1&tunnus=' + laite_tunnus
				}).done(function() {
					if (console && console.log) {
						console.log('Laitteen poisto onnistui');
					}
					button.parent().parent().remove();

				}).fail(function() {
					if (console && console.log) {
						console.log('Laitteen poisto EPÄONNISTUI');
					}
					alert($('#poisto_epaonnistui_message').val());
				});
			}
		});
	}

</script>
<?php

$request = array(
	'ytunnus'		 => $ytunnus,
	'asiakasid'		 => $asiakasid,
	'asiakas_tunnus' => $asiakas_tunnus,
	'ala_tee'		 => $ala_tee,
);

if ($tee == 'hae_asiakas' or ($tee == '' and !empty($valitse_asiakas))) {
	$request['haettu_asiakas'] = hae_asiakas($request);
}


if (!empty($request['haettu_asiakas'])) {
	$asiakkaan_kohteet = hae_asiakkaan_kohteet_joissa_laitteita($request);

	if ($request['ala_tee'] == 'tulosta_kalustoraportti') {
		$asiakkaan_kohteet['yhtio'] = $yhtiorow;
		$asiakkaan_kohteet['asiakas'] = $request['haettu_asiakas'];
		$request['pdf_filepath'] = tulosta_kalustoraportti($asiakkaan_kohteet);
	}

	echo_kohteet_table($asiakkaan_kohteet, $request);
}

echo "<div id='wrapper'>";
echo_kayttoliittyma($request);
echo "</div>";

function echo_kayttoliittyma($request = array()) {
	global $kukarow, $yhtiorow, $palvelin2;

	echo "<input type='hidden' id='down_arrow' value='{$palvelin2}pics/lullacons/bullet-arrow-down.png' />";
	echo "<input type='hidden' id='right_arrow' value='{$palvelin2}pics/lullacons/bullet-arrow-right.png' />";
	echo "<input type='hidden' id='oletko_varma_confirm_message' value='".t("Oletko varma")."' />";
	echo "<input type='hidden' id='poisto_epaonnistui_message' value='".t("Poisto epäonnistui")."' />";

	echo "<form method='POST' action='' name='asiakas_haku'>";

	echo "<input type='hidden' id='tee' name='tee' value='hae_asiakas' />";
	echo "<input type='text' id='ytunnus' name='ytunnus' />";
	echo "<input type='submit' value='".t("Hae")."' />";

	echo "</form>";
}

function echo_kalustoraportti_form($haettu_asiakas) {
	global $kukarow, $yhtiorow;

	echo "<form method='POST' action='' name='tulosta_kalustoraportti'>";
	echo "<input type='hidden' id='tee' name='tee' value='hae_asiakas' />";
	echo "<input type='hidden' id='ala_tee' name='ala_tee' value='tulosta_kalustoraportti' />";
	echo "<input type='hidden' id='ytunnus' name='ytunnus' value='{$haettu_asiakas['tunnus']}' />";
	echo "<input type='submit' value='".t("Tulosta kalustoraportti")."' />";
	echo "</form>";
}

function tulosta_kalustoraportti($kohteet) {
	global $kukarow, $yhtiorow;

	$filepath = kirjoita_json_tiedosto($kohteet);
	return aja_ruby($filepath);
}

function echo_kohteet_table($asiakkaan_kohteet = array(), $request = array()) {
	global $palvelin2, $lopetus;

	$haettu_asiakas = $request['haettu_asiakas'];

	$lopetus = "{$palvelin2}asiakkaan_laite_hallinta.php////tee=hae_asiakas//ytunnus={$haettu_asiakas['tunnus']}";

	echo "<font class='head'>{$haettu_asiakas['nimi']}</font>";
	echo "<br/>";
	echo "<br/>";

	echo_kalustoraportti_form($haettu_asiakas);
	echo "<br/>";
	echo "<br/>";

	if (!empty($request['pdf_filepath'])) {
		var_dump($request['pdf_filepath']);
		$tiedostot = explode(' ', $request['pdf_filepath']);
		foreach ($tiedostot as $tiedosto) {
			echo_tallennus_formi($tiedosto, t("Kalustoraportti"), 'pdf');
		}
	}

	echo "<table>";

	echo "<tr>";
	echo "<th>".t("Kohteen nimi")."</th>";
	echo "<th>".t("Paikan nimi")."</th>";
	echo "<th>".t("Laitteet")."</th>";
	echo "</tr>";

	echo "<tr>";
	echo "<td>";
	echo "<a href='yllapito.php?toim=kohde&uusi=1&lopetus={$lopetus}&valittu_asiakas={$haettu_asiakas['tunnus']}'><button>".t("Luo uusi kohde")."</button></a>";
	echo "</td>";

	echo "<td>";
	echo "</td>";

	echo "<td>";
	echo "</td>";

	echo "</tr>";

	foreach ($asiakkaan_kohteet['kohteet'] as $kohde_index => $kohde) {
		kohde_tr($kohde_index, $kohde);
	}

	echo "</table>";
}

function kohde_tr($kohde_index, $kohde) {
	global $palvelin2, $lopetus;

	echo "<tr class='kohde_tr hidden'>";

	echo "<td>";
	echo "<button class='poista_kohde'>".t("Poista kohde")."</button>";
	echo "&nbsp";
	echo "<input type='hidden' class='kohde_tunnus' value='{$kohde_index}' />";
	echo "<a href='yllapito.php?toim=kohde&lopetus={$lopetus}&tunnus={$kohde_index}'>".$kohde['kohde_nimi']."</a>";
	echo "&nbsp";
	echo "<img class='porautumis_img' src='{$palvelin2}pics/lullacons/bullet-arrow-down.png' />";
	echo "</td>";

	echo "<td>";
	//paikan nimi tyhjä
	echo "</td>";

	echo "<td>";
	//laitteet tyhjä
	echo "</td>";

	echo "</tr>";
	paikka_tr($kohde_index, $kohde['paikat']);
}

function paikka_tr($kohde_index, $paikat = array()) {
	global $palvelin2, $lopetus;

	echo "<tr class='paikka_tr_hidden paikat_{$kohde_index}'>";
	echo "<td>";
	echo "<a href='yllapito.php?toim=paikka&uusi=1&&lopetus={$lopetus}&valittu_kohde={$kohde_index}'><button>".t("Luo kohteelle uusi paikka")."</button></a>";
	echo "</td>";

	echo "<td>";
	echo "</td>";

	echo "<td>";
	echo "</td>";

	echo "</tr>";
	if (!empty($paikat)) {
		foreach ($paikat as $paikka_index => $paikka) {
			echo "<tr class='paikat_tr paikka_tr_hidden paikat_{$kohde_index}'>";

			echo "<td>";
			echo "<input type='hidden' class='paikka_tunnus' value='{$paikka_index}' />";
			echo "</td>";

			echo "<td>";
			echo "<a href='yllapito.php?toim=paikka&lopetus={$lopetus}&tunnus={$paikka_index}'>{$paikka['paikka_nimi']}</a>";
			echo "&nbsp";
			echo "<img class='porautumis_img' src='{$palvelin2}pics/lullacons/bullet-arrow-down.png' />";
			echo "<br/>";
			echo "<button class='poista_paikka'>".t("Poista paikka")."</button>";
			echo "</td>";

			echo "<td>";
			echo "<a href='yllapito.php?toim=laite&uusi=1&lopetus={$lopetus}&valittu_paikka={$paikka_index}'><button>".t("Luo paikkaan uusi laite")."</button></a>";
			echo "<br/>";
			laitteet_table($paikka['laitteet']);
			echo "</td>";

			echo "</tr>";
		}
	}
}

function laitteet_table($laitteet = array()) {
	global $palvelin2, $lopetus;

	echo "<table class='laitteet_table_hidden'>";
	echo "<tr>";
	echo "<th>".t("Tuotenumero")."</th>";
	echo "<th>".t("Tuotteen nimi")."</th>";
	echo "<th>".t("Sijainti")."</th>";
	echo "<th>".t("Poista")."</th>";
	echo "</tr>";

	foreach ($laitteet as $laite) {
		echo "<tr>";

		echo "<td>";
		echo "<a href='yllapito.php?toim=laite&lopetus={$lopetus}&tunnus={$laite['laite_tunnus']}'>{$laite['tuoteno']}</a>";
		echo "</td>";

		echo "<td>";
		echo $laite['tuote_nimi'];
		echo "</td>";

		echo "<td>";
		echo $laite['sijainti'];
		echo "</td>";

		echo "<td>";
		if (!empty($laite['laite_tunnus'])) {
			echo "<input type='hidden' class='laite_tunnus' value='{$laite['laite_tunnus']}' />";
			echo "<button class='poista_laite'>".t("Poista laite")."</button>";
		}
		echo "</td>";

		echo "</tr>";
	}
	echo "</table>";
}

function hae_asiakas($request) {
	global $kukarow, $yhtiorow;

	if ($request['ytunnus'] != '') {
		$ytunnus = $request['ytunnus'];
		$asiakasid = $request['asiakasid'];
		require("inc/asiakashaku.inc");
	}

	return $asiakasrow;
}

function hae_asiakkaan_kohteet_joissa_laitteita($request) {
	global $kukarow;

	$select = "";
	$join = "";
	if (!empty($request['ala_tee'])) {
		$select = "ta1.selite as sammutin_tyyppi,
					ta2.selite as sammutin_koko,
					huoltosykli.huoltovali as huoltovali,";

		$join = "	LEFT JOIN tuotteen_avainsanat ta1
					ON ( ta1.yhtio = tuote.yhtio
						AND ta1.tuoteno = tuote.tuoteno
						AND ta1.laji = 'sammutin_tyyppi' )
					LEFT JOIN tuotteen_avainsanat ta2
					ON ( ta2.yhtio = tuote.yhtio
						AND ta2.tuoteno = tuote.tuoteno
						AND ta2.laji = 'sammutin_koko' )
					LEFT JOIN huoltosykli
					ON ( huoltosykli.yhtio = laite.yhtio
						AND huoltosykli.tyyppi = ta1.selite
						AND huoltosykli.koko = ta2.selite
						AND huoltosykli.olosuhde = paikka.olosuhde )";
	}

	$query = "	SELECT kohde.tunnus as kohde_tunnus,
				kohde.nimi as kohde_nimi,
				paikka.tunnus as paikka_tunnus,
				paikka.nimi as paikka_nimi,
				tuote.nimitys as tuote_nimi,
				laite.tunnus as laite_tunnus,
				{$select}
				laite.*
				FROM kohde
				LEFT JOIN paikka
				ON ( paikka.yhtio = kohde.yhtio
					AND paikka.kohde = kohde.tunnus)
				LEFT JOIN laite
				ON ( laite.yhtio = paikka.yhtio
					AND laite.paikka = paikka.tunnus )
				LEFT JOIN tuote
				ON ( tuote.yhtio = laite.yhtio
					AND tuote.tuoteno = laite.tuoteno )
				{$join}
				WHERE kohde.yhtio = '{$kukarow['yhtio']}'
				AND kohde.asiakas = {$request['haettu_asiakas']['tunnus']}";
	$result = pupe_query($query);

	$asiakkaan_kohteet = array();
	while ($kohde = mysql_fetch_assoc($result)) {
		$asiakkaan_kohteet['kohteet'][$kohde['kohde_tunnus']]['kohde_nimi'] = $kohde['kohde_nimi'];
		$asiakkaan_kohteet['kohteet'][$kohde['kohde_tunnus']]['kohde_tunnus'] = $kohde['kohde_tunnus'];

		if (!empty($kohde['paikka_tunnus'])) {
			$asiakkaan_kohteet['kohteet'][$kohde['kohde_tunnus']]['paikat'][$kohde['paikka_tunnus']]['laitteet'][] = $kohde;
			$asiakkaan_kohteet['kohteet'][$kohde['kohde_tunnus']]['paikat'][$kohde['paikka_tunnus']]['paikka_nimi'] = $kohde['paikka_nimi'];
		}
	}

	return $asiakkaan_kohteet;
}

function kirjoita_json_tiedosto($data) {
	$filename = "kalustoraportti_{$data['tunnus']}.json";
	$filepath = "/tmp/{$filename}";

	array_walk_recursive($data, 'array_utf8_encode');

	file_put_contents($filepath, json_encode($data));

	return $filepath;
}

function array_utf8_encode(&$item, $key) {
	$item = utf8_encode($item);
}

function aja_ruby2($filepath) {
	global $pupe_root_polku;
	echo "ruby {$pupe_root_polku}/pdfs/ruby/kalustoraportti.rb {$filepath}";
//	return proc_open("sudo ruby {$pupe_root_polku}/pdfs/ruby/kalustoraportti.rb {$filepath}");

	$descriptorspec = array(
		0	 => array("pipe", "r"), // stdin is a pipe that the child will read from
		1	 => array("pipe", "w"), // stdout is a pipe that the child will write to
		2	 => array("file", "/tmp/error.txt", "a") // stderr is a file to write to
	);

	$cwd = '/tmp';
	$env = array('some_option' => 'aeiou');

	$process = proc_open("ruby {$pupe_root_polku}/pdfs/ruby/kalustoraportti.rb {$filepath}", $descriptorspec, $pipes, $cwd, $env);

	if (is_resource($process)) {
		// $pipes now looks like this:
		// 0 => writeable handle connected to child stdin
		// 1 => readable handle connected to child stdout
		// Any error output will be appended to /tmp/error-output.txt

		fwrite($pipes[0], '<?php print_r($_ENV); ?>');
		fclose($pipes[0]);

		echo stream_get_contents($pipes[1]);
		fclose($pipes[1]);

		// It is important that you close any pipes before calling
		// proc_close in order to avoid a deadlock
		$return_value = proc_close($process);

		//echo "command returned $return_value\n";
	}
}

function aja_ruby($filepath) {
	global $pupe_root_polku;
	$return = array();
	exec("ruby {$pupe_root_polku}/pdfs/ruby/kalustoraportti.rb {$filepath}", $return);
	return $return;
}
require ("inc/footer.inc");