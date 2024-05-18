var pendingbackups = [];
(function($) {
	// Helper function
	function uptimeCalc(sec) {
		var days = Math.floor(sec / 86400);
		var hours = Math.floor((sec % 86400) / 3600);
		var minutes = Math.floor(((sec % 86400) % 3600) / 60);
		var seconds = ((sec % 86400) % 3600) % 60;
		return (days + ' days ' + hours + ' hours ' + minutes + ' minutes ' + seconds + ' seconds');
	}
	function isEmpty(str) {
		return (!str || 0 === str.length);
	}
	function isBlank(str) {
		return (!str || /^\s*$/.test(str));
	}
	String.prototype.isEmpty = function() {
		return (this.length === 0 || !this.trim());
	};
	function isDomain(str) {
		var exp = /^[0-9a-z\-\.]+$/;
		if(str.match(exp)) return true;
		else return false;
	}
	function isAlphaNum(str) {
		var exp = /^[0-9a-z]+$/;
		if(str.match(exp)) return true;
		else return false;
	}

	var data = {
		hb_account_id: $('#lxcinfo').val(),
		respond: true
	};

	setInterval(function() {
		if(pendingbackups.length > 0) {
			socket.emit('LXCBackupStatusReq', pendingbackups);
		}
	}, 11000);
	socket.on('LXCBackupStatusRes', function(res) {
		if(res.status == 'ok') {
			if(res.tasks.length > 0) {
				$('#backup_info').html('');
				for(var i = 0; i < res.tasks.length; i++) {
					var tstatus = res.tasks[i].status;
					var tupid = res.tasks[i].upid;
					var tid = tupid.split(":");
					tid = tid[2] + ':' + tid[3] + ':' + tid[4];
					var tlog = res.tasks[i].log;
					if(tstatus == 'stopped') {
						var tindex = pendingbackups.indexOf(tupid);
						if(tindex !== -1) {
							pendingbackups.splice(tindex, 1);
						}
						if(typeof(Storage) !== "undefined") {
							localStorage.setItem("proxcp_user_backup_progress2", btoa(JSON.stringify(pendingbackups)));
						}
						$('#backup_info').append('<div class="panel panel-success"><div class="panel-heading"><div class="panel-title">Backup Job Status</div></div><div class="panel-body">Backup job '+tid+' completed!</div></div><br />');
						if(i == (res.tasks.length - 1)) {
							setTimeout(function() { window.location.reload(); }, 2500);
						}
					}else{
						tlog.reverse();
						var tindex = tlog.findIndex(element => element.includes("%"));
						if(tindex !== null && tindex >= 0) {
							var lastpercent = tlog[tindex].split("%")[0].split(":")[1];
							$('#backup_info').append('<div class="panel panel-info"><div class="panel-heading"><div class="panel-title">Backup Job Status</div></div><div class="panel-body">Backup job '+tid+' still running. Last percentage: '+lastpercent+'%</div></div><br />');
						}else{
							$('#backup_info').append('<div class="panel panel-info"><div class="panel-heading"><div class="panel-title">Backup Job Status</div></div><div class="panel-body">Backup job '+tid+' still running. Last percentage: unknown</div></div><br />');
						}
					}
				}
			}else{
				pendingbackups = [];
			}
		}else{
			pendingbackups = [];
		}
	});

	// LXC server status & resource check
	socket.emit('LXCStatusCheckReq', data);
	setInterval(function() {
		socket.emit('LXCStatusCheckReq', data);
	}, 10000);
	socket.on('LXCStatusCheckRes', function(res) {
		if(res.status == 'running') {
			$('#status_2').removeClass('label-danger');
			$('#status_2').addClass('label-success');
			$('#status_2').html('<i class="fa fa-check"></i> Online');
			// Buttons
			$('#start_server').prop('disabled', true);
			$('#shutdown_server').prop('disabled', false);
			$('#restart_server').prop('disabled', false);
			$('#kill_server').prop('disabled', false);
		}else if(res.status == 'stopped'){
			$('#status_2').removeClass('label-success');
			$('#status_2').addClass('label-danger');
			$('#status_2').html('<i class="fa fa-times"></i> Offline');
			// Buttons
			$('#start_server').prop('disabled', false);
			$('#shutdown_server').prop('disabled', true);
			$('#restart_server').prop('disabled', true);
			$('#kill_server').prop('disabled', true);
		}else{
			$('#status_2').removeClass('label-success');
			$('#status_2').addClass('label-danger');
			$('#status_2').html('<i class="fa fa-times"></i> Offline');
			$('#func_error').html('Error: could not get server status.');
			// Buttons
			$('#start_server').prop('disabled', false);
			$('#shutdown_server').prop('disabled', false);
			$('#restart_server').prop('disabled', false);
			$('#kill_server').prop('disabled', false);
		}
		var cpu = Math.round(res.cpu * 100);
		var ram = Math.round((res.mem / res.maxmem) * 100);
		var disk = Math.round((res.disk / res.maxdisk) * 100);
		var swap = Math.round((res.swap / res.maxswap) * 100);
		$('#cpu_usage_1').attr('aria-valuenow', cpu);
		$('#cpu_usage_1').css('width', cpu + '%');
		$('#cpu_usage_2').html(cpu + '%');
		if(cpu <= 33) {
			$('#cpu_usage_1').removeClass('progress-bar-info');
			$('#cpu_usage_1').removeClass('progress-bar-warning');
			$('#cpu_usage_1').removeClass('progress-bar-danger');
			$('#cpu_usage_1').addClass('progress-bar-success');
		}else if(cpu >= 34 && cpu <= 66) {
			$('#cpu_usage_1').removeClass('progress-bar-info');
			$('#cpu_usage_1').removeClass('progress-bar-success');
			$('#cpu_usage_1').removeClass('progress-bar-danger');
			$('#cpu_usage_1').addClass('progress-bar-warning');
		}else{
			$('#cpu_usage_1').removeClass('progress-bar-info');
			$('#cpu_usage_1').removeClass('progress-bar-warning');
			$('#cpu_usage_1').removeClass('progress-bar-success');
			$('#cpu_usage_1').addClass('progress-bar-danger');
		}
		$('#ram_usage_1').attr('aria-valuenow', ram);
		$('#ram_usage_1').css('width', ram + '%');
		$('#ram_usage_2').html(ram + '%');
		if(ram <= 33) {
			$('#ram_usage_1').removeClass('progress-bar-info');
			$('#ram_usage_1').removeClass('progress-bar-warning');
			$('#ram_usage_1').removeClass('progress-bar-danger');
			$('#ram_usage_1').addClass('progress-bar-success');
		}else if(ram >= 34 && ram <= 66) {
			$('#ram_usage_1').removeClass('progress-bar-info');
			$('#ram_usage_1').removeClass('progress-bar-success');
			$('#ram_usage_1').removeClass('progress-bar-danger');
			$('#ram_usage_1').addClass('progress-bar-warning');
		}else{
			$('#ram_usage_1').removeClass('progress-bar-info');
			$('#ram_usage_1').removeClass('progress-bar-warning');
			$('#ram_usage_1').removeClass('progress-bar-success');
			$('#ram_usage_1').addClass('progress-bar-danger');
		}
		$('#disk_usage_1').attr('aria-valuenow', disk);
		$('#disk_usage_1').css('width', disk + '%');
		$('#disk_usage_2').html(disk + '%');
		if(disk <= 33) {
			$('#disk_usage_1').removeClass('progress-bar-info');
			$('#disk_usage_1').removeClass('progress-bar-warning');
			$('#disk_usage_1').removeClass('progress-bar-danger');
			$('#disk_usage_1').addClass('progress-bar-success');
		}else if(disk >= 34 && disk <= 66) {
			$('#disk_usage_1').removeClass('progress-bar-info');
			$('#disk_usage_1').removeClass('progress-bar-success');
			$('#disk_usage_1').removeClass('progress-bar-danger');
			$('#disk_usage_1').addClass('progress-bar-warning');
		}else{
			$('#disk_usage_1').removeClass('progress-bar-info');
			$('#disk_usage_1').removeClass('progress-bar-warning');
			$('#disk_usage_1').removeClass('progress-bar-success');
			$('#disk_usage_1').addClass('progress-bar-danger');
		}
		$('#swap_usage_1').attr('aria-valuenow', swap);
		$('#swap_usage_1').css('width', swap + '%');
		$('#swap_usage_2').html(swap + '%');
		if(swap <= 33) {
			$('#swap_usage_1').removeClass('progress-bar-info');
			$('#swap_usage_1').removeClass('progress-bar-warning');
			$('#swap_usage_1').removeClass('progress-bar-danger');
			$('#swap_usage_1').addClass('progress-bar-success');
		}else if(swap >= 34 && swap <= 66) {
			$('#swap_usage_1').removeClass('progress-bar-info');
			$('#swap_usage_1').removeClass('progress-bar-success');
			$('#swap_usage_1').removeClass('progress-bar-danger');
			$('#swap_usage_1').addClass('progress-bar-warning');
		}else{
			$('#swap_usage_1').removeClass('progress-bar-info');
			$('#swap_usage_1').removeClass('progress-bar-warning');
			$('#swap_usage_1').removeClass('progress-bar-success');
			$('#swap_usage_1').addClass('progress-bar-danger');
		}
		$('#uptime').html(uptimeCalc(res.uptime));
	});

	// LXC start button
	$('#start_server').click(function() {
		$('#start_server, #shutdown_server, #restart_server, #kill_server').prop('disabled', true);
		data.respond = false;
		socket.emit('LXCStartReq', data);
	});
	socket.on('LXCStartRes', function(res) {
		if(res == 'ok') {
			data.respond = true;
		}else{
			data.respond = false;
			$('#func_error').html('Error: could not start VM.');
		}
	});

	// LXC shutdown button
	$('#shutdown_server').click(function() {
		$('#start_server, #shutdown_server, #restart_server, #kill_server').prop('disabled', true);
		data.respond = false;
		socket.emit('LXCShutdownReq', data);
	});
	socket.on('LXCShutdownRes', function(res) {
		if(res == 'ok') {
			data.respond = true;
		}else{
			data.respond = false;
			$('#func_error').html('Error: could not shutdown VM.');
		}
	});

	// LXC restart button
	$('#restart_server').click(function() {
		$('#start_server, #shutdown_server, #restart_server, #kill_server').prop('disabled', true);
		data.respond = false;
		socket.emit('LXCRestartReq', data);
	});
	socket.on('LXCRestartRes', function(res) {
		if(res == 'ok') {
			data.respond = true;
		}else{
			data.respond = false;
			$('#func_error').html('Error: could not restart VM.');
		}
	});

	// LXC kill button
	$('#kill_server').click(function() {
		$('#start_server, #shutdown_server, #restart_server, #kill_server').prop('disabled', true);
		data.respond = false;
		socket.emit('LXCKillReq', data);
	});
	socket.on('LXCKillRes', function(res) {
		if(res == 'ok') {
			data.respond = true;
		}else{
			data.respond = false;
			$('#func_error').html('Error: could not kill VM.');
		}
	});

	$('form#scheduled_form').submit(function(e) {
		e.preventDefault();
		var dow = $('#scheduled_dow').val();
		var time = $('#scheduled_time').val();
		if(!isEmpty(dow) && !isEmpty(time)) {
			var schopts = {
				aid: $('#lxcinfo').val(),
				dow: dow,
				time: time
			};
			$(this).find(':input[type=submit]').prop("disabled", true).html('<i class="fa fa-cog fa-spin"></i> Please wait...scheduling');
			socket.emit('LXCScheduleBackupReq', schopts);
			$('#scheduled_dow').val('');
			$('#scheduled_time').val('');
		}else{
			alert('Invalid schedule selections. Please select a valid day of week and time.');
		}
	});
	socket.on('LXCScheduleBackupRes', function(res) {
		if(res.status == 'ok') {
			window.location.reload();
		}else{
			$('#scheduled_submit').prop("disabled", true).html('Error scheduling backup. Contact your vendor for assistance.');
		}
	});
	$('form#schdelete_form').submit(function(e) {
		e.preventDefault();
		var schopts = {
			aid: $('#lxcinfo').val(),
			schid: $('#schid').val()
		};
		$(this).find(':input[type=submit]').prop("disabled", true).html('<i class="fa fa-cog fa-spin"></i> Please wait...deleting');
		socket.emit('LXCScheduledBackupDelReq', schopts);
	});
	socket.on('LXCScheduleBackupDelRes', function(res) {
		if(res.status == 'ok') {
			window.location.reload();
		}else{
			$('#schdelete_submit').prop("disabled", true).html('Error deleting scheduled backup. Contact your vendor for assistance.');
		}
	});

	// LXC create backup
	$('#create_backup').click(function() {
		$(this).prop('disabled', true);
		var newbackup = {
			aid: $('#backup_aid').val(),
			notification: $('#notification').val()
		};
		data.respond = false;
		socket.emit('LXCCreateBackupReq', newbackup);
		$('#backup_message').html('Backup job tasked successfully!');
		$('#cancel_backup').html('Close');
	});
	socket.on('LXCCreateBackupRes', function(res) {
		if(res.status == 'ok') {
			data.respond = true;
			$('#countsection > button').prop('disabled', true);
			var oldcount = parseInt($('#currentbackupcount').html());
			var newcount = oldcount + 1;
			$('#currentbackupcount').html('' + newcount);
			$('#backup_modal').modal('toggle');
			var maxcount = parseInt($('#maxbackupcount').html());
			if(newcount >= maxcount) {
				$('#countsection').html('').html('<button type="button" class="btn btn-md btn-warning btn-block" disabled="disabled" id="backup-warning">Create backup</button><span id="backup-warning-2">&nbsp;&nbsp;&nbsp;&nbsp;<small><em>Backup limit reached. Remove some old backups to create more.</em></small></span><br /><br />');
			}
			$('#backuplist > tbody:last-child').append('<tr><td><i class="fa fa-cog fa-spin"></i> Pending...</td><td>Unknown</td><td>N/A</td><td>N/A</td><td>N/A</td></tr>');
			pendingbackups.push(res.upid);
			if(typeof(Storage) !== "undefined") {
				localStorage.setItem("proxcp_user_backup_progress2", btoa(JSON.stringify(pendingbackups)));
			}
		}else{
			window.location.reload();
		}
	});

	// LXC remove backup
	$('[id^=remove_backup_]').click(function() {
		var confirmed = confirm('Are you sure you want to delete this backup?');
		if(confirmed === true) {
			$(this).prop('disabled', true);
			var rmid = $(this).attr('id').split("_");
			rmid = rmid[rmid.length - 1];
			$('#restore_backup_'+rmid).prop('disabled', true);
			var rmbk = {
				aid: $('#lxcinfo').val(),
				volid: $(this).attr('content')
			};
			socket.emit('LXCRemoveBackupReq', rmbk);
			$(this).closest('tr').remove();
		}
	});
	socket.on('LXCRemoveBackupRes', function(res) {
		if(res == 'ok') {
			var newcount = parseInt($('#currentbackupcount').html()) - 1;
			$('#currentbackupcount').html('' + newcount);
			$('#backup-warning-2').remove();
			$('#backup-warning').removeClass('btn-warning').addClass('btn-success').prop('disabled', false).attr('data-toggle', 'modal').attr('data-target', '#backup_modal');
		}else{
			window.location.reload();
		}
	});

	$('[id^=get_backup_config_]').click(function() {
		$(this).prop('disabled', true);
		$('#config_modal').modal('toggle');
		var getcf = {
			aid: $('#lxcinfo').val(),
			volid: $(this).attr('content')
		};
		$('#confheader').html('');
		$('#confheader').html($(this).attr('content').split("/")[1]);
		socket.emit('LXCGetBackupConfReq', getcf);
	});
	socket.on('LXCGetBackupConfRes', function(res) {
		if(res.status == 'ok') {
			$('#confwell').html('');
			$('#confwell').html(res.conf);
		}else{
			$('#confwell').html('Error: could not get backup configuration.');
		}
	});
	$('#config_modal').on('hidden.bs.modal', function(e) {
		$('[id^=get_backup_config_]').prop('disabled', false);
	});

	// LXC restore backup
	$('#restore_modal').modal({
		backdrop: 'static',
		keyboard: false,
		show: false
	});
	$('[id^=restore_backup_]').click(function() {
		var confirmed = confirm('Are you sure you want to restore this backup? If yes, all current data will be deleted and your VPS will be restored to a previous state.');
		if(confirmed === true) {
			data.respond = false;
			$(this).prop('disabled', true);
			var rsid = $(this).attr('id').split("_");
			rsid = rsid[rsid.length - 1];
			$('#remove_backup_'+rsid).prop('disabled', true);
			$('#get_backup_config_'+rsid).prop('disabled', true);
			$('#countsection > button').prop('disabled', true);
			var rsbk = {
				aid: $('#lxcinfo').val(),
				volid: $(this).attr('content')
			};
			socket.emit('LXCRestoreBackupReq', rsbk);
			$('#restore_modal').modal('show');
			$('.restore_progress').animate({
				width: "100%"
			}, 60000, 'swing', function() {
				$('.restore_progress').removeClass('progress-bar-info active');
				$('.restore_progress').addClass('progress-bar-success');
				$('.restore_progress').html('Complete!');
				setTimeout(function() {
					$('#restore_modal').modal('hide');
					window.location.href = '/index';
				}, 2500);
			});
		}
	});
	socket.on('LXCRestoreBackupRes', function(res) {
		if(res == 'ok') {
			data.respond = true;
			$('#restore_output').html('Almost done. Saving configuration and cleaning up...');
		}else{
			data.respond = false
			$('#restore_output').html('An unexpected error occurred.');
		}
	});

	// LXC Rebuild
	$('#rebuild_modal').modal({
		backdrop: 'static',
		keyboard: false,
		show: false
	});
	$('#rebuild_btn').click(function(e) {
		data.respond = false;
		e.preventDefault();
		$('#os_error, #hostname_error, #password_error').html('');
		$(this).prop('disabled', true);
		var os = $('#os').val();
		var hostname = $('#hostname').val();
		var password = $('#password').val();
		var aid = $('#aid').val();
		if(os == 'default') {
			$('#os_error').html('Error: no operating system was chosen.');
			$(this).prop('disabled', false);
		}else{
			if(isEmpty(hostname) || isBlank(hostname) || hostname.isEmpty() || !isDomain(hostname) || !isAlphaNum(hostname[hostname.length - 1])) {
				$('#hostname_error').html('Error: invalid hostname.');
				$(this).prop('disabled', false);
			}else{
				if(isEmpty(password) || isBlank(password) || password.isEmpty() || password.length < 5) {
					$('#password_error').html('Error: invalid password.');
					$(this).prop('disabled', false);
				}else{
					var newvm = {
						os: os,
						hostname: hostname,
						password: password,
						aid: aid
					};
					$('#hostname, #password').val('');
					$('#os_error, #hostname_error, #password_error').html('');
					var confirmed = confirm("WARNING: Rebuilding your VPS will delete ALL data it currently stores. Do you want to proceed?");
					if(confirmed === true) {
						socket.emit('LXCRebuildReq', newvm);
						$('#rebuild_modal').modal('show');
						$('.rebuild_progress').animate({
							width: "100%"
						}, 60000, 'swing', function() {
							$('.rebuild_progress').removeClass('progress-bar-info active');
							$('.rebuild_progress').addClass('progress-bar-success');
							$('.rebuild_progress').html('Complete!');
							setTimeout(function() {
								$('#rebuild_modal').modal('hide');
								window.location.href = '/index';
							}, 2500);
						});
					}else{
						$('#rebuild_btn').prop('disabled', false);
					}
				}
			}
		}
	});
	socket.on('LXCRebuildRes', function(res) {
		if(res == 'ok') {
			data.respond = true;
			$('#rebuild_output').html('Almost done. Saving configuration and cleaning up...');
		}else{
			data.respond = false
			$('#rebuild_output').html('An unexpected error occurred.');
		}
	});

	// LXC console
	$('#lxcconsole').click(function(e) {
		e.preventDefault();
		var id = $(this).attr('role');
		window.open("/console?id="+id+"&virt=lxc", "_blank", "height=580,width=820,status=yes,toolbar=no,menubar=no,location=no,addressbar=no");
	});

	// LXC resource graphs
	$('#day, #week, #month, #year').hide();
	var currentScale = '#hour';
	$('#graphtime').change(function() {
		var scale = $(this).val();
		$(''+currentScale).hide();
		$('#'+scale).show();
		currentScale = '#'+scale;
	});

	// LXC enable/disable tun/tap
	$('#enabletap').click(function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var data = {
			aid: $('#lxcinfo').val()
		};
		socket.emit('LXCEnableTAPReq', data);
	});
	socket.on('LXCEnableTAPRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}else{
			$('#enabletap').html('Error');
		}
	});

	// LXC enable/disable tun/tap
	$('#disabletap').click(function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var data = {
			aid: $('#lxcinfo').val()
		};
		socket.emit('LXCDisableTAPReq', data);
	});
	socket.on('LXCDisableTAPRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}else{
			$('#disabletap').html('Error');
		}
	});

	$('#chgrootpw_lxc').click(function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var data = {
			aid: $('#lxcinfo').val()
		};
		socket.emit('LXCChangePWReq', data);
	});
	socket.on('LXCChangePWRes', function(res) {
		if(res.status == 'ok') {
			$('#lxc_pwsuccess').html('<div class="alert alert-success" role="alert"><strong>New Root Password:</strong> '+res.password+'</div>');
		}else{
			$('#chgrootpw_lxc').html('Error');
			$('#lxc_pwsuccess').html('<div class="alert alert-danger" role="alert"><strong>Error:</strong> reset root password failed.</div>');
		}
	});

	// LXC enable/disable onboot
	$('#enableonboot').click(function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var data = {
			aid: $('#lxcinfo').val()
		};
		socket.emit('LXCEnableOnbootReq', data);
	});
	socket.on('LXCEnableOnbootRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}else{
			$('#enableonboot').html('Error');
		}
	});

	// LXC enable/disable onboot
	$('#disableonboot').click(function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var data = {
			aid: $('#lxcinfo').val()
		};
		socket.emit('LXCDisableOnbootReq', data);
	});
	socket.on('LXCDisableOnbootRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}else{
			$('#disableonboot').html('Error');
		}
	});

	// LXC enable quotas
	$('#enablequotas').click(function(e) {
		var confirmed = confirm('Enabling quotas requires your VPS to be shutdown. Do you want to proceed now?');
		if(confirmed === true) {
			e.preventDefault();
			$(this).prop("disabled", true);
			var data = {
				aid: $('#lxcinfo').val()
			};
			socket.emit('LXCEnableQuotasReq', data);
		}
	});
	socket.on('LXCEnableQuotasRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}else{
			$('#enablequotas').html('Error');
		}
	});

	// LXC disable quotas
	$('#disablequotas').click(function(e) {
		var confirmed = confirm('Disabling quotas requires your VPS to be shutdown. Do you want to proceed now?');
		if(confirmed === true) {
			e.preventDefault();
			$(this).prop("disabled", true);
			var data = {
				aid: $('#lxcinfo').val()
			};
			socket.emit('LXCDisableQuotasReq', data);
		}
	});
	socket.on('LXCDisableQuotasRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}else{
			$('#disablequotas').html('Error');
		}
	});

	// LXC enable private network
	$('#enableprivatenet').click(function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var data = {
			aid: $('#lxcinfo').val()
		};
		socket.emit('LXCEnablePrivateNetworkReq', data);
	});
	socket.on('LXCEnablePrivateNetworkRes', function(res) {
		window.location.reload();
	});

	// LXC disable private network
	$('#disableprivatenet').click(function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var data = {
			aid: $('#lxcinfo').val()
		};
		socket.emit('LXCDisablePrivateNetworkReq', data);
	});
	socket.on('LXCDisablePrivateNetworkRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}
	});

	// LXC assign IPv6
	$('#assignipv6').click(function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var data = {
			aid: $('#lxcinfo').val()
		};
		socket.emit('LXCAssignIPv6Req', data);
	});
	socket.on('LXCAssignIPv6Res', function(res) {
		window.location.reload();
	});

	// LXC get task log
	$('#getvmlog').click(function(e) {
		e.preventDefault();
		$(this).prop('disabled', true);
		var data = {
			aid: $('#lxcinfo').val()
		};
		socket.emit('LXCGetLogReq', data);
	});
	$('#clearvmlog').click(function(e) {
		e.preventDefault();
		$('#vmlog').html('null');
	});
	socket.on('LXCGetLogRes', function(res) {
		if(res.status == 'ok') {
			$('#vmlog').html(res.log);
			$('#getvmlog').prop('disabled', false);
		}else{
			$('#vmlog').html('Error fetching log');
		}
	});

	$('#natport_btn').click(function(e) {
		e.preventDefault();
		$('#natport_error, #natdesc_error').html('');
		$(this).prop('disabled', true);
		var natport = $('#chosennatport').val();
		var natdesc = $('#natportdesc').val();
		var aid = $('#aid').val();
		if(natport < 1 || natport > 65535) {
			$('#natport_error').html('Error: invalid NAT port.');
			$(this).prop('disabled', false);
		}else{
			if(isEmpty(natdesc) || isBlank(natdesc) || natdesc.isEmpty()) {
				$('#natdesc_error').html('Error: invalid description.');
				$(this).prop('disabled', false);
			}else{
				var newport = {
					natport: natport,
					natdesc: natdesc,
					aid: aid
				};
				$('#chosennatport, #natportdesc').val('');
				$('#natport_error, #natdesc_error').html('');
				socket.emit('LXCAddNATPortReq', newport);
			}
		}
	});
	socket.on('LXCAddNATPortRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}else{
			$('#natport_error').html('Unable to add new NAT port forward.');
		}
	});

	$('#user_porttable').on('click', '[id^=user_natportdelete]', function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var data = {
			id: $(this).attr('role'),
			aid: $('#lxcinfo').val()
		};
		socket.emit('LXCDelNATPortReq', data);
	});
	socket.on('LXCDelNATPortRes', function(res) {
		window.location.reload();
	});

	$('#natdomain_btn').click(function(e) {
		e.preventDefault();
		$('#natdomain_error').html('');
		$(this).prop('disabled', true);
		var natdomain = $('#chosendomain').val().trim();
		var natsslcert = $('#nat_sslcert').val().trim();
		var natsslkey = $('#nat_sslkey').val().trim();
		var aid = $('#aid').val();
		if(isEmpty(natdomain) || isBlank(natdomain) || natdomain.isEmpty() || !isDomain(natdomain) || !isAlphaNum(natdomain[natdomain.length - 1])) {
			$('#natdomain_error').html('Error: invalid NAT domain.');
			$(this).prop('disabled', false);
		}else{
			var newdomain = {
				natdomain: natdomain,
				natsslcert: natsslcert,
				natsslkey: natsslkey,
				aid: aid
			};
			$('#chosendomain').val('');
			$('#nat_sslcert').val('');
			$('#nat_sslkey').val('');
			$('#natdomain_error').html('');
			socket.emit('LXCAddNATDomainReq', newdomain);
		}
	});
	socket.on('LXCAddNATDomainRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}else{
			$('#natdomain_error').html('Unable to add new NAT domain forward.');
			$('#natdomain_btn').prop('disabled', false);
		}
	});

	$('#user_domaintable').on('click', '[id^=user_natdomaindelete]', function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var data = {
			id: $(this).attr('role'),
			aid: $('#lxcinfo').val()
		};
		socket.emit('LXCDelNATDomainReq', data);
	});
	socket.on('LXCDelNATDomainRes', function(res) {
		window.location.reload();
	});
})(jQuery);
$(document).ready(function() {
	if(typeof(Storage) !== "undefined" && (typeof(localStorage.getItem("proxcp_user_backup_progress2")) == "string")) {
		pendingbackups = JSON.parse(atob(localStorage.getItem("proxcp_user_backup_progress2")));
	}
	if(pendingbackups.length > 0) {
		$('#countsection').html('<button type="button" class="btn btn-md btn-warning btn-block" disabled="disabled" id="backup-warning">Create backup</button><span id="backup-warning-2"><p><center><em>Please wait...checking status of last backup.</em></center></p></span><br /><br />');
	}
});
