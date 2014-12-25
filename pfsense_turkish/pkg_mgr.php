<?php
/* $Id$ */
/*
    pkg_mgr.php
    Copyright (C) 2004, 2005 Scott Ullrich
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

require_once("globals.inc");
require_once("guiconfig.inc");
require_once("pkg-utils.inc");

$pkg_info = get_pkg_info('all', array('noembedded', 'name', 'category', 'website', 'version', 'status', 'descr', 'maintainer', 'required_version', 'pkginfolink'));
if($pkg_info) {
	$fout = fopen("{$g['tmp_path']}/pkg_info.cache", "w");
	fwrite($fout, serialize($pkg_info));
	fclose($fout);
    //$pkg_sizes = get_pkg_sizes();
} else {
	$using_cache = true;
	if(file_exists("{$g['tmp_path']}/pkg_info.cache")) {
	    $savemsg = "Unable to retrieve package info from {$g['xmlrpcbaseurl']}. Cached data will be used.";
		$pkg_info = unserialize(@file_get_contents("{$g['tmp_path']}/pkg_info.cache"));
	} else {
		$savemsg = "Unable to communicate to {$g['product_name']}.com.  Please check DNS, default gateway, etc.";
	}
}

if (! empty($_GET)) {
  if (isset($_GET['ver'])) {
    $requested_version = htmlspecialchars($_GET['ver']);
  }
}

$pgtitle = "System: Package Manager";
include("head.inc");

?>

<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<?php
include("fbegin.inc");
?>
<p class="pgtitle"><?=$pgtitle?></p>
<?php if ($savemsg) print_info_box($savemsg); ?>
<?php

?>

<table width="100%" border="0" cellpadding="0" cellspacing="0">  <tr><td>
<?php
	$version = file_get_contents("/etc/version");
  $dot = strpos($version, ".");
  $hyphen = strpos($version, "-");
  $major = substr($version, 0, $dot);
  $minor = substr($version, $dot + 1, $hyphen - $dot - 1);
  $testing_version = substr($version, $hyphen + 1, strlen($version) - $hyphen);

	$tab_array = array();
//	$tab_array[] = array("{$version} packages", $requested_version <> "" ? false : true, "pkg_mgr.php");
	$tab_array[] = array("Packages for any platform", $requested_version == "none" ? true : false, "pkg_mgr.php?ver=none");
/*  $tab_array[] = array("Packages with a different version", $requested_version == "other" ? true : false, "pkg_mgr.php?ver=other"); */
	$tab_array[] = array("Installed Packages", false, "pkg_mgr_installed.php");
	display_top_tabs($tab_array);
?>
  </td></tr>
  <tr>
    <td>
	<div id="mainarea">
              <table class="tabcont" width="100%" border="0" cellpadding="6" cellspacing="0">
                <tr>
                  <td width="10%" class="listhdrr">Package Name</td>
                  <td width="25%" class="listhdrr">Category</td>
<!--
		  <td width="10%" class="listhdrr">Size</td>
-->
		  <td width="5%" class="listhdrr">Status</td>
		  <td width="5%" class="listhdrr">Package Info</td>
                  <td width="50%" class="listhdr">Description</td>
                </tr>

		<?php
		 if(!$pkg_info) {
			echo "<tr><td colspan=\"5\"><center>There are currently no packages available for installation.</td></tr>";
		 } else {
			$pfSense_installed_version = rtrim(file_get_contents("/etc/version"));
		 	$pkgs = array();
		 	$instpkgs = array();
		    if($config['installedpackages']['package'] != "")
			foreach($config['installedpackages']['package'] as $instpkg) $instpkgs[] = $instpkg['name'];
		    $pkg_names = array_keys($pkg_info);
		    $pkg_keys = array();
		    foreach($pkg_names as $name) {
				if(!in_array($name, $instpkgs)) 
					$pkg_keys[] = $name;
		    }
		    sort($pkg_keys);
		    if(count($pkg_keys) != 0) {
		    	foreach($pkg_keys as $key) {
			    $index = &$pkg_info[$key];
			    if(in_array($index['name'], $instpkgs)) continue;
          		$dot = strpos($index['required_version'], ".");
          		$index['major_version'] = substr($index['required_version'], 0, $dot);
				if($g['platform'] == "nanobsd") 
					if($index['noembedded']) 
						continue;
          		if ($version <> "HEAD" &&
              		$index['required_version'] == "HEAD" &&
              		$requested_version <> "other") { continue; }
          		if (empty($index['required_version']) &&
                    $requested_version <> "none") { continue; }
          		if($index['major_version'] > $major &&
             		$requested_version <> "other") { continue; }
          		if(isset($index['major_version']) &&
					$requested_version == "none") { continue; }
          		if($index['major_version'] == $major &&
             		$requested_version == "other") { continue; }

			?>
                            <tr valign="top">
                                <td class="listlr">
                                    <A target="_blank" href="<?= $index['website'] ?>"><?= $index['name'] ?></a>
                                </td>
                                <td class="listlr">
                                    <?= $index['category'] ?>
    				</td>
				<?php
					/*
					if(!$using_cache) {
						$size = get_package_install_size($index['name'], $pkg_sizes);
                               			$size = squash_from_bytes($size[$index['name']], 1);
					}
					if(!$size) $size = "Unknown.";
					*/
				?>
				<!--
				<td class="listlr">
                                 	<?= $size ?>
                                </td>
				-->
				<td class="listlr">
					<?= $index['status'] ?>
					<br>
					<?= $index['version'] ?>
					<br />
					platform: <?= $index['required_version'] ?>
                                </td>
				<td class="listlr">
				<?php
				if($index['pkginfolink']) {
                                        $pkginfolink = $index['pkginfolink'];
					echo "<a target='_new' href='$pkginfolink'>Package Info</a>";
				} else {
					echo "No info, check the <a href='http://forum.pfsense.org/index.php/board,15.0.html'>forum</a>";
				}
                                ?>
				</td>
                                <td class="listbg" class="listbg" style="color: #FFFFFF; overflow: hidden;">
                                    <?= $index['descr'] ?>
                                </td>
                                <td valign="middle" class="list" nowrap>
                                    <a onclick="return confirm('Do you really want to install this package?')" href="pkg_mgr_install.php?id=<?=$index['name'];?>"><img src="./themes/<?= $g['theme']; ?>/images/icons/icon_plus.gif" width="17" height="17" border="0"></a>
                                </td>
                            </tr>
                            <?php
                        }
		    } else {
			echo '<tr><td colspan="5"><center>There are currently no packages available for installation.</center></td></tr>';
		    }
		}
		?>
        </table>
	</div>
    </td>
  </tr>
</table>
<?php include("fend.inc"); ?>
</body>
</html>
