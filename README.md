# ProxCP v1.7 Open Source
Full version of ProxCP v1.7 open source (last commercial use update was 9/25/2021)

- all source code is available for the Web and Daemon components
- all licensing/license checking code has been removed
- no limitations on code use, editing, or sharing
- ZERO support is provided for use of this code base or modifications made to it

# Useful Links
- https://www.digitalocean.com/community/tutorials/how-to-install-lamp-stack-on-ubuntu
- https://askubuntu.com/questions/1432704/force-install-php-7-packages-on-ubuntu-22-04-lts
- https://www.digitalocean.com/community/tutorials/how-to-install-node-js-on-ubuntu-20-04#option-3-installing-node-using-the-node-version-manager

# Build Instructions (Web)
- php composer.phar
- php composer.phar install

# Build Instructions (Daemon)
- Note: this is only required if you want to package all of the socket/*.js scripts into a single binary file...you could just run "node server.js"
- nvm use v12.22.5
- npm install -g pkg
- npm install -g forever
- cd /opt/proxcp && npm install
- pkg -t node12-linux-x64 . --options no-warnings

# System Requirements and Prereqs
# Proxmox
- Minimum Proxmox version: Proxmox VE 5.3 (released 2019-01-23)
- Highly recommended: install Proxmox from ISO; not on top of a Debian installation

# Web Server
- Note: the ProxCP web application will run on cPanel servers
- PHP 7.2 (ProxCP will not work on older versions)
- MySQLi extension
- PDO extension
- GD extension
- Curl with SSL extension
- JSON support
- XML support
- MBstring extension
- Iconv extension
- OpenSSL support extension
- PHP Mail support
- Any web server (tested with Apache)
- MySQL or MariaDB

# Daemon Server
- Don't feel like installing all these pre-reqs? ProxCP Daemon v1.5+ is available on docker ProxCP Daemon w/ Docker

- The ProxCP daemon is a packaged NodeJS application. It is recommended to run the daemon on a separate server from the ProxCP web server but this is not required. DO NOT run the ProxCP daemon from a web-accessible directory.

- NodeJS 12.x or above
- PHP 7.2 (ProxCP will not work on older versions)
- PHP CLI support
- MySQLi extension
- PDO extension
- GD extension
- Curl with SSL extension
- JSON support
- XML support
- MBstring extension
- Iconv extension
- OpenSSL support extension
- PHP Mail support (sendmail)
- MySQL or MariaDB
