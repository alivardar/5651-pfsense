
mount -uw /
 
rm -rf /CA

mkdir /CA
cd /CA
mkdir private
mkdir newcerts

touch index.txt
echo "01" > serial


openssl req -new -x509 -keyout /CA/private/cakey.pem -out /CA/cacert.pem -days 3650 -config /usr/local/ssl/openssl.cnf -passin pass:nevport -nodes
openssl genrsa -aes256 -out /CA/private/tsakey.pem -passout pass:nevport
openssl req -new -key /CA/private/tsakey.pem -out tsareq.csr -passin pass:nevport
openssl ca -config /usr/local/ssl/openssl.cnf -in tsareq.csr -out tsacert.pem -batch

mv tsakey.pem /CA/private/









