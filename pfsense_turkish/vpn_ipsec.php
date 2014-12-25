<?php
/*
	vpn_ipsec.php
	part of m0n0wall (http://m0n0.ch/wall)

	Copyright (C) 2003-2005 Manuel Kasper <mk@neon1.net>.
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

if (!is_array($config['ipsec']['tunnel'])) {
	$config['ipsec']['tunnel'] = array();
}
$a_ipsec = &$config['ipsec']['tunnel'];
$wancfg = &$config['interfaces']['wan'];

$pconfig['enable'] = isset($config['ipsec']['enable']);
$pconfig['ipcomp'] = isset($config['ipsec']['ipcomp']);

if ($_POST) {

	if ($_POST['apply']) {
		$retval = 0;
		$retval = vpn_ipsec_refresh_policies();
		$retval = vpn_ipsec_configure();
		/* reload the filter in the background */
		filter_configure();
		$savemsg = get_std_save_message($retval);
		if ($retval == 0) {
			if (file_exists($d_ipsecconfdirty_path))
				unlink($d_ipsecconfdirty_path);
		}
	} else if ($_POST['submit']) {
		$pconfig = $_POST;

		$config['ipsec']['enable'] = $_POST['enable'] ? true : false;
		$config['ipsec']['ipcomp'] = $_POST['ipcomp'] ? true : false;
		
		write_config();

		$retval = 0;
		config_lock();
		$retval = vpn_ipsec_refresh_policies();
		$retval = vpn_ipsec_configure();
		config_unlock();
		/* reload the filter in the background */
		filter_configure();

		$savemsg = get_std_save_message($retval);
		if ($retval == 0) {
			if (file_exists($d_ipsecconfdirty_path))
				unlink($d_ipsecconfdirty_path);
		}
	}
}

if ($_GET['act'] == "del") {
	if ($a_ipsec[$_GET['id']]) {
		/* remove static route if interface is not WAN */
		if($a_ipsec[$_GET['id']]['interface'] <> "wan") {
			$oldgw = resolve_retry($a_ipsec[$_GET['id']]['remote-gateway']);
			mwexec("/sbin/route delete -host {$oldgw}");
		}
		unset($a_ipsec[$_GET['id']]);
		vpn_ipsec_configure();
		filter_configure();
		write_config();
		header("Location: vpn_ipsec.php");
		exit;
	}
}

$pgtitle = "VPN: IPsec";
include("head.inc");

?>


<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<?php include("fbegin.inc"); ?>
<p class="pgtitle"><?=$pgtitle?></p>
<form action="vpn_ipsec.php" method="post">
<?php if ($savemsg) print_info_box($savemsg); ?>
<?php if (file_exists($d_ipsecconfdirty_path)): ?><p>
<?php if ($pconfig['enable'])
		print_info_box_np("IPsec tünel ayarlari değiştirildi.<br>Ayarlar uygulandıktan sonra etkili olacaktır.");?><br>
<?php endif; ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr><td class="tabnavtbl">
<?php
	$tab_array = array();
	$tab_array[0] = array("Tüneller", true, "vpn_ipsec.php");
	$tab_array[1] = array("Mobil istemciler", false, "vpn_ipsec_mobile.php");
	$tab_array[2] = array("Pre-shared anahtarlar", false, "vpn_ipsec_keys.php");
	$tab_array[3] = array("CAs", false, "vpn_ipsec_ca.php");
	display_top_tabs($tab_array);
