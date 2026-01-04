import csv
import socket
import os

IPV4_CSV = 'GeoLite2-Country-Blocks-IPv4.csv'
IPV6_CSV = 'GeoLite2-Country-Blocks-IPv6.csv'
LOC_CSV  = 'GeoLite2-Country-Locations-en.csv'
OUTPUT_FILE = 'GeoDB.dat'
TMP_TEXT = 'geodb_load.txt'

def build_fixed_db():
    names = {}
    print("Step 1: Loading Country Map...")
    with open(LOC_CSV, 'r', encoding='utf-8') as f:
        for r in csv.DictReader(f):
            cc = r['country_iso_code']
            if cc:
                # 2 chars + null terminator
                names[r['geoname_id']] = cc[:2].lower().encode('ascii') + b'\x00'

    records = []

    # Step 2: Critical Metadata
    # Key 0: The version string (16 nulls)
    records.append((b'\x00' * 16, b'-- Webalizer GeoDB 20210301-1\x00'))
    # Key 1: The copyright string (15 nulls + 0x01)
    records.append((b'\x00' * 15 + b'\x01', b'-- Copyright (c) 2021  Bradford L. Barrett\x00'))
    
    # Step 3: IPv4 Processing (12-byte padding + End IP)
    print("Step 2: Processing IPv4 Ranges...")
    with open(IPV4_CSV, 'r', encoding='utf-8') as f:
        for r in csv.DictReader(f):
            gid = r['geoname_id'] or r['registered_country_geoname_id']
            cc_val = names.get(gid, b'??\x00')
            net, mask = r['network'].split('/')
            mask = int(mask)
            
            start_ip_int = int.from_bytes(socket.inet_aton(net), 'big')
            end_ip_int = start_ip_int + (1 << (32 - mask)) - 1
            
            key = b'\x00' * 12 + end_ip_int.to_bytes(4, 'big')
            if end_ip_int > 1:
                records.append((key, cc_val))

    # Step 4: IPv6 Processing (Full 16-byte End IP)
    if os.path.exists(IPV6_CSV):
        print("Step 3: Processing IPv6 Ranges...")
        with open(IPV6_CSV, 'r', encoding='utf-8') as f:
            for r in csv.DictReader(f):
                gid = r['geoname_id'] or r['registered_country_geoname_id']
                cc_val = names.get(gid, b'??\x00')
                net, mask = r['network'].split('/')
                mask = int(mask)
                
                # Convert IPv6 to 128-bit integer
                start_ip_bytes = socket.inet_pton(socket.AF_INET6, net)
                start_ip_int = int.from_bytes(start_ip_bytes, 'big')
                
                # Calculate the End IP of the IPv6 block
                end_ip_int = start_ip_int + (1 << (128 - mask)) - 1
                key = end_ip_int.to_bytes(16, 'big')
                
                records.append((key, cc_val))

    print("Step 4: Sorting %d total records..." % len(records))
    # Binary sorting is critical for B-Tree performance and lookup
    records.sort(key=lambda x: x[0])

    print("Step 5: Creating Load File...")
    with open(TMP_TEXT, 'w') as f:
        f.write("VERSION=3\nformat=print\ntype=btree\ndb_pagesize=4096\nHEADER=END\n")
        for key, val in records:
            k_hex = "".join(["\\%02x" % b for b in key])
            v_hex = "".join(["\\%02x" % b for b in val])
            f.write(" " + k_hex + "\n")
            f.write(" " + v_hex + "\n")

    print("Step 6: Building Database...")
    cmd = "db_load"
    for c in ["db5.3_load", "db4.8_load", "db_load"]:
        if os.system("which %s > /dev/null 2>&1" % c) == 0:
            cmd = c
            break
            
    os.system("%s %s < %s" % (cmd, OUTPUT_FILE, TMP_TEXT))
    
    if os.path.exists(TMP_TEXT): os.remove(TMP_TEXT)
    print("Done! %s created with IPv4 and IPv6 support." % OUTPUT_FILE)

if __name__ == "__main__":
    build_fixed_db()
