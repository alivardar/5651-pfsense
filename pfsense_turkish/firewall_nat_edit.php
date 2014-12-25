<?php
/* $Id$ */
/*
	firewall_nat_edit.php
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

if (!is_array($config['nat']['rule'])) {
	$config['nat']['rule'] = array();
}
//nat_rules_sort();
$a_nat = &$config['nat']['rule'];

$id = $_GET['id'];
if (isset($_POST['id']))
	$id = $_POST['id'];

if (isset($_GET['dup'])) {
        $id = $_GET['dup'];
        $after = $_GET['dup'];
}

if (isset($id) && $a_nat[$id]) {
	$pconfig['extaddr'] = $a_nat[$id]['external-address'];
	$pconfig['proto'] = $a_nat[$id]['protocol'];
	list($pconfig['beginport'],$pconfig['endport']) = explode("-", $a_nat[$id]['external-port']);
	$pconfig['localip'] = $a_nat[$id]['target'];
	$pconfig['localbeginport'] = $a_nat[$id]['local-port'];
	$pconfig['descr'] = $a_nat[$id]['descr'];
	$pconfig['interface'] = $a_nat[$id]['interface'];
	$pconfig['nosync'] = isset($a_nat[$id]['nosync']);
	if (!$pconfig['interface'])
		$pconfig['interface'] = "wan";
} else {
	$pconfig['interface'] = "wan";
}

if (isset($_GET['dup']))
	unset($id);

if ($_POST) {

	if ($_POST['beginport_cust'] && !$_POST['beginport'])
		$_POST['beginport'] = $_POST['beginport_cust'];
	if ($_POST['endport_cust'] && !$_POST['endport'])
		$_POST['endport'] = $_POST['endport_cust'];
	if ($_POST['localbeginport_cust'] && !$_POST['localbeginport'])
		$_POST['localbeginport'] = $_POST['localbeginport_cust'];

	if (!$_POST['endport'])
		$_POST['endport'] = $_POST['beginport'];
        /* Make beginning port end port if not defined and endport is */
        if (!$_POST['beginport'] && $_POST['endport'])
                $_POST['beginport'] = $_POST['endport'];

	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	if(strtoupper($_POST['proto']) == "TCP" or strtoupper($_POST['proto']) == "UDP" or strtoupper($_POST['proto']) == "TCP/UDP") {
		$reqdfields = explode(" ", "interface proto beginport endport localip localbeginport");
		$reqdfieldsn = explode(",", "Interface,Protocol,External port from,External port to,NAT IP,Local port");
	} else {
		$reqdfields = explode(" ", "interface proto localip");
		$reqdfieldsn = explode(",", "Interface,Protocol,NAT IP");
	}

	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

	if (($_POST['localip'] && !is_ipaddroralias($_POST['localip']))) {
		$input_errors[] = "\"{$_POST['localip']}\" geçerli bir NAT IP adresi vey ahost alias değildir.";
	}

	/* only validate the ports if the protocol is TCP, UDP or TCP/UDP */
	if(strtoupper($_POST['proto']) == "TCP" or strtoupper($_POST['proto']) == "UDP" or strtoupper($_POST['proto']) == "TCP/UDP") {

		if (($_POST['beginport'] && !is_ipaddroralias($_POST['beginport']) && !is_port($_POST['beginport']))) {
			$input_errors[] = "Başlangıç portu 1 ile 65535 arasında bir tamsayı olmalıdır.";
		}

		if (($_POST['endport'] && !is_ipaddroralias($_POST['endport']) && !is_port($_POST['endport']))) {
			$input_errors[] = "Bitiş portu 1 ile 65535 arasında bir değer olmalıdır.";
		}

		if (($_POST['localbeginport'] && !is_ipaddroralias($_POST['localbeginport']) && !is_port($_POST['localbeginport']))) {
			$input_errors[] = "Yerel port tamsayı ve 1 ile 65535 arasında bir değer olmalıdır.";
		}

		if ($_POST['beginport'] > $_POST['endport']) {
			/* swap */
			$tmp = $_POST['endport'];
			$_POST['endport'] = $_POST['beginport'];
			$_POST['beginport'] = $tmp;
		}

		if (!$input_errors) {
			if (($_POST['endport'] - $_POST['beginport'] + $_POST['localbeginport']) > 65535)
				$input_errors[] = "Hedef port 1 ile 65535 arasında bir değer olmalıdır.";
		}

	}

	/* check for overlaps */
	foreach ($a_nat as $natent) {
		if (isset($id) && ($a_nat[$id]) && ($a_nat[$id] === $natent))
			continue;
		if ($natent['interface'] != $_POST['interface'])
			continue;
		if ($natent['external-address'] != $_POST['extaddr'])
			continue;
		if (($natent['proto'] != $_POST['proto']) && ($natent['proto'] != "tcp/udp") && ($_POST['proto'] != "tcp/udp"))
			continue;

		list($begp,$endp) = explode("-", $natent['external-port']);
		if (!$endp)
			$endp = $begp;

		if (!(   (($_POST['beginport'] < $begp) && ($_POST['endport'] < $begp))
		      || (($_POST['beginport'] > $endp) && ($_POST['endport'] > $endp)))) {

			$input_errors[] = "Varolan bir kayıt ile harici port aralığı çakışıyor.";
			break;
		}
	}

	if (!$input_errors) {
		$natent = array();
		if ($_POST['extaddr'])
			$natent['external-address'] = $_POST['extaddr'];
		$natent['protocol'] = $_POST['proto'];

		if ($_POST['beginport'] == $_POST['endport'])
			$natent['external-port'] = $_POST['beginport'];
		else
			$natent['external-port'] = $_POST['beginport'] . "-" . $_POST['endport'];

		$natent['target'] = $_POST['localip'];
		$natent['local-port'] = $_POST['localbeginport'];
		$natent['interface'] = $_POST['interface'];
		$natent['descr'] = $_POST['descr'];

		if($_POST['nosync'] == "yes")
			$natent['nosync'] = true;
		else
			unset($natent['nosync']);

		if (isset($id) && $a_nat[$id])
			$a_nat[$id] = $natent;
		else {
			if (is_numeric($after))
				array_splice($a_nat, $after+1, 0, array($natent));
			else
				$a_nat[] = $natent;
		}

		touch($d_natconfdirty_path);

		if ($_POST['autoadd']) {
			/* auto-generate a matching firewall rule */
			$filterent = array();
			$filterent['interface'] = $_POST['interface'];
			$filterent['protocol'] = $_POST['proto'];
			$filterent['source']['any'] = "";
			$filterent['destination']['address'] = $_POST['localip'];

			$dstpfrom = $_POST['localbeginport'];
			$dstpto = $dstpfrom + $_POST['endport'] - $_POST['beginport'];

			if ($dstpfrom == $dstpto)
				$filterent['destination']['port'] = $dstpfrom;
			else
				$filterent['destination']['port'] = $dstpfrom . "-" . $dstpto;

			$filterent['descr'] = "NAT " . $_POST['descr'];
			/*
			 * Our firewall filter description may be no longer than
			 * 63 characters, so don't let it be. (and take "NAT "
			 * into account)
			 */
			$filterent['descr'] = substr("NAT " . $_POST['descr'], 0, 59);

			$config['filter']['rule'][] = $filterent;

			/*    auto add rule to external port 21 as well since we are using
			 *    pftpx to help open up ports automatically
			 */
			if($_POST['endport'] == "21") {
				$filterent = array();
				$filterent['interface'] = $_POST['interface'];
				$filterent['protocol'] = $_POST['proto'];
				$filterent['source']['any'] = "";

				if($_POST['extaddr'] == "") {
					$filterent['destination']['network'] = "wanip";
				} else {
					$filterent['destination']['address'] = $_POST['extaddr'];
				}

				$dstpfrom = $_POST['localbeginport'];
				$dstpto = $dstpfrom + $_POST['endport'] - $_POST['beginport'];

				if ($dstpfrom == $dstpto)
					$filterent['destination']['port'] = $dstpfrom;
				else
					$filterent['destination']['port'] = $dstpfrom . "-" . $dstpto;

				$filterent['descr'] = "NAT " . $_POST['descr'];
				/* See comment above */
				$filterent['descr'] = substr("NAT " . $_POST['descr'], 0, 63);

				$config['filter']['rule'][] = $filterent;

				touch($d_filterconfdirty_path);

				write_config();

				header("Location: firewall_nat.php?savemsg=The%20changes%20have%20been%20saved.%20%20Please%20note%20that%20we%20have%20added%20an%20additional%20rule%20for%20the%20FTP%20helper.");

				exit;

			}

			touch($d_filterconfdirty_path);
		}

		write_config();

		header("Location: firewall_nat.php");
		exit;
	}
}

