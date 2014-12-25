<?php
/* $Id$ */
/*
	interfaces_vlan.php
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

if (!is_array($config['vlans']['vlan']))
	$config['vlans']['vlan'] = array();

$a_vlans = &$config['vlans']['vlan'] ;

function vlan_inuse($num) {
	global $config, $g;

	if ($config['interfaces']['lan']['if'] == "vlan{$num}")
		return true;
	if ($config['interfaces']['wan']['if'] == "vlan{$num}")
		return true;

	for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++) {
		if ($config['interfaces']['opt' . $i]['if'] == "vlan{$num}")
			return true;
	}

	return false;
}

function renumber_vlan($if, $delvlan) {
	if (!preg_match("/^vlan/", $if))
		return $if;

	$vlan = substr($if, 4);
	if ($vlan > $delvlan)
		return "vlan" . ($vlan - 1);
	else
		return $if;
}

if ($_GET['act'] == "del") {
	/* check if still in use */
	if (vlan_inuse($_GET['id'])) {
		$input_errors[] = "Bu VLAN silinemez çünkü bir ağ aygıtı tarafından kullanımdadır.";
	} else {
		unset($a_vlans[$_GET['id']]);

		/* renumber all interfaces that use VLANs */
		$config['interfaces']['lan']['if'] = renumber_vlan($config['interfaces']['lan']['if'], $_GET['id']);
		$config['interfaces']['wan']['if'] = renumber_vlan($config['interfaces']['wan']['if'], $_GET['id']);
		for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++)
			$config['interfaces']['opt' . $i]['if'] = renumber_vlan($config['interfaces']['opt' . $i]['if'], $_GET['id']);

		write_config();

		interfaces_vlan_configure();
		reload_interfaces_sync();
		filter_configure_sync();

		header("Location: interfaces_vlan.php");
		exit;
	}
}


$pgtitle = "Ağ aygıtları: VLAN";
include("head.inc");

?>

<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<?php include("fbegin.inc"); ?>
<p class="pgtitle"><?=$pgtitle?></p>
<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if (file_exists("/tmp/vlanchanged")) { 
	print_info_box("VLAN ayarları değiştirildi.  UYARI: Etkin olmasıı için <a href='reboot.php'>yeniden başlatma</a> gerekmektedir.");
	mwexec("/bin/rm /tmp/vlanchanged");
}
?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr><td>
<?php
	$tab_array = array();
	$tab_array[0] = array("Interface assignments", false, "interfaces_assign.php");
	$tab_array[1] = array("VLANs", true, "interfaces_vlan.php");
	display_top_tabs($tab_array);
?>
  </td></tr>
  <tr>
    <td>
	<div id="mainarea">
	<table class="tabcont" width="100%" border="0" cellpadding="0" cellspacing="0">
                <tr>
                  <td width="20%" class="listhdrr">Ağ Aygıtı</td>
                  <td width="20%" class="listhdrr">VLAN etiketi</td>
                  <td width="50%" class="listhdr">Açıklama</td>
                  <td width="10%" class="list"></td>
				</tr>
			  <?php $i = 0; foreach ($a_vlans as $vlan): ?>
                <tr>
                  <td class="listlr">
					<?=htmlspecialchars($vlan['if']);?>
                  </td>
                  <td class="listr">
					<?=htmlspecialchars($vlan['tag']);?>
                  </td>
                  <td class="listbg">
		    <font color="white">
                    <?=htmlspecialchars($vlan['descr']);?>&nbsp;
		    </font>
                  </td>
                  <td valign="middle" nowrap class="list"> <a href="interfaces_vlan_edit.php?id=<?=$i;?>"><img src="./themes/<?= $g['theme']; ?>/images/icons/icon_e.gif" width="17" height="17" border="0"></a>
                     &nbsp;<a href="interfaces_vlan.php?act=del&id=<?=$i;?>" onclick="return confirm('Bu VLAN silmek istediğinize emin misiniz?')"><img src="./themes/<?= $g['theme']; ?>/images/icons/icon_x.gif" width="17" height="17" border="0"></a></td>
				</tr>
			  <?php $i++; endforeach; ?>
                <tr>
                  <td class="list" colspan="3">&nbsp;</td>
                  <td class="list"> <a href="interfaces_vlan_edit.php"><img src="./themes/<?= $g['theme']; ?>/images/icons/icon_plus.gif" width="17" height="17" border="0"></a></td>
				</tr>
				<tr>
				<td colspan="3" class="list"><p class="vexpl"><span class="red"><strong>
				  Not:<br>
				  </strong></span>
				  Bütün ağ aygıt sürücüleri başarılı şekilde 802.1Q VLAN etiketlemeyi desteklememektedir.
				  </p>
				  </td>
				<td class="list">&nbsp;</td>
				</tr>
              </table>
	      </div>
	</td>
	</tr>
</table>
<?php include("fend.inc"); ?>
</body>
</html>
