<?php

$pgtitle = "Servisler:Captive portal";

require_once("guiconfig.inc");

if (!is_array($config['captiveportal']['element']))
	$config['captiveportal']['element'] = array();

cpelements_sort();
$a_element = &$config['captiveportal']['element'];

// Calculate total size of all files
$total_size = 0;
foreach ($a_element as $element) {
	$total_size += $element['size'];
}

if ($_POST) {
    unset($input_errors);

    if (is_uploaded_file($_FILES['new']['tmp_name'])) {

    	if(!stristr($_FILES['new']['name'], "captiveportal-"))
    		$name = "captiveportal-" . $_FILES['new']['name'];
    	else
    		$name = $_FILES['new']['name'];
    	$size = filesize($_FILES['new']['tmp_name']);

    	// is there already a file with that name?
    	foreach ($a_element as $element) {
			if ($element['name'] == $name) {
				$input_errors[] = "Bu isimde '$name' bir dosya zaten mevcut.";
				break;
			}
		}

		// check total file size
		if (($total_size + $size) > $g['captiveportal_element_sizelimit']) {
			$input_errors[] = "Tüm dosyaların toplam boyutu limiti geçmemelidir." .
				format_bytes($g['captiveportal_element_sizelimit']) . ".";
		}

		if (!$input_errors) {
			$element = array();
			$element['name'] = $name;
			$element['size'] = $size;
			$element['content'] = base64_encode(file_get_contents($_FILES['new']['tmp_name']));

			$a_element[] = $element;

			write_config();
			captiveportal_write_elements();
			header("Location: services_captiveportal_filemanager.php");
			exit;
		}
    }
} else {
	if (($_GET['act'] == "del") && $a_element[$_GET['id']]) {
		conf_mount_rw();
		unlink_if_exists($g['captiveportal_path'] . "/" . $a_element[$id]['name']);
		unset($a_element[$_GET['id']]);
		write_config();
		captiveportal_write_elements();
		conf_mount_ro();
		header("Location: services_captiveportal_filemanager.php");
		exit;
	}
}

include("head.inc");

?>
<?php include("fbegin.inc"); ?>
<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<p class="pgtitle"><?=$pgtitle?></p>
<form action="services_captiveportal_filemanager.php" method="post" enctype="multipart/form-data" name="iform" id="iform">
<?php if ($input_errors) print_input_errors($input_errors); ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr><td class="tabnavtbl">
<?php
	$tab_array = array();
	$tab_array[] = array("Hotspot", false, "services_captiveportal.php");
	$tab_array[] = array("MAC üzerinden geçiş", false, "services_captiveportal_mac.php");
	$tab_array[] = array("İzinli IP Adresleri", false, "services_captiveportal_ip.php");
	$tab_array[] = array("Kullanıcılar", false, "services_captiveportal_users.php");
	$tab_array[] = array("Dosya yöneticisi", true, "services_captiveportal_filemanager.php");
	display_top_tabs($tab_array);
?>  </td></tr>
  <tr>
    <td class="tabcont">
	<table width="80%" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td width="70%" class="listhdrr">İsim</td>
        <td width="20%" class="listhdr">Boyut</td>
        <td width="10%" class="list">
		<table border="0" cellspacing="0" cellpadding="1">
		    <tr>
			<td width="17" heigth="17"></td>
			<td><a href="services_captiveportal_filemanager.php?act=add"><img src="/themes/<?php echo $g['theme']; ?>/images/icons/icon_plus.gif" title="add file" width="17" height="17" border="0"></a></td>
		    </tr>
		</table>
	</td>
      </tr>
  <?php $i = 0; foreach ($a_element as $element): ?>
  	  <tr>
		<td class="listlr"><?=htmlspecialchars($element['name']);?></td>
		<td class="listr" align="right"><?=format_bytes($element['size']);?></td>
		<td valign="middle" nowrap class="list">
		<a href="services_captiveportal_filemanager.php?act=del&id=<?=$i;?>" onclick="return confirm('Bu dosyası silmek istediğinizden emin misiniz?')"><img src="/themes/<?php echo $g['theme']; ?>/images/icons/icon_x.gif" title="delete file" width="17" height="17" border="0"></a>
		</td>
	  </tr>
  <?php $i++; endforeach; ?>

  <?php if (count($a_element) > 0): ?>
  	  <tr>
		<td class="listlr" style="background-color: #eee"><strong>TOPLAM</strong></td>
		<td class="listr" style="background-color: #eee" align="right"><strong><?=format_bytes($total_size);?></strong></td>
		<td valign="middle" nowrap class="list"></td>
	  </tr>
  <?php endif; ?>

  <?php if ($_GET['act'] == 'add'): ?>
	  <tr>
		<td class="listlr" colspan="2"><input type="file" name="new" class="formfld" size="40" id="new">
		<input name="Submit" type="submit" class="formbtn" value="Yükle"></td>
		<td valign="middle" nowrap class="list">
		<a href="services_captiveportal_filemanager.php"><img src="/themes/<?php echo $g['theme']; ?>/images/icons/icon_x.gif" title="cancel" width="17" height="17" border="0"></a>
		</td>
	  </tr>
  <?php else: ?>
	  <tr>
		<td class="list" colspan="2"></td>
		<td class="list">
			<table border="0" cellspacing="0" cellpadding="1">
			    <tr>
				<td width="17" heigth="17"></td>
				<td><a href="services_captiveportal_filemanager.php?act=add"><img src="/themes/<?php echo $g['theme']; ?>/images/icons/icon_plus.gif" title="add file" width="17" height="17" border="0"></a></td>
			    </tr>
			</table>
		</td>
	  </tr>
  <?php endif; ?>
	</table>
	<span class="vexpl"><span class="red"><strong>
	Bilgi:<br>
	</strong></span>
	Herhangi bir isimde bir dosya sunucuya yüklenebilir. Bu dosya sunucunun ana dizininde bulunacaktır.
	Yüklediniz kendi kodunuzdak dosyayı herhangi bir yol belirtmeden kullanabilirsiniz.	
	Örnek: HTML kodumuz içinde 'test.jpg' isimli bir dosya olduğunu varsayalım bu dosyayı yükledikten sonra
	HTML kon içinde aşağıdaki şekilde kullanabiliriz:<br><br>
	<tt>&lt;img src=&quot;test.jpg&quot; width=... height=...&gt;</tt>
	<br><br>	
	<br><br>
	<tt>&lt;a href="/captiveportal-aup.php?redirurl=$PORTAL_REDIRURL$"&gt;Acceptable usage policy&lt/a&gt;</tt>
	<br><br>
	Dosya yükleme sınırı <?=format_bytes($g['captiveportal_element_sizelimit']);?>.</span>
</td>
</tr>
</table>
</form>
<?php include("fend.inc"); ?>
</body>
</html>
