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
		
	# Get courier itself
	echo -e "Generating DEB packages for courier itself..."
	wget -N "https://sourceforge.net/projects/courier/files/courier/1.3.13/courier-1.3.13.tar.bz2/download" -O "courier-1.3.13.tar.bz2"
	mkdir tmp
	mv "courier-1.3.13.tar.bz2" tmp
	cd tmp
	tar xvf "courier-1.3.13.tar.bz2"
	cd courier-1.3.13
	./courier-debuild -us -uc
	cp deb/* /root/source/courier_dpkg_builds/pkgs
	dpkg -i "/root/source/courier_dpkg_builds/pkgs/courier_1.3.13-100_${arch}.deb"
	dpkg -i "/root/source/courier_dpkg_builds/pkgs/courier-imapd_1.3.13-100_${arch}.deb"
	dpkg -i "/root/source/courier_dpkg_builds/pkgs/courier-pop3d_1.3.13-100_${arch}.deb"
	
	# Go back to source dir
	cd ..
	cd ..
	
	echo -e "Done building and installing deb packages..."
	
}

installCourierManually
