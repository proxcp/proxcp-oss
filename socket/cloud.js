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
			console.log('[PROXCP:CLOUD] Attempting to reconnect...');
			handleDisconnect();
		}else{
			throw err;
		}
	});
}
handleDisconnect();

var exec = require('exec');
var MagicCrypt = require('magiccrypt');
crypto = config.vncp_secret_key;
crypto = crypto.split(".");
var mc = new MagicCrypt(crypto[0], 256, crypto[1]);
var SSH = require('simple-ssh');

function randomInt (low, high) {
    return Math.floor(Math.random() * (high - low + 1) + low);
}

function genMAC(){
    var hexDigits = "0123456789ABCDEF";
    var macAddress = "00:";
    for (var i = 0; i < 5; i++) {
        macAddress += hexDigits.charAt(Math.round(Math.random() * 15));
        macAddress += hexDigits.charAt(Math.round(Math.random() * 15));
        if (i != 4) macAddress += ":";
    }
    return macAddress;
}

function removeIPv4(ipaddr, iplist) {
	var list = iplist.split(';');
	var index = list.indexOf(ipaddr);
	var str = '';
	if(index > -1) {
		list.splice(index, 1);
		for(var i = 0; i < list.length; i++) {
			str = str + list[i];
			if(i != (list.length - 1)) {
				str = str + ';';
			}
		}
		return str;
	}else{
		return str;
	}
}

function genDeleteCode() {
    var text = "";
    var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
    for(var i = 0; i < 6; i++)
        text += possible.charAt(Math.floor(Math.random() * possible.length));
    return text;
}

function randPW(x) {
    var s = "";
    while(s.length < x && x > 0) {
        var r = Math.random();
        s += (r < 0.1 ? Math.floor(r*100) : String.fromCharCode(Math.floor(r * 26) + (r > 0.5 ? 97 : 65)));
	}
	return s;
}

