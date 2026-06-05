#!/bin/bash
# EHCP Force Edition - OpenDKIM Multi-Domain Automation Hook
# Automatically maps new domains, generates 2048-bit keys, builds TXT logs,
# injects raw custom BIND strings into customsettings, alerts the daemon loop,
# and enforces system-wide Postfix/OpenDKIM milter configurations dynamically.
# Includes inline backward compatibility for Ubuntu 14.04 Upstart actions.
# Includes RFC 1035 255-character TXT string splitting for old BIND compilers.
# Includes robust MySQL-native tracking to force database upgrades.
# Fixes Bash subshell variable loss using parent process herestring redirection.
#
# Original base components by Eric Arnol-Martin <earnolmartin@gmail.com>

SELECTOR="mail"
DKIM_BASE="/etc/opendkim"
KEY_DIR="$DKIM_BASE/keys"
DB_USER="ehcp"

# Foolproof extraction handling both single (') and double (") quotes safely
CONFIG_FILE="/var/www/new/ehcp/config.php"
DB_PASS=$(grep 'dbpass' "$CONFIG_FILE" 2>/dev/null | sed -E "s/.*dbpass\s*=\s*['\"]([^'\"]+)['\"].*/\1/")

# =====================================================================
# FUNCTIONS
# =====================================================================

function rootCheck(){
    # Check to make sure the script is running as root
    if [ "$(id -u)" != "0" ]; then
        echo "This script must be run as root" 1>&2
        exit 1
    fi
}

function aptgetInstall(){
    # Parameter $1 is a list of programs to install
    # Parameter $2 is used to specify runlevel 1 in front of the command to prevent daemons from automatically starting
    if [ -n "$noapt" ] ; then
        echo "skipping apt-get install for:$1"
        return
    fi

    # Try to install without any prompt, fallback to normal install if necessary
    cmd="apt-get -qq -y --no-remove --allow-unauthenticated install $1"
    if [ ! -z "$2" ]; then
        cmd="RUNLEVEL=1 $cmd"
    fi
    
    sh -c "DEBIAN_FRONTEND=noninteractive ${cmd} < /dev/null > /dev/null" > /dev/null 2>&1 
    
    if [ $? -ne 0 ]; then
        cmd="apt-get -qq -y --allow-unauthenticated install $1"
        if [ ! -z "$2" ]; then
            cmd="RUNLEVEL=1 $cmd"
        fi
        sh -c "DEBIAN_FRONTEND=noninteractive ${cmd} < /dev/null > /dev/null" > /dev/null 2>&1      
    fi
}

function manageService() {
    # Portable service action function mapping supporting Upstart (14.04) and Systemd (16.04+)
    local SERVICE_NAME="$1"
    local ACTION="$2"

    if [ -x "/bin/systemctl" ] || [ -x "/usr/bin/systemctl" ]; then
        systemctl "$ACTION" "$SERVICE_NAME" >/dev/null 2>&1
    else
        # Fallback strictly to standard SysV/Upstart execution tools
        service "$SERVICE_NAME" "$ACTION" >/dev/null 2>&1
    fi
}

function reloadInitDaemon() {
    if [ -x "/bin/systemctl" ] || [ -x "/usr/bin/systemctl" ]; then
        systemctl daemon-reload >/dev/null 2>&1
    else
        initctl reload-configuration >/dev/null 2>&1
    fi
}

# =====================================================================
# SYSTEM ENVIRONMENT VALIDATION BLOCK (Idempotent sanity checks)
# =====================================================================

rootCheck
CurDate=$(date +%Y_%m_%d_%s)

# Ensure dependencies are present
aptgetInstall "opendkim opendkim-tools"

# Ensure directories and group assignments exist with strict permissions
mkdir -p "$KEY_DIR"
mkdir -p /var/spool/postfix/opendkim

if [ "$(stat -c '%U:%G' /var/spool/postfix/opendkim)" != "opendkim:postfix" ]; then
    chown opendkim:postfix /var/spool/postfix/opendkim
    chmod 750 /var/spool/postfix/opendkim
fi

if ! groups opendkim | grep -q '\bpostfix\b'; then
    usermod -aG postfix opendkim
fi

