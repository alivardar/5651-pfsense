<?php



include('guiconfig.inc');

$pgtitle = 'Tanımlama: Yönlendirme Tablosu';

include('head.inc');

?>
<body link="#000000" vlink="#000000" alink="#000000">
<?php include("fbegin.inc"); ?>
<p class="pgtitle"><?=$pgtitle?></p>

<div id="mainarea">
<form action="diag_routes.php" method="post">
<table class="tabcont" width="100%" border="0" cellspacing="0" cellpadding="6">

<tr>
<td class="vncellreq" width="22%">İsim Çözümleme</td>
<td class="listr" width="78%">
<input type="checkbox" class="formfld" name="resolve" value="yes" <?php if ($_POST['resolve'] == 'yes') echo 'checked'; ?>> Etkinleştir</input>
<br />
<span class="expl">Bu işlem aktifleştirilirse tabloda görülebilir.</span>
</tr>

<tr>
<td class="vncellreq" width="22%">&nbsp;</td>
<td class="listr" width="78%">
<input type="submit" class="formbtn" name="submit" value="Göster" />
<br />
<br />
<span class="vexpl"><span class="red"><strong>Bilgi:</strong></span> İsim çözümleme etkinleştirilirse, sorgulama süresi biraz daha uzun sürmektedir. Tarayıcınızda dur düğmesine tıklayarak istediğiniz zaman durdurabilirsiniz.</span>
</td>
</tr>

</table>
</form>

<?php

	$netstat = ($_POST['resolve'] == 'yes' ? 'netstat -rW' : 'netstat -nrW');
	list($dummy, $internet, $internet6) = explode("\n\n", shell_exec($netstat));

	foreach (array(&$internet, &$internet6) as $tabindex => $table) {
		$elements = ($tabindex == 0 ? 8 : 8);
		$name = ($tabindex == 0 ? 'IPv4' : 'IPv6');
?>
<table class="tabcont" width="100%" cellspacing="0" cellpadding="6" border="0">
<tr><td class="listbg" colspan="<?=$elements?>"><font color="white"><strong><?=$name;?></strong></font></td></tr>
<? 
		foreach (explode("\n", $table) as $i => $line) {
			if ($i == 0) continue;

			if ($i == 1)
				$class = 'listhdrr';
			else
				$class = 'listr';

			print("<tr>\n");
			$j = 0;
			foreach (explode(' ', $line) as $entry) {
				if ($entry == '') continue;
				print("<td class=\"$class\">$entry</td>\n");
				$j++;
			}
			// The 'Expire' field might be blank
			if ($j == $elements - 1)
				print('<td class="listr">&nbsp;</td>' . "\n");
			print("</tr>\n");
		}
		print("</table>\n");
	} 

?>
</table>

</div>

<?php
include('fend.inc');
?>
