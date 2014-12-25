<?php
/* $Id$ */
/*
	firewall_nat_1to1.php
	part of m0n0wall (http://m0n0.ch/wall)

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

if (!is_array($config['nat']['onetoone'])) {
	$config['nat']['onetoone'] = array();
}
$a_1to1 = &$config['nat']['onetoone'];
nat_1to1_rules_sort();

if ($_POST) {

	$pconfig = $_POST;

	if ($_POST['apply']) {
		$retval = 0;

		config_lock();
		$retval |= filter_configure();
		config_unlock();

		$savemsg = get_std_save_message($retval);

		if ($retval == 0) {
			if (file_exists($d_natconfdirty_path))
				unlink($d_natconfdirty_path);
			if (file_exists($d_filterconfdirty_path))
				unlink($d_filterconfdirty_path);
		}
	}
}

if ($_GET['act'] == "del") {
	if ($a_1to1[$_GET['id']]) {
		unset($a_1to1[$_GET['id']]);
		write_config();
		touch($d_natconfdirty_path);
		header("Location: firewall_nat_1to1.php");
		exit;
	}
}

$pgtitle = "Firewall: NAT: 1:1";
include("head.inc");

?>
<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<?php include("fbegin.inc"); ?>
<p class="pgtitle"><?=$pgtitle?></p>
<form action="firewall_nat_1to1.php" method="post">
<?php if ($savemsg) print_info_box($savemsg); ?>
<?php if (file_exists($d_natconfdirty_path)): ?><p>
<?php print_info_box_np("The NAT configuration has been changed.<br>You must apply the changes in order for them to take effect.");?><br>
<?php endif; ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">  <tr><td>
<?php
	$tab_array = array();
	$tab_array[] = array("Port Forward", false, "firewall_nat.php");
	$tab_array[] = array("1:1", true, "firewall_nat_1to1.php");
	$tab_array[] = array("Outbound", false, "firewall_nat_out.php");
	display_top_tabs($tab_array);
?>
  </td></tr>
  <tr>
    <td>
	<div id="mainarea">
              <table class="tabcont" width="100%" border="0" cellpadding="0" cellspacing="0">
                <tr>
		  <td width="10%" class="listhdrr">Ağ aygıtı</td>
                  <td width="20%" class="listhdrr">Dış IP</td>
                  <td width="20%" class="listhdrr">İç IP</td>
                  <td width="40%" class="listhdr">Açıklama</td>
                  <td width="10%" class="list">
                    <table border="0" cellspacing="0" cellpadding="1">
                      <tr>
			<td width="17"></td>
                        <td valign="middle"><a href="firewall_nat_1to1_edit.php"><img src="/themes/<?= $g['theme']; ?>/images/icons/icon_plus.gif" width="17" height="17" border="0" title="add rule"></a></td>
                      </tr>
                    </table>
		  </td>
				</tr>
			  <?php $i = 0; foreach ($a_1to1 as $natent): ?>
                <tr>
		  <td class="listlr" ondblclick="document.location='firewall_nat_1to1_edit.php?id=<?=$i;?>';">
                  <?php
					if (!$natent['interface'] || ($natent['interface'] == "wan"))
						echo "WAN";
					else
						echo htmlspecialchars($config['interfaces'][$natent['interface']]['descr']);
				  ?>
                  </td>
                  <td class="listr" ondblclick="document.location='firewall_nat_1to1_edit.php?id=<?=$i;?>';">
                    <?php echo $natent['external'];
					if ($natent['subnet']) echo "/" . $natent['subnet']; ?>
                  </td>
                  <td class="listr" ondblclick="document.location='firewall_nat_1to1_edit.php?id=<?=$i;?>';">
                    <?php echo $natent['internal'];
					if ($natent['subnet']) echo "/" . $natent['subnet']; ?>
                  </td>
                  <td class="listbg" ondblclick="document.location='firewall_nat_1to1_edit.php?id=<?=$i;?>';">
                    <font color="#ffffff"><?=htmlspecialchars($natent['descr']);?>&nbsp;
                  </td>
                  <td class="list" nowrap>
                    <table border="0" cellspacing="0" cellpadding="1">
                      <tr>
                        <td valign="middle"><a href="firewall_nat_1to1_edit.php?id=<?=$i;?>"><img src="/themes/<?= $g['theme']; ?>/images/icons/icon_e.gif" width="17" height="17" border="0" title="edit rule"></a></td>
                        <td valign="middle"><a href="firewall_nat_1to1.php?act=del&id=<?=$i;?>" onclick="return confirm('Do you really want to delete this mapping?')"><img src="/themes/<?= $g['theme']; ?>/images/icons/icon_x.gif" width="17" height="17" border="0" title="delete rule"></a></td>
                      </tr>
                    </table>
                  </td>
                </tr>
		<?php $i++; endforeach; ?>
                <tr>
                  <td class="list" colspan="4"></td>
                  <td class="list">
                    <table border="0" cellspacing="0" cellpadding="1">
                      <tr>
			<td width="17"></td>
                        <td valign="middle"><a href="firewall_nat_1to1_edit.php"><img src="/themes/<?= $g['theme']; ?>/images/icons/icon_plus.gif" width="17" height="17" border="0" title="add rule"></a></td>
                      </tr>
                    </table>
                  </td>
                </tr>
		<tr>
		  <td colspan="4">
		        <p><span class="vexpl"><span class="red"><strong>Bilgi:<br>
                      </strong></span>Depending on the way your WAN connection is setup, you may also need a <a href="firewall_virtual_ip.php">Virtual IP</a>.</span></p>
		  </td>
		<tr>
              </table>
	    </div>
	</td>
</tr>
</table>
</form>
<?php include("fend.inc"); ?>
</body>
</html>
