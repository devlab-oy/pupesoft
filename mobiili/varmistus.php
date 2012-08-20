<?php
echo "<div class='header'><h1>SUUNTALAVALLE</h1></div>";
echo "<div class='main'>";
echo "Laitetaanko suuntalava {$suuntalava} hyllytyskierrokselle tai suoraan hyllyyn?";
echo "
    <form method='post' action=''>
    <table>
    <tr>
        <th>Suuntalavan korkeus</th>
        <td><input type='text' name='korkeus'/></td>
    </table>";
echo "</div>";

echo "<div class='controls'>
    <button name='submit' value='hyllytyskierrokselle' onclick='submit();'>Hyllytyskierrokselle</button>
    <button name='submit' value='hyllyyn' onclick='submit();'>Suoraan hyllyyn</button>
    <button name='submit' value='takaisin' onclick='submit();'>Takaisin</button>
    <input type='hidden' name='varmistus' value='true' />
    </form>
</div>";