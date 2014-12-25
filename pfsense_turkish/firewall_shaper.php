<?php
/* $Id$ */
/*
    firewall_shaper.php
    Copyright (C) 2004, 2005 Scott Ullrich
    All rights reserved.

    Originally part of m0n0wall (http://m0n0.ch/wall)
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

/* redirect to wizard if shaper isn't already configured */
if(isset($config['shaper']['enable'])) {
	$pconfig['enable'] = TRUE;
} else {
	if(!is_array($config['shaper']['queue']))
		Header("Location: wizard.php?xml=traffic_shaper_wizard.xml");
}

if (!is_array($config['shaper']['rule'])) {
	$config['shaper']['rule'] = array();
}

if (!is_array($config['shaper']['queue'])) {
	$config['shaper']['queue'] = array();
}

$a_shaper = &$config['shaper']['rule'];
$a_queue = &$config['shaper']['queue'];

function wipe_magic () {
  global $config;

  /* wipe previous */
  unset($config['shaper']['queue']);
  unset($config['shaper']['rule']);
  $config['shaper']['enable'] = FALSE;
}

if ($_POST['remove'] or $_GET['remove']) {
  wipe_magic();
  $savemsg = '<p><span class="red"><strong>Bilgi: Trafik sınırlandırıcı etkin değildir.</strong></span><strong><br>';
  touch($d_shaperconfdirty_path);
  unset($config['shaper']['enable']);
  write_config();
  filter_configure();
  Header("Location: index.php");
  exit;
}

if ($_POST) {

	if ($_POST['submit']) {
		$pconfig = $_POST;
		$config['shaper']['enable'] = $_POST['enable'] ? true : false;
		write_config();
	}

	if ($_POST['apply'] || $_POST['submit']) {
		$config['shaper']['enable'] = $_POST['enable'] ? true : false;
		write_config();		
		$retval = 0;
		$savemsg = get_std_save_message($retval);
		/* Setup pf rules since the user may have changed the optimization value */
		config_lock();
		$retval = filter_configure();
		config_unlock();
		if(stristr($retval, "error") <> true)
		    $savemsg = get_std_save_message($retval);
		else
		    $savemsg = $retval;

	        if(file_exists($d_shaperconfdirty_path))
	          unlink($d_shaperconfdirty_path);
	}
}

if (isset($_POST['del_x'])) {
        /* delete selected rules */
        if (is_array($_POST['rule']) && count($_POST['rule'])) {
                foreach ($_POST['rule'] as $rulei) {
                        unset($a_shaper[$rulei]);
                }
                write_config();
                touch($d_natconfdirty_path);
                header("Location: firewall_shaper.php");
                exit;
        }
}


