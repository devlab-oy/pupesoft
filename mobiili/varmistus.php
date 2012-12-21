<?php
echo "<div class='header'><h1>",t("SUUNTALAVALLE"),"</h1></div>";
echo "<div class='main'>";
echo t("Laitetaanko suuntalava {$suuntalava} hyllytyskierrokselle tai suoraan hyllyyn?");
echo "
    <form method='post' action=''>
    <table>
    <tr>
        <th>", t("Suuntalavan korkeus"),"</th>
        <td><input type='text' name='korkeus'/></td>
    </table>";
echo "</div>";

echo "<div class='controls'>
    <button name='submit' value='hyllytyskierrokselle' onclick='submit();'>",t("Hyllytyskierrokselle"),"</button>
    <button name='submit' value='hyllyyn' onclick='submit();'>",t("Suoraan hyllyyn"),"</button>
    <button name='submit' value='takaisin' onclick='submit();'>",t("Takaisin"),"</button>
    <input type='hidden' name='varmistus' value='true' />
    </form>
</div>";