<?php 

$pgtitle = "Services:Hotspot:Geçiş izni verilen MAC adreslerini düzenle";
require("guiconfig.inc");

if (!is_array($config['captiveportal']['passthrumac']))
	$config['captiveportal']['passthrumac'] = array();

passthrumacs_sort();
$a_passthrumacs = &$config['captiveportal']['passthrumac'];

$id = $_GET['id'];
if (isset($_POST['id']))
	$id = $_POST['id'];

if (isset($id) && $a_passthrumacs[$id]) {
	$pconfig['mac'] = $a_passthrumacs[$id]['mac'];
	$pconfig['descr'] = $a_passthrumacs[$id]['descr'];
}

if ($_POST) {

	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	$reqdfields = explode(" ", "mac");
	$reqdfieldsn = explode(",", "MAC address");
	
	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
	
	$_POST['mac'] = str_replace("-", ":", $_POST['mac']);
	
	if (($_POST['mac'] && !is_macaddr($_POST['mac']))) {
		$input_errors[] = "Geçerli bir MAC adresi tanımlanmalıdır. [".$_POST['mac']."]";
	}

	foreach ($a_passthrumacs as $macent) {
		if (isset($id) && ($a_passthrumacs[$id]) && ($a_passthrumacs[$id] === $macent))
			continue;
		
		if ($macent['mac'] == $_POST['mac']){
			$input_errors[] = "[" . $_POST['mac'] . "] zaten izin verilmiş." ;
			break;
		}	
	}

	if (!$input_errors) {
		$mac = array();
		$mac['mac'] = $_POST['mac'];
		$mac['descr'] = $_POST['descr'];

		if (isset($id) && $a_passthrumacs[$id])
			$a_passthrumacs[$id] = $mac;
		else
			$a_passthrumacs[] = $mac;
		
		write_config();

		touch($d_passthrumacsdirty_path) ;
		
		header("Location: services_captiveportal_mac.php");
		exit;
	}
}
include("head.inc");
?>
<?php include("fbegin.inc"); ?>
<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<p class="pgtitle"><?=$pgtitle?></p>
<?php if ($input_errors) print_input_errors($input_errors); ?>
            <form action="services_captiveportal_mac_edit.php" method="post" name="iform" id="iform">
              <table width="100%" border="0" cellpadding="6" cellspacing="0">
				<tr>
                  <td width="22%" valign="top" class="vncellreq">MAC adresi</td>
                  <td width="78%" class="vtable"> 
                    <?=$mandfldhtml;?><input name="mac" type="text" class="formfld" id="mac" size="17" value="<?=htmlspecialchars($pconfig['mac']);?>">
                    <br> 
                    <span class="vexpl">MAC adresi (6 adet hex sayı iki nokta üstüste ile birleştirerek)</span></td>
                </tr>
				<tr>
                  <td width="22%" valign="top" class="vncell">Açıklama</td>
                  <td width="78%" class="vtable"> 
                    <input name="descr" type="text" class="formfld" id="descr" size="40" value="<?=htmlspecialchars($pconfig['descr']);?>">
                    <br> <span class="vexpl">Referans için bu alana açıklama girilebilir.</span></td>
                </tr>
                <tr>
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%"> 
                    <input name="Submit" type="submit" class="formbtn" value="Kaydet">
                    <?php if (isset($id) && $a_passthrumacs[$id]): ?>
                    <input name="id" type="hidden" value="<?=$id;?>">
                    <?php endif; ?>
                  </td>
                </tr>
              </table>
</form>
<?php include("fend.inc"); ?>
</body>
</html>
