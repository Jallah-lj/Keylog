import nmap
import argparse
import sys
import os

def scan_target(target, ports, scan_type):
    nm = nmap.PortScanner()
    arguments = ''
    
    if scan_type == 'comprehensive':
        # -sS: SYN Scan (stealth)
        # -sV: Version detection
        # -sC: Default scripts
        # -A: Aggressive (OS, version, script, traceroute)
        # -O: OS detection
        arguments = '-sS -sV -sC -A -O'
        if os.geteuid() != 0:
            print("Error: Comprehensive scan requires root privileges (sudo).")
            sys.exit(1)
    elif scan_type == 'syn':
        arguments = '-sS'
        if os.geteuid() != 0:
            print("Error: SYN scan requires root privileges (sudo).")
            sys.exit(1)
    elif scan_type == 'udp':
        arguments = '-sU'
        if os.geteuid() != 0:
            print("Error: UDP scan requires root privileges (sudo).")
            sys.exit(1)
    elif scan_type == 'version':
        arguments = '-sV'
    else:
        # Default/Connect scan
        arguments = '-sT'

    print(f"Starting {scan_type.upper()} scan on {target} ports: {ports}...")
    
    try:
        # Check if hosts are up even if they block ping
        arguments += ' -Pn' 
        print(f"Running nmap with args: {arguments}")
        
        nm.scan(hosts=target, ports=ports, arguments=arguments)
    except Exception as e:
        print(f"\nCritical Error during scan: {e}")
        print("Ensure 'nmap' is installed and you have necessary permissions.")
        return

    if not nm.all_hosts():
        print(f"\nNo hosts found or host is down: {target}")
        return

    for host in nm.all_hosts():
        print('\n' + '=' * 60)
        print(f'Host: {host} ({nm[host].hostname()})')
        print(f'State: {nm[host].state()}')
        
        # OS Detection result
        if 'osmatch' in nm[host] and nm[host]['osmatch']:
            print(f"OS Detection: {nm[host]['osmatch'][0]['name']} (Accuracy: {nm[host]['osmatch'][0]['accuracy']}%)")

        for proto in nm[host].all_protocols():
            print('-' * 60)
            print(f'Protocol: {proto.upper()}')
            
            lport = nm[host][proto].keys()
            sorted_ports = sorted(lport)
            
            print(f"{'PORT':<10} {'STATE':<10} {'SERVICE':<15} {'VERSION'}")
            print("-" * 60)
            
            for port in sorted_ports:
                service = nm[host][proto][port]
                state = service['state']
                name = service.get('name', 'unknown')
                version = service.get('version', '')
                product = service.get('product', '')
                extra = service.get('extrainfo', '')
                
                # Format version info
                version_info = []
                if product: version_info.append(product)
                if version: version_info.append(version)
                if extra: version_info.append(f"({extra})")
                
                version_str = " ".join(version_info)
                
                print(f"{port:<10} {state:<10} {name:<15} {version_str}")

def main():
    parser = argparse.ArgumentParser(description="Advanced Python Nmap Scanner")
    parser.add_argument("-t", "--target", required=True, help="Target Host/IP or range (e.g. 192.168.1.1)")
    parser.add_argument("-p", "--ports", default="1-1000", help="Port range to scan (default: 1-1000)")
    parser.add_argument("-s", "--scan-type", choices=['connect', 'syn', 'udp', 'version', 'comprehensive'], default='connect', help="Type of scan to perform")
    
    args = parser.parse_args()
    
    scan_target(args.target, args.ports, args.scan_type)

if __name__ == "__main__":
    main()
