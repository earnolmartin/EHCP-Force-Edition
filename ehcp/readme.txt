#################################
#   EHCP FoRcE Edition ReadMe   #
#################################
The Easy Hosting Control Panel (EHCP) is a fully functional, advanced, free, and open source website panel that provides a user interface for creating and managing multiple users, resellers, administrators, websites, FTP accounts, MySQL databases, email accounts, and more!

EHCP even installs and configures your web server software for you while also providing additional security by slipstreaming and including fail2ban and DDoS automatic banning (against Apache).

#################################
#   What Does EHCP Do           #
#################################
- EHCP installs and configures your web server, email server, and database services automatically.
- Creates a fully functional and secure web server that can be used to host multiple users and websites.
- Offers a control panel with multiple themes to automate the creation of anything website related.

#################################
#   What's Cool About EHCP      #
#################################
It ensures proper syntax by creating VHOST entries automatically, configures virtual email addresses and FTP accounts automatically, offers more features and functionality than other control panel software, and it's totally free!

#################################
#        Requirements           #
#################################
Clean installation of a Debian based Linux distribution.  It will work on all supported versions of Ubuntu out of the box.  

#################################
#  What is the [FoRcE Edition]  #
#################################
EHCP FoRcE Edition is the name I gave to this forked version of EHCP.  The original version can be downloaded and installed from www.ehcp.net.

This version differs from the original version slightly.  In fact, both the lead developer of the original EHCP release and I collaborate on updating EHCP.

In my version, custom FTP accounts to a custom file path can be created.  Also, php chmod and FTP chmod should both work since the apache user has been changed.  

The code is managed and maintained within GitHub (it is not in the original EHCP).

As new versions of Ubuntu are released, this version is updated more quickly.  

#################################
#        How to Install         #
#################################
Run the following commands to install the latest version of EHCP [FoRcE Edition] from a terminal:

mkdir -p ~/Downloads
sudo apt-get -y install git
cd ~/Downloads
if [ -e "ehcp" ]; then
    rm -rf "ehcp"
fi
git clone "https://github.com/earnolmartin/EHCP-Force-Edition.git" "ehcp"
cd ehcp
cd ehcp
sudo bash install.sh

Follow the prompts.

#################################
#        Access the CP          #
#################################
After successful installation, you may access your web panel at http://localhost/ or from the outside (assuming ports are forwarded) via http://myip/.

#################################
#        Support/Website        #
#################################
Please visit our website and use our forums at https://ehcpforce.ezpz.cc
