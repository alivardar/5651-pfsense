<?php
/*

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

$pgtitle = "Tanımlama: Paket Yakalama";
require_once("guiconfig.inc");
require_once("pfsense-utils.inc");

$fp = "/root/";
$fn = "packetcapture.cap";
$snaplen = 1500;//default packet length
$count = 100;//default number of packets to capture

function get_interface_addr($if) {
	global $config;

	$ifdescr = convert_friendly_interface_to_friendly_descr($if);

	/* find out interface name */
	if ($ifdescr == "wan")
		$if = get_real_wan_interface();
	else
		$if = $config['interfaces'][$ifdescr];

	return $if;

}

if ($_POST) {
	$do_tcpdump = true;
	$host = $_POST['host'];
	$selectedif = $_POST['interface'];
	$count = $_POST['count'];
	$packetlength = $_POST['snaplen'];
	$port = $_POST['port'];
	$detail = $_POST['detail'];

	conf_mount_rw();

	if ($_POST['dnsquery'])//if dns lookup is checked
	{
		$disabledns = "";
	}
	else //if dns lookup is unchecked
	{
		$disabledns = "-n";
	}

	if ($_POST['startbtn'] != "" )
	{
		$action = "Start";
		
	 	//delete previous packet capture if it exists
	 	if (file_exists($fp.$fn))
	 		unlink ($fp.$fn);

	}
	elseif ($_POST['stopbtn']!= "")
	{
		$action = "Stop";
		$processes_running = trim(shell_exec("ps axw -O pid= | grep tcpdump | grep $fn | grep -v pflog"));

		//explode processes into an array, (delimiter is new line)
		$processes_running_array = explode("\n", $processes_running);

		//kill each of the packetcapture processes
		foreach ($processes_running_array as $process)
		{
			$process_id_pos = strpos($process, ' ');
			$process_id = substr($process, 0, $process_id_pos);
			exec("kill $process_id");
		}

	}
	else //download file
	{
		$fs = filesize($fp.$fn);
		header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=$fn");
		header("Content-Length: $fs");
		readfile($fp.$fn);
	}
}
else
{
	$do_tcpdump = false;

}

