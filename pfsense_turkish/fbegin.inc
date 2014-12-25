<?php

require_once("globals.inc");
require_once("notices.inc");
/* $Id$ */
	function return_ext_menu($section) {
		global $config;
		$htmltext = "";
		if($config['installedpackages']['menu'] <> "") {
			foreach($config['installedpackages']['menu'] as $menuitem) {
				if($menuitem['section'] != $section) continue;
				if($menuitem['url'] <> "") {
				 	$addresswithport = getenv("HTTP_HOST");
					$colonpos = strpos($addresswithport, ":");
					if ($colonpos !== False){
						//my url is actually just the IP address of the pfsense box
						$myurl = substr($addresswithport, 0, $colonpos);
					}
					else
					{
						$myurl = $addresswithport;
					}

					$description = str_replace('$myurl', $myurl, $menuitem['url']);
				} else {
					$description = '/pkg.php?xml=' . $menuitem['configfile'];
				}
				$htmltext .= '<li><a href="' . $description . ' "class="navlnk">' . $menuitem['name'] . '</a></li>' . "\n";
			}
		}
		return $htmltext;
	}




	/* NOTICE ACKNOWLEDGE CODE by Erik Kristensen */
	if ($_REQUEST['noticeaction'] == 'acknowledge') {
		$notice_id = $_REQUEST['noticeid'];
		close_notice($notice_id);
	}
	/**********************************************/
?>

<div id="wrapper">

	<div id="header">
		<div id="header-left"><a href="index.php" id="status-link"><img src="/themes/<?= $g['theme']; ?>/images/transparent.gif" border="0"></img></a></div>
		<div id="header-right">
			<div class="container">
				<div class="left">Ayarlar</div>
				<div class="right">
<?
				if (are_notices_pending()) {
					$notices = get_notices();

					$requests=array();

					## Get Query Arguments from URL ###
					foreach ($_REQUEST as $key => $value) {
						if ($key != "PHPSESSID")
							$requests[] = $key.'='.$value;
					}
					if(is_array($requests))
						$request_string = implode("&", $requests);

					if(is_array($notices)) {
						foreach ($notices as $key => $value) {
							$date = date("m-d-y H:i:s", $key);
							$noticemsg = str_replace("'", "", $value['notice']);
							$noticemsg = str_replace('"', "", $noticemsg);
							$noticemsg = str_replace("\n", "", $noticemsg);
							$noticemsg = str_replace("<p>", "", $noticemsg);
							$noticemsg = str_replace("<pre>", "", $noticemsg);
							$noticemsg = str_replace("</pre>", "", $noticemsg);
							$noticemsg = str_replace("</p>", "", $noticemsg);
							$noticemsg = str_replace("<br>", "", $noticemsg);
							$extra_args = "";
							if($_GET['xml']) 
								$extraargs="&xml=" . $_GET['xml'];
							if($_POST['xml']) 
								$extraargs="&xml=" . $_POST['xml'];
							if($_GET['id']) 
								$extraargs="&xml=" . $_GET['id'];
							if($_POST['id'])
								$extraargs="&xml=" . $_POST['id'];
							$notice_msgs = '<a href="?noticeaction=acknowledge&noticeid=all' . $extraargs . '">Acknowledge All</a> &nbsp;&nbsp;&nbsp;&nbsp;.:.&nbsp;&nbsp;&nbsp;&nbsp; ';
							if ($value['url']) {
								$notice_msgs .= $date.' - <a href="'.$url.'?'.$request_string.'&noticeaction=acknowledge&noticeid='.$key.'">['.$value['id'].']</a>';
							} else {
								$notice_msgs .= $date.' - <a href="?'.$request_string.'&noticeaction=acknowledge&noticeid='.$key.'">['.$value['id'].']'.$noticemsg.'</a>';
							}
							$notice_msgs .= " &nbsp;&nbsp;&nbsp;&nbsp;.:.&nbsp;&nbsp;&nbsp;&nbsp; ";
						}
					}

				} 