# ---------------------------------------------------------------------
# LEGACY CONVERSION CLEANUP BLOCK (Deconstruct old single-domain setups)
# ---------------------------------------------------------------------
OPENDKIM_DEFAULT="/etc/default/opendkim"
if [ -f "$OPENDKIM_DEFAULT" ]; then
    # Purge old inet port definitions from default file to allow clean UNIX socket binding
    if grep -q "inet:12301@localhost" "$OPENDKIM_DEFAULT"; then
        sed -i '/inet:12301@localhost/d' "$OPENDKIM_DEFAULT"
        sed -i 's/^SOCKET=.*/SOCKET="local:\/var\/spool\/postfix\/opendkim\/opendkim.sock"/g' "$OPENDKIM_DEFAULT"
    fi
fi

OPENDKIM_CONF="/etc/opendkim.conf"
FORCE_OPENDKIM_RESTART=0

if [ -f "$OPENDKIM_CONF" ]; then
    # Strip explicit old raw port attributes if present inside key structures
    if grep -q "inet:12301@localhost" "$OPENDKIM_CONF"; then
        sed -i '/Socket.*inet:12301/d' "$OPENDKIM_CONF"
        FORCE_OPENDKIM_RESTART=1
    fi

    # Inject core global parameters if missing
    for PARAM in "KeyTable" "SigningTable" "Socket" "InternalHosts" "ExternalIgnoreList"; do
        if ! grep -q "^${PARAM}" "$OPENDKIM_CONF"; then
            case $PARAM in
                KeyTable)           echo "KeyTable                refile:$DKIM_BASE/key_table" >> "$OPENDKIM_CONF" ;;
                SigningTable)       echo "SigningTable            refile:$DKIM_BASE/signing_table" >> "$OPENDKIM_CONF" ;;
                Socket)             echo "Socket                  local:/var/spool/postfix/opendkim/opendkim.sock" >> "$OPENDKIM_CONF" ;;
                InternalHosts)      echo "InternalHosts           refile:$DKIM_BASE/TrustedHosts" >> "$OPENDKIM_CONF" ;;
                ExternalIgnoreList) echo "ExternalIgnoreList      refile:$DKIM_BASE/TrustedHosts" >> "$OPENDKIM_CONF" ;;
            esac
            FORCE_OPENDKIM_RESTART=1
        fi
    done
fi

# Dynamically validate and enforce TrustedHosts configurations
TRUSTED_HOSTS="$DKIM_BASE/TrustedHosts"
if [ ! -f "$TRUSTED_HOSTS" ] || [ ! -s "$TRUSTED_HOSTS" ]; then
    echo -e "127.0.0.1\nlocalhost\n::1" > "$TRUSTED_HOSTS"
    chown opendkim:opendkim "$TRUSTED_HOSTS"
    chmod 644 "$TRUSTED_HOSTS"
    FORCE_OPENDKIM_RESTART=1
else
    for LOOPBACK in "127.0.0.1" "localhost" "::1"; do
        if ! grep -q "^${LOOPBACK}$" "$TRUSTED_HOSTS"; then
            echo "$LOOPBACK" >> "$TRUSTED_HOSTS"
            FORCE_OPENDKIM_RESTART=1
        fi
    done
fi

# Enforce Postfix milter settings in main.cf if missing or migration needed
POSTFIX_MAIN="/etc/postfix/main.cf"
if [ -f "$POSTFIX_MAIN" ]; then
    NEED_POSTFIX_RESTART=0
    
    # Strip legacy inet definitions out of postfix configuration maps safely
    if grep -q "inet:localhost:12301" "$POSTFIX_MAIN"; then
        sed -i '/milter_protocol/d' "$POSTFIX_MAIN"
        sed -i '/milter_default_action/d' "$POSTFIX_MAIN"
        sed -i '/smtpd_milters/d' "$POSTFIX_MAIN"
        sed -i '/non_smtpd_milters/d' "$POSTFIX_MAIN"
        NEED_POSTFIX_RESTART=1
    fi

    if ! grep -q "^smtpd_milters" "$POSTFIX_MAIN"; then
        echo -e "\n# OpenDKIM Milter Integration Added by EHCP Hook" >> "$POSTFIX_MAIN"
        echo "milter_protocol = 6" >> "$POSTFIX_MAIN"
        echo "milter_default_action = accept" >> "$POSTFIX_MAIN"
        echo "smtpd_milters = unix:opendkim/opendkim.sock" >> "$POSTFIX_MAIN"
        echo "non_smtpd_milters = unix:opendkim/opendkim.sock" >> "$POSTFIX_MAIN"
        NEED_POSTFIX_RESTART=1
    fi

    # ---------------------------------------------------------------------
    # OPTIONAL CLI PARAMETER OVERRIDE BLOCK (Your original myhostname fix)
    # ---------------------------------------------------------------------
    if [ ! -z "$1" ]; then
        OVERRIDE_DOMAIN="$1"
        
        # Make a safety backup exactly like your legacy layout did
        cp "$POSTFIX_MAIN" "/etc/postfix/main_before_dkim_addition.cf_${CurDate}"
        
        hasMyHostName=$(grep -o "^myhostname" "$POSTFIX_MAIN")
        if [ -z "$hasMyHostName" ]; then
            echo "myhostname = ${OVERRIDE_DOMAIN}" >> "$POSTFIX_MAIN"
        else
            sed -i "s#^myhostname.*#myhostname = ${OVERRIDE_DOMAIN}#g" "$POSTFIX_MAIN"
        fi
        NEED_POSTFIX_RESTART=1
    fi

    if [ "$NEED_POSTFIX_RESTART" -eq 1 ]; then
        manageService "postfix" "restart"
    fi