if ($_GET['act'] == "down") {
	if ($a_shaper[$_GET['id']] && $a_shaper[$_GET['id']+1]) {
		$tmp = $a_shaper[$_GET['id']+1];
		$a_shaper[$_GET['id']+1] = $a_shaper[$_GET['id']];
		$a_shaper[$_GET['id']] = $tmp;
		write_config();
		touch($d_shaperconfdirty_path);
		header("Location: firewall_shaper.php");
		exit;
	}
} else if ($_GET['act'] == "up") {
	if (($_GET['id'] > 0) && $a_shaper[$_GET['id']]) {
		$tmp = $a_shaper[$_GET['id']-1];
		$a_shaper[$_GET['id']-1] = $a_shaper[$_GET['id']];
		$a_shaper[$_GET['id']] = $tmp;
		write_config();
		touch($d_shaperconfdirty_path);
		header("Location: firewall_shaper.php");
		exit;
	}
} else if ($_GET['act'] == "toggle") {
	if ($a_shaper[$_GET['id']]) {
		$a_shaper[$_GET['id']]['disabled'] = !isset($a_shaper[$_GET['id']]['disabled']);
		write_config();
		touch($d_shaperconfdirty_path);
		header("Location: firewall_shaper.php");
		exit;
	}
} else {
	/* yuck - IE won't send value attributes for image buttons, while Mozilla does -
	   so we use .x/.y to fine move button clicks instead... */
	unset($movebtn);
	foreach ($_POST as $pn => $pd) {
		if (preg_match("/move_(\d+)_x/", $pn, $matches)) {
			$movebtn = $matches[1];
			break;
		}
	}
	/* move selected rules before this rule */
	if (isset($movebtn) && is_array($_POST['rule']) && count($_POST['rule'])) {
		$a_shaper_new = array();

		/* copy all rules < $movebtn and not selected */
		for ($i = 0; $i < $movebtn; $i++) {
			if (!in_array($i, $_POST['rule']))
				$a_shaper_new[] = $a_shaper[$i];
		}

		/* copy all selected rules */
		for ($i = 0; $i < count($a_shaper); $i++) {
			if ($i == $movebtn)
				continue;
			if (in_array($i, $_POST['rule']))
				$a_shaper_new[] = $a_shaper[$i];
		}

		/* copy $movebtn rule */
		if ($movebtn < count($a_shaper))
			$a_shaper_new[] = $a_shaper[$movebtn];

		/* copy all rules > $movebtn and not selected */
		for ($i = $movebtn+1; $i < count($a_shaper); $i++) {
			if (!in_array($i, $_POST['rule']))
				$a_shaper_new[] = $a_shaper[$i];
		}

		$a_shaper = $a_shaper_new;
		write_config();
		touch($d_shaperconfdirty_path);
		header("Location: firewall_shaper.php");
		exit;
	}
}

$pgtitle = "Firewall: Sınırlandırıcı: Kurallar";
include("head.inc");

?>

<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<?php include("fbegin.inc"); ?>
<p class="pgtitle"><?=$pgtitle?></p>
<form action="firewall_shaper.php" method="post" name="iform">
<script type="text/javascript" language="javascript" src="row_toggle.js">
</script>
<?php if ($savemsg) print_info_box($savemsg); ?>
<?php if (file_exists($d_shaperconfdirty_path)): ?><p>
<?php print_info_box_np("The traffic shaper configuration has been changed.<br>You must apply the changes in order for them to take effect.");?><br>
<?php endif; ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr><td>
<?php
	$tab_array = array();
	$tab_array[0] = array("Rules", true, "firewall_shaper.php");
	$tab_array[1] = array("Queues", false, "firewall_shaper_queues.php");
	$tab_array[2] = array("EZ Shaper wizard", false, "wizard.php?xml=traffic_shaper_wizard.xml");
	display_top_tabs($tab_array);
