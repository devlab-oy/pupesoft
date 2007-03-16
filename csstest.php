<?php

require("inc/papametrit.inc");

echo "
<html>
<body>
<form action='#'>
<input type='text'>
<input type='checkbox'	name="1">
<input type='radio' 	name="2">
<input type='radio' 	name="2">
<input type='submit'>
</form>
<br>
<br>
<table>
<tr><td>TH</td></tr>
<tr><td>TD</td></tr>
<tr><td class='tumma'>TD.tumma: Fontti ja tausta samanväriset kuin TH:ssa</td><td class='tumma'>TD.tumma</td><td class='tumma'>TD.tumma</td></tr>
<tr><td class='green'>TD.green: Fontti vihreä. Tausta sama kuin TD:ssä</td><td class='green'>TD.green</td><td class='green'>TD.green</td></tr>
<tr><td class='spec'>TD.spec: Tausta sama kuin TD:ssä mutta fontinväri sama ku TH:ssa</td><td class='spec'>TD.spec</td><td class='spec'>TD.spec</td></tr>
<tr><td class='back'>TD.back: Fontinväri sama kuin TD:ssä mutta tausta sama kuin BODY:ssä</td><td class='back'>TD.back</td><td class='back'>TD.back</td></tr>
</table>
<br>
<br>
<a href='#'>Default linkki</a><br>
<a.td href='#'>TD:class linkki (Tässä on hover toiminto)</a><br>
<a.menu href='#'>Menu:class linkki</a><br>
<br>
<br>
Default tekstiä: bla bla bla bla!!!<br>
<font class='info'>INFO tekstiä: bla bla bla bla!!!</font><br>
<font class='head'>HEAD tekstiä: bla bla bla bla!!!</font><br>
<font class='menu'>MENU tekstiä: bla bla bla bla!!!</font><br>
<font class='error'>ERROR tekstiä: bla bla bla bla!!!</font><br>
<font class='message'>MESSAGE tekstiä: bla bla bla bla!!!</font><br>
<pre>PRE-tekstiä: bla bla bla bla!!!</pre>
<br>
<br>
<div class='popup' style='visibility:visible'>DIV:POPUP kannataa vaan kattoa, rttä on hyvän näköinen suhteessa muihin väreihin</div>
<br>
<br>
</body>
</html>";

require("inc/footer.inc");

?>