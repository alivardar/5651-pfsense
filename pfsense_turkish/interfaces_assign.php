<?php 
/*
	interfaces_assign.php
	part of m0n0wall (http://m0n0.ch/wall)
	Written by Jim McBeath based on existing m0n0wall files
	
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

$pgtitle = "Ağ Aygıtları: Ağ Aygıtı Atama";
require("guiconfig.inc");

/*
	In this file, "port" refers to the physical port name,
	while "interface" refers to LAN, WAN, or OPTn.
*/

/* get list without VLAN interfaces */
$portlist = get_interface_list();

/* add VLAN interfaces */
if (is_array($config['vlans']['vlan']) && count($config['vlans']['vlan'])) {
	$i = 0;
	foreach ($config['vlans']['vlan'] as $vlan) {
		$portlist['vlan' . $i] = $vlan;
		$portlist['vlan' . $i]['isvlan'] = true;
		$i++;
	}
}

if ($_POST) {

	unset($input_errors);

	/* input validation */

	/* Build a list of the port names so we can see how the interfaces map */
	$portifmap = array();
	foreach ($portlist as $portname => $portinfo)
		$portifmap[$portname] = array();

	/* Go through the list of ports selected by the user,
	   build a list of port-to-interface mappings in portifmap */
	foreach ($_POST as $ifname => $ifport) {
		if (($ifname == 'lan') || ($ifname == 'wan') || (substr($ifname, 0, 3) == 'opt'))
			$portifmap[$ifport][] = strtoupper($ifname);
	}

	/* Deliver error message for any port with more than one assignment */
	foreach ($portifmap as $portname => $ifnames) {
		if (count($ifnames) > 1) {
			$errstr = "Port " . $portname .
				" atandı " . count($ifnames) .
				" arayüz:";
				
			foreach ($portifmap[$portname] as $ifn)
				$errstr .= " " . $ifn;
			
			$input_errors[] = $errstr;
		}
	}


	if (!$input_errors) {
		/* No errors detected, so update the config */
		foreach ($_POST as $ifname => $ifport) {
		
			if (($ifname == 'lan') || ($ifname == 'wan') ||
				(substr($ifname, 0, 3) == 'opt')) {
				
				if (!is_array($ifport)) {
					$config['interfaces'][$ifname]['if'] = $ifport;
					
					/* check for wireless interfaces, set or clear ['wireless'] */
					if (preg_match($g['wireless_regex'], $ifport)) {
						if (!is_array($config['interfaces'][$ifname]['wireless']))
							$config['interfaces'][$ifname]['wireless'] = array();
					} else {
						unset($config['interfaces'][$ifname]['wireless']);
					}
					
					/* make sure there is a name for OPTn */
					if (substr($ifname, 0, 3) == 'opt') {
						if (!isset($config['interfaces'][$ifname]['descr']))
							$config['interfaces'][$ifname]['descr'] = strtoupper($ifname);
					}
				}
			}
		}
	
		$savemsg = get_std_save_message($retval);
	
		write_config();
		
	}
}

if ($_GET['act'] == "del") {
	$id = $_GET['id'];

	$i = substr($id, 3); /* the number of the OPTn port being deleted */
	unset($config['interfaces'][$id]['enable']);
	interfaces_optional_configure_if($i);   /* down the interface */
		
	unset($config['interfaces'][$id]);	/* delete the specified OPTn */

	write_config();
	
	/*   move all the interfaces up.  for example:
	 *      opt1 --> opt1
	 *	opt2 --> delete
	 *	opt3 --> opt2
         *      opt4 --> opt3
         */
	cleanup_opt_interfaces_after_removal($i);
	
	parse_config(true);
	
	$savemsg = "Ağ aygıtı silindi.";

}

if ($_GET['act'] == "add") {
	/* find next free optional interface number */
	$i = 1;
	while (is_array($config['interfaces']['opt' . $i]))
		$i++;
	
	$newifname = 'opt' . $i;
	$config['interfaces'][$newifname] = array();
	$config['interfaces'][$newifname]['descr'] = "OPT" . $i;
	
	/* Find an unused port for this interface */
	foreach ($portlist as $portname => $portinfo) {
		$portused = false;
		foreach ($config['interfaces'] as $ifname => $ifdata) {
			if ($ifdata['if'] == $portname) {
				$portused = true;
				break;
			}
		}
		if (!$portused) {
			$config['interfaces'][$newifname]['if'] = $portname;
			if (preg_match($g['wireless_regex'], $portname))
				$config['interfaces'][$newifname]['wireless'] = array();
			break;
		}
	}
	
	write_config();

	$savemsg = "Ağ aygıtı eklendi.";

}