?>  
  </td></tr>
  <tr>
    <td>
	<div id="mainarea">
              <table class="tabcont" width="100%" border="0" cellpadding="6" cellspacing="0">
                <tr>
                  <td class="vtable"><p>
                      <input name="enable" type="checkbox" id="enable" value="yes" <?php if ($pconfig['enable'] == "yes") echo "checked";?>>
                      <strong>Trafik sınırlandırmayı etkinleştir<br>
                      </strong></p></td>
                </tr>
                <tr>
                  <td> 
		  <input name="submit" type="submit" class="formbtn" value="Kaydet"> 
		  <input name="remove" type="submit" class="formbtn" id="remove" value="Remove Wizard"> 
                  </td>
		  
                </tr>
              </table>
              <table class="tabcont" width="100%" border="0" cellpadding="0" cellspacing="0">
                      <tr id="frheader">
		        <td width="3%" class="list">&nbsp;</td>
                        <td width="3%" class="list">&nbsp;</td>
                        <td width="5%" class="listhdrrns">If</td>
                        <td width="5%" class="listhdrrns">Proto</td>
                        <td width="20%" class="listhdrr">Kaynak</td>
                        <td width="20%" class="listhdrr">Yön</td>
                        <td width="15%" class="listhdrrns">Hedef</td>
                        <td width="25%" class="listhdr">Açıklama</td>
                        <td width="10%" class="list"></td>
                      </tr>
                      <?php $nrules = $i = 0; foreach ($a_shaper as $shaperent): ?>
                      <tr valign="top" id="fr<?=$nrules;?>">
                        <td class="listt"><input type="checkbox" id="frc<?=$nrules;?>" name="rule[]" value="<?=$i;?>" onClick="fr_bgcolor('<?=$nrules;?>')" style="margin: 0; padding: 0; width: 15px; height: 15px;"></td>
                        <td class="listt" align="center"></td>
                        <td class="listlr" onClick="fr_toggle(<?=$nrules;?>)" id="frd<?=$nrules;?>" ondblclick="document.location='firewall_shaper_edit.php?id=<?=$i;?>';">
                          <?php
				  $dis = "";
				  if (isset($shaperent['disabled'])) {
				  	$dis = "_d";
					$textss = "<span class=\"gray\">";
					$textse = "</span>";
				  } else {
				  	$textss = $textse = "";
				  }
				  $iflabels = array('lan' => 'LAN', 'wan' => 'WAN', 'pptp' => 'PPTP');
				  for ($j = 1; isset($config['interfaces']['opt' . $j]); $j++)
				  	$iflabels['opt' . $j] = $config['interfaces']['opt' . $j]['descr'];
				  echo $textss . htmlspecialchars($iflabels[$shaperent['in-interface']]) . "->" . htmlspecialchars($iflabels[$shaperent['out-interface']]);

				  echo "<br>";
				  echo "<a href=\"?act=toggle&id={$i}\">";
				  if ($shaperent['direction'] == "in")
				  	echo "<img src=\"./themes/".$g['theme']."/images/icons/icon_in{$dis}.gif\" width=\"11\" height=\"11\" border=\"0\" style=\"margin-top: 5px\" title=\"click to toggle enabled/disabled status\">";
				  if ($shaperent['direction'] == "out")
				  	echo "<img src=\"./themes/".$g['theme']."/images/icons/icon_out{$dis}.gif\" width=\"11\" height=\"11\" border=\"0\" style=\"margin-top: 5px\" title=\"click to toggle enabled/disabled status\">";

				  echo "</a>" . $textse;;
				  ?>
                        </td>
                        <td class="listr" onClick="fr_toggle(<?=$nrules;?>)" id="frd<?=$nrules;?>" ondblclick="document.location='firewall_shaper_edit.php?id=<?=$i;?>';">
                          <?=$textss;?><?php if (isset($shaperent['protocol'])) echo strtoupper($shaperent['protocol']); else echo "*"; ?><?=$textse;?>
                        </td>
                        <td class="listr" onClick="fr_toggle(<?=$nrules;?>)" id="frd<?=$nrules;?>" ondblclick="document.location='firewall_shaper_edit.php?id=<?=$i;?>';"><?=$textss;?><?php echo htmlspecialchars(pprint_address($shaperent['source'])); ?>
						<?php if ($shaperent['source']['port']): ?><br>
						Port: <?=htmlspecialchars(pprint_port($shaperent['source']['port'])); ?>
						<?php endif; ?><?=$textse;?>
                        </td>
                        <td class="listr" onClick="fr_toggle(<?=$nrules;?>)" id="frd<?=$nrules;?>" ondblclick="document.location='firewall_shaper_edit.php?id=<?=$i;?>';"><?=$textss;?><?php echo htmlspecialchars(pprint_address($shaperent['destination'])); ?>
						<?php if ($shaperent['destination']['port']): ?><br>
						Port: <?=htmlspecialchars(pprint_port($shaperent['destination']['port'])); ?>
						<?php endif; ?><?=$textse;?>
                        </td>
                        <td class="listr" onClick="fr_toggle(<?=$nrules;?>)" id="frd<?=$nrules;?>" ondblclick="document.location='firewall_shaper_edit.php?id=<?=$i;?>';"><?=$textss;?>
                          <?php
							if (isset($shaperent['outqueue']) && isset($shaperent['inqueue'])) {
								$desc = htmlspecialchars($shaperent['outqueue']);
							    echo "<a href=\"firewall_shaper_queues_edit.php?id={$shaperent['outqueue']}\">{$desc}</a>";
								$desc = htmlspecialchars($shaperent['inqueue']);
							    echo "/<a href=\"firewall_shaper_queues_edit.php?id={$shaperent['inqueue']}\">{$desc}</a>";
							}
						  ?><?=$textse;?>
                        </td>
                        <td class="listbg" onClick="fr_toggle(<?=$nrules;?>)" ondblclick="document.location='firewall_shaper_edit.php?id=<?=$i;?>';"><font color="white">
                          <?=$textss;?><?=htmlspecialchars($shaperent['descr']);?><?=$textse;?>
                          &nbsp; </td>
                        <td valign="middle" nowrap class="list"> <a href="firewall_shaper_edit.php?id=<?=$i;?>"><img src="./themes/<?= $g['theme']; ?>/images/icons/icon_e.gif" title="edit rule" width="17" height="17" border="0"></a>
                          <?php if ($i > 0): ?>
                          <a href="firewall_shaper.php?act=up&id=<?=$i;?>"><img src="./themes/<?= $g['theme']; ?>/images/icons/icon_up.gif" title="move up" width="17" height="17" border="0"></a>
                          <?php else: ?>
                          <img src="./themes/<?= $g['theme']; ?>/images/icons/icon_up_d.gif" width="17" height="17" border="0">
                          <?php endif; ?>
			  <input name="move_<?=$i;?>" type="image" src="./themes/<?= $g['theme']; ?>/images/icons/icon_left.gif" width="17" height="17" title="move selected rules before this rule" onMouseOver="fr_insline(<?=$nrules;?>, true)" onMouseOut="fr_insline(<?=$nrules;?>, false)"><br>
			  
			  <input name="del" type="image" src="./themes/<?= $g['theme']; ?>/images/icons/icon_x.gif" width="17" height="17" title="delete selected mappings" onclick="return confirm('Do you really want to delete the selected mappings?')">
			  
                          <?php if (isset($a_shaper[$i+1])): ?>
                          <a href="firewall_shaper.php?act=down&id=<?=$i;?>"><img src="./themes/<?= $g['theme']; ?>/images/icons/icon_down.gif" title="move down" width="17" height="17" border="0"></a>
                          <?php else: ?>
                          <img src="./themes/<?= $g['theme']; ?>/images/icons/icon_down_d.gif" width="17" height="17" border="0">
                          <?php endif; ?>
                          <a href="firewall_shaper_edit.php?dup=<?=$i;?>"><img src="./themes/<?= $g['theme']; ?>/images/icons/icon_plus.gif" title="add a new rule based on this one" width="17" height="17" border="0"></a>
                        </td>
                      </tr>
                      <?php $nrules++; $i++; endforeach; ?>
                      <tr>
                        <td class="list" colspan="8"></td>
                        <td class="list"> <a href="firewall_shaper_edit.php"><img src="./themes/<?= $g['theme']; ?>/images/icons/icon_plus.gif" width="17" height="17" border="0"></a></td>
                      </tr>
		      <tr>
		    <td colspan="8"><p><span class="red"><strong>Bilgi:</strong></span><strong><br>
                    </strong>The first rule that matches a packet will be executed.<br>
                    The following match patterns are not shown in the list above:
                    IP packet length, TCP flags.<br>
                    You can check the results of your queues at <a href="status_queues.php">Durum:Queues</a>.</td>
		    </tr>
                    </table>
		</div>
	  </td>
	</tr>
</table>
            </form>
<?php include("fend.inc"); ?>
</body>
</html>
