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

$ipsec_logfile = "{$g['varlog_path']}/ipsec.log";

/* Create array with all IPSEC tunnel descriptions */
$search = array();
$replace = array();
if(is_array($config['ipsec']['tunnel']))
	foreach($config['ipsec']['tunnel'] as $tunnel) {
		if(!is_ipaddr($tunnel['remote-gateway']))
			$tunnel['remote-gateway'] = resolve_retry($tunnel['remote-gateway']);

		$gateway = "{$tunnel['remote-gateway']}";
		$search[] = "/(racoon: )([A-Z:].*?)({$gateway}\[[0-9].+\]|{$gateway})(.*)/i";
		$replace[] = "$1<strong>[{$tunnel['descr']}]</strong>: $2$3$4";
	}
/* collect all our own ip addresses */
exec("/sbin/ifconfig|/usr/bin/awk '/inet / {print $2}'", $ip_address_list);
foreach($ip_address_list as $address) {
	$search[] = "/(racoon: )([A-Z:].*?)({$address}\[[0-9].+\])(.*isakmp.*)/i";
	$replace[] = "$1<strong>[Self]</strong>: $2$3$4";
}

$nentries = $config['syslog']['nentries'];
if (!$nentries)
	$nentries = 50;

if ($_POST['clear']) {
	exec("killall syslogd");
	exec("/usr/sbin/clog -i -s 262144 {$ipsec_logfile}");
	system_syslogd_start();
}

$ipsec_logarr = return_clog($ipsec_logfile, $nentries);

$pgtitle = "Durum: Sistem Kayıtları: IPSEC VPN";
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
	$tab_array[] = array("Sistem", false, "diag_logs.php");
	$tab_array[] = array("Firewall", false, "diag_logs_filter.php");
	$tab_array[] = array("DHCP", false, "diag_logs_dhcp.php");
	$tab_array[] = array("Hotspot", false, "diag_logs_auth.php");
	$tab_array[] = array("IPsec", true, "diag_logs_ipsec.php");
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
					<td colspan="2" class="listtopic">Last <?=$nentries;?> IPSEC log entries</td>
		  		</tr>
				<?php
				foreach($ipsec_logarr as $logent){
					foreach($search as $string) {
						if(preg_match($string, $logent))
							$match = true;
					}
					if(isset($match)) {
						$logent = preg_replace($search, $replace, $logent);
					} else {
						$searchs = "/(racoon: )([A-Z:].*?)([0-9].+\.[0-9].+.[0-9].+.[0-9].+\[[0-9].+\])(.*)/i";
						$replaces = "$1<strong><font color=red>[Unknown Gateway/Dynamic]</font></strong>: $2$3$4";
						$logent = preg_replace($searchs, $replaces, $logent);
					}
					$logent = preg_split("/\s+/", $logent, 6);
					echo "<tr valign=\"top\">\n";
					$entry_date_time = htmlspecialchars(join(" ", array_slice($logent, 0, 3)));
					echo "<td class=\"listlr\" nowrap>" . $entry_date_time  . "</td>\n";
					echo "<td class=\"listr\">" . $logent[4] . " " . $logent[5] . "</td>\n";
					echo "</tr>\n";
				}
				?>
				<tr>
					<td>
						<br>
						<form action="diag_logs_ipsec.php" method="post">
						<input name="clear" type="submit" class="formbtn" value="Kayıtları Temizle">
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
