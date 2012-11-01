<div class='header'>
	<button onclick='window.location.href="index.php"' class='button left'><img src='back2.png'></button>
	<h1><?php echo $title ?></h1>
</div>

<div class='main valikko'>
	<p><a href='inventointi?tee=haku' class='button'><?php echo t("Vapaa inventointi") ?></a></p>
	<p><a href='?tee=listat' class='button'><?php echo t("Keräyspaikat listalta") ?></a></p>
	<p><a href='?tee=listat&reservipaikka=k' class='button'><?php echo t("Reservipaikat listalta") ?></a></p>
</div>