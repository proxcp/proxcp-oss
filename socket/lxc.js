var config = require(process.cwd()+'/config');
var prox = require('./index');
var mysql = require('mysql2');

var connection;
function handleDisconnect() {
	connection = mysql.createConnection({
		host: config.sqlHost,
		user: config.sqlUser,
		password: config.sqlPassword,
		database: config.sqlDB
	});
	connection.connect(function(err) {
		if(err) {
			console.log('[PROXCP] Error when connecting to database: ', err);
			console.log('[PROXCP] Attempting to reconnect in 10 seconds...');
			setTimeout(handleDisconnect, 10000);
		}
	});
	connection.on('error', function(err) {
		console.log('[PROXCP] Database error: ', err);
		if(err.code === 'PROTOCOL_CONNECTION_LOST' || err.code === 'PROTOCOL_UNEXPECTED_PACKET') {
			console.log('[PROXCP:LXC] Attempting to reconnect...');
			handleDisconnect();
		}else{
			throw err;
		}
	});
}
handleDisconnect();

var exec = require('ssh-exec');
var MagicCrypt = require('magiccrypt');
crypto = config.vncp_secret_key;
crypto = crypto.split(".");
var mc = new MagicCrypt(crypto[0], 256, crypto[1]);

// Helper functions
function twoDigits(d) {
	if(0 <= d && d < 10) return "0" + d.toString();
    if(-10 < d && d < 0) return "-0" + (-1*d).toString();
    return d.toString();
}
Date.prototype.toMysqlFormat = function() {
	return this.getUTCFullYear() + "-" + twoDigits(1 + this.getUTCMonth()) + "-" + twoDigits(this.getUTCDate()) + " " + twoDigits(this.getUTCHours()) + ":" + twoDigits(this.getUTCMinutes()) + ":" + twoDigits(this.getUTCSeconds());
};
function timeConverter(UNIX_timestamp){
  var a = new Date(UNIX_timestamp * 1000);
  var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
  var year = a.getFullYear();
  var month = months[a.getMonth()];
  var date = a.getDate();
  var hour = a.getHours();
  var min = a.getMinutes();
  var sec = a.getSeconds();
  var time = date + ' ' + month + ' ' + year + ' ' + hour + ':' + min + ':' + sec ;
  return time;
}

var mt_rand = require('locutus/php/math/mt_rand');
var chunk_split = require('locutus/php/strings/chunk_split');
var rtrim = require('locutus/php/strings/rtrim');
function genpost(length) {
	var chars = 'abcdef0123456789';
	var random_string = '';
	var num_valid_chars = chars.length;
	for(var i = 0; i < length; i++) {
		var random_pick = mt_rand(1, num_valid_chars);
		var random_char = chars[random_pick - 1];
		random_string += random_char;
	}
	return random_string;
}
function genv6(count, prefix, subnet) {
	if(prefix.substr(prefix.length - 1) == ":") {
		prefix = prefix.slice(0, -1);
	}
	var r = [];
	var gen_num = null;
	if(subnet == 68 || subnet == 72 || subnet == 76 || subnet == 80) {
		gen_num = 12;
	}else if(subnet == 84 || subnet == 88 || subnet == 92 || subnet == 96) {
		gen_num = 8;
	}else if(subnet == 100 || subnet == 104 || subnet == 108 || subnet == 112) {
		gen_num = 4;
	}else if(subnet == 116 || subnet == 120 || subnet == 124 || subnet == 128) {
		return prefix;
	}else{
		gen_num = 16;
	}
	for(var i = 0; i < count; i++) {
		var chars = genpost(gen_num);
		var postfix = chunk_split(chars, 4, ':');
		postfix = rtrim(postfix, ":");
		r[i] = prefix + postfix;
	}
	return r;
}
function randomInt (low, high) {
    return Math.floor(Math.random() * (high - low + 1) + low);
}
function randPW(x) {
    var s = "";
    while(s.length < x && x > 0) {
        var r = Math.random();
        s += (r < 0.1 ? Math.floor(r*100) : String.fromCharCode(Math.floor(r * 26) + (r > 0.5 ? 97 : 65)));
	}
	return s;
}
function valDomain(x) {
	var reg = new RegExp("[^a-z0-9-.]", "i");
	return reg.test(x);
}

