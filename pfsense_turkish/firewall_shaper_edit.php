<?php
/* $Id$ */
/*
	firewall_shaper_edit.php
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

if (!is_array($config['shaper']['rule'])) {
	$config['shaper']['rule'] = array();
}
$a_shaper = &$config['shaper']['rule'];

/* redirect to wizard if shaper isn't already configured */
if(isset($config['shaper']['enable'])) {
	$pconfig['enable'] = TRUE;
} else {
	if(!is_array($config['shaper']['queue']))
		Header("Location: wizard.php?xml=traffic_shaper_wizard.xml");
}

$specialsrcdst = explode(" ", "any wanip lanip lan pptp");

$id = $_GET['id'];
if (isset($_POST['id']))
	$id = $_POST['id'];

$after = $_GET['after'];
if (isset($_POST['after']))
	$after = $_POST['after'];

if (isset($_GET['dup'])) {
	$id = $_GET['dup'];
	$after = $_GET['dup'];
}

if (isset($id) && $a_shaper[$id]) {
	$pconfig['in-interface'] = $a_shaper[$id]['in-interface'];
	$pconfig['out-interface'] = $a_shaper[$id]['out-interface'];

	if (isset($a_shaper[$id]['protocol']))
		$pconfig['proto'] = $a_shaper[$id]['protocol'];
	else
		$pconfig['proto'] = "any";

	address_to_pconfig($a_shaper[$id]['source'], $pconfig['src'],
		$pconfig['srcmask'], $pconfig['srcnot'],
		$pconfig['srcbeginport'], $pconfig['srcendport']);

	address_to_pconfig($a_shaper[$id]['destination'], $pconfig['dst'],
		$pconfig['dstmask'], $pconfig['dstnot'],
		$pconfig['dstbeginport'], $pconfig['dstendport']);

	$pconfig['inqueue'] = $a_shaper[$id]['inqueue'];
	$pconfig['outqueue'] = $a_shaper[$id]['outqueue'];

	$pconfig['direction'] = $a_shaper[$id]['direction'];
	$pconfig['iptos'] = $a_shaper[$id]['iptos'];
	$pconfig['tcpflags'] = $a_shaper[$id]['tcpflags'];
	$pconfig['descr'] = $a_shaper[$id]['descr'];
	$pconfig['disabled'] = isset($a_shaper[$id]['disabled']);

	if ($pconfig['srcbeginport'] == 0) {
		$pconfig['srcbeginport'] = "any";
		$pconfig['srcendport'] = "any";
	}
	if ($pconfig['dstbeginport'] == 0) {
		$pconfig['dstbeginport'] = "any";
		$pconfig['dstendport'] = "any";
	}

} else {
	/* defaults */
	$pconfig['src'] = "any";
	$pconfig['dst'] = "any";
}

if (isset($_GET['dup']))
	unset($id);