?>
					<div id="hostname">
						<? 
						print $config['system']['hostname'] . "." . $config['system']['domain']; 
						print("<br>");					
						?>
						
					</div>

				</div>
			</div>
		</div>
	</div> <!-- Header DIV -->
	<div id="content">
		<div id="left">
			<div id="navigation" style="z-index:1000">  
				<ul id="menu">
					<li class="firstdrop">
						<div class="menubaslik">Sistem</div>
						<ul class="subdrop">
						
							<li><a href="/system.php" class="navlnk">Genel Ayarlar</a></li>
							<li><a href="/system_advanced.php" class="navlnk">Gelişmiş Ayarlar</a></li>
							<li><a href="/system_firmware.php" class="navlnk">Güncelleme</a></li>
							
						<?php 
						if($g['platform'] == "pfSense" or $g['platform'] == "nanobsd"): 
							print'<li><a href="/pkg_mgr.php" class="navlnk">Paket Yönetimi</a></li>';
						endif; 
						?>
						

							<li><a href="/wizard.php?xml=setup_wizard.xml" class="navlnk">Ayar Sihirbazı</a></li>

							<li><a href="/system_routes.php" class="navlnk">Statik Yönlendirme</a></li>
						</ul>
					</li>
					<li class="drop">
						<div class="menubaslik">Ağ Aygıtları</div>
						<ul class="subdrop">
							<?php if (!isset($config['system']['webgui']['noassigninterfaces'])): ?><li><a href="/interfaces_assign.php" class="navlnks">İlişkilendir</a></li><?php endif; ?>
							<li><a href="/interfaces_wan.php" class="navlnk">WAN</a></li>
							<li><a href="/interfaces_lan.php" class="navlnk">LAN</a></li>
							<?php for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++): if (!isset($config['interfaces']['opt' . $i]['ovpn'])): ?>
							<li><a href="/interfaces_opt.php?index=<?=$i;?>" class="navlnk"><?=htmlspecialchars($config['interfaces']['opt' . $i]['descr']);?></a></li>
							<?php endif; endfor; ?>
							<?php echo return_ext_menu("Interfaces"); ?>
						</ul>
					</li>
<?php
        if($config['system']['shapertype'] <> "m0n0")
          $shaper = "firewall_shaper.php";
        else
          $shaper = "m0n0/firewall_shaper.php";
