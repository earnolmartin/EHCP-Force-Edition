#!/bin/bash
# Builds the courier deb packages needed by EHCP Force
# Script Author:  Eric
function installCourierManually(){
	arch=$(dpkg --print-architecture)
	
	echo -e "Generating DEB packages and installing them for courier..."
	
	# Make source dir
	rm -rf "/root/source/courier_dpkg_builds"
	mkdir -p "/root/source/courier_dpkg_builds"
	mkdir -p "/root/source/courier_dpkg_builds/pkgs"
	cd /root/source/courier_dpkg_builds
	
	# Get build-essential tools
	apt-get install -y build-essential
	apt-get install -y expect
	apt-get install -y libtool
	apt-get install -y libgdbm-dev
	apt-get install -y libidn2-dev
	apt-get install -y libidn2-0
	apt-get install -y pkg-config
	apt-get install -y libpcre2-dev
	apt-get install -y libperl-dev
	apt-get install -y devscripts debhelper
	apt-get install -y libldap2-dev sysconftool gnutls-dev gnutls-bin libgcrypt-dev hunspell libhunspell-dev mgetty-fax
	apt-get install -y libmariadb-dev libmariadb-dev-compat libsqlite3-dev libpq-dev libpam0g-dev
	apt-get install -y mailcap netpbm libnet-cidr-perl libltdl-dev
	
	# Get courier unicode
	echo -e "Generating DEB packages for courier-unicode..."
	wget -N "https://sourceforge.net/projects/courier/files/courier-unicode/2.3.1/courier-unicode-2.3.1.tar.bz2/download" -O "courier-unicode-2.3.1.tar.bz2"
	mkdir tmp
	mv courier-unicode-2.3.1.tar.bz2 tmp
	cd tmp
	tar xvf courier-unicode-2.3.1.tar.bz2
	cd courier-unicode-2.3.1
	./courier-debuild -us -uc
	cp deb/* /root/source/courier_dpkg_builds/pkgs
	dpkg -i "/root/source/courier_dpkg_builds/pkgs/libcourier-unicode8_2.3.1-100_${arch}.deb"
	dpkg -i "/root/source/courier_dpkg_builds/pkgs/libcourier-unicode-dev_2.3.1-100_${arch}.deb"
	
	# Go back to source dir
	cd ..
	cd ..
	
	# Get courier authlib
	echo -e "Generating DEB packages for courier-authlib..."
	wget -N "https://sourceforge.net/projects/courier/files/authlib/0.72.3/courier-authlib-0.72.3.tar.bz2/download" -O "courier-authlib-0.72.3.tar.bz2"
	mkdir tmp
	mv courier-authlib-0.72.3.tar.bz2 tmp
	cd tmp
	tar xvf courier-authlib-0.72.3.tar.bz2
	cd courier-authlib-0.72.3
	./courier-debuild -us -uc
	cp deb/* /root/source/courier_dpkg_builds/pkgs
	dpkg -i "/root/source/courier_dpkg_builds/pkgs/libcourier-auth-config-daemon-daemon_0.72.3-100_${arch}.deb"
	dpkg -i "/root/source/courier_dpkg_builds/pkgs/libcourier-auth0_0.72.3-100_${arch}.deb"
	dpkg -i "/root/source/courier_dpkg_builds/pkgs/libcourier-auth_0.72.3-100_${arch}.deb"
	dpkg -i "/root/source/courier_dpkg_builds/pkgs/libcourier-auth-mysql_0.72.3-100_${arch}.deb"
	dpkg -i "/root/source/courier_dpkg_builds/pkgs/libcourier-auth-dev_0.72.3-100_${arch}.deb"
	
	# Go back to source dir
	cd ..
	cd ..
		
	# Get courier-imap itself
	echo -e "Generating DEB packages for courier-imap itself..."
	wget -N "https://sourceforge.net/projects/courier/files/imap/5.2.10/courier-imap-5.2.10.tar.bz2/download" -O "courier-imap-5.2.10.tar.bz2"
	mkdir tmp
	mv "courier-imap-5.2.10.tar.bz2" tmp
	cd tmp
	tar xvf "courier-imap-5.2.10.tar.bz2"
	cd courier-imap-5.2.10
	./courier-debuild -us -uc
	cp deb/* /root/source/courier_dpkg_builds/pkgs
	dpkg -i "/root/source/courier_dpkg_builds/pkgs/courier-imap_5.2.10-100_${arch}.deb"
	mkdir -p /etc/courier
	mv /usr/lib/courier-imap/etc/pop3d /etc/courier/
	mv /usr/lib/courier-imap/etc/pop3d-ssl /etc/courier/
	mv /usr/lib/courier-imap/etc/imapd /etc/courier/
	mv /usr/lib/courier-imap/etc/imapd-ssl /etc/courier/
	ln -s /etc/courier/pop3d /usr/lib/courier-imap/etc/pop3d
	ln -s /etc/courier/pop3d-ssl /usr/lib/courier-imap/etc/pop3d-ssl
	ln -s /etc/courier/imapd /usr/lib/courier-imap/etc/imapd
	ln -s /etc/courier/imapd-ssl /usr/lib/courier-imap/etc/imapd-ssl
	service courier-imap restart
	systemctl enable courier-imap
	
	# Go back to source dir
	cd ..
	cd ..
	
	echo -e "Done building and installing deb packages..."
	
}

installCourierManually
