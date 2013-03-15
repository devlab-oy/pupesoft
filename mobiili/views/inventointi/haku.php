<div class='header'>
	<button onclick='window.location.href="inventointi.php"' class='button left'><img src='back2.png'></button>
	<h1><?php echo $title ?></h1>
</div>

<div class='main'>
	<form action='inventointi.php' method='get' id='haku_formi'>
		<input type='hidden' name='tee' value='haku'>
		<table>
			<tr>
				<th>Viivakoodi</th>
				<td><input type='text' name='viivakoodi' id='viivakoodi' autofocus></td>
			</tr>
			<tr>
				<th>Tuoteno</th>
				<td><input type='text' name='tuoteno'></td>
			</tr>
			<tr>
				<th>Tuotepaikka</th>
				<td><input type='text' name='tuotepaikka'></td>
			</tr>
		</table>
</div>
<div class='controls'>
	<input type='submit' value='OK'>
</div>
</form>

<input type='button' id='myHiddenButton' visible='false' onclick='javascript:doFocus();' width='1px' style='display:none'>
<script type='text/javascript'>

	$(document).ready(function() {
		$('#viivakoodi').on('keyup', function() {
			// Autosubmit vain jos on syötetty tarpeeksi pitkä viivakoodi
			if ($('#viivakoodi').val().length > 8) {
				$('#haku_formi').submit();
			}
		});
	});

	function doFocus() {
		document.getElementById('viivakoodi').focus();
	}

	function clickButton() {
	   document.getElementById('myHiddenButton').click();
	}

 	setTimeout('clickButton()', 500);
</script>