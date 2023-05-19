# Infosys_Cidr

This module allows you to add IPs to the `var/.maintenance.ip` allowlist file by use of a Console Command that takes in a an IP/CIDR string.

## Usage

`mr infosys:allow-ips-cidr {{IP/CIDR}}`

An IP/CIDR of 192.0.0.1/30 should yield:

`Added: 192.168.0.0`

`Added: 192.168.0.1`

`Added: 192.168.0.2`

`Added: 192.168.0.3`

and write these values to `var/.maintenance.ip`.

## Of Note
No checking for the size of the network defined is performed!