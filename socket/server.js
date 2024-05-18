console.warn = function() {};

var fs = require('fs');
var fsj = require('fs-jetpack');
var https = require('https');
var logger = require('winston');
logger.remove(logger.transports.Console);
logger.add(logger.transports.Console, {colorize:true,timestamp:true});
logger.add(logger.transports.File, {
	level: 'info',
	colorize: false,
	timestamp: true,
	filename: 'proxcp-socket.log',
	maxsize: 104857600,
	maxFiles: 5,
	json: false,
	tailable: true
});
const EMAIL_REGEX = /\S+@\S+\.\S+/;
const DOMAIN_REGEX = /^([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}$/;
var GREENLOCK_INIT = false;
const GREENLOCK_OPTIONS = {
	configDir: process.cwd() + '/keys',
	packageAgent: 'proxcp-socket/1.0.0',
	maintainerEmail: 'hello@proxcp.com',
	staging: false,
	notify: function(event, details) {
		if('error' === event) {
			logger.info(details);
		}
	},
	packageRoot: process.cwd()
};
try {
	var server = https.createServer({
		key: fs.readFileSync(process.cwd() + '/keys/domain.key'),
		cert: fs.readFileSync(process.cwd() + '/keys/domain.crt'),
		ca: fs.readFileSync(process.cwd() + '/keys/ca.crt')
	});
	yeet(GREENLOCK_INIT, server);
}catch(err) {
	if(err instanceof Error) {
		if(err.code === 'ENOENT') {
			logger.info('[PROXCP] Error: file not found');
			logger.info('[PROXCP] Error code: ' + err.message);
			const readline = require('readline');
			const rl = readline.createInterface({ input: process.stdin, output: process.stdout });
			rl.question("ProxCP SSL certificate not found. Would you like to generate one with Let's Encrypt certbot? (y/N) ", function(confirm) {
				if(confirm.trim().toLowerCase() == 'y') {
					rl.question("Enter an email address for Let's Encrypt registration: ", function(le_emailaddress) {
						le_emailaddress = le_emailaddress.trim().toLowerCase();
						if(EMAIL_REGEX.test(le_emailaddress)) {
							rl.question("Enter the domain for the SSL certificate (i.e. proxcp.mydomain.com): ", function(le_domain) {
								le_domain = le_domain.trim().toLowerCase();
								if(DOMAIN_REGEX.test(le_domain)) {
									rl.question("Enter the base directory for the " + le_domain + " domain (i.e. /var/www/html or /home/user/public_html): ", function(le_path) {
										if(le_path && le_path.trim()) {
											le_path = le_path.trim();
											rl.question("Confirm the following information is correct:\n\nEmail: " + le_emailaddress + "\nDomains: [ " + le_domain + ", www." + le_domain + " ]*\nBase Directory: " + le_path + "\n\n* Ensure each of these domains has an A Record that points to this server.\n\n(y/N) ", function(le_allgood) {
												if(le_allgood.toLowerCase() == 'y') {
													var GreenLock = require('greenlock');
													var greenlock = GreenLock.create(GREENLOCK_OPTIONS);
													greenlock.manager.defaults({
														agreeToTerms: true,
														subscriberEmail: le_emailaddress
													});
													greenlock.add({
														subject: le_domain,
														altnames: [le_domain, 'www.'+le_domain],
														challenges: {
															"http-01": {
																module: "acme-http-01-webroot",
																webroot: le_path + "/.well-known/acme-challenge"
															}
														}
													}).then(function() {
														GREENLOCK_INIT = true;
														logger.info('[PROXCP] Getting SSL certificate from Let\'s Encrypt - please wait...');
													});
													greenlock.get({servername: le_domain}).then(function(pems) {
														if(pems && pems.pems.privkey && pems.pems.cert && pems.pems.chain && pems.site.subject == le_domain) {
															fsj.symlink(process.cwd() + '/keys/live/' + le_domain + '/chain.pem', process.cwd() + '/keys/ca.crt');
															fsj.symlink(process.cwd() + '/keys/live/' + le_domain + '/cert.pem', process.cwd() + '/keys/domain.crt');
															fsj.symlink(process.cwd() + '/keys/live/' + le_domain + '/privkey.pem', process.cwd() + '/keys/domain.key');
															logger.info('[PROXCP] SSL certificate installed successfully!');
															if(fsj.exists(process.cwd() + '/config.js') == 'file') {
																logger.info('[PROXCP] Config file found. Loading Daemon...');
																yeet(GREENLOCK_INIT, null);
															}else{
																logger.info('[PROXCP] Config file not found. Performing first time setup.');
																logger.info('[PROXCP] NOTICE: Do not install this Daemon inside a web-accessible directory.');
																firstTimeSetup(readline);
															}
														}
													}).catch(function(e) {
														logger.info(e);
													});
													rl.close();
												}else{
													logger.info('[PROXCP] Ok, we can start over. Exiting...');
													rl.close();
													process.exit(1);
												}
											});
										}else{
											logger.info('[PROXCP] Domain path seems to be invalid. Exiting...');
											rl.close();
											process.exit(1);
										}
									});
								}else{
									logger.info('[PROXCP] Invalid domain. Exiting...');
									rl.close();
									process.exit(1);
								}
							});
						}else{
							logger.info('[PROXCP] Invalid email address. Exiting...');
							rl.close();
							process.exit(1);
						}
					});
				}else{
					logger.info('[PROXCP] A SSL certificate is required for this Daemon. You can use our wizard to generate one for you or install your own certificate according to https://docs.proxcp.com/index.php?title=ProxCP_Installation');
					logger.info('[PROXCP] No SSL certificate found. Exiting...');
					rl.close();
					process.exit(1);
				}
			});
		}else{
			throw err;
		}
	}
}

function firstTimeSetup(readline) {
	var cfg_socketPort = '8000';
	var cfg_socketIP = '';
	var cfg_socketOrigins = '';
	var cfg_sqlHost = '';
	var cfg_sqlUser = '';
	var cfg_sqlPassword = '';
	var cfg_sqlDB = '';
	var cfg_vncp_secret_key = '';
	var cfg_timezone = '';
	var cfg_company_name = '';
	var cfg_cron_to_email = '';

	const rl = readline.createInterface({ input: process.stdin, output: process.stdout });
	rl.question("Which port should the Daemon use (recommended: 8000)? ", function(q_socketPort) {
		cfg_socketPort = q_socketPort.trim();
		rl.question("What is this server's public IP address? ", function(q_socketIP) {
			cfg_socketIP = q_socketIP.trim();
			rl.question("What is the domain of your ProxCP Web installation (i.e. proxcp.domain.com)? ", function(q_socketOrigins) {
				cfg_socketOrigins = 'https://' + q_socketOrigins.trim() + ':443';
				rl.question("MySQL Host/IP Address: ", function(q_sqlHost) {
					cfg_sqlHost = q_sqlHost.trim();
					rl.question("MySQL User: ", function(q_sqlUser) {
						cfg_sqlUser = q_sqlUser.trim();
						rl.question("MySQL Password: ", function(q_sqlPassword) {
							cfg_sqlPassword = q_sqlPassword.trim();
							rl.question("MySQL Database Name: ", function(q_sqlDB) {
								cfg_sqlDB = q_sqlDB.trim();
								rl.question("Enter the secret key from ProxCP Web: ", function(q_vncp_secret_key) {
									cfg_vncp_secret_key = q_vncp_secret_key.trim();
									logger.info('[PROXCP] Do not share this key! It protects encryption.');
									rl.question("Daemon timezone (Options: https://www.php.net/manual/en/timezones.php) (Example: America/New_York): ", function(q_timezone) {
										cfg_timezone = q_timezone.trim();
										rl.question("Your company name (used for emails): ", function(q_company_name) {
											cfg_company_name = q_company_name.trim();
											rl.question("Enter an email address to send cron job reports to: ", function(q_cron_to_email) {
												cfg_cron_to_email = q_cron_to_email.trim();
												var cfgstr = "module.exports = {\n  socketPort: " + cfg_socketPort + ",\n  socketIP: '" + cfg_socketIP + "',\n  socketOrigins: '" + cfg_socketOrigins + "',\n  sqlHost: '" + cfg_sqlHost + "',\n  sqlUser: '" + cfg_sqlUser + "',\n  sqlPassword: '" + cfg_sqlPassword + "',\n  sqlDB: '" + cfg_sqlDB + "',\n  vncp_secret_key: '" + cfg_vncp_secret_key + "',\n  timezone: '" + cfg_timezone + "',\n  company_name: '" + cfg_company_name + "',\n  cron_to_email: '" + cfg_cron_to_email + "',\n};";
												fsj.write(process.cwd() + '/config.js', cfgstr);
												rl.close();
												yeet(GREENLOCK_INIT, null);
											});
										});
									});
								});
							});
						});
					});
				});
			});
		});
	});
}

function yeet(gl_init, serv) {
	if(gl_init == false) {
		var GreenLock = require('greenlock');
		var greenlock = GreenLock.create(GREENLOCK_OPTIONS);
		gl_init = true;
		GREENLOCK_INIT = true;
	}
	if(fsj.exists(process.cwd() + '/config.js') == false) {
		logger.info('[PROXCP] Config file not found. Performing first time setup.');
		logger.info('[PROXCP] NOTICE: Do not install this Daemon inside a web-accessible directory.');
		const readline = require('readline');
		firstTimeSetup(readline);
		return;
	}
	var config = require(process.cwd() + '/config');
	var lxc = require('./lxc');
	var kvm = require('./kvm');
	var cloud = require('./cloud');
	var admin = require('./admin');
	if(!serv) {
		var server = https.createServer({
			key: fs.readFileSync(process.cwd() + '/keys/domain.key'),
			cert: fs.readFileSync(process.cwd() + '/keys/domain.crt'),
			ca: fs.readFileSync(process.cwd() + '/keys/ca.crt')
		});
	}else{
		var server = serv;
	}
	var io = require('socket.io')(server);
	var CronJob = require('cron').CronJob;
	var cron = require('./cron');

	logger.info('[PROXCP] Socket listening on https://' + config.socketIP + ':' + config.socketPort);

	var connections = {};
	io.set('origins', config.socketOrigins);
	io.on('connection', function(socket) {
		logger.info('[PROXCP] Connected socket ' + socket.id);
		socket.on('addUserConnection', function(data) {
			connections[socket.id] = data;
		});
		socket.on('disconnect', function() {
			logger.info('[PROXCP] Disconnected socket ' + socket.id);
			delete connections[socket.id];
		});

		// LXC
		socket.on('LXCStatusCheckReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:LXC] Request status check on ' + data.hb_account_id);
				lxc.getStatus(data, socket, connections, logger);
			}
		});
		socket.on('LXCStartReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:LXC] Request start on ' + data.hb_account_id);
				lxc.start(data, socket, connections, logger);
			}
		});
		socket.on('LXCShutdownReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:LXC] Request shutdown on ' + data.hb_account_id);
				lxc.shutdown(data, socket, connections, logger);
			}
		});
		socket.on('LXCRestartReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:LXC] Request restart on ' + data.hb_account_id);
				lxc.restart(data, socket, connections, logger);
			}
		});
		socket.on('LXCKillReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:LXC] Request kill on ' + data.hb_account_id);
				lxc.kill(data, socket, connections, logger);
			}
		});
		socket.on('LXCCreateBackupReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:LXC] Request backup creation on ' + data.aid);
				lxc.createbackup(data, socket, connections, logger);
			}
		});
		socket.on('LXCRemoveBackupReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:LXC] Request backup removal on ' + data.aid + ' of ' + data.volid);
				lxc.removebackup(data, socket, connections, logger);
			}
		});
		socket.on('LXCRestoreBackupReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:LXC] Request backup restore on ' + data.aid + ' of ' + data.volid);
				lxc.restorebackup(data, socket, connections, logger);
			}
		});
		socket.on('LXCRebuildReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:LXC] Request rebuild on ' + data.aid);
				lxc.rebuild(data, socket, connections, logger);
			}
		});
		socket.on('LXCEnableTAPReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:LXC] Request to enable TUN/TAP on ' + data.aid);
				lxc.enabletap(data, socket, connections, logger);
			}
		});
		socket.on('LXCDisableTAPReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:LXC] Request to disable TUN/TAP on ' + data.aid);
				lxc.disabletap(data, socket, connections, logger);
			}
		});
		socket.on('LXCChangePWReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:LXC] Request to change root password on ' + data.aid);
				lxc.changepw(data, socket, connections, logger);
			}
		});
		socket.on('LXCEnableOnbootReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:LXC] Request to enable onboot on ' + data.aid);
				lxc.enableonboot(data, socket, connections, logger);
			}
		});
		socket.on('LXCDisableOnbootReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:LXC] Request to disable onboot on ' + data.aid);
				lxc.disableonboot(data, socket, connections, logger);
			}
		});
		socket.on('LXCEnableQuotasReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:LXC] Request to enable quotas on ' + data.aid);
				lxc.enablequotas(data, socket, connections, logger);
			}
		});
		socket.on('LXCDisableQuotasReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:LXC] Request to disable quotas on ' + data.aid);
				lxc.disablequotas(data, socket, connections, logger);
			}
		});
		socket.on('LXCGetLogReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:LXC] Request task log on ' + data.aid);
				lxc.getlog(data, socket, connections, logger);
			}
		});
		socket.on('LXCEnablePrivateNetworkReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:LXC] Request enable private network on ' + data.aid);
				lxc.enableprivatenet(data, socket, connections, logger);
			}
		});
		socket.on('LXCDisablePrivateNetworkReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:LXC] Request disable private network on ' + data.aid);
				lxc.disableprivatenet(data, socket, connections, logger);
			}
		});
		socket.on('LXCAssignIPv6Req', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:LXC] Request IPv6 assignment on ' + data.aid);
				lxc.assignipv6(data, socket, connections, logger);
			}
		});
		socket.on('LXCFirewallOptionsReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:LXC] Set firewall options on ' + data.aid);
				lxc.setfwopts(data, socket, connections, logger);
			}
		});
		socket.on('LXCFirewallRuleReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:LXC] Add new firewall rule on ' + data.aid);
				lxc.addfwrule(data, socket, connections, logger);
			}
		});
		socket.on('LXCFirewallRemoveReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:LXC] Remove firewall rule on ' + data.aid);
				lxc.rmfwrule(data, socket, connections, logger);
			}
		});
		socket.on('LXCIfaceNet0Req', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:LXC] NET0 interface modification on ' + data.aid);
				lxc.pubiface(data, socket, connections, logger);
			}
		});
		socket.on('LXCIfaceNet1Req', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:LXC] NET1 interface modification on ' + data.aid);
				lxc.priviface(data, socket, connections, logger);
			}
		});
		socket.on('LXCFirewallEditReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:LXC] Editing firewall rule ' + data.pos + ' on ' + data.aid);
				lxc.editfw(data, socket, connections, logger);
			}
		});
		socket.on('LXCAddNATPortReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:LXC] Request to add NAT port forward on ' + data.aid);
				lxc.addnatport(data, socket, connections, logger);
			}
		});
		socket.on('LXCDelNATPortReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:LXC] Request to delete NAT port forward on ' + data.aid);
				lxc.delnatport(data, socket, connections, logger);
			}
		});
		socket.on('LXCAddNATDomainReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:LXC] Request to add NAT domain forward on ' + data.aid);
				lxc.addnatdomain(data, socket, connections, logger);
			}
		});
		socket.on('LXCDelNATDomainReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:LXC] Request to delete NAT domain forward on ' + data.aid);
				lxc.delnatdomain(data, socket, connections, logger);
			}
		});
		socket.on('LXCGetBackupConfReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:LXC] Request to get backup configuration for ' + data.volid + ' via ' + data.aid);
				lxc.getbackupconf(data, socket, connections, logger);
			}
		});
		socket.on('KVMGetBackupConfReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:KVM] Request to get backup configuration for ' + data.volid + ' via ' + data.aid);
				kvm.getbackupconf(data, socket, connections, logger);
			}
		});

		// KVM
		socket.on('KVMStatusCheckReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:KVM] Request status check on ' + data.hb_account_id);
				kvm.getStatus(data, socket, connections, logger);
			}
		});
		socket.on('KVMStartReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:KVM] Request start on ' + data.hb_account_id);
				kvm.start(data, socket, connections, logger);
			}
		});
		socket.on('KVMShutdownReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:KVM] Request shutdown on ' + data.hb_account_id);
				kvm.shutdown(data, socket, connections, logger);
			}
		});
		socket.on('KVMRestartReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:KVM] Request restart on ' + data.hb_account_id);
				kvm.restart(data, socket, connections, logger);
			}
		});
		socket.on('KVMKillReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:KVM] Request kill on ' + data.hb_account_id);
				kvm.kill(data, socket, connections, logger);
			}
		});
		socket.on('KVMCreateBackupReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:KVM] Request snapshot creation on ' + data.aid);
				kvm.createbackup(data, socket, connections, logger);
			}
		});
		socket.on('KVMRemoveBackupReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:KVM] Request snapshot removal on ' + data.aid + ' of ' + data.snapname);
				kvm.removebackup(data, socket, connections, logger);
			}
		});
		socket.on('KVMRestoreBackupReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:KVM] Request snapshot restore on ' + data.aid + ' of ' + data.snapname);
				kvm.restorebackup(data, socket, connections, logger);
			}
		});
		socket.on('KVMRebuildReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:KVM] Request rebuild on ' + data.aid);
				kvm.rebuild(data, socket, connections, logger);
			}
		});
		socket.on('KVMRebuildTemplateReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:KVM] Request template rebuild on ' + data.aid);
				kvm.rebuildtemplate(data, socket, connections, logger);
			}
		});
		socket.on('KVMEnableOnbootReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:KVM] Request to enable onboot on ' + data.aid);
				kvm.enableonboot(data, socket, connections, logger);
			}
		});
		socket.on('KVMDisableOnbootReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:KVM] Request to disable onboot on ' + data.aid);
				kvm.disableonboot(data, socket, connections, logger);
			}
		});
		socket.on('KVMEnableRNGReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:KVM] Request to enable RNG on ' + data.aid);
				kvm.enablerng(data, socket, connections, logger);
			}
		});
		socket.on('KVMDisableRNGReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:KVM] Request to disable RNG on ' + data.aid);
				kvm.disablerng(data, socket, connections, logger);
			}
		});
		socket.on('KVMChangeISOReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:KVM] Request ISO change on ' + data.hb_account_id);
				kvm.changeiso(data, socket, connections, logger);
			}
		});
		socket.on('KVMBootOrderReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:KVM] Request boot order change on ' + data.hb_account_id);
				kvm.bootorder(data, socket, connections, logger);
			}
		});
		socket.on('KVMGetLogReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:KVM] Request task log on ' + data.aid);
				kvm.getlog(data, socket, connections, logger);
			}
		});
		socket.on('KVMEnablePrivateNetworkReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:KVM] Request enable private network on ' + data.aid);
				kvm.enableprivatenet(data, socket, connections, logger);
			}
		});
		socket.on('KVMDisablePrivateNetworkReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:KVM] Request disable private network on ' + data.aid);
				kvm.disableprivatenet(data, socket, connections, logger);
			}
		});
		socket.on('KVMAssignIPv6Req', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:KVM] Request IPv6 assignment on ' + data.aid);
				kvm.assignipv6(data, socket, connections, logger);
			}
		});
		socket.on('KVMChangePWReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:KVM] Request to change root password on ' + data.aid);
				kvm.changepw(data, socket, connections, logger);
			}
		});
		socket.on('UserISODeleteReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:KVM] Request user ISO delete on ' + data.id);
				kvm.isodelete(data, socket, connections, logger);
			}
		});
		socket.on('KVMFirewallOptionsReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:KVM] Set firewall options on ' + data.aid);
				kvm.setfwopts(data, socket, connections, logger);
			}
		});
		socket.on('KVMFirewallRuleReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:KVM] Add new firewall rule on ' + data.aid);
				kvm.addfwrule(data, socket, connections, logger);
			}
		});
		socket.on('KVMFirewallRemoveReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:KVM] Remove firewall rule on ' + data.aid);
				kvm.rmfwrule(data, socket, connections, logger);
			}
		});
		socket.on('KVMIfaceNet0Req', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:KVM] NET0 interface modification on ' + data.aid);
				kvm.pubiface(data, socket, connections, logger);
			}
		});
		socket.on('KVMIfaceNet1Req', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:KVM] NET1 interface modification on ' + data.aid);
				kvm.priviface(data, socket, connections, logger);
			}
		});
		socket.on('KVMFirewallEditReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:KVM] Editing firewall rule ' + data.pos + ' on ' + data.aid);
				kvm.editfw(data, socket, connections, logger);
			}
		});
		socket.on('KVMAddNATPortReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:KVM] Request to add NAT port forward on ' + data.aid);
				kvm.addnatport(data, socket, connections, logger);
			}
		});
		socket.on('KVMDelNATPortReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:KVM] Request to delete NAT port forward on ' + data.aid);
				kvm.delnatport(data, socket, connections, logger);
			}
		});
		socket.on('KVMAddNATDomainReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:KVM] Request to add NAT domain forward on ' + data.aid);
				kvm.addnatdomain(data, socket, connections, logger);
			}
		});
		socket.on('KVMDelNATDomainReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:KVM] Request to delete NAT domain forward on ' + data.aid);
				kvm.delnatdomain(data, socket, connections, logger);
			}
		});
		socket.on('KVMBackupStatusReq', function(data) {
			if(data.length !== 0 && data.constructor === Array) {
				logger.info('[PROXCP:KVM] Request to check backup job status');
				kvm.backupstatus(data, socket, connections, logger);
			}
		});
		socket.on('KVMScheduleBackupReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:KVM] Request to schedule backup for ' + data.aid);
				kvm.schedulebackup(data, socket, connections, logger);
			}
		});
		socket.on('KVMScheduledBackupDelReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:KVM] Request to delete scheduled backup for ' + data.aid);
				kvm.delschbackup(data, socket, connections, logger);
			}
		});
		socket.on('LXCBackupStatusReq', function(data) {
			if(data.length !== 0 && data.constructor === Array) {
				logger.info('[PROXCP:LXC] Request to check backup job status');
				lxc.backupstatus(data, socket, connections, logger);
			}
		});
		socket.on('LXCScheduleBackupReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:LXC] Request to schedule backup for ' + data.aid);
				lxc.schedulebackup(data, socket, connections, logger);
			}
		});
		socket.on('LXCScheduledBackupDelReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:LXC] Request to delete scheduled backup for ' + data.aid);
				lxc.delschbackup(data, socket, connections, logger);
			}
		});

		// Public cloud
		socket.on('PubCloudCreateReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:CLOUD] Request new VM creation on ' + data.clpoolid);
				cloud.create(data, socket, connections, logger);
			}
		});
		socket.on('PubCloudQueryPoolReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:CLOUD] Request pool query on ' + data.clpoolid);
				cloud.querypool(data, socket, connections, logger);
			}
		});
		socket.on('PubCloudDeleteReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:CLOUD] Request delete VM '+data.hb_account_id+' on ' + data.clpoolid);
				cloud.delete(data, socket, connections, logger);
			}
		});
		socket.on('PubCloudCancelDeleteReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:CLOUD] Cancelling deletion request for ' + data.hb_account_id);
				cloud.cancel(data, socket, connections, logger);
			}
		});
		socket.on('PubCloudConfirmDeleteReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:CLOUD] Confirming deletion request for ' + data.hb_account_id + ' from ' + data.clpoolid);
				cloud.confirmdelete(data, socket, connections, logger);
			}
		});
		socket.on('PubCloudAssignIPReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:CLOUD] Assigning IPv4 from cloud pool for ' + data.aid);
				cloud.assignip(data, socket, connections, logger);
			}
		});
		socket.on('PubCloudRemoveIPReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:CLOUD] Removing ' + data.ip + ' from VM for ' + data.aid);
				cloud.removeip(data, socket, connections, logger);
			}
		});

		// Admin
		socket.on('ADMUserLockReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:ADMIN] Locking user ID ' + data.id);
				admin.lockuser(data, socket, connections, logger);
			}
		});
		socket.on('ADMUserUnlockReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:ADMIN] Unlocking user ID ' + data.id);
				admin.unlockuser(data, socket, connections, logger);
			}
		});
		socket.on('ADMUserPWReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:ADMIN] Changing password for user ID ' + data.id);
				admin.changepw(data, socket, connections, logger);
			}
		});
		socket.on('ADMResetBWReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:ADMIN] Resetting bandwidth monitor now for ID ' + data.id);
				admin.resetbw(data, socket, connections, logger);
			}
		});
		socket.on('ADMNodeDeleteReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:ADMIN] Deleting node ID ' + data.id);
				admin.deletenode(data, socket, connections, logger);
			}
		});
		socket.on('ADMTunDeleteReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:ADMIN] Deleting creds ID ' + data.id);
				admin.deletetun(data, socket, connections, logger);
			}
		});
		socket.on('ADMDHCPDeleteReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:ADMIN] Deleting DHCP server ID ' + data.id);
				admin.deletedhcp(data, socket, connections, logger);
			}
		});
		socket.on('ADMUserDeleteReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:ADMIN] Deleting user ID ' + data.id);
				admin.deleteuser(data, socket, connections, logger);
			}
		});
		socket.on('ADMQueryNATDNSReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:ADMIN] Querying DNS A record for ' + data.id);
				admin.querydnsa(data, socket, connections, logger);
			}
		});
		socket.on('ADMQueryStorageReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:ADMIN] Querying node storage for ' + data.id);
				admin.querystorage(data, socket, connections, logger);
			}
		});
		socket.on('ADMLXCDeleteReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:ADMIN] Deleting LXC ID ' + data.id);
				admin.deletelxc(data, socket, connections, logger);
			}
		});
		socket.on('ADMQueryNodesReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:ADMIN] Querying node stats for ' + data.id);
				admin.querynode(data, socket, connections, logger);
			}
		});
		socket.on('ADMLXCSuspendReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:ADMIN] Suspending LXC ID ' + data.id);
				admin.lxcsuspend(data, socket, connections, logger);
			}
		});
		socket.on('ADMLXCUnsuspendReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:ADMIN] Unsuspending LXC ID ' + data.id);
				admin.lxcunsuspend(data, socket, connections, logger);
			}
		});
		socket.on('ADMKVMSuspendReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:ADMIN] Suspending KVM ID ' + data.id);
				admin.kvmsuspend(data, socket, connections, logger);
			}
		});
		socket.on('ADMKVMUnsuspendReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:ADMIN] Unsuspending KVM ID ' + data.id);
				admin.kvmunsuspend(data, socket, connections, logger);
			}
		});
		socket.on('ADMKVMDeleteReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:ADMIN] Deleting KVM ID ' + data.id);
				admin.deletekvm(data, socket, connections, logger);
			}
		});
		socket.on('ADMLXCTempDeleteReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:ADMIN] Deleting LXC template ID ' + data.id);
				admin.deletelxctemp(data, socket, connections, logger);
			}
		});
		socket.on('ADMAPIDeleteReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:ADMIN] Deleting API pair ID ' + data.id);
				admin.deleteapi(data, socket, connections, logger);
			}
		});
		socket.on('ADMKVMTempDeleteReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:ADMIN] Deleting KVM template ID ' + data.id);
				admin.deletekvmtemp(data, socket, connections, logger);
			}
		});
		socket.on('ADMKVMISODeleteReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:ADMIN] Deleting KVM ISO ID ' + data.id);
				admin.deletekvmiso(data, socket, connections, logger);
			}
		});
		socket.on('ADMACLDeleteReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:ADMIN] Deleting ACL ID ' + data.id);
				admin.deleteacl(data, socket, connections, logger);
			}
		});
		socket.on('ADMQueryCloudReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:ADMIN] Querying cloud ID ' + data.id);
				admin.querycloud(data, socket, connections, logger);
			}
		});
		socket.on('ADMEditCloudReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:ADMIN] Editing cloud ID ' + data.id);
				admin.editcloud(data, socket, connections, logger);
			}
		});
		socket.on('ADMCloudSuspendReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:ADMIN] Suspending cloud ID ' + data.id);
				admin.cloudsuspend(data, socket, connections, logger);
			}
		});
		socket.on('ADMCloudUnsuspendReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:ADMIN] Unsuspending cloud ID ' + data.id);
				admin.cloudunsuspend(data, socket, connections, logger);
			}
		});
		socket.on('ADMCloudDeleteReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:ADMIN] Deleting cloud ID ' + data.id);
				admin.clouddelete(data, socket, connections, logger);
			}
		});
		socket.on('ADMQueryPropsReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:ADMIN] Querying VM ID ' + data.id);
				admin.queryvmprops(data, socket, connections, logger);
			}
		});
		socket.on('ADMRecordDeleteReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:ADMIN] Deleting DNS record ID ' + data.id);
				admin.deletednsrecord(data, socket, connections, logger);
			}
		});
		socket.on('ADMDomainDeleteReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:ADMIN] Deleting domain ID ' + data.id);
				admin.deletedomain(data, socket, connections, logger);
			}
		});
		socket.on('ADMPTRDeleteReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:ADMIN] Deleting PTR ID ' + data.id);
				admin.deleteptr(data, socket, connections, logger);
			}
		});
		socket.on('ADMIP2DeleteReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:ADMIN] Deleting IP2 ID ' + data.id);
				admin.deleteip2(data, socket, connections, logger);
			}
		});
		socket.on('ADMPrivateDeleteReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:ADMIN] Clearing private assignment ID ' + data.id);
				admin.deleteprivate(data, socket, connections, logger);
			}
		});
		socket.on('ADMPublicDeleteReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:ADMIN] Clearing public assignment ID ' + data.id);
				admin.deletepublic(data, socket, connections, logger);
			}
		});
		socket.on('ADMPublicClrReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:ADMIN] Deleting IP ID ' + data.id + ' from pool');
				admin.clrpublic(data, socket, connections, logger);
			}
		});
		socket.on('ADMSetIPReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:ADMIN] Assigning IP ID ' + data.id + ' to HBID ' + data.hbid);
				admin.setpublic(data, socket, connections, logger);
			}
		});
		socket.on('ADMIPv6AssignDeleteReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:ADMIN] Deleting IPv6 assignment ID ' + data.id);
				admin.deleteipv6assign(data, socket, connections, logger);
			}
		});
		socket.on('ADMIPv6PoolDeleteReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:ADMIN] Deleting IPv6 pool ID ' + data.id);
				admin.deleteipv6pool(data, socket, connections, logger);
			}
		});
		socket.on('ADMKVMCustomISODeleteReq', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:ADMIN] Deleting KVM custom ISO ID ' + data.id);
				admin.deletecustomiso(data, socket, connections, logger);
			}
		});
		socket.on('ADMQueryLvl1Req', function(data) {
			if(Object.keys(data).length !== 0 && data.constructor === Object) {
				logger.info('[PROXCP:ADMIN] Querying NAT node stats for ' + data.id);
				admin.querylvl1(data, socket, connections, logger);
			}
		});
	});
	setInterval(function() {
		logger.info('[PROXCP] Active socket connections: ' + JSON.stringify(connections));
	}, 60000);
	// Every day at 12:00PM noon
	new CronJob('0 12 * * *', function() {
		cron.run(logger);
	}, null, true, config.timezone);

	// Every 15 minutes
	new CronJob('*/15 * * * *', function() {
		cron.checkbw(logger);
	}, null, true, config.timezone);

	// Every day at 00:00AM midnight
	new CronJob('0 0 * * *', function() {
		cron.resetbw(logger);
	}, null, true, config.timezone);

	server.listen(config.socketPort, config.socketIP);

}
