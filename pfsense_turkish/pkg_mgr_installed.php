<?php
/* $Id$ */
/*
    pkg_mgr.php
    Copyright (C) 2004 Scott Ullrich
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

require_once("guiconfig.inc");
require_once("pkg-utils.inc");

if(is_array($config['installedpackages']['package'])) {
	foreach($config['installedpackages']['package'] as $instpkg) {
		$tocheck[] = $instpkg['name'];
	}
	$currentvers = get_pkg_info($tocheck, array('version', 'xmlver', 'pkginfolink'));
}

$pgtitle = "System: Package Manager";
include("head.inc");

?>

<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<?php include("fbegin.inc"); ?>
<p class="pgtitle"><?=$pgtitle?></p>
<?php if ($savemsg) print_info_box($savemsg); ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">  <tr><td>
<?php
	$version = file_get_contents("/etc/version");
	$tab_array = array();
	$tab_array[] = array("{$version} packages", false, "pkg_mgr.php");
//	$tab_array[] = array("Packages for any platform", false, "pkg_mgr.php?ver=none");
//  $tab_array[] = array("Packages for a different platform", $requested_version == "other" ? true : false, "pkg_mgr.php?ver=other");
	$tab_array[] = array("Installed packages", true, "pkg_mgr_installed.php");
	display_top_tabs($tab_array);
?>
  </td></tr>
  <tr>
    <td>
	<div id="mainarea">
              <table class="tabcont" width="100%" border="0" cellpadding="6" cellspacing="0">
                <tr>
                  <td width="10%" class="listhdrr">Package Name</td>
                  <td width="20%" class="listhdrr">Category</td>
		  <td width="10%" class="listhdrr">Package Info</td>
		  <td width="15%" class="listhdrr">Package Version</td>
                  <td width="45%" class="listhdr">Description</td>
                </tr>
		<?php
                 if($config['installedpackages']['package'] != "") {
		    $instpkgs = array();
		    foreach($config['installedpackages']['package'] as $instpkg) $instpkgs[] = $instpkg['name'];
		    asort($instpkgs);
		    foreach ($instpkgs as $index => $pkgname){
			$pkg = $config['installedpackages']['package'][$index];
                        if($pkg['name'] <> "") {
                            ?>
                            <tr valign="top">
                                <td class="listlr">
                                    <?= $pkg['name'] ?>
                                </td>
                                <td class="listlr">
                                    <?= $pkg['category'] ?>
                                </td>
				<td class="listlr">
				<?php
				if($currentvers[$pkg['name']]['pkginfolink']) {
					$pkginfolink = $currentvers[$pkg['name']]['pkginfolink'];
					echo "<a target='_new' href='$pkginfolink'>Package Info</a>";
				} else {
					echo "No info, check the <a href='http://forum.pfsense.org/index.php/board,15.0.html'>forum</a>";
				}
				?>
				</td>
                                <?php
                                        $latest_package = $currentvers[$pkg['name']]['version'];
					if($latest_package == false) {
						// We can't determine this package's version status.
						?><td class="listlr"><?php
						echo "Current: Unknown.<br>Installed: " . $pkg['version'];
                                       		?></td><?php
					} elseif(strcmp($pkg['version'], $latest_package) > 0) {
                                            /* we're running a newer version of the package */
					    ?><td class="listbggrey"><font color="#FFFFFF"><?php
                                            echo "Current: {$latest_package}";
                                            echo "<br>Installed: {$pkg['version']}";
					    ?></td><?php
                                        } elseif(strcmp($pkg['version'], $latest_package) < 0) {
					    /* our package is out of date */
					    ?><td class="listbg"><font color="#FFFFFF"><?php
                                            echo "Current: {$latest_package}";
					    echo "<br>Installed: {$pkg['version']}";
					    ?></td><?php
                                        } else {
					    ?><td class="listlr"><?php
					    echo $pkg['version'];
					    ?></td><?php
					}
                                ?>
                                <td class="listbg">
                                    <font color="#ffffff">
                                    <?= $pkg['descr'] ?>
                                </td>
                                <td valign="middle" class="list" nowrap>
                                    <a onclick="return confirm('Do you really want to remove this package?')" href="pkg_mgr_install.php?mode=delete&pkg=<?= $pkg['name']; ?>"><img title="Remove this package." src="./themes/<?= $g['theme']; ?>/images/icons/icon_x.gif" width="17" height="17" border="0"></a>
                                    <br>
				    <a href="pkg_mgr_install.php?mode=reinstallpkg&pkg=<?= $pkg['name']; ?>"><img title="Reinstall this package." src="./themes/<?= $g['theme']; ?>/images/icons/icon_reinstall_pkg.gif" width="17" height="17" border="0"</a>
				    <a href="pkg_mgr_install.php?mode=reinstallxml&pkg=<?= $pkg['name']; ?>"><img title="Reinstall this package's GUI components." src="./themes/<?= $g['theme']; ?>/images/icons/icon_reinstall_xml.gif" width="17" height="17" border="0"</a>
				</td>
                            </tr>
                            <?php
                        }
		    }
                 } else {
                    echo "<tr><td colspan=\"5\"><center>There are no packages currently installed.</td></tr>";
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
<?php conf_mount_ro(); ?>