<?php
/* $Id$ */
/*
	services_dhcp.php
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

/*  Fix failover DHCP problem 
 *  http://article.gmane.org/gmane.comp.security.firewalls.pfsense.support/18749
 */
ini_set("memory_limit","64M");

/* This function will remove entries from dhcpd.leases that would otherwise
 * overlap with static DHCP reservations. If we don't clean these out,
 * then DHCP will print a warning in the logs about a duplicate lease
 */
function dhcp_clean_leases() {
	global $g, $config;
	$leasesfile = "{$g['dhcpd_chroot_path']}/var/db/dhcpd.leases";
	if (!file_exists($leasesfile))
		return;
	/* Build list of static MACs */
	$staticmacs = array();
	foreach($config['interfaces'] as $ifname => $ifarr)
		if (is_array($config['dhcpd'][$ifname]['staticmap']))
			foreach($config['dhcpd'][$ifname]['staticmap'] as $static)
				$staticmacs[] = $static['mac'];
	/* Read existing leases */
	$leases_contents = explode("\n", file_get_contents($leasesfile));
	$newleases_contents = array();
	$i=0;
	while ($i < count($leases_contents)) {
		/* Find a lease definition */
		if (substr($leases_contents[$i], 0, 6) == "lease ") {
			$templease = array();
			$thismac = "";
			/* Read to the end of the lease declaration */
			do {
				if (substr($leases_contents[$i], 0, 20) == "  hardware ethernet ")
					$thismac = substr($leases_contents[$i], 20, 17);
				$templease[] = $leases_contents[$i];
				$i++;
			} while ($leases_contents[$i-1] != "}");
			/* Check for a matching MAC address and if not present, keep it. */
			if (! in_array($thismac, $staticmacs))
				$newleases_contents = array_merge($newleases_contents, $templease);
		} else {
			/* It's a line we want to keep, copy it over. */
			$newleases_contents[] = $leases_contents[$i];
			$i++;
		}
	}
	/* Write out the new leases file */
	$fd = fopen($leasesfile, 'w');
	fwrite($fd, implode("\n", $newleases_contents));
	fclose($fd);
}

$if = $_GET['if'];
if ($_POST['if'])
	$if = $_POST['if'];

/* if OLSRD is enabled, allow WAN to house DHCP. */
if($config['installedpackages']['olsrd']) {
	foreach($config['installedpackages']['olsrd']['config'] as $olsrd) {
			if($olsrd['enable']) {
				$iflist = array("lan" => "LAN", "wan" => "WAN");
				$is_olsr_enabled = true;
				break;
			}
	}
}

if(!$iflist)
	$iflist = array("lan" => "LAN");

for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++) {
	$oc = $config['interfaces']['opt' . $i];

	if (isset($oc['enable']) && $oc['if'] && (!$oc['bridge'])) {
		$iflist['opt' . $i] = $oc['descr'];
	}
}

if (!$if || !isset($iflist[$if]))
	$if = "lan";

$pconfig['range_from'] = $config['dhcpd'][$if]['range']['from'];
$pconfig['range_to'] = $config['dhcpd'][$if]['range']['to'];
$pconfig['deftime'] = $config['dhcpd'][$if]['defaultleasetime'];
$pconfig['maxtime'] = $config['dhcpd'][$if]['maxleasetime'];
$pconfig['gateway'] = $config['dhcpd'][$if]['gateway'];
list($pconfig['wins1'],$pconfig['wins2']) = $config['dhcpd'][$if]['winsserver'];
list($pconfig['dns1'],$pconfig['dns2']) = $config['dhcpd'][$if]['dnsserver'];
$pconfig['enable'] = isset($config['dhcpd'][$if]['enable']);
$pconfig['denyunknown'] = isset($config['dhcpd'][$if]['denyunknown']);
$pconfig['staticarp'] = isset($config['dhcpd'][$if]['staticarp']);
$pconfig['ddnsdomain'] = $config['dhcpd'][$if]['ddnsdomain'];
$pconfig['ddnsupdate'] = isset($config['dhcpd'][$if]['ddnsupdate']);
list($pconfig['ntp1'],$pconfig['ntp2']) = $config['dhcpd'][$if]['ntpserver'];
$pconfig['netboot'] = isset($config['dhcpd'][$if]['netboot']);
$pconfig['nextserver'] = $config['dhcpd'][$if]['next-server'];
$pconfig['filename'] = $config['dhcpd'][$if]['filename'];
$pconfig['failover_peerip'] = $config['dhcpd'][$if]['failover_peerip'];
$pconfig['netmask'] = $config['dhcpd'][$if]['netmask'];

