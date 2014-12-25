<?php
/* $Id$ */
/*
	diag_ipsec_sad.php
	Copyright (C) 2004 Scott Ullrich
	All rights reserved.

	originially part of m0n0wall (http://m0n0.ch/wall)
	Copyright (C) 2003-2004 Manuel Kasper <mk@neon1.net>.
	All rights reserved.

	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met:

	1. Redistributions of source code must retain the above copyright notice,
	   this list of conditions and the following disclaimer.

	2. Redistributions in binary form must reproduce the above copyright
	   notice, this list of conditions and the following disclaimer in the
	   documentation and/or other materials provided with the distribution.

	THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
	AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
	POSSIBILITY OF SUCH DAMAGE.
*/

require("guiconfig.inc");

$pgtitle = "Durum: IPSec: SA";
include("head.inc");

?>

<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<?php include("fbegin.inc"); ?>
<p class="pgtitle"><?=$pgtitle?></p>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr><td>
<?php
	$tab_array = array();
	$tab_array[0] = array("Overview", false, "diag_ipsec.php");
	$tab_array[1] = array("SAD", true, "diag_ipsec_sad.php");
	$tab_array[2] = array("SPD", false, "diag_ipsec_spd.php");
	display_top_tabs($tab_array);
?>
  </td></tr>
  <tr>
    <td>
<?php

/* delete any SA? */
if ($_GET['act'] == "del") {
	$fd = @popen("/usr/local/sbin/setkey -c > /dev/null 2>&1", "w");
	if ($fd) {
		fwrite($fd, "delete {$_GET['src']} {$_GET['dst']} {$_GET['proto']} {$_GET['spi']} ;\n");
		pclose($fd);
		sleep(1);
	}
}

/* query SAD */
$sad = return_ipsec_sad_array();

?>
	<div id="mainarea">
            <table class="tabcont" width="100%" border="0" cellpadding="6" cellspacing="0">
<?php if (count($sad)): ?>
  <tr>
                <td nowrap class="listhdrr">Kaynak</td>
                <td nowrap class="listhdrr">Hedef</a></td>
                <td nowrap class="listhdrr">Protokol</td>
                <td nowrap class="listhdrr">SPI</td>
                <td nowrap class="listhdrr">Sifreleme. alg.</td>
                <td nowrap class="listhdr">Kimlik dog. alg.</td>
                <td nowrap class="list"></td>
	</tr>
<?php
foreach ($sad as $sa): ?>
	<tr>
		<td class="listlr"><?=htmlspecialchars($sa['src']);?></td>
		<td class="listr"><?=htmlspecialchars($sa['dst']);?></td>
		<td class="listr"><?=htmlspecialchars(strtoupper($sa['proto']));?></td>
		<td class="listr"><?=htmlspecialchars($sa['spi']);?></td>
		<td class="listr"><?=htmlspecialchars($sa['ealgo']);?></td>
		<td class="listr"><?=htmlspecialchars($sa['aalgo']);?></td>
		<td class="list" nowrap>
		<?php
			$args = "src=" . rawurlencode($sa['src']);
			$args .= "&dst=" . rawurlencode($sa['dst']);
			$args .= "&proto=" . rawurlencode($sa['proto']);
			$args .= "&spi=" . rawurlencode("0x" . $sa['spi']);
		?>
		  <a href="diag_ipsec_sad.php?act=del&<?=$args;?>" onclick="return confirm('Bu güvenlik eşleştirmesini silmek istediğinizden emin misiniz?')"><img src="/themes/<?= $g['theme']; ?>/images/icons/icon_x.gif" width="17" height="17" border="0"></a>
		</td>

	</tr>
<?php endforeach; ?>
<?php else: ?>
<tr><td><p><strong>IPsec ait güvenlik eşleştirmesi bulunamadı.</strong></p></td></tr>
<?php endif; ?>
<td colspan="4">
		      <p><span class="vexpl"><span class="red"><strong>Not:<br>
                      </strong></span>IPsec ayarlarını <a href="vpn_ipsec.php">buradan</a> değiştirebilirsiniz.</span></p>
		  </td>
</table>
</div>

</td></tr>

</table>

<?php include("fend.inc"); ?>
</body>
</html>
