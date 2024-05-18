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
			console.log('[PROXCP:KVM] Attempting to reconnect...');
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

function isUrlValid(url) {
    return /^(https?|s?):\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i.test(url);
}

function randomString(length, chars) {
    var result = '';
    for (var i = length; i > 0; --i) result += chars[Math.floor(Math.random() * chars.length)];
    return result;
}

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
				connection.query('SELECT * FROM vncp_kvm_ct WHERE hb_account_id = ?', [data.hb_account_id], function(err, results) {
					if(err) {
						logger.info(err);
						socket.emit('KVMStatusCheckRes', response.status);
					}else{
						if(results.length == 1 && results[0].user_id == connections[socket.id]) {
							connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
								var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
								px.get('/pools/'+results[0].pool_id, {}, function(err, data) {
									if(err) {
										logger.info(err);
										socket.emit('KVMStatusCheckRes', response.status);
									}else{
										if(data['members'].length == 1) {
											var vmid = data['members'][0]['vmid'];
										}else{
											for(var j = 0; j < data['members'].length; j++) {
												if(data['members'][j]['name'] == results[0].cloud_hostname) {
													var vmid = data['members'][j]['vmid'];
												}
											}
										}
										px.get('/nodes/'+results[0].node+'/qemu/'+vmid+'/status/current', {}, function(err, vm) {
											if(err) {
												logger.info(err);
												socket.emit('KVMStatusCheckRes', response.status);
											}else if(vm){
												response.status = vm.status;
												response.cpu = vm.cpu;
												response.cpus = vm.cpus;
												response.mem = vm.mem;
												response.maxmem = vm.maxmem;
												response.uptime = vm.uptime;
												socket.emit('KVMStatusCheckRes', response);
											}else{
												logger.info('Proxmox returned empty response');
												socket.emit('KVMStatusCheckRes', response.status);
											}
										});
									}
								});
							});
						}else{
							logger.info('Invalid user ID');
							socket.emit('KVMStatusCheckRes', response.status);
						}
					}
				});
			}
		}else{
			logger.info("Data structure missing input.");
			socket.emit('KVMStatusCheckRes', response.status);
		}
	},
	start: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("hb_account_id" in data) {
			connection.query('SELECT * FROM vncp_kvm_ct WHERE hb_account_id = ?', [data.hb_account_id], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('KVMStartRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
							px.get('/pools/'+results[0].pool_id, {}, function(err, data) {
								if(data['members'].length == 1) {
									var vmid = data['members'][0]['vmid'];
								}else{
									for(var j = 0; j < data['members'].length; j++) {
										if(data['members'][j]['name'] == results[0].cloud_hostname) {
											var vmid = data['members'][j]['vmid'];
										}
									}
								}
								if(results[0].suspended == 0) {
									px.post('/nodes/'+results[0].node+'/qemu/'+vmid+'/status/start', {}, function(err, proc) {
										response.status = 'ok';
										socket.emit('KVMStartRes', response.status);
									});
								}else{
									logger.info('VM is suspended, not starting');
									socket.emit('KVMStartRes', response.status);
								}
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('KVMStartRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('KVMStartRes', response.status);
		}
	},
	shutdown: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("hb_account_id" in data) {
			connection.query('SELECT * FROM vncp_kvm_ct WHERE hb_account_id = ?', [data.hb_account_id], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('KVMShutdownRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
							px.get('/pools/'+results[0].pool_id, {}, function(err, data) {
								if(data['members'].length == 1) {
									var vmid = data['members'][0]['vmid'];
								}else{
									for(var j = 0; j < data['members'].length; j++) {
										if(data['members'][j]['name'] == results[0].cloud_hostname) {
											var vmid = data['members'][j]['vmid'];
										}
									}
								}
								px.post('/nodes/'+results[0].node+'/qemu/'+vmid+'/status/stop', {}, function(err, proc) {
									response.status = 'ok';
									socket.emit('KVMShutdownRes', response.status);
								});
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('KVMShutdownRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('KVMShutdownRes', response.status);
		}
	},
	restart: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("hb_account_id" in data) {
			connection.query('SELECT * FROM vncp_kvm_ct WHERE hb_account_id = ?', [data.hb_account_id], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('KVMRestartRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
							px.get('/pools/'+results[0].pool_id, {}, function(err, data) {
								if(data['members'].length == 1) {
									var vmid = data['members'][0]['vmid'];
								}else{
									for(var j = 0; j < data['members'].length; j++) {
										if(data['members'][j]['name'] == results[0].cloud_hostname) {
											var vmid = data['members'][j]['vmid'];
										}
									}
								}
								if(results[0].suspended == 0) {
									px.post('/nodes/'+results[0].node+'/qemu/'+vmid+'/status/stop', {}, function(err, proc) {
										var delayed = require('delayed');
										delayed.delay(function() {
											px.post('/nodes/'+results[0].node+'/qemu/'+vmid+'/status/start', {}, function(err, proc) {
												response.status = 'ok';
												socket.emit('KVMStartRes', response.status);
											});
										}, 10000);
									});
								}else{
									logger.info('VM is suspended, not restarting');
									socket.emit('KVMRestartRes', response.status);
								}
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('KVMRestartRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('KVMRestartRes', response.status);
		}
	},
	kill: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("hb_account_id" in data) {
			connection.query('SELECT * FROM vncp_kvm_ct WHERE hb_account_id = ?', [data.hb_account_id], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('KVMKillRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
							px.get('/pools/'+results[0].pool_id, {}, function(err, data) {
								if(data['members'].length == 1) {
									var vmid = data['members'][0]['vmid'];
								}else{
									for(var j = 0; j < data['members'].length; j++) {
										if(data['members'][j]['name'] == results[0].cloud_hostname) {
											var vmid = data['members'][j]['vmid'];
										}
									}
								}
								px.post('/nodes/'+results[0].node+'/qemu/'+vmid+'/status/stop', {}, function(err, proc) {
									response.status = 'ok';
									socket.emit('KVMKillRes', response.status);
								});
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('KVMKillRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('KVMKillRes', response.status);
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
			socket.emit('KVMBackupStatusRes', response);
		}, 1000);
	},
	createbackup: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		response.upid = 'none';
		if("aid" in data && "notify" in data) {
			connection.query('SELECT * FROM vncp_kvm_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('KVMCreateBackupRes', response);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						if(results[0].allow_backups == 1) {
							connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
								connection.query('SELECT email FROM vncp_users WHERE id = ?', [results[0].user_id], function(err, userobj) {
									var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
									px.get('/pools/'+results[0].pool_id, {}, function(err, pooldata) {
										if(pooldata['members'].length == 1) {
											var vmid = pooldata['members'][0]['vmid'];
										}else{
											for(var j = 0; j < pooldata['members'].length; j++) {
												if(pooldata['members'][j]['name'] == results[0].cloud_hostname) {
													var vmid = pooldata['members'][j]['vmid'];
												}
											}
										}
										var opts = {
											all: 0,
											compress: 'lzo',
											mode: 'snapshot',
											remove: 1,
											storage: node[0].backup_store,
											vmid: vmid
										};
										if(data.notify == 'yes') {
											opts.mailnotification = 'always';
											opts.mailto = userobj[0].email;
										}
										px.post('/nodes/'+results[0].node+'/vzdump', opts, function(err, out) {
											response.upid = out;
											response.status = 'ok';
											socket.emit('KVMCreateBackupRes', response);
										});
									});
								});
							});
						}else{
							logger.info('Backups are disabled for this VM');
							socket.emit('KVMCreateBackupRes', response);
						}
					}else{
						logger.info('Invalid user ID');
						socket.emit('KVMCreateBackupRes', response);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('KVMCreateBackupRes', response);
		}
	},
	removebackup: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data && "snapname" in data) {
			connection.query('SELECT * FROM vncp_kvm_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('KVMRemoveBackupRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
							px.get('/pools/'+results[0].pool_id, {}, function(err, pooldata) {
								if(pooldata['members'].length == 1) {
									var vmid = pooldata['members'][0]['vmid'];
								}else{
									for(var j = 0; j < pooldata['members'].length; j++) {
										if(pooldata['members'][j]['name'] == results[0].cloud_hostname) {
											var vmid = pooldata['members'][j]['vmid'];
										}
									}
								}
								if(data.snapname.indexOf(vmid) > -1 && data.snapname.indexOf('qemu') > -1) {
									var storage = data.snapname.split(":");
									storage = storage[0];
									px.delete('/nodes/'+results[0].node+'/storage/'+storage+'/content/'+data.snapname, {}, function(err, rmd) {
										response.status = 'ok';
										socket.emit('KVMRemoveBackupRes', response.status);
									});
								}
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('KVMRemoveBackupRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('KVMRemoveBackupRes', response.status);
		}
	},
	getbackupconf: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		response.conf = 'none';
		if("aid" in data && "volid" in data) {
			connection.query('SELECT * FROM vncp_kvm_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('KVMGetBackupConfRes', response);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
							px.get('/pools/'+results[0].pool_id, {}, function(err, pooldata) {
								var vmid = pooldata['members'][0]['vmid'];
								if(data.volid.indexOf(vmid) > -1 && data.volid.indexOf('qemu') > -1) {
									var opts = {
										volume: data.volid
									};
									px.get('/nodes/'+results[0].node+'/vzdump/extractconfig', opts, function(err, confdata) {
										var td = confdata.split("\n");
										response.conf = "IP: "+td[0].substring(1)+"<br />CPU Type: "+td[7].substring(5)+"<br />CPU Cores: "+td[6].substring(7)+"<br />Hostname: "+td[15].substring(6)+"<br />RAM (MB): "+td[14].substring(8)+"M<br />OS Type: "+td[19].substring(8)+"<br />Storage (GB): "+td[27].split("=")[2];
										response.status = 'ok';
										socket.emit('KVMGetBackupConfRes', response);
									});
								}
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('KVMGetBackupConfRes', response);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('KVMGetBackupConfRes', response);
		}
	},
	restorebackup: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data && "snapname" in data) {
			connection.query('SELECT * FROM vncp_kvm_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('KVMRestoreBackupRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
							px.get('/pools/'+results[0].pool_id, {}, function(err, res) {
								if(res['members'].length == 1) {
									var vmid = res['members'][0]['vmid'];
								}else{
									for(var j = 0; j < res['members'].length; j++) {
										if(res['members'][j]['name'] == results[0].cloud_hostname) {
											var vmid = res['members'][j]['vmid'];
										}
									}
								}
								px.post('/nodes/'+results[0].node+'/qemu/'+vmid+'/status/stop', {}, function(err, proc) {
									px.get('/nodes/'+results[0].node+'/qemu/'+vmid+'/config', {}, function(err, vmconfig) {
										if(data.snapname.indexOf(vmid) > -1 && data.snapname.indexOf('qemu') > -1) {
											var storage = '';
											if("virtio0" in vmconfig) {
												storage = vmconfig.virtio0.split(":");
											}else if("scsi0" in vmconfig) {
												storage = vmconfig.scsi0.split(":");
											}else{
												storage = vmconfig.ide0.split(":");
											}
											var newvm = {
												vmid: vmid,
												force: 1,
												archive: data.snapname,
												storage: storage[0],
												pool: results[0].pool_id
											};
											var delayed = require('delayed');
											delayed.delay(function() {
												px.delete('/nodes/'+results[0].node+'/qemu/'+vmid, {}, function(err, proc) {
													delayed.delay(function() {
														px.post('/nodes/'+results[0].node+'/qemu', newvm, function(err, createvm) {
															delayed.delay(function() {
																px.get('/nodes/'+results[0].node+'/qemu/'+vmid+'/config', {}, function(err, vmconfig) {
																	var new_os = '';
																	if(vmconfig.ostype[0] == 'l') {
																		new_os = 'Linux';
																	}else if(vmconfig.ostype[0] == 'w') {
																		new_os = 'Windows';
																	}else{
																		new_os = 'Other';
																	}
																	connection.query('UPDATE vncp_kvm_ct SET os = ?, from_template = 0 WHERE hb_account_id = ?', [new_os, data.aid], function(err, update) {
																		if(err) {
																			logger.info(err);
																			socket.emit('KVMRestoreBackupRes', response.status);
																		}else{
																			var date = new Date().toMysqlFormat();
																			connection.query('INSERT INTO vncp_users_rebuild_log (client_id, date, vmid, hostname) VALUES ('+results[0].user_id+', "'+date+'", '+vmid+', "restored_backup '+data.snapname+'")', function(err, insert) {
																				if(err) {
																					logger.info(err);
																					socket.emit('KVMRestoreBackupRes', response.status);
																				}else{
																					response.status = 'ok';
																					socket.emit('KVMRestoreBackupRes', response.status);
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
						socket.emit('KVMRestoreBackupRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('KVMRestoreBackupRes', response.status);
		}
	},
	rebuild: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data && "os" in data && "hostname" in data) {
			connection.query('SELECT * FROM vncp_kvm_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('KVMRebuildRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
							px.get('/pools/'+results[0].pool_id, {}, function(err, res) {
								if(res['members'].length == 1) {
									var vmid = res['members'][0]['vmid'];
								}else{
									for(var j = 0; j < res['members'].length; j++) {
										if(res['members'][j]['name'] == results[0].cloud_hostname) {
											var vmid = res['members'][j]['vmid'];
										}
									}
								}
								px.post('/nodes/'+results[0].node+'/qemu/'+vmid+'/status/stop', {}, function(err, proc) {
									px.get('/nodes/'+results[0].node+'/qemu/'+vmid+'/config', {}, function(err, vmconfig) {
										if(("ide1" in vmconfig && (vmconfig.ide1.indexOf('2008') > -1 || vmconfig.ide1.indexOf('2012') > -1 || vmconfig.ide1.indexOf('2016') > -1 || vmconfig.ide1.indexOf('2019') > -1))
										|| ("ide2" in vmconfig && (vmconfig.ide2.indexOf('2008') > -1 || vmconfig.ide2.indexOf('2012') > -1 || vmconfig.ide2.indexOf('2016') > -1 || vmconfig.ide2.indexOf('2019') > -1) && !(vmconfig.ide2.indexOf('cloudinit') > -1))) {
											var storage = vmconfig.ide0.split(":");
											storage = storage[0];
											var space = vmconfig.ide0.split(",");
											space = space[space.length - 1];
											space = space.split("=");
											space = space[1].slice(0, -1);
										}else if("scsi0" in vmconfig && vmconfig.ide2.indexOf('cloudinit') > -1) {
											var storage = vmconfig.scsi0.split(":");
											storage = storage[0];
											var space = vmconfig.scsi0.split(",");
											space = space[space.length - 1];
											space = space.split("=");
											space = space[1].slice(0, -1);
										}else if("ide0" in vmconfig && vmconfig.ide2.indexOf('cloudinit') > -1) {
											var storage = vmconfig.ide0.split(":");
											storage = storage[0];
											var space = vmconfig.ide0.split(",");
											space = space[space.length - 1];
											space = space.split("=");
											space = space[1].slice(0, -1);
										}else if("ide0" in vmconfig && !(vmconfig.ide2.indexOf('cloudinit') > -1)) {
											var storage = vmconfig.ide0.split(":");
											storage = storage[0];
											var space = vmconfig.ide0.split(",");
											space = space[space.length - 1];
											space = space.split("=");
											space = space[1].slice(0, -1);
										}else{
											var storage = vmconfig.virtio0.split(":");
											storage = storage[0];
											var space = vmconfig.virtio0.split(",");
											space = space[space.length - 1];
											space = space.split("=");
											space = space[1].slice(0, -1);
										}
										if(data.os.indexOf('2008') > -1 || data.os.indexOf('2012') > -1 || data.os.indexOf('2016') > -1 || data.os.indexOf('2019') > -1) {
											var bootdisk = 'ide0';
											var netdev = 'e1000';
											var ostype = 'win8';
											var vga = 'std';
										}else{
											var bootdisk = 'virtio0';
											var netdev = 'virtio';
											var ostype = 'l26';
											var vga = 'cirrus';
										}
										if(data.os.indexOf('2016') > -1 || data.os.indexOf('2019') > -1) {
											ostype = 'win10';
										}
										var cputype = vmconfig.cpu;
										var macaddr = vmconfig.net0.match(/([0-9A-F]{2}:?){6}/g);
										var ratelimit = vmconfig.net0.match(/rate=[0-9]+/g);
										var vlantag = vmconfig.net0.match(/tag=[0-9]+/g);
										var newvm = {
											vmid: vmid,
											acpi: 1,
											agent: 0,
											balloon: vmconfig.memory,
											boot: 'cdn',
											bootdisk: bootdisk,
											cores: vmconfig.cores,
											cpu: cputype,
											cpulimit: '0',
											cpuunits: 1024,
											description: vmconfig.description,
											hotplug: '1',
											ide2: data.os + ',media=cdrom',
											kvm: 1,
											localtime: 1,
											memory: vmconfig.memory,
											name: data.hostname.trim(),
											net0: 'bridge=vmbr0,' + netdev + '=' + macaddr[0],
											numa: 0,
											onboot: 0,
											ostype: ostype,
											pool: results[0].pool_id,
											protection: 0,
											reboot: 1,
											sockets: 1,
											storage: storage,
											tablet: 1,
											template: 0,
											vga: vga
										};
										if(ratelimit != null) {
											newvm.net0 = newvm.net0 + ',' + ratelimit[0];
										}
										if(vlantag != null) {
											newvm.net0 = newvm.net0 + ',' + vlantag[0];
										}
										if(vmconfig.net1 != null) {
											var macaddr2 = vmconfig.net1.match(/([0-9A-F]{2}:?){6}/g);
											newvm.net1 = 'bridge=vmbr1,' + 'e1000=' + macaddr2[0];
										}
										if(vmconfig.net10 != null) {
											var macaddr3 = vmconfig.net10.match(/([0-9A-F]{2}:?){6}/g);
											newvm.net10 = 'bridge=vmbr0,' + 'e1000=' + macaddr3[0];
										}
										if(data.os.indexOf('2008') > -1 || data.os.indexOf('2012') > -1 || data.os.indexOf('2016') > -1 || data.os.indexOf('2019') > -1) {
											if((storage.indexOf('lvm') > -1) || (storage.indexOf('zfs') > -1) || (storage.indexOf('thin') > -1))
												newvm.ide0 = storage + ':' + space + ',format=raw,cache=writeback,discard=on';
											else
												newvm.ide0 = storage + ':' + space + ',format=qcow2,cache=writeback,discard=on';
										}else{
											if((storage.indexOf('lvm') > -1) || (storage.indexOf('zfs') > -1) || (storage.indexOf('thin') > -1))
												newvm.virtio0 = storage + ':' + space + ',format=raw,cache=writeback,discard=on';
											else
												newvm.virtio0 = storage + ':' + space + ',format=qcow2,cache=writeback,discard=on';
										}
										var iso = data.os.split("/");
										iso = iso[1];
										var delayed = require('delayed');
										delayed.delay(function() {
											px.delete('/nodes/'+results[0].node+'/qemu/'+vmid, {}, function(err, proc) {
												delayed.delay(function() {
													px.post('/nodes/'+results[0].node+'/qemu', newvm, function(err, createvm) {
														delayed.delay(function() {
															if(results[0].cloud_account_id == 0) {
																connection.query('UPDATE vncp_kvm_ct SET os = ?, fw_enabled_net0 = 0, fw_enabled_net1 = 0, onboot = 0, from_template = 0 WHERE hb_account_id = ?', [iso, data.aid], function(err, update) {
																	if(err) {
																		logger.info(err);
																		socket.emit('KVMRebuildRes', response.status);
																	}else{
																		var date = new Date().toMysqlFormat();
																		connection.query('INSERT INTO vncp_users_rebuild_log (client_id, date, vmid, hostname) VALUES ('+results[0].user_id+', "'+date+'", '+vmid+', "'+data.hostname.trim()+'")', function(err, insert) {
																			if(err) {
																				logger.info(err);
																				socket.emit('KVMRebuildRes', response.status);
																			}else{
																				response.status = 'ok';
																				socket.emit('KVMRebuildRes', response.status);
																			}
																		});
																	}
																});
															}else{
																connection.query('UPDATE vncp_kvm_ct SET os = ?, fw_enabled_net0 = 0, fw_enabled_net1 = 0, onboot = 0, cloud_hostname = ?, from_template = 0 WHERE hb_account_id = ?', [iso, data.hostname.trim(), data.aid], function(err, update) {
																	if(err) {
																		logger.info(err);
																		socket.emit('KVMRebuildRes', response.status);
																	}else{
																		var date = new Date().toMysqlFormat();
																		connection.query('INSERT INTO vncp_users_rebuild_log (client_id, date, vmid, hostname) VALUES ('+results[0].user_id+', "'+date+'", '+vmid+', "'+data.hostname.trim()+'")', function(err, insert) {
																			if(err) {
																				logger.info(err);
																				socket.emit('KVMRebuildRes', response.status);
																			}else{
																				response.status = 'ok';
																				socket.emit('KVMRebuildRes', response.status);
																			}
																		});
																	}
																});
															}
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
						socket.emit('KVMRebuildRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('KVMRebuildRes', response.status);
		}
	},
	rebuildtemplate: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data && "hostname" in data && "os" in data) {
			connection.query('SELECT * FROM vncp_kvm_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('KVMRebuildTemplateRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
							px.get('/pools/'+results[0].pool_id, {}, function(err, res) {
								if(res['members'].length == 1) {
									var vmid = res['members'][0]['vmid'];
								}else{
									for(var j = 0; j < res['members'].length; j++) {
										if(res['members'][j]['name'] == results[0].cloud_hostname) {
											var vmid = res['members'][j]['vmid'];
										}
									}
								}
								px.post('/nodes/'+results[0].node+'/qemu/'+vmid+'/status/stop', {}, function(err, proc) {
									px.get('/nodes/'+results[0].node+'/qemu/'+vmid+'/config', {}, function(err, vmconfig) {
										if("scsi0" in vmconfig && vmconfig.ide2.indexOf('cloudinit') > -1) {
											var storage = vmconfig.scsi0.split(":");
											storage = storage[0];
											var space = vmconfig.scsi0.split(",");
											space = space[space.length - 1];
											space = space.split("=");
											space = space[1].slice(0, -1);
										}else if("ide0" in vmconfig && vmconfig.ide2.indexOf('cloudinit') > -1) {
											var storage = vmconfig.ide0.split(":");
											storage = storage[0];
											var space = vmconfig.ide0.split(",");
											space = space[space.length - 1];
											space = space.split("=");
											space = space[1].slice(0, -1);
										}else{
											var storage = vmconfig.virtio0.split(":");
											storage = storage[0];
											var space = vmconfig.virtio0.split(",");
											space = space[space.length - 1];
											space = space.split("=");
											space = space[1].slice(0, -1);
										}
										var macaddr = vmconfig.net0.match(/([0-9A-F]{2}:?){6}/g);
										var ratelimit = vmconfig.net0.match(/rate=[0-9]+/g);
										var vlantag = vmconfig.net0.match(/tag=[0-9]+/g);
										if(ratelimit != null) {
											ratelimit = ratelimit[0].split("=")[1];
										}else{
											ratelimit = "0";
										}
										if(vlantag != null) {
											vlantag = vlantag[0].split("=")[1];
										}else{
											vlantag = "0";
										}
										var newvm = {
											newid: vmid,
											description: vmconfig.description,
											format: 'raw',
											full: 1,
											name: data.hostname.trim(),
											pool: results[0].pool_id,
											storage: storage
										};
										if(vmconfig.net1 != null) {
											var macaddr2 = vmconfig.net1.match(/([0-9A-F]{2}:?){6}/g);
										}
										if(vmconfig.net10 != null) {
											var macaddr3 = vmconfig.net10.match(/([0-9A-F]{2}:?){6}/g);
										}
										connection.query('SELECT * FROM vncp_kvm_templates WHERE id = ?', [data.os], function(err, clonevm) {
											if(err) {
												logger.info(err);
												socket.emit('KVMRebuildTemplateRes', response.status);
											}else{
												var iso = clonevm[0].vmid;
												var delayed = require('delayed');
												delayed.delay(function() {
													px.delete('/nodes/'+results[0].node+'/qemu/'+vmid, {}, function(err, proc) {
														delayed.delay(function() {
															px.post('/nodes/'+results[0].node+'/qemu/'+iso+'/clone', newvm, function(err, createvm) {
																delayed.delay(function() {
																	if(results[0].cloud_account_id == 0) {
																		connection.query('UPDATE vncp_kvm_ct SET os = ?, fw_enabled_net0 = 0, fw_enabled_net1 = 0, onboot = 0, from_template = 1 WHERE hb_account_id = ?', [clonevm[0].friendly_name, data.aid], function(err, update) {
																			if(err) {
																				logger.info(err);
																				socket.emit('KVMRebuildTemplateRes', response.status);
																			}else{
																				var date = new Date().toMysqlFormat();
																				connection.query('INSERT INTO vncp_users_rebuild_log (client_id, date, vmid, hostname) VALUES ('+results[0].user_id+', "'+date+'", '+vmid+', "'+data.hostname.trim()+'")', function(err, insert) {
																					if(err) {
																						logger.info(err);
																						socket.emit('KVMRebuildTemplateRes', response.status);
																					}else{
																						var pending_clone = {
																							vmid: vmid,
																							cores: vmconfig.cores,
																							cpu: vmconfig.cpu,
																							memory: vmconfig.memory,
																							cipassword: mc.encrypt(randPW(16)),
																							storage_size: space,
																							cvmtype: clonevm[0].type,
																							gateway: macaddr,
																							ip: results[0].ip,
																							setmacaddress: macaddr,
																							portspeed: ratelimit,
																							vlantag: vlantag
																						};
																						if(macaddr2) {
																							pending_clone.netmask = macaddr2;
																						}
																						if(macaddr3) {
																							pending_clone.net10 = macaddr3;
																						}
																						connection.query('INSERT INTO vncp_pending_clone (node, upid, hb_account_id, data) VALUES (?, ?, ?, ?)', [results[0].node, createvm, data.aid, JSON.stringify(pending_clone)], function(err, ins_pending) {
																							if(err) {
																								logger.info(err);
																								socket.emit('KVMRebuildTemplateRes', response.status);
																							}else{
																								response.status = 'ok';
																								socket.emit('KVMRebuildTemplateRes', response.status);
																							}
																						});
																					}
																				});
																			}
																		});
																	}else{
																		connection.query('UPDATE vncp_kvm_ct SET os = ?, fw_enabled_net0 = 0, fw_enabled_net1 = 0, onboot = 0, cloud_hostname = ?, from_template = 1 WHERE hb_account_id = ?', [clonevm[0].friendly_name, data.hostname.trim(), data.aid], function(err, update) {
																			if(err) {
																				logger.info(err);
																				socket.emit('KVMRebuildTemplateRes', response.status);
																			}else{
																				var date = new Date().toMysqlFormat();
																				connection.query('INSERT INTO vncp_users_rebuild_log (client_id, date, vmid, hostname) VALUES ('+results[0].user_id+', "'+date+'", '+vmid+', "'+data.hostname.trim()+'")', function(err, insert) {
																					if(err) {
																						logger.info(err);
																						socket.emit('KVMRebuildTemplateRes', response.status);
																					}else{
																						var pending_clone = {
																							vmid: vmid,
																							cores: vmconfig.cores,
																							cpu: vmconfig.cpu,
																							memory: vmconfig.memory,
																							cipassword: mc.encrypt(randPW(16)),
																							storage_size: space,
																							cvmtype: clonevm[0].type,
																							gateway: macaddr,
																							ip: results[0].ip,
																							setmacaddress: macaddr,
																							portspeed: ratelimit,
																							vlantag: vlantag
																						};
																						if(macaddr2) {
																							pending_clone.netmask = macaddr2;
																						}
																						if(macaddr3) {
																							pending_clone.net10 = macaddr3;
																						}
																						connection.query('INSERT INTO vncp_pending_clone (node, upid, hb_account_id, data) VALUES (?, ?, ?, ?)', [results[0].node, createvm, data.aid, JSON.stringify(pending_clone)], function(err, ins_pending) {
																							if(err) {
																								logger.info(err);
																								socket.emit('KVMRebuildTemplateRes', response.status);
																							}else{
																								response.status = 'ok';
																								socket.emit('KVMRebuildTemplateRes', response.status);
																							}
																						});
																					}
																				});
																			}
																		});
																	}
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
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('KVMRebuildTemplateRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('KVMRebuildTemplateRes', response.status);
		}
	},
	setfwopts: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data && "enable" in data && "policy_in" in data && "policy_out" in data && "log_level_in" in data && "log_level_out" in data) {
			connection.query('SELECT * FROM vncp_kvm_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('KVMFirewallOptionsRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
							px.get('/pools/'+results[0].pool_id, {}, function(err, pooldata) {
								if(pooldata['members'].length == 1) {
									var vmid = pooldata['members'][0]['vmid'];
								}else{
									for(var j = 0; j < pooldata['members'].length; j++) {
										if(pooldata['members'][j]['name'] == results[0].cloud_hostname) {
											var vmid = pooldata['members'][j]['vmid'];
										}
									}
								}
								var fwopts = {
									enable: data.enable,
									policy_in: data.policy_in,
									policy_out: data.policy_out,
									log_level_in: data.log_level_in,
									log_level_out: data.log_level_out
								};
								px.put('/nodes/'+results[0].node+'/qemu/'+vmid+'/firewall/options', fwopts, function(err, set) {
									response.status = 'ok';
									socket.emit('KVMFirewallOptionsRes', response.status);
								});
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('KVMFirewallOptionsRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('KVMFirewallOptionsRes', response.status);
		}
	},
	addfwrule: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data && "action" in data && "type" in data && "enable" in data && "iface" in data && "proto" in data && "source" in data && "sport" in data && "dest" in data && "dport" in data && "comment" in data) {
			connection.query('SELECT * FROM vncp_kvm_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('KVMFirewallRuleRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
							px.get('/pools/'+results[0].pool_id, {}, function(err, pooldata) {
								if(pooldata['members'].length == 1) {
									var vmid = pooldata['members'][0]['vmid'];
								}else{
									for(var j = 0; j < pooldata['members'].length; j++) {
										if(pooldata['members'][j]['name'] == results[0].cloud_hostname) {
											var vmid = pooldata['members'][j]['vmid'];
										}
									}
								}
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
								px.post('/nodes/'+results[0].node+'/qemu/'+vmid+'/firewall/rules', fwopts, function(err, set) {
									if(err) {
										logger.info(err);
										socket.emit('KVMFirewallRuleRes', response.status);
									}else{
										response.status = 'ok';
										socket.emit('KVMFirewallRuleRes', response.status);
									}
								});
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('KVMFirewallRuleRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('KVMFirewallRuleRes', response.status);
		}
	},
	rmfwrule: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data && "pos" in data) {
			connection.query('SELECT * FROM vncp_kvm_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('KVMFirewallRemoveRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
							px.get('/pools/'+results[0].pool_id, {}, function(err, pooldata) {
								if(pooldata['members'].length == 1) {
									var vmid = pooldata['members'][0]['vmid'];
								}else{
									for(var j = 0; j < pooldata['members'].length; j++) {
										if(pooldata['members'][j]['name'] == results[0].cloud_hostname) {
											var vmid = pooldata['members'][j]['vmid'];
										}
									}
								}
								px.delete('/nodes/'+results[0].node+'/qemu/'+vmid+'/firewall/rules/'+data.pos, {}, function(err, set) {
									if(err) {
										logger.info(err);
										socket.emit('KVMFirewallRemoveRes', response.status);
									}else{
										response.status = 'ok';
										socket.emit('KVMFirewallRemoveRes', response.status);
									}
								});
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('KVMFirewallRemoveRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('KVMFirewallRemoveRes', response.status);
		}
	},
	pubiface: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data && "action" in data) {
			connection.query('SELECT * FROM vncp_kvm_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('KVMIfaceNet0Res', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
							px.get('/pools/'+results[0].pool_id, {}, function(err, pooldata) {
								if(pooldata['members'].length == 1) {
									var vmid = pooldata['members'][0]['vmid'];
								}else{
									for(var j = 0; j < pooldata['members'].length; j++) {
										if(pooldata['members'][j]['name'] == results[0].cloud_hostname) {
											var vmid = pooldata['members'][j]['vmid'];
										}
									}
								}
								px.get('/nodes/'+results[0].node+'/qemu/'+vmid+'/config', {}, function(err, resp) {
									if(data.action == 'enable') {
										var current = resp.net0;
										var opts = {
											net0: current + ',firewall=1'
										};
										px.put('/nodes/'+results[0].node+'/qemu/'+vmid+'/config', opts, function(err, set) {
											if(err) {
												logger.info(err);
												socket.emit('KVMIfaceNet0Res', response.status);
											}else{
												connection.query('UPDATE vncp_kvm_ct SET fw_enabled_net0 = 1 WHERE hb_account_id = ?', [data.aid], function(err, update) {
													if(err) {
														logger.info(err);
														socket.emit('KVMIfaceNet0Res', response.status);
													}else{
														response.status = 'ok';
														socket.emit('KVMIfaceNet0Res', response.status);
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
										px.put('/nodes/'+results[0].node+'/qemu/'+vmid+'/config', opts, function(err, set) {
											if(err) {
												logger.info(err);
												socket.emit('KVMIfaceNet0Res', response.status);
											}else{
												connection.query('UPDATE vncp_kvm_ct SET fw_enabled_net0 = 0 WHERE hb_account_id = ?', [data.aid], function(err, update) {
													if(err) {
														logger.info(err);
														socket.emit('KVMIfaceNet0Res', response.status);
													}else{
														response.status = 'ok';
														socket.emit('KVMIfaceNet0Res', response.status);
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
						socket.emit('KVMIfaceNet0Res', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('KVMIfaceNet0Res', response.status);
		}
	},
	priviface: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data && "action" in data) {
			connection.query('SELECT * FROM vncp_kvm_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('KVMIfaceNet1Res', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
							px.get('/pools/'+results[0].pool_id, {}, function(err, pooldata) {
								if(pooldata['members'].length == 1) {
									var vmid = pooldata['members'][0]['vmid'];
								}else{
									for(var j = 0; j < pooldata['members'].length; j++) {
										if(pooldata['members'][j]['name'] == results[0].cloud_hostname) {
											var vmid = pooldata['members'][j]['vmid'];
										}
									}
								}
								px.get('/nodes/'+results[0].node+'/qemu/'+vmid+'/config', {}, function(err, resp) {
									if(data.action == 'enable') {
										var current = resp.net1;
										var opts = {
											net1: current + ',firewall=1'
										};
										px.put('/nodes/'+results[0].node+'/qemu/'+vmid+'/config', opts, function(err, set) {
											if(err) {
												logger.info(err);
												socket.emit('KVMIfaceNet1Res', response.status);
											}else{
												connection.query('UPDATE vncp_kvm_ct SET fw_enabled_net1 = 1 WHERE hb_account_id = ?', [data.aid], function(err, update) {
													if(err) {
														logger.info(err);
														socket.emit('KVMIfaceNet1Res', response.status);
													}else{
														response.status = 'ok';
														socket.emit('KVMIfaceNet1Res', response.status);
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
										px.put('/nodes/'+results[0].node+'/qemu/'+vmid+'/config', opts, function(err, set) {
											if(err) {
												logger.info(err);
												socket.emit('KVMIfaceNet1Res', response.status);
											}else{
												connection.query('UPDATE vncp_kvm_ct SET fw_enabled_net1 = 0 WHERE hb_account_id = ?', [data.aid], function(err, update) {
													if(err) {
														logger.info(err);
														socket.emit('KVMIfaceNet1Res', response.status);
													}else{
														response.status = 'ok';
														socket.emit('KVMIfaceNet1Res', response.status);
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
						socket.emit('KVMIfaceNet1Res', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('KVMIfaceNet1Res', response.status);
		}
	},
	enableonboot: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data) {
			connection.query('SELECT * FROM vncp_kvm_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('KVMEnableOnbootRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
							px.get('/pools/'+results[0].pool_id, {}, function(err, pooldata) {
								if(pooldata['members'].length == 1) {
									var vmid = pooldata['members'][0]['vmid'];
								}else{
									for(var j = 0; j < pooldata['members'].length; j++) {
										if(pooldata['members'][j]['name'] == results[0].cloud_hostname) {
											var vmid = pooldata['members'][j]['vmid'];
										}
									}
								}
								var opts = {
									onboot: 1
								};
								px.put('/nodes/'+results[0].node+'/qemu/'+vmid+'/config', opts, function(err, set) {
									if(err) {
										logger.info(err);
										socket.emit('KVMEnableOnbootRes', response.status);
									}else{
										connection.query('UPDATE vncp_kvm_ct SET onboot = 1 WHERE hb_account_id = ?', [data.aid], function(err, update) {
											if(err) {
												logger.info(err);
												socket.emit('KVMEnableOnbootRes', response.status);
											}else{
												response.status = 'ok';
												socket.emit('KVMEnableOnbootRes', response.status);
											}
										});
									}
								});
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('KVMEnableOnbootRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('KVMEnableOnbootRes', response.status);
		}
	},
	disableonboot: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data) {
			connection.query('SELECT * FROM vncp_kvm_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('KVMDisableOnbootRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
							px.get('/pools/'+results[0].pool_id, {}, function(err, pooldata) {
								if(pooldata['members'].length == 1) {
									var vmid = pooldata['members'][0]['vmid'];
								}else{
									for(var j = 0; j < pooldata['members'].length; j++) {
										if(pooldata['members'][j]['name'] == results[0].cloud_hostname) {
											var vmid = pooldata['members'][j]['vmid'];
										}
									}
								}
								var opts = {
									onboot: 0
								};
								px.put('/nodes/'+results[0].node+'/qemu/'+vmid+'/config', opts, function(err, set) {
									if(err) {
										logger.info(err);
										socket.emit('KVMDisableOnbootRes', response.status);
									}else{
										connection.query('UPDATE vncp_kvm_ct SET onboot = 0 WHERE hb_account_id = ?', [data.aid], function(err, update) {
											if(err) {
												logger.info(err);
												socket.emit('KVMDisableOnbootRes', response.status);
											}else{
												response.status = 'ok';
												socket.emit('KVMDisableOnbootRes', response.status);
											}
										});
									}
								});
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('KVMDisableOnbootRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('KVMDisableOnbootRes', response.status);
		}
	},
	disablerng: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data) {
			connection.query('SELECT * FROM vncp_kvm_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('KVMDisableRNGRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							connection.query('SELECT * FROM vncp_tuntap WHERE node = ?', [results[0].node], function(err, rcreds) {
								if(err) {
									logger.info(err);
									socket.emit('KVMDisableRNGRes', response.status);
								}else{
									if(rcreds.length > 0) {
										var exec = require('ssh-exec');
										var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
										px.get('/pools/'+results[0].pool_id, {}, function(err, pooldata) {
											if(pooldata['members'].length == 1) {
												var vmid = pooldata['members'][0]['vmid'];
											}else{
												for(var j = 0; j < pooldata['members'].length; j++) {
													if(pooldata['members'][j]['name'] == results[0].cloud_hostname) {
														var vmid = pooldata['members'][j]['vmid'];
													}
												}
											}
											var opts = {
												user: 'root',
												host: node[0].hostname,
												password: mc.decrypt(rcreds[0].password),
												port: rcreds[0].port
											};
											var execstr = 'pvesh set /nodes/'+results[0].node+'/qemu/'+vmid+'/config --delete rng0';
											exec(execstr, opts, function(err, stdout, stderr) {
												if(err) {
													logger.info(err);
													socket.emit('KVMDisableRNGRes', response.status);
												}else{
													response.status = 'ok';
													socket.emit('KVMDisableRNGRes', response.status);
												}
											});
										});
									}else{
										logger.info('No TUN/TAP credentials exist for this node (kvm disable RNG).');
										socket.emit('KVMDisableRNGRes', response.status);
									}
								}
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('KVMDisableRNGRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('KVMDisableRNGRes', response.status);
		}
	},
	enablerng: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data) {
			connection.query('SELECT * FROM vncp_kvm_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('KVMEnableRNGRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							connection.query('SELECT * FROM vncp_tuntap WHERE node = ?', [results[0].node], function(err, rcreds) {
								if(err) {
									logger.info(err);
									socket.emit('KVMEnableRNGRes', response.status);
								}else{
									if(rcreds.length > 0) {
										var exec = require('ssh-exec');
										var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
										px.get('/pools/'+results[0].pool_id, {}, function(err, pooldata) {
											if(pooldata['members'].length == 1) {
												var vmid = pooldata['members'][0]['vmid'];
											}else{
												for(var j = 0; j < pooldata['members'].length; j++) {
													if(pooldata['members'][j]['name'] == results[0].cloud_hostname) {
														var vmid = pooldata['members'][j]['vmid'];
													}
												}
											}
											var opts = {
												user: 'root',
												host: node[0].hostname,
												password: mc.decrypt(rcreds[0].password),
												port: rcreds[0].port
											};
											var execstr = 'pvesh set /nodes/'+results[0].node+'/qemu/'+vmid+'/config --rng0 source=/dev/urandom,max_bytes=1024,period=1000';
											exec(execstr, opts, function(err, stdout, stderr) {
												if(err) {
													logger.info(err);
													socket.emit('KVMEnableRNGRes', response.status);
												}else{
													response.status = 'ok';
													socket.emit('KVMEnableRNGRes', response.status);
												}
											});
										});
									}else{
										logger.info('No TUN/TAP credentials exist for this node (kvm enable RNG).');
										socket.emit('KVMEnableRNGRes', response.status);
									}
								}
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('KVMEnableRNGRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('KVMEnableRNGRes', response.status);
		}
	},
	changeiso: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("hb_account_id" in data && "iso" in data) {
			connection.query('SELECT * FROM vncp_kvm_ct WHERE hb_account_id = ?', [data.hb_account_id], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('KVMChangeISORes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
							px.get('/pools/'+results[0].pool_id, {}, function(err, pooldata) {
								if(pooldata['members'].length == 1) {
									var vmid = pooldata['members'][0]['vmid'];
								}else{
									for(var j = 0; j < pooldata['members'].length; j++) {
										if(pooldata['members'][j]['name'] == results[0].cloud_hostname) {
											var vmid = pooldata['members'][j]['vmid'];
										}
									}
								}
								var opts = {
									ide2: data.iso + ',media=cdrom'
								};
								px.post('/nodes/'+results[0].node+'/qemu/'+vmid+'/config', opts, function(err, set) {
									if(err) {
										logger.info(err);
										socket.emit('KVMChangeISORes', response.status);
									}else{
										var newos = data.iso.split("/");
										connection.query('UPDATE vncp_kvm_ct SET os = ? WHERE hb_account_id = ?', [newos[1], data.hb_account_id], function(err, update) {
											if(err) {
												logger.info(err);
												socket.emit('KVMChangeISORes', response.status);
											}else{
												response.status = 'ok';
												socket.emit('KVMChangeISORes', response.status);
											}
										});
									}
								});
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('KVMChangeISORes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('KVMChangeISORes', response.status);
		}
	},
	bootorder: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("hb_account_id" in data && "bo1" in data && "bo2" in data && "bo3" in data) {
			connection.query('SELECT * FROM vncp_kvm_ct WHERE hb_account_id = ?', [data.hb_account_id], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('KVMBootOrderRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
							px.get('/pools/'+results[0].pool_id, {}, function(err, pooldata) {
								if(pooldata['members'].length == 1) {
									var vmid = pooldata['members'][0]['vmid'];
								}else{
									for(var j = 0; j < pooldata['members'].length; j++) {
										if(pooldata['members'][j]['name'] == results[0].cloud_hostname) {
											var vmid = pooldata['members'][j]['vmid'];
										}
									}
								}
								var str = '';
								if(data.bo1 == data.bo2)
									str = str + data.bo1 + data.bo3;
								else if(data.bo1 == data.bo3)
									str = str + data.bo1 + data.bo2;
								else if(data.bo2 == data.bo3)
									str = str + data.bo1 + data.bo2;
								else if(data.bo1 == data.bo2 && data.bo2 == data.bo3)
									str = str + data.bo1;
								else if(data.bo2 == data.bo1 && data.bo1 == data.bo3)
									str = str + data.bo2;
								else
									str = str + data.bo1 + data.bo2 + data.bo3;
								var opts = {
									boot: str
								};
								px.post('/nodes/'+results[0].node+'/qemu/'+vmid+'/config', opts, function(err, set) {
									if(err) {
										logger.info(err);
										socket.emit('KVMBootOrderRes', response.status);
									}else{
										response.status = 'ok';
										socket.emit('KVMBootOrderRes', response.status);
									}
								});
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('KVMBootOrderRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('KVMBootOrderRes', response.status);
		}
	},
	getlog: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data) {
			connection.query('SELECT * FROM vncp_kvm_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('KVMGetLogRes', response);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
							px.get('/pools/'+results[0].pool_id, {}, function(err, pooldata) {
								if(pooldata['members'].length == 1) {
									var vmid = pooldata['members'][0]['vmid'];
								}else{
									for(var j = 0; j < pooldata['members'].length; j++) {
										if(pooldata['members'][j]['name'] == results[0].cloud_hostname) {
											var vmid = pooldata['members'][j]['vmid'];
										}
									}
								}
								px.get('/nodes/'+results[0].node+'/tasks', { vmid: vmid }, function(err, tasks) {
									response.log = '';
									for(var k = 0; k < tasks.length; k++) {
										var starttime = timeConverter(tasks[k].starttime);
										var endtime = timeConverter(tasks[k].endtime);
										response.log = response.log + starttime + ' - ' + endtime + ': ' + tasks[k].type + ' > ' + tasks[k].status + '&#13;&#10;';
									}
									response.status = 'ok';
									socket.emit('KVMGetLogRes', response);
								});
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('KVMGetLogRes', response);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('KVMGetLogRes', response.status);
		}
	},
	schedulebackup: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data) {
			connection.query('SELECT * FROM vncp_kvm_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('KVMScheduleBackupRes', response);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
							px.get('/pools/'+results[0].pool_id, {}, function(err, pooldata) {
								if(pooldata['members'].length == 1) {
									var vmid = pooldata['members'][0]['vmid'];
								}else{
									for(var j = 0; j < pooldata['members'].length; j++) {
										if(pooldata['members'][j]['name'] == results[0].cloud_hostname) {
											var vmid = pooldata['members'][j]['vmid'];
										}
									}
								}
								connection.query('SELECT * FROM vncp_ct_backups WHERE hb_account_id = ?', [data.aid], function(err, ctbackups) {
									if(err) {
										logger.info(err);
										socket.emit('KVMScheduleBackupRes', response);
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
												socket.emit('KVMScheduleBackupRes', response);
											});
										}else if(ctbackups[0].backuplimit < 0) {
											connection.query('SELECT * FROM vncp_settings WHERE item = ?', ['backup_limit'], function(err, defaultlimit) {
												if(err) {
													logger.info(err);
													socket.emit('KVMScheduleBackupRes', response);
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
														socket.emit('KVMScheduleBackupRes', response);
													});
												}
											});
										}else{
											logger.info('Backup limit set to 0, scheduled backup not allowed.');
											socket.emit('KVMScheduleBackupRes', response);
										}
									}
								});
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('KVMScheduleBackupRes', response);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('KVMScheduleBackupRes', response);
		}
	},
	delschbackup: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data) {
			connection.query('SELECT * FROM vncp_kvm_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('KVMScheduleBackupDelRes', response);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
							px.delete('/cluster/backup/'+data.schid, {}, function(err, delsch) {
								response.status = 'ok';
								socket.emit('KVMScheduleBackupDelRes', response);
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('KVMScheduleBackupDelRes', response);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('KVMScheduleBackupDelRes', response);
		}
	},
	enableprivatenet: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data) {
			connection.query('SELECT * FROM vncp_kvm_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('KVMEnablePrivateNetworkRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
							px.get('/pools/'+results[0].pool_id, {}, function(err, pooldata) {
								if(pooldata['members'].length == 1) {
									var vmid = pooldata['members'][0]['vmid'];
								}else{
									for(var j = 0; j < pooldata['members'].length; j++) {
										if(pooldata['members'][j]['name'] == results[0].cloud_hostname) {
											var vmid = pooldata['members'][j]['vmid'];
										}
									}
								}
								var opts = {
									net1: 'model=e1000,bridge=vmbr1'
								};
								px.post('/nodes/'+results[0].node+'/qemu/'+vmid+'/config', opts, function(err, device) {
									if(err) {
										logger.info(err);
										socket.emit('KVMEnablePrivateNetworkRes', response.status);
									}else{
										connection.query('UPDATE vncp_private_pool SET user_id=?, hb_account_id=?, available=0 WHERE (available=1 AND nodes LIKE ?) LIMIT 1', [results[0].user_id, data.aid, "%"+results[0].node+"%"], function(err, ip) {
											if(err) {
												logger.info(err);
												socket.emit('KVMEnablePrivateNetworkRes', response.status);
											}else{
												connection.query('UPDATE vncp_kvm_ct SET has_net1=1 WHERE hb_account_id=?', [data.aid], function(err, priv) {
													if(err) {
														logger.info(err);
														socket.emit('KVMEnablePrivateNetworkRes', response.status);
													}else{
														response.status = 'ok';
														socket.emit('KVMEnablePrivateNetworkRes', response.status);
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
						socket.emit('KVMEnablePrivateNetworkRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('KVMEnablePrivateNetworkRes', response.status);
		}
	},
	disableprivatenet: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data) {
			connection.query('SELECT * FROM vncp_kvm_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('KVMDisablePrivateNetworkRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
							px.get('/pools/'+results[0].pool_id, {}, function(err, pooldata) {
								if(pooldata['members'].length == 1) {
									var vmid = pooldata['members'][0]['vmid'];
								}else{
									for(var j = 0; j < pooldata['members'].length; j++) {
										if(pooldata['members'][j]['name'] == results[0].cloud_hostname) {
											var vmid = pooldata['members'][j]['vmid'];
										}
									}
								}
								var opts = {
									delete: 'net1'
								};
								px.post('/nodes/'+results[0].node+'/qemu/'+vmid+'/config', opts, function(err, device) {
									if(err) {
										logger.info(err);
										socket.emit('KVMDisablePrivateNetworkRes', response.status);
									}else{
										connection.query('UPDATE vncp_private_pool SET user_id=0, hb_account_id=0, available=1 WHERE hb_account_id=?', [data.aid], function(err, ip) {
											if(err) {
												logger.info(err);
												socket.emit('KVMDisablePrivateNetworkRes', response.status);
											}else{
												connection.query('UPDATE vncp_kvm_ct SET has_net1=0 WHERE hb_account_id=?', [data.aid], function(err, priv) {
													if(err) {
														logger.info(err);
														socket.emit('KVMDisablePrivateNetworkRes', response.status);
													}else{
														response.status = 'ok';
														socket.emit('KVMDisablePrivateNetworkRes', response.status);
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
						socket.emit('KVMDisablePrivateNetworkRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('KVMDisablePrivateNetworkRes', response.status);
		}
	},
	assignipv6: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data) {
			connection.query('SELECT * FROM vncp_kvm_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('KVMAssignIPv6Res', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query("SELECT * FROM vncp_ipv6_pool WHERE nodes LIKE ?", ["%"+results[0].node+"%"], function(err, v6pool) {
							if(err) {
								logger.info(err);
								socket.emit('KVMAssignIPv6Res', response.status);
							}else{
								if(v6pool.length > 0) {
									connection.query("SELECT * FROM vncp_settings WHERE item = ?", ['ipv6_mode'], function(err, v6mode) {
										if(err) {
											logger.info(err);
											socket.emit('KVMAssignIPv6Res', response.status);
										}else{
											connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
												var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
												px.get('/pools/'+results[0].pool_id, {}, function(err, pooldata) {
													if(pooldata['members'].length == 1) {
														var vmid = pooldata['members'][0]['vmid'];
													}else{
														for(var j = 0; j < pooldata['members'].length; j++) {
															if(pooldata['members'][j]['name'] == results[0].cloud_hostname) {
																var vmid = pooldata['members'][j]['vmid'];
															}
														}
													}
													px.get('/nodes/'+results[0].node+'/qemu/'+vmid+'/config', {}, function(err, device) {
														if(err) {
															logger.info(err);
															socket.emit('KVMAssignIPv6Res', response.status);
														}else{
															var dnet0 = device.net0.split(",");
															var opts = null;
															if(dnet0.indexOf('bridge=vmbr10') != -1) {
																opts = {
																	net10: 'model=e1000,bridge=vmbr0,firewall=0'
																};
															}
															if(v6mode[0].value == 'single') {
																var srand = randomInt(0, v6pool.length - 1);
																var ipv6 = genv6(1, v6pool[srand].subnet.split("/")[0], parseInt(v6pool[srand].subnet.split("/")[1]));
																if(opts != null) {
																	px.put('/nodes/'+results[0].node+'/qemu/'+vmid+'/config', opts, function(err, ip6set) {
																		if(err) {
																			logger.info(err);
																			socket.emit('KVMAssignIPv6Res', response.status);
																		}else{
																			connection.query('INSERT INTO vncp_ipv6_assignment (user_id, hb_account_id, address, ipv6_pool_id) VALUES (?, ?, ?, ?)', [results[0].user_id, data.aid, ipv6[0], v6pool[srand].id], function(err, insert) {
																				if(err) {
																					logger.info(err);
																					socket.emit('KVMAssignIPv6Res', response.status);
																				}else{
																					response.status = 'ok';
																					socket.emit('KVMAssignIPv6Res', response.status);
																				}
																			});
																		}
																	});
																}else{
																	connection.query('INSERT INTO vncp_ipv6_assignment (user_id, hb_account_id, address, ipv6_pool_id) VALUES (?, ?, ?, ?)', [results[0].user_id, data.aid, ipv6[0], v6pool[srand].id], function(err, insert) {
																		if(err) {
																			logger.info(err);
																			socket.emit('KVMAssignIPv6Res', response.status);
																		}else{
																			response.status = 'ok';
																			socket.emit('KVMAssignIPv6Res', response.status);
																		}
																	});
																}
															}else if(v6mode[0].value == 'subnet') {
																var ip6 = require('ip6');
																var srand = randomInt(0, v6pool.length - 1);
																var subnets = ip6.randomSubnet(v6pool[srand].subnet.split("/")[0], parseInt(v6pool[srand].subnet.split("/")[1]), 64, 1, true)[0];
																if(opts != null) {
																	px.put('/nodes/'+results[0].node+'/qemu/'+vmid+'/config', opts, function(err, ip6set) {
																		if(err) {
																			logger.info(err);
																			socket.emit('KVMAssignIPv6Res', response.status);
																		}else{
																			connection.query('INSERT INTO vncp_ipv6_assignment (user_id, hb_account_id, address, ipv6_pool_id) VALUES (?, ?, ?, ?)', [results[0].user_id, data.aid, subnets+"/64", v6pool[srand].id], function(err, insert) {
																				if(err) {
																					logger.info(err);
																					socket.emit('KVMAssignIPv6Res', response.status);
																				}else{
																					response.status = 'ok';
																					socket.emit('KVMAssignIPv6Res', response.status);
																				}
																			});
																		}
																	});
																}else{
																	connection.query('INSERT INTO vncp_ipv6_assignment (user_id, hb_account_id, address, ipv6_pool_id) VALUES (?, ?, ?, ?)', [results[0].user_id, data.aid, subnets+"/64", v6pool[srand].id], function(err, insert) {
																		if(err) {
																			logger.info(err);
																			socket.emit('KVMAssignIPv6Res', response.status);
																		}else{
																			response.status = 'ok';
																			socket.emit('KVMAssignIPv6Res', response.status);
																		}
																	});
																}
															}else{
																logger.info('Invalid IPv6 mode');
																socket.emit('KVMAssignIPv6Res', response.status);
															}
														}
													});
												});
											});
										}
									});
								}else{
									logger.info('No IPv6 pools found.');
									socket.emit('KVMAssignIPv6Res', response.status);
								}
							}
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('KVMAssignIPv6Res', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('KVMAssignIPv6Res', response.status);
		}
	},
	changepw: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data) {
			connection.query('SELECT * FROM vncp_kvm_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('KVMChangePWRes', response);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							if(err) {
								logger.info(err);
								socket.emit('KVMChangePWRes', response);
							}else{
								var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
								px.get('/pools/'+results[0].pool_id, {}, function(err, pooldata) {
									var vmid = pooldata['members'][0]['vmid'];
									var newpw = randPW(12);
									px.post('/nodes/'+results[0].node+'/qemu/'+vmid+'/config', {cipassword: newpw}, function(err, passset) {
										px.post('/nodes/'+results[0].node+'/qemu/'+vmid+'/status/stop', {}, function(err, pwupdated) {
											var delayed = require('delayed');
											delayed.delay(function() {
												px.post('/nodes/'+results[0].node+'/qemu/'+vmid+'/status/start', {}, function(err, pwupdated2) {
													response.status = 'ok';
													response.password = newpw;
													socket.emit('KVMChangePWRes', response);
												});
											}, 3000);
										});
									});
								});
							}
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('KVMChangePWRes', response);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('KVMChangePWRes', response.status);
		}
	},
	isodelete: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("id" in data) {
			connection.query('SELECT * FROM vncp_kvm_isos_custom WHERE id = ?', [data.id], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('UserISODeleteRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_kvm_isos WHERE id > 0 LIMIT 1', [], function(err, isos) {
							if(err) {
								logger.info(err);
								socket.emit('UserISODeleteRes', response.status);
							}else{
								var storage_location = isos[0].volid.split(":")[0];
								connection.query('SELECT * FROM vncp_nodes WHERE id > 0 LIMIT 1', [], function(err, node) {
									if(err) {
										logger.info(err);
										socket.emit('UserISODeleteRes', response.status);
									}else{
										var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
										px.delete('/nodes/'+node[0].name+'/storage/'+storage_location+'/content/'+storage_location+':iso/'+results[0].upload_key+'.iso', {}, function(err, deleteiso) {
											connection.query('DELETE FROM vncp_kvm_isos_custom WHERE id = ?', [data.id], function(err, deleted) {
												if(err) {
													logger.info(err);
													socket.emit('UserISODeleteRes', response.status);
												}else{
													response.status = 'ok';
													socket.emit('UserISODeleteRes', response.status);
												}
											});
										});
									}
								});
							}
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('UserISODeleteRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('UserISODeleteRes', response.status);
		}
	},
	editfw: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data && "action" in data && "type" in data && "enable" in data && "iface" in data && "proto" in data && "source" in data && "sport" in data && "dest" in data && "dport" in data && "comment" in data) {
			connection.query('SELECT * FROM vncp_kvm_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('KVMFirewallEditRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
							px.get('/pools/'+results[0].pool_id, {}, function(err, pooldata) {
								if(pooldata['members'].length == 1) {
									var vmid = pooldata['members'][0]['vmid'];
								}else{
									for(var j = 0; j < pooldata['members'].length; j++) {
										if(pooldata['members'][j]['name'] == results[0].cloud_hostname) {
											var vmid = pooldata['members'][j]['vmid'];
										}
									}
								}
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
								px.put('/nodes/'+results[0].node+'/qemu/'+vmid+'/firewall/rules/'+data.pos, fwopts, function(err, set) {
									if(err) {
										logger.info(err);
										socket.emit('KVMFirewallEditRes', response.status);
									}else{
										response.status = 'ok';
										socket.emit('KVMFirewallEditRes', response.status);
									}
								});
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('KVMFirewallEditRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('KVMFirewallEditRes', response.status);
		}
	},
	addnatport: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data) {
			connection.query('SELECT * FROM vncp_kvm_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('KVMAddNATPortRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							connection.query('SELECT * FROM vncp_nat WHERE node = ?', [results[0].node], function(err, natnode) {
								connection.query('SELECT * FROM vncp_tuntap WHERE node = ?', [results[0].node], function(err, rcreds) {
									if(err) {
										logger.info(err);
										socket.emit('KVMAddNATPortRes', response.status);
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
													socket.emit('KVMAddNATPortRes', response.status);
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
															socket.emit('KVMAddNATPortRes', response.status);
														}else{
															var portcount = curports[0].ports.split(";").length - 1;
															var availports = parseInt(curports[0].avail_ports);
															if(portcount >= availports) {
																logger.info('Could not add NAT port, only '+availports+' allowed.');
																socket.emit('KVMAddNATPortRes', response.status);
															}else{
																exec('iptables -t nat -A PREROUTING -p tcp -d '+natnode[0].publicip+' --dport '+publicport+' -i vmbr0 -j DNAT --to-destination '+results[0].ip+':'+data.natport+' && iptables-save > /root/proxcp-iptables.rules', opts, function(err, stdout, stderr) {
																	var currentports = curports[0].ports + (portcount+1).toString() + ":"+publicport+":"+data.natport+":"+data.natdesc.substring(0, 20)+";";
																	connection.query('UPDATE vncp_natforwarding SET ports = ? WHERE hb_account_id = ?', [currentports, data.aid], function(err, update) {
																		if(err) {
																			logger.info(err);
																			socket.emit('KVMAddNATPortRes', response.status);
																		}else{
																			response.status = 'ok';
																			socket.emit('KVMAddNATPortRes', response.status);
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
											socket.emit('KVMAddNATPortRes', response.status);
										}
									}
								});
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('KVMAddNATPortRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('KVMAddNATPortRes', response.status);
		}
	},
	delnatport: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data) {
			connection.query('SELECT * FROM vncp_kvm_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('KVMDelNATPortRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							connection.query('SELECT * FROM vncp_nat WHERE node = ?', [results[0].node], function(err, natnode) {
								connection.query('SELECT * FROM vncp_tuntap WHERE node = ?', [results[0].node], function(err, rcreds) {
									if(err) {
										logger.info(err);
										socket.emit('KVMDelNATPortRes', response.status);
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
													socket.emit('KVMDelNATPortRes', response.status);
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
																socket.emit('KVMDelNATPortRes', response.status);
															}else{
																response.status = 'ok';
																socket.emit('KVMDelNATPortRes', response.status);
															}
														});
													});
												}
											});
										}else{
											logger.info('No TUN/TAP credentials exist for this node.');
											socket.emit('KVMDelNATPortRes', response.status);
										}
									}
								});
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('KVMDelNATPortRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('KVMDelNATPortRes', response.status);
		}
	},
	addnatdomain: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data) {
			connection.query('SELECT * FROM vncp_kvm_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('KVMAddNATDomainRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							connection.query('SELECT * FROM vncp_nat WHERE node = ?', [results[0].node], function(err, natnode) {
								connection.query('SELECT * FROM vncp_tuntap WHERE node = ?', [results[0].node], function(err, rcreds) {
									if(err) {
										logger.info(err);
										socket.emit('KVMAddNATDomainRes', response.status);
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
													socket.emit('KVMAddNATDomainRes', response.status);
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
															socket.emit('KVMAddNATDomainRes', response.status);
														}else{
															connection.query('SELECT * FROM vncp_natforwarding WHERE hb_account_id = ?', [data.aid], function(err, curdomains) {
																if(err) {
																	logger.info(err);
																	socket.emit('KVMAddNATDomainRes', response.status);
																}else{
																	var domaincount = curdomains[0].domains.split(";").length - 1;
																	var availdomains = parseInt(curdomains[0].avail_domains);
																	if(domaincount >= availdomains) {
																		logger.info('Could not add NAT domain, only '+availdomains+' allowed.');
																		socket.emit('KVMAddNATDomainRes', response.status);
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
																								socket.emit('KVMAddNATDomainRes', response.status);
																							}else{
																								response.status = 'ok';
																								socket.emit('KVMAddNATDomainRes', response.status);
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
																							socket.emit('KVMAddNATDomainRes', response.status);
																						}else{
																							response.status = 'ok';
																							socket.emit('KVMAddNATDomainRes', response.status);
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
														socket.emit('KVMAddNATDomainRes', response.status);
													}
												}
											});
										}else{
											logger.info('No TUN/TAP credentials exist for this node.');
											socket.emit('KVMAddNATDomainRes', response.status);
										}
									}
								});
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('KVMAddNATDomainRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('KVMAddNATDomainRes', response.status);
		}
	},
	delnatdomain: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data) {
			connection.query('SELECT * FROM vncp_kvm_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('KVMDelNATDomainRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [results[0].node], function(err, node) {
							connection.query('SELECT * FROM vncp_nat WHERE node = ?', [results[0].node], function(err, natnode) {
								connection.query('SELECT * FROM vncp_tuntap WHERE node = ?', [results[0].node], function(err, rcreds) {
									if(err) {
										logger.info(err);
										socket.emit('KVMDelNATDomainRes', response.status);
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
														socket.emit('KVMDelNATDomainRes', response.status);
													}else{
														if(curdomains[0].domains.includes(data.id)) {
															var alldomaindata = curdomains[0].domains.split(";");
															alldomaindata.splice(alldomaindata.indexOf(data.id), 1);
															alldomaindata = alldomaindata.join(';');
															exec('rm /etc/nginx/conf.d/'+data.aid+'-'+data.id+'-*.conf && rm /etc/nginx/proxcp-nat-ssl/cert-'+data.aid+'-'+data.id+'-*.pem && rm /etc/nginx/proxcp-nat-ssl/key-'+data.aid+'-'+data.id+'-*.pem && service nginx restart', opts, function(err, stdout, stderr) {
																connection.query('UPDATE vncp_natforwarding SET domains = ? WHERE hb_account_id = ?', [alldomaindata, data.aid], function(err, update) {
																	if(err) {
																		logger.info(err);
																		socket.emit('KVMDelNATDomainRes', response.status);
																	}else{
																		response.status = 'ok';
																		socket.emit('KVMDelNATDomainRes', response.status);
																	}
																});
															});
														}else{
															logger.info('Invalid NAT domain permissions.');
															socket.emit('KVMDelNATDomainRes', response.status);
														}
													}
												});
											}else{
												logger.info('Invalid NAT domain.');
												socket.emit('KVMDelNATDomainRes', response.status);
											}
										}else{
											logger.info('No TUN/TAP credentials exist for this node.');
											socket.emit('KVMDelNATDomainRes', response.status);
										}
									}
								});
							});
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('KVMDelNATDomainRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('KVMDelNATDomainRes', response.status);
		}
	}
};
