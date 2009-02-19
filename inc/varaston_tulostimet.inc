<?php
	$otsikko = 'Varaston tulostusalueet';
	$kentat = " tunnus, 				
				(select nimitys from varastopaikat where yhtio='$kukarow[yhtio]' and tunnus = varaston_tulostimet.varasto) varasto,
				nimi,
				pakkaamo,
				prioriteetti,
				alkuhyllyalue,
				alkuhyllynro,
				loppuhyllyalue,
				loppuhyllynro,
				(select kirjoitin from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus = varaston_tulostimet.printteri) printteri";
	$jarjestys = 'varasto, prioriteetti, alkuhyllyalue';	
?>