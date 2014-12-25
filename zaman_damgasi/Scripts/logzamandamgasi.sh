#!/bin/sh

# DEGISKENLER

# Imzali dosyalara ait tar.gz dosyasinin ismine eklenecek zaman bilgisi.
tarih=`date "+%Y%m%d-%H%M%S"`

# Imza islemi icin calisma dizinine kopyalanacak log dosyasinin adi.
dosya_adi=dhcpdagitimlogtibformat.txt

# Log dosyasinin bulundugu dizin.
yol=/var/zaman_damgala

# Imzalama islerinin yapilacagi dizinin adi.
calisma_dizini=/var/zaman_damgala

# Imza sonrasi olusan dosyalarin backuplanacagi dizin.
saklama_dizini=/var/zaman_damgali_loglar

#Hatali imzalanmis dosyalarin tasinacagi dizin.
hatali_imzalar=/var/imzali_loglar/hatali-imza-$dosya_adi-$tarih

# Imzalama sirasinda kullanilan CA private key'e ait public key.
CAPublicKEY=/CA/cacert.pem

# Zaman damgasi otoritesine ait public key.
TSAPublicKEY=/CA/tsacert.pem

# Imzalama sirasinda kullanilan TSA private key'e ait public key.
openssl=/usr/local/ssl/bin/openssl

# Imza islemi icin kullanilacak openssl binarysi.
openssl_conf=/usr/local/ssl/openssl.cnf	# OpenSSL binary'sinin kullanacagi config dosyasi.

# TSA'ya ait Private Key'in Pass Pharse'i
password=nevport

#--------------------------------------CALISMA DIZININE KOPYALAMA ISLEMI--------------------------------------#

if [ ! -d $calisma_dizini ]
	then
		mkdir $calisma_dizini
	fi

sleep 1

#cp $yol/$dosya_adi $calisma_dizini
cd $calisma_dizini

#--------------------------------------IMZALAMA ISLEMI--------------------------------------#

$openssl ts -query -data $dosya_adi -no_nonce -out $dosya_adi.tsq
sleep 1
$openssl ts -reply -queryfile $dosya_adi.tsq -out $dosya_adi.der -token_out -config $openssl_conf -passin pass:$password

#--------------------------------------DOGRULAMA ISLEMI--------------------------------------#

COMMAND=`$openssl ts -verify -data $dosya_adi -in $dosya_adi.der -token_in -CAfile  $CAPublicKEY -untrusted $TSAPublicKEY`

cd 

if [ "${COMMAND}" = "Verification: OK" ]
        then
                echo "Dogrulama tamam."
        else
                echo "Dogrulama Saglanamadi. Islemler geri aliniyor."
                
		if [ ! -d $saklama_dizini ]
       		    then
                	mkdir $saklama_dizini
        	fi

		if [ ! -d $hatali_imzalar ]
		    then
			mkdir $hatali_imzalar
		fi

		mv $calisma_dizini/$dosya_adi* $hatali_imzalar
		echo "$dosya_adi isimli log dosyasi imzalamanamadi. $hatali_imzalar dizininde bulabilirsiniz. "
		exit
fi

sleep 1

if [ ! -d $saklama_dizini ]
	then
		mkdir $saklama_dizini
	fi

sleep 1

cd $calisma_dizini
tar cvfz $saklama_dizini/$dosya_adi.$tarih.tar.gz $dosya_adi*
rm  $calisma_dizini/$dosya_adi*