module.exports = {
	getStatus: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("respond" in data && "hb_account_id" in data) {
			if(data.respond == true) {
				connection.query('SELECT * FROM vncp_lxc_ct WHERE hb_account_id = ?', [data.hb_account_id], function(err, results) {
					if(err) {
						logger.info(err);
						socket.emit('LXCStatusCheckRes', response.status);
					}else{
						if(results.length == 1 && results[0].user_id == connections[socket.id]) {
							connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
								var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
								px.get('/pools/'+results[0].pool_id, {}, function(err, data) {
									if(err) {
										logger.info(err);
										socket.emit('LXCStatusCheckRes', response.status);
									}else{
										var vmid = data['members'][0]['vmid'];
										px.get('/nodes/'+results[0].node+'/lxc/'+vmid+'/status/current', {}, function(err, vm) {
											if(err) {
												logger.info(err);
												socket.emit('LXCStatusCheckRes', response.status);
											}else if(vm){
												response.status = vm.status;
												response.cpu = vm.cpu;
												response.cpus = vm.cpus;
												response.mem = vm.mem;
												response.maxmem = vm.maxmem;
												response.disk = vm.disk;
												response.maxdisk = vm.maxdisk;
												response.swap = vm.swap;
												response.maxswap = vm.maxswap;
												response.uptime = vm.uptime;
												socket.emit('LXCStatusCheckRes', response);
											}else{
												logger.info('Proxmox returned empty response');
												socket.emit('LXCStatusCheckRes', response.status);
											}
										});
									}
								});
							});
						}else{
							logger.info('Invalid user ID');
							socket.emit('LXCStatusCheckRes', response.status);
						}
					}
				});
			}
		}else{
			logger.info("Data structure missing input.");
			socket.emit('LXCStatusCheckRes', response.status);
		}
	},
	start: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("hb_account_id" in data) {
			connection.query('SELECT * FROM vncp_lxc_ct WHERE hb_account_id = ?', [data.hb_account_id], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('LXCStartRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
							px.get('/pools/'+results[0].pool_id, {}, function(err, data) {
								var vmid = data['members'][0]['vmid'];
								if(results[0].suspended == 0) {
									px.post('/nodes/'+results[0].node+'/lxc/'+vmid+'/status/start', {}, function(err, proc) {
										response.status = 'ok';
										socket.emit('LXCStartRes', response.status);
									});
								}else{
									logger.info('VM is suspended, not starting');
									socket.emit('LXCStartRes', response.status);
								}
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('LXCStartRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('LXCStartRes', response.status);
		}
	},
	shutdown: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("hb_account_id" in data) {
			connection.query('SELECT * FROM vncp_lxc_ct WHERE hb_account_id = ?', [data.hb_account_id], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('LXCShutdownRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
							px.get('/pools/'+results[0].pool_id, {}, function(err, data) {
								var vmid = data['members'][0]['vmid'];
								px.post('/nodes/'+results[0].node+'/lxc/'+vmid+'/status/shutdown', {}, function(err, proc) {
									response.status = 'ok';
									socket.emit('LXCShutdownRes', response.status);
								});
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('LXCShutdownRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('LXCShutdownRes', response.status);
		}
	},
	restart: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("hb_account_id" in data) {
			connection.query('SELECT * FROM vncp_lxc_ct WHERE hb_account_id = ?', [data.hb_account_id], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('LXCRestartRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
							px.get('/pools/'+results[0].pool_id, {}, function(err, data) {
								var vmid = data['members'][0]['vmid'];
								if(results[0].suspended == 0) {
									px.post('/nodes/'+results[0].node+'/lxc/'+vmid+'/status/stop', {}, function(err, proc) {
										var delayed = require('delayed');
										delayed.delay(function() {
											px.post('/nodes/'+results[0].node+'/lxc/'+vmid+'/status/start', {}, function(err, proc) {
												response.status = 'ok';
												socket.emit('LXCStartRes', response.status);
											});
										}, 10000);
									});
								}else{
									logger.info('VM is suspended, not restarting');
									socket.emit('LXCRestartRes', response.status);
								}
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('LXCRestartRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('LXCRestartRes', response.status);
		}
	},
	kill: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("hb_account_id" in data) {
			connection.query('SELECT * FROM vncp_lxc_ct WHERE hb_account_id = ?', [data.hb_account_id], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('LXCKillRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
							px.get('/pools/'+results[0].pool_id, {}, function(err, data) {
								var vmid = data['members'][0]['vmid'];
								px.post('/nodes/'+results[0].node+'/lxc/'+vmid+'/status/stop', {}, function(err, proc) {
									response.status = 'ok';
									socket.emit('LXCKillRes', response.status);
								});
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('LXCKillRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('LXCKillRes', response.status);
		}
	},
	backupstatus: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		response.tasks = [];
		for(var i = 0; i < data.length; i++) {
			var tnode = data[i].split(":")[1];
			var tupid = data[i];
			connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [tnode], function(err, node) {
				var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
				px.get('/nodes/'+tnode+'/tasks/'+tupid+'/status', {}, function(err, taskdata) {
					px.get('/nodes/'+tnode+'/tasks/'+tupid+'/log', {}, function(err, tasklog) {
						var ttasklog = [];
						for(var j = 0; j < tasklog.length; j++) {
							ttasklog.push(tasklog[j].t);
						}
						response.tasks.push({
							upid: tupid,
							status: taskdata.status,
							log: ttasklog
						});
					});
				});
			});
		}
		var delayed = require('delayed');
		delayed.delay(function() {
			response.status = 'ok';
			socket.emit('LXCBackupStatusRes', response);
		}, 1000);
	},
	createbackup: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		response.upid = 'none';
		if("aid" in data && "notification" in data) {
			connection.query('SELECT * FROM vncp_lxc_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('LXCCreateBackupRes', response);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						if(results[0].allow_backups == 1) {
							connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
								connection.query('SELECT email FROM vncp_users WHERE id = ?', [results[0].user_id], function(err, userobj) {
									var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
									px.get('/pools/'+results[0].pool_id, {}, function(err, pooldata) {
										var vmid = pooldata['members'][0]['vmid'];
										var opts = {
											all: 0,
											compress: 'lzo',
											mode: 'suspend',
											remove: 1,
											storage: node[0].backup_store,
											vmid: vmid
										};
										if(data.notification == 'yes') {
											opts.mailnotification = 'always';
											opts.mailto = userobj[0].email;
										}
										px.post('/nodes/'+results[0].node+'/vzdump', opts, function(err, out) {
											response.upid = out;
											response.status = 'ok';
											socket.emit('LXCCreateBackupRes', response);
										});
									});
								});
							});
						}else{
							logger.info('Backups are disabled for this VM');
							socket.emit('LXCCreateBackupRes', response);
						}
					}else{
						logger.info('Invalid user ID');
						socket.emit('LXCCreateBackupRes', response);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('LXCCreateBackupRes', response);
		}
	},
	removebackup: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data && "volid" in data) {
			connection.query('SELECT * FROM vncp_lxc_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('LXCRemoveBackupRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
							px.get('/pools/'+results[0].pool_id, {}, function(err, pooldata) {
								var vmid = pooldata['members'][0]['vmid'];
								if(data.volid.indexOf(vmid) > -1 && data.volid.indexOf('lxc') > -1) {
									var storage = data.volid.split(":");
									storage = storage[0];
									px.delete('/nodes/'+results[0].node+'/storage/'+storage+'/content/'+data.volid, {}, function(err, rmd) {
										response.status = 'ok';
										socket.emit('LXCRemoveBackupRes', response.status);
									});
								}
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('LXCRemoveBackupRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('LXCRemoveBackupRes', response.status);
		}
	},
	getbackupconf: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		response.conf = 'none';
		if("aid" in data && "volid" in data) {
			connection.query('SELECT * FROM vncp_lxc_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('LXCGetBackupConfRes', response);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
							px.get('/pools/'+results[0].pool_id, {}, function(err, pooldata) {
								var vmid = pooldata['members'][0]['vmid'];
								if(data.volid.indexOf(vmid) > -1 && data.volid.indexOf('lxc') > -1) {
									var opts = {
										volume: data.volid
									};
									px.get('/nodes/'+results[0].node+'/vzdump/extractconfig', opts, function(err, confdata) {
										var td = confdata.split("\n");
										response.conf = "IP: "+td[0].substring(1)+"<br />Arch: "+td[1].substring(6)+"<br />CPU Cores: "+td[3].substring(7)+"<br />Hostname: "+td[6].substring(10)+"<br />RAM (MB): "+td[7].substring(8)+"M<br />OS Type: "+td[10].substring(8)+"<br />Storage (GB): "+td[12].split("=")[1];
										response.status = 'ok';
										socket.emit('LXCGetBackupConfRes', response);
									});
								}
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('LXCGetBackupConfRes', response);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('LXCGetBackupConfRes', response);
		}
	},
	restorebackup: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data && "volid" in data) {
			connection.query('SELECT * FROM vncp_lxc_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('LXCRestoreBackupRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
							px.get('/pools/'+results[0].pool_id, {}, function(err, res) {
								var vmid = res['members'][0]['vmid'];
								px.post('/nodes/'+results[0].node+'/lxc/'+vmid+'/status/stop', {}, function(err, proc) {
									px.get('/nodes/'+results[0].node+'/lxc/'+vmid+'/config', {}, function(err, vmconfig) {
										if(data.volid.indexOf(vmid) > -1 && data.volid.indexOf('lxc') > -1) {
											var storage = vmconfig.rootfs.split(":");
											var oldostype = vmconfig.ostype;
											var newvm = {
												vmid: vmid,
												force: 1,
												ostemplate: data.volid,
												restore: 1,
												storage: storage[0],
												pool: results[0].pool_id,
												unprivileged: 1
											};
											var delayed = require('delayed');
											delayed.delay(function() {
												px.delete('/nodes/'+results[0].node+'/lxc/'+vmid, {}, function(err, proc) {
													delayed.delay(function() {
														px.post('/nodes/'+results[0].node+'/lxc', newvm, function(err, createvm) {
															delayed.delay(function() {
																px.get('/nodes/'+results[0].node+'/lxc/'+vmid+'/config', {}, function(err, vmconfig) {
																	connection.query('UPDATE vncp_lxc_ct SET os = ? WHERE hb_account_id = ?', [oldostype, data.aid], function(err, update) {
																		if(err) {
																			logger.info(err);
																			socket.emit('LXCRestoreBackupRes', response.status);
																		}else{
																			var date = new Date().toMysqlFormat();
																			connection.query('INSERT INTO vncp_users_rebuild_log (client_id, date, vmid, hostname) VALUES ('+results[0].user_id+', "'+date+'", '+vmid+', "restored_backup '+data.volid+'")', function(err, insert) {
																				if(err) {
																					logger.info(err);
																					socket.emit('LXCRestoreBackupRes', response.status);
																				}else{
																					response.status = 'ok';
																					socket.emit('LXCRestoreBackupRes', response.status);
																				}
																			});
																		}
																	});
																});
															}, 15000);
														});
													}, 15000);
												});
											}, 15000);
										}
									});
								});
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('LXCRestoreBackupRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('LXCRestoreBackupRes', response.status);
		}
	},
	rebuild: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data && "os" in data && "hostname" in data && "password" in data) {
			connection.query('SELECT * FROM vncp_lxc_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('LXCRebuildRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
							px.get('/pools/'+results[0].pool_id, {}, function(err, res) {
								var vmid = res['members'][0]['vmid'];
								px.post('/nodes/'+results[0].node+'/lxc/'+vmid+'/status/stop', {}, function(err, proc) {
									px.get('/nodes/'+results[0].node+'/lxc/'+vmid+'/config', {}, function(err, vmconfig) {
										if(data.os.search(new RegExp('debian', 'i')) > -1) {
											var ostype = 'debian';
										}else if(data.os.search(new RegExp('ubuntu', 'i')) > -1) {
											var ostype = 'ubuntu';
										}else if(data.os.search(new RegExp('alpine', 'i')) > -1) {
											var ostype = 'alpine';
										}else if(data.os.search(new RegExp('fedora', 'i')) > -1) {
											var ostype = 'fedora';
										}else if(data.os.search(new RegExp('opensuse', 'i')) > -1) {
											var ostype = 'opensuse';
										}else{
											var ostype = 'centos';
										}
										var storage = vmconfig.rootfs.split(":");
										var rootfs = vmconfig.rootfs.split("=");
										rootfs = rootfs[rootfs.length - 1];
										rootfs = rootfs.slice(0, -1);
										var newvm = {
											ostemplate: data.os,
											vmid: vmid,
											cmode: 'tty',
											cores: vmconfig.cores,
											cpulimit: vmconfig.cpulimit,
											cpuunits: vmconfig.cpuunits,
	                    description: vmconfig.description,
											hostname: data.hostname.trim(),
											memory: vmconfig.memory,
											net0: vmconfig.net0,
											onboot: 0,
											ostype: ostype,
											password: data.password,
											pool: results[0].pool_id,
											protection: 0,
											rootfs: ''+storage[0]+':'+rootfs,
											storage: storage[0],
											swap: vmconfig.swap,
											tty: 2,
											unprivileged: 1
										};
										if(vmconfig.net1 != null) {
											newvm.net1 = vmconfig.net1;
										}
										if(vmconfig.net10 != null) {
											newvm.net10 = vmconfig.net10;
										}
										var delayed = require('delayed');
										delayed.delay(function() {
											px.delete('/nodes/'+results[0].node+'/lxc/'+vmid, {}, function(err, proc) {
												delayed.delay(function() {
													px.post('/nodes/'+results[0].node+'/lxc', newvm, function(err, createvm) {
														delayed.delay(function() {
															connection.query('UPDATE vncp_lxc_ct SET os = ?, fw_enabled_net0 = 0, fw_enabled_net1 = 0 WHERE hb_account_id = ?', [ostype, data.aid], function(err, update) {
																if(err) {
																	logger.info(err);
																	socket.emit('LXCRebuildRes', response.status);
																}else{
																	var date = new Date().toMysqlFormat();
																	connection.query('INSERT INTO vncp_users_rebuild_log (client_id, date, vmid, hostname) VALUES ('+results[0].user_id+', "'+date+'", '+vmid+', "'+data.hostname.trim()+'")', function(err, insert) {
																		if(err) {
																			logger.info(err);
																			socket.emit('LXCRebuildRes', response.status);
																		}else{
																			response.status = 'ok';
																			socket.emit('LXCRebuildRes', response.status);
																		}
																	});
																}
															});
														}, 15000);
													});
												}, 15000);
											});
										}, 15000);
									});
								});
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('LXCRebuildRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('LXCRebuildRes', response.status);
		}
	},
	setfwopts: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data && "enable" in data && "policy_in" in data && "policy_out" in data && "log_level_in" in data && "log_level_out" in data) {
			connection.query('SELECT * FROM vncp_lxc_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('LXCFirewallOptionsRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
							px.get('/pools/'+results[0].pool_id, {}, function(err, pooldata) {
								var vmid = pooldata['members'][0]['vmid'];
								var fwopts = {
									enable: data.enable,
									policy_in: data.policy_in,
									policy_out: data.policy_out,
									log_level_in: data.log_level_in,
									log_level_out: data.log_level_out
								};
								px.put('/nodes/'+results[0].node+'/lxc/'+vmid+'/firewall/options', fwopts, function(err, set) {
									response.status = 'ok';
									socket.emit('LXCFirewallOptionsRes', response.status);
								});
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('LXCFirewallOptionsRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('LXCFirewallOptionsRes', response.status);
		}
	},
	addfwrule: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data && "action" in data && "type" in data && "enable" in data && "iface" in data && "proto" in data && "source" in data && "sport" in data && "dest" in data && "dport" in data && "comment" in data) {
			connection.query('SELECT * FROM vncp_lxc_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('LXCFirewallRuleRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
							px.get('/pools/'+results[0].pool_id, {}, function(err, pooldata) {
								var vmid = pooldata['members'][0]['vmid'];
								var fwopts = {
									action: data.action,
									type: data.type,
									enable: data.enable,
									iface: data.iface,
									proto: data.proto,
									source: data.source,
									sport: data.sport,
									dest: data.dest,
									dport: data.dport,
									comment: data.comment
								};
								px.post('/nodes/'+results[0].node+'/lxc/'+vmid+'/firewall/rules', fwopts, function(err, set) {
									if(err) {
										logger.info(err);
										socket.emit('LXCFirewallRuleRes', response.status);
									}else{
										response.status = 'ok';
										socket.emit('LXCFirewallRuleRes', response.status);
									}
								});
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('LXCFirewallRuleRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('LXCFirewallRuleRes', response.status);
		}
	},
	rmfwrule: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data && "pos" in data) {
			connection.query('SELECT * FROM vncp_lxc_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('LXCFirewallRemoveRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
							px.get('/pools/'+results[0].pool_id, {}, function(err, pooldata) {
								var vmid = pooldata['members'][0]['vmid'];
								px.delete('/nodes/'+results[0].node+'/lxc/'+vmid+'/firewall/rules/'+data.pos, {}, function(err, set) {
									if(err) {
										logger.info(err);
										socket.emit('LXCFirewallRemoveRes', response.status);
									}else{
										response.status = 'ok';
										socket.emit('LXCFirewallRemoveRes', response.status);
									}
								});
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('LXCFirewallRemoveRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('LXCFirewallRemoveRes', response.status);
		}
	},
	pubiface: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data && "action" in data) {
			connection.query('SELECT * FROM vncp_lxc_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('LXCIfaceNet0Res', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
							px.get('/pools/'+results[0].pool_id, {}, function(err, pooldata) {
								var vmid = pooldata['members'][0]['vmid'];
								px.get('/nodes/'+results[0].node+'/lxc/'+vmid+'/config', {}, function(err, resp) {
									if(data.action == 'enable') {
										var current = resp.net0;
										var opts = {
											net0: current + ',firewall=1'
										};
										px.put('/nodes/'+results[0].node+'/lxc/'+vmid+'/config', opts, function(err, set) {
											if(err) {
												logger.info(err);
												socket.emit('LXCIfaceNet0Res', response.status);
											}else{
												connection.query('UPDATE vncp_lxc_ct SET fw_enabled_net0 = 1 WHERE hb_account_id = ?', [data.aid], function(err, update) {
													if(err) {
														logger.info(err);
														socket.emit('LXCIfaceNet0Res', response.status);
													}else{
														response.status = 'ok';
														socket.emit('LXCIfaceNet0Res', response.status);
													}
												});
											}
										});
									}else if(data.action == 'disable') {
										var current = resp.net0;
										current = current.split(",");
										var fwindex = current.indexOf('firewall=1');
										current.splice(fwindex, 1);
										current = current.join(',');
										var opts = {
											net0: current
										};
										px.put('/nodes/'+results[0].node+'/lxc/'+vmid+'/config', opts, function(err, set) {
											if(err) {
												logger.info(err);
												socket.emit('LXCIfaceNet0Res', response.status);
											}else{
												connection.query('UPDATE vncp_lxc_ct SET fw_enabled_net0 = 0 WHERE hb_account_id = ?', [data.aid], function(err, update) {
													if(err) {
														logger.info(err);
														socket.emit('LXCIfaceNet0Res', response.status);
													}else{
														response.status = 'ok';
														socket.emit('LXCIfaceNet0Res', response.status);
													}
												});
											}
										});
									}
								});
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('LXCIfaceNet0Res', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('LXCIfaceNet0Res', response.status);
		}
	},
	priviface: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data && "action" in data) {
			connection.query('SELECT * FROM vncp_lxc_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('LXCIfaceNet1Res', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
							px.get('/pools/'+results[0].pool_id, {}, function(err, pooldata) {
								var vmid = pooldata['members'][0]['vmid'];
								px.get('/nodes/'+results[0].node+'/lxc/'+vmid+'/config', {}, function(err, resp) {
									if(data.action == 'enable') {
										var current = resp.net1;
										var opts = {
											net1: current + ',firewall=1'
										};
										px.put('/nodes/'+results[0].node+'/lxc/'+vmid+'/config', opts, function(err, set) {
											if(err) {
												logger.info(err);
												socket.emit('LXCIfaceNet1Res', response.status);
											}else{
												connection.query('UPDATE vncp_lxc_ct SET fw_enabled_net1 = 1 WHERE hb_account_id = ?', [data.aid], function(err, update) {
													if(err) {
														logger.info(err);
														socket.emit('LXCIfaceNet1Res', response.status);
													}else{
														response.status = 'ok';
														socket.emit('LXCIfaceNet1Res', response.status);
													}
												});
											}
										});
									}else if(data.action == 'disable') {
										var current = resp.net1;
										current = current.split(",");
										var fwindex = current.indexOf('firewall=1');
										current.splice(fwindex, 1);
										current = current.join(',');
										var opts = {
											net1: current
										};
										px.put('/nodes/'+results[0].node+'/lxc/'+vmid+'/config', opts, function(err, set) {
											if(err) {
												logger.info(err);
												socket.emit('LXCIfaceNet1Res', response.status);
											}else{
												connection.query('UPDATE vncp_lxc_ct SET fw_enabled_net1 = 0 WHERE hb_account_id = ?', [data.aid], function(err, update) {
													if(err) {
														logger.info(err);
														socket.emit('LXCIfaceNet1Res', response.status);
													}else{
														response.status = 'ok';
														socket.emit('LXCIfaceNet1Res', response.status);
													}
												});
											}
										});
									}
								});
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('LXCIfaceNet1Res', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('LXCIfaceNet1Res', response.status);
		}
	},
	enabletap: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data) {
			connection.query('SELECT * FROM vncp_lxc_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('LXCEnableTAPRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							connection.query('SELECT * FROM vncp_tuntap WHERE node = ?', [results[0].node], function(err, rcreds) {
								if(err) {
									logger.info(err);
									socket.emit('LXCEnableTAPRes', response.status);
								}else{
									if(rcreds.length > 0) {
										var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
										px.get('/pools/'+results[0].pool_id, {}, function(err, pooldata) {
											var vmid = pooldata['members'][0]['vmid'];
											var opts = {
												user: 'root',
												host: node[0].hostname,
												password: mc.decrypt(rcreds[0].password),
												port: rcreds[0].port
											};
											exec('printf \'lxc.cgroup.devices.allow: c 10:200 rwm\nlxc.mount.entry: /dev/net/tun dev/net/tun none bind,create=file\n\' >> /etc/pve/lxc/'+vmid+'.conf', opts, function(err, stdout, stderr) {
												connection.query('UPDATE vncp_lxc_ct SET tuntap = 1 WHERE hb_account_id = ?', [data.aid], function(err, update) {
													if(err) {
														logger.info(err);
														socket.emit('LXCEnableTAPRes', response.status);
													}else{
														response.status = 'ok';
														socket.emit('LXCEnableTAPRes', response.status);
													}
												});
											});
										});
									}else{
										logger.info('No TUN/TAP credentials exist for this node.');
										socket.emit('LXCEnableTAPRes', response.status);
									}
								}
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('LXCEnableTAPRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('LXCEnableTAPRes', response.status);
		}
	},
	disabletap: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data) {
			connection.query('SELECT * FROM vncp_lxc_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('LXCDisableTAPRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							connection.query('SELECT * FROM vncp_tuntap WHERE node = ?', [results[0].node], function(err, rcreds) {
								if(err) {
									logger.info(err);
									socket.emit('LXCDisableTAPRes', response.status);
								}else{
									if(rcreds.length > 0) {
										var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
										px.get('/pools/'+results[0].pool_id, {}, function(err, pooldata) {
											var vmid = pooldata['members'][0]['vmid'];
											var opts = {
												user: 'root',
												host: node[0].hostname,
												password: mc.decrypt(rcreds[0].password),
												port: rcreds[0].port
											};
											exec('grep -v "lxc.cgroup.devices.allow: c 10:200 rwm" /etc/pve/lxc/'+vmid+'.conf > '+vmid+'.temp && mv '+vmid+'.temp /etc/pve/lxc/'+vmid+'.conf && grep -v \'lxc.hook.autodev = sh -c "modprobe tun; cd ${LXC_ROOTFS_MOUNT}/dev; mkdir net; mknod net/tun c 10 200; chmod 0666 net/tun"\' /etc/pve/lxc/'+vmid+'.conf > '+vmid+'.temp && mv '+vmid+'.temp /etc/pve/lxc/'+vmid+'.conf', opts, function(err, stdout, stderr) {
												connection.query('UPDATE vncp_lxc_ct SET tuntap = 0 WHERE hb_account_id = ?', [data.aid], function(err, update) {
													if(err) {
														logger.info(err);
														socket.emit('LXCDisableTAPRes', response.status);
													}else{
														response.status = 'ok';
														socket.emit('LXCDisableTAPRes', response.status);
													}
												});
											});
										});
									}else{
										logger.info('No TUN/TAP credentials exist for this node.');
										socket.emit('LXCDisableTAPRes', response.status);
									}
								}
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('LXCDisableTAPRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('LXCDisableTAPRes', response.status);
		}
	},
	changepw: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data) {
			connection.query('SELECT * FROM vncp_lxc_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('LXCChangePWRes', response);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							connection.query('SELECT * FROM vncp_tuntap WHERE node = ?', [results[0].node], function(err, rcreds) {
								if(err) {
									logger.info(err);
									socket.emit('LXCChangePWRes', response);
								}else{
									if(rcreds.length > 0) {
										var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
										px.get('/pools/'+results[0].pool_id, {}, function(err, pooldata) {
											var vmid = pooldata['members'][0]['vmid'];
											var opts = {
												user: 'root',
												host: node[0].hostname,
												password: mc.decrypt(rcreds[0].password),
												port: rcreds[0].port
											};
											var newpw = randPW(12);
											var execstr = 'pct exec '+vmid+' -- bash -c \'echo -e "'+newpw+'\\n'+newpw+'" | passwd root\'';
											exec(execstr, opts, function(err, stdout, stderr) {
												if(err) {
													logger.info(err);
													socket.emit('LXCChangePWRes', response);
												}else{
													response.password = newpw;
													response.status = 'ok';
													socket.emit('LXCChangePWRes', response);
												}
											});
										});
									}else{
										logger.info('No TUN/TAP credentials exist for this node (lxc change password).');
										socket.emit('LXCChangePWRes', response);
									}
								}
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('LXCChangePWRes', response);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('LXCChangePWRes', response.status);
		}
	},
	enableonboot: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data) {
			connection.query('SELECT * FROM vncp_lxc_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('LXCEnableOnbootRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
							px.get('/pools/'+results[0].pool_id, {}, function(err, pooldata) {
								var vmid = pooldata['members'][0]['vmid'];
								var opts = {
									onboot: 1
								};
								px.put('/nodes/'+results[0].node+'/lxc/'+vmid+'/config', opts, function(err, set) {
									if(err) {
										logger.info(err);
										socket.emit('LXCEnableOnbootRes', response.status);
									}else{
										connection.query('UPDATE vncp_lxc_ct SET onboot = 1 WHERE hb_account_id = ?', [data.aid], function(err, update) {
											if(err) {
												logger.info(err);
												socket.emit('LXCEnableOnbootRes', response.status);
											}else{
												response.status = 'ok';
												socket.emit('LXCEnableOnbootRes', response.status);
											}
										});
									}
								});
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('LXCEnableOnbootRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('LXCEnableOnbootRes', response.status);
		}
	},
	disableonboot: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data) {
			connection.query('SELECT * FROM vncp_lxc_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('LXCDisableOnbootRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
							px.get('/pools/'+results[0].pool_id, {}, function(err, pooldata) {
								var vmid = pooldata['members'][0]['vmid'];
								var opts = {
									onboot: 0
								};
								px.put('/nodes/'+results[0].node+'/lxc/'+vmid+'/config', opts, function(err, set) {
									if(err) {
										logger.info(err);
										socket.emit('LXCDisableOnbootRes', response.status);
									}else{
										connection.query('UPDATE vncp_lxc_ct SET onboot = 0 WHERE hb_account_id = ?', [data.aid], function(err, update) {
											if(err) {
												logger.info(err);
												socket.emit('LXCDisableOnbootRes', response.status);
											}else{
												response.status = 'ok';
												socket.emit('LXCDisableOnbootRes', response.status);
											}
										});
									}
								});
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('LXCDisableOnbootRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('LXCDisableOnbootRes', response.status);
		}
	},
	enablequotas: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data) {
			connection.query('SELECT * FROM vncp_lxc_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('LXCEnableQuotasRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
							px.get('/pools/'+results[0].pool_id, {}, function(err, pooldata) {
								var vmid = pooldata['members'][0]['vmid'];
								px.get('/nodes/'+results[0].node+'/lxc/'+vmid+'/config', {}, function(err, vmconf) {
									px.post('/nodes/'+results[0].node+'/lxc/'+vmid+'/status/stop', {}, function(err, stoplxc) {
										var delayed = require('delayed');
										delayed.delay(function() {
											var quotas = vmconf.rootfs + ',quota=1';
											var opts = {
												rootfs: quotas
											};
											px.put('/nodes/'+results[0].node+'/lxc/'+vmid+'/config', opts, function(err, set) {
												connection.query('UPDATE vncp_lxc_ct SET quotas = 1 WHERE hb_account_id = ?', [data.aid], function(err, update) {
													if(err) {
														logger.info(err);
														socket.emit('LXCEnableQuotasRes', response.status);
													}else{
														response.status = 'ok';
														socket.emit('LXCEnableQuotasRes', response.status);
													}
												});
											});
										}, 5000);
									});
								});
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('LXCEnableQuotasRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('LXCEnableQuotasRes', response.status);
		}
	},
	disablequotas: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data) {
			connection.query('SELECT * FROM vncp_lxc_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('LXCDisableQuotasRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
							px.get('/pools/'+results[0].pool_id, {}, function(err, pooldata) {
								var vmid = pooldata['members'][0]['vmid'];
								px.get('/nodes/'+results[0].node+'/lxc/'+vmid+'/config', {}, function(err, vmconf) {
									px.post('/nodes/'+results[0].node+'/lxc/'+vmid+'/status/stop', {}, function(err, stoplxc) {
										var delayed = require('delayed');
										delayed.delay(function() {
											var quotas = vmconf.rootfs.split(',');
											var index = quotas.indexOf('quota=1');
											quotas.splice(index, 1);
											var str = '';
											for(var i = 0; i < quotas.length; i++) {
												str = str + quotas[i];
												if(i != (quotas.length - 1))
													str = str + ',';
											}
											var opts = {
												rootfs: str
											};
											px.put('/nodes/'+results[0].node+'/lxc/'+vmid+'/config', opts, function(err, set) {
												connection.query('UPDATE vncp_lxc_ct SET quotas = 0 WHERE hb_account_id = ?', [data.aid], function(err, update) {
													if(err) {
														logger.info(err);
														socket.emit('LXCDisableQuotasRes', response.status);
													}else{
														response.status = 'ok';
														socket.emit('LXCDisableQuotasRes', response.status);
													}
												});
											});
										}, 5000);
									});
								});
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('LXCDisableQuotasRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('LXCDisableQuotasRes', response.status);
		}
	},
	getlog: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data) {
			connection.query('SELECT * FROM vncp_lxc_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('LXCGetLogRes', response);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
							px.get('/pools/'+results[0].pool_id, {}, function(err, pooldata) {
								var vmid = pooldata['members'][0]['vmid'];
								px.get('/nodes/'+results[0].node+'/tasks', { vmid: vmid }, function(err, tasks) {
									response.log = '';
									for(var k = 0; k < tasks.length; k++) {
										var starttime = timeConverter(tasks[k].starttime);
										var endtime = timeConverter(tasks[k].endtime);
										response.log = response.log + starttime + ' - ' + endtime + ': ' + tasks[k].type + ' > ' + tasks[k].status + '&#13;&#10;';
									}
									response.status = 'ok';
									socket.emit('LXCGetLogRes', response);
								});
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('LXCGetLogRes', response);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('LXCGetLogRes', response.status);
		}
	},
	schedulebackup: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data) {
			connection.query('SELECT * FROM vncp_lxc_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('LXCScheduleBackupRes', response);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
							px.get('/pools/'+results[0].pool_id, {}, function(err, pooldata) {
								var vmid = pooldata['members'][0]['vmid'];
								connection.query('SELECT * FROM vncp_ct_backups WHERE hb_account_id = ?', [data.aid], function(err, ctbackups) {
									if(err) {
										logger.info(err);
										socket.emit('LXCScheduleBackupRes', response);
									}else{
										var maxfiles = 0;
										if(ctbackups[0].backuplimit > 0) {
											maxfiles = ctbackups[0].backuplimit;
											var opts = {
												starttime: data.time,
												all: 0,
												bwlimit: 0,
												compress: 'lzo',
												dow: data.dow.join(','),
												enabled: 1,
												mailnotification: 'failure',
												mailto: '',
												mode: 'snapshot',
												node: results[0].node,
												maxfiles: maxfiles,
												storage: node[0].backup_store,
												vmid: vmid.toString()
											};
											px.post('/cluster/backup', opts, function(err, scheduled) {
												response.status = 'ok';
												socket.emit('LXCScheduleBackupRes', response);
											});
										}else if(ctbackups[0].backuplimit < 0) {
											connection.query('SELECT * FROM vncp_settings WHERE item = ?', ['backup_limit'], function(err, defaultlimit) {
												if(err) {
													logger.info(err);
													socket.emit('LXCScheduleBackupRes', response);
												}else{
													maxfiles = parseInt(defaultlimit[0].value);
													var opts = {
														starttime: data.time,
														all: 0,
														bwlimit: 0,
														compress: 'lzo',
														dow: data.dow.join(','),
														enabled: 1,
														mailnotification: 'failure',
														mailto: '',
														mode: 'snapshot',
														node: results[0].node,
														maxfiles: maxfiles,
														storage: node[0].backup_store,
														vmid: vmid.toString()
													};
													px.post('/cluster/backup', opts, function(err, scheduled) {
														response.status = 'ok';
														socket.emit('LXCScheduleBackupRes', response);
													});
												}
											});
										}else{
											logger.info('Backup limit set to 0, scheduled backup not allowed.');
											socket.emit('LXCScheduleBackupRes', response);
										}
									}
								});
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('LXCScheduleBackupRes', response);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('LXCScheduleBackupRes', response);
		}
	},
	delschbackup: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data) {
			connection.query('SELECT * FROM vncp_lxc_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('LXCScheduleBackupDelRes', response);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
							px.delete('/cluster/backup/'+data.schid, {}, function(err, delsch) {
								response.status = 'ok';
								socket.emit('LXCScheduleBackupDelRes', response);
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('LXCScheduleBackupDelRes', response);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('LXCScheduleBackupDelRes', response);
		}
	},
	enableprivatenet: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data) {
			connection.query('SELECT * FROM vncp_lxc_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('LXCEnablePrivateNetworkRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							connection.query('SELECT * FROM vncp_private_pool WHERE available=? LIMIT 1', [1], function(err, selected) {
								if(err) {
									logger.info(err);
									socket.emit('LXCEnablePrivateNetworkRes', response.status);
								}else{
									if(selected.length > 0) {
										var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
										px.get('/pools/'+results[0].pool_id, {}, function(err, pooldata) {
											var vmid = pooldata['members'][0]['vmid'];
											if(selected[0].netmask == '255.255.255.0') {
												var cidr = '24';
											}else if(selected[0].netmask == '255.255.255.128') {
												var cidr = '25';
											}else if(selected[0].netmask == '255.255.255.192') {
												var cidr = '26';
											}else if(selected[0].netmask == '255.255.255.224') {
												var cidr = '27';
											}else if(selected[0].netmask == '255.255.255.240') {
												var cidr = '28';
											}else if(selected[0].netmask == '255.255.255.248') {
												var cidr = '29';
											}else{
												var cidr = '30';
											}
											var opts = {
												net1: 'name=eth1,bridge=vmbr1,ip=' + selected[0].address + '/' + cidr
											};
											px.put('/nodes/'+results[0].node+'/lxc/'+vmid+'/config', opts, function(err, device) {
												if(err) {
													logger.info(err);
													socket.emit('LXCEnablePrivateNetworkRes', response.status);
												}else{
													connection.query('UPDATE vncp_private_pool SET user_id=?, hb_account_id=?, available=0 WHERE (available=1 AND nodes LIKE ?) LIMIT 1', [results[0].user_id, data.aid, "%"+results[0].node+"%"], function(err, ip) {
														if(err) {
															logger.info(err);
															socket.emit('LXCEnablePrivateNetworkRes', response.status);
														}else{
															connection.query('UPDATE vncp_lxc_ct SET has_net1=1 WHERE hb_account_id=?', [data.aid], function(err, priv) {
																if(err) {
																	logger.info(err);
																	socket.emit('LXCEnablePrivateNetworkRes', response.status);
																}else{
																	response.status = 'ok';
																	socket.emit('LXCEnablePrivateNetworkRes', response.status);
																}
															});
														}
													});
												}
											});
										});
									}else{
										logger.info('No private pools found.');
										socket.emit('LXCEnablePrivateNetworkRes', response.status);
									}
								}
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('LXCEnablePrivateNetworkRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('LXCEnablePrivateNetworkRes', response.status);
		}
	},
	disableprivatenet: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data) {
			connection.query('SELECT * FROM vncp_lxc_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('LXCDisablePrivateNetworkRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
							px.get('/pools/'+results[0].pool_id, {}, function(err, pooldata) {
								var vmid = pooldata['members'][0]['vmid'];
								var opts = {
									delete: 'net1'
								};
								px.put('/nodes/'+results[0].node+'/lxc/'+vmid+'/config', opts, function(err, device) {
									if(err) {
										logger.info(err);
										socket.emit('LXCDisablePrivateNetworkRes', response.status);
									}else{
										connection.query('UPDATE vncp_private_pool SET user_id=0, hb_account_id=0, available=1 WHERE hb_account_id=?', [data.aid], function(err, ip) {
											if(err) {
												logger.info(err);
												socket.emit('LXCDisablePrivateNetworkRes', response.status);
											}else{
												connection.query('UPDATE vncp_lxc_ct SET has_net1=0 WHERE hb_account_id=?', [data.aid], function(err, priv) {
													if(err) {
														logger.info(err);
														socket.emit('LXCDisablePrivateNetworkRes', response.status);
													}else{
														response.status = 'ok';
														socket.emit('LXCDisablePrivateNetworkRes', response.status);
													}
												});
											}
										});
									}
								});
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('LXCDisablePrivateNetworkRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('LXCDisablePrivateNetworkRes', response.status);
		}
	},
	assignipv6: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data) {
			connection.query('SELECT * FROM vncp_lxc_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('LXCAssignIPv6Res', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query("SELECT * FROM vncp_ipv6_pool WHERE nodes LIKE ?", ["%"+results[0].node+"%"], function(err, v6pool) {
							if(err) {
								logger.info(err);
								socket.emit('LXCAssignIPv6Res', response.status);
							}else{
								if(v6pool.length > 0) {
									connection.query("SELECT * FROM vncp_settings WHERE item = ?", ['ipv6_mode'], function(err, v6mode) {
										if(err) {
											logger.info(err);
											socket.emit('LXCAssignIPv6Res', response.status);
										}else{
											if(v6mode[0].value == 'single') {
												var srand = randomInt(0, v6pool.length - 1);
												var ipv6 = genv6(1, v6pool[srand].subnet.split("/")[0], parseInt(v6pool[srand].subnet.split("/")[1]));
												connection.query('INSERT INTO vncp_ipv6_assignment (user_id, hb_account_id, address, ipv6_pool_id) VALUES (?, ?, ?, ?)', [results[0].user_id, data.aid, ipv6[0], v6pool[srand].id], function(err, insert) {
													if(err) {
														logger.info(err);
														socket.emit('LXCAssignIPv6Res', response.status);
													}else{
														connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
															var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
															px.get('/pools/'+results[0].pool_id, {}, function(err, pooldata) {
																var vmid = pooldata['members'][0]['vmid'];
																px.get('/nodes/'+results[0].node+'/lxc/'+vmid+'/config', {}, function(err, device) {
																	if(err) {
																		logger.info(err);
																		socket.emit('LXCAssignIPv6Res', response.status);
																	}else{
																		var dnet0 = device.net0.split(",");
																		if(dnet0.indexOf('bridge=vmbr10') != -1) {
																			var opts = {
																				net10: 'name=eth10,bridge=vmbr0,firewall=0,gw6='+v6pool[srand].subnet.split("/")[0]+'1,ip6='+ipv6[0]+'/'+v6pool[srand].subnet.split("/")[1]+',type=veth'
																			};
																		}else{
																			var ip6index = dnet0.indexOf('ip6=auto');
																			dnet0.splice(ip6index, 1);
																			dnet0 = dnet0.join(',');
																			var opts = {
																				net0: '' + dnet0 + ',gw6='+v6pool[srand].subnet.split("/")[0]+'1,ip6=' + ipv6[0] + '/' + v6pool[srand].subnet.split("/")[1]
																			};
																		}
																		px.put('/nodes/'+results[0].node+'/lxc/'+vmid+'/config', opts, function(err, ip6set) {
																			if(err) {
																				logger.info(err);
																				socket.emit('LXCAssignIPv6Res', response.status);
																			}else{
																				response.status = 'ok';
																				socket.emit('LXCAssignIPv6Res', response.status);
																			}
																		});
																	}
																});
															});
														});
													}
												});
											}else if(v6mode[0].value == 'subnet') {
												var ip6 = require('ip6');
												var srand = randomInt(0, v6pool.length - 1);
												var subnets = ip6.randomSubnet(v6pool[srand].subnet.split("/")[0], parseInt(v6pool[srand].subnet.split("/")[1]), 64, 1, true)[0];
												connection.query('INSERT INTO vncp_ipv6_assignment (user_id, hb_account_id, address, ipv6_pool_id) VALUES (?, ?, ?, ?)', [results[0].user_id, data.aid, subnets+"/64", v6pool[srand].id], function(err, insert) {
													if(err) {
														logger.info(err);
														socket.emit('LXCAssignIPv6Res', response.status);
													}else{
														connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
															var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
															px.get('/pools/'+results[0].pool_id, {}, function(err, pooldata) {
																var vmid = pooldata['members'][0]['vmid'];
																px.get('/nodes/'+results[0].node+'/lxc/'+vmid+'/config', {}, function(err, device) {
																	if(err) {
																		logger.info(err);
																		socket.emit('LXCAssignIPv6Res', response.status);
																	}else{
																		var dnet0 = device.net0.split(",");
																		if(dnet0.indexOf('bridge=vmbr10') != -1) {
																			var opts = {
																				net10: 'name=eth10,bridge=vmbr0,firewall=0,gw6='+v6pool[srand].subnet.split("/")[0]+'1,ip6='+subnets+'/64,type=veth'
																			};
																		}else{
																			var ip6index = dnet0.indexOf('ip6=auto');
																			dnet0.splice(ip6index, 1);
																			dnet0 = dnet0.join(',');
																			var opts = {
																				net0: '' + dnet0 + ',gw6='+v6pool[srand].subnet.split("/")[0]+'1,ip6=' + subnets + '/64'
																			};
																		}
																		px.put('/nodes/'+results[0].node+'/lxc/'+vmid+'/config', opts, function(err, ip6set) {
																			if(err) {
																				logger.info(err);
																				socket.emit('LXCAssignIPv6Res', response.status);
																			}else{
																				response.status = 'ok';
																				socket.emit('LXCAssignIPv6Res', response.status);
																			}
																		});
																	}
																});
															});
														});
													}
												});
											}else{
												logger.info('Invalid IPv6 mode');
												socket.emit('LXCAssignIPv6Res', response.status);
											}
										}
									});
								}else{
									logger.info('No IPv6 pool found.');
									socket.emit('LXCAssignIPv6Res', response.status);
								}
							}
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('LXCAssignIPv6Res', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('LXCAssignIPv6Res', response.status);
		}
	},
	editfw: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data && "action" in data && "type" in data && "enable" in data && "iface" in data && "proto" in data && "source" in data && "sport" in data && "dest" in data && "dport" in data && "comment" in data) {
			connection.query('SELECT * FROM vncp_lxc_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('LXCFirewallEditRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
							px.get('/pools/'+results[0].pool_id, {}, function(err, pooldata) {
								var vmid = pooldata['members'][0]['vmid'];
								var fwopts = {
									action: data.action,
									type: data.type,
									enable: data.enable,
									iface: data.iface,
									proto: data.proto,
									source: data.source,
									sport: data.sport,
									dest: data.dest,
									dport: data.dport,
									comment: data.comment
								};
								px.put('/nodes/'+results[0].node+'/lxc/'+vmid+'/firewall/rules/'+data.pos, fwopts, function(err, set) {
									if(err) {
										logger.info(err);
										socket.emit('LXCFirewallEditRes', response.status);
									}else{
										response.status = 'ok';
										socket.emit('LXCFirewallEditRes', response.status);
									}
								});
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('LXCFirewallEditRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('LXCFirewallEditRes', response.status);
		}
	},
	addnatport: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data) {
			connection.query('SELECT * FROM vncp_lxc_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('LXCAddNATPortRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							connection.query('SELECT * FROM vncp_nat WHERE node = ?', [results[0].node], function(err, natnode) {
								connection.query('SELECT * FROM vncp_tuntap WHERE node = ?', [results[0].node], function(err, rcreds) {
									if(err) {
										logger.info(err);
										socket.emit('LXCAddNATPortRes', response.status);
									}else{
										if(rcreds.length > 0) {
											var opts = {
												user: 'root',
												host: node[0].hostname,
												password: mc.decrypt(rcreds[0].password),
												port: rcreds[0].port
											};
											connection.query('SELECT * FROM vncp_natforwarding WHERE node = ?', [results[0].node], function(err, forwarddata) {
												if(err) {
													logger.info(err);
													socket.emit('LXCAddNATPortRes', response.status);
												}else{
													var usedports = [];
													for(var i = 0; i < forwarddata.length; i++) {
														var tempports = forwarddata[i].ports;
														usedports.push(parseInt(tempports.split(":")[1]));
													}
													var publicport = null;
													if(usedports.length > 0) {
														publicport = randomInt(10000, 65535);
														while(usedports.includes(publicport)) {
															publicport = randomInt(10000, 65535);
														}
													}else{
														publicport = randomInt(10000, 65535);
													}
													connection.query('SELECT * FROM vncp_natforwarding WHERE hb_account_id = ?', [data.aid], function(err, curports) {
														if(err) {
															logger.info(err);
															socket.emit('LXCAddNATPortRes', response.status);
														}else{
															var portcount = curports[0].ports.split(";").length - 1;
															var availports = parseInt(curports[0].avail_ports);
															if(portcount >= availports) {
																logger.info('Could not add NAT port, only '+availports+' allowed.');
																socket.emit('LXCAddNATPortRes', response.status);
															}else{
																exec('iptables -t nat -A PREROUTING -p tcp -d '+natnode[0].publicip+' --dport '+publicport+' -i vmbr0 -j DNAT --to-destination '+results[0].ip+':'+data.natport+' && iptables-save > /root/proxcp-iptables.rules', opts, function(err, stdout, stderr) {
																	var currentports = curports[0].ports + (portcount+1).toString() + ":"+publicport+":"+data.natport+":"+data.natdesc.substring(0, 20)+";";
																	connection.query('UPDATE vncp_natforwarding SET ports = ? WHERE hb_account_id = ?', [currentports, data.aid], function(err, update) {
																		if(err) {
																			logger.info(err);
																			socket.emit('LXCAddNATPortRes', response.status);
																		}else{
																			response.status = 'ok';
																			socket.emit('LXCAddNATPortRes', response.status);
																		}
																	});
																});
															}
														}
													});
												}
											});
										}else{
											logger.info('No TUN/TAP credentials exist for this node.');
											socket.emit('LXCAddNATPortRes', response.status);
										}
									}
								});
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('LXCAddNATPortRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('LXCAddNATPortRes', response.status);
		}
	},
	delnatport: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data) {
			connection.query('SELECT * FROM vncp_lxc_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('LXCDelNATPortRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							connection.query('SELECT * FROM vncp_nat WHERE node = ?', [results[0].node], function(err, natnode) {
								connection.query('SELECT * FROM vncp_tuntap WHERE node = ?', [results[0].node], function(err, rcreds) {
									if(err) {
										logger.info(err);
										socket.emit('LXCDelNATPortRes', response.status);
									}else{
										if(rcreds.length > 0) {
											var opts = {
												user: 'root',
												host: node[0].hostname,
												password: mc.decrypt(rcreds[0].password),
												port: rcreds[0].port
											};
											connection.query('SELECT * FROM vncp_natforwarding WHERE hb_account_id = ?', [data.aid], function(err, curports) {
												if(err) {
													logger.info(err);
													socket.emit('LXCDelNATPortRes', response.status);
												}else{
													var allportdata = curports[0].ports.split(";");
													var portdata = allportdata[data.id - 1].split(":");
													allportdata.splice(data.id - 1, 1);
													for(var i = 0; i < allportdata.length - 1; i++) {
														var t = allportdata[i].split(":");
														allportdata[i] = (i+1).toString() + ":" + t[1] + ":" + t[2] + ":" + t[3];
													}
													allportdata = allportdata.join(';');
													exec('iptables -t nat -D PREROUTING -p tcp -d '+natnode[0].publicip+' --dport '+portdata[1]+' -i vmbr0 -j DNAT --to-destination '+results[0].ip+':'+portdata[2]+' && iptables-save > /root/proxcp-iptables.rules', opts, function(err, stdout, stderr) {
														connection.query('UPDATE vncp_natforwarding SET ports = ? WHERE hb_account_id = ?', [allportdata, data.aid], function(err, update) {
															if(err) {
																logger.info(err);
																socket.emit('LXCDelNATPortRes', response.status);
															}else{
																response.status = 'ok';
																socket.emit('LXCDelNATPortRes', response.status);
															}
														});
													});
												}
											});
										}else{
											logger.info('No TUN/TAP credentials exist for this node.');
											socket.emit('LXCDelNATPortRes', response.status);
										}
									}
								});
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('LXCDelNATPortRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('LXCDelNATPortRes', response.status);
		}
	},
	addnatdomain: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data) {
			connection.query('SELECT * FROM vncp_lxc_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('LXCAddNATDomainRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							connection.query('SELECT * FROM vncp_nat WHERE node = ?', [results[0].node], function(err, natnode) {
								connection.query('SELECT * FROM vncp_tuntap WHERE node = ?', [results[0].node], function(err, rcreds) {
									if(err) {
										logger.info(err);
										socket.emit('LXCAddNATDomainRes', response.status);
									}else{
										if(rcreds.length > 0) {
											var opts = {
												user: 'root',
												host: node[0].hostname,
												password: mc.decrypt(rcreds[0].password),
												port: rcreds[0].port
											};
											connection.query('SELECT * FROM vncp_natforwarding', [], function(err, forwarddata) {
												if(err) {
													logger.info(err);
													socket.emit('LXCAddNATDomainRes', response.status);
												}else{
													if(valDomain(data.natdomain) == false) {
														var useddomains = [];
														for(var i = 0; i < forwarddata.length; i++) {
															var tempdomains = forwarddata[i].domains;
															var tdspl = tempdomains.split(";");
															for(var k = 0; k < tdspl.length - 1; k++) {
																useddomains.push(tdspl[k]);
															}
														}
														var uniquedomain = true;
														for(var i = 0; i < useddomains.length; i++) {
															if(useddomains[i] == data.natdomain) {
																uniquedomain = false;
																break;
															}
														}
														var fs = require('fs');
														var darray = fs.readFileSync('./nat/deny-domains.txt').toString().split("\n");
														for(var i = 0; i < darray.length - 1; i++) {
															var li = new String(darray[i]).valueOf().trim();
															var ri = new String(data.natdomain).valueOf().trim();
															if((li == ri) || ri.includes(li)) {
																uniquedomain = false;
																break;
															}
														}
														if(uniquedomain == false) {
															logger.info('NAT domain is not globally unique or chosen domain is denied (nat/deny-domains.txt).');
															socket.emit('LXCAddNATDomainRes', response.status);
														}else{
															connection.query('SELECT * FROM vncp_natforwarding WHERE hb_account_id = ?', [data.aid], function(err, curdomains) {
																if(err) {
																	logger.info(err);
																	socket.emit('LXCAddNATDomainRes', response.status);
																}else{
																	var domaincount = curdomains[0].domains.split(";").length - 1;
																	var availdomains = parseInt(curdomains[0].avail_domains);
																	if(domaincount >= availdomains) {
																		logger.info('Could not add NAT domain, only '+availdomains+' allowed.');
																		socket.emit('LXCAddNATDomainRes', response.status);
																	}else{
																		var natcert = data.natsslcert;
																		var natkey = data.natsslkey;
																		if(natcert == undefined || natcert == null) {
																			natcert = '';
																		}
																		if(natkey == undefined || natkey == null) {
																			natkey = '';
																		}
																		var confstr = '';
																		var natssl = false;
																		var natsslhash1 = randPW(6);
																		var natsslhash2 = randPW(6);
																		if((natcert.length > 50 && natcert.substr(0, 27) == "-----BEGIN CERTIFICATE-----") && (natkey.length > 50 && (natkey.substr(0, 27) == "-----BEGIN PRIVATE KEY-----" || natkey.substr(0, 31) == "-----BEGIN RSA PRIVATE KEY-----"))) {
																			natssl = true;
																			confstr = `
server {
  listen 80;
  server_name ${data.natdomain} *.${data.natdomain};

  location / {
    proxy_pass              http://${results[0].ip};
    proxy_set_header        Host $host;
    proxy_set_header        X-Real-IP $remote_addr;
    proxy_set_header        X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header        X-Forwarded-Proto http;
    proxy_connect_timeout   150;
    proxy_send_timeout      100;
    proxy_read_timeout      100;
    proxy_buffers           4 32k;
    client_max_body_size    8m;
    client_body_buffer_size 128k;
  }
}
server {
	listen 443 ssl;
	server_name ${data.natdomain} *.${data.natdomain};
	ssl on;
	ssl_certificate /etc/nginx/proxcp-nat-ssl/cert-${data.aid}-${data.natdomain}-${natsslhash1}.pem;
	ssl_certificate_key /etc/nginx/proxcp-nat-ssl/key-${data.aid}-${data.natdomain}-${natsslhash2}.pem;

	location / {
		proxy_pass              https://${results[0].ip};
    proxy_set_header        Host $host;
    proxy_set_header        X-Real-IP $remote_addr;
    proxy_set_header        X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header        X-Forwarded-Proto https;
    proxy_connect_timeout   150;
    proxy_send_timeout      100;
    proxy_read_timeout      100;
    proxy_buffers           4 32k;
    client_max_body_size    8m;
    client_body_buffer_size 128k;
	}
}
	`;
																		}else{
																			confstr = `
server {
  listen 80;
  server_name ${data.natdomain} *.${data.natdomain};

  location / {
    proxy_pass              http://${results[0].ip};
    proxy_set_header        Host $host;
    proxy_set_header        X-Real-IP $remote_addr;
    proxy_set_header        X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header        X-Forwarded-Proto http;
    proxy_connect_timeout   150;
    proxy_send_timeout      100;
    proxy_read_timeout      100;
    proxy_buffers           4 32k;
    client_max_body_size    8m;
    client_body_buffer_size 128k;
  }
}
	`;
																		}
																		exec("printf -- '"+confstr+"' > /etc/nginx/conf.d/"+data.aid+"-"+data.natdomain+"-"+randPW(6)+".conf", opts, function(err, stdout, stderr) {
																			if(natssl == true) {
																				exec("mkdir -p /etc/nginx/proxcp-nat-ssl && printf -- '"+natcert+"' > /etc/nginx/proxcp-nat-ssl/cert-"+data.aid+"-"+data.natdomain+"-"+natsslhash1+".pem", opts, function(err, stdout, stderr) {
																					exec("printf -- '"+natkey+"' > /etc/nginx/proxcp-nat-ssl/key-"+data.aid+"-"+data.natdomain+"-"+natsslhash2+".pem && service nginx restart", opts, function(err, stdout, stderr) {
																						var currentdomains = curdomains[0].domains + data.natdomain + ";";
																						connection.query('UPDATE vncp_natforwarding SET domains = ? WHERE hb_account_id = ?', [currentdomains, data.aid], function(err, update) {
																							if(err) {
																								logger.info(err);
																								socket.emit('LXCAddNATDomainRes', response.status);
																							}else{
																								response.status = 'ok';
																								socket.emit('LXCAddNATDomainRes', response.status);
																							}
																						});
																					});
																				});
																			}else{
																				exec("service nginx restart", opts, function(err, stdout, stderr) {
																					var currentdomains = curdomains[0].domains + data.natdomain + ";";
																					connection.query('UPDATE vncp_natforwarding SET domains = ? WHERE hb_account_id = ?', [currentdomains, data.aid], function(err, update) {
																						if(err) {
																							logger.info(err);
																							socket.emit('LXCAddNATDomainRes', response.status);
																						}else{
																							response.status = 'ok';
																							socket.emit('LXCAddNATDomainRes', response.status);
																						}
																					});
																				});
																			}
																		});
																	}
																}
															});
														}
													}else{
														logger.info('Invalid NAT domain.');
														socket.emit('LXCAddNATDomainRes', response.status);
													}
												}
											});
										}else{
											logger.info('No TUN/TAP credentials exist for this node.');
											socket.emit('LXCAddNATDomainRes', response.status);
										}
									}
								});
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('LXCAddNATDomainRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('LXCAddNATDomainRes', response.status);
		}
	},
	delnatdomain: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data) {
			connection.query('SELECT * FROM vncp_lxc_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('LXCDelNATDomainRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							connection.query('SELECT * FROM vncp_nat WHERE node = ?', [results[0].node], function(err, natnode) {
								connection.query('SELECT * FROM vncp_tuntap WHERE node = ?', [results[0].node], function(err, rcreds) {
									if(err) {
										logger.info(err);
										socket.emit('LXCDelNATDomainRes', response.status);
									}else{
										if(rcreds.length > 0) {
											var opts = {
												user: 'root',
												host: node[0].hostname,
												password: mc.decrypt(rcreds[0].password),
												port: rcreds[0].port
											};
											if(valDomain(data.natdomain) == false) {
												connection.query('SELECT * FROM vncp_natforwarding WHERE hb_account_id = ?', [data.aid], function(err, curdomains) {
													if(err) {
														logger.info(err);
														socket.emit('LXCDelNATDomainRes', response.status);
													}else{
														if(curdomains[0].domains.includes(data.id)) {
															var alldomaindata = curdomains[0].domains.split(";");
															alldomaindata.splice(alldomaindata.indexOf(data.id), 1);
															alldomaindata = alldomaindata.join(';');
															exec('rm /etc/nginx/conf.d/'+data.aid+'-'+data.id+'-*.conf && rm /etc/nginx/proxcp-nat-ssl/cert-'+data.aid+'-'+data.id+'-*.pem && rm /etc/nginx/proxcp-nat-ssl/key-'+data.aid+'-'+data.id+'-*.pem && service nginx restart', opts, function(err, stdout, stderr) {
																connection.query('UPDATE vncp_natforwarding SET domains = ? WHERE hb_account_id = ?', [alldomaindata, data.aid], function(err, update) {
																	if(err) {
																		logger.info(err);
																		socket.emit('LXCDelNATDomainRes', response.status);
																	}else{
																		response.status = 'ok';
																		socket.emit('LXCDelNATDomainRes', response.status);
																	}
																});
															});
														}else{
															logger.info('Invalid NAT domain permissions.');
															socket.emit('LXCDelNATDomainRes', response.status);
														}
													}
												});
											}else{
												logger.info('Invalid NAT domain.');
												socket.emit('LXCDelNATDomainRes', response.status);
											}
										}else{
											logger.info('No TUN/TAP credentials exist for this node.');
											socket.emit('LXCDelNATDomainRes', response.status);
										}
									}
								});
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('LXCDelNATDomainRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('LXCDelNATDomainRes', response.status);
		}
	}
};