if ($_POST) {

	if (($_POST['proto'] != "tcp") && ($_POST['proto'] != "udp") && ($_POST['proto'] != "any")) {
		$_POST['srcbeginport'] = 0;
		$_POST['srcendport'] = 0;
		$_POST['dstbeginport'] = 0;
		$_POST['dstendport'] = 0;
	} else {

		if ($_POST['srcbeginport_cust'] && !$_POST['srcbeginport'])
			$_POST['srcbeginport'] = $_POST['srcbeginport_cust'];
		if ($_POST['srcendport_cust'] && !$_POST['srcendport'])
			$_POST['srcendport'] = $_POST['srcendport_cust'];

		if ($_POST['srcbeginport'] == "any") {
			$_POST['srcbeginport'] = 0;
			$_POST['srcendport'] = 0;
		} else {
			if (!$_POST['srcendport'])
				$_POST['srcendport'] = $_POST['srcbeginport'];
		}
		if ($_POST['srcendport'] == "any")
			$_POST['srcendport'] = $_POST['srcbeginport'];

		if ($_POST['dstbeginport_cust'] && !$_POST['dstbeginport'])
			$_POST['dstbeginport'] = $_POST['dstbeginport_cust'];
		if ($_POST['dstendport_cust'] && !$_POST['dstendport'])
			$_POST['dstendport'] = $_POST['dstendport_cust'];

		if ($_POST['dstbeginport'] == "any") {
			$_POST['dstbeginport'] = 0;
			$_POST['dstendport'] = 0;
		} else {
			if (!$_POST['dstendport'])
				$_POST['dstendport'] = $_POST['dstbeginport'];
		}
		if ($_POST['dstendport'] == "any")
			$_POST['dstendport'] = $_POST['dstbeginport'];
	}

	if (is_specialnet($_POST['srctype'])) {
		$_POST['src'] = $_POST['srctype'];
		$_POST['srcmask'] = 0;
	} else if ($_POST['srctype'] == "single") {
		$_POST['srcmask'] = 32;
	}
	if (is_specialnet($_POST['dsttype'])) {
		$_POST['dst'] = $_POST['dsttype'];
		$_POST['dstmask'] = 0;
	}  else if ($_POST['dsttype'] == "single") {
		$_POST['dstmask'] = 32;
	}

	$intos = array();
	foreach ($iptos as $tos) {
		if ($_POST['iptos_' . $tos] == "on")
			$intos[] = $tos;
		else if ($_POST['iptos_' . $tos] == "off")
			$intos[] = "!" . $tos;
	}
	$_POST['iptos'] = join(",", $intos);

	$intcpflags = array();
	foreach ($tcpflags as $tcpflag) {
		if ($_POST['tcpflags_' . $tcpflag] == "on")
			$intcpflags[] = $tcpflag;
		else if ($_POST['tcpflags_' . $tcpflag] == "off")
			$intcpflags[] = "!" . $tcpflag;
	}
	$_POST['tcpflags'] = join(",", $intcpflags);

	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	$reqdfields = explode(" ", "inqueue outqueue proto src dst");
	$reqdfieldsn = explode(",", "Inbound Queue,Outbound Queue,Protocol,Source,Destination");

	if (!(is_specialnet($_POST['srctype']) || ($_POST['srctype'] == "single"))) {
		$reqdfields[] = "srcmask";
		$reqdfieldsn[] = "Source bit count";
	}
	if (!(is_specialnet($_POST['dsttype']) || ($_POST['dsttype'] == "single"))) {
		$reqdfields[] = "dstmask";
		$reqdfieldsn[] = "Destination bit count";
	}

	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

	if (!$_POST['srcbeginport']) {
		$_POST['srcbeginport'] = 0;
		$_POST['srcendport'] = 0;
	}
	if (!$_POST['dstbeginport']) {
		$_POST['dstbeginport'] = 0;
		$_POST['dstendport'] = 0;
	}

	if (($_POST['srcbeginport'] && !alias_expand($_POST['srcbeginport']) && !is_port($_POST['srcbeginport']))) {
		$input_errors[] = "The start source port must be an alias or integer between 1 and 65535.";
	}
	if (($_POST['srcendport'] && !alias_expand($_POST['srcendport']) && !is_port($_POST['srcendport']))) {
		$input_errors[] = "The end source port must be an alias or integer between 1 and 65535.";
	}
	if (($_POST['dstbeginport'] && !alias_expand($_POST['dstbeginport']) && !is_port($_POST['dstbeginport']))) {
		$input_errors[] = "The start destination port must be an alias or integer between 1 and 65535.";
	}
	if (($_POST['dstendport'] && !alias_expand($_POST['dstbeginport']) && !is_port($_POST['dstendport']))) {
		$input_errors[] = "The end destination port must be an alias or integer between 1 and 65535.";
	}

	if (!is_specialnet($_POST['srctype'])) {
		if (($_POST['src'] && !is_ipaddroranyalias($_POST['src']))) {
			$input_errors[] = "A valid source IP address or alias must be specified.";
		}
		if (($_POST['srcmask'] && !is_numericint($_POST['srcmask']))) {
			$input_errors[] = "A valid source bit count must be specified.";
		}
	}
	if (!is_specialnet($_POST['dsttype'])) {
		if (($_POST['dst'] && !is_ipaddroranyalias($_POST['dst']))) {
			$input_errors[] = "A valid destination IP address or alias must be specified.";
		}
		if (($_POST['dstmask'] && !is_numericint($_POST['dstmask']))) {
			$input_errors[] = "A valid destination bit count must be specified.";
		}
	}

	if ($_POST['srcbeginport'] > $_POST['srcendport']) {
		/* swap */
		$tmp = $_POST['srcendport'];
		$_POST['srcendport'] = $_POST['srcbeginport'];
		$_POST['srcbeginport'] = $tmp;
	}
	if ($_POST['dstbeginport'] > $_POST['dstendport']) {
		/* swap */
		$tmp = $_POST['dstendport'];
		$_POST['dstendport'] = $_POST['dstbeginport'];
		$_POST['dstbeginport'] = $tmp;
	}

	if (!$input_errors) {
		$shaperent = array();
		$shaperent['in-interface'] = $_POST['in-interface'];
		$shaperent['out-interface'] = $_POST['out-interface'];

		if ($_POST['proto'] != "any")
			$shaperent['protocol'] = $_POST['proto'];
		else
			unset($shaperent['protocol']);

		pconfig_to_address($shaperent['source'], $_POST['src'],
			$_POST['srcmask'], $_POST['srcnot'],
			$_POST['srcbeginport'], $_POST['srcendport']);

		pconfig_to_address($shaperent['destination'], $_POST['dst'],
			$_POST['dstmask'], $_POST['dstnot'],
			$_POST['dstbeginport'], $_POST['dstendport']);

		$shaperent['direction'] = $_POST['direction'];
		$shaperent['iptos'] = $_POST['iptos'];
		$shaperent['tcpflags'] = $_POST['tcpflags'];
		$shaperent['descr'] = $_POST['descr'];
		$shaperent['disabled'] = $_POST['disabled'] ? true : false;

		$shaperent['inqueue'] = $_POST['inqueue'];
		$shaperent['outqueue'] = $_POST['outqueue'];

		if (isset($id) && $a_shaper[$id])
			$a_shaper[$id] = $shaperent;
		else {
			if (is_numeric($after))
				array_splice($a_shaper, $after+1, 0, array($shaperent));
			else
				$a_shaper[] = $shaperent;
		}

		write_config();
		touch($d_shaperconfdirty_path);

		header("Location: firewall_shaper.php");
		exit;
	}
}

