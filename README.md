5651-pfsense
============

5651 sayılı yasa çıktığından beri çok yerde loglama işlemleri konuşuldu. Bu konuda da Pfsense kullanarak bir çözüm geliştirmeye karar verdim sizlere birazdan anlatacağım ürün ortaya çıktı.
Aslında bu ürünü ticari bir kutu haline getirmiştim ama daha sonradan açık kodlu olarak sizlerle paylaşmaya karar verdim.

Neler gerekiyor?
Öncelikle TİB subnet altında olan ağlarda dağıtılan ip adreslerini belirli bir formatta istemektedir.
Bu format;
http://www.tib.gov.tr/dokuman/Ornek_Dahili_IP_Dagitim_Logu.txt
adresinde belirtildiği şekilde olmalıdır. Eğer bu ip adresleri DHCP sunucu tarafından dağıtılmıyor 
statik olarak tanımlanmışsa o zaman el ile tanımlanma yapılmalıdır.
Bu şekilde hazırlnamış olan dosya;
http://www.tib.gov.tr/IP_log_imzalayici
adresinde bulunan zaman damgalayıcı ile damgalanmalı ve bir daha içinde herhangi bir değişiklik
yapılmamalıdır. Bunun yanında yasaklı olan kelimeleri yasaklayan bir filtreleme sisteminin
bulundurulması ve kullanıma dahil edilerek yasaklı olan "sex porno" gibi kelimelerin yasaklanması
istenmektedir.

Çözüm:
Çözüm olarak pfsense 1.2.3 sürümüne ait 2gb imaj dosyası compact flash üzerine kurularak 
üzerinde değişiklikler yapıldı. Yazılan uygulamalar ile DHCP sunucunun logları istenen formatta 
Windows paylaşımı, FTP paylaşımı veyahutta sistem üzerine takılı FAT32 formatlı bir disk 
(testlerde ben usb kullandım) üzerine aktarılmasını sağlamak. Özellikle windows paylaşımı 
yerel ağdaki ağlar, FTP ise çoklu ağlar üzerinde tek bir yerden zaman damgası işlemlerini
yerine getirmek için ideal olacaktır. Windows paylaşım dizinleri ve ftp dizinleri özellike TİB'e
ait olan zaman damgalama dizini ile eşleştirilirse sorunsuz bir şekilde işler otomatik olmaktadır.

Gelelim bu çözüme ait yönteme;
Yöntem :
Downloads->Projects bölümüne yerleştirilen dosyalar kullanılarak kendinize ait olan pfsense üzerinde
gerekli dosyalar değiştirilerek işler hale getirilebilir.
Yapılması gerekenler;
Download bölümünde bulunan Projects kısmında ki zip li dosyayı indiriniz ve bir dizine açınız. Pfsense
kutunuzda SSH aktifleştiriniz ve winscp ve size uygun bir sftp cihazı ile bağlanınız
/usr/local/www/diag_logs_settings.php dosyasının yedeğini alınız ve zip içinden çıkanı onun yerine
yazınız.
Kopyalama yapmadan önce SSH ile bağlanarak

mount -uw /

komutunu çalışıtırınız.
Daha sonra diğer dosyaları /sbin dizine içine kopyalayınız. Çalıştırma haklarını veriniz. Bilindik yol;
chmod 755 dosya_adi
Daha sonra diag_log_settings.php içinden girerek ayarlama yapınız.
Çalışmaya başlamadan önce sisteme sambaclient paketini kurmak gerekiyor. Bunun için pfsense ssh
ile bağlandıktan sonra aşağıdaki komutları konsolda yazınız.

mount -uw /

pkg_add -r samba3-smbclient

cp /usr/local/etc/smb-client.conf.sample /usr/local/etc/smb-client.conf


Nasıl kullanılır?
Sunucu kayıtları -> Ayarlar
Dosya adı diag_logs_settings.php dosyası pfsense üzerinde çalıştırılır. Eğer ingilizce pfsense
üzerine koymussanız Türkçe karakterlerde saçmalama olacaktır. Logları aldığınız dizinlerinin TİB
imzalayici ile aynı dizini işaret etmesine dikkat ediniz. Ayarlarınızı yaptıktan sonra;
Test yapmak için SSH ile bağlandıktan sonra, ftp test etmek için

/sbin/dhcplistcronsftp.sh

windows paylaşımını test etmek için

/sbin/dhcplistcronsmb.sh
yazarak çıkan sonuçlara göre dosyaları kopyalayıp kopyalamadığı anlaşılabilir.

Önceki sürüm değişiklikleri;

---> Sürüm 0.2 Deniz Porsuk bey tarafından gönderilen düzeltmeyi içermektedir. Kendisi logları windows domain sistemlere kopyalama yeteneği eklemiştir.

---> Sürüm 0.3 Technical isimli kullanıcımız tarafından gönderilmiştir. Bu sürümle birlikte Pfsense sürüm 2.0 ile ilgili düzenlemeler eklenmiştir.

---> Sürüm 0.4 Technical hata düzeltmeleri yapmıştır. Forumdan takip ediniz.