include("head.inc"); ?>
<body link="#000000" vlink="#0000CC" alink="#0000CC">
<? include("fbegin.inc"); ?>
<p class="pgtitle"><?=$pgtitle?></p>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td>
			<form action="diag_packet_capture.php" method="post" name="iform" id="iform">
			  <table width="100%" border="0" cellpadding="6" cellspacing="0">
               	<tr>
				  <td width="17%" valign="top" class="vncellreq">Ağ Aygıtı</td>
				  <td width="83%" class="vtable">
				<select name="interface" class="formfld">
                     <?php $interfaces = array('wan' => 'WAN', 'lan' => 'LAN');
					  for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++) {
					    if (isset($config['interfaces']['opt' . $i]['enable']) &&
							!$config['interfaces']['opt' . $i]['bridge'])
					  		$interfaces['opt' . $i] = $config['interfaces']['opt' . $i]['descr'];
					  }
					  foreach ($interfaces as $iface => $ifacename): ?>
                      <option value="<?=$iface;?>" <?php if ($selectedif == $iface) echo "selected"; ?>>
                      <?php echo $ifacename;?>
                      </option>
                      <?php endforeach;?>
                    </select>
                    <br/>Select the interface the traffic will be passing through. Typically this will be the WAN interface.
				  </td>
				</tr>
			    <tr>
				  <td width="17%" valign="top" class="vncellreq">Host Adresi</td>
				  <td width="83%" class="vtable">
                    <input name="host" type="text" class="formfld" id="host" size="20" value="<?=htmlspecialchars($host);?>">
					<br/>This value is either the Source or Destination IP address. The packet capture will look for this address in either field.
					<br/>This value can be a domain name or IP address.
					<br/>If you leave this field blank all packets on the specified interface will be captured 
					</td>
				</tr>
				<tr>
				  <td width="17%" valign="top" class="vncellreq">Port</td>
				  <td width="83%" class="vtable">
                    <input name="port" type="text" class="formfld" id="port" size="5" value="<?=$port;?>">
					<br/>The port can be either the source or destination port. The packet capture will look for this port in either field.
					<br/>Leave blank if you do not want to the capture to filter by port.
					</td>
				</tr>
				<tr>
				  <td width="17%" valign="top" class="vncellreq">Paket Büyüklüğü</td>
				  <td width="83%" class="vtable">
                    <input name="snaplen" type="text" class="formfld" id="snaplen" size="5" value="<?=$snaplen;?>">
					<br/>The Packet length is the number of bytes the packet will capture for each payload. Default value is 1500.
					<br/>This value should be the same as the MTU of the Interface selected above.
					</td>
				</tr>
				<tr>
				  <td width="17%" valign="top" class="vncellreq">Sayaç</td>
				  <td width="83%" class="vtable">
                    <input name="count" type="text" class="formfld" id="count" size="5" value="<?=$count;?>">
					<br/>This is the number of packets the packet capture will grab. Default value is 100. <br/>Enter 0 (zero) for no count limit.
				</tr>
				<tr>
				  <td width="17%" valign="top" class="vncellreq">Detaylandırma Seviyesi</td>
				  <td width="83%" class="vtable">
                    <select name="detail" type="text" class="formfld" id="detail" size="1">
						<option value="-q" <?php if ($detail == "-q") echo "selected"; ?>>Normal</option>
						<option value="-v" <?php if ($detail == "-v") echo "selected"; ?>>Medium</option>
						<option value="-vv" <?php if ($detail == "-vv") echo "selected"; ?>>High</option>
						<option value="-vv -e" <?php if ($detail == "-vv -e") echo "selected"; ?>>Full</option>
					</select>
					<br/>This is the level of detail that will be displayed after hitting 'Stop' when the packets have been captured. <br/><b>Note:</b> This option does not affect the level of detail when downloading the packet capture.
				</tr>
				<tr>
				  <td width="17%" valign="top" class="vncellreq">Ters DNS Kaydı</td>
				  <td width="83%" class="vtable">
					<input name="dnsquery" type="checkbox"<?php if($_POST['dnsquery']) echo " CHECKED"; ?>>
					<br/>This check box will cause the packet capture to perform a reverse DNS lookup associated with all IP addresses.
					<br/><b>Note: </b>This option can be CPU intensive for large packet captures.
					</td>
				</tr>
				<tr>
				  <td width="17%" valign="top">&nbsp;</td>
				  <td width="83%">
                    <?php

                    /*check to see if packet capture tcpdump is already running*/
					$processcheck = (trim(shell_exec("ps axw -O pid= | grep tcpdump | grep $fn | grep -v pflog")));
					
					$processisrunning = false;

					if ($processcheck != false)
						$processisrunning = true;
						
					if (($action == "Stop" or $action == "") and $processisrunning != true)
						echo "<input type=\"submit\" name=\"startbtn\" value=\"Başlat\">&nbsp;";
				  	else{
					  	echo "<input type=\"submit\" name=\"stopbtn\" value=\"Durdur\">&nbsp;";
				  	}
					if (file_exists($fp.$fn)){
						echo "<input type=\"submit\" name=\"downloadbtn\" value=\"Yakalananları İndir\">";
						echo "&nbsp;&nbsp;(The packet capture file was last updated: " . date("F jS, Y g:i:s a.", filemtime($fp.$fn)) . ")";
					}
					?>
				  </td>
				</tr>
				<tr>
				<td valign="top" colspan="2">
				<?php
				echo "<font face='terminal' size='2'>";
				if ($processisrunning == true)
						echo("<strong>Paket yakalama şu anda çalışıyor.</strong><br/>");
						
				if ($do_tcpdump) {					

					if ($port != "")
                    {
                       $searchport = "ve port ".$port;
                       if($host <> "")                        
							$searchport = "ve port ".$port;
						else
							$searchport = "port ".$port;
                    }
                    else
                    {
                        $searchport = "";
                    }

       				if ($host != "")
         	       {
             	       $searchhost = "host " . $host;
            	   }
             	   else
                	{
                       $searchhost = "";
             		}
             		if ($count != "0" )
             		{
             			 $searchcount = "-c " . $count;
             		}
             		else
             		{
             			$searchcount = "";
             		}

					$selectedif = convert_friendly_interface_to_real_interface_name($selectedif);					
						
					if ($action == "Start")
					{
						echo("<strong>Paket Yakalama şu anda çalışıyor.</strong><br/>");
					 	mwexec_bg ("/usr/sbin/tcpdump -i $selectedif $searchcount -s $packetlength -w $fp$fn $searchhost $searchport");
						}
					else  //action = stop
					{

						echo("<strong>Packet Capture stopped. <br/><br/>Packetler Yakalandı:</strong><br/>");
						?>
						<textarea style="width:98%" name="code" rows="15" cols="66" wrap="off" readonly="readonly">
						<?php
						system ("/usr/sbin/tcpdump $disabledns $detail -r $fp$fn");?>
						</textarea><?php
					}
				}?>
				</td>
				</tr>
				<tr>

		</table>
</form>
</td></tr></table>
<?php 

conf_mount_ro();

include("fend.inc"); 

?>
