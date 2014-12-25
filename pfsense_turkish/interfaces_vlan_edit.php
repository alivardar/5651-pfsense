<?php
/* $Id$ */
/*
	interfaces_vlan_edit.php
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

if (!is_array($config['vlans']['vlan']))
	$config['vlans']['vlan'] = array();

$a_vlans = &$config['vlans']['vlan'];

$portlist = get_interface_list();

$id = $_GET['id'];
if (isset($_POST['id']))
	$id = $_POST['id'];

if (isset($id) && $a_vlans[$id]) {
	$pconfig['if'] = $a_vlans[$id]['if'];
	$pconfig['tag'] = $a_vlans[$id]['tag'];
	$pconfig['descr'] = $a_vlans[$id]['descr'];
}

if ($_POST) {

	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	$reqdfields = explode(" ", "if tag");
	$reqdfieldsn = explode(",", "Parent interface,VLAN tag");

	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

	if ($_POST['tag'] && (!is_numericint($_POST['tag']) || ($_POST['tag'] < '1') || ($_POST['tag'] > '4094'))) {
		$input_errors[] = "VLAN etiketi sayı olarak 1 ile 4094 arasında olmalıdır.";
	}

	foreach ($a_vlans as $vlan) {
		if (isset($id) && ($a_vlans[$id]) && ($a_vlans[$id] === $vlan))
			continue;

		if (($vlan['if'] == $_POST['if']) && ($vlan['tag'] == $_POST['tag'])) {
			$input_errors[] = "Tanımlanmaya çalışılan VLAN {$vlan['tag']} zaten tanımlıdır.";
			break;
		}
	}

	if (!$input_errors) {
		$vlan = array();
		$vlan['if'] = $_POST['if'];
		$vlan['tag'] = $_POST['tag'];
		$vlan['descr'] = $_POST['descr'];

		if (isset($id) && $a_vlans[$id])
			$a_vlans[$id] = $vlan;
		else
			$a_vlans[] = $vlan;

		write_config();

		/* TODO sometime post-1.2 release
		this does not always work, some systems require
		a reboot before VLANs function properly
		
		This portion of code is also very slow, this is why
		it takes a long time to add a new VLAN.
		Benchmark_Timer on a 800 MHz VIA: 
			interfaces_lan_configure() takes about 6 seconds 
			interfaces_wan_configure() takes about 9.5 seconds
			interfaces_optional_configure() takes about 5 seconds
		*/
		
		mwexec("touch /tmp/vlanchanged");
		
		interfaces_vlan_configure();
		interfaces_lan_configure();
		interfaces_wan_configure();
		interfaces_optional_configure();

		header("Location: interfaces_vlan.php");
		exit;
	}
}

$pgtitle = "Firewall: VLAN: Düzenle";
include("head.inc");

?>

<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<?php include("fbegin.inc"); ?>
<p class="pgtitle"><?=$pgtitle?></p>
<?php if ($input_errors) print_input_errors($input_errors); ?>
            <form action="interfaces_vlan_edit.php" method="post" name="iform" id="iform">
              <table width="100%" border="0" cellpadding="6" cellspacing="0">
				<tr>
                  <td width="22%" valign="top" class="vncellreq">Sahip ağ aygıtı</td>
                  <td width="78%" class="vtable">
                    <select name="if" class="formfld">
                      <?php
					  foreach ($portlist as $ifn => $ifinfo)
						if (is_jumbo_capable($ifn)) {
							echo "<option value=\"{$ifn}\"";
							if ($ifn == $pconfig['if'])
								echo "selected";
							echo ">";
                      				        echo htmlspecialchars($ifn . " (" . $ifinfo['mac'] . ")");
                      					echo "</option>";
						}
		      ?>
                    </select>
			<br/>
			<span class="vexpl">Sadece VLAN destekli ag aygıtları gözükecektir.</span></td>
                </tr>
				<tr>
                  <td valign="top" class="vncellreq">VLAN etiketi </td>
                  <td class="vtable">
                    <input name="tag" type="text" class="formfld" id="tag" size="6" value="<?=htmlspecialchars($pconfig['tag']);?>">
                    <br>
                    <span class="vexpl">802.1Q VLAN etiketi (1 ile 4094 arasında olmalıdır) </span></td>
			    </tr>
				<tr>
                  <td width="22%" valign="top" class="vncell">Açıklama</td>
                  <td width="78%" class="vtable">
                    <input name="descr" type="text" class="formfld" id="descr" size="40" value="<?=htmlspecialchars($pconfig['descr']);?>">
                    <br> <span class="vexpl">Bu alana referans olması için bir açıklama yazılabilir.
                    </span></td>
                </tr>
                <tr>
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%">
                    <input name="Submit" type="submit" class="formbtn" value="Kaydet"> <input type="button" value="Vazgeç" onclick="history.back()">
                    <?php if (isset($id) && $a_vlans[$id]): ?>
                    <input name="id" type="hidden" value="<?=$id;?>">
                    <?php endif; ?>
                  </td>
                </tr>
              </table>
</form>
<?php include("fend.inc"); ?>
</body>
</html>
