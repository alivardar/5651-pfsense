<?php
/* $Id$ */
/*
	diag_logs.php
	Copyright (C) 2004 Scott Ullrich
	All rights reserved.

	originally part of m0n0wall (http://m0n0.ch/wall)
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

$system_logfile = "{$g['varlog_path']}/system.log";

$nentries = $config['syslog']['nentries'];
if (!$nentries)
	$nentries = 50;

if ($_POST['clear']) {
	exec("killall syslogd");
	exec("/usr/sbin/clog -i -s 262144 {$system_logfile}");
	system_syslogd_start();
}

if ($_GET['filtertext'])
	$filtertext = $_GET['filtertext'];

if ($_POST['filtertext'])
	$filtertext = $_POST['filtertext'];

if ($filtertext)
	$filtertextmeta="?filtertext=$filtertext";

$pgtitle = "Durum: Sistem Kayıtları: Sistem";
include("head.inc");

?>

<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<?php include("fbegin.inc"); ?>
<p class="pgtitle"><?=$pgtitle?></p>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td>
<?php
	$tab_array = array();
	$tab_array[] = array("Sistem", true, "diag_logs.php");
	$tab_array[] = array("Firewall", false, "diag_logs_filter.php");
	$tab_array[] = array("DHCP", false, "diag_logs_dhcp.php");
	$tab_array[] = array("Hotspot", false, "diag_logs_auth.php");
	$tab_array[] = array("IPsec", false, "diag_logs_ipsec.php");
	$tab_array[] = array("PPTP", false, "diag_logs_vpn.php");
	$tab_array[] = array("Yük Dengeleyici", false, "diag_logs_slbd.php");
	$tab_array[] = array("OpenVPN", false, "diag_logs_openvpn.php");
	$tab_array[] = array("OpenNTPD", false, "diag_logs_ntpd.php");
	$tab_array[] = array("Ayarlar", false, "diag_logs_settings.php");
	display_top_tabs($tab_array);
?>
		</td>
	</tr>
	<tr>
		<td>
			<div id="mainarea">
			<table class="tabcont" width="100%" border="0" cellspacing="0" cellpadding="0">
				<tr>
					<td colspan="2" class="listtopic">Son <?=$nentries;?> sistem kayıtları</td>
				</tr>
				<?php
					if($filtertext)
						dump_clog($system_logfile, $nentries, true, array("$filtertext"), array("racoon", "ntpd", "pppoe"));
					else
						dump_clog($system_logfile, $nentries, true, array(), array("racoon", "ntpd", "pppoe"));
				?>
				<tr>
					<td align="left" valign="top">
						<form id="filterform" name="filterform" action="diag_logs.php" method="post" style="margin-top: 14px;">
              				<input id="submit" name="clear" type="submit" class="formbtn" value="<?=gettext("Kayıtları temizle");?>" />
						</form>
					</td>
					<td align="right" valign="top" >
						<form id="clearform" name="clearform" action="diag_logs.php" method="post" style="margin-top: 14px;">
              				<input id="filtertext" name="filtertext" value="<?=gettext($filtertext);?>" />
              				<input id="filtersubmit" name="filtersubmit" type="submit" class="formbtn" value="<?=gettext("Filtrele");?>" />
						</form>
					</td>
				</tr>
			</table>
	    	</div>
		</td>
	</tr>
</table>
<?php include("fend.inc"); ?>
</body>
</html>
