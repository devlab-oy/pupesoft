<?php

/*
	Täällä siis handlataan kaikki menun rakentelut
	Tässä siis pelkkä kysely tilaus_osto.php handlaa kyselyt
*/

$ostomenu = array("oletus"	=> array(		"menuset" 	=> "Toimittajan tuotteet", 
											"query" 	=> "liitostunnus =  '$laskurow[liitostunnus]' ORDER BY tuoteno")
				);

?>