$ifcfg = $config['interfaces'][$if];

/*   set the enabled flag which will tell us if DHCP relay is enabled
 *   on any interface.   We will use this to disable DHCP server since
 *   the two are not compatible with each other.
 */

$dhcrelay_enabled = false;
$dhcrelaycfg = $config['dhcrelay'];

if(is_array($dhcrelaycfg)) {
	foreach ($dhcrelaycfg as $dhcrelayif => $dhcrelayifconf) {
		if (isset($dhcrelayifconf['enable']) &&
			(($dhcrelayif == "lan") ||
			(isset($config['interfaces'][$dhcrelayif]['enable']) &&
			$config['interfaces'][$dhcrelayif]['if'] && (!$config['interfaces'][$dhcrelayif]['bridge']))))
			$dhcrelay_enabled = true;
	}
}


if (!is_array($config['dhcpd'][$if]['staticmap'])) {
	$config['dhcpd'][$if]['staticmap'] = array();
}
staticmaps_sort($if);
$a_maps = &$config['dhcpd'][$if]['staticmap'];

function is_inrange($test, $start, $end) {
	if ( (ip2long($test) < ip2long($end)) && (ip2long($test) > ip2long($start)) )
		return true;
	else
		return false;
}

if ($_POST) {

	unset($input_errors);

	$pconfig = $_POST;

	/* input validation */
	if ($_POST['enable']) {
		$reqdfields = explode(" ", "range_from range_to");
		$reqdfieldsn = explode(",", "Range begin,Range end");

		do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

		foreach($a_maps as $mapent) {
			if(is_inrange($mapent['ipaddr'], $_POST['range_from'], $_POST['range_to'])) {
				$input_errors[] = "{$mapent['ipaddr']} is inside the range you specified.";
			}

		}

		if (($_POST['range_from'] && !is_ipaddr($_POST['range_from']))) {
			$input_errors[] = "Geçerli bir aralık belirtilmelidir.";
		}
		if (($_POST['range_to'] && !is_ipaddr($_POST['range_to']))) {
			$input_errors[] = "Geçerli bir aralık belirtilmelidir.";
		}
		if (($_POST['gateway'] && !is_ipaddr($_POST['gateway']))) {
			$input_errors[] = "Geçerli bir IP adresi ağ geçidi için belirtilmelidir.";
		}
		if (($_POST['wins1'] && !is_ipaddr($_POST['wins1'])) || ($_POST['wins2'] && !is_ipaddr($_POST['wins2']))) {
			$input_errors[] = "Birincil/ikincil WINS sunucuları için geçerli bir IP adresi tanımlanmalıdır.";
		}
		if (($_POST['dns1'] && !is_ipaddr($_POST['dns1'])) || ($_POST['dns2'] && !is_ipaddr($_POST['dns2']))) {
			$input_errors[] = "Birincil/ikinci DNS sunucuları için geçerli bir IP adresi tanımlanmaldır.";
		}
		if ($_POST['deftime'] && (!is_numeric($_POST['deftime']) || ($_POST['deftime'] < 60))) {
			$input_errors[] = "Varsayılan IP salıverme zamanı 60 saniyeden fazla olmak zorundadır.";
		}
		if ($_POST['maxtime'] && (!is_numeric($_POST['maxtime']) || ($_POST['maxtime'] < 60) || ($_POST['maxtime'] <= $_POST['deftime']))) {
			$input_errors[] = "The maximum lease time must be at least 60 seconds and higher than the default lease time.";
		}
		if (($_POST['ddnsdomain'] && !is_domain($_POST['ddnsdomain']))) {
			$input_errors[] = "A valid domain name must be specified for the dynamic DNS registration.";
		}
		if (($_POST['ntp1'] && !is_ipaddr($_POST['ntp1'])) || ($_POST['ntp2'] && !is_ipaddr($_POST['ntp2']))) {
			$input_errors[] = "Birincil/ikincil NTP sunucuları için geçerli bir IP tanımlanmalıdır.";
		}
		if (($_POST['nextserver'] && !is_ipaddr($_POST['nextserver']))) {
			$input_errors[] = "Ağ boot sunucusu için geçerli bir IP adresi tanımlanmalıdır.";
		}


		if (!$input_errors) {
			/* make sure the range lies within the current subnet */
			$subnet_start = (ip2long($ifcfg['ipaddr']) & gen_subnet_mask_long($ifcfg['subnet']));
			$subnet_end = (ip2long($ifcfg['ipaddr']) | (~gen_subnet_mask_long($ifcfg['subnet'])));

			if ((ip2long($_POST['range_from']) < $subnet_start) || (ip2long($_POST['range_from']) > $subnet_end) ||
			    (ip2long($_POST['range_to']) < $subnet_start) || (ip2long($_POST['range_to']) > $subnet_end)) {
				$input_errors[] = "The specified range lies outside of the current subnet.";
			}

			if (ip2long($_POST['range_from']) > ip2long($_POST['range_to']))
				$input_errors[] = "The range is invalid (first element higher than second element).";

			/* make sure that the DHCP Relay isn't enabled on this interface */
			if (isset($config['dhcrelay'][$if]['enable']))
				$input_errors[] = "You must disable the DHCP relay on the {$iflist[$if]} interface before enabling the DHCP server.";
		}
	}

	if (!$input_errors) {
		$config['dhcpd'][$if]['range']['from'] = $_POST['range_from'];
		$config['dhcpd'][$if]['range']['to'] = $_POST['range_to'];
		$config['dhcpd'][$if]['defaultleasetime'] = $_POST['deftime'];
		$config['dhcpd'][$if]['maxleasetime'] = $_POST['maxtime'];
		$config['dhcpd'][$if]['netmask'] = $_POST['netmask'];
		$previous = $config['dhcpd'][$if]['failover_peerip'];
		if($previous <> $_POST['failover_peerip']) {
			mwexec("rm -rf /var/dhcpd/var/db/*");
		}
		$config['dhcpd'][$if]['failover_peerip'] = $_POST['failover_peerip'];

		unset($config['dhcpd'][$if]['winsserver']);
		if ($_POST['wins1'])
			$config['dhcpd'][$if]['winsserver'][] = $_POST['wins1'];
		if ($_POST['wins2'])
			$config['dhcpd'][$if]['winsserver'][] = $_POST['wins2'];

		unset($config['dhcpd'][$if]['dnsserver']);
		if ($_POST['dns1'])
			$config['dhcpd'][$if]['dnsserver'][] = $_POST['dns1'];
		if ($_POST['dns2'])
			$config['dhcpd'][$if]['dnsserver'][] = $_POST['dns2'];

		$config['dhcpd'][$if]['gateway'] = $_POST['gateway'];
		$config['dhcpd'][$if]['denyunknown'] = ($_POST['denyunknown']) ? true : false;
		$config['dhcpd'][$if]['enable'] = ($_POST['enable']) ? true : false;
		$config['dhcpd'][$if]['staticarp'] = ($_POST['staticarp']) ? true : false;
		$config['dhcpd'][$if]['ddnsdomain'] = $_POST['ddnsdomain'];
		$config['dhcpd'][$if]['ddnsupdate'] = ($_POST['ddnsupdate']) ? true : false;

		unset($config['dhcpd'][$if]['ntpserver']);
		if ($_POST['ntp1'])
			$config['dhcpd'][$if]['ntpserver'][] = $_POST['ntp1'];
		if ($_POST['ntp2'])
			$config['dhcpd'][$if]['ntpserver'][] = $_POST['ntp2'];

		$config['dhcpd'][$if]['netboot'] = ($_POST['netboot']) ? true : false;
		$config['dhcpd'][$if]['next-server'] = $_POST['nextserver'];
		$config['dhcpd'][$if]['filename'] = $_POST['filename'];

		write_config();

		/* static arp configuration */
		interfaces_staticarp_configure($if);

		$retval = 0;
		$retvaldhcp = 0;
		$retvaldns = 0;
		config_lock();
		/* Stop DHCP so we can cleanup leases */
		killbyname("dhcpd");
		dhcp_clean_leases();
		/* dnsmasq_configure calls dhcpd_configure */
		/* no need to restart dhcpd twice */
		if (isset($config['dnsmasq']['regdhcpstatic']))	{
			$retvaldns = services_dnsmasq_configure();
			if ($retvaldns == 0) {
				if (file_exists($d_hostsdirty_path))
					unlink($d_hostsdirty_path);
				if (file_exists($d_staticmapsdirty_path))
					unlink($d_staticmapsdirty_path);
			}					
		} else {
			$retvaldhcp = services_dhcpd_configure();	
			if ($retvaldhcp == 0) {
				if (file_exists($d_staticmapsdirty_path))
					unlink($d_staticmapsdirty_path);
			}
		}	
		config_unlock();
		if($retvaldhcp == 1 || $retvaldns == 1)
			$retval = 1;
		$savemsg = get_std_save_message($retval);
	}
}

