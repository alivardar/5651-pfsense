<?php
/* $Id$ */
/*
	services_dhcp.php
	part of m0n0wall (http://m0n0.ch/wall)

	Copyright (C) 2003-2004 Justin Ellison <justin@techadvise.com>.
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

function get_wan_dhcp_server() {
	global $config, $g;
	$dhclientfn = $g['vardb_path'] . "/dhclient.leases." . $config['interfaces']['wan']['if'];
	if(file_exists($dhclientfn))
		$leases = file($dhclientfn);
	else 
		$leases = array();
	/* Start at the end, work backwards finding the latest lease for the WAN */
	$dhcpserver = "";
	$iface = "";
	$iface = "";
	for ($i = (count($leases)-1); $i >= 0; $i--) {
		if ($leases[$i] == "}") {
			unset($iface);
			unset($dhcpserver);
		} elseif (strstr($leases[$i],"interface")) {
			preg_match("/\s+interface \"(\w+)\";/",$leases[$i],$iface);
		}  	elseif (strstr($leases[$i],"dhcp-server-identifier")) {
				preg_match("/\s+dhcp-server-identifier (\d+\.\d+\.\d+\.\d+);/",$leases[$i],$dhcpserver);
			}
		if ($iface == $config['interfaces']['wan'] && isset($dhcpserver)) {
			break;
		}
	}
	return $dhcpserver[1];
}


require("guiconfig.inc");

$if = $_GET['if'];
if ($_POST['if'])
	$if = $_POST['if'];

$iflist = array("lan" => "LAN");

for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++) {
	$oc = $config['interfaces']['opt' . $i];

	if (isset($oc['enable']) && $oc['if'] && (!$oc['bridge'])) {
		$iflist['opt' . $i] = $oc['descr'];
	}
}

if (!$if || !isset($iflist[$if]))
	$if = "lan";

$pconfig['enable'] = isset($config['dhcrelay'][$if]['enable']);
$pconfig['server'] = $config['dhcrelay']['server'];
$pconfig['proxydhcp'] = isset($config['dhcrelay']['proxydhcp']);
$pconfig['agentoption'] = isset($config['dhcrelay']['agentoption']);

$ifcfg = $config['interfaces'][$if];

/*   set the enabled flag which will tell us if DHCP server is enabled
 *   on any interface.   We will use this to disable dhcp-relay since
 *   the two are not compatible with each other.
 */
$dhcpd_enabled = false;
if($config['dhcpd']) {
	foreach($config['dhcpd'] as $dhcp) {
		if(isset($dhcp['enable'])) $dhcpd_enabled = true;
	}
}

if ($_POST) {

	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	if ($_POST['enable']) {
		if (isset($_POST['proxydhcp']))
			$_POST['server'] = get_wan_dhcp_server();
		$reqdfields = explode(" ", "server");
		$reqdfieldsn = explode(",", "Hedef Sunucu");

		do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

		if (($_POST['server'] && !is_ipaddr($_POST['server'])))
			$input_errors[] = "Geçerli bir hedef IP adresi tanımlanmalıdır.";

		if (!$input_errors) {
			/* make sure that the DHCP server isn't enabled on this interface */
			if (isset($config['dhcpd'][$if]['enable']))
				$input_errors[] = "You must disable the DHCP server on the {$iflist[$if]} interface before enabling the DHCP Relay.";
			/* make sure that the DHCP server isn't running on any of the implied interfaces */
			foreach ($config['interfaces'] as $ifname => $ifcfg) {
				$subnet = $ifcfg['ipaddr'] . "/" . $ifcfg['subnet'];
				if (ip_in_subnet($_POST['server'],$subnet))
					$destif = $ifname;
			}
			if (!isset($destif))
				$destif = "wan";
			if (isset($config['dhcpd'][$destif]['enable']))
				$input_errors[] = "You must disable the DHCP server on the {$destif} interface before enabling the DHCP Relay.";

			/* if proxydhcp is selected, make sure DHCP is enabled on WAN */
			if (isset($config['dhcrelay']['proxydhcp']) && $config['interfaces']['wan']['ipaddr'] != "dhcp")
				$input_errors[] = "You must have DHCP active on the WAN interface before enabling the DHCP proxy option.";
		}
	}

	if (!$input_errors) {
		$config['dhcrelay']['agentoption'] = $_POST['agentoption'] ? true : false;
		$config['dhcrelay']['proxydhcp'] = $_POST['proxydhcp'] ? true : false;
		$config['dhcrelay']['server'] = $_POST['server'];
		$config['dhcrelay'][$if]['enable'] = $_POST['enable'] ? true : false;

		write_config();

		$retval = 0;
		config_lock();
		$retval = services_dhcrelay_configure();
		config_unlock();
		$savemsg = get_std_save_message($retval);

	}
}

$pgtitle = "Services: DHCP Relay";
include("head.inc");

?>

<script language="JavaScript">
<!--
function enable_change(enable_over) {
	if (document.iform.enable.checked || enable_over) {
		document.iform.server.disabled = 0;
		document.iform.agentoption.disabled = 0;
		document.iform.proxydhcp.disabled = 0;
	} else {
		document.iform.server.disabled = 1;
		document.iform.agentoption.disabled = 1;
		document.iform.proxydhcp.disabled = 1;
	}
	if (document.iform.proxydhcp.checked) {
		document.iform.server.disabled = 1;
	}
}
//-->
</script>


<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<?php include("fbegin.inc"); ?>
<p class="pgtitle"><?=$pgtitle?></p>
<form action="services_dhcp_relay.php" method="post" name="iform" id="iform">
<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>
<?php 
	if ($dhcpd_enabled) {
		echo "DHCP Sunucu zaten etkindir. DHCP sunucu aktifken DHCP relay servisi çalıştırılamaz.";
		include("fend.inc"); 
		echo "</body>";
		echo "</html>";
		exit;
	}
?>
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
		$tab_array[] = array($ifname, $active, "services_dhcp_relay.php?if={$ifent}");
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
                          <strong>Enable DHCP relay on
                          <?=htmlspecialchars($iflist[$if]);?>
                          interface</strong></td>
                      </tr>
			<tr>
	              <td width="22%" valign="top" class="vtable">&nbsp;</td>
                      <td width="78%" class="vtable">
<input name="agentoption" type="checkbox" value="yes" <?php if ($pconfig['agentoption']) echo "checked"; ?>>
                      <strong>Append circuit ID and agent ID to requests</strong><br>
                      If this is checked, the DHCP relay will append the circuit ID (<?=$g['product_name']?> interface number) and the agent ID to the DHCP request.</td>
        		  </tr>
                      <tr>
                        <td width="22%" valign="top" class="vncell">Hedef Sunucu</td>
                        <td width="78%" class="vtable">
			<input name="proxydhcp" type="checkbox" value="yes" <?php if ($pconfig['proxydhcp']) echo "checked"; ?> onClick="enable_change(false)">  Proxy requests to DHCP server on WAN subnet
                          <br><br><input name="server" type="text" class="formfld" id="server" size="20" value="<?=htmlspecialchars($pconfig['server']);?>">
                          <br>
			  This is the IP address of the server to which the DHCP packet is relayed.  Select "Proxy requests to DHCP server on WAN subnet" to relay DHCP packets to the server that was used on the WAN interface.
                        </td>
                      </tr>
                      <tr>
                        <td width="22%" valign="top">&nbsp;</td>
                        <td width="78%">
                          <input name="if" type="hidden" value="<?=$if;?>">
                          <input name="Submit" type="submit" class="formbtn" value="Kaydet" onclick="enable_change(true)">
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
