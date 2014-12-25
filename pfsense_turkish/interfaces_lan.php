<?php
/* $Id$ */
/*
	interfaces_lan.php
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

$lancfg = &$config['interfaces']['lan'];
$optcfg = &$config['interfaces']['lan'];

$pconfig['ipaddr'] = $lancfg['ipaddr'];
$pconfig['subnet'] = $lancfg['subnet'];
$pconfig['bridge'] = $lancfg['bridge'];

$pconfig['disableftpproxy'] = isset($lancfg['disableftpproxy']);

/* Wireless interface? */
if (isset($lancfg['wireless'])) {
	require("interfaces_wlan.inc");
	wireless_config_init();
}

if ($_POST) {

	if ($_POST['bridge']) {
		/* double bridging? */
		for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++) {
			if ($i != $index) {
				if ($config['interfaces']['opt' . $i]['bridge'] == $_POST['bridge']) {
					//$input_errors[] = "Optional interface {$i} " .
					//	"({$config['interfaces']['opt' . $i]['descr']}) is already bridged to " .
					//	"the specified interface.";
				} else if ($config['interfaces']['opt' . $i]['bridge'] == "opt{$index}") {
					//$input_errors[] = "Optional interface {$i} " .
					//	"({$config['interfaces']['opt' . $i]['descr']}) is already bridged to " .
					//	"this interface.";
				}
			}
		}
		if ($config['interfaces'][$_POST['bridge']]['bridge']) {
			//$input_errors[] = "The specified interface is already bridged to " .
			//	"another interface.";
		}
		/* captive portal on? */
		if (isset($config['captiveportal']['enable'])) {
			//$input_errors[] = "Interfaces cannot be bridged while the captive portal is enabled.";
		}
	}

	unset($input_errors);
	$pconfig = $_POST;
	$changedesc = "LAN Interface: ";

	/* input validation */
	$reqdfields = explode(" ", "ipaddr subnet");
	$reqdfieldsn = explode(",", "IP address,Subnet bit count");
	
	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

	if (($_POST['ipaddr'] && !is_ipaddr($_POST['ipaddr']))) {
		$input_errors[] = "Geçerli bir IP adresi tanımlanmalıdır.";
	}
	if (($_POST['subnet'] && !is_numeric($_POST['subnet']))) {
		$input_errors[] = "Geçerli bir subnet bit count tanımlanmalıdır.";
	}

	/* Wireless interface? */
	if (isset($lancfg['wireless'])) {
		$wi_input_errors = wireless_config_post();
		if ($wi_input_errors) {
			$input_errors = array_merge($input_errors, $wi_input_errors);
		}
	}

	if (!$input_errors) {
		
		unset($lancfg['disableftpproxy']);
		
		/* per interface pftpx helper */
		if($_POST['disableftpproxy'] == "yes") {
			$lancfg['disableftpproxy'] = true;
			system_start_ftp_helpers();
		} else {			
			system_start_ftp_helpers();
		}			
		
		$bridge = discover_bridge($lancfg['if'], filter_translate_type_to_real_interface($lancfg['bridge']));
		if($bridge <> "-1") {
			destroy_bridge($bridge);
			setup_bridge();
		}

		$lancfg['bridge'] = $_POST['bridge'];
		
		if (($lancfg['ipaddr'] != $_POST['ipaddr']) || ($lancfg['subnet'] != $_POST['subnet'])) {
			update_if_changed("IP Address", &$lancfg['ipaddr'], $_POST['ipaddr']);
			update_if_changed("subnet", &$lancfg['subnet'], $_POST['subnet']);
		}

		write_config($changedesc);

		touch($d_landirty_path);

		/* restart snmp so that it binds to correct address */
		services_snmpd_configure();

		if ($_POST['apply'] <> "") {
			
			unlink($d_landirty_path);
			
			$savemsg = "Değişiklikler uygulandı.  İnternet gezgininizde yeni IP adresini yazmanız gerekebilir.";
			
		}
	}
}

$pgtitle = "Ağ aygıtları: LAN";
include("head.inc");

?>

<script language="JavaScript">
<!--
function enable_change(enable_over) {
	return;
	var endis;
	endis = !((document.iform.bridge.selectedIndex == 0) || enable_over);
	document.iform.ipaddr.disabled = endis;
	document.iform.subnet.disabled = endis;
}
// -->
</script>


