<div class='header'>
	<button onclick='window.location.href="index.php"' class='button left'><img src='back2.png'></button>
	<h1><?php echo $title ?></h1>
</div>

<div class='main'>
	<p><a href='inventointi?tee=haku' class='button'><?= t("Vapaa inventointi") ?></a></p>
	<p><a href='?tee=listat' class='button'><?= t("Keräyspaikat listalta") ?></a></p>
	<p><a href='?tee=listat' class='button'><?= t("Reservipaikat listalta") ?></a></p>
</div>
