<?php
/*
	$Id$

        xmlrpc.php
        Copyright (C) 2005 Colin Smith
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
        POSSIBILITY OF SUCH DAMAGE._Value(

	TODO:
		* Expose more functions.
*/

require_once("xmlrpc_server.inc");
require_once("xmlrpc.inc");
require_once("config.inc");
require_once("functions.inc");
require_once("array_intersect_key.inc");

/* grab sync to ip if enabled */
if ($config['installedpackages']['carpsettings']['config']) {
	foreach ($config['installedpackages']['carpsettings']['config'] as $carp) {
		$synchronizetoip = $carp['synchronizetoip'];
	}
}

if($synchronizetoip) {
	if($synchronizetoip == $_SERVER['REMOTE_ADDR']) {
		log_error("CARP senkronizasyon döngüsü kısıtlanıyor.");
		die;	
	}
}

$xmlrpc_g = array(
			"return" => array(
						"true" => new XML_RPC_Response(new XML_RPC_Value(true, $XML_RPC_Boolean)),
						"false" => new XML_RPC_Response(new XML_RPC_Value(false, $XML_RPC_Boolean)),
						"authfail" => new XML_RPC_Response(0, $XML_RPC_erruser+1, "Kimlik doğrulama hatası")
				)
		);

/*
 *   pfSense XMLRPC errors
 *   $XML_RPC_erruser + 1 = Auth failure
 */
$XML_RPC_erruser = 200;

/* EXPOSED FUNCTIONS */

$exec_php_doc = 'XMLRPC wrapper for eval(). This method must be called with two parameters: a string containing the local system\'s password followed by the PHP code to evaluate.';
$exec_php_sig = array(
				array(
					$XML_RPC_Boolean, // First signature element is return value.
					$XML_RPC_String, // password
					$XML_RPC_String, // shell code to exec
				)
			);


function exec_php_xmlrpc($raw_params) {
	global $config, $xmlrpc_g;
	$params = xmlrpc_params_to_php($raw_params);
	if(!xmlrpc_auth($params)) return $xmlrpc_g['return']['authfail'];
	$exec_php = $params[0];
	eval($exec_php);
	return $xmlrpc_g['return']['true'];
}



/*****************************/


$exec_shell_doc = 'XMLRPC wrapper for mwexec(). This method must be called with two parameters: a string containing the local system\'s password followed by an shell command to execute.';
$exec_shell_sig = array(
				array(
					$XML_RPC_Boolean, // First signature element is return value.
					$XML_RPC_String, // password
					$XML_RPC_String, // shell code to exec
				)
			);


function exec_shell_xmlrpc($raw_params) {
	global $config, $xmlrpc_g;
	$params = xmlrpc_params_to_php($raw_params);
	if(!xmlrpc_auth($params)) return $xmlrpc_g['return']['authfail'];
	$shell_cmd = $params[0];
	mwexec($shell_cmd);
	return $xmlrpc_g['return']['true'];
}



/*****************************/

$backup_config_section_doc = 'XMLRPC wrapper for backup_config_section. This method must be called with two parameters: a string containing the local system\'s password followed by an array containing the keys to be backed up.';
$backup_config_section_sig = array(
				array(
					$XML_RPC_Struct, // First signature element is return value.
					$XML_RPC_String,
					$XML_RPC_Array
				)
			);

function backup_config_section_xmlrpc($raw_params) {
	global $config, $xmlrpc_g;
	$params = xmlrpc_params_to_php($raw_params);
	if(!xmlrpc_auth($params)) return $xmlrpc_g['return']['authfail'];
	$val = array_intersect_key($config, array_flip($params[0]));
	return new XML_RPC_Response(XML_RPC_encode($val));
}

/*****************************/

$restore_config_section_doc = 'XMLRPC wrapper for restore_config_section. This method must be called with two parameters: a string containing the local system\'s password and an array to merge into the system\'s config. This function returns true upon completion.';
$restore_config_section_sig = array(
					array(
						$XML_RPC_Boolean,
						$XML_RPC_String,
						$XML_RPC_Struct
					)
				);