if ($_GET['act'] == "del") {
	if ($a_maps[$_GET['id']]) {
		unset($a_maps[$_GET['id']]);
		write_config();
		if(isset($config['dhcpd'][$if]['enable'])) {
			touch($d_staticmapsdirty_path);
			if (isset($config['dnsmasq']['regdhcpstatic']))
				touch($d_hostsdirty_path);
		}
		header("Location: services_dhcp.php?if={$if}");
		exit;
	}
}

$pgtitle = "Servisler: DHCP Sunucu";
include("head.inc");

?>

<script type="text/javascript" language="JavaScript">

function enable_change(enable_over) {
	var endis;
	endis = !(document.iform.enable.checked || enable_over);
	document.iform.range_from.disabled = endis;
	document.iform.range_to.disabled = endis;
	document.iform.wins1.disabled = endis;
	document.iform.wins2.disabled = endis;
	document.iform.dns1.disabled = endis;
	document.iform.dns2.disabled = endis;
	document.iform.deftime.disabled = endis;
	document.iform.maxtime.disabled = endis;
	document.iform.gateway.disabled = endis;
	document.iform.failover_peerip.disabled = endis;
	document.iform.staticarp.disabled = endis;
	document.iform.ddnsdomain.disabled = endis;
	document.iform.ddnsupdate.disabled = endis;
	document.iform.ntp1.disabled = endis;
	document.iform.ntp2.disabled = endis;
	document.iform.netboot.disabled = endis;
	document.iform.nextserver.disabled = endis;
	document.iform.filename.disabled = endis;
	document.iform.denyunknown.disabled = endis;
}