module.exports = {
	create: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("clpoolid" in data && "clhostname" in data && "clos" in data && "ram" in data && "cpu" in data && "disk" in data && "clddevice" in data && "clnetdevice" in data) {
			connection.query('SELECT * FROM vncp_kvm_cloud WHERE hb_account_id = ?', [data.clpoolid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('PubCloudCreateRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_kvm_ct WHERE cloud_account_id = ?', [data.clpoolid], function(err, hncheck) {
							var uniquehostname = true;
							if("length" in hncheck && hncheck.length > 0) {
								for(var k = 0; k < hncheck.length; k++) {
									if(hncheck[k].cloud_hostname == data.clhostname.trim()) {
										uniquehostname = false;
									}
								}
							}
							if(uniquehostname == false) {
								logger.info('Hostname not unique');
								socket.emit('PubCloudCreateRes', response.status);
							}else if(results[0].avail_ipv4 == '') {
								logger.info('No available IPv4');
								socket.emit('PubCloudCreateRes', response.status);
							}else if(results[0].avail_memory < data.ram) {
								logger.info('Not enough available RAM');
								socket.emit('PubCloudCreateRes', response.status);
							}else if(results[0].avail_cpu_cores < data.cpu) {
								logger.info('Not enough available CPU cores');
								socket.emit('PubCloudCreateRes', response.status);
							}else if(results[0].avail_disk_size < data.disk) {
								logger.info('Not enough available disk space');
								socket.emit('PubCloudCreateRes', response.status);
							}else{
								var nodes = results[0].nodes;
								nodes = nodes.split(';');
								var rand = randomInt(0, nodes.length - 1);
								connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [nodes[rand]], function(err, node) {
									var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
									px.get('/cluster/nextid', {}, function(err, res) {
										px.get('/nodes/'+nodes[rand]+'/storage', {'enabled':1}, function(err, strlocs) {
											var storages = [];
											for(var i = 0; i < strlocs.length; i++) {
												if(strlocs[i].used_fraction < 0.80 && strlocs[i].content.includes("images")) {
													storages.push(strlocs[i].storage);
												}
											}
											var delayed = require('delayed');
											delayed.delay(function() {
												if(storages.length > 0) {
												  var srand = randomInt(0, storages.length - 1);
												  var storage = storages[srand];
												}else{
												  var storage = 'local';
												}
												var macaddr = genMAC();
												var ipassign = results[0].avail_ipv4;
												ipassign = ipassign.split(';');
												var ipv4 = ipassign[0];
												if(data.clos.indexOf('2008') > -1 || data.clos.indexOf('2012') > -1) {
												  var ostype = 'win8';
												  var vga = 'std';
												}else if(data.clos.indexOf('2016') > -1) {
												  var ostype = 'win10';
												  var vga = 'std';
												}else{
												  var ostype = 'l26';
												  var vga = 'cirrus';
												}
												if(data.clddevice == 'ide') {
												  var bootdisk = 'ide0';
												}else{
												  var bootdisk = 'virtio0';
												}
												if(data.clnetdevice == 'e1000') {
												  var netdev = 'e1000';
												}else{
												  var netdev = 'virtio';
												}
												if(!parseInt(data.clos)) {
												  var newvm = {
												    vmid: res,
												    acpi: 1,
												    agent: 0,
												    balloon: data.ram,
												    boot: 'cdn',
												    bootdisk: bootdisk,
												    cores: data.cpu,
												    cpu: 'kvm64',
												    cpulimit: '0',
												    cpuunits: 1024,
												    description: ipv4,
												    hotplug: '1',
												    ide2: data.clos + ',media=cdrom',
												    kvm: 1,
												    localtime: 1,
												    memory: data.ram,
												    name: data.clhostname.trim(),
												    net0: 'bridge=vmbr0,' + netdev + '=' + macaddr,
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
												}else{
												  var newvm = {
												    newid: res,
												    description: ipv4,
												    format: 'raw',
												    full: 1,
												    name: data.clhostname.trim(),
												    pool: results[0].pool_id,
												    storage: storage
												  };
												}
												if(data.clddevice == 'ide' && !parseInt(data.clos) && !("newid" in newvm)) {
												  newvm.ide0 = storage + ':' + data.disk + ',format=raw,cache=writeback';
												}else if(data.clddevice == 'virtio' && !parseInt(data.clos) && !("newid" in newvm)){
												  newvm.virtio0 = storage + ':' + data.disk + ',format=raw,cache=writeback';
												}
												if(!parseInt(data.clos)) {
												  var post_string = '/nodes/'+nodes[rand]+'/qemu';
												  var from_template = 0;
												}else{
												  var post_string = '/nodes/'+nodes[rand]+'/qemu/'+data.clos+'/clone';
												  var from_template = 1;
												}
												px.post(post_string, newvm, function(err, createvm) {
												  delayed.delay(function() {
												    var avail_memory = results[0].avail_memory - data.ram;
												    var avail_cpu_cores = results[0].avail_cpu_cores - data.cpu;
												    var avail_disk_size = results[0].avail_disk_size - data.disk;
												    var avail_ip_limit = results[0].avail_ip_limit - 1;
												    var avail_ipv4 = removeIPv4(ipv4, results[0].avail_ipv4);
												    connection.query('UPDATE vncp_kvm_cloud SET avail_memory=?, avail_cpu_cores=?, avail_disk_size=?, avail_ip_limit=?, avail_ipv4=? WHERE hb_account_id=?', [avail_memory, avail_cpu_cores, avail_disk_size, avail_ip_limit, avail_ipv4, data.clpoolid], function(err, update) {
												      if(err) {
												        logger.info(err);
												        socket.emit('PubCloudCreateRes', response.status);
												      }else{
												        var uniq = randomInt(2, 1000);
												        var newhb = data.clpoolid * uniq;
																if(parseInt(data.clos)) {
																	if(data.clddevice == 'ide') {
																		var newos = 'Windows';
																	}else{
																		var newos = 'Linux';
																	}
																}else{
																	var newos = data.clos.split("/")[1];
																}
												        connection.query("INSERT INTO vncp_kvm_ct (user_id, node, os, hb_account_id, pool_id, pool_password, ip, suspended, allow_backups, fw_enabled_net0, fw_enabled_net1, has_net1, onboot, cloud_account_id, cloud_hostname, from_template) VALUES ("+results[0].user_id+", '"+nodes[rand]+"', '"+newos+"', "+newhb+", '"+results[0].pool_id+"', '"+results[0].pool_password+"', '"+ipv4+"', 0, 1, 0, 0, 0, 0, "+data.clpoolid+", '"+data.clhostname.trim()+"', "+from_template+")", function(err, insert) {
												          if(err) {
												            logger.info(err);
												            socket.emit('PubCloudCreateRes', response.status);
												          }else{
																			connection.query('INSERT INTO vncp_dhcp (mac_address, ip, gateway, netmask, network, type) VALUES (?, ?, ?, ?, ?, ?)', [macaddr, ipv4, ipv4, ipv4, ipv4, 0], function(err, dhcpinsert) {
													              if(err) {
													                logger.info(err);
													                socket.emit('PubCloudCreateRes', response.status);
													              }else{
																					connection.query('INSERT INTO vncp_ct_backups (userid, hb_account_id, backuplimit) VALUES (?, ?, ?)', [results[0].user_id, newhb, -1], function(err, backuplimit) {
																						if(err) {
																							logger.info(err);
																							socket.emit('PubCloudCreateRes', response.status);
																						}else{
																							if(from_template == 0) {
								                                response.status = 'ok';
								                                socket.emit('PubCloudCreateRes', response.status);
								                              }else{
								                                if(data.clnetdevice == 'virtio') {
								                                  var cvmtype = 'linux';
								                                }else{
								                                  var cvmtype = 'windows';
								                                }
								                                var pending_clone = {
								                                  vmid: res,
								                                  cores: data.cpu,
								                                  cpu: results[0].cpu_type,
								                                  memory: data.ram,
								                                  cipassword: mc.encrypt(randPW(16)),
								                                  storage_size: data.disk,
								                                  cvmtype: cvmtype,
								                                  gateway: [macaddr],
								                                  ip: ipv4
								                                };
								                                connection.query('INSERT INTO vncp_pending_clone (node, upid, hb_account_id, data) VALUES (?, ?, ?, ?)', [nodes[rand], createvm, newhb, JSON.stringify(pending_clone)], function(err, ins_pending) {
								                                  if(err) {
								                                    logger.info(err);
								                                    socket.emit('PubCloudCreateRes', response.status);
								                                  }else{
								                                    response.status = 'ok';
								                                    socket.emit('PubCloudCreateRes', response.status);
								                                  }
								                                });
								                              }
																						}
																					});
													              }
													            });
												          }
												        });
												      }
												    });
												  }, 5000);
												});
											}, 2000);
										});
									});
								});
							}
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('PubCloudCreateRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('PubCloudCreateRes', response.status);
		}
	},
	querypool: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("clpoolid" in data) {
			connection.query('SELECT * FROM vncp_kvm_cloud WHERE hb_account_id = ?', [data.clpoolid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('PubCloudQueryPoolRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT vmid,friendly_name FROM vncp_kvm_templates WHERE node = ?', [results[0].nodes], function(err, temps) {
							if(err) {
								logger.info(err);
								socket.emit('PubCloudQueryPoolRes', response.status);
							}else{
								var retdata = {
									avail_memory: results[0].avail_memory,
									avail_cpu_cores: results[0].avail_cpu_cores,
									avail_disk_size: results[0].avail_disk_size,
									avail_ip_limit: results[0].avail_ip_limit,
									suspended: results[0].suspended,
									templates: temps
								};
								response.status = 'ok';
								response.retdata = retdata;
								socket.emit('PubCloudQueryPoolRes', response);
							}
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('PubCloudQueryPoolRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('PubCloudQueryPoolRes', response.status);
		}
	},
	delete: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("hb_account_id" in data && "clpoolid" in data) {
			connection.query('SELECT * FROM vncp_kvm_ct WHERE hb_account_id = ?', [data.hb_account_id], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('PubCloudDeleteRes', response.status);
				}else if(results.length == 1 && results[0].cloud_account_id == 0) {
					logger.info('This VM is not part of a cloud pool');
					socket.emit('PubCloudDeleteRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_users WHERE id = ?', [results[0].user_id], function(err, useremail) {
							if(err) {
								logger.info(err);
								socket.emit('PubCloudDeleteRes', response.status);
							}else{
								var delcode = genDeleteCode();
								var date = new Date().toMysqlFormat();
								connection.query("INSERT INTO vncp_pending_deletion (user_id, hb_account_id, cloud_account_id, code, date_created) VALUES ("+results[0].user_id+", "+data.hb_account_id+", "+data.clpoolid+", '"+delcode+"', '"+date+"')", function(err, insert) {
									if(err) {
										logger.info(err);
										socket.emit('PubCloudDeleteRes', response.status);
									}else{
										exec(['php', process.cwd()+'/lib/delete_email.php', useremail[0].email, delcode], function(err, out, code) {
											if(err) {
												logger.info(err);
												socket.emit('PubCloudDeleteRes', response.status);
											}else{
												logger.info('Sent confirmation email. Awaiting response...');
												response.status = 'ok';
												socket.emit('PubCloudDeleteRes', response.status);
											}
										});
									}
								});
							}
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('PubCloudDeleteRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('PubCloudDeleteRes', response.status);
		}
	},
	cancel: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("hb_account_id" in data) {
			connection.query('SELECT * FROM vncp_kvm_ct WHERE hb_account_id = ?', [data.hb_account_id], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('PubCloudCancelDeleteRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('DELETE FROM vncp_pending_deletion WHERE hb_account_id = ?', [data.hb_account_id], function(err, deleted) {
							if(err) {
								logger.info(err);
								socket.emit('PubCloudCancelDeleteRes', response.status);
							}else{
								response.status = 'ok';
								socket.emit('PubCloudCancelDeleteRes', response.status);
							}
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('PubCloudCancelDeleteRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('PubCloudCancelDeleteRes', response.status);
		}
	},
	confirmdelete: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("hb_account_id" in data && "delcode" in data && "clpoolid" in data) {
			connection.query('SELECT * FROM vncp_kvm_ct WHERE hb_account_id = ?', [data.hb_account_id], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('PubCloudConfirmDeleteRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_pending_deletion WHERE code = ?', [data.delcode.trim()], function(err, verify) {
							if(err) {
								logger.info(err);
								socket.emit('PubCloudConfirmDeleteRes', response.status);
							}else if(verify.length == 0) {
								logger.info('Invalid code');
								socket.emit('PubCloudConfirmDeleteRes', response.status);
							}else{
								if(verify[0].user_id == results[0].user_id && verify[0].hb_account_id == data.hb_account_id && verify[0].cloud_account_id == data.clpoolid) {
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
											px.post('/nodes/'+results[0].node+'/qemu/'+vmid+'/status/stop', {}, function(err, proc) {
												var delayed = require('delayed');
												delayed.delay(function() {
													px.get('/nodes/'+results[0].node+'/qemu/'+vmid+'/config', {}, function(err, vmconf) {
														var bootdisk = vmconf.bootdisk;
														var oldvm = {
															memory: vmconf.memory,
															cores: vmconf.cores,
															ipv4: vmconf.description
														};
														if(bootdisk == "virtio0") {
															oldvm.disk = vmconf.virtio0;
														}else if(bootdisk == "scsi0") {
															oldvm.disk = vmconf.scsi0;
														}else{
															oldvm.disk = vmconf.ide0;
														}
														oldvm.disk = oldvm.disk.split(",");
														oldvm.disk = oldvm.disk[2];
														oldvm.disk = oldvm.disk.split("=");
														oldvm.disk = oldvm.disk[1];
														oldvm.disk = oldvm.disk.slice(0, -1);
														px.delete('/nodes/'+results[0].node+'/qemu/'+vmid, {}, function(err, deletevm) {
															delayed.delay(function() {
																connection.query('DELETE FROM vncp_kvm_ct WHERE hb_account_id = ?', [data.hb_account_id], function(err, deleted) {
																	if(err) {
																		logger.info(err);
																		socket.emit('PubCloudConfirmDeleteRes', response.status);
																	}else{
																		connection.query('SELECT * FROM vncp_kvm_cloud WHERE hb_account_id = ?', [data.clpoolid], function(err, cloud) {
																			connection.query('SELECT * FROM vncp_secondary_ips WHERE hb_account_id = ?', [data.hb_account_id], function(err, ipcheck) {
																				var avail_memory = cloud[0].avail_memory + parseInt(oldvm.memory);
																				var avail_cpu_cores = cloud[0].avail_cpu_cores + parseInt(oldvm.cores);
																				var avail_disk_size = cloud[0].avail_disk_size + parseInt(oldvm.disk);
																				if(ipcheck.length == 0) {
																					var avail_ip_limit = cloud[0].avail_ip_limit + 1;
																					if(cloud[0].avail_ipv4 == '') {
																						var avail_ipv4 = oldvm.ipv4;
																					}else{
																						var avail_ipv4 = cloud[0].avail_ipv4 + ';' + oldvm.ipv4;
																					}
																				}else{
																					var avail_ip_limit = cloud[0].avail_ip_limit + ipcheck.length + 1;
																					var avail_ipv42 = oldvm.ipv4;
																					for(var i = 0; i < ipcheck.length; i++) {
																						avail_ipv42 = avail_ipv42 + ';' + ipcheck[i].address;
																					}
																					if(cloud[0].avail_ipv4 == '') {
																						var avail_ipv4 = avail_ipv42;
																					}else{
																						var avail_ipv4 = cloud[0].avail_ipv4 + ';' + avail_ipv42;
																					}
																				}
																				delayed.delay(function() {
																					connection.query('UPDATE vncp_kvm_cloud SET avail_memory = ?, avail_cpu_cores = ?, avail_disk_size = ?, avail_ip_limit = ?, avail_ipv4 = ? WHERE hb_account_id = ?', [avail_memory, avail_cpu_cores, avail_disk_size, avail_ip_limit, avail_ipv4, data.clpoolid], function(err, update) {
																						if(err) {
																							logger.info(err);
																							socket.emit('PubCloudConfirmDeleteRes', response.status);
																						}else{
																							connection.query('DELETE FROM vncp_secondary_ips WHERE hb_account_id = ?', [data.hb_account_id], function(err, rmip) {
																								connection.query('DELETE FROM vncp_ipv6_assignment WHERE hb_account_id = ?', [data.hb_account_id], function(err, rmip2) {
																									connection.query('UPDATE vncp_private_pool SET user_id=0, hb_account_id=0, available=1 WHERE hb_account_id = ?', [data.hb_account_id], function(err, rmip3) {
																										connection.query('UPDATE vncp_ipv4_pool SET user_id=0, hb_account_id=0, available=1 WHERE hb_account_id = ?', [data.hb_account_id], function(err, rmip4) {
																											connection.query('DELETE FROM vncp_pending_deletion WHERE code = ?', [data.delcode], function(err, done) {
																												if(err) {
																													logger.info(err);
																													socket.emit('PubCloudConfirmDeleteRes', response.status);
																												}else{
																													connection.query('SELECT * FROM vncp_dhcp WHERE ip = ?', [results[0].ip], function(err, dhcprecord) {
																														if(err) {
																															logger.info(err);
																															socket.emit('PubCloudConfirmDeleteRes', response.status);
																														}else{
																															connection.query('DELETE FROM vncp_dhcp WHERE ip = ?', [results[0].ip], function(err, deletedhcp) {
																																if(err) {
																																	logger.info(err);
																																	socket.emit('PubCloudConfirmDeleteRes', response.status);
																																}else{
																																	connection.query('DELETE FROM vncp_ct_backups WHERE hb_account_id = ?', [data.hb_account_id], function(err, deletect) {
																																		if(err) {
																																			logger.info(err);
																																			socket.emit('PubCloudConfirmDeleteRes', response.status);
																																		}else{
																																			connection.query('SELECT * FROM vncp_dhcp_servers WHERE dhcp_network = ? LIMIT 1', [dhcprecord[0].network], function(err, dhcpserver) {
																																				if(err) {
																																					logger.info(err);
																																					socket.emit('PubCloudConfirmDeleteRes', response.status);
																																				}else{
																																					if(dhcpserver.length == 1) {
																																						var ssh = new SSH({
																																							host: dhcpserver[0].hostname,
																																							user: 'root',
																																							pass: mc.decrypt(dhcpserver[0].password),
																																							port: dhcpserver[0].port
																																						});
																																						ssh.exec("printf 'ddns-update-style none;\n\n' > /root/dhcpd.test").exec("printf 'option domain-name-servers 8.8.8.8, 8.8.4.4;\n\n' >> /root/dhcpd.test").exec("printf 'default-lease-time 7200;\n' >> /root/dhcpd.test").exec("printf 'max-lease-time 86400;\n\n' >> /root/dhcpd.test").exec("printf 'log-facility local7;\n\n' >> /root/dhcpd.test").exec("printf 'subnet "+dhcpserver[0].dhcp_network+" netmask "+dhcprecord[0].netmask+" {}\n\n' >> /root/dhcpd.test");
																																						connection.query('SELECT * FROM vncp_dhcp WHERE network = ?', [dhcpserver[0].dhcp_network], function(err, fulldhcp) {
																																							if(err) {
																																								logger.info(err);
																																								socket.emit('PubCloudConfirmDeleteRes', response.status);
																																							}else{
																																								for(var i = 0; i < fulldhcp.length; i++) {
																																									ssh.exec("printf 'host "+fulldhcp[i].id+" {hardware ethernet "+fulldhcp[i].mac_address+";fixed-address "+fulldhcp[i].ip+";option routers "+fulldhcp[i].gateway+";}\n' >> /root/dhcpd.test");
																																								}
																																								ssh.exec("mv /root/dhcpd.test /etc/dhcp/dhcpd.conf && rm /root/dhcpd.test").exec("service isc-dhcp-server restart").start({
																																									success: function() {
																																										response.status = 'ok';
																																										socket.emit('PubCloudConfirmDeleteRes', response.status);
																																									},
																																									fail: function(err) {
																																										logger.info(err);
																																										socket.emit('PubCloudConfirmDeleteRes', response.status);
																																									}
																																								});
																																							}
																																						});
																																					}else{
																																						response.status = 'ok';
																																						socket.emit('PubCloudConfirmDeleteRes', response.status);
																																					}
																																				}
																																			});
																																		}
																																	});
																																}
																															});
																														}
																													});
																												}
																											});
																										});
																								});
																								});
																							});
																						}
																					});
																				}, 5000);
																			});
																		});
																	}
																});
															}, 5000);
														});
													});
												}, 5000);
											});
										});
									});
								}else{
									logger.info('Invalid verification');
									socket.emit('PubCloudConfirmDeleteRes', response.status);
								}
							}
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('PubCloudConfirmDeleteRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('PubCloudConfirmDeleteRes', response.status);
		}
	},
	assignip: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data) {
			connection.query('SELECT * FROM vncp_kvm_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('PubCloudAssignIPRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_kvm_cloud WHERE hb_account_id = ?', [results[0].cloud_account_id], function(err, clinfo) {
							if(err) {
								logger.info(err);
								socket.emit('PubCloudAssignIPRes', response.status);
							}else{
								if(clinfo[0].avail_ip_limit != 0) {
									var ipassign = clinfo[0].avail_ipv4;
									ipassign = ipassign.split(';');
									var ipv4 = ipassign[0];
									var newlimit = clinfo[0].avail_ip_limit - 1;
									var newipv4 = removeIPv4(ipv4, clinfo[0].avail_ipv4);
									connection.query('UPDATE vncp_kvm_cloud SET avail_ip_limit = ?, avail_ipv4 = ? WHERE hb_account_id = ?', [newlimit, newipv4, results[0].cloud_account_id], function(err, newip) {
										if(err) {
											logger.info(err);
											socket.emit('PubCloudAssignIPRes', response.status);
										}else{
											connection.query('INSERT INTO vncp_secondary_ips (user_id, hb_account_id, address) VALUES (?, ?, ?)', [results[0].user_id, data.aid, ipv4], function(err, updateip) {
												if(err) {
													logger.info(err);
													socket.emit('PubCloudAssignIPRes', response.status);
												}else{
													response.status = 'ok';
													socket.emit('PubCloudAssignIPRes', response.status);
												}
											});
										}
									});
								}else{
									logger.info('No free IPv4 for this pool');
									socket.emit('PubCloudAssignIPRes', response.status);
								}
							}
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('PubCloudAssignIPRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('PubCloudAssignIPRes', response.status);
		}
	},
	removeip: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("aid" in data && "ip" in data) {
			connection.query('SELECT * FROM vncp_kvm_ct WHERE hb_account_id = ?', [data.aid], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('PubCloudRemoveIPRes', response.status);
				}else{
					if(results.length == 1 && results[0].user_id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_kvm_cloud WHERE hb_account_id = ?', [results[0].cloud_account_id], function(err, clinfo) {
							if(err) {
								logger.info(err);
								socket.emit('PubCloudRemoveIPRes', response.status);
							}else{
								connection.query('DELETE FROM vncp_secondary_ips WHERE hb_account_id = ? AND address = ?', [data.aid, data.ip], function(err, rmsecondary) {
									if(err) {
										logger.info(err);
										socket.emit('PubCloudRemoveIPRes', response.status);
									}else{
										var newlimit = clinfo[0].avail_ip_limit + 1;
										if(clinfo[0].avail_ipv4 == '') {
											var newipv4 = data.ip;
										}else{
											var newipv4 = clinfo[0].avail_ipv4 + ';' + data.ip;
										}
										connection.query('UPDATE vncp_kvm_cloud SET avail_ip_limit = ?, avail_ipv4 = ? WHERE hb_account_id = ?', [newlimit, newipv4, results[0].cloud_account_id], function(err, addip) {
											if(err) {
												logger.info(err);
												socket.emit('PubCloudRemoveIPRes', response.status);
											}else{
												response.status = 'ok';
												socket.emit('PubCloudRemoveIPRes', response.status);
											}
										});
									}
								});
							}
						});
					}else{
						logger.info('Invalid user ID');
						socket.emit('PubCloudRemoveIPRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('PubCloudRemoveIPRes', response.status);
		}
	}
};