function restore_config_section_xmlrpc($raw_params) {
	global $config, $xmlrpc_g;
	$params = xmlrpc_params_to_php($raw_params);
	if(!xmlrpc_auth($params)) return $xmlrpc_g['return']['authfail'];
	
	$config = array_merge($config, $params[0]);
	$mergedkeys = implode(",", array_keys($params[0]));
	write_config("Merged in config ({$mergedkeys} sections) from XMLRPC client.");
	return $xmlrpc_g['return']['true'];
}


/*****************************/


$merge_config_section_doc = 'XMLRPC wrapper for merging package sections. This method must be called with two parameters: a string containing the local system\'s password and an array to merge into the system\'s config. This function returns true upon completion.';
$merge_config_section_sig = array(
					array(
						$XML_RPC_Boolean,
						$XML_RPC_String,
						$XML_RPC_Struct
					)
				);

function merge_installedpackages_section_xmlrpc($raw_params) {
	global $config, $xmlrpc_g;
	$params = xmlrpc_params_to_php($raw_params);
	if(!xmlrpc_auth($params)) return $xmlrpc_g['return']['authfail'];
	$config['installedpackages'] = array_merge($config['installedpackages'], $params[0]);
	$mergedkeys = implode(",", array_keys($params[0]));
	write_config("Merged in config ({$mergedkeys} sections) from XMLRPC client.");
	return $xmlrpc_g['return']['true'];
}

/*****************************/


$merge_config_section_doc = 'XMLRPC wrapper for merge_config_section. This method must be called with two parameters: a string containing the local system\'s password and an array to merge into the system\'s config. This function returns true upon completion.';
$merge_config_section_sig = array(
					array(
						$XML_RPC_Boolean,
						$XML_RPC_String,
						$XML_RPC_Struct
					)
				);

function merge_config_section_xmlrpc($raw_params) {
	global $config, $xmlrpc_g;
	$params = xmlrpc_params_to_php($raw_params);
	if(!xmlrpc_auth($params)) return $xmlrpc_g['return']['authfail'];
	$config = array_merge_recursive_unique($config, $params[0]);
	$mergedkeys = implode(",", array_keys($params[0]));
	write_config("Merged in config ({$mergedkeys} sections) from XMLRPC client.");
	return $xmlrpc_g['return']['true'];
}

/*****************************/

$filter_configure_doc = 'Basic XMLRPC wrapper for filter_configure. This method must be called with one paramater: a string containing the local system\'s password. This function returns true upon completion.';
$filter_configure_sig = array(
				array(
					$XML_RPC_Boolean,
					$XML_RPC_String
				)
			);

function filter_configure_xmlrpc($raw_params) {
	global $xmlrpc_g;
	$params = xmlrpc_params_to_php($raw_params);
	if(!xmlrpc_auth($params)) return $xmlrpc_g['return']['authfail'];
	require_once("vslb.inc");
	slbd_configure();
	filter_configure();
	system_routing_configure();
	return $xmlrpc_g['return']['true'];
}

/*****************************/

$check_firmware_version_doc = 'Basic XMLRPC wrapper for check_firmware_version. This function will return the output of check_firmware_version upon completion.';
$check_firmware_version_sig = array(
					array(
						$XML_RPC_String,
						$XML_RPC_String
					)
				);

function check_firmware_version_xmlrpc($raw_params) {
	global $xmlrpc_g, $XML_RPC_String;
	$params = xmlrpc_params_to_php($raw_params);
	if(!xmlrpc_auth($params)) return $xmlrpc_g['return']['authfail'];
	return new XML_RPC_Response(new XML_RPC_Value(check_firmware_version(false), $XML_RPC_String));
}

/*****************************/

$reboot_doc = 'Basic XMLRPC wrapper for rc.reboot.';
$reboot_sig = array(array($XML_RPC_Boolean, $XML_RPC_String));