function show_ddns_config() {
	document.getElementById("showddnsbox").innerHTML='';
	aodiv = document.getElementById('showddns');
	aodiv.style.display = "block";
}

function show_ntp_config() {
	document.getElementById("showntpbox").innerHTML='';
	aodiv = document.getElementById('showntp');
	aodiv.style.display = "block";
}

function show_netboot_config() {
	document.getElementById("shownetbootbox").innerHTML='';
	aodiv = document.getElementById('shownetboot');
	aodiv.style.display = "block";
}

</script>

<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<?php include("fbegin.inc"); ?>
<p class="pgtitle"><?=$pgtitle?></p>
<form action="services_dhcp.php" method="post" name="iform" id="iform">
<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>
<?php 
	if ($dhcrelay_enabled) {
		echo "DHCP Relay şuanda çalışmaktadır. DHCP sunucu bu durumda herhangi bir ağ aygıtı üzerinde aktifleştirilemez.";
		include("fend.inc"); 
		echo "</body>";
		echo "</html>";
		exit;
	}
?>
<?php if (file_exists($d_staticmapsdirty_path)): ?><p>
<?php print_info_box_np("The static mapping configuration has been changed.<br>You must apply the changes in order for them to take effect.");?><br>
<?php endif; ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr><td>
  <?php
	/* active tabs */
	$tab_array = array();
	$tabscounter = 0;
	$i = 0;
	foreach ($iflist as $ifent => $ifname) {
		if ($ifent == $if)
			$active = true;
		else
			$active = false;
		$tab_array[] = array($ifname, $active, "services_dhcp.php?if={$ifent}");
	}
	display_top_tabs($tab_array);
  ?>
  </td></tr>
  <tr>
    <td>
	<div id="mainarea">
              <table class="tabcont" width="100%" border="0" cellpadding="6" cellspacing="0">
                      <tr>
                        <td width="22%" valign="top" class="vtable">&nbsp;</td>
                        <td width="78%" class="vtable">
			  <input name="enable" type="checkbox" value="yes" <?php if ($pconfig['enable']) echo "checked"; ?> onClick="enable_change(false)">
                          <strong> 
                          <?=htmlspecialchars($iflist[$if]);?>
                          üzerinde DHCP sunucuyu aktifleştir</strong></td>
                      </tr>
				  <tr>
	              <td width="22%" valign="top" class="vtable">&nbsp;</td>
                      <td width="78%" class="vtable">
			<input name="denyunknown" id="denyunknown" type="checkbox" value="yes" <?php if ($pconfig['denyunknown']) echo "checked"; ?>>
                      <strong>Bilinmeyen istemcileri engelle</strong><br>
					  Eğer bu alan seçilirse, sadece tanımlanmış olan istemcilere IP dağıtılacaktır
                      </td>
		      		  </tr>
                      <tr>
                        <td width="22%" valign="top" class="vncellreq">Alt ağ</td>
                        <td width="78%" class="vtable">
                          <?=gen_subnet($ifcfg['ipaddr'], $ifcfg['subnet']);?>
                        </td>
                      </tr>
                      <tr>
                        <td width="22%" valign="top" class="vncellreq">Alt ağ
                          maskesi</td>
                        <td width="78%" class="vtable">
                          <?=gen_subnet_mask($ifcfg['subnet']);?>
                        </td>
                      </tr>
                      <tr>
                        <td width="22%" valign="top" class="vncellreq">Mevcut aralık</td>
                        <td width="78%" class="vtable">
                          <?=long2ip(ip2long($ifcfg['ipaddr']) & gen_subnet_mask_long($ifcfg['subnet']));?>
                          -
                          <?=long2ip(ip2long($ifcfg['ipaddr']) | (~gen_subnet_mask_long($ifcfg['subnet']))); ?>
                        </td>
                      </tr>
					  <?php if($is_olsr_enabled): ?>
                      <tr>
                        <td width="22%" valign="top" class="vncellreq">Alt ağ maskesi</td>
                        <td width="78%" class="vtable">
	                        <select name="netmask" class="formfld" id="netmask">
							<?php
							for ($i = 32; $i > 0; $i--) {
								if($i <> 31) {
									echo "<option value=\"{$i}\" ";
									if ($i == $pconfig['netmask']) echo "selected";
									echo ">" . $i . "</option>";
								}
							}
							?>
							</select>
                        </td>
                      </tr>
                      <?php endif; ?>
                      <tr>
                        <td width="22%" valign="top" class="vncellreq">Aralık</td>
                        <td width="78%" class="vtable">
                          <input name="range_from" type="text" class="formfld" id="range_from" size="20" value="<?=htmlspecialchars($pconfig['range_from']);?>">
                          &nbsp;to&nbsp; <input name="range_to" type="text" class="formfld" id="range_to" size="20" value="<?=htmlspecialchars($pconfig['range_to']);?>">
			</td>
                      </tr>
                      <tr>
                        <td width="22%" valign="top" class="vncell">WINS Sunucu</td>
                        <td width="78%" class="vtable">
                          <input name="wins1" type="text" class="formfld" id="wins1" size="20" value="<?=htmlspecialchars($pconfig['wins1']);?>"><br>
                          <input name="wins2" type="text" class="formfld" id="wins2" size="20" value="<?=htmlspecialchars($pconfig['wins2']);?>">
			</td>
                      </tr>
                      <tr>
                        <td width="22%" valign="top" class="vncell">DNS Sunucular</td>
                        <td width="78%" class="vtable">
                          <input name="dns1" type="text" class="formfld" id="dns1" size="20" value="<?=htmlspecialchars($pconfig['dns1']);?>"><br>
                          <input name="dns2" type="text" class="formfld" id="dns2" size="20" value="<?=htmlspecialchars($pconfig['dns2']);?>"><br>
			  NOTE: leave blank to use the system default DNS servers - this interface's IP if DNS forwarder is enabled, otherwise the servers configured on the General page.  
			</td>
                      </tr>
                     <tr>
                       <td width="22%" valign="top" class="vncell">Ağ geçidi</td>
                       <td width="78%" class="vtable">
                         <input name="gateway" type="text" class="formfld" id="gateway" size="20" value="<?=htmlspecialchars($pconfig['gateway']);?>"><br>
			 The default is to use the IP on this interface of the firewall as the gateway.  Specify an alternate gateway here if this is not the correct gateway for your network.
			</td>
                     </tr>
                      <tr>
                        <td width="22%" valign="top" class="vncell">Varsayılan salıverme zamanı</td>
                        <td width="78%" class="vtable">
                          <input name="deftime" type="text" class="formfld" id="deftime" size="10" value="<?=htmlspecialchars($pconfig['deftime']);?>">
                          saniye<br>
                          Bu alan zaman aşımı süresi için kullanılır..<br>
						  Varsayılan değer 7200 saniyedir.                          
			</td>
                      </tr>
                      <tr>
                        <td width="22%" valign="top" class="vncell">Maksimum salıverme zamanı</td>
                        <td width="78%" class="vtable">
                          <input name="maxtime" type="text" class="formfld" id="maxtime" size="10" value="<?=htmlspecialchars($pconfig['maxtime']);?>">
                          saniye<br>
                          This is the maximum lease time for clients that ask
                          for a specific expiration time.<br>
                          The default is 86400 seconds.
			</td>
                      </tr>
                      <tr>
                        <td width="22%" valign="top" class="vncell">Failover peer IP:</td>
                        <td width="78%" class="vtable">
				<input name="failover_peerip" type="text" class="formfld" id="failover_peerip" size="20" value="<?=htmlspecialchars($pconfig['failover_peerip']);?>"><br>
				Leave blank to disable.  Enter the REAL address of the other machine.  Machines must be using CARP.
			</td>
		      </tr>
                      <tr>
                        <td width="22%" valign="top" class="vncell">Static ARP</td>
                        <td width="78%" class="vtable">
				<table>
					<tr>
						<td>
							<input valign="middle" type="checkbox" value="yes" name="staticarp" id="staticarp" <?php if($pconfig['staticarp']) echo " checked"; ?>>&nbsp;
						</td>
						<td>
							<b>Static ARP girdilerini etkinleştir</b>
						</td>
					</tr>
					<tr>
						<td>
							&nbsp;
						</td>
						<td>
							<span class="red"><strong>Bilgi:</strong></span> Sadece aşağıda listelenen makineler bu NIC güvenlik duvarı ile iletişim sağlayabileceklerdir.
						</td>
					</tr>
				</table>
			</td>
                      </tr>
                      <tr>
                        <td width="22%" valign="top" class="vncell">Dinamik DNS</td>
                        <td width="78%" class="vtable">
				<div id="showddnsbox">
					<input type="button" onClick="show_ddns_config()" value="Advanced"></input> - Dinamik DNS Göster</a>
				</div>
				<div id="showddns" style="display:none">
					<input valign="middle" type="checkbox" value="yes" name="ddnsupdate" id="ddnsupdate" <?php if($pconfig['ddnsupdate']) echo " checked"; ?>>&nbsp;
					<b>Enable registration of DHCP client names in DNS.</b><br />
					<p>
					<input name="ddnsdomain" type="text" class="formfld" id="ddnsdomain" size="20" value="<?=htmlspecialchars($pconfig['ddnsdomain']);?>"><br />
					Bilgi: Leave blank to disable dynamic DNS registration.<br />
					Enter the dynamic DNS domain which will be used to register client names in the DNS server.
				</div>
			</td>
		      </tr>
                      <tr>
                        <td width="22%" valign="top" class="vncell">NTP sunucular</td>
                        <td width="78%" class="vtable">
				<div id="showntpbox">
					<input type="button" onClick="show_ntp_config()" value="Advanced"></input> - NTP ayarlarını göster</a>
				</div>
				<div id="showntp" style="display:none">
					<input name="ntp1" type="text" class="formfld" id="ntp1" size="20" value="<?=htmlspecialchars($pconfig['ntp1']);?>"><br>
					<input name="ntp2" type="text" class="formfld" id="ntp2" size="20" value="<?=htmlspecialchars($pconfig['ntp2']);?>">
				</div>
			</td>
                      </tr>
                      <tr>
                        <td width="22%" valign="top" class="vncell">Ağ boot etmeyi etkinleştir</td>
                        <td width="78%" class="vtable">
				<div id="shownetbootbox">
					<input type="button" onClick="show_netboot_config()" value="Advanced"></input> - Ağ boot etmeyi göster</a>
				</div>
				<div id="shownetboot" style="display:none">
					<input valign="middle" type="checkbox" value="yes" name="netboot" id="netboot" <?php if($pconfig['netboot']) echo " checked"; ?>>&nbsp;
					<b>Ağ boot etmeyi etkinleştir.</b>
					<p>
					<input name="nextserver" type="text" class="formfld" id="nextserver" size="20" value="<?=htmlspecialchars($pconfig['nextserver']);?>"><br>
					Ağ üzerinden boot edecek sunucunun IP adresini yazınız.					
					<p>
					<input name="filename" type="text" class="formfld" id="filename" size="20" value="<?=htmlspecialchars($pconfig['filename']);?>"><br>
					Ağ boot işlemi için dosya adını yazınız.<br />
					Bilgi: Boot sunucu ayarları için dosyası ve boot sunucu tanımlı olmalıdır!
				</div>
			</td>
		      </tr>
                      <tr>
                        <td width="22%" valign="top">&nbsp;</td>
                        <td width="78%">
                          <input name="if" type="hidden" value="<?=$if;?>">
                          <input name="Submit" type="submit" class="formbtn" value="Kaydet" onclick="enable_change(true)">
                        </td>
                      </tr>
                      <tr>
                        <td width="22%" valign="top">&nbsp;</td>
                        <td width="78%"> <p><span class="vexpl"><span class="red"><strong>Bilgi:<br>
                            </strong></span>The DNS servers entered in <a href="system.php">Sistem:
                            General setup</a> (or the <a href="services_dnsmasq.php">DNS
                            forwarder</a>, if enabled) </span><span class="vexpl">will
                            be assigned to clients by the DHCP server.<br>
                            <br>
                            DHCP salıverme sayfası <a href="diag_dhcp_leases.php">Durum:
                            DHCP salıverme durum </a> sayfası.<br>
                            </span></p>
			</td>
                      </tr>
                    </table>
              <table class="tabcont" width="100%" border="0" cellpadding="0" cellspacing="0">
                <tr>
                  <td width="25%" class="listhdrr">MAC adresi</td>
                  <td width="15%" class="listhdrr">IP adresi</td>
				  <td width="20%" class="listhdrr">Hostname</td>
                  <td width="30%" class="listhdr">Açıklama</td>
                  <td width="10%" class="list">
                    <table border="0" cellspacing="0" cellpadding="1">
                      <tr>
			<td valign="middle" width="17"></td>
                        <td valign="middle"><a href="services_dhcp_edit.php?if=<?=$if;?>"><img src="./themes/<?= $g['theme']; ?>/images/icons/icon_plus.gif" width="17" height="17" border="0"></a></td>
                      </tr>
                    </table>
		  </td>
		</tr>
			  <?php if(is_array($a_maps)): ?>
			  <?php $i = 0; foreach ($a_maps as $mapent): ?>
			  <?php if($mapent['mac'] <> "" or $mapent['ipaddr'] <> ""): ?>
                <tr>
                  <td class="listlr" ondblclick="document.location='services_dhcp_edit.php?if=<?=$if;?>&id=<?=$i;?>';">
                    <?=htmlspecialchars($mapent['mac']);?>
                  </td>
                  <td class="listr" ondblclick="document.location='services_dhcp_edit.php?if=<?=$if;?>&id=<?=$i;?>';">
                    <?=htmlspecialchars($mapent['ipaddr']);?>&nbsp;
                  </td>
                  <td class="listr" ondblclick="document.location='services_dhcp_edit.php?if=<?=$if;?>&id=<?=$i;?>';">
                    <?=htmlspecialchars($mapent['hostname']);?>&nbsp;
                  </td>	
                  <td class="listbg" ondblclick="document.location='services_dhcp_edit.php?if=<?=$if;?>&id=<?=$i;?>';">
                    <font color="#FFFFFF"><?=htmlspecialchars($mapent['descr']);?>&nbsp;</font>
                  </td>
                  <td valign="middle" nowrap class="list">
                    <table border="0" cellspacing="0" cellpadding="1">
                      <tr>
                        <td valign="middle"><a href="services_dhcp_edit.php?if=<?=$if;?>&id=<?=$i;?>"><img src="./themes/<?= $g['theme']; ?>/images/icons/icon_e.gif" width="17" height="17" border="0"></a></td>
                        <td valign="middle"><a href="services_dhcp.php?if=<?=$if;?>&act=del&id=<?=$i;?>" onclick="return confirm('Bu kaydı silmek istediğinizden emin misiniz?')"><img src="./themes/<?= $g['theme']; ?>/images/icons/icon_x.gif" width="17" height="17" border="0"></a></td>
                      </tr>
                    </table>
                  </td>
                </tr>
		<?php endif; ?>
		<?php $i++; endforeach; ?>
		<?php endif; ?>
                <tr>
                  <td class="list" colspan="4"></td>
                  <td class="list">
                    <table border="0" cellspacing="0" cellpadding="1">
                      <tr>
			<td valign="middle" width="17"></td>
                        <td valign="middle"><a href="services_dhcp_edit.php?if=<?=$if;?>"><img src="./themes/<?= $g['theme']; ?>/images/icons/icon_plus.gif" width="17" height="17" border="0"></a></td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>
	</div>
    </td>
  </tr>
</table>
</form>
<script language="JavaScript">
<!--
enable_change(false);
//-->
</script>
<?php include("fend.inc"); ?>
</body>
</html>