$pgtitle = "Firewall: Sınırlandırma: Kurallar: Düzenle";
$closehead = false;
include("head.inc");
?>

<script language="JavaScript">
<!--
var portsenabled = 1;

function ext_change() {
	if ((document.iform.srcbeginport.selectedIndex == 0) && portsenabled) {
		document.iform.srcbeginport_cust.disabled = 0;
	} else {
		document.iform.srcbeginport_cust.value = "";
		document.iform.srcbeginport_cust.disabled = 1;
	}
	if ((document.iform.srcendport.selectedIndex == 0) && portsenabled) {
		document.iform.srcendport_cust.disabled = 0;
	} else {
		document.iform.srcendport_cust.value = "";
		document.iform.srcendport_cust.disabled = 1;
	}
	if ((document.iform.dstbeginport.selectedIndex == 0) && portsenabled) {
		document.iform.dstbeginport_cust.disabled = 0;
	} else {
		document.iform.dstbeginport_cust.value = "";
		document.iform.dstbeginport_cust.disabled = 1;
	}
	if ((document.iform.dstendport.selectedIndex == 0) && portsenabled) {
		document.iform.dstendport_cust.disabled = 0;
	} else {
		document.iform.dstendport_cust.value = "";
		document.iform.dstendport_cust.disabled = 1;
	}

	if (!portsenabled) {
		document.iform.srcbeginport.disabled = 1;
		document.iform.srcendport.disabled = 1;
		document.iform.dstbeginport.disabled = 1;
		document.iform.dstendport.disabled = 1;
	} else {
		document.iform.srcbeginport.disabled = 0;
		document.iform.srcendport.disabled = 0;
		document.iform.dstbeginport.disabled = 0;
		document.iform.dstendport.disabled = 0;
	}
}

