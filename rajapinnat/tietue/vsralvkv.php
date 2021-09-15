<?php

date_default_timezone_set("Europe/Helsinki");

$aikaleima = new DateTime();
$aikaleima = $aikaleima->format("dmYHms");

$kk_vsralvkv = (int) $kk;
$vv_vsralvkv = (int) $vv;

$file  = "000:VSRALVKV\n";
$file .= "198:$aikaleima\n";
$file .= "048:Pupesoft\n";
$file .= "014:".$uytunnus."_pu\n";
$file .= "010:$uytunnus\n";
$file .= "050:K\n";
$file .= "052:$kk_vsralvkv\n";
$file .= "053:$vv_vsralvkv\n";

for ($fx = 1; $fx <= 20; $fx++) {
    $fx_num = $fx + 300;
    $fi_3 = "fi".$fx_num;
    $fi_3 = $$fi_3;
    if ($fi_3 == "-0") {
      $fi_3 = str_replace("-0", "0.00", $fi_3);
    }
    $fi_3 = number_format($fi_3, 2, ',', '');
    if ($fi_3 != "") {
      $file .= "$fx_num:$fi_3\n";
    }
}

$file .= "042:".$yhtiorow['puhelin']."\n";
$file .= "999:1\n";

echo "<pre>";
echo $file;
echo "</pre>";

$file = base64_encode($file);
?>

<style>
.linkbutton {
    display: inline-block;
    border: solid 0px #999; 
    background-color: #999;
    font-size: 10pt;
    font-weight: bold; text-decoration: none;
    color: #fff;
    margin: 1px;
    padding: 5px 10px 5px 10px;
    min-width: 90px;
    -webkit-border-radius: 3px;
    border-radius: 3px;" 
}
.linkbutton:hover {
    background: #666;
    border-color: #666;
    color: #fff;
}
</style>

<a class="linkbutton" href="../rajapinnat/tietue/lataa.php?tietuedataname=<?php echo 'VSRALVKV_'.$aikaleima.'_'.$kk.$vv.'.txt'; ?>&tietuedata=<?php echo $file; ?>" target="_blank">
<?php echo t('Tallenna'); ?> VSRALVKV ></a><br><br>