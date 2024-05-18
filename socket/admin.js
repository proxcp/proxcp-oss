var config = require(process.cwd()+'/config');
var MagicCrypt = require('magiccrypt');
var mc = new MagicCrypt(config.vncp_secret_key.split('.')[0], 256, config.vncp_secret_key.split('.')[1]);
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
			console.log('[PROXCP:ADMIN] Attempting to reconnect...');
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
var moment = require('moment');

function randPW(x) {
    var s = "";
    while(s.length < x && x > 0) {
        var r = Math.random();
        s += (r < 0.1 ? Math.floor(r*100) : String.fromCharCode(Math.floor(r * 26) + (r > 0.5 ? 97 : 65)));
	}
	return s;
}


module.exports = {
	lockuser: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("by" in data && "id" in data) {
			connection.query('SELECT * FROM vncp_users WHERE id = ?', [data.by], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('ADMUserLockRes', response.status);
				}else{
					if(results.length == 1 && 'group' in results[0] && results[0].group == 2 && results[0].id == connections[socket.id]) {
						connection.query('UPDATE vncp_users SET locked = 1 WHERE id = ?', [data.id], function(err, update) {
							if(err) {
								logger.info(err);
								socket.emit('ADMUserLockRes', response.status);
							}else{
								response.status = 'ok';
								socket.emit('ADMUserLockRes', response.status);
							}
						});
					}else{
						logger.info('User not admin group');
						socket.emit('ADMUserLockRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('ADMUserLockRes', response.status);
		}
	},
	deletenode: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("by" in data && "id" in data) {
			connection.query('SELECT * FROM vncp_users WHERE id = ?', [data.by], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('ADMNodeDeleteRes', response.status);
				}else{
					if(results.length == 1 && 'group' in results[0] && results[0].group == 2 && results[0].id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE id = ?', [data.id], function(err, noder) {
							if(err) {
								logger.info(err);
								socket.emit('ADMNodeDeleteRes', response.status);
							}else{
								var nodename = noder[0].name;
								connection.query('SELECT * FROM vncp_kvm_ct WHERE node = ?', [nodename], function(err, kvmcheck) {
									if(err) {
										logger.info(err);
										socket.emit('ADMNodeDeleteRes', response.status);
									}else{
										if(kvmcheck.length > 0) {
											logger.info('Node in use. Cannot delete.');
											socket.emit('ADMNodeDeleteRes', response.status);
										}else{
											connection.query('SELECT * FROM vncp_lxc_ct WHERE node = ?', [nodename], function(err, lxccheck) {
												if(err) {
													logger.info(err);
													socket.emit('ADMNodeDeleteRes', response.status);
												}else{
													if(lxccheck.length > 0) {
														logger.info('Node in use. Cannot delete.');
														socket.emit('ADMNodeDeleteRes', response.status);
													}else{
														connection.query('DELETE FROM vncp_nodes WHERE id = ?', [data.id], function(err, noderdone) {
															if(err) {
																logger.info(err);
																socket.emit('ADMNodeDeleteRes', response.status);
															}else{
																connection.query('DELETE FROM vncp_nat WHERE node = ?', [nodename], function(err, noderdone2) {
																	if(err) {
																		logger.info(err);
																		socket.emit('ADMNodeDeleteRes', response.status);
																	}else{
																		response.status = 'ok';
																		socket.emit('ADMNodeDeleteRes', response.status);
																	}
																});
															}
														});
													}
												}
											});
										}
									}
								});
							}
						});
					}else{
						logger.info('User not admin group');
						socket.emit('ADMNodeDeleteRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('ADMNodeDeleteRes', response.status);
		}
	},
	deletetun: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("by" in data && "id" in data) {
			connection.query('SELECT * FROM vncp_users WHERE id = ?', [data.by], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('ADMTunDeleteRes', response.status);
				}else{
					if(results.length == 1 && 'group' in results[0] && results[0].group == 2 && results[0].id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_tuntap WHERE id = ?', [data.id], function(err, nodecheck) {
							if(err) {
								logger.info(err);
								socket.emit('ADMTunDeleteRes', response.status);
							}else{
								connection.query('SELECT * FROM vncp_nat WHERE node = ?', [nodecheck[0].node], function(err, natcheck) {
									if(err) {
										logger.info(err);
										socket.emit('ADMTunDeleteRes', response.status);
									}else{
										connection.query('DELETE FROM vncp_tuntap WHERE id = ?', [data.id], function(err, noderdone) {
											if(err) {
												logger.info(err);
												socket.emit('ADMTunDeleteRes', response.status);
											}else{
												if(natcheck.length > 0) {
													response.status = 'oknat';
													socket.emit('ADMTunDeleteRes', response.status);
												}else{
													response.status = 'ok';
													socket.emit('ADMTunDeleteRes', response.status);
												}
											}
										});
									}
								});
							}
						});
					}else{
						logger.info('User not admin group');
						socket.emit('ADMTunDeleteRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('ADMTunDeleteRes', response.status);
		}
	},
	deletedhcp: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("by" in data && "id" in data) {
			connection.query('SELECT * FROM vncp_users WHERE id = ?', [data.by], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('ADMDHCPDeleteRes', response.status);
				}else{
					if(results.length == 1 && 'group' in results[0] && results[0].group == 2 && results[0].id == connections[socket.id]) {
						connection.query('DELETE FROM vncp_dhcp_servers WHERE id = ?', [data.id], function(err, noderdone) {
							if(err) {
								logger.info(err);
								socket.emit('ADMDHCPDeleteRes', response.status);
							}else{
								response.status = 'ok';
								socket.emit('ADMDHCPDeleteRes', response.status);
							}
						});
					}else{
						logger.info('User not admin group');
						socket.emit('ADMDHCPDeleteRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('ADMDHCPDeleteRes', response.status);
		}
	},
	deleteuser: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("by" in data && "id" in data) {
			connection.query('SELECT * FROM vncp_users WHERE id = ?', [data.by], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('ADMUserDeleteRes', response.status);
				}else{
					if(results.length == 1 && 'group' in results[0] && results[0].group == 2 && results[0].id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_users WHERE id = ?', [data.id], function(err, noder) {
							if(err) {
								logger.info(err);
								socket.emit('ADMUserDeleteRes', response.status);
							}else{
								connection.query('SELECT * FROM vncp_kvm_ct WHERE user_id = ?', [data.id], function(err, kvmcheck) {
									if(err) {
										logger.info(err);
										socket.emit('ADMUserDeleteRes', response.status);
									}else{
										if(kvmcheck.length > 0) {
											logger.info('User has KVM assignment. Cannot delete.');
											socket.emit('ADMUserDeleteRes', response.status);
										}else{
											connection.query('SELECT * FROM vncp_lxc_ct WHERE user_id = ?', [data.id], function(err, lxccheck) {
												if(err) {
													logger.info(err);
													socket.emit('ADMUserDeleteRes', response.status);
												}else{
													if(lxccheck.length > 0) {
														logger.info('User has LXC assignment. Cannot delete.');
														socket.emit('ADMUserDeleteRes', response.status);
													}else{
														connection.query('SELECT `group` FROM `vncp_users` WHERE `group` = 2', [], function(err, admcheck) {
															if(err) {
																logger.info(err);
																socket.emit('ADMUserDeleteRes', response.status);
															}else{
																if(admcheck.length == 1 && noder[0].group == 2) {
																	logger.info('Cannot delete last ProxCP admin account.');
																	socket.emit('ADMUserDeleteRes', response.status);
																}else{
																	connection.query('DELETE FROM vncp_users WHERE id = ?', [data.id], function(err, noderdone) {
																		if(err) {
																			logger.info(err);
																			socket.emit('ADMUserDeleteRes', response.status);
																		}else{
																			response.status = 'ok';
																			socket.emit('ADMUserDeleteRes', response.status);
																		}
																	});
																}
															}
														});
													}
												}
											});
										}
									}
								});
							}
						});
					}else{
						logger.info('User not admin group');
						socket.emit('ADMUserDeleteRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('ADMUserDeleteRes', response.status);
		}
	},
	unlockuser: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("by" in data && "id" in data) {
			connection.query('SELECT * FROM vncp_users WHERE id = ?', [data.by], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('ADMUserUnlockRes', response.status);
				}else{
					if(results.length == 1 && 'group' in results[0] && results[0].group == 2 && results[0].id == connections[socket.id]) {
						connection.query('UPDATE vncp_users SET locked = 0 WHERE id = ?', [data.id], function(err, update) {
							if(err) {
								logger.info(err);
								socket.emit('ADMUserUnlockRes', response.status);
							}else{
								response.status = 'ok';
								socket.emit('ADMUserUnlockRes', response.status);
							}
						});
					}else{
						logger.info('User not admin group');
						socket.emit('ADMUserUnlockRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('ADMUserUnlockRes', response.status);
		}
	},
	changepw: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("by" in data && "id" in data) {
			connection.query('SELECT * FROM vncp_users WHERE id = ?', [data.by], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('ADMUserPWRes', response);
				}else{
					if(results.length == 1 && 'group' in results[0] && results[0].group == 2 && results[0].id == connections[socket.id]) {
						var newpw = randPW(12);
						exec(['php', process.cwd()+'/lib/admin_pw.php', newpw, data.id], function(err, out, code) {
							if(err) {
								logger.info(err);
								socket.emit('ADMUserPWRes', response);
							}else{
								response.status = 'ok';
								response.pw = newpw;
								socket.emit('ADMUserPWRes', response);
							}
						});
					}else{
						logger.info('User not admin group');
						socket.emit('ADMUserPWRes', response);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('ADMUserPWRes', response.status);
		}
	},
	querystorage: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("by" in data && "id" in data) {
			connection.query('SELECT * FROM vncp_users WHERE id = ?', [data.by], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('ADMQueryStorageRes', response);
				}else{
					if(results.length == 1 && 'group' in results[0] && results[0].group == 2 && results[0].id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [data.id], function(err, node) {
							if(err) {
								logger.info(err);
								socket.emit('ADMQueryStorageRes', response);
							}else{
								var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
								px.get('/nodes/'+data.id+'/storage', {'enabled':1}, function(err, data) {
									response.locs = [];
									for(var i = 0; i < data.length; i++) {
										response.locs.push(data[i].storage);
									}
									response.status = 'ok';
									socket.emit('ADMQueryStorageRes', response);
								});
							}
						});
					}else{
						logger.info('User not admin group');
						socket.emit('ADMQueryStorageRes', response);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('ADMQueryStorageRes', response.status);
		}
	},
	querydnsa: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("by" in data && "id" in data) {
			connection.query('SELECT * FROM vncp_users WHERE id = ?', [data.by], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('ADMQueryNATDNSRes', response);
				}else{
					if(results.length == 1 && 'group' in results[0] && results[0].group == 2 && results[0].id == connections[socket.id]) {
						var dns = require('dns');
						dns.resolve4(data.id, function(err, address) {
							if(err) {
								logger.info(err);
								socket.emit('ADMQueryNATDNSRes', response);
							}else{
								response.ipv4 = address[0];
								response.status = 'ok';
								socket.emit('ADMQueryNATDNSRes', response);
							}
						});
					}else{
						logger.info('User not admin group');
						socket.emit('ADMQueryNATDNSRes', response);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('ADMQueryNATDNSRes', response.status);
		}
	},
	deletelxc: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("by" in data && "id" in data) {
			connection.query('SELECT * FROM vncp_users WHERE id = ?', [data.by], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('ADMLXCDeleteRes', response.status);
				}else{
					if(results.length == 1 && 'group' in results[0] && results[0].group == 2 && results[0].id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_lxc_ct WHERE hb_account_id = ?', [data.id], function(err, lxcconfig) {
							if(err) {
								logger.info(err);
								socket.emit('ADMLXCDeleteRes', response.status);
							}else{
								connection.query('DELETE FROM vncp_dhcp WHERE ip = ?', [lxcconfig[0].ip], function(err, deldhcp) {
									if(err) {
										logger.info(err);
										socket.emit('ADMLXCDeleteRes', response.status);
									}else{
										connection.query('DELETE FROM vncp_lxc_ct WHERE hb_account_id = ?', [data.id], function(err, update) {
											if(err) {
												logger.info(err);
												socket.emit('ADMLXCDeleteRes', response.status);
											}else{
												connection.query('DELETE FROM vncp_ct_backups WHERE hb_account_id = ?', [data.id], function(err, deletect) {
													if(err) {
														logger.info(err);
														socket.emit('ADMLXCDeleteRes', response.status);
													}else{
														connection.query('DELETE FROM vncp_bandwidth_monitor WHERE hb_account_id = ?', [data.id], function(err, deletebw) {
															if(err) {
																logger.info(err);
																socket.emit('ADMLXCDeleteRes', response.status);
															}else{
																connection.query('UPDATE vncp_ipv4_pool SET user_id=0, hb_account_id=0, available=1 WHERE hb_account_id = ?', [data.id], function(err, updatepool) {
																	if(err) {
																		logger.info(err);
																		socket.emit('ADMLXCDeleteRes', response.status);
																	}else{
																		connection.query('DELETE FROM vncp_ipv6_assignment WHERE hb_account_id = ?', [data.id], function(err, deletev6) {
																			if(err) {
																				logger.info(err);
																				socket.emit('ADMLXCDeleteRes', response.status);
																			}else{
																				connection.query('UPDATE vncp_private_pool SET user_id=0, hb_account_id=0, available=1 WHERE hb_account_id = ?', [data.id], function(err, updatepriv) {
																					if(err) {
																						logger.info(err);
																						socket.emit('ADMLXCDeleteRes', response.status);
																					}else{
																						connection.query('DELETE FROM vncp_secondary_ips WHERE hb_account_id = ?', [data.id], function(err, deleteip2) {
																							if(err) {
																								logger.info(err);
																								socket.emit('ADMLXCDeleteRes', response.status);
																							}else{
																								connection.query('SELECT * FROM vncp_natforwarding JOIN vncp_nat WHERE vncp_natforwarding.hb_account_id = ? AND vncp_nat.node = ?', [data.id, lxcconfig[0].node], function(err, natentries) {
																									if(err) {
																										logger.info(err);
																										socket.emit('ADMLXCDeleteRes', response.status);
																									}else{
																										if(natentries.length == 1) {
																											var domain_array = natentries[0].domains.split(";");
																											var domain_str = '';
																											for(var i = 0; i < domain_array.length - 1; i++) {
																												domain_str += 'rm /etc/nginx/conf.d/'+data.id+'-'+domain_array[i]+'-*.conf && rm /etc/nginx/proxcp-nat-ssl/cert-'+data.id+'-'+domain_array[i]+'-*.pem && rm /etc/nginx/proxcp-nat-ssl/key-'+data.id+'-'+domain_array[i]+'-*.pem && ';
																											}
																											domain_str += 'service nginx restart';
																											var port_array = natentries[0].ports.split(";");
																											var port_str = '';
																											for(var i = 0; i < port_array.length - 1; i++) {
																												var tport = port_array[i].split(":");
																												port_str += 'iptables -t nat -D PREROUTING -p tcp -d '+natentries[0].publicip+' --dport '+tport[1]+' -i vmbr0 -j DNAT --to-destination '+lxcconfig[0].ip+':'+tport[2]+' && ';
																											}
																											port_str += 'iptables-save > /root/proxcp-iptables.rules';
																											connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [lxcconfig[0].node], function(err, node) {
																												if(err) {
																													logger.info(err);
																													socket.emit('ADMLXCDeleteRes', response.status);
																												}else{
																													connection.query('SELECT * FROM vncp_tuntap WHERE node = ?', [lxcconfig[0].node], function(err, rcreds) {
																														if(err) {
																															logger.info(err);
																															socket.emit('ADMLXCDeleteRes', response.status);
																														}else{
																															if(rcreds.length > 0) {
																																var ssh = new SSH({
																																	user: 'root',
																																	host: node[0].hostname,
																																	pass: mc.decrypt(rcreds[0].password),
																																	port: rcreds[0].port
																																});
																																ssh.exec(domain_str).exec(port_str).start({
																																	success: function() {
																																		connection.query('DELETE FROM vncp_natforwarding WHERE hb_account_id = ?', [data.id], function(err, deletenat) {
																																			if(err) {
																																				logger.info(err);
																																				socket.emit('ADMLXCDeleteRes', response.status);
																																			}else{
																																				response.status = 'ok';
																																				socket.emit('ADMLXCDeleteRes', response.status);
																																			}
																																		});
																																	},
																																	fail: function(err) {
																																		logger.info(err);
																																		socket.emit('ADMLXCDeleteRes', response.status);
																																	}
																																});
																															}else{
																																logger.info('No TUN/TAP credentials exist for this node.');
																																socket.emit('ADMLXCDeleteRes', response.status);
																															}
																														}
																													});
																												}
																											});
																										}else{
																											response.status = 'ok';
																											socket.emit('ADMLXCDeleteRes', response.status);
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
					}else{
						logger.info('User not admin group');
						socket.emit('ADMLXCDeleteRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('ADMLXCDeleteRes', response.status);
		}
	},
	querynode: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("by" in data && "id" in data) {
			connection.query('SELECT * FROM vncp_users WHERE id = ?', [data.by], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('ADMQueryNodesRes', response);
				}else{
					if(results.length == 1 && 'group' in results[0] && results[0].group == 2 && results[0].id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [data.id], function(err, node) {
							if(err) {
								logger.info(err);
								socket.emit('ADMQueryNodesRes', response);
							}else{
								var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
								px.get('/nodes/'+data.id+'/status', {}, function(err, data) {
									if(data) {
										response.uptime = data.uptime;
										response.loadavg = data.loadavg;
										response.kernel = data.kversion;
										response.pve = data.pveversion;
										response.cpumod = data.cpuinfo.model;
										response.cpuusage = data.cpu;
										response.ramusage = data.memory;
										response.diskusage = data.rootfs;
										response.swapusage = data.swap;
										response.status = 'ok';
										socket.emit('ADMQueryNodesRes', response);
									}else{
										logger.info('Proxmox returned empty response');
										socket.emit('ADMQueryNodesRes', response);
									}
								});
							}
						});
					}else{
						logger.info('User not admin group');
						socket.emit('ADMQueryNodesRes', response);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('ADMQueryNodesRes', response.status);
		}
	},
	lxcsuspend: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("by" in data && "id" in data) {
			connection.query('SELECT * FROM vncp_users WHERE id = ?', [data.by], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('ADMLXCSuspendRes', response.status);
				}else{
					if(results.length == 1 && 'group' in results[0] && results[0].group == 2 && results[0].id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_lxc_ct WHERE hb_account_id = ?', [data.id], function(err, getnode) {
							if(err) {
								logger.info(err);
								socket.emit('ADMLXCSuspendRes', response.status);
							}else{
								connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [getnode[0].node], function(err, node) {
									if(err) {
										logger.info(err);
										socket.emit('ADMLXCSuspendRes', response.status);
									}else{
										var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
										px.get('/pools/'+getnode[0].pool_id, {}, function(err, getdata) {
											var vmid = getdata['members'][0]['vmid'];
											px.post('/nodes/'+getnode[0].node+'/lxc/'+vmid+'/status/stop', {}, function(err, proc) {
												connection.query('UPDATE vncp_lxc_ct SET suspended=1 WHERE hb_account_id = ?', [data.id], function(err, update) {
													if(err) {
														logger.info(err);
														socket.emit('ADMLXCSuspendRes', response.status);
													}else{
														response.status = 'ok';
														socket.emit('ADMLXCSuspendRes', response.status);
													}
												});
											});
										});
									}
								});
							}
						});
					}else{
						logger.info('User not admin group');
						socket.emit('ADMLXCSuspendRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('ADMLXCSuspendRes', response.status);
		}
	},
	lxcunsuspend: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("by" in data && "id" in data) {
			connection.query('SELECT * FROM vncp_users WHERE id = ?', [data.by], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('ADMLXCUnsuspendRes', response.status);
				}else{
					if(results.length == 1 && 'group' in results[0] && results[0].group == 2 && results[0].id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_lxc_ct WHERE hb_account_id = ?', [data.id], function(err, getnode) {
							if(err) {
								logger.info(err);
								socket.emit('ADMLXCUnsuspendRes', response.status);
							}else{
								connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [getnode[0].node], function(err, node) {
									if(err) {
										logger.info(err);
										socket.emit('ADMLXCUnsuspendRes', response.status);
									}else{
										var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
										px.get('/pools/'+getnode[0].pool_id, {}, function(err, getdata) {
											var vmid = getdata['members'][0]['vmid'];
											px.post('/nodes/'+getnode[0].node+'/lxc/'+vmid+'/status/start', {}, function(err, proc) {
												connection.query('UPDATE vncp_lxc_ct SET suspended=0 WHERE hb_account_id = ?', [data.id], function(err, update) {
													if(err) {
														logger.info(err);
														socket.emit('ADMLXCUnsuspendRes', response.status);
													}else{
														response.status = 'ok';
														socket.emit('ADMLXCUnsuspendRes', response.status);
													}
												});
											});
										});
									}
								});
							}
						});
					}else{
						logger.info('User not admin group');
						socket.emit('ADMLXCUnsuspendRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('ADMLXCUnsuspendRes', response.status);
		}
	},
	deletekvm: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("by" in data && "id" in data) {
			connection.query('SELECT * FROM vncp_users WHERE id = ?', [data.by], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('ADMKVMDeleteRes', response.status);
				}else{
					if(results.length == 1 && 'group' in results[0] && results[0].group == 2 && results[0].id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_kvm_ct WHERE hb_account_id = ?', [data.id], function(err, kvmconfig) {
							if(err) {
								logger.info(err);
								socket.emit('ADMKVMDeleteRes', response.status);
							}else{
								connection.query('SELECT * FROM vncp_dhcp WHERE ip = ?', [kvmconfig[0].ip], function(err, dhcprecord) {
									if(err) {
										logger.info(err);
										socket.emit('ADMKVMDeleteRes', response.status);
									}else{
										connection.query('DELETE FROM vncp_dhcp WHERE ip = ?', [kvmconfig[0].ip], function(err, deletedhcp) {
											if(err) {
												logger.info(err);
												socket.emit('ADMKVMDeleteRes', response.status);
											}else{
												connection.query('DELETE FROM vncp_kvm_ct WHERE hb_account_id = ?', [data.id], function(err, update) {
													if(err) {
														logger.info(err);
														socket.emit('ADMKVMDeleteRes', response.status);
													}else{
														connection.query('DELETE FROM vncp_bandwidth_monitor WHERE hb_account_id = ?', [data.id], function(err, deletebw) {
															if(err) {
																logger.info(err);
																socket.emit('ADMKVMDeleteRes', response.status);
															}else{
																connection.query('DELETE FROM vncp_ct_backups WHERE hb_account_id = ?', [data.id], function(err, deletect) {
																	if(err) {
																		logger.info(err);
																		socket.emit('ADMKVMDeleteRes', response.status);
																	}else{
																		connection.query('UPDATE vncp_ipv4_pool SET user_id=0, hb_account_id=0, available=1 WHERE hb_account_id = ?', [data.id], function(err, updatepool) {
																			if(err) {
																				logger.info(err);
																				socket.emit('ADMKVMDeleteRes', response.status);
																			}else{
																				connection.query('DELETE FROM vncp_ipv6_assignment WHERE hb_account_id = ?', [data.id], function(err, deletev6) {
																					if(err) {
																						logger.info(err);
																						socket.emit('ADMKVMDeleteRes', response.status);
																					}else{
																						connection.query('UPDATE vncp_private_pool SET user_id=0, hb_account_id=0, available=1 WHERE hb_account_id = ?', [data.id], function(err, updatepriv) {
																							if(err) {
																								logger.info(err);
																								socket.emit('ADMKVMDeleteRes', response.status);
																							}else{
																								connection.query('DELETE FROM vncp_secondary_ips WHERE hb_account_id = ?', [data.id], function(err, deleteip2) {
																									if(err) {
																										logger.info(err);
																										socket.emit('ADMKVMDeleteRes', response.status);
																									}else{
																										connection.query('SELECT * FROM vncp_natforwarding JOIN vncp_nat WHERE vncp_natforwarding.hb_account_id = ? AND vncp_nat.node = ?', [data.id, kvmconfig[0].node], function(err, natentries) {
																											if(err) {
																												logger.info(err);
																												socket.emit('ADMKVMDeleteRes', response.status);
																											}else{
																												if(natentries.length == 1) {
																													var domain_array = natentries[0].domains.split(";");
																													var domain_str = '';
																													for(var i = 0; i < domain_array.length - 1; i++) {
																														domain_str += 'rm /etc/nginx/conf.d/'+data.id+'-'+domain_array[i]+'-*.conf && rm /etc/nginx/proxcp-nat-ssl/cert-'+data.id+'-'+domain_array[i]+'-*.pem && rm /etc/nginx/proxcp-nat-ssl/key-'+data.id+'-'+domain_array[i]+'-*.pem && ';
																													}
																													domain_str += 'service nginx restart';
																													var port_array = natentries[0].ports.split(";");
																													var port_str = '';
																													for(var i = 0; i < port_array.length - 1; i++) {
																														var tport = port_array[i].split(":");
																														port_str += 'iptables -t nat -D PREROUTING -p tcp -d '+natentries[0].publicip+' --dport '+tport[1]+' -i vmbr0 -j DNAT --to-destination '+kvmconfig[0].ip+':'+tport[2]+' && ';
																													}
																													port_str += 'iptables-save > /root/proxcp-iptables.rules';
																													connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [kvmconfig[0].node], function(err, node) {
																														if(err) {
																															logger.info(err);
																															socket.emit('ADMKVMDeleteRes', response.status);
																														}else{
																															connection.query('SELECT * FROM vncp_tuntap WHERE node = ?', [kvmconfig[0].node], function(err, rcreds) {
																																if(err) {
																																	logger.info(err);
																																	socket.emit('ADMKVMDeleteRes', response.status);
																																}else{
																																	if(rcreds.length > 0) {
																																		var ssh = new SSH({
																																			user: 'root',
																																			host: node[0].hostname,
																																			pass: mc.decrypt(rcreds[0].password),
																																			port: rcreds[0].port
																																		});
																																		ssh.exec(domain_str).exec(port_str).start({
																																			success: function() {
																																				connection.query('DELETE FROM vncp_natforwarding WHERE hb_account_id = ?', [data.id], function(err, deletenat) {
																																					if(err) {
																																						connection.query('SELECT * FROM vncp_dhcp_servers WHERE dhcp_network = ? LIMIT 1', [dhcprecord[0].network], function(err, dhcpserver) {
																																							if(err) {
																																								logger.info(err);
																																								socket.emit('ADMKVMDeleteRes', response.status);
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
																																											socket.emit('ADMKVMDeleteRes', response.status);
																																										}else{
																																											for(var i = 0; i < fulldhcp.length; i++) {
																																												ssh.exec("printf 'host "+fulldhcp[i].id+" {hardware ethernet "+fulldhcp[i].mac_address+";fixed-address "+fulldhcp[i].ip+";option routers "+fulldhcp[i].gateway+";}\n' >> /root/dhcpd.test");
																																											}
																																											ssh.exec("mv /root/dhcpd.test /etc/dhcp/dhcpd.conf && rm /root/dhcpd.test").exec("service isc-dhcp-server restart").start({
																																												success: function() {
																																													response.status = 'ok';
																																													socket.emit('ADMKVMDeleteRes', response.status);
																																												},
																																												fail: function(err) {
																																													logger.info(err);
																																													socket.emit('ADMKVMDeleteRes', response.status);
																																												}
																																											});
																																										}
																																									});
																																								}else{
																																									response.status = 'ok';
																																									socket.emit('ADMKVMDeleteRes', response.status);
																																								}
																																							}
																																						});
																																					}else{
																																						response.status = 'ok';
																																						socket.emit('ADMKVMDeleteRes', response.status);
																																					}
																																				});
																																			},
																																			fail: function(err) {
																																				logger.info(err);
																																				socket.emit('ADMKVMDeleteRes', response.status);
																																			}
																																		});
																																	}else{
																																		logger.info('No TUN/TAP credentials exist for this node.');
																																		socket.emit('ADMKVMDeleteRes', response.status);
																																	}
																																}
																															});
																														}
																													});
																												}else{
																													response.status = 'ok';
																													socket.emit('ADMKVMDeleteRes', response.status);
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
							}
						});
					}else{
						logger.info('User not admin group');
						socket.emit('ADMKVMDeleteRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('ADMKVMDeleteRes', response.status);
		}
	},
	kvmsuspend: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("by" in data && "id" in data) {
			connection.query('SELECT * FROM vncp_users WHERE id = ?', [data.by], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('ADMKVMSuspendRes', response.status);
				}else{
					if(results.length == 1 && 'group' in results[0] && results[0].group == 2 && results[0].id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_kvm_ct WHERE hb_account_id = ?', [data.id], function(err, getnode) {
							if(err) {
								logger.info(err);
								socket.emit('ADMKVMSuspendRes', response.status);
							}else{
								connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [getnode[0].node], function(err, node) {
									if(err) {
										logger.info(err);
										socket.emit('ADMKVMSuspendRes', response.status);
									}else{
										var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
										px.get('/pools/'+getnode[0].pool_id, {}, function(err, getdata) {
											var vmid = getdata['members'][0]['vmid'];
											px.post('/nodes/'+getnode[0].node+'/qemu/'+vmid+'/status/stop', {}, function(err, proc) {
												connection.query('UPDATE vncp_kvm_ct SET suspended=1 WHERE hb_account_id = ?', [data.id], function(err, update) {
													if(err) {
														logger.info(err);
														socket.emit('ADMKVMSuspendRes', response.status);
													}else{
														response.status = 'ok';
														socket.emit('ADMKVMSuspendRes', response.status);
													}
												});
											});
										});
									}
								});
							}
						});
					}else{
						logger.info('User not admin group');
						socket.emit('ADMKVMSuspendRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('ADMKVMSuspendRes', response.status);
		}
	},
	kvmunsuspend: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("by" in data && "id" in data) {
			connection.query('SELECT * FROM vncp_users WHERE id = ?', [data.by], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('ADMKVMUnsuspendRes', response.status);
				}else{
					if(results.length == 1 && 'group' in results[0] && results[0].group == 2 && results[0].id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_kvm_ct WHERE hb_account_id = ?', [data.id], function(err, getnode) {
							if(err) {
								logger.info(err);
								socket.emit('ADMKVMUnsuspendRes', response.status);
							}else{
								connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [getnode[0].node], function(err, node) {
									if(err) {
										logger.info(err);
										socket.emit('ADMKVMUnsuspendRes', response.status);
									}else{
										var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
										px.get('/pools/'+getnode[0].pool_id, {}, function(err, getdata) {
											var vmid = getdata['members'][0]['vmid'];
											px.post('/nodes/'+getnode[0].node+'/qemu/'+vmid+'/status/start', {}, function(err, proc) {
												connection.query('UPDATE vncp_kvm_ct SET suspended=0 WHERE hb_account_id = ?', [data.id], function(err, update) {
													if(err) {
														logger.info(err);
														socket.emit('ADMKVMUnsuspendRes', response.status);
													}else{
														response.status = 'ok';
														socket.emit('ADMKVMUnsuspendRes', response.status);
													}
												});
											});
										});
									}
								});
							}
						});
					}else{
						logger.info('User not admin group');
						socket.emit('ADMKVMUnsuspendRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('ADMKVMUnsuspendRes', response.status);
		}
	},
	deletelxctemp: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("by" in data && "id" in data) {
			connection.query('SELECT * FROM vncp_users WHERE id = ?', [data.by], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('ADMLXCTempDeleteRes', response.status);
				}else{
					if(results.length == 1 && 'group' in results[0] && results[0].group == 2 && results[0].id == connections[socket.id]) {
						connection.query('DELETE FROM vncp_lxc_templates WHERE id = ?', [data.id], function(err, update) {
							if(err) {
								logger.info(err);
								socket.emit('ADMLXCTempDeleteRes', response.status);
							}else{
								response.status = 'ok';
								socket.emit('ADMLXCTempDeleteRes', response.status);
							}
						});
					}else{
						logger.info('User not admin group');
						socket.emit('ADMLXCTempDeleteRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('ADMLXCTempDeleteRes', response.status);
		}
	},
	deleteapi: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("by" in data && "id" in data) {
			connection.query('SELECT * FROM vncp_users WHERE id = ?', [data.by], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('ADMAPIDeleteRes', response.status);
				}else{
					if(results.length == 1 && 'group' in results[0] && results[0].group == 2 && results[0].id == connections[socket.id]) {
						connection.query('DELETE FROM vncp_api WHERE id = ?', [data.id], function(err, update) {
							if(err) {
								logger.info(err);
								socket.emit('ADMAPIDeleteRes', response.status);
							}else{
								response.status = 'ok';
								socket.emit('ADMAPIDeleteRes', response.status);
							}
						});
					}else{
						logger.info('User not admin group');
						socket.emit('ADMAPIDeleteRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('ADMAPIDeleteRes', response.status);
		}
	},
	deletekvmtemp: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("by" in data && "id" in data) {
			connection.query('SELECT * FROM vncp_users WHERE id = ?', [data.by], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('ADMKVMTempDeleteRes', response.status);
				}else{
					if(results.length == 1 && 'group' in results[0] && results[0].group == 2 && results[0].id == connections[socket.id]) {
						connection.query('DELETE FROM vncp_kvm_templates WHERE id = ?', [data.id], function(err, update) {
							if(err) {
								logger.info(err);
								socket.emit('ADMKVMTempDeleteRes', response.status);
							}else{
								response.status = 'ok';
								socket.emit('ADMKVMTempDeleteRes', response.status);
							}
						});
					}else{
						logger.info('User not admin group');
						socket.emit('ADMKVMTempDeleteRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('ADMKVMTempDeleteRes', response.status);
		}
	},
	deletekvmiso: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("by" in data && "id" in data) {
			connection.query('SELECT * FROM vncp_users WHERE id = ?', [data.by], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('ADMKVMISODeleteRes', response.status);
				}else{
					if(results.length == 1 && 'group' in results[0] && results[0].group == 2 && results[0].id == connections[socket.id]) {
						connection.query('DELETE FROM vncp_kvm_isos WHERE id = ?', [data.id], function(err, update) {
							if(err) {
								logger.info(err);
								socket.emit('ADMKVMISODeleteRes', response.status);
							}else{
								response.status = 'ok';
								socket.emit('ADMKVMISODeleteRes', response.status);
							}
						});
					}else{
						logger.info('User not admin group');
						socket.emit('ADMKVMISODeleteRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('ADMKVMISODeleteRes', response.status);
		}
	},
	deleteacl: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("by" in data && "id" in data) {
			connection.query('SELECT * FROM vncp_users WHERE id = ?', [data.by], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('ADMACLDeleteRes', response.status);
				}else{
					if(results.length == 1 && 'group' in results[0] && results[0].group == 2 && results[0].id == connections[socket.id]) {
						connection.query('DELETE FROM vncp_acl WHERE id = ?', [data.id], function(err, update) {
							if(err) {
								logger.info(err);
								socket.emit('ADMACLDeleteRes', response.status);
							}else{
								response.status = 'ok';
								socket.emit('ADMACLDeleteRes', response.status);
							}
						});
					}else{
						logger.info('User not admin group');
						socket.emit('ADMACLDeleteRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('ADMACLDeleteRes', response.status);
		}
	},
	querycloud: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("by" in data && "id" in data) {
			connection.query('SELECT * FROM vncp_users WHERE id = ?', [data.by], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('ADMQueryCloudRes', response);
				}else{
					if(results.length == 1 && 'group' in results[0] && results[0].group == 2 && results[0].id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_kvm_cloud WHERE hb_account_id = ?', [data.id], function(err, node) {
							if(err) {
								logger.info(err);
								socket.emit('ADMQueryCloudRes', response);
							}else{
								response.ipv4 = node[0].ipv4;
								response.ipv4_avail = node[0].avail_ipv4;
								response.cpucores = node[0].cpu_cores;
								response.cpucores_avail = node[0].avail_cpu_cores;
								response.ram = node[0].memory;
								response.ram_avail = node[0].avail_memory;
								response.storage_size = node[0].disk_size;
								response.storage_size_avail = node[0].avail_disk_size;
								response.status = 'ok';
								socket.emit('ADMQueryCloudRes', response);
							}
						});
					}else{
						logger.info('User not admin group');
						socket.emit('ADMQueryCloudRes', response);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('ADMQueryCloudRes', response.status);
		}
	},
	editcloud: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("by" in data && "id" in data && "ipv4" in data && "ipv4_avail" in data && "cpucores" in data && "cpucores_avail" in data && "ram" in data && "ram_avail" in data && "storage_size" in data && "storage_size_avail" in data) {
			connection.query('SELECT * FROM vncp_users WHERE id = ?', [data.by], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('ADMEditCloudRes', response.status);
				}else{
					if(results.length == 1 && 'group' in results[0] && results[0].group == 2 && results[0].id == connections[socket.id]) {
						if(data.ipv4 == '' || data.ipv4_avail == '' || data.cpucores == '' || data.cpucores_avail == '' || data.ram == '' || data.ram_avail == '' || data.storage_size == '' || data.storage_size_avail == '' || data.id == '') {
							logger.info('All fields are required.');
							socket.emit('ADMEditCloudRes', response.status);
						}else{
							var payload = [
								data.ipv4,
								data.ipv4_avail,
								parseInt(data.cpucores),
								parseInt(data.cpucores_avail),
								parseInt(data.ram),
								parseInt(data.ram_avail),
								parseInt(data.storage_size),
								parseInt(data.storage_size_avail),
								data.ipv4.split(';').length,
								data.ipv4_avail.split(';').length,
								parseInt(data.id)
							];
							connection.query('UPDATE vncp_kvm_cloud SET ipv4 = ?, avail_ipv4 = ?, cpu_cores = ?, avail_cpu_cores = ?, memory = ?, avail_memory = ?, disk_size = ?, avail_disk_size = ?, ip_limit = ?, avail_ip_limit = ? WHERE hb_account_id = ?', payload, function(err, update) {
								if(err) {
									logger.info(err);
									socket.emit('ADMEditCloudRes', response.status);
								}else{
									response.status = 'ok';
									socket.emit('ADMEditCloudRes', response.status);
								}
							});
						}
					}else{
						logger.info('User not admin group');
						socket.emit('ADMEditCloudRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('ADMEditCloudRes', response.status);
		}
	},
	cloudsuspend: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("by" in data && "id" in data) {
			connection.query('SELECT * FROM vncp_users WHERE id = ?', [data.by], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('ADMCloudSuspendRes', response.status);
				}else{
					if(results.length == 1 && 'group' in results[0] && results[0].group == 2 && results[0].id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_kvm_ct WHERE cloud_account_id = ?', [data.id], function(err, getnode) {
							if(err) {
								logger.info(err);
								socket.emit('ADMCloudSuspendRes', response.status);
							}else{
								if(getnode.length == 0) {
									connection.query('UPDATE vncp_kvm_cloud SET suspended=1 WHERE hb_account_id = ?', [data.id], function(err, updatealso) {
										if(err) {
											logger.info(err);
											socket.emit('ADMCloudSuspendRes', response.status);
										}else{
											response.status = 'ok';
											socket.emit('ADMCloudSuspendRes', response.status);
										}
									});
								}else{
									connection.query('SELECT * FROM vncp_nodes WHERE name = ?', [getnode[0].node], function(err, node) {
										if(err) {
											logger.info(err);
											socket.emit('ADMCloudSuspendRes', response.status);
										}else{
											var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
											px.get('/pools/'+getnode[0].pool_id, {}, function(err, getdata) {
												for(var j = 0; j < getdata['members'].length; j++) {
													var vmid = getdata['members'][j]['vmid'];
													px.post('/nodes/'+getnode[0].node+'/qemu/'+vmid+'/status/stop', {}, function(err, proc) {});
												}
												connection.query('UPDATE vncp_kvm_ct SET suspended=1 WHERE cloud_account_id = ?', [data.id], function(err, update) {
													if(err) {
														logger.info(err);
														socket.emit('ADMCloudSuspendRes', response.status);
													}else{
														connection.query('UPDATE vncp_kvm_cloud SET suspended=1 WHERE hb_account_id = ?', [data.id], function(err, updatealso) {
															if(err) {
																logger.info(err);
																socket.emit('ADMCloudSuspendRes', response.status);
															}else{
																response.status = 'ok';
																socket.emit('ADMCloudSuspendRes', response.status);
															}
														});
													}
												});
											});
										}
									});
								}
							}
						});
					}else{
						logger.info('User not admin group');
						socket.emit('ADMCloudSuspendRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('ADMCloudSuspendRes', response.status);
		}
	},
	cloudunsuspend: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("by" in data && "id" in data) {
			connection.query('SELECT * FROM vncp_users WHERE id = ?', [data.by], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('ADMCloudUnsuspendRes', response.status);
				}else{
					if(results.length == 1 && 'group' in results[0] && results[0].group == 2 && results[0].id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_kvm_ct WHERE cloud_account_id = ?', [data.id], function(err, getnode) {
							if(err) {
								logger.info(err);
								socket.emit('ADMCloudUnsuspendRes', response.status);
							}else{
								if(getnode.length == 0) {
									connection.query('UPDATE vncp_kvm_cloud SET suspended=0 WHERE hb_account_id = ?', [data.id], function(err, updatealso) {
										if(err) {
											logger.info(err);
											socket.emit('ADMCloudUnsuspendRes', response.status);
										}else{
											response.status = 'ok';
											socket.emit('ADMCloudUnsuspendRes', response.status);
										}
									});
								}else{
									connection.query('UPDATE vncp_kvm_ct SET suspended=0 WHERE cloud_account_id = ?', [data.id], function(err, update) {
										if(err) {
											logger.info(err);
											socket.emit('ADMCloudUnsuspendRes', response.status);
										}else{
											connection.query('UPDATE vncp_kvm_cloud SET suspended=0 WHERE hb_account_id = ?', [data.id], function(err, updatealso) {
												if(err) {
													logger.info(err);
													socket.emit('ADMCloudUnsuspendRes', response.status);
												}else{
													response.status = 'ok';
													socket.emit('ADMCloudUnsuspendRes', response.status);
												}
											});
										}
									});
								}
							}
						});
					}else{
						logger.info('User not admin group');
						socket.emit('ADMCloudUnsuspendRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('ADMCloudUnsuspendRes', response.status);
		}
	},
	clouddelete: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("by" in data && "id" in data) {
			connection.query('SELECT * FROM vncp_users WHERE id = ?', [data.by], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('ADMCloudDeleteRes', response.status);
				}else{
					if(results.length == 1 && 'group' in results[0] && results[0].group == 2 && results[0].id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_kvm_cloud WHERE hb_account_id = ?', [data.id], function(err, scloud) {
							if(err) {
								logger.info(err);
								socket.emit('ADMCloudDeleteRes', response.status);
							}else{
								connection.query('DELETE FROM vncp_kvm_ct WHERE cloud_account_id = ?', [data.id], function(err, update) {
									if(err) {
										logger.info(err);
										socket.emit('ADMCloudDeleteRes', response.status);
									}else{
										connection.query('DELETE FROM vncp_ct_backups WHERE hb_account_id = ?', [data.id], function(err, deletect) {
											if(err) {
												logger.info(err);
												socket.emit('ADMCloudDeleteRes', response.status);
											}else{
												connection.query('DELETE FROM vncp_kvm_cloud WHERE hb_account_id = ?', [data.id], function(err, updatealso) {
													if(err) {
														logger.info(err);
														socket.emit('ADMCloudDeleteRes', response.status);
													}else{
														var ips = scloud[0].ipv4;
														ips = ips.split(";");
														for(var i = 0; i < ips.length; i++) {
															connection.query('DELETE FROM vncp_dhcp WHERE ip = ?', [ips[i]], function(err, deldhcp) {});
														}
														response.status = 'ok';
														socket.emit('ADMCloudDeleteRes', response.status);
													}
												});
											}
										});
									}
								});
							}
						});
					}else{
						logger.info('User not admin group');
						socket.emit('ADMCloudDeleteRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('ADMCloudDeleteRes', response.status);
		}
	},
	queryvmprops: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("by" in data && "id" in data) {
			connection.query('SELECT * FROM vncp_users WHERE id = ?', [data.by], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('ADMQueryPropsRes', response);
				}else{
					if(results.length == 1 && 'group' in results[0] && results[0].group == 2 && results[0].id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_lxc_ct WHERE hb_account_id = ?', [data.id], function(err, lxcprops) {
							if(err) {
								logger.info(err);
								socket.emit('ADMQueryPropsRes', response);
							}else{
								if(lxcprops.length == 0) {
									connection.query('SELECT * FROM vncp_kvm_ct WHERE hb_account_id = ?', [data.id], function(err, kvmprops) {
										if(err) {
											logger.info(err);
											socket.emit('ADMQueryPropsRes', response);
										}else{
											response.userid = kvmprops[0].user_id;
											response.vmnode = kvmprops[0].node;
											response.vmos = kvmprops[0].os;
											response.vmip = kvmprops[0].ip;
											response.backups = kvmprops[0].allow_backups;
											response.poolname = kvmprops[0].pool_id;
											connection.query('SELECT * FROM vncp_dhcp WHERE ip = ?', [response.vmip], function(err, dhcptable) {
												if(err) {
													logger.info(err);
													socket.emit('ADMQueryPropsRes', response);
												}else{
													response.vmip_gateway = dhcptable[0].gateway;
													response.vmip_netmask = dhcptable[0].netmask;
													connection.query('SELECT * FROM vncp_ct_backups WHERE hb_account_id = ?', [data.id], function(err, backuplimit) {
														if(err) {
															logger.info(err);
															socket.emit('ADMQueryPropsRes', response);
														}else{
															response.override = backuplimit[0].backuplimit;
															response.status = 'ok';
															socket.emit('ADMQueryPropsRes', response);
														}
													});
												}
											});
										}
									});
								}else{
									response.userid = lxcprops[0].user_id;
									response.vmnode = lxcprops[0].node;
									response.vmos = lxcprops[0].os;
									response.vmip = lxcprops[0].ip;
									response.backups = lxcprops[0].allow_backups;
									response.poolname = lxcprops[0].pool_id;
									connection.query('SELECT * FROM vncp_dhcp WHERE ip = ?', [response.vmip], function(err, dhcptable) {
										if(err) {
											logger.info(err);
											socket.emit('ADMQueryPropsRes', response);
										}else{
											response.vmip_gateway = dhcptable[0].gateway;
											response.vmip_netmask = dhcptable[0].netmask;
											connection.query('SELECT * FROM vncp_ct_backups WHERE hb_account_id = ?', [data.id], function(err, backuplimit) {
												if(err) {
													logger.info(err);
													socket.emit('ADMQueryPropsRes', response);
												}else{
													response.override = backuplimit[0].backuplimit;
													response.status = 'ok';
													socket.emit('ADMQueryPropsRes', response);
												}
											});
										}
									});
								}
							}
						});
					}else{
						logger.info('User not admin group');
						socket.emit('ADMQueryPropsRes', response);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('ADMQueryPropsRes', response);
		}
	},
	deletednsrecord: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("by" in data && "id" in data) {
			connection.query('SELECT * FROM vncp_users WHERE id = ?', [data.by], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('ADMRecordDeleteRes', response.status);
				}else{
					if(results.length == 1 && 'group' in results[0] && results[0].group == 2 && results[0].id == connections[socket.id]) {
						connection.query('DELETE FROM vncp_forward_dns_record WHERE id = ?', [data.id], function(err, update) {
							if(err) {
								logger.info(err);
								socket.emit('ADMRecordDeleteRes', response.status);
							}else{
								response.status = 'ok';
								socket.emit('ADMRecordDeleteRes', response.status);
							}
						});
					}else{
						logger.info('User not admin group');
						socket.emit('ADMRecordDeleteRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('ADMRecordDeleteRes', response.status);
		}
	},
	deletedomain: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("by" in data && "id" in data) {
			connection.query('SELECT * FROM vncp_users WHERE id = ?', [data.by], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('ADMDomainDeleteRes', response.status);
				}else{
					if(results.length == 1 && 'group' in results[0] && results[0].group == 2 && results[0].id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_forward_dns_domain WHERE id = ?', [data.id], function(err, getdomain) {
							if(err) {
								logger.info(err);
								socket.emit('ADMDomainDeleteRes', response.status);
							}else{
								connection.query('SELECT * FROM vncp_forward_dns_record WHERE domain = ?', [getdomain[0].domain], function(err, crecords) {
									if(err) {
										logger.info(err);
										socket.emit('ADMDomainDeleteRes', response.status);
									}else{
										if(crecords.length > 0) {
											logger.info('Cannot delete domain - records exist.');
											socket.emit('ADMDomainDeleteRes', response.status);
										}else{
											connection.query('DELETE FROM vncp_forward_dns_domain WHERE id = ?', [data.id], function(err, ddelete) {
												if(err) {
													logger.info(err);
													socket.emit('ADMDomainDeleteRes', response.status);
												}else{
													response.status = 'ok';
													socket.emit('ADMDomainDeleteRes', response.status);
												}
											});
										}
									}
								});
							}
						});
					}else{
						logger.info('User not admin group');
						socket.emit('ADMDomainDeleteRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('ADMDomainDeleteRes', response.status);
		}
	},
	deleteptr: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("by" in data && "id" in data) {
			connection.query('SELECT * FROM vncp_users WHERE id = ?', [data.by], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('ADMPTRDeleteRes', response.status);
				}else{
					if(results.length == 1 && 'group' in results[0] && results[0].group == 2 && results[0].id == connections[socket.id]) {
						connection.query('DELETE FROM vncp_reverse_dns WHERE id = ?', [data.id], function(err, update) {
							if(err) {
								logger.info(err);
								socket.emit('ADMPTRDeleteRes', response.status);
							}else{
								response.status = 'ok';
								socket.emit('ADMPTRDeleteRes', response.status);
							}
						});
					}else{
						logger.info('User not admin group');
						socket.emit('ADMPTRDeleteRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('ADMPTRDeleteRes', response.status);
		}
	},
	deleteip2: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("by" in data && "id" in data) {
			connection.query('SELECT * FROM vncp_users WHERE id = ?', [data.by], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('ADMIP2DeleteRes', response.status);
				}else{
					if(results.length == 1 && 'group' in results[0] && results[0].group == 2 && results[0].id == connections[socket.id]) {
						connection.query('DELETE FROM vncp_secondary_ips WHERE id = ?', [data.id], function(err, update) {
							if(err) {
								logger.info(err);
								socket.emit('ADMIP2DeleteRes', response.status);
							}else{
								response.status = 'ok';
								socket.emit('ADMIP2DeleteRes', response.status);
							}
						});
					}else{
						logger.info('User not admin group');
						socket.emit('ADMIP2DeleteRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('ADMIP2DeleteRes', response.status);
		}
	},
	deleteprivate: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("by" in data && "id" in data) {
			connection.query('SELECT * FROM vncp_users WHERE id = ?', [data.by], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('ADMPrivateDeleteRes', response.status);
				}else{
					if(results.length == 1 && 'group' in results[0] && results[0].group == 2 && results[0].id == connections[socket.id]) {
						connection.query('UPDATE vncp_private_pool SET user_id=0, hb_account_id=0, available=1 WHERE id = ?', [data.id], function(err, update) {
							if(err) {
								logger.info(err);
								socket.emit('ADMPrivateDeleteRes', response.status);
							}else{
								response.status = 'ok';
								socket.emit('ADMPrivateDeleteRes', response.status);
							}
						});
					}else{
						logger.info('User not admin group');
						socket.emit('ADMPrivateDeleteRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('ADMPrivateDeleteRes', response.status);
		}
	},
	deletepublic: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("by" in data && "id" in data) {
			connection.query('SELECT * FROM vncp_users WHERE id = ?', [data.by], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('ADMPublicDeleteRes', response.status);
				}else{
					if(results.length == 1 && 'group' in results[0] && results[0].group == 2 && results[0].id == connections[socket.id]) {
						connection.query('UPDATE vncp_ipv4_pool SET user_id=0, hb_account_id=0, available=1 WHERE id = ?', [data.id], function(err, update) {
							if(err) {
								logger.info(err);
								socket.emit('ADMPublicDeleteRes', response.status);
							}else{
								response.status = 'ok';
								socket.emit('ADMPublicDeleteRes', response.status);
							}
						});
					}else{
						logger.info('User not admin group');
						socket.emit('ADMPublicDeleteRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('ADMPublicDeleteRes', response.status);
		}
	},
	clrpublic: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("by" in data && "id" in data) {
			connection.query('SELECT * FROM vncp_users WHERE id = ?', [data.by], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('ADMPublicClrRes', response.status);
				}else{
					if(results.length == 1 && 'group' in results[0] && results[0].group == 2 && results[0].id == connections[socket.id]) {
						connection.query('DELETE FROM vncp_ipv4_pool WHERE id = ?', [data.id], function(err, update) {
							if(err) {
								logger.info(err);
								socket.emit('ADMPublicClrRes', response.status);
							}else{
								response.status = 'ok';
								socket.emit('ADMPublicClrRes', response.status);
							}
						});
					}else{
						logger.info('User not admin group');
						socket.emit('ADMPublicClrRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('ADMPublicClrRes', response.status);
		}
	},
	setpublic: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("by" in data && "id" in data && "hbid" in data) {
			connection.query('SELECT * FROM vncp_users WHERE id = ?', [data.by], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('ADMSetIPRes', response.status);
				}else{
					if(results.length == 1 && 'group' in results[0] && results[0].group == 2 && results[0].id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_lxc_ct WHERE hb_account_id = ?', [data.hbid], function(err, lxcct) {
							if(err) {
								logger.info(err);
								socket.emit('ADMSetIPRes', response.status);
							}else{
								if(lxcct.length == 1) {
									connection.query('SELECT * FROM vncp_ipv4_pool WHERE id = ?', [data.id], function(err, nodecheck) {
										if(err) {
											logger.info(err);
											socket.emit('ADMSetIPRes', response.status);
										}else{
											var vnodes = nodecheck[0].nodes.split(";");
											if(vnodes.includes(lxcct[0].node)) {
												connection.query('UPDATE vncp_ipv4_pool SET user_id=?, hb_account_id=?, available=0 WHERE id = ?', [lxcct[0].user_id, data.hbid, data.id], function(err, update) {
													if(err) {
														logger.info(err);
														socket.emit('ADMSetIPRes', response.status);
													}else{
														response.status = 'ok';
														socket.emit('ADMSetIPRes', response.status);
													}
												});
											}else{
												logger.info('Chosen billing ID and node do not match.');
												socket.emit('ADMSetIPRes', response.status);
											}
										}
									});
								}else{
									connection.query('SELECT * FROM vncp_kvm_ct WHERE hb_account_id = ?', [data.hbid], function(err, kvmct) {
										if(err) {
											logger.info(err);
											socket.emit('ADMSetIPRes', response.status);
										}else{
											if(kvmct.length == 1) {
												connection.query('SELECT * FROM vncp_ipv4_pool WHERE id = ?', [data.id], function(err, nodecheck) {
													if(err) {
														logger.info(err);
														socket.emit('ADMSetIPRes', response.status);
													}else{
														var vnodes = nodecheck[0].nodes.split(";");
														if(vnodes.includes(kvmct[0].node)) {
															connection.query('UPDATE vncp_ipv4_pool SET user_id=?, hb_account_id=?, available=0 WHERE id = ?', [kvmct[0].user_id, data.hbid, data.id], function(err, update) {
																if(err) {
																	logger.info(err);
																	socket.emit('ADMSetIPRes', response.status);
																}else{
																	response.status = 'ok';
																	socket.emit('ADMSetIPRes', response.status);
																}
															});
														}else{
															logger.info('Chosen billing ID and node do not match.');
															socket.emit('ADMSetIPRes', response.status);
														}
													}
												});
											}else{
												logger.info('Billing ID not found in KVM or LXC tables.');
												socket.emit('ADMSetIPRes', response.status);
											}
										}
									});
								}
							}
						});
					}else{
						logger.info('User not admin group');
						socket.emit('ADMSetIPRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('ADMSetIPRes', response.status);
		}
	},
	deleteipv6assign: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("by" in data && "id" in data) {
			connection.query('SELECT * FROM vncp_users WHERE id = ?', [data.by], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('ADMIPv6AssignDeleteRes', response.status);
				}else{
					if(results.length == 1 && 'group' in results[0] && results[0].group == 2 && results[0].id == connections[socket.id]) {
						connection.query('DELETE FROM vncp_ipv6_assignment WHERE id = ?', [data.id], function(err, update) {
							if(err) {
								logger.info(err);
								socket.emit('ADMIPv6AssignDeleteRes', response.status);
							}else{
								response.status = 'ok';
								socket.emit('ADMIPv6AssignDeleteRes', response.status);
							}
						});
					}else{
						logger.info('User not admin group');
						socket.emit('ADMIPv6AssignDeleteRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('ADMIPv6AssignDeleteRes', response.status);
		}
	},
	deleteipv6pool: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("by" in data && "id" in data) {
			connection.query('SELECT * FROM vncp_users WHERE id = ?', [data.by], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('ADMIPv6PoolDeleteRes', response.status);
				}else{
					if(results.length == 1 && 'group' in results[0] && results[0].group == 2 && results[0].id == connections[socket.id]) {
						connection.query('DELETE FROM vncp_ipv6_pool WHERE id = ?', [data.id], function(err, update) {
							if(err) {
								logger.info(err);
								socket.emit('ADMIPv6PoolDeleteRes', response.status);
							}else{
								response.status = 'ok';
								socket.emit('ADMIPv6PoolDeleteRes', response.status);
							}
						});
					}else{
						logger.info('User not admin group');
						socket.emit('ADMIPv6PoolDeleteRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('ADMIPv6PoolDeleteRes', response.status);
		}
	},
	resetbw: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("by" in data && "id" in data) {
			connection.query('SELECT * FROM vncp_users WHERE id = ?', [data.by], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('ADMResetBWRes', response.status);
				}else{
					if(results.length == 1 && 'group' in results[0] && results[0].group == 2 && results[0].id == connections[socket.id]) {
						var today1 = moment();
						connection.query('UPDATE vncp_bandwidth_monitor SET current = 0, reset_date = ? WHERE id = ?', [today1.add(30, 'days').format('YYYY-MM-DD 00:00:00'), data.id], function(err, resetdate) {
							if(err) {
								logger.info(err);
								socket.emit('ADMResetBWRes', response.status);
							}else{
								response.status = 'ok';
								socket.emit('ADMResetBWRes', response.status);
							}
						});
					}else{
						logger.info('User not admin group');
						socket.emit('ADMResetBWRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('ADMResetBWRes', response.status);
		}
	},
	deletecustomiso: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("by" in data && "id" in data) {
			connection.query('SELECT * FROM vncp_users WHERE id = ?', [data.by], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('ADMKVMCustomISODeleteRes', response.status);
				}else{
					if(results.length == 1 && 'group' in results[0] && results[0].group == 2 && results[0].id == connections[socket.id]) {
						connection.query('SELECT * FROM vncp_kvm_isos_custom WHERE id = ?', [data.id], function(err, results) {
							if(err) {
								logger.info(err);
								socket.emit('ADMKVMCustomISODeleteRes', response.status);
							}else{
								connection.query('SELECT * FROM vncp_kvm_isos WHERE id > 0 LIMIT 1', [], function(err, isos) {
									if(err) {
										logger.info(err);
										socket.emit('ADMKVMCustomISODeleteRes', response.status);
									}else{
										var storage_location = isos[0].volid.split(":")[0];
										connection.query('SELECT * FROM vncp_nodes WHERE id > 0 LIMIT 1', [], function(err, node) {
											if(err) {
												logger.info(err);
												socket.emit('ADMKVMCustomISODeleteRes', response.status);
											}else{
												var px = new prox(node[0].hostname, node[0].username, node[0].realm, mc.decrypt(node[0].password));
												px.delete('/nodes/'+node[0].name+'/storage/'+storage_location+'/content/'+storage_location+':iso/'+results[0].upload_key+'.iso', {}, function(err, deleteiso) {
													connection.query('DELETE FROM vncp_kvm_isos_custom WHERE id = ?', [data.id], function(err, deleted) {
														if(err) {
															logger.info(err);
															socket.emit('ADMKVMCustomISODeleteRes', response.status);
														}else{
															response.status = 'ok';
															socket.emit('ADMKVMCustomISODeleteRes', response.status);
														}
													});
												});
											}
										});
									}
								});
							}
						});
					}else{
						logger.info('User not admin group');
						socket.emit('ADMKVMCustomISODeleteRes', response.status);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('ADMKVMCustomISODeleteRes', response.status);
		}
	},
	querylvl1: function(data, socket, connections, logger) {
		var response = {};
		response.status = 'error';
		if("by" in data && "id" in data) {
			connection.query('SELECT * FROM vncp_users WHERE id = ?', [data.by], function(err, results) {
				if(err) {
					logger.info(err);
					socket.emit('ADMQueryLvl1Res', response);
				}else{
					if(results.length == 1 && 'group' in results[0] && results[0].group == 2 && results[0].id == connections[socket.id]) {
						connection.query('SELECT x.node,x.hb_account_id,x.avail_ports,x.ports,x.avail_domains,x.domains,y.username FROM vncp_natforwarding AS x JOIN vncp_users AS y WHERE x.node = ? AND y.id = x.user_id', [data.id], function(err, natlvl1) {
							if(err) {
								logger.info(err);
								socket.emit('ADMQueryLvl1Res', response);
							}else{
								response.status = 'ok';
								response.tbl = natlvl1;
								socket.emit('ADMQueryLvl1Res', response);
							}
						});
					}else{
						logger.info('User not admin group');
						socket.emit('ADMQueryLvl1Res', response);
					}
				}
			});
		}else{
			logger.info("Data structure missing input.");
			socket.emit('ADMQueryLvl1Res', response);
		}
	}
};