fi

# =====================================================================
# CORE PROCESSING BLOCK
# =====================================================================

# Temporary build tables
cat /dev/null > "$DKIM_BASE/key_table.tmp"
cat /dev/null > "$DKIM_BASE/signing_table.tmp"

# Keep track of whether we actually added any new keys to decide if a sync is needed
NEW_RECORDS_ADDED=0

# 1. Fetch data by joining domains and ftpaccounts on panelusername
SQL_QUERY="SELECT d.domainname, f.ftpusername, d.panelusername 
           FROM domains d 
           INNER JOIN ftpaccounts f ON d.panelusername = f.panelusername;"

DOMAINS_DATA=$(mysql -u"$DB_USER" -p"$DB_PASS" ehcp -e "$SQL_QUERY" -N -B 2>/dev/null)

if [ -z "$DOMAINS_DATA" ]; then
    echo "No domains found or database connection failed."
    exit 1
fi

# CRITICAL PROCESS FIX: Use a Herestring redirection loop instead of a pipeline to prevent 
# child subshell variables from dropping out of bounds before Section 8 updates.
while IFS=$'\t' read -r DOMAIN FTPUSER PANELUSER; do
    [ -z "$DOMAIN" ] || [ -z "$FTPUSER" ] || [ -z "$PANELUSER" ] && continue
    
    DOMAIN_DIR="$KEY_DIR/$DOMAIN"
    
    # Check for legacy old-script footprint (1024-bit key or legacy file name structure)
    IS_LEGACY_KEY=0
    if [ -f "$DOMAIN_DIR/mail.private" ]; then
        # If openSSL parsing shows it's a 1024-bit key, mark it for regeneration
        if openssl rsa -in "$DOMAIN_DIR/mail.private" -text -noout 2>/dev/null | grep -q "Private-Key: (1024 bit)"; then
            IS_LEGACY_KEY=1
        fi
    fi

    # 2. Generate key pair if it doesn't exist yet OR if a legacy 1024 key requires replacement
    FORCE_DATABASE_REWRITE=0
    if [ ! -d "$DOMAIN_DIR" ] || [ "$IS_LEGACY_KEY" -eq 1 ]; then
        mkdir -p "$DOMAIN_DIR"
        rm -f "$DOMAIN_DIR/$SELECTOR.private" "$DOMAIN_DIR/$SELECTOR.txt"
        opendkim-genkey -b 2048 -d "$DOMAIN" -s "$SELECTOR" -D "$DOMAIN_DIR/"
        
        # Enforce exact permissions on the fresh private key
        chown -R opendkim:opendkim "$DOMAIN_DIR"
        chmod 600 "$DOMAIN_DIR/$SELECTOR.private"
        
        # Tracking flag indicating a newly generated or rotated disk key pair requires matching DB persistence
        FORCE_DATABASE_REWRITE=1
    fi
    
    # 3. Append to the OpenDKIM internal layout maps
    echo "$DOMAIN $DOMAIN:$SELECTOR:$DOMAIN_DIR/$SELECTOR.private" >> "$DKIM_BASE/key_table.tmp"
    echo "*@$DOMAIN $DOMAIN" >> "$DKIM_BASE/signing_table.tmp"
    
    # 4. Bulletproof Public Key Extraction and RFC-Compliant 255-char Chunking
    PUB_KEY_BASE64=$(openssl rsa -in "$DOMAIN_DIR/$SELECTOR.private" -pubout -outform DER 2>/dev/null | openssl base64 -A)
    FULL_DKIM_STR="v=DKIM1; k=rsa; p=${PUB_KEY_BASE64}"
    
    # Fold into 200 character pieces, enclose each in quotes, strip trailing newline/spaces
    CHUNKED_KEY=$(echo -n "$FULL_DKIM_STR" | fold -w 200 | sed 's/.*/"&" /' | tr -d '\n' | sed 's/ $//')
    
    # Assemble final valid BIND9 record map layout
    EHCP_DNS_VALUE="mail._domainkey.${DOMAIN}. IN TXT ( ${CHUNKED_KEY} )"
    
    # Generate the backup log file in the user's specific vhosts directory
    VHOST_DIR="/var/www/vhosts/$FTPUSER/$DOMAIN"
    if [ -d "$VHOST_DIR" ]; then
        OUTPUT_FILE="$VHOST_DIR/dkim_bind9_record.txt"
        echo "; --- BIND9 DKIM Record for $DOMAIN ---" > "$OUTPUT_FILE"
        echo "$EHCP_DNS_VALUE" >> "$OUTPUT_FILE"
        
        chown "$FTPUSER":www-data "$OUTPUT_FILE" 2>/dev/null || chown www-data:www-data "$OUTPUT_FILE"
        chmod 644 "$OUTPUT_FILE"
    fi

    # 5. DB Injection & Automated Self-Cleaning Upgrades (MySQL-Native Verification)
    # Look ONLY for a valid chunked record containing the parenthesis syntax to avoid trailing whitespace bugs in Bash strings
    VALID_RECORD_EXISTS=$(mysql -u"$DB_USER" -p"$DB_PASS" ehcp -e "SELECT id FROM customsettings WHERE domainname='$DOMAIN' AND name='customdns' AND value LIKE 'mail._domainkey%_(_%';" -N -B 2>/dev/null)
    
    # If a new key was generated, OR a legacy key was found, OR no properly chunked record exists in the DB, write it.
    if [ "$FORCE_DATABASE_REWRITE" -eq 1 ] || [ "$IS_LEGACY_KEY" -eq 1 ] || [ -z "$VALID_RECORD_EXISTS" ]; then
        
        # Clear out any un-chunked or broken records for this specific domain
        mysql -u"$DB_USER" -p"$DB_PASS" ehcp -e "DELETE FROM customsettings WHERE domainname='$DOMAIN' AND name='customdns' AND value LIKE 'mail._domainkey%';" 2>/dev/null
        
        # Escape internal quotes safely for the MySQL transaction
        ESCAPED_VALUE=$(echo "$EHCP_DNS_VALUE" | sed "s/'/\\\'/g")
        
        # Ingest fresh, RFC-compliant split row
        mysql -u"$DB_USER" -p"$DB_PASS" ehcp -e "INSERT INTO customsettings (host, reseller, panelusername, domainname, name, webservertype, value, value2, comment) VALUES (NULL, NULL, '$PANELUSER', '$DOMAIN', 'customdns', NULL, '$ESCAPED_VALUE', NULL, 'A DKIM public key record');" 2>/dev/null
        
        # Explicitly increment the tracking variable so Section 8 triggers
        NEW_RECORDS_ADDED=$((NEW_RECORDS_ADDED + 1))
    fi
done <<< "$DOMAINS_DATA"

# 6. Atomically swap map tables to prevent race conditions
mv "$DKIM_BASE/key_table.tmp" "$DKIM_BASE/key_table"
mv "$DKIM_BASE/signing_table.tmp" "$DKIM_BASE/signing_table"

chown opendkim:opendkim "$DKIM_BASE/key_table" "$DKIM_BASE/signing_table"
chmod 644 "$DKIM_BASE/key_table" "$DKIM_BASE/signing_table"

# 7. Reload OpenDKIM safely if tables changed or files were modified
if [ "$NEW_RECORDS_ADDED" -gt 0 ] || [ "$FORCE_OPENDKIM_RESTART" -eq 1 ]; then
    reloadInitDaemon
    manageService "opendkim" "restart"
fi

# 8. Trigger Daemon Operation Syncdns
if [ "$NEW_RECORDS_ADDED" -gt 0 ]; then
    DAEMON_SQL="INSERT INTO operations (op, user, ip, action, info, info2, tarih) 
                VALUES ('syncdns', 'admin', '127.0.0.1', '', '', '', NOW());"
    
    mysql -u"$DB_USER" -p"$DB_PASS" ehcp -e "$DAEMON_SQL" 2>/dev/null
fi
