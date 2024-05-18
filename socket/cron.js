var config = require(process.cwd()+'/config');
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
			console.log('[PROXCP:CRON] Attempting to reconnect...');
			handleDisconnect();
		}else{
			throw err;
		}
	});
}
handleDisconnect();

var moment = require('moment');
var exec = require('exec');

module.exports = {
	run: function(logger) {
		var counter = 0;
		connection.query('SELECT * FROM vncp_pending_deletion', function(err, result) {
			var oldCount = result.length;
			if(err) {
				logger.info(err);
			}else{
				var now = moment();
				for(var i = 0; i < result.length; i++) {
					if(now.isAfter(moment(result[i].date_created).format('YYYY-MM-DD'), 'day')) {
						connection.query('DELETE FROM vncp_pending_deletion WHERE id = ?', [result[i].id], null);
						counter++;
					}
				}
				var newCount = oldCount - counter;
				exec(['php', process.cwd()+'/lib/cron_email.php', oldCount, newCount, counter], function(err, out, code) {
					if(err) {
						logger.info(err);
					}else{
						logger.info('Sent cron email successfully');
					}
				});
			}
		});
	},
	checkbw: function(logger) {
		logger.info('[CRON] Starting bandwidth check cron');
		connection.query('SELECT * FROM vncp_bandwidth_monitor WHERE id != ?', [0], function(err, bwmon) {
			if(err) {
				logger.info(err);
			}else{
				var all_pools = [];
				var all_nodes = [];
				var all_types = [];
				var all_current = [];
				for(var i = 0; i < bwmon.length; i++) {
					all_pools.push(bwmon[i].pool_id);
					all_nodes.push(bwmon[i].node);
					all_types.push(bwmon[i].ct_type);
					all_current.push(bwmon[i].current);
				}
				exec('php '+process.cwd()+'/lib/cron_bw_collect.php '+JSON.stringify(JSON.stringify(all_pools))+' '+JSON.stringify(JSON.stringify(all_nodes))+' '+JSON.stringify(JSON.stringify(all_types))+' '+JSON.stringify(JSON.stringify(all_current))+'', function(err, out, bw) {
					if(err) {
						logger.info(err);
					}else{
						logger.info('[CRON] Finished bandwidth check cron successfully');
					}
				});
			}
		});
	},
	resetbw: function(logger) {
		logger.info('[CRON] Starting bandwidth reset cron');
		var today1 = moment();
		var today2 = moment();
		connection.query('UPDATE vncp_bandwidth_monitor SET current = 0, reset_date = ? WHERE reset_date < ?', [today1.add(30, 'days').format('YYYY-MM-DD 00:00:00'), today2.format('YYYY-MM-DD 00:00:00')], function(err, resetdate) {
			if(err) {
				logger.info(err);
			}else{
				connection.query('SELECT * FROM vncp_settings WHERE item = ?', ['bw_auto_suspend'], function(err, autosetting) {
					if(err) {
						logger.info(err);
					}else{
						if(autosetting[0].value == 'true') {
							connection.query('SELECT * FROM vncp_bandwidth_monitor WHERE current > max', function(err, overids) {
								if(err) {
									logger.info(err);
								}else{
									if(overids.length > 0) {
										exec('php '+process.cwd()+'/lib/cron_bw_suspend.php '+JSON.stringify(JSON.stringify(overids))+'', function(err, out, suspend) {
											if(err) {
												logger.info(err);
											}else{
												logger.info('[CRON] Finished bandwidth reset cron successfully');
											}
										});
									}else{
										exec(['php', process.cwd()+'/lib/cron_bw_unsuspend.php'], function(err, out, unsuspend) {
											if(err) {
												logger.info(err);
											}else{
												logger.info('[CRON] Finished bandwidth reset cron successfully');
											}
										});
									}
								}
							});
						}else{
							logger.info('[CRON] Finished bandwidth reset cron successfully');
						}
					}
				});
			}
		});
	}
};