function reboot_xmlrpc($raw_params) {
	global $xmlrpc_g;
	$params = xmlrpc_params_to_php($raw_params);
	if(!xmlrpc_auth($params)) return $xmlrpc_g['return']['authfail'];
	mwexec_bg("/etc/rc.reboot");
	return $xmlrpc_g['return']['true'];
}

/*****************************/

$get_notices_sig = array(
				array(
					$XML_RPC_Array,
					$XML_RPC_String
				),
				array(
					$XML_RPC_Array
				)
			);

function get_notices_xmlrpc($raw_params) {
	global $g, $xmlrpc_g;
	$params = xmlrpc_params_to_php($raw_params);
	if(!xmlrpc_auth($params)) return $xmlrpc_g['return']['authfail'];
	require_once("notices.inc");
	if(!$params) {
		$toreturn = get_notices();
	} else {
		$toreturn = get_notices($params);
	}
	$response = new XML_RPC_Response(XML_RPC_encode($toreturn));
	return $response;
}

/*****************************/

$carp_configure_doc = 'Basic XMLRPC wrapper for configuring CARP interfaces.';
$carp_configure_sig = array(array($XML_RPC_Boolean, $XML_RPC_String));

function interfaces_carp_configure_xmlrpc($raw_params) {
	global $xmlrpc_g;
	$params = xmlrpc_params_to_php($raw_params);
	if(!xmlrpc_auth($params)) return $xmlrpc_g['return']['authfail'];
	interfaces_carp_configure();
	interfaces_carp_bring_up_final();
	return $xmlrpc_g['return']['true'];
}

/*****************************/

$server = new XML_RPC_Server(
        array(
            'pfsense.exec_shell' 		=> array('function' => 'exec_shell_xmlrpc',
							'signature' => $exec_shell_sig,
							'docstring' => $exec_shell_doc),
            'pfsense.exec_php'	 		=> array('function' => 'exec_php_xmlrpc',
							'signature' => $exec_php_sig,
							'docstring' => $exec_php_doc),	
            'pfsense.interfaces_carp_configure' => array('function' => 'interfaces_carp_configure_xmlrpc',
							'signature' => $carp_configure_doc,
							'docstring' => $carp_configure_sig),
            'pfsense.backup_config_section' => 	array('function' => 'backup_config_section_xmlrpc',
							'signature' => $backup_config_section_sig,
							'docstring' => $backup_config_section_doc),
	    'pfsense.restore_config_section' => array('function' => 'restore_config_section_xmlrpc',
							'signature' => $restore_config_section_sig,
							'docstring' => $restore_config_section_doc),
	    'pfsense.merge_config_section' => array('function' => 'merge_config_section_xmlrpc',
							'signature' => $merge_config_section_sig,
							'docstring' => $merge_config_section_doc),
	    'pfsense.merge_installedpackages_section_xmlrpc' => array('function' => 'merge_installedpackages_section_xmlrpc',
							'signature' => $merge_config_section_sig,
							'docstring' => $merge_config_section_doc),							

	    'pfsense.merge_installedpackages_section_xmlrpc' => array('function' => 'merge_installedpackages_section_xmlrpc',
							'signature' => $merge_config_section_sig,
							'docstring' => $merge_config_section_doc),
							
	    'pfsense.filter_configure' => 	array('function' => 'filter_configure_xmlrpc',
							'signature' => $filter_configure_sig,
							'docstring' => $filter_configure_doc),
	    'pfsense.check_firmware_version' =>	array('function' => 'check_firmware_version_xmlrpc',
							'signature' => $check_firmware_version_sig,
							'docstring' => $check_firmware_version_doc),
	    'pfsense.reboot' =>			array('function' => 'reboot_xmlrpc',
							'signature' => $reboot_sig,
							'docstring' => $reboot_doc),
	    'pfsense.get_notices' =>		array('function' => 'get_notices_xmlrpc',
							'signature' => $get_notices_sig)
        )
);
?>