$pgtitle = "Ağ Aygıtı: Atama";
include("head.inc");

if(file_exists("/var/run/interface_mismatch_reboot_needed")) 
	if ($_POST) 
		$savemsg = "Firewall yeniden başlatılıyor.";
	else
		$savemsg = "Ağ aygıtında uyumsuzluk tespit edildi.  Lütfen uyumsuzluğu tespit ediniz ve Kaydeti tıklayınız.  Firewall daha sonra yeniden başlatılacaktır.";

?>

<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<?php include("fbegin.inc"); ?>
<p class="pgtitle"><?=$pgtitle?></p>
<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>
<form action="interfaces_assign.php" method="post" name="iform" id="iform">
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr><td class="tabnavtbl">
<?php
	$tab_array = array();
	$tab_array[0] = array("Ağ Aygıtı Eşleştirmeleri", true, "interfaces_assign.php");
	$tab_array[1] = array("VLAN Listesi", false, "interfaces_vlan.php");
	display_top_tabs($tab_array);
?>  
  </td></tr>
  <tr> 
    <td>
	<div id="mainarea">
        <table class="tabcont" width="100%" border="0" cellpadding="0" cellspacing="0">
       <tr> 
	<td class="listhdrr">Ağ aygıtı</td>
	<td class="listhdr">Ağ portu</td>
	<td class="list">&nbsp;</td>
  </tr>
  <?php foreach ($config['interfaces'] as $ifname => $iface):
  	if ($iface['descr'])
		$ifdescr = $iface['descr'];
	else
		$ifdescr = strtoupper($ifname);
	?>
  <tr> 
	<td class="listlr" valign="middle"><strong><?=$ifdescr;?></strong></td>
	  <td valign="middle" class="listr">
		<select name="<?=$ifname;?>" class="formfld" id="<?=$ifname;?>">
		  <?php foreach ($portlist as $portname => $portinfo): ?>
		  <option value="<?=$portname;?>" <?php if ($portname == $iface['if']) echo "selected";?>> 
		  <?php if ($portinfo['isvlan']) {
		  			$descr = "VLAN {$portinfo['tag']} on {$portinfo['if']}";
					if ($portinfo['descr'])
						$descr .= " (" . $portinfo['descr'] . ")";
					echo htmlspecialchars($descr);
				  } else
					echo htmlspecialchars($portname . " (" . $portinfo['mac'] . ")");
		  ?>
		  </option>
		  <?php endforeach; ?>
		</select>
		</td>
		<td valign="middle" class="list"> 
		  <?php if (($ifname != 'lan') && ($ifname != 'wan')): ?>
		  <a href="interfaces_assign.php?act=del&id=<?=$ifname;?>"><img src="./themes/<?= $g['theme']; ?>/images/icons/icon_x.gif" title="Ağ Aygıtı Sil" width="17" height="17" border="0"></a> 
		  <?php endif; ?>
		</td>
  </tr>
  <?php endforeach; ?>
  <?php if (count($config['interfaces']) < count($portlist)): ?>
  <tr>
	<td class="list" colspan="2"></td>
	<td class="list" nowrap>
	<a href="interfaces_assign.php?act=add"><img src="./themes/<?= $g['theme']; ?>/images/icons/icon_plus.gif" title="Ağ Aygıtı Ekle" width="17" height="17" border="0"></a>
	</td>
  </tr>
  <?php else: ?>
  <tr>
	<td class="list" colspan="3" height="10"></td>
  </tr>
  <?php endif; ?>
</table>
</div>
<br>
<input name="Submit" type="submit" class="formbtn" value="Kaydet"><br><br>
<p>
</p>
<ul>
  <li><span class="vexpl">Bilgisayarınızın IP adresini değiştirdikten sonra</span></li>
  <li><span class="vexpl">DHCP den alınan IP leri yenilemeyi unutmayınız.</span></li>
  <li><span class="vexpl">Web Kontrol Arayüzüne yeni IP adresinden erişin.</span></li>
</ul></td>
	</tr>
</table>
</form>
<?php include("fend.inc"); ?>
</body>
</html>

<?php

	if ($_POST) {
		if (!$input_errors)
			touch("/tmp/reload_interfaces");
		if(file_exists("/var/run/interface_mismatch_reboot_needed")) 
			exec("/etc/rc.reboot");
	}
	
?>
