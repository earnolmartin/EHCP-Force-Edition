#!/bin/bash
# karsi sunuculara otomatik baglanmayi ayarlar ve bazi kucuk ayarlar yapar. bashrc ye bazi aliasler ekler v.b.


cp $0 /bin
cp $0 /bin/SSH2.SH

function checksshkey(){	
	echo "sskey hallediliyor..";
	ssayi=`cat ~/.ssh/id_dsa.pub | wc -l `

	if [ $ssayi -eq 0 ] ; then
		echo "ssh keyiniz yok, olusturuluyor..";
		ssh-keygen -t dsa		
	fi
	
	if [ ! -f ~/.ssh/id_dsa.pub ] ; then  # bash or calismadi, solarisde, bu nedenle ayri bir if yazildi.
		echo "ssh keyiniz yok, olusturuluyor..";
		ssh-keygen -t dsa		
	fi
	
	echo "karsi sunucuya keyiniz kopyalaniyor-copying key to remote server";
	ssh-copy-id -i ~/.ssh/id_dsa.pub $ip
	if [ $? -gt 0 ] ; then #ssh-copy-id komutu yoksa, dandik solaris gibi.
		oncebaglan=true
		scp ~/.ssh/id_dsa.pub $ip:/
		echo "karsi sunucuya kopyalandi, karsi sunucuda sunu calistirin:"
		echo "mkdir ~/.ssh/";
		echo "cat /id_dsa.pub >> ~/.ssh/authorized_keys"
		echo 
	else
		oncebaglan=false
	fi
}

if [ "$1" == "" ] ; then
	echo "kull: $0 ip ";
	exit;
fi

ping -c 2 $1

ip="$1"
sayi=`grep $ip /etc/hosts | wc -l `
if [ $sayi -eq 0 ] ; then # hosts dosyasinda yoksa ekle, hizli baglanma icin
	echo "$ip uzakmakine`date +%H%M%S`" >> /etc/hosts
	echo "hosts dosyasina eklendi."
fi


liste="/etc/sshyapilanyerler"
sayi=`grep "$1" $liste | wc -l`

function gerekli_dosyalari_kopyala(){
	echo "gerekli dosyalar karsiya kopyalaniyor.. "
	scp $0 $1:/bin/
	scp $0 $1:/bin/SSH2.SH
	scp /bin/bekle.sh $1:/bin/
	scp /bin/zorlaoldur.sh $1:/bin/
	scp /bin/zmore.sh $1:/bin/
	scp /bin/gozat.sh $1:/bin/
	scp /bin/bashrc_ornek $1:/root/.bashrc
	scp /bin/bashrc_ornek $1:/bin/bashrc_ornek
}

function baglan(){
	echo "karsiya baglaniyor-connecting to remote: $1"
	if [ "$2" == "" ] ; then # solarisde info verme, sifre soruyor ayrica
		ssh $1 "cat /etc/*ele*"
	fi
	
	ssh $1
}

if [ $sayi -eq 0 -o "$2" == "-r" ] ; then
	
	if [ "$2" == "" ] ; then
		echo "this ssh is new, do you want to setup automatic login?(y/n)    bu ssh yeni, otomatik dosya olusturulsun mu ? (e/h)"
		read cevap
	else
		cevap="e"
	fi	
	
	if [ "$cevap" == "e" -o "$cevap" == "y" ] ; then
		sed -i "s/$ip//" /etc/sshyapilanyerler  # zaten varsa, temizle
		checksshkey;
		echo
		echo "give it a filename/dosyaadi:"
		read dosyaadi
		
		echo "ssh $ip \"cat /etc/*ele*\"" > /bin/$dosyaadi.sh
		echo "ssh $ip" >> /bin/$dosyaadi.sh 
		chmod a+x /bin/$dosyaadi.sh 		
		echo "$ip" >> $liste
		
		if $oncebaglan ; then # solarisde once baglanmak gerekiyor.. yoksa bisuru sifre soruyor. 
			baglan $ip "noinfo"
			#gerekli_dosyalari_kopyala $ip
		else
			#gerekli_dosyalari_kopyala $ip
			baglan $ip
		fi
		
		exit;
	else
		baglan $ip
		exit
	fi
	
fi

#gerekli_dosyalari_kopyala $ip
baglan $ip
