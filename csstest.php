<?php

require("inc/parametrit.inc");

echo "
<font class='head'>CSS-testing:</font><hr><br>

Tässä näkee miten formit käyttäytyy:
<form action='#'>
<input type='text'>
<input type='checkbox'	name='1'>
<input type='radio' 	name='2'>
<input type='radio' 	name='2'>
<input type='submit'>
</form> pitäisi pysyä nipussa ilman suurempia aukkoja.

<br>
<br>

<table>
<tr><th>TH</th><th>TH</th><th>TH</th></tr>
<tr><td>TD</td><td>TD</td><td>TD</td></tr>
<tr class='aktiivi'><td>TD (TR.aktiivi) (Tässä on hover toiminto)</td><td>TD (tr aktiivi)</td><td>TD (tr aktiivi)</td></tr>
<tr><td class='back'>TD.back: Fontinväri sama kuin TD:ssä mutta tausta sama kuin BODY:ssä</td><td class='back'>TD.back</td><td class='back'>TD.back</td></tr>
<tr><td class='green'>TD.green: Fontti vihreä. Tausta sama kuin TD:ssä</td><td class='green'>TD.green</td><td class='green'>TD.green</td></tr>
<tr><td class='spec'>TD.spec: Tausta sama kuin TD:ssä mutta fontinväri sama ku TH:ssa</td><td class='spec'>TD.spec</td><td class='spec'>TD.spec</td></tr>
<tr><td class='tumma'>TD.tumma: Fontti ja tausta samanväriset kuin TH:ssa</td><td class='tumma'>TD.tumma</td><td class='tumma'>TD.tumma</td></tr>
</table>

<br>
<br>

<a href='#'>Default linkki (Tässä on hover toiminto)</a>
<a class='td' href='#'>TD:class linkki (Tässä on hover toiminto)</a>
<a class='menu' href='#'>Menu:class linkki (Tässä on hover toiminto)</a>

<br>
<br>

Default tekstiä: bla bla bla bla!!!<br>
<font class='info'>INFO tekstiä: bla bla bla bla!!!</font><br>
<font class='head'>HEAD tekstiä: bla bla bla bla!!!</font><br>
<font class='menu'>MENU tekstiä: bla bla bla bla!!!</font><br>
<font class='error'>ERROR tekstiä: bla bla bla bla!!!</font><br>
<font class='message'>MESSAGE tekstiä: bla bla bla bla!!!</font><br>

<br>
<br>

<pre>PRE-tekstiä: bla bla bla bla!!!</pre>

<br>
<br>

<div class='popup' style='visibility:visible'>DIV:POPUP kannataa vaan kattoa, rttä on hyvän näköinen suhteessa muihin väreihin</div>

<br>
<br>";

require("inc/footer.inc");

?>