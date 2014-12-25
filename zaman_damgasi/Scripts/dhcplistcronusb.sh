#!/bin/sh

tarih=`date "+%Y%m%d-%H%M%S"`
disks="da0s1 da0s2 da0s3 da0s4 da1s1 da1s2 da1s3 da1s4"

mount -uw /
mkdir /var/mountusb
mkdir /var/zaman_damgala
cd /var/zaman_damgala

for db in $disks
do
  if mount -t msdosfs /dev/$db /var/mountusb; then
  logger "/dev/$db USB aygiti basariyla baglandi."  
  
  awk -f /sbin/dhcptibduzenle.sh < /var/dhcpd/var/db/dhcpd.leases > ./dhcpdagitimlogtibformat.txt

  sleep 1
  /sbin/logzamandamgasi.sh
  sleep 3
  
  cd /var/zaman_damgali_loglar
  mv * /var/mountusb  
  umount /var/mountusb
  logger "Kopyalama gerceklestirildi."
  else
  logger "/dev/$db USB aygit baglanamadi."
  fi
done

rm -rf /var/mountusb
rm -rf /var/zaman_damgala
rm -rf /var/zaman_damgali_loglar