<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<form action="interfaces_lan.php" method="post" name="iform" id="iform">
<?php include("fbegin.inc"); ?>
<p class="pgtitle"><?=$pgtitle?></p>
<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if (file_exists($d_landirty_path)): ?><p>
<?php print_info_box_np("LAN ayarları değiştirildi.<p>Değişiklikler uygulandıktan sonra etkinleşecektir.<p>Değişiklikleri uygulamadan önce DHCP sunucu ayarlarını unutmayınız.");?><br>
<?php endif; ?>
<?php if ($savemsg) print_info_box_np($savemsg); ?>
              <table width="100%" border="0" cellpadding="6" cellspacing="0">
		<tr>
                  <td colspan="2" valign="top" class="listtopic">IP ayarları</td>
		</tr>	      
		<tr>
                  <td width="22%" valign="top" class="vncellreq">Bridge </td>
                  <td width="78%" class="vtable">
			<select name="bridge" class="formfld" id="bridge" onChange="enable_change(false)">
				  	<option <?php if (!$pconfig['bridge']) echo "selected";?> value="">none</option>
                      <?php $opts = array('wan' => "WAN");
					  	for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++) {
							if ($i != $index)
								$opts['opt' . $i] = "Optional " . $i . " (" .
									$config['interfaces']['opt' . $i]['descr'] . ")";
						}
					foreach ($opts as $opt => $optname): ?>
                      <option <?php if ($opt == $pconfig['bridge']) echo "selected";?> value="<?=htmlspecialchars($opt);?>">
                      <?=htmlspecialchars($optname);?>
                      </option>
                      <?php endforeach; ?>
                    </select> </td>
		</tr>	      
                <tr>
                  <td width="22%" valign="top" class="vncellreq">IP adresi</td>
                  <td width="78%" class="vtable">
                    <input name="ipaddr" type="text" class="formfld" id="hostname" size="20" value="<?=htmlspecialchars($pconfig['ipaddr']);?>">
                    /
                    <select name="subnet" class="formfld" id="subnet">
					<?php
					for ($i = 32; $i > 0; $i--) {
						if($i <> 31) {
							echo "<option value=\"{$i}\" ";
							if ($i == $pconfig['subnet']) echo "selected";
							echo ">" . $i . "</option>";
						}
					}
					?>
                    </select></td>
                </tr>
                <tr>
                  <td colspan="2" valign="top" height="16"></td>
                </tr>
                <tr>
                  <td colspan="2" valign="top" class="listtopic">FTP Helper</td>
                </tr>		
		<tr>
			<td width="22%" valign="top" class="vncell">FTP Helper</td>
			<td width="78%" class="vtable">
				<input name="disableftpproxy" type="checkbox" id="disableftpproxy" value="yes" <?php if ($pconfig['disableftpproxy']) echo "checked"; ?> onclick="enable_change(false)" />
				<strong>FTP-Proxy uygulamasını devre dışı bırakın</strong>
				<br />
			</td>
		</tr>			
				<?php /* Wireless interface? */
				if (isset($lancfg['wireless']))
					wireless_config_print();
				?>


                <tr>
                  <td colspan="2" valign="top" height="16"></td>
                </tr>
                <tr>
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%">
                    <input name="Submit" type="submit" class="formbtn" value="Kaydet">
                  </td>
                </tr>
                <tr>
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%"><span class="vexpl"><span class="red"><strong>Uyarı:<br>
                    </strong></span> &quot;Kaydet&quot; tıklandıktan sonra firewall kutunuza erişim için aşağıda yazılanların uygulanması gerekebilir.
                    <ul>
                      <li>Bilgisayarınızın IP adresini yeni IP değerlerine göre değiştirin</li>
                      <li>DHCP den yenileme yapın</li>
                      <li>Yeni IP değerleri ile Web Kontrol Arayüzüne erişin</li>
		      <li>Erişim için <a href="firewall_rules.php">firewall kurallarından </a> emin olunuz.</li>
		      
                    </ul>
                    </span></td>
                </tr>
              </table>
</form>
<?php include("fend.inc"); ?>
</body>
</html>

<?php

if ($_POST['apply'] <> "") {

	ob_flush();
	flush();
	
	interfaces_lan_configure();
	
	reset_carp();
	
	/* sync filter configuration */
	filter_configure();

	/* set up static routes */
	system_routing_configure();
	
	if(file_exists($d_landirty_path))
		unlink($d_landirty_path);
	
}

?>