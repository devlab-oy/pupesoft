<div class='header'>
  <button onclick='window.location.href="tulouta.php"' class='button left'><img src='back2.png'></button>
  <button onclick='window.location.href="suuntalavat.php?uusi"' class='button right'>Uusi</button>
  <h1><?php echo $title ?></h1>
</div>

<div class='main'>

  <div class='search'>
    <form action='suuntalavat.php' method='get'>
      <table>
        <tr><th><label for='hae'><?php echo t("Hae suuntalava:") ?></label></th>
        <td><input id='hae' name='hae' type='text' /></td>
        <td><input type='submit' value='Hae' class='button'/></td>
      </tr>
      </table>
    </form>
  </div>

  <table>
    <tr>
      <th><?php echo t("SSCC") ?></th>
      <th><?php echo t("Ker.vy�hyk.") ?></th>
      <th><?php echo t("Rivit") ?></th>
      <th><?php echo t("Tyyppi") ?></th>
    </tr>

    <?php foreach($suuntalavat as $lava): ?>
    <tr>
      <td>
        <a href='suuntalavat.php?muokkaa=<?php echo $lava['tunnus'] ?>'>
          <?php echo $lava['sscc'] ?></a>
      </td>
      <td><?php echo $lava['keraysvyohyke'] ?></td>
      <td><?php echo $lava['rivit'] ?></td>
      <td><?php echo $lava['tyyppi'] ?></td>
    <tr>
    <?php endforeach ?>
  </table>

</div>

<div class='controls'>
</div>