$pgtitle = "Firewall: NAT: Port Yönlendir: Düzenle";
include("head.inc");

?>

<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<?php
include("fbegin.inc"); ?>
<p class="pgtitle"><?=$pgtitle?></p>
<?php if ($input_errors) print_input_errors($input_errors); ?>
            <form action="firewall_nat_edit.php" method="post" name="iform" id="iform">
              <table width="100%" border="0" cellpadding="6" cellspacing="0">
	  	<tr>
                  <td width="22%" valign="top" class="vncellreq">Ağ aygıtı</td>
                  <td width="78%" class="vtable">
					<select name="interface" class="formfld">
						<?php
						$interfaces = array('wan' => 'WAN', 'lan' => 'LAN', 'pptp' => 'PPTP', 'pppoe' => 'PPPOE');
						for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++) {
							$interfaces['opt' . $i] = $config['interfaces']['opt' . $i]['descr'];
						}
						foreach ($interfaces as $iface => $ifacename): ?>
						<option value="<?=$iface;?>" <?php if ($iface == $pconfig['interface']) echo "selected"; ?>>
						<?=htmlspecialchars($ifacename);?>
						</option>
						<?php endforeach; ?>
					</select><br>
                     <span class="vexpl">Bu kuralın geçerli olacağı arayüzü seçiniz.<br>
                     İpucu: Çoğunlukla bu alanda WAN seçilir.</span></td>
                </tr>
			    <tr>
                  <td width="22%" valign="top" class="vncellreq">Dış adres</td>
                  <td width="78%" class="vtable">
					<select name="extaddr" class="formfld">
						<option value="" <?php if (!$pconfig['extaddr']) echo "selected"; ?>>Ağ aygıt adresi</option>
