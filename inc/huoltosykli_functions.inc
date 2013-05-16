<?php

if (!function_exists('hae_huoltosykli')) {

	function hae_huoltosykli($huoltosykli_tunnus) {
		global $kukarow, $yhtiorow;

		$query = "	SELECT *
					FROM huoltosykli
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tunnus = '{$huoltosykli_tunnus}'";
		$result = pupe_query($query);

		return mysql_fetch_assoc($result);
	}
}
if (!function_exists('hae_laitteen_oletus_huoltosyklit')) {

	function hae_laitteen_oletus_huoltosyklit($laite_tunnus) {
		global $kukarow, $yhtiorow;

		$query = "	SELECT huoltosykli.*
					FROM   laite
					JOIN tuotteen_avainsanat ta
					ON ( ta.yhtio = laite.yhtio
						AND ta.tuoteno = laite.tuoteno
						AND ta.laji = 'sammutin_tyyppi' )
					JOIN tuotteen_avainsanat ta2
					ON ( ta2.yhtio = laite.yhtio
						AND ta2.tuoteno = laite.tuoteno
						AND ta2.laji = 'sammutin_koko' )
					JOIN paikka
					ON ( paikka.yhtio = laite.yhtio
						AND paikka.tunnus = laite.paikka )
					JOIN huoltosykli
					ON ( huoltosykli.yhtio = laite.yhtio
						AND huoltosykli.tyyppi = ta.selite
						AND huoltosykli.koko = ta2.selite
						AND huoltosykli.olosuhde = paikka.olosuhde )
					WHERE laite.yhtio = '{$kukarow['yhtio']}'
					AND laite.tunnus = '{$laite_tunnus}'";
		$result = pupe_query($query);

		$huoltosyklit = array();
		while ($huoltosykli = mysql_fetch_assoc($result)) {
			$huoltosyklit[] = $huoltosykli;
		}

		return $huoltosyklit;
	}
}

/**
 * Iframe näkymään huoltosykli dropdownin change bind ja huoltosyklin huoltovälin muutto
 */
function js_huoltosykli_dropdown_huoltovali_change() {
	?>
	<script>
		$(document).ready(function() {
			bind_huoltosykli_change();

			//jos ollaan muokkaustilassa niin ensimmäisellä sivulatauksella ei päivitetä huoltosykliä, koska kentässä saattaa olla poikkeava huoltoväli ja sitä ei haluta kadottaa
			if ($('input[name=tunnus]').val().length === 0) {
				$('.huoltosykli_tunnus').change();
			}
		});

		function bind_huoltosykli_change() {
			$('.huoltosykli_tunnus').on('change', function() {
				var ajax_request_obj = hae_huoltosyklin_huoltovali($(this).val());

				var huoltovali_input = $(this).parent().parent().parent().find('.huoltovali');
				ajax_request_obj.done(function(huoltosykli) {
					$(huoltovali_input).val(huoltosykli.huoltovali);
				});
			});
		}

		function hae_huoltosyklin_huoltovali(huoltosykli_tunnus) {
			return $.ajax({
				async: true,
				dataType: 'json',
				type: 'GET',
				url: 'yllapito.php?no_head=yes&ajax_request=1&toim=huoltosykli&hae_huoltosykli=1&huoltosykli_tunnus=' + huoltosykli_tunnus
			}).done(function(data) {
				if (console && console.log) {
					console.log('Huoltoväli haku onnistui');
					//console.log(data);
				}

			});
		}
	</script>
	<?php

}