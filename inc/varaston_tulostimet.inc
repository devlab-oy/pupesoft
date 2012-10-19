<?php

	$otsikko = 'Varaston tulostusalueet';
	$otsikko_nappi = "tulostusalue";

	$kentat = " tunnus,
				(select nimitys from varastopaikat where yhtio='$kukarow[yhtio]' and tunnus = varaston_tulostimet.varasto AND varasto_status != 'P') varasto,
				nimi,
				pakkaamo,
				prioriteetti,
				alkuhyllyalue,
				alkuhyllynro,
				loppuhyllyalue,
				loppuhyllynro,
				(select kirjoitin from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus = varaston_tulostimet.printteri1) printteri1";

	$jarjestys = 'varasto, prioriteetti, alkuhyllyalue';
?>