<?php
/* $Id$ */
/*
	status_rrd_graph.php
	Part of pfSense
	Copyright (C) 2007 Seth Mos <seth.mos@xs4all.nl>
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

$pconfig['enable'] = isset($config['rrd']['enable']);
$pconfig['category'] = $config['rrd']['category'];
$pconfig['style'] = $config['rrd']['style'];

$curcat = "settings";
$categories = array('system' => 'System',
		'traffic' => 'Traffic',
		'packets' => 'Packets',
		'quality' => 'Quality',
		'queues' => 'Queues');
$styles = array('inverse' => 'Inverse',
		'absolute' => 'Absolute');

if ($_POST) {

	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	/* none */

        if (!$input_errors) {
                $config['rrd']['enable'] = $_POST['enable'] ? true : false;
                $config['rrd']['category'] = $_POST['category'];
                $config['rrd']['style'] = $_POST['style'];
                write_config();

                $retval = 0;
                config_lock();
                $retval = enable_rrd_graphing();
                config_unlock();

                $savemsg = get_std_save_message($retval);
	}
}

$pgtitle = gettext("Durum: RRD Grafikleri");
include("head.inc");

?>
<script src="/javascript/scriptaculous/prototype.js" type="text/javascript"></script>
<script src="/javascript/scriptaculous/scriptaculous.js" type="text/javascript"></script>
<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<?php include("fbegin.inc"); ?>
<p class="pgtitle"><?=$pgtitle?></p>
<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>
<form action="status_rrd_graph_settings.php" method="post" name="iform" id="iform">
<table width="100%" border="0" cellpadding="0" cellspacing="0">
        <tr>
                <td>
			<?php
			        $tab_array = array();
				if($curcat == "system") { $tabactive = True; } else { $tabactive = False; }
			        $tab_array[] = array("System", $tabactive, "status_rrd_graph.php?cat=system");
				if($curcat == "traffic") { $tabactive = True; } else { $tabactive = False; }
			        $tab_array[] = array("Traffic", $tabactive, "status_rrd_graph.php?cat=traffic");
				if($curcat == "packets") { $tabactive = True; } else { $tabactive = False; }
			        $tab_array[] = array("Packets", $tabactive, "status_rrd_graph.php?cat=packets");
				if($curcat == "quality") { $tabactive = True; } else { $tabactive = False; }
			        $tab_array[] = array("Quality", $tabactive, "status_rrd_graph.php?cat=quality");
				if($curcat == "queues") { $tabactive = True; } else { $tabactive = False; }
			        $tab_array[] = array("Queues", $tabactive, "status_rrd_graph.php?cat=queues");
				if($curcat == "settings") { $tabactive = True; } else { $tabactive = False; }
			        $tab_array[] = array("Settings", $tabactive, "status_rrd_graph_settings.php?cat=settings");
			        display_top_tabs($tab_array);
			?>
                </td>
        </tr>
        <tr>
                <td>
                        <div id="mainarea">
                        <table class="tabcont" width="100%" border="0" cellspacing="0" cellpadding="6">
			<tr>
				<td width="22%" valign="top" class="vtable">RRD Grafikleri</td>
				<td width="78%" class="vtable">
					<input name="enable" type="checkbox" id="enable" value="yes" <?php if ($pconfig['enable']) echo "checked"; ?> onClick="enable_change(false)">
					<b><?=gettext("RRD grafikleri geri planda aktifleştir.");?></b>
				</td>
			</tr>
			<tr>
                        	<td width="22%" valign="top" class="vtable">Varsayılan Kategori</td>
	                        <td width="78%" class="vtable">
					<select name="category" id="category" class="formselect" style="z-index: -10;" >
					<?php
					foreach ($categories as $category => $categoryd) {
						echo "<option value=\"$category\"";
						if ($category == $pconfig['category']) echo " selected";
						echo ">" . htmlspecialchars($categoryd) . "</option>\n";
					}
					?>
					</select>
					<b><?=gettext("Bu seçilenler varsayılan kategoridir.");?></b>
				</td>
			</tr>
			<tr>
                        	<td width="22%" valign="top" class="vtable">Varsayılan Sitil</td>
	                        <td width="78%" class="vtable">
					<select name="style" class="formselect" style="z-index: -10;" >
					<?php
					foreach ($styles as $style => $styled) {
						echo "<option value=\"$style\"";
						if ($style == $pconfig['style']) echo " selected";
						echo ">" . htmlspecialchars($styled) . "</option>\n";
					}
					?>
					</select>
					<b><?=gettext("Bu seçilen varsayılan sitildir.");?></b>
				</td>
			</tr>
			<tr>
				<td width="22%" valign="top">&nbsp;</td>
				<td width="78%">
					<input name="Submit" type="submit" class="formbtn" value="Save" onclick="enable_change(true)">
				</td>
			</tr>
			<tr>
				<td width="22%" height="53" valign="top">&nbsp;</td>
				<td width="78%"><strong><span class="red">Bilgi:</span></strong><br>
					<?=gettext("Grafikler 
					Grafikler bir 1 dakikalık zaman aralığı içinde yeniden çalışmayacaktır, lütfen
					stil değişimi yaptığınızda bunu dikkate alınız.");?>
				</td>
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

