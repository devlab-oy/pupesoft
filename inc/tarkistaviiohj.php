<?php
//	echo "Aluper‰inen viite '$viite'<br>";
	$kerroin = 7;
	for ($i=0; $i < strlen($viite); $i++) {
		$merkki = substr($viite, $i, 1);
		if (!(($merkki < '0') || ($merkki > '9'))) {
			$uviite .= $merkki;
		}
	}
//	echo "Puhdistettu viite on '$uviite'<br>";
	for ($i=2; $i <= strlen($uviite); $i++) {
		$merkki = substr($uviite, -1 * $i, 1); 
		$tulo += $kerroin * $merkki;
		$uuviite .= $merkki;
//		echo "Kerroin $kerroin indexi $i merkki '$merkki' tulo $tulo<br>";
		switch ($kerroin):
			case 7:
				$kerroin = 3;
				break;
			case 3:
				$kerroin = 1;
				break;
			case 1:
				$kerroin = 7;
				break;
		endswitch;
	}
	$tmerkki = substr($uviite, -1);
//	echo "Laskettu : '$tulo'<br>";
	$tulo = substr ($tulo, -1);
	$tulo = 10 - $tulo;
//	echo "Lopullinen merkki on $tulo po $tmerkki";
	$ok = 0;
	if ($tulo == $tmerkki) {
		$ok = 1;
//		echo "Viite oli ok!<br>";
	}
//	else
//	{
//		echo "Viite oli v‰‰rin!<br>";
//	}
//	echo "<form action = 'tarkistaviite.php' method='post'>
//             <input type='text' name = 'viite'>
//	      <input type='Submit' value='Laske'></form>";
?>

		