?>

					<li class="drop">
						<div class="menubaslik">Firewall</div>
						<ul class="subdrop">
							<li><a href="/firewall_aliases.php" class="navlnk">Takma İsimler</a></li>
							<li><a href="/firewall_nat.php" class="navlnk">NAT</a></li>
							<li><a href="/firewall_rules.php" class="navlnk">Kurallar</a></li>
							<li><a href="/firewall_schedule.php" class="navlnk">Zamanlayıcı</a></li>
							<li><a href="<?=$shaper?>" class="navlnk">Trafik Sınırlandırma</a></li>
							<li><a href="/firewall_virtual_ip.php" class="navlnk">Sanal IP </a></li>
							<?php echo return_ext_menu("Firewall"); ?>
						</ul>
					</li>
					<li class="drop">
						<div class="menubaslik">Servisler</div>
						<ul class="subdrop">
							<li><a href="/services_captiveportal.php" class="navlnk">HotSpot</a></li>
							<li><a href="/services_dhcp.php" class="navlnk">DHCP Sunucu</a></li>
							<li><a href="/services_dhcp_relay.php" class="navlnk">DHCP Relay</a></li>
							<li><a href="/services_dyndns.php" class="navlnk">Dinamik DNS</a></li>
							<li><a href="/services_dnsmasq.php" class="navlnk">DNS Yönlendirme</a></li>

							<li><a href="/load_balancer_pool.php" class="navlnk">Yük Dengeleyici</a></li>
							<li><a href="/carp_status.php" class="navlnk">CARP (failover)</a></li>
							
							<?php
							//<li><a href="/pkg_edit.php?xml=olsrd.xml&id=0" class="navlnk">OLSR</a></li>
							//<li><a href="/pkg_edit.php?xml=routed/routed.xml&id=0" class="navlnk">RIP</a></li>
							//<li><a href="/pkg_edit.php?xml=miniupnpd.xml&id=0" class="navlnk">UPnP</a></li>
							//<li><a href="/services_wol.php" class="navlnk">Wake on LAN</a></li>
							//<li><a href="/vpn_pppoe.php" class="navlnk">PPPoE Sunucusu</a></li>	
							?>
							
							
							<li><a href="/services_snmp.php" class="navlnk">SNMP</a></li>							
							<li><a href="/pkg_edit.php?xml=openntpd.xml&id=0" class="navlnk">OpenNTPD</a></li>
							
							<?php echo return_ext_menu("Services"); ?>
						</ul>
					</li>
					
					<li class="drop">
						<div class="menubaslik">VPN</div>
						<ul class="subdrop">
							<li><a href="/vpn_ipsec.php" class="navlnk">IPsec</a></li>			
							<li><a href="/vpn_pptp.php" class="navlnk">PPTP</a></li>
							<li><a href="/pkg.php?xml=openvpn.xml" class="navlnk">OpenVPN</a></li>
							<?php echo return_ext_menu("VPN"); ?>
						</ul>
					</li>
					
					
					<li class="drop">
						<div class="menubaslik">Durum</div>
						<ul class="subdrop">
						
							<li><a href="/diag_logs.php" class="navlnk">Sunucu Kayıtları</a></li>
							<li><a href="/diag_dhcp_leases.php" class="navlnk">DHCP Kullanıcıları</a></li>
							<li><a href="/status_services.php" class="navlnk">Servisler</a></li>
							
							<?php if (isset($config['captiveportal']['enable'])): ?>
							<li><a href="/status_captiveportal.php" class="navlnk">Hotspot</a></li>
							<?php endif; ?>
							
							<li><a href="/status_filter_reload.php" class="navlnk">Filtre Durumu</a></li>
							<li><a href="/status_interfaces.php" class="navlnk">Ağ Aygıtları</a></li>
							<li><a href="/diag_ipsec.php" class="navlnk">IPsec</a></li>
							<li><a href="/status_slbd_pool.php" class="navlnk">Yük Dengeleyici</a></li>

							<li><a href="/status_queues.php" class="navlnk">Kuyruklar</a></li>							
							<li><a href="/status_graph.php?if=wan" class="navlnk">Trafik Grafiği</a></li>
							
							<?php
							//<li><a href="/status_upnp.php" class="navlnk">UPnP</a></li>
							//<li><a href="/status_rrd_graph.php" class="navlnk">RRD Grafikleri</a></li>							
							?>
							
							<?php $i = 0; $ifdescrs = array();
							if (is_array($config['interfaces']['wan']['wireless']) &&
								preg_match($g['wireless_regex'], $config['interfaces']['wan']['if']))
									$ifdescrs['wan'] = 'WAN';
							if (is_array($config['interfaces']['lan']['wireless']) &&
								preg_match($g['wireless_regex'], $config['interfaces']['lan']['if']))
									$ifdescrs['lan'] = 'LAN';
							for ($j = 1; isset($config['interfaces']['opt' . $j]); $j++) {
								if (is_array($config['interfaces']['opt' . $j]['wireless']) &&
									isset($config['interfaces']['opt' . $j]['enable']) &&
									 preg_match($g['wireless_regex'], $config['interfaces']['opt' . $j]['if']))
										$ifdescrs['opt' . $j] = $config['interfaces']['opt' . $j]['descr'];
							}
							if (count($ifdescrs) > 0): ?>
							<li><a href="/status_wireless.php" class="navlnk">Kablosuz</a></li>
							<?php endif; ?>
							<?php echo return_ext_menu("Status"); ?>
						</ul>
					</li>
					
					<li class="lastdrop">
						<div class="menubaslik">Araçlar</div>
						<ul id="diag" class="subdrop">
							<li><a href="/diag_ping.php" class="navlnk">Ping</a></li>
							<li><a href="/diag_traceroute.php" class="navlnk">Traceroute</a></li>
							
							
							<li><a href="/diag_dump_states.php" class="navlnk">Bağlantı Durumu</a></li>
							<li><a href="/diag_routes.php" class="navlnk">Yönlendirmeler</a></li>							
							<li><a href="/diag_arp.php" class="navlnk">ARP Tabloları</a></li>
							
							<li><a href="/diag_defaults.php" class="navlnk">Fabrika Ayarları</a></li>
							<li><a href="/diag_backup.php" class="navlnk">Yedekleme İşlemleri</a></li>

							<li><a href="/halt.php" class="navlnk">Sistemi Durdur</a></li>							
							<li><a href="/reboot.php" class="navlnk">Yeniden Başlat</a></li>
							
							<?php echo return_ext_menu("Diagnostics"); ?>
							<?php if(isset($config['system']['developer'])): ?>
							<li><hr width="80%"/></li>
							<li><a href="/restart_httpd.php" class="navlnk">HTTPD Yeniden Başlat</a></li>
							<?php endif; ?>
						</ul>
					</li>
					
					
					
				</ul>
			</div>

		</div> <!-- Left DIV -->

		<div id="right">


<?php
	/* display a top alert bar if need be */
	$need_alert_display = false;
	$found_notices = are_notices_pending();
	if($found_notices == true) {
		$notices = get_notices();
		if(!$notices) {
			$need_alert_display = true;
			$display_text = print_notices() . "<br>";
		}
	}
	if($need_alert_display == true) {
                echo "<div style=\"background-color:#000000\" id=\"roundalert\">";
                echo "<table>";
		echo "<tr><td><font color=\"#ffffff\">";
		echo "&nbsp;&nbsp;<img align=\"middle\" src=\"/top_notification.gif\">&nbsp;&nbsp;&nbsp;";
		echo $display_text;
		echo "</td>";
		echo "</tr>";
		echo "</table>";
		echo "</div>";
	}

?>