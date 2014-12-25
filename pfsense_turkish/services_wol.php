<?php
/* $Id$ */
/*
	services_wol.php
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

if (!is_array($config['wol']['wolentry'])) {
	$config['wol']['wolentry'] = array();
}
wol_sort();
$a_wol = &$config['wol']['wolentry'];

if($_GET['wakeall'] <> "") {
	$i = 0;
	$savemsg = "";
	foreach ($a_wol as $wolent) {
		$mac = $wolent['mac'];
		$if = $wolent['interface'];
		$bcip = gen_subnet_max($config['interfaces'][$if]['ipaddr'],
			$config['interfaces'][$if]['subnet']);
		mwexec("/usr/local/bin/wol -i {$bcip} {$mac}");
		$savemsg .= "Sent magic packet to {$mac}.<br>";
	}
}

if ($_POST || $_GET['mac']) {
	unset($input_errors);

	if ($_GET['mac']) {
        	/* normalize MAC addresses - lowercase and convert Windows-ized hyphenated MACs to colon delimited */
        	$_GET['mac'] = strtolower(str_replace("-", ":", $_GET['mac']));
		$mac = $_GET['mac'];
		$if = $_GET['if'];
	} else {
        	/* normalize MAC addresses - lowercase and convert Windows-ized hyphenated MACs to colon delimited */
        	$_POST['mac'] = strtolower(str_replace("-", ":", $_POST['mac']));
		$mac = $_POST['mac'];
		$if = $_POST['interface'];
	}

	/* input validation */
	if (!$mac || !is_macaddr($mac))
		$input_errors[] = "Geçerli bir MAC adresi tanımlanmalıdır.";
	if (!$if)
		$input_errors[] = "Geçerli bir ağ aygıtı tanımlanmalıdır.";

	if (!$input_errors) {
		/* determine broadcast address */
		$bcip = gen_subnet_max($config['interfaces'][$if]['ipaddr'],
			$config['interfaces'][$if]['subnet']);

		mwexec("/usr/local/bin/wol -i {$bcip} {$mac}");
		$savemsg = "Sent magic packet to {$mac}.";
	}
}

if ($_GET['act'] == "del") {
	if ($a_wol[$_GET['id']]) {
		unset($a_wol[$_GET['id']]);
		write_config();
		header("Location: services_wol.php");
		exit;
	}
}

$pgtitle = "Servisler: Wake on LAN";
include("head.inc");

?>

<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<?php include("fbegin.inc"); ?>
<p class="pgtitle"><?=$pgtitle?></p>
<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>
			<form action="services_wol.php" method="post" name="iform" id="iform">
			  <table width="100%" border="0" cellpadding="6" cellspacing="0">
			  <tr>
                  <td width="22%" valign="top" class="vncellreq">Ağ Aygıtları</td>
                  <td width="78%" class="vtable">
		     <select name="interface" class="formfld">
                      <?php $interfaces = array('lan' => 'LAN');
					  for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++) {
					    if (isset($config['interfaces']['opt' . $i]['enable']) &&
							!$config['interfaces']['opt' . $i]['bridge'])
					  		$interfaces['opt' . $i] = $config['interfaces']['opt' . $i]['descr'];
					  }
					  foreach ($interfaces as $iface => $ifacename): ?>
                      <option value="<?=$iface;?>" <?php if ($iface == $if) echo "selected"; ?>>
                      <?=htmlspecialchars($ifacename);?>
                      </option>
                      <?php endforeach; ?>
                    </select> <br>
                    <span class="vexpl">Choose which interface the host to be woken up is connected to.</span></td>
                </tr>
                <tr>
				  <td width="22%" valign="top" class="vncellreq">MAC adresi</td>
				  <td width="78%" class="vtable">
                      <input name="mac" type="text" class="formfld" id="mac" size="20" value="<?=htmlspecialchars($mac);?>">
                      <br>
                      MAC adresini <span class="vexpl"> şu şekilde giriniz: xx:xx:xx:xx:xx:xx</span></td></tr>
				<tr>
				  <td width="22%" valign="top">&nbsp;</td>
				  <td width="78%">
                    <input name="Submit" type="submit" class="formbtn" value="Gönder">
				</td>
				</tr>
			</table>
			<span class="vexpl"><span class="red"><strong>Bilgi:<br>
            </strong></span>This service can be used to wake up (power on) computers by sending special &quot;Magic Packets&quot;. The NIC in the computer that is to be woken up must support Wake on LAN and has to be configured properly (WOL cable, BIOS settings). </span><br>
                      <br>
                      You may store MAC addresses below for your convenience.