<?php					if (is_array($config['virtualip']['vip'])):
						foreach ($config['virtualip']['vip'] as $sn): ?>
						<option value="<?=$sn['subnet'];?>" <?php if ($sn['subnet'] == $pconfig['extaddr']) echo "selected"; ?>><?=htmlspecialchars("{$sn['subnet']} ({$sn['descr']})");?></option>
<?php					endforeach;
						endif; ?>
						<option value="any" <?php if($pconfig['extaddr'] == "any") echo "selected"; ?>>any</option>
					</select>
					<br />
                    <span class="vexpl">
					If you want this rule to apply to another IP address than the address of the interface chosen above,
					select it here (you need to define <a href="firewall_virtual_ip.php">Virtual IP</a> addresses first).  Note if you are redirecting connections on the LAN, select the "any" option.</span></td>
                </tr>
                <tr>
                  <td width="22%" valign="top" class="vncellreq">Protokol</td>
                  <td width="78%" class="vtable">
                    <select name="proto" class="formfld" onChange="proto_change(); check_for_aliases();">
                      <?php $protocols = explode(" ", "TCP UDP TCP/UDP GRE ESP"); foreach ($protocols as $proto): ?>
                      <option value="<?=strtolower($proto);?>" <?php if (strtolower($proto) == $pconfig['proto']) echo "selected"; ?>><?=htmlspecialchars($proto);?></option>
                      <?php endforeach; ?>
                    </select> <br> <span class="vexpl">Choose which IP protocol
                    this rule should match.<br>
                    Hint: in most cases, you should specify <em>TCP</em> &nbsp;here.</span></td>
                </tr>
                <tr>
                  <td width="22%" valign="top" class="vncellreq">Dış port
                    aralığı </td>
                  <td width="78%" class="vtable">
                    <table border="0" cellspacing="0" cellpadding="0">
                      <tr>
                        <td>from:&nbsp;&nbsp;</td>
                        <td><select name="beginport" class="formfld" onChange="ext_rep_change(); ext_change(); check_for_aliases();">
                            <option value="">(other)</option>
                            <?php $bfound = 0; foreach ($wkports as $wkport => $wkportdesc): ?>
                            <option value="<?=$wkport;?>" <?php if ($wkport == $pconfig['beginport']) {
								echo "selected";
								$bfound = 1;
							}?>>
							<?=htmlspecialchars($wkportdesc);?>
							</option>
                            <?php endforeach; ?>
                          </select> <input onChange="check_for_aliases();" autocomplete='off' class="formfldalias" name="beginport_cust" id="beginport_cust" type="text" size="5" value="<?php if (!$bfound) echo $pconfig['beginport']; ?>"></td>
                      </tr>
                      <tr>
                        <td>to:</td>
                        <td><select name="endport" class="formfld" onChange="ext_change(); check_for_aliases();">
                            <option value="">(diğer)</option>
                            <?php $bfound = 0; foreach ($wkports as $wkport => $wkportdesc): ?>
                            <option value="<?=$wkport;?>" <?php if ($wkport == $pconfig['endport']) {
								echo "selected";
								$bfound = 1;
							}?>>
							<?=htmlspecialchars($wkportdesc);?>
							</option>
							<?php endforeach; ?>
                          </select> <input onChange="check_for_aliases();" class="formfldalias" autocomplete='off' name="endport_cust" id="endport_cust" type="text" size="5" value="<?php if (!$bfound) echo $pconfig['endport']; ?>"></td>
                      </tr>
                    </table>
                    <br> <span class="vexpl">Specify the port or port range on
                    the firewall's external address for this mapping.<br>
                    Hint: you can leave the <em>'to'</em> field empty if you only
                    want to map a single port</span></td>
                </tr>
                <tr>
                  <td width="22%" valign="top" class="vncellreq">NAT IP</td>
                  <td width="78%" class="vtable">
                    <input autocomplete='off' name="localip" type="text" class="formfldalias" id="localip" size="20" value="<?=htmlspecialchars($pconfig['localip']);?>">
                    <br> <span class="vexpl">Enter the internal IP address of
                    the server on which you want to map the ports.<br>
                    örnek <em>192.168.1.12</em></span></td>
                </tr>
                <tr>
                  <td width="22%" valign="top" class="vncellreq">Yerel port</td>
                  <td width="78%" class="vtable">
                    <select name="localbeginport" class="formfld" onChange="ext_change();check_for_aliases();">
                      <option value="">(other)</option>
                      <?php $bfound = 0; foreach ($wkports as $wkport => $wkportdesc): ?>
                      <option value="<?=$wkport;?>" <?php if ($wkport == $pconfig['localbeginport']) {
							echo "selected";
							$bfound = 1;
						}?>>
					  <?=htmlspecialchars($wkportdesc);?>
					  </option>
                      <?php endforeach; ?>
                    </select> <input onChange="check_for_aliases();" autocomplete='off' class="formfldalias" name="localbeginport_cust" id="localbeginport_cust" type="text" size="5" value="<?php if (!$bfound) echo $pconfig['localbeginport']; ?>">
                    <br>
                    <span class="vexpl">Specify the port on the machine with the
                    IP address entered above. In case of a port range, specify
                    the beginning port of the range (the end port will be calculated
                    automatically).<br>
                    Hint: this is usually identical to the 'from' port above</span></td>
                </tr>
                <tr>
                  <td width="22%" valign="top" class="vncell">Açıklama</td>
                  <td width="78%" class="vtable">
                    <input name="descr" type="text" class="formfld" id="descr" size="40" value="<?=htmlspecialchars($pconfig['descr']);?>">
                    <br> <span class="vexpl">You may enter a description here
                    for your reference (not parsed).</span></td>
                </tr>
				<tr>
					<td width="22%" valign="top" class="vncell">No XMLRPC Sync</td>
					<td width="78%" class="vtable">
						<input type="checkbox" value="yes" name="nosync"<?php if($pconfig['nosync']) echo " CHECKED"; ?>><br>
						HINT: This prevents the rule from automatically syncing to other CARP members.
					</td>
				</tr>
                <?php if ((!(isset($id) && $a_nat[$id])) || (isset($_GET['dup']))): ?>
                <tr>
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%">
                    <input name="autoadd" type="checkbox" id="autoadd" value="yes" CHECKED>
                    <strong>Auto-add a firewall rule to permit traffic through
                    this NAT rule</strong></td>
                </tr><?php endif; ?>
                <tr>
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%">
                    <input name="Submit" type="submit" class="formbtn" value="Kaydet"> <input type="button" class="formbtn" value="Vazgeç" onclick="history.back()">
                    <?php if (isset($id) && $a_nat[$id]): ?>
                    <input name="id" type="hidden" value="<?=$id;?>">
                    <?php endif; ?>
                  </td>
                </tr>
              </table>
</form>
<script language="JavaScript">
<!--
	ext_change();
//-->
</script>
<?php
$isfirst = 0;
$aliases = "";
$addrisfirst = 0;
$aliasesaddr = "";
if($config['aliases']['alias'] <> "")
	foreach($config['aliases']['alias'] as $alias_name) {
		if(!stristr($alias_name['address'], ".")) {
			if($isfirst == 1) $aliases .= ",";
			$aliases .= "'" . $alias_name['name'] . "'";
			$isfirst = 1;
		} else {
			if($addrisfirst == 1) $aliasesaddr .= ",";
			$aliasesaddr .= "'" . $alias_name['name'] . "'";
			$addrisfirst = 1;
		}
	}
?>
<script language="JavaScript">
<!--
	var addressarray=new Array(<?php echo $aliasesaddr; ?>);
	var customarray=new Array(<?php echo $aliases; ?>);
//-->
</script>
<?php include("fend.inc"); ?>
</body>
</html>