function typesel_change() {
	switch (document.iform.srctype.selectedIndex) {
		case 1:	/* single */
			document.iform.src.disabled = 0;
			document.iform.srcmask.value = "";
			document.iform.srcmask.disabled = 1;
			break;
		case 2:	/* network */
			document.iform.src.disabled = 0;
			document.iform.srcmask.disabled = 0;
			break;
		default:
			document.iform.src.value = "";
			document.iform.src.disabled = 1;
			document.iform.srcmask.value = "";
			document.iform.srcmask.disabled = 1;
			break;
	}
	switch (document.iform.dsttype.selectedIndex) {
		case 1:	/* single */
			document.iform.dst.disabled = 0;
			document.iform.dstmask.value = "";
			document.iform.dstmask.disabled = 1;
			break;
		case 2:	/* network */
			document.iform.dst.disabled = 0;
			document.iform.dstmask.disabled = 0;
			break;
		default:
			document.iform.dst.value = "";
			document.iform.dst.disabled = 1;
			document.iform.dstmask.value = "";
			document.iform.dstmask.disabled = 1;
			break;
	}
}

function proto_change() {
	if (document.iform.proto.selectedIndex < 2 || document.iform.proto.selectedIndex == 8) {
		portsenabled = 1;
	} else {
		portsenabled = 0;
	}

	ext_change();
}

function src_rep_change() {
	document.iform.srcendport.selectedIndex = document.iform.srcbeginport.selectedIndex;
}
function dst_rep_change() {
	document.iform.dstendport.selectedIndex = document.iform.dstbeginport.selectedIndex;
}
//-->
</script>
</head>