Click the MAC address to wake up a computer. <br>
&nbsp;<br>
You also can wake all clients at once: <a href="services_wol.php?wakeall=true"><img src="./themes/<?= $g['theme']; ?>/images/icons/icon_wol_all.gif" width="17" height="17" border="0"></a><br>
&nbsp;<br>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
                <tr>
                  <td width="15%" class="listhdrr">Ağ Aygıtı</td>
                  <td width="25%" class="listhdrr">MAC adresi</td>
                  <td width="50%" class="listhdr">Açıklama</td>
                  <td width="10%" class="list">
                    <table border="0" cellspacing="0" cellpadding="1">
                      <tr>
			<td valign="middle" width="17"></td>
                        <td valign="middle"><a href="services_wol_edit.php"><img src="./themes/<?= $g['theme']; ?>/images/icons/icon_plus.gif" width="17" height="17" border="0"></a></td>
                      </tr>
                    </table>
		  </td>
		</tr>
			  <?php $i = 0; foreach ($a_wol as $wolent): ?>
                <tr>
                  <td class="listlr" ondblclick="document.location='services_wol_edit.php?id=<?=$i;?>';">
                    <?php if ($wolent['interface'] == "lan")
							   echo "LAN";
						   else
						       echo $config['interfaces'][$wolent['interface']]['descr'];
					?>&nbsp;
                  </td>
                  <td class="listr" ondblclick="document.location='services_wol_edit.php?id=<?=$i;?>';">
                    <a href="?mac=<?=$wolent['mac'];?>&if=<?=$wolent['interface'];?>"><?=strtolower($wolent['mac']);?></a>&nbsp;
                  </td>
                  <td class="listbg" ondblclick="document.location='services_wol_edit.php?id=<?=$i;?>';">
                    <font color="#FFFFFF"><?=htmlspecialchars($wolent['descr']);?>&nbsp;
                  </td>
                  <td valign="middle" nowrap class="list">
                    <table border="0" cellspacing="0" cellpadding="1">
                      <tr>
                        <td valign="middle"><a href="services_wol_edit.php?id=<?=$i;?>"><img src="./themes/<?= $g['theme']; ?>/images/icons/icon_e.gif" width="17" height="17" border="0"></a></td>
                        <td valign="middle"><a href="services_wol.php?act=del&id=<?=$i;?>" onclick="return confirm('Bu kaydı silmek istediğinizden emin misiniz?')"><img src="./themes/<?= $g['theme']; ?>/images/icons/icon_x.gif" width="17" height="17" border="0"></a></td>
                      </tr>
                    </table>
                  </td>
		</tr>
	        <?php $i++; endforeach; ?>
                <tr>
                  <td class="list" colspan="3"></td>
                  <td class="list">
                    <table border="0" cellspacing="0" cellpadding="1">
                      <tr>
			<td valign="middle" width="17"></td>
                        <td valign="middle"><a href="services_wol_edit.php"><img src="./themes/<?= $g['theme']; ?>/images/icons/icon_plus.gif" width="17" height="17" border="0"></a></td>
                      </tr>
                    </table>
                  </td>
                  			
		</tr>
              </table>
</form>
<?php include("fend.inc"); ?>
</body>
</html>