?>
  </td></tr>
  <tr>
    <td>
	<div id="mainarea">
        <table class="tabcont" width="100%" border="0" cellpadding="6" cellspacing="0">
                <tr>
                  <td class="vtable">
                      <input name="enable" type="checkbox" id="enable" value="yes" <?php if ($pconfig['enable']) echo "checked";?>>
                      <strong>IPsec etkinleştir</strong></td>
                </tr>
                <tr>
                  <td> <input name="submit" type="submit" class="formbtn" value="Save">
                  </td>
                </tr>
        </table>
        <table class="tabcont" width="100%" border="0" cellpadding="0" cellspacing="0">
                <tr>
                  <td nowrap class="listhdrr">Yerel ağ<br>
                    Uzak ağ</td>
                  <td class="listhdrr">Ağ aygıtı<br>Uzak gw</td>
                  <td class="listhdrr">P1 mode</td>
                  <td class="listhdrr">P1 Enc. Algo</td>
                  <td class="listhdrr">P1 Hash Algo</td>
                  <td class="listhdr">Açıklama</td>
                  <td class="list" >
			<table border="0" cellspacing="0" cellpadding="1">
			     <tr>
				<td width="17" heigth="17"></td>
				<td><a href="vpn_ipsec_edit.php"><img src="./themes/<?= $g['theme']; ?>/images/icons/icon_plus.gif" title="tünel ekle" width="17" height="17" border="0"></a></td>
			     </tr>
			</table>
		  </td>
		</tr>
                <?php $i = 0; foreach ($a_ipsec as $ipsecent):
					if (isset($ipsecent['disabled'])) {
						$spans = "<span class=\"gray\">";
						$spane = "</span>";
					} else {
						$spans = $spane = "";
					}
				?>
                <tr valign="top">
                  <td nowrap class="listlr" ondblclick="document.location='vpn_ipsec_edit.php?id=<?=$i;?>'"><?=$spans;?>
                    <?php	if ($ipsecent['local-subnet']['network'])
								echo strtoupper($ipsecent['local-subnet']['network']);
							else
								echo $ipsecent['local-subnet']['address'];
					?>
                    <br>
                    <?=$ipsecent['remote-subnet'];?>
                  <?=$spane;?></td>
                  <td class="listr" ondblclick="document.location='vpn_ipsec_edit.php?id=<?=$i;?>'"><?=$spans;?>
				  <?php if ($ipsecent['interface']) {
							$iflabels = array('lan' => 'LAN', 'wan' => 'WAN');
                 	        $carpips = find_number_of_needed_carp_interfaces();
                         	    for($j=0; $j<$carpips; $j++) {
                       				$carpip = find_interface_ip("carp" . $j);
                      	 			$iflabels['carp' . $j] = "CARP{$j} ({$carpip})"; 
                     		    }
							for ($j = 1; isset($config['interfaces']['opt' . $j]); $j++)
								$iflabels['opt' . $j] = $config['interfaces']['opt' . $j]['descr'];
							$if = htmlspecialchars($iflabels[$ipsecent['interface']]);
						} else
							$if = "WAN";

						echo $if . "<br>" . $ipsecent['remote-gateway'];
					?>
                  <?=$spane;?></td>
                  <td class="listr" ondblclick="document.location='vpn_ipsec_edit.php?id=<?=$i;?>'"><?=$spans;?>
				    <?=$ipsecent['p1']['mode'];?>
                  <?=$spane;?></td>
                  <td class="listr" ondblclick="document.location='vpn_ipsec_edit.php?id=<?=$i;?>'"><?=$spans;?>
				    <?=$p1_ealgos[$ipsecent['p1']['encryption-algorithm']];?>
                  <?=$spane;?></td>
                  <td class="listr" ondblclick="document.location='vpn_ipsec_edit.php?id=<?=$i;?>'"><?=$spans;?>
				    <?=$p1_halgos[$ipsecent['p1']['hash-algorithm']];?>
                  <?=$spane;?></td>
                  <td class="listbg" ondblclick="document.location='vpn_ipsec_edit.php?id=<?=$i;?>'"><?=$spans;?><font color="#FFFFFF">
                    <?=htmlspecialchars($ipsecent['descr']);?>&nbsp;
                  <?=$spane;?></td>
                  <td valign="middle" nowrap class="list">
			<table border="0" cellspacing="0" cellpadding="1">
			     <tr>
				<td><a href="vpn_ipsec_edit.php?id=<?=$i;?>"><img src="./themes/<?= $g['theme']; ?>/images/icons/icon_e.gif" title="edit tunnel" width="17" height="17" border="0"></a></td>
                    		<td><a href="vpn_ipsec.php?act=del&id=<?=$i;?>" onclick="return confirm('Bu tünellemeyi silmek istediğinizden emin misiniz?')"><img src="./themes/<?= $g['theme']; ?>/images/icons/icon_x.gif" title="delete tunnel" width="17" height="17" border="0"></a></td>
			     </tr>
			     <tr>
				<td></td>
				<td><a href="vpn_ipsec_edit.php?dup=<?=$i;?>"><img src="./themes/<?= $g['theme']; ?>/images/icons/icon_plus.gif" title="bunun üzerine yeni bir kural ekle" width="17" height="17" border="0"></a></td>
			     </tr>
			</table>
		  </td>
		</tr>
			  <?php $i++; endforeach; ?>
                <tr>
                  <td class="list" colspan="6"></td>
		  <td class="list">
			<table border="0" cellspacing="0" cellpadding="1">
			     <tr>
				<td width="17"></td>
				<td><a href="vpn_ipsec_edit.php"><img src="./themes/<?= $g['theme']; ?>/images/icons/icon_plus.gif" title="tünel ekle" width="17" height="17" border="0"></a></td>
			     </tr>
			</table>
		  <td>
                </tr>
		    <td colspan="4">
		      <p><span class="vexpl"><span class="red"><strong>Bilgi:<br>
                      </strong></span> IPsec durumunu buradan <a href="diag_ipsec_sad.php">Status:IPsec</a> kontrol edebilirsiniz.</span></p>
		  </td>
		</tr>
              </table>
	      </div>
  	  </td>
	</tr>
</table>
</form>
<?php include("fend.inc"); ?>
</body>
</html>