<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<?php include("fbegin.inc"); ?>
<p class="pgtitle"><?=$pgtitle?></p>
<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if (is_array($config['shaper']['queue']) && (count($config['shaper']['queue']) > 0)): ?>
            <form action="firewall_shaper_edit.php" method="post" name="iform" id="iform">
              <table width="100%" border="0" cellpadding="6" cellspacing="0">
                <tr>
                  <td valign="top" class="vncellreq">Target</td>
                  <td class="vtable"> <select name="outqueue" class="formfld">
                      <?php
					  foreach ($config['shaper']['queue'] as $queuei => $queue): ?>
                      <option value="<?=$queue['name'];?>" <?php if ($queue['name'] == $pconfig['outqueue']) echo "selected"; ?>>
                        <?php
					  	echo htmlspecialchars("Outbound Queue " . ($queuei + 1));
						if ($queue['name'])
							echo htmlspecialchars(" (" . $queue['name'] . ")");
			?>
                      </option>
                      <?php endforeach; ?>
                    </select>/<select name="inqueue" class="formfld">
                      <?php
					  foreach ($config['shaper']['queue'] as $queuei => $queue): ?>
                      <option value="<?=$queue['name'];?>" <?php if ($queue['name'] == $pconfig['inqueue']) echo "selected"; ?>>
                        <?php
					  	echo htmlspecialchars("Inbound Queue " . ($queuei + 1));
						if ($queue['name'])
							echo htmlspecialchars(" (" . $queue['name'] . ")");
			?>
                      </option>
                      <?php endforeach; ?> <br>
                    <span class="vexpl">Choose a queue where packets that
                    match this rule should be sent.</span></td>
                </tr>
                <tr>
                  <td valign="top" class="vncellreq">Disabled</td>
                  <td class="vtable">
                    <input name="disabled" type="checkbox" id="disabled" value="yes" <?php if ($pconfig['disabled']) echo "checked"; ?>>
                    <strong>Disable this rule</strong><br>
                    <span class="vexpl">Set this option to disable this rule without removing it from the list.</span></td>
                </tr>
                <tr>
                  <td width="22%" valign="top" class="vncellreq">In Interface</td>
                  <td width="78%" class="vtable"> <select name="in-interface" class="formfld">
                      <?php $interfaces = array('lan' => 'LAN', 'wan' => 'WAN', 'pptp' => 'PPTP');
					  for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++) {
					  	$interfaces['opt' . $i] = $config['interfaces']['opt' . $i]['descr'];
					  }
					  foreach ($interfaces as $iface => $ifacename): ?>
                      <option value="<?=$iface;?>" <?php if ($iface == $pconfig['in-interface']) echo "selected"; ?>>
                      <?=htmlspecialchars($ifacename);?>
                      </option>
                      <?php endforeach; ?>
                    </select> <br>
                    <span class="vexpl">Choose which interface packets must pass in to match this rule.</span></td>
                </tr>
                <tr>
                  <td width="22%" valign="top" class="vncellreq">Out Interface</td>
                  <td width="78%" class="vtable"> <select name="out-interface" class="formfld">
                      <?php $interfaces = array('lan' => 'LAN', 'wan' => 'WAN', 'pptp' => 'PPTP');
					  for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++) {
					  	$interfaces['opt' . $i] = $config['interfaces']['opt' . $i]['descr'];
					  }
					  foreach ($interfaces as $iface => $ifacename): ?>
                      <option value="<?=$iface;?>" <?php if ($iface == $pconfig['out-interface']) echo "selected"; ?>>
                      <?=htmlspecialchars($ifacename);?>
                      </option>
                      <?php endforeach; ?>
                    </select> <br>
                    <span class="vexpl">Choose which interface packets must pass out to match this rule.</span></td>
                </tr>
                <tr>
                  <td width="22%" valign="top" class="vncellreq">Protocol</td>
                  <td width="78%" class="vtable"> <select name="proto" class="formfld" onchange="proto_change()">
                      <?php $protocols = explode(" ", "TCP UDP ICMP ESP AH GRE IPv6 IGMP any"); foreach ($protocols as $proto): ?>
                      <option value="<?=strtolower($proto);?>" <?php if (strtolower($proto) == $pconfig['proto']) echo "selected"; ?>>
                      <?=htmlspecialchars($proto);?>
                      </option>
                      <?php endforeach; ?>
                    </select> <br> <span class="vexpl">Choose which IP protocol
                    this rule should match.<br>
                    Hint: in most cases, you should specify <em>TCP</em> &nbsp;here.</span></td>
                </tr>
                <tr>
                  <td width="22%" valign="top" class="vncellreq">Source</td>
                  <td width="78%" class="vtable"> <input name="srcnot" type="checkbox" id="srcnot" value="yes" <?php if ($pconfig['srcnot']) echo "checked"; ?>>
                    <strong>not</strong><br>
                    Use this option to invert the sense of the match.<br> <br>
                    <table border="0" cellspacing="0" cellpadding="0">
                      <tr>
                        <td>Type:&nbsp;&nbsp;</td>
                        <td><select name="srctype" class="formfld" onChange="typesel_change()">
                            <?php $sel = is_specialnet($pconfig['src']); ?>
                            <option value="any" <?php if ($pconfig['src'] == "any") { echo "selected"; } ?>>
                            any</option>
                            <option value="single" <?php if (($pconfig['srcmask'] == 32) && !$sel) { echo "selected"; $sel = 1; } ?>>
                            Single host or alias</option>
                            <option value="network" <?php if (!$sel) echo "selected"; ?>>
                            Network</option>
							<option value="wanip" <?php if ($pconfig['src'] == "wanip") { echo "selected"; } ?>>
                            WAN address</option>
							<option value="lanip" <?php if ($pconfig['src'] == "lanip") { echo "selected"; } ?>>
                            LAN address</option>
                            <option value="lan" <?php if ($pconfig['src'] == "lan") { echo "selected"; } ?>>
                            LAN subnet</option>
                            <option value="pptp" <?php if ($pconfig['src'] == "pptp") { echo "selected"; } ?>>
                            PPTP clients</option>
                            <?php for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++): ?>
                            <option value="opt<?=$i;?>" <?php if ($pconfig['src'] == "opt" . $i) { echo "selected"; } ?>>
                            <?=htmlspecialchars($config['interfaces']['opt' . $i]['descr']);?>
                            subnet</option>
                            <?php endfor; ?>
                          </select></td>
                      </tr>
                      <tr>
                        <td>Adres:&nbsp;&nbsp;</td>
                        <td><input autocomplete='off' name="src" type="text" class="formfldalias" id="src" size="20" value="<?php if (!is_specialnet($pconfig['src'])) echo htmlspecialchars($pconfig['src']);?>">
                          /
                          <select name="srcmask" class="formfld" id="srcmask">
                            <?php for ($i = 31; $i > 0; $i--): ?>
                            <option value="<?=$i;?>" <?php if ($i == $pconfig['srcmask']) echo "selected"; ?>>
                            <?=$i;?>
                            </option>
                            <?php endfor; ?>
                          </select></td>
                      </tr>
                    </table></td>
                </tr>
                <tr>
                  <td width="22%" valign="top" class="vncellreq">Kaynak Port Aralığı
                  </td>
                  <td width="78%" class="vtable"> <table border="0" cellspacing="0" cellpadding="0">
                      <tr>
                        <td>from:&nbsp;&nbsp;</td>
                        <td><select name="srcbeginport" class="formfld" onchange="src_rep_change();ext_change()">
                            <option value="">(other)</option>
                            <option value="any" <?php $bfound = 0; if ($pconfig['srcbeginport'] == "any") { echo "selected"; $bfound = 1; } ?>>any</option>
                            <?php foreach ($wkports as $wkport => $wkportdesc): ?>
                            <option value="<?=$wkport;?>" <?php if ($wkport == $pconfig['srcbeginport']) {
																echo "selected";
																$bfound = 1;
															}?>>
                            <?=htmlspecialchars($wkportdesc);?>
                            </option>
                            <?php endforeach; ?>
                          </select> <input autocomplete='off' class="formfldalias" name="srcbeginport_cust" id="srcbeginport_cust" type="text" size="5" value="<?php if (!$bfound && $pconfig['srcbeginport']) echo $pconfig['srcbeginport']; ?>"></td>
                      </tr>
                      <tr>
                        <td>to:</td>
                        <td><select name="srcendport" class="formfld" onchange="ext_change()">
                            <option value="">(other)</option>
                            <option value="any" <?php $bfound = 0; if ($pconfig['srcendport'] == "any") { echo "selected"; $bfound = 1; } ?>>any</option>
                            <?php foreach ($wkports as $wkport => $wkportdesc): ?>
                            <option value="<?=$wkport;?>" <?php if ($wkport == $pconfig['srcendport']) {
																echo "selected";
																$bfound = 1;
															}?>>
                            <?=htmlspecialchars($wkportdesc);?>
                            </option>
                            <?php endforeach; ?>
                          </select> <input autocomplete='off' class="formfldalias" name="srcendport_cust" id="srcendport_cust" type="text" size="5" value="<?php if (!$bfound && $pconfig['srcendport']) echo $pconfig['srcendport']; ?>"></td>
                      </tr>
                    </table>
                    <br> <span class="vexpl">Specify the port or port range for
                    the source of the packet for this rule.<br>
                    Hint: you can leave the <em>'to'</em> field empty if you only
                    want to filter a single port</span></td>
                <tr>
                  <td width="22%" valign="top" class="vncellreq">Destination</td>
                  <td width="78%" class="vtable"> <input name="dstnot" type="checkbox" id="dstnot" value="yes" <?php if ($pconfig['dstnot']) echo "checked"; ?>>
                    <strong>not</strong><br>
                    Use this option to invert the sense of the match.<br> <br>
                    <table border="0" cellspacing="0" cellpadding="0">
                      <tr>
                        <td>Type:&nbsp;&nbsp;</td>
                        <td><select name="dsttype" class="formfld" onChange="typesel_change()">
                            <?php $sel = is_specialnet($pconfig['dst']); ?>
                            <option value="any" <?php if ($pconfig['dst'] == "any") { echo "selected"; } ?>>
                            any</option>
                            <option value="single" <?php if (($pconfig['dstmask'] == 32) && !$sel) { echo "selected"; $sel = 1; } ?>>
                            Single host or alias</option>
                            <option value="network" <?php if (!$sel) echo "selected"; ?>>
                            Network</option>
							<option value="wanip" <?php if ($pconfig['dst'] == "wanip") { echo "selected"; } ?>>
                            WAN address</option>
							<option value="lanip" <?php if ($pconfig['dst'] == "lanip") { echo "selected"; } ?>>
                            LAN address</option>
                            <option value="lan" <?php if ($pconfig['dst'] == "lan") { echo "selected"; } ?>>
                            LAN subnet</option>
                            <option value="pptp" <?php if ($pconfig['dst'] == "pptp") { echo "selected"; } ?>>
                            PPTP clients</option>
                            <?php for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++): ?>
                            <option value="opt<?=$i;?>" <?php if ($pconfig['dst'] == "opt" . $i) { echo "selected"; } ?>>
                            <?=htmlspecialchars($config['interfaces']['opt' . $i]['descr']);?>
                            subnet</option>
                            <?php endfor; ?>
                          </select> </td>
                      </tr>
                      <tr>
                        <td>Adres:&nbsp;&nbsp;</td>
                        <td><input name="dst" autocomplete='off' type="text" class="formfldalias" id="dst" size="20" value="<?php if (!is_specialnet($pconfig['dst'])) echo htmlspecialchars($pconfig['dst']);?>">
                          /
                          <select name="dstmask" class="formfld" id="dstmask">
                            <?php for ($i = 31; $i > 0; $i--): ?>
                            <option value="<?=$i;?>" <?php if ($i == $pconfig['dstmask']) echo "selected"; ?>>
                            <?=$i;?>
                            </option>
                            <?php endfor; ?>
                          </select></td>
                      </tr>
                    </table></td>
                </tr>
                <tr>
                  <td width="22%" valign="top" class="vncellreq">Hedef port
                    aralığı </td>
                  <td width="78%" class="vtable"> <table border="0" cellspacing="0" cellpadding="0">
                      <tr>
                        <td>from:&nbsp;&nbsp;</td>
                        <td><select name="dstbeginport" class="formfld" onchange="dst_rep_change();ext_change()">
                            <option value="">(other)</option>
                            <option value="any" <?php $bfound = 0; if ($pconfig['dstbeginport'] == "any") { echo "selected"; $bfound = 1; } ?>>any</option>
                            <?php foreach ($wkports as $wkport => $wkportdesc): ?>
                            <option value="<?=$wkport;?>" <?php if ($wkport == $pconfig['dstbeginport']) {
																echo "selected";
																$bfound = 1;
															}?>>
                            <?=htmlspecialchars($wkportdesc);?>
                            </option>
                            <?php endforeach; ?>
                          </select> <input autocomplete='off' class="formfldalias" name="dstbeginport_cust" id="dstbeginport_cust" type="text" size="5" value="<?php if (!$bfound && $pconfig['dstbeginport']) echo $pconfig['dstbeginport']; ?>"></td>
                      </tr>
                      <tr>
                        <td>to:</td>
                        <td><select name="dstendport" class="formfld" onchange="ext_change()">
                            <option value="">(other)</option>
                            <option value="any" <?php $bfound = 0; if ($pconfig['dstendport'] == "any") { echo "selected"; $bfound = 1; } ?>>any</option>
                            <?php foreach ($wkports as $wkport => $wkportdesc): ?>
                            <option value="<?=$wkport;?>" <?php if ($wkport == $pconfig['dstendport']) {
																echo "selected";
																$bfound = 1;
															}?>>
                            <?=htmlspecialchars($wkportdesc);?>
                            </option>
                            <?php endforeach; ?>
                          </select> <input autocomplete='off' class="formfldalias" name="dstendport_cust" id="dstendport_cust" type="text" size="5" value="<?php if (!$bfound && $pconfig['dstendport']) echo $pconfig['dstendport']; ?>"></td>
                      </tr>
                    </table>
                    <br> <span class="vexpl">Specify the port or port range for
                    the destination of the packet for this rule.<br>
                    Hint: you can leave the <em>'to'</em> field empty if you only
                    want to filter a single port</span></td>
                <tr>
                  <td valign="top" class="vncell">Direction</td>
                  <td class="vtable"> <select name="direction" class="formfld">
                      <option value="" <?php if (!$pconfig['direction']) echo "selected"; ?>>any</option>
                      <option value="in" <?php if ($pconfig['direction'] == "in") echo "selected"; ?>>in</option>
                      <option value="out" <?php if ($pconfig['direction'] == "out") echo "selected"; ?>>out</option>
                    </select> <br>
                    Use this to match only packets travelling in a given direction
                    on the interface specified above (as seen from the firewall's
                    perspective). </td>
                </tr>
				<tr>
                  <td width="22%" valign="top" class="vncell">IP Type of Service (TOS)</td>
                  <td width="78%" class="vtable"> <table border="0" cellspacing="0" cellpadding="0">
                      <?php
				  $iniptos = explode(",", $pconfig['iptos']);
				  foreach ($iptos as $tos): $dontcare = true; ?>
                      <tr>
                        <td width="80" nowrap><strong>
			  <?echo $tos;?>
                          </strong></td>
                        <td nowrap> <input type="radio" name="iptos_<?=$tos;?>" value="on" <?php if (array_search($tos, $iniptos) !== false) { echo "checked"; $dontcare = false; }?>>
                          yes&nbsp;&nbsp;&nbsp;</td>
                        <td nowrap> <input type="radio" name="iptos_<?=$tos;?>" value="off" <?php if (array_search("!" . $tos, $iniptos) !== false) { echo "checked"; $dontcare = false; }?>>
                          no&nbsp;&nbsp;&nbsp;</td>
                        <td nowrap> <input type="radio" name="iptos_<?=$tos;?>" value="" <?php if ($dontcare) echo "checked";?>>
                          don't care</td>
                      </tr>
                      <?php endforeach; ?>
                    </table>
                    <span class="vexpl">Use this to match packets according to their IP TOS values.
                    </span></td>
                </tr>
                <tr>
                  <td width="22%" valign="top" class="vncell">TCP flags</td>
                  <td width="78%" class="vtable"> <table border="0" cellspacing="0" cellpadding="0">
                      <?php
				  $inflags = explode(",", $pconfig['tcpflags']);
				  foreach ($tcpflags as $tcpflag): $dontcare = true; ?>
                      <tr>
                        <td width="40" nowrap><strong>
                          <?=strtoupper($tcpflag);?>
                          </strong></td>
                        <td nowrap> <input type="radio" name="tcpflags_<?=$tcpflag;?>" value="on" <?php if (array_search($tcpflag, $inflags) !== false) { echo "checked"; $dontcare = false; }?>>
                          set&nbsp;&nbsp;&nbsp;</td>
                        <td nowrap> <input type="radio" name="tcpflags_<?=$tcpflag;?>" value="off" <?php if (array_search("!" . $tcpflag, $inflags) !== false) { echo "checked"; $dontcare = false; }?>>
                          cleared&nbsp;&nbsp;&nbsp;</td>
                        <td nowrap> <input type="radio" name="tcpflags_<?=$tcpflag;?>" value="" <?php if ($dontcare) echo "checked";?>>
                          don't care</td>
                      </tr>
                      <?php endforeach; ?>
                    </table>
                    <span class="vexpl">Use this to choose TCP flags that must
                    be set or cleared for this rule to match.</span></td>
                </tr>
                <tr>
                  <td width="22%" valign="top" class="vncell">Description</td>
                  <td width="78%" class="vtable"> <input name="descr" type="text" class="formfld" id="descr" size="40" value="<?=htmlspecialchars($pconfig['descr']);?>">
                    <br> <span class="vexpl">You may enter a description here
                    for your reference (not parsed).</span></td>
                </tr>
                <tr>
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%"> <input name="Submit" type="submit" class="formbtn" value="Kaydet"> <input type="button" class="formbtn" value="Vazgeç" onclick="history.back()">
                    <?php if (isset($id) && $a_shaper[$id]): ?>
                    <input name="id" type="hidden" value="<?=$id;?>">
                    <?php endif; ?>
					<input name="after" type="hidden" value="<?=$after;?>">
                  </td>
                </tr>
              </table>
</form>
<script language="JavaScript">
<!--
ext_change();
typesel_change();
proto_change();
-->
</script>
<?php else: ?>
<p><strong>Yeni bir kural eklemeden önce yeni bir kuyruk oluşturmanız gerekmektedir.</strong></p>
<?php endif; ?>
<?php
$isfirst = 0;
$aliases = "";
$addrisfirst = 0;
$aliasesaddr = "";
if(is_array($config['aliases']['alias'])) {
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
