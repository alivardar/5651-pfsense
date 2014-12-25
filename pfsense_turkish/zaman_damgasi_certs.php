<?php
/* $Id$ */
/*
	system_advanced_create_certs.php
	part of pfSense

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

require("guiconfig.inc");

if(file_exists("/usr/local/ssl/openssl.cnf")) 
	$openssl = file_get_contents("/usr/local/ssl/openssl.cnf");

/* Lets match the fileds in the read in file and
   populate the variables for the form */
preg_match('/C\=(.*)\n/', $openssl, $countrycodeA);
preg_match('/\nST\=(.*)\n/', $openssl, $stateorprovinceA);
preg_match('/\nL\=(.*)\n/', $openssl, $citynameA);
preg_match('/\nO\=(.*)\n/', $openssl, $orginizationnameA);
preg_match('/\nOU\=(.*)\n/', $openssl, $orginizationdepartmentA);
preg_match('/\nCN\=(.*)\n/', $openssl, $commonnameA);

$countrycode = $countrycodeA[1];
$stateorprovince = $stateorprovinceA[1];
$cityname = $citynameA[1];
$orginizationname = $orginizationnameA[1];
$orginizationdepartment = $orginizationdepartmentA[1];
$commonname = $commonnameA[1];

if ($_POST) {

    /* Grab posted variables and create a new openssl.cnf */
    $countrycode=$_POST['countrycode'];
    $stateorprovince=$_POST['stateorprovince'];
    $cityname=$_POST['cityname'];
    $orginizationname=$_POST['orginizationname'];
    $orginizationdepartment=$_POST['orginizationdepartment'];
    $commonname=$_POST['commonname'];

    /* Write out /var/etc/ssl/openssl.cnf */
    conf_mount_rw();
	
	safe_mkdir("/usr/local/ssl/");
		
    $fd = fopen("/usr/local/ssl/openssl.cnf", "w");
    fwrite($fd, "");
    fwrite($fd, "[ req ]\n");
    fwrite($fd, "distinguished_name=req_distinguished_name \n");
    fwrite($fd, "req_extensions = v3_req \n");
    fwrite($fd, "prompt=no\n");
    fwrite($fd, "default_bits            = 1024\n");
    fwrite($fd, "default_keyfile         = privkey.pem\n");
    fwrite($fd, "distinguished_name      = req_distinguished_name\n");
    fwrite($fd, "attributes              = req_attributes\n");
    fwrite($fd, "x509_extensions = v3_ca # The extentions to add to the self signed cert\n");
    fwrite($fd, "[ req_distinguished_name ] \n");
    fwrite($fd, "C=" . $countrycode . " \n");
    fwrite($fd, "ST=" . $stateorprovince. " \n");
    fwrite($fd, "L=" . $cityname . " \n");
    fwrite($fd, "O=" . $orginizationname . " \n");
    fwrite($fd, "OU=" . $orginizationdepartment . " \n");
    fwrite($fd, "CN=" . $commonname . " \n");
    fwrite($fd, "[EMAIL PROTECTED] \n");
    fwrite($fd, "[EMAIL PROTECTED] \n");
    fwrite($fd, "[ v3_req ] \n");
    fwrite($fd, "basicConstraints = critical,CA:FALSE \n");
    fwrite($fd, "keyUsage = nonRepudiation, digitalSignature, keyEncipherment, dataEncipherment, keyAgreement \n");
    fwrite($fd, "extendedKeyUsage=emailProtection,clientAuth \n");
    fwrite($fd, "[ ca ]\n");
    fwrite($fd, "default_ca      = CA_default\n");
    fwrite($fd, "[ CA_default ]\n");
    fwrite($fd, "certificate             = /CA/cacert.pem \n");
    fwrite($fd, "private_key             = /CA/private/cakey.pem \n");
    fwrite($fd, "dir                     = /CA/\n");
    fwrite($fd, "certs                   = /CA/certs\n");
    fwrite($fd, "crl_dir                 = /CA/crl\n");
    fwrite($fd, "database                = /CA/index.txt \n");
    fwrite($fd, "new_certs_dir           = /CA/newcerts \n");
    fwrite($fd, "serial                  = /CA/serial \n");
    fwrite($fd, "crl                     = /CA/crl.pem \n");
    fwrite($fd, "RANDFILE                = /CA/.rand  \n");
    fwrite($fd, "x509_extensions         = usr_cert  \n");
    fwrite($fd, "name_opt                = ca_default \n");
    fwrite($fd, "cert_opt                = ca_default \n");
    fwrite($fd, "default_days            = 3650 \n");
    fwrite($fd, "default_crl_days        = 30  \n");
    fwrite($fd, "default_md              = md5 \n");
    fwrite($fd, "preserve                = no \n");
    fwrite($fd, "policy                  = policy_match\n");
    fwrite($fd, "[ policy_match ]\n");
    fwrite($fd, "countryName             = match\n");
    fwrite($fd, "stateOrProvinceName     = match\n");
    fwrite($fd, "organizationName        = match\n");
    fwrite($fd, "organizationalUnitName  = optional\n");
    fwrite($fd, "commonName              = supplied\n");
    fwrite($fd, "emailAddress            = optional\n");
    fwrite($fd, "[ policy_anything ]\n");
    fwrite($fd, "countryName             = optional\n");
    fwrite($fd, "stateOrProvinceName     = optional\n");
    fwrite($fd, "localityName            = optional\n");
    fwrite($fd, "organizationName        = optional\n");
    fwrite($fd, "organizationalUnitName  = optional\n");
    fwrite($fd, "commonName              = supplied\n");
    fwrite($fd, "emailAddress            = optional\n");
    fwrite($fd, "[ req_distinguished_name ]\n");
    //fwrite($fd, "countryName                     = US\n");
    fwrite($fd, "[ req_attributes ]\n");
    fwrite($fd, "challengePassword               = A challenge password\n");
    fwrite($fd, "unstructuredName                = An optional company name\n");
    fwrite($fd, "[ usr_cert ]\n");
    fwrite($fd, "basicConstraints=CA:FALSE\n");
    fwrite($fd, "[ v3_ca ]\n");
    fwrite($fd, "subjectKeyIdentifier=hash\n");
    fwrite($fd, "authorityKeyIdentifier=keyid:always,issuer:always\n");
    fwrite($fd, "basicConstraints = CA:true\n");
    fwrite($fd, "[ crl_ext ]\n");
    fwrite($fd, "authorityKeyIdentifier=keyid:always,issuer:always\n");
	
	fwrite($fd, "[ new_oids ]\n");
	fwrite($fd, "tsa_policy1 = 1.2.3.4.1\n");
	fwrite($fd, "tsa_policy2 = 1.2.3.4.5.6\n");
	fwrite($fd, "tsa_policy3 = 1.2.3.4.5.7\n");
	fwrite($fd, "[ tsa ]\n");
	fwrite($fd, "default_tsa = TSA_default\n");
	fwrite($fd, "[ TSA_default ]\n");
	fwrite($fd, "dir				= /CA\n");
	fwrite($fd, "serial			= /CA/tsaserial\n");
	fwrite($fd, "crypto_device	= builtin\n");
	fwrite($fd, "signer_cert		= /CA/tsacert.pem\n");
	fwrite($fd, "certs			= /CA/cacert.pem\n");
	fwrite($fd, "signer_key		= /CA/private/tsakey.pem\n");
	fwrite($fd, "default_policy		= tsa_policy1\n");
	fwrite($fd, "other_policies		= tsa_policy2, tsa_policy3\n");
	fwrite($fd, "digests			= md5, sha1\n");
	fwrite($fd, "accuracy		= secs:1, millisecs:500, microsecs:100\n");
	fwrite($fd, "clock_precision_digits	= 0\n");
	fwrite($fd, "ordering		= yes\n");
	fwrite($fd, "tsa_name		= yes\n");
	fwrite($fd, "ess_cert_id_chain	= no\n");
	
    fclose($fd);

	
$pgtitle = "Tib işlemleri: Zaman Sertifikası Oluşturma";
include("head.inc");

?>

<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<p class="pgtitle"><?=$pgtitle?></p>
<form action="zaman_damgasi_certs.php" method="post" name="iform" id="iform">
            <?php if ($input_errors) print_input_errors($input_errors); ?>
            <?php if ($savemsg) print_info_box($savemsg); ?>
	    <p>Anahtarlar Üretiliyor...
	<?php


	conf_mount_rw();	
	
	mwexec("rm -rf /CA");
	mwexec("mkdir /CA ");

	?>
	
	<script language="JavaScript">	
	    this.close();	
	</script>

</body>
</html>

<?php

} else {

$pgtitle = 'Sertifika Oluşturma';
include("head.inc");
?>


    <body link="#0000CC" vlink="#0000CC" alink="#0000CC">
    <form action="zaman_damgasi_certs.php" method="post" name="iform" id="iform">
	  <p class="pgtitle">Sistem: İleri Seviye - Sertifika Oluşturma</p>

	  <table width="100%" border="0" cellpadding="6" cellspacing="0">
		    <tr>
		      <td width="35%" valign="top" class="vncell"><B>Ülke Kodu (2 Harf)</td>
		      <td width="78%" class="vtable">
			<input name="countrycode" value="<?=$countrycode?>">
			</span></td>
		    </tr>

		    <tr>
		      <td width="35%" valign="top" class="vncell"><B>Eyalet veya bölge adı</td>
		      <td width="78%" class="vtable">
			<input name="stateorprovince" value="<?=$stateorprovince?>">
			</span></td>
		    </tr>

		    <tr>
		      <td width="35%" valign="top" class="vncell"><B>Şehir adı</td>
		      <td width="78%" class="vtable">
			<input name="cityname" value="<?=$cityname?>">
			</span></td>
		    </tr>

		    <tr>
		      <td width="35%" valign="top" class="vncell"><B>Organizasyon adı</td>
		      <td width="78%" class="vtable">
			<input name="orginizationname" value="<?=$orginizationname?>">
			</span></td>
		    </tr>

		    <tr>
		      <td width="35%" valign="top" class="vncell"><B>Organizasyon bölümü</td>
		      <td width="78%" class="vtable">
			<input name="orginizationdepartment" value="<?=$orginizationdepartment?>">
			</span></td>
		    </tr>

		    <tr>
		      <td width="35%" valign="top" class="vncell"><B>Sizin Adınız</td>
		      <td width="78%" class="vtable">
			<input name="commonname" value="<?=$commonname?>">
			</span></td>
		    </tr>

		    <!--
		    <tr>
		      <td width="35%" valign="top" class="vncell"><B>E-Mail address</td>
		      <td width="78%" class="vtable">
			<input name="email" value="<?=$email?>">
			</span></td>
		    </tr>
		    -->

		    <tr>
		      <td width="35%" valign="top">&nbsp;</td>
		      <td width="78%">
			<input name="Submit" type="submit" class="formbtn" value="Kaydet">
		      </td>
		    </tr>

    </body>
    </html>

<?php
}	
	conf_mount_ro();

?>
