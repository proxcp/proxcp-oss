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
	function isUrlValid(url) {
	    return /^(https?|s?):\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i.test(url);
	}

	var data = {
		hb_account_id: $('#kvminfo').val(),
		respond: true
	};

	setInterval(function() {
		if(pendingbackups.length > 0) {
			socket.emit('KVMBackupStatusReq', pendingbackups);
		}
	}, 11000);
	socket.on('KVMBackupStatusRes', function(res) {
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
							localStorage.setItem("proxcp_user_backup_progress", btoa(JSON.stringify(pendingbackups)));
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

	// KVM server status & resource check
	socket.emit('KVMStatusCheckReq', data);
	setInterval(function() {
		socket.emit('KVMStatusCheckReq', data);
	}, 10000);
	socket.on('KVMStatusCheckRes', function(res) {
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
		$('#uptime').html(uptimeCalc(res.uptime));
	});

	// KVM start button
	$('#start_server').click(function() {
		$('#start_server, #shutdown_server, #restart_server, #kill_server').prop('disabled', true);
		data.respond = false;
		socket.emit('KVMStartReq', data);
	});
	socket.on('KVMStartRes', function(res) {
		if(res == 'ok') {
			data.respond = true;
		}else{
			data.respond = false;
			$('#func_error').html('Error: could not start VM.');
		}
	});

	// KVM shutdown button
	$('#shutdown_server').click(function() {
		$('#start_server, #shutdown_server, #restart_server, #kill_server').prop('disabled', true);
		data.respond = false;
		socket.emit('KVMShutdownReq', data);
	});
	socket.on('KVMShutdownRes', function(res) {
		if(res == 'ok') {
			data.respond = true;
		}else{
			data.respond = false;
			$('#func_error').html('Error: could not shutdown VM.');
		}
	});

	// KVM restart button
	$('#restart_server').click(function() {
		$('#start_server, #shutdown_server, #restart_server, #kill_server').prop('disabled', true);
		data.respond = false;
		socket.emit('KVMRestartReq', data);
	});
	socket.on('KVMRestartRes', function(res) {
		if(res == 'ok') {
			data.respond = true;
		}else{
			data.respond = false;
			$('#func_error').html('Error: could not restart VM.');
		}
	});

	// KVM kill button
	$('#kill_server').click(function() {
		$('#start_server, #shutdown_server, #restart_server, #kill_server').prop('disabled', true);
		data.respond = false;
		socket.emit('KVMKillReq', data);
	});
	socket.on('KVMKillRes', function(res) {
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
				aid: $('#kvminfo').val(),
				dow: dow,
				time: time
			};
			$(this).find(':input[type=submit]').prop("disabled", true).html('<i class="fa fa-cog fa-spin"></i> Please wait...scheduling');
			socket.emit('KVMScheduleBackupReq', schopts);
			$('#scheduled_dow').val('');
			$('#scheduled_time').val('');
		}else{
			alert('Invalid schedule selections. Please select a valid day of week and time.');
		}
	});
	socket.on('KVMScheduleBackupRes', function(res) {
		if(res.status == 'ok') {
			window.location.reload();
		}else{
			$('#scheduled_submit').prop("disabled", true).html('Error scheduling backup. Contact your vendor for assistance.');
		}
	});
	$('form#schdelete_form').submit(function(e) {
		e.preventDefault();
		var schopts = {
			aid: $('#kvminfo').val(),
			schid: $('#schid').val()
		};
		$(this).find(':input[type=submit]').prop("disabled", true).html('<i class="fa fa-cog fa-spin"></i> Please wait...deleting');
		socket.emit('KVMScheduledBackupDelReq', schopts);
	});
	socket.on('KVMScheduleBackupDelRes', function(res) {
		if(res.status == 'ok') {
			window.location.reload();
		}else{
			$('#schdelete_submit').prop("disabled", true).html('Error deleting scheduled backup. Contact your vendor for assistance.');
		}
	});

	// KVM create backup
	$('#create_backup').click(function() {
		if(isEmpty($('#notification').val()) || isBlank($('#notification').val()) || $('#notification').val().isEmpty()) {
			$('#backup_message').html('Invalid notification value.');
		}else{
			$(this).prop('disabled', true);
			var newbackup = {
				aid: $('#backup_aid').val(),
				notify: $('#notification').val()
			};
			data.respond = false;
			socket.emit('KVMCreateBackupReq', newbackup);
			$('#backup_message').html('Backup job tasked successfully!');
			$('#cancel_backup').html('Close');
		}
	});
	socket.on('KVMCreateBackupRes', function(res) {
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
				localStorage.setItem("proxcp_user_backup_progress", btoa(JSON.stringify(pendingbackups)));
			}
		}else{
			window.location.reload();
		}
	});

	// KVM remove backup
	$('[id^=remove_backup_]').click(function() {
		var confirmed = confirm('Are you sure you want to delete this backup?');
		if(confirmed === true) {
			$(this).prop('disabled', true);
			var rmid = $(this).attr('id').split("_");
			rmid = rmid[rmid.length - 1];
			$('#restore_backup_'+rmid).prop('disabled', true);
			var rmbk = {
				aid: $('#kvminfo').val(),
				snapname: $(this).attr('content')
			};
			socket.emit('KVMRemoveBackupReq', rmbk);
			$(this).closest('tr').remove();
		}
	});
	socket.on('KVMRemoveBackupRes', function(res) {
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
			aid: $('#kvminfo').val(),
			volid: $(this).attr('content')
		};
		$('#confheader').html('');
		$('#confheader').html($(this).attr('content').split("/")[1]);
		socket.emit('KVMGetBackupConfReq', getcf);
	});
	socket.on('KVMGetBackupConfRes', function(res) {
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

	// KVM restore backup
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
				aid: $('#kvminfo').val(),
				snapname: $(this).attr('content')
			};
			socket.emit('KVMRestoreBackupReq', rsbk);
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
	socket.on('KVMRestoreBackupRes', function(res) {
		if(res == 'ok') {
			data.respond = true;
			$('#restore_output').html('Almost done. Saving configuration and cleaning up...');
		}else{
			data.respond = false
			$('#restore_output').html('An unexpected error occurred.');
		}
	});

	// KVM Rebuild
	$('#man_rebuild_modal').modal({
		backdrop: 'static',
		keyboard: false,
		show: false
	});
	$('#man_rebuild_btn').click(function(e) {
		data.respond = false;
		e.preventDefault();
		$('#man_os_error, #man_hostname_error').html('');
		$(this).prop('disabled', true);
		var os = $('#man_os').val();
		var hostname = $('#man_hostname').val();
		var aid = $('#man_aid').val();
		if(os == 'default') {
			$('#man_os_error').html('Error: no operating system was chosen.');
			$(this).prop('disabled', false);
		}else{
			if(isEmpty(hostname) || isBlank(hostname) || hostname.isEmpty() || !isDomain(hostname) || !isAlphaNum(hostname[hostname.length - 1])) {
				$('#man_hostname_error').html('Error: invalid hostname.');
				$(this).prop('disabled', false);
			}else{
				var newvm = {
					os: os,
					hostname: hostname,
					aid: aid
				};
				$('#hostname').val('');
				$('#man_os_error, #man_hostname_error').html('');
				var confirmed = confirm("WARNING: Rebuilding your VPS will delete ALL data it currently stores. Do you want to proceed?");
				if(confirmed === true) {
					socket.emit('KVMRebuildReq', newvm);
					$('#man_rebuild_modal').modal('show');
					$('.man_rebuild_progress').animate({
						width: "100%"
					}, 60000, 'swing', function() {
						$('.man_rebuild_progress').removeClass('progress-bar-info active');
						$('.man_rebuild_progress').addClass('progress-bar-success');
						$('.man_rebuild_progress').html('Complete!');
						setTimeout(function() {
							$('#man_rebuild_modal').modal('hide');
							window.location.href = '/index';
						}, 2500);
					});
				}else{
					$('#man_rebuild_btn').prop('disabled', false);
				}
			}
		}
	});
	socket.on('KVMRebuildRes', function(res) {
		if(res == 'ok') {
			data.respond = true;
			$('#man_rebuild_output').html('Almost done. Saving configuration and cleaning up...');
		}else{
			data.respond = false;
			$('#man_rebuild_output').html('An unexpected error occurred.');
		}
	});

	$('#man_rebuild_modal').modal({
		backdrop: 'static',
		keyboard: false,
		show: false
	});
	$('#temp_rebuild_btn').click(function(e) {
		data.respond = false;
		e.preventDefault();
		$('#temp_os_error, #temp_hostname_error').html('');
		$(this).prop('disabled', true);
		var os = $('#temp_os').val();
		var hostname = $('#temp_hostname').val();
		var aid = $('#temp_aid').val();
		if(os == 'default') {
			$('#temp_os_error').html('Error: no operating system was chosen.');
			$(this).prop('disabled', false);
		}else{
			if(isEmpty(hostname) || isBlank(hostname) || hostname.isEmpty() || !isDomain(hostname) || !isAlphaNum(hostname[hostname.length - 1])) {
				$('#temp_hostname_error').html('Error: invalid hostname.');
				$(this).prop('disabled', false);
			}else{
				var newvm = {
					os: os,
					hostname: hostname,
					aid: aid
				};
				$('#hostname').val('');
				$('#temp_os_error, #temp_hostname_error').html('');
				var confirmed = confirm("WARNING: Rebuilding your VPS will delete ALL data it currently stores. Do you want to proceed?");
				if(confirmed === true) {
					socket.emit('KVMRebuildTemplateReq', newvm);
					$('#man_rebuild_modal').modal('show');
					$('.man_rebuild_progress').animate({
						width: "100%"
					}, 60000, 'swing', function() {
						$('.man_rebuild_progress').removeClass('progress-bar-info active');
						$('.man_rebuild_progress').addClass('progress-bar-success');
						$('.man_rebuild_progress').html('Complete!');
						setTimeout(function() {
							$('#man_rebuild_modal').modal('hide');
							window.location.href = '/index';
						}, 2500);
					});
				}else{
					$('#temp_rebuild_btn').prop('disabled', false);
				}
			}
		}
	});
	socket.on('KVMRebuildTemplateRes', function(res) {
		if(res == 'ok') {
			data.respond = true;
			$('#man_rebuild_output').html('Almost done. Saving configuration and cleaning up...');
		}else{
			data.respond = false;
			$('#man_rebuild_output').html('An unexpected error occurred.');
		}
	});

	// KVM console
	$('#kvmconsole').click(function(e) {
		e.preventDefault();
		var id = $(this).attr('role');
		window.open("/console?id="+id+"&virt=kvm", "_blank", "height=580,width=820,status=yes,toolbar=no,menubar=no,location=no,addressbar=no");
	});

	// KVM resource graphs
	$('#day, #week, #month, #year').hide();
	var currentScale = '#hour';
	$('#graphtime').change(function() {
		var scale = $(this).val();
		$(''+currentScale).hide();
		$('#'+scale).show();
		currentScale = '#'+scale;
	});

	// KVM enable/disable onboot
	$('#enableonboot').click(function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var data = {
			aid: $('#kvminfo').val()
		};
		socket.emit('KVMEnableOnbootReq', data);
	});
	socket.on('KVMEnableOnbootRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}
	});

	// KVM enable/disable onboot
	$('#disableonboot').click(function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var data = {
			aid: $('#kvminfo').val()
		};
		socket.emit('KVMDisableOnbootReq', data);
	});
	socket.on('KVMDisableOnbootRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}
	});

	$('#disablerng').click(function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var data = {
			aid: $('#kvminfo').val()
		};
		socket.emit('KVMDisableRNGReq', data);
	});
	socket.on('KVMDisableRNGRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}
	});

	$('#enablerng').click(function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var data = {
			aid: $('#kvminfo').val()
		};
		socket.emit('KVMEnableRNGReq', data);
	});
	socket.on('KVMEnableRNGRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}
	});

	// KVM enable private network
	$('#enableprivatenet').click(function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var data = {
			aid: $('#kvminfo').val()
		};
		socket.emit('KVMEnablePrivateNetworkReq', data);
	});
	socket.on('KVMEnablePrivateNetworkRes', function(res) {
		window.location.reload();
	});

	// KVM disable private network
	$('#disableprivatenet').click(function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var data = {
			aid: $('#kvminfo').val()
		};
		socket.emit('KVMDisablePrivateNetworkReq', data);
	});
	socket.on('KVMDisablePrivateNetworkRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}
	});

	// KVM assign IPv6
	$('#assignipv6').click(function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var data = {
			aid: $('#kvminfo').val()
		};
		socket.emit('KVMAssignIPv6Req', data);
	});
	socket.on('KVMAssignIPv6Res', function(res) {
		window.location.reload();
	});

	// Cloud - assign IPv4 from pool
	$('#cloudassignip').click(function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var data = {
			aid: $('#kvminfo').val()
		};
		socket.emit('PubCloudAssignIPReq', data);
	});
	socket.on('PubCloudAssignIPRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}
	});

	// Cloud - remove IPv4 assignment
	$('#cloudremoveip').click(function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var data = {
			aid: $('#kvminfo').val(),
			ip: $(this).attr('role')
		};
		socket.emit('PubCloudRemoveIPReq', data);
	});
	socket.on('PubCloudRemoveIPRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}
	});

	// Cloud - delete VM
	$('#cldeletevmconfirm').modal({
		backdrop: 'static',
		keyboard: false,
		show: false
	});
	$('#cldeletevm').click(function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var details = {
			hb_account_id: $('#kvminfo').val(),
			clpoolid: $(this).attr('role')
		};
		socket.emit('PubCloudDeleteReq', details);
		$('#cldeletevmconfirm').modal('show');
	});
	socket.on('PubCloudDeleteRes', function(res) {
		if(res == 'ok') {
			$('#cldeletevmres').html('An email was sent to you containing a confirmation code for this deletion request.<br /><br /><input type="text" placeholder="Enter confirmation code" id="cldeletevmcode" class="form-control" /><br /><br /><button type="button" class="btn btn-md btn-success" id="clconfirmdelete">Confirm deletion request</button><br /><br /><br /><br /><button type="button" class="btn btn-md btn-danger" id="clcanceldelete">Cancel deletion request</button>');
		}else{
			$('#cldeletevmres').html('An error occurred while attempting to process your deletion request.');
		}
	});
	$('#cldeletevmres').on('click', '#clconfirmdelete', function(e) {
		data.respond = false;
		e.preventDefault();
		$(this).prop('disabled', true);
		$('#clcanceldelete').prop('disabled', true);
		$('#cldeletevmcode').prop('disabled', true);
		var details = {
			hb_account_id: $('#kvminfo').val(),
			clpoolid: $('#cldeletevm').attr('role'),
			delcode: $('#cldeletevmcode').val()
		};
		socket.emit('PubCloudConfirmDeleteReq', details);
		$('#cldeletevmres').append('<br /><br />Please wait...');
	});
	socket.on('PubCloudConfirmDeleteRes', function(res) {
		if(res == 'ok') {
			window.location.href = "/index";
		}else{
			$('#cldeletevmres').append('<br /><br />An error has occurred.');
			$('#clcanceldelete').prop('disabled', false);
		}
	});
	$('#cldeletevmres').on('click', '#clcanceldelete', function(e) {
		e.preventDefault();
		$(this).prop('disabled', true);
		var details = {
			hb_account_id: $('#kvminfo').val()
		};
		socket.emit('PubCloudCancelDeleteReq', details);
	});
	socket.on('PubCloudCancelDeleteRes', function(res) {
		if(res == 'ok') {
			$('#cldeletevmconfirm').modal('hide');
			$('#cldeletevm').prop('disabled', false);
		}else{
			$('#cldeletevmres').html('An error occurred while attempting to cancel this request.');
		}
	});

	// KVM - change ISO
	$('#changeiso').change(function() {
		if($(this).find(':selected').attr('role') != 'first') {
			$('#changeisosubmit').prop('disabled', false);
		}else{
			$('#changeisosubmit').prop('disabled', true);
		}
	});
	$('#changeisosubmit').click(function(e) {
		e.preventDefault();
		$(this).prop('disabled', true);
		var details = {
			hb_account_id: $('#kvminfo').val(),
			iso: $('#changeiso').val()
		};
		socket.emit('KVMChangeISOReq', details);
	});
	socket.on('KVMChangeISORes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}else{
			$('#changeisosubmit').html('Error');
		}
	});

	// KVM - change boot order
	$('#bosubmit').click(function(e) {
		e.preventDefault();
		$(this).prop('disabled', true);
		var details = {
			hb_account_id: $('#kvminfo').val(),
			bo1: $('#bo1').val(),
			bo2: $('#bo2').val(),
			bo3: $('#bo3').val()
		};
		socket.emit('KVMBootOrderReq', details);
	});
	socket.on('KVMBootOrderRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}else{
			$('#bosubmit').html('Error');
		}
	});

	$('#chgrootpw_kvm').click(function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var data = {
			aid: $('#kvminfo').val()
		};
		socket.emit('KVMChangePWReq', data);
	});
	socket.on('KVMChangePWRes', function(res) {
		if(res.status == 'ok') {
			$('#kvm_pwsuccess').html('<div class="alert alert-success" role="alert"><strong>New Root Password:</strong> '+res.password+'</div>');
		}else{
			$('#chgrootpw_kvm').html('Error');
			$('#kvm_pwsuccess').html('<div class="alert alert-danger" role="alert"><strong>Error:</strong> reset root password failed.</div>');
		}
	});

	// KVM get task log
	$('#getvmlog').click(function(e) {
		e.preventDefault();
		$(this).prop('disabled', true);
		var data = {
			aid: $('#kvminfo').val()
		};
		socket.emit('KVMGetLogReq', data);
	});
	$('#clearvmlog').click(function(e) {
		e.preventDefault();
		$('#vmlog').html('null');
	});
	socket.on('KVMGetLogRes', function(res) {
		if(res.status == 'ok') {
			$('#vmlog').html(res.log);
			$('#getvmlog').prop('disabled', false);
		}else{
			$('#vmlog').html('Error fetching log');
		}
	});

	if($('#template_setup_div').length) {
		location.reload(true);
	}

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
				socket.emit('KVMAddNATPortReq', newport);
			}
		}
	});
	socket.on('KVMAddNATPortRes', function(res) {
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
			aid: $('#kvminfo').val()
		};
		socket.emit('KVMDelNATPortReq', data);
	});
	socket.on('KVMDelNATPortRes', function(res) {
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
			socket.emit('KVMAddNATDomainReq', newdomain);
		}
	});
	socket.on('KVMAddNATDomainRes', function(res) {
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
			aid: $('#kvminfo').val()
		};
		socket.emit('KVMDelNATDomainReq', data);
	});
	socket.on('KVMDelNATDomainRes', function(res) {
		window.location.reload();
	});
})(jQuery);
$(document).ready(function() {
	if(typeof(Storage) !== "undefined" && (typeof(localStorage.getItem("proxcp_user_backup_progress")) == "string")) {
		pendingbackups = JSON.parse(atob(localStorage.getItem("proxcp_user_backup_progress")));
	}
	if(pendingbackups.length > 0) {
		$('#countsection').html('<button type="button" class="btn btn-md btn-warning btn-block" disabled="disabled" id="backup-warning">Create backup</button><span id="backup-warning-2">&nbsp;&nbsp;&nbsp;&nbsp;<small><em>Please wait...checking status of last backup.</em></small></span><br /><br />');
	}
});
