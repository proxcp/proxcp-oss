(function($) {
	$('#admin_usertable').DataTable();
	$('#admin_nodetable').DataTable();
	$('#admin_natnodetable').DataTable();
	$('#admin_nodelvl1table').DataTable();
	$('#admin_lxctable').DataTable();
	$('#admin_kvmtable').DataTable();
	$('#admin_lxctemptable').DataTable();
	$('#admin_kvmisotable').DataTable();
	$('#admin_acltable').DataTable();
	$('#admin_cloudtable').DataTable();
	$('#admin_domainstable').DataTable();
	$('#admin_recordstable').DataTable();
	$('#admin_ptrtable').DataTable();
	$('#admin_ip2table').DataTable();
	$('#admin_privatetable').DataTable();
	$('#admin_v6poolstable').DataTable();
	$('#admin_v6assigntable').DataTable();
	$('#admin_general_log').DataTable();
	$('#admin_admin_log').DataTable();
	$('#admin_error_log').DataTable();

	function uptimeCalc(sec) {
		var days = Math.floor(sec / 86400);
		var hours = Math.floor((sec % 86400) / 3600);
		var minutes = Math.floor(((sec % 86400) % 3600) / 60);
		var seconds = ((sec % 86400) % 3600) % 60;
		return (days + ' days ' + hours + ' hours ' + minutes + ' minutes ' + seconds + ' seconds');
	}

	// Account lock
	$('#admin_usertable').on('click', '[id^=acctlock]', function(e) {
		e.preventDefault();
		$(this).prop('disabled', true);
		var details = {
			id: $(this).attr('role'),
			by: $('#user').val()
		};
		socket.emit('ADMUserLockReq', details);
	});
	socket.on('ADMUserLockRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}
	});

	// Account unlock
	$('#admin_usertable').on('click', '[id^=acctunlock]', function(e) {
		e.preventDefault();
		$(this).prop('disabled', true);
		var details = {
			id: $(this).attr('role'),
			by: $('#user').val()
		};
		socket.emit('ADMUserUnlockReq', details);
	});
	socket.on('ADMUserUnlockRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}
	});

	// Account change password
	$('#admin_usertable').on('click', '[id^=acctpw]', function(e) {
		e.preventDefault();
		$(this).prop('disabled', true);
		var details = {
			id: $(this).attr('role'),
			by: $('#user').val()
		};
		socket.emit('ADMUserPWReq', details);
	});
	socket.on('ADMUserPWRes', function(res) {
		if(res.status == 'ok') {
			$('#adm_message').html('<div class="alert alert-success" role="alert"><strong>New Password:</strong> <input type="text" class="form-control" value="'+res.pw+'" /></div>');
		}else{
			$('#adm_message').html('<div class="alert alert-danger" role="alert"><strong>Error:</strong> unknown error has occurred.</div>');
		}
	});

	$('#admin_usertable').on('click', '[id^=resetbw]', function(e) {
		e.preventDefault();
		$(this).prop('disabled', true);
		var details = {
			id: $(this).attr('role'),
			by: $('#user').val()
		};
		socket.emit('ADMResetBWReq', details);
	});
	socket.on('ADMResetBWRes', function(res) {
		window.location.reload();
	});

	// Delete node
	$('#admin_nodetable').on('click', '[id^=admin_nodedelete]', function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var data = {
			id: $(this).attr('role'),
			by: $('#user').val()
		};
		socket.emit('ADMNodeDeleteReq', data);
	});
	socket.on('ADMNodeDeleteRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}else{
			$('#adm_message').html('<div class="alert alert-danger" role="alert"><strong>Error:</strong> could not delete node.</div>');
		}
	});

	$('#admin_nodetable').on('click', '[id^=admin_tuntapdelete]', function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var data = {
			id: $(this).attr('role'),
			by: $('#user').val()
		};
		socket.emit('ADMTunDeleteReq', data);
	});
	socket.on('ADMTunDeleteRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}else if(res == 'oknat') {
			$('#adm_message').html('<div class="alert alert-success" role="alert"><strong>Success:</strong> credentials have been deleted however NAT features will not work unless you add new credentials for this node.</div>');
		}else{
			$('#adm_message').html('<div class="alert alert-danger" role="alert"><strong>Error:</strong> could not delete credentials.</div>');
		}
	});

	$('#admin_nodetable').on('click', '[id^=admin_dhcpdelete]', function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var data = {
			id: $(this).attr('role'),
			by: $('#user').val()
		};
		socket.emit('ADMDHCPDeleteReq', data);
	});
	socket.on('ADMDHCPDeleteRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}else{
			$('#adm_message').html('<div class="alert alert-danger" role="alert"><strong>Error:</strong> could not delete DHCP server.</div>');
		}
	});

	// Delete user
	$('#admin_usertable').on('click', '[id^=admin_userdelete]', function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var confirmed = confirm("Are you sure you want to delete this user?");
		if(confirmed === true) {
			var data = {
				id: $(this).attr('role'),
				by: $('#user').val()
			};
			socket.emit('ADMUserDeleteReq', data);
		}else{
			$(this).prop("disabled", false);
			return;
		}
	});
	socket.on('ADMUserDeleteRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}else{
			$('#adm_message').html('<div class="alert alert-danger" role="alert"><strong>Error:</strong> could not delete user.</div>');
		}
	});

	$('#natnode').change(function() {
		if($(this).val() != 'default') {
			socket.emit('ADMQueryNATDNSReq', {
				id: $('#natnode option:selected').text(),
				by: $('#user').val()
			});
		}else{
			$('#natnodeip').val('');
		}
	});
	socket.on('ADMQueryNATDNSRes', function(res) {
		if(res.status == 'ok') {
			$('#natnodeip').val(res.ipv4);
		}else{
			$('#adm_message').html('<div class="alert alert-danger" role="alert"><strong>Error:</strong> could not resolve node hostname (DNS A record IPv4).</div>');
		}
	});

	// Create LXC - get storage locations
	$('#node').change(function() {
		$('#storage_location').html('<option value="default">Select...</option>');
		if($(this).val() != 'default') {
			socket.emit('ADMQueryStorageReq', {
				id: $(this).val(),
				by: $('#user').val()
			});
		}else{
			$('#storage_location').html('<option value="default">Select...</option>');
		}
	});
	socket.on('ADMQueryStorageRes', function(res) {
		if(res.status == 'ok') {
			$.each(res.locs, function(i, item) {
				$('#storage_location').append($('<option>', {
					value: item,
					text: item
				}));
			});
		}else{
			$('#adm_message').html('<div class="alert alert-danger" role="alert"><strong>Error:</strong> could not query node storage.</div>');
		}
	});

	$('#os_installation_type').change(function() {
		if($(this).val() == 'iso') {
			$('#admin_createkvm_iso').show();
			$('#admin_createkvm_next').show();
			$('#admin_createkvm_template').hide();
		}else if($(this).val() == 'template') {
			$('#admin_createkvm_template').show();
			$('#admin_createkvm_next').show();
			$('#admin_createkvm_iso').hide();
		}else{
			$('#admin_createkvm_iso').hide();
			$('#admin_createkvm_template').hide();
			$('#admin_createkvm_next').hide();
		}
	});

	$('#lxcisnat').change(function() {
		if($(this).val() == 'true') {
			$('#lxcnatfields').show();
			$('#ipv4').attr("placeholder", "1.1.1.5/CIDR, must be within NAT range");
		}else{
			$('#lxcnatfields').hide();
			$('#ipv4').attr("placeholder", "1.1.1.5/CIDR");
		}
	});

	$('#kvmisnat').change(function() {
		if($(this).val() == 'true') {
			$('#kvmnatfields').show();
			$('#ipv4').attr("placeholder", "1.1.1.5, must be within NAT range");
		}else{
			$('#kvmnatfields').hide();
			$('#ipv4').attr("placeholder", "1.1.1.5");
		}
	});

	// Delete LXC - DB only
	$('#admin_lxctable').on('click', '[id^=admin_lxcdelete]', function(e) {
		var r = confirm("== MANUAL DELETION ==\n\nRemoval from ProxCP only. Pools, users, and/or VMs may need to be manually removed from Proxmox too!");
		if(r == true) {
			e.preventDefault();
			$(this).prop("disabled", true);
			var data = {
				id: $(this).attr('role'),
				by: $('#user').val()
			};
			socket.emit('ADMLXCDeleteReq', data);
		}else{
			return false;
		}
	});
	socket.on('ADMLXCDeleteRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}else{
			$('#adm_message').html('<div class="alert alert-danger" role="alert"><strong>Error:</strong> could not delete LXC from database.</div>');
		}
	});

	// Get node stats
	$('#selectnodestats').change(function() {
		if($(this).val() != 'default') {
			socket.emit('ADMQueryNodesReq', {
				id: $(this).val(),
				by: $('#user').val()
			});
		}
		if(typeof(Storage) !== "undefined") {
			localStorage.setItem("proxcp_last_node_selection", btoa($(this).val()));
		}
	});
	socket.on('ADMQueryNodesRes', function(res) {
		if(res.status == 'ok') {
			$('#admin_nodestatus2').removeClass('label-danger').addClass('label-success').html('<i class="fa fa-check"></i> Online');
			var cpu = Math.round(res.cpuusage * 100);
			var ram = Math.round((res.ramusage.used / res.ramusage.total) * 100);
			var disk = Math.round((res.diskusage.used / res.diskusage.total) * 100);
			var swap = Math.round((res.swapusage.used / res.swapusage.total) * 100) || 0;
			$('#admin_cpu_1').attr('aria-valuenow', cpu);
			$('#admin_cpu_1').css('width', cpu + '%');
			$('#admin_cpu_2').html(cpu + '%');
			if(cpu <= 33) {
				$('#admin_cpu_1').removeClass('progress-bar-info');
				$('#admin_cpu_1').removeClass('progress-bar-warning');
				$('#admin_cpu_1').removeClass('progress-bar-danger');
				$('#admin_cpu_1').addClass('progress-bar-success');
			}else if(cpu >= 34 && cpu <= 66) {
				$('#admin_cpu_1').removeClass('progress-bar-info');
				$('#admin_cpu_1').removeClass('progress-bar-success');
				$('#admin_cpu_1').removeClass('progress-bar-danger');
				$('#admin_cpu_1').addClass('progress-bar-warning');
			}else{
				$('#admin_cpu_1').removeClass('progress-bar-info');
				$('#admin_cpu_1').removeClass('progress-bar-warning');
				$('#admin_cpu_1').removeClass('progress-bar-success');
				$('#admin_cpu_1').addClass('progress-bar-danger');
			}
			$('#admin_ram_1').attr('aria-valuenow', ram);
			$('#admin_ram_1').css('width', ram + '%');
			$('#admin_ram_2').html(ram + '%');
			if(ram <= 33) {
				$('#admin_ram_1').removeClass('progress-bar-info');
				$('#admin_ram_1').removeClass('progress-bar-warning');
				$('#admin_ram_1').removeClass('progress-bar-danger');
				$('#admin_ram_1').addClass('progress-bar-success');
			}else if(ram >= 34 && ram <= 66) {
				$('#admin_ram_1').removeClass('progress-bar-info');
				$('#admin_ram_1').removeClass('progress-bar-success');
				$('#admin_ram_1').removeClass('progress-bar-danger');
				$('#admin_ram_1').addClass('progress-bar-warning');
			}else{
				$('#admin_ram_1').removeClass('progress-bar-info');
				$('#admin_ram_1').removeClass('progress-bar-warning');
				$('#admin_ram_1').removeClass('progress-bar-success');
				$('#admin_ram_1').addClass('progress-bar-danger');
			}
			$('#admin_disk_1').attr('aria-valuenow', disk);
			$('#admin_disk_1').css('width', disk + '%');
			$('#admin_disk_2').html(disk + '%');
			if(disk <= 33) {
				$('#admin_disk_1').removeClass('progress-bar-info');
				$('#admin_disk_1').removeClass('progress-bar-warning');
				$('#admin_disk_1').removeClass('progress-bar-danger');
				$('#admin_disk_1').addClass('progress-bar-success');
			}else if(disk >= 34 && disk <= 66) {
				$('#admin_disk_1').removeClass('progress-bar-info');
				$('#admin_disk_1').removeClass('progress-bar-success');
				$('#admin_disk_1').removeClass('progress-bar-danger');
				$('#admin_disk_1').addClass('progress-bar-warning');
			}else{
				$('#admin_disk_1').removeClass('progress-bar-info');
				$('#admin_disk_1').removeClass('progress-bar-warning');
				$('#admin_disk_1').removeClass('progress-bar-success');
				$('#admin_disk_1').addClass('progress-bar-danger');
			}
			$('#admin_swap_1').attr('aria-valuenow', swap);
			$('#admin_swap_1').css('width', swap + '%');
			$('#admin_swap_2').html(swap + '%');
			if(swap <= 33) {
				$('#admin_swap_1').removeClass('progress-bar-info');
				$('#admin_swap_1').removeClass('progress-bar-warning');
				$('#admin_swap_1').removeClass('progress-bar-danger');
				$('#admin_swap_1').addClass('progress-bar-success');
			}else if(swap >= 34 && swap <= 66) {
				$('#admin_swap_1').removeClass('progress-bar-info');
				$('#admin_swap_1').removeClass('progress-bar-success');
				$('#admin_swap_1').removeClass('progress-bar-danger');
				$('#admin_swap_1').addClass('progress-bar-warning');
			}else{
				$('#admin_swap_1').removeClass('progress-bar-info');
				$('#admin_swap_1').removeClass('progress-bar-warning');
				$('#admin_swap_1').removeClass('progress-bar-success');
				$('#admin_swap_1').addClass('progress-bar-danger');
			}
			$('#node_uptime').html(uptimeCalc(res.uptime));
			$('#node_loadavg').html(res.loadavg.toString());
			$('#node_kernel').html(res.kernel);
			$('#node_pve').html(res.pve);
			$('#node_cpumod').html(res.cpumod);
		}else{
			$('#admin_nodestatus2').removeClass('label-success').addClass('label-danger').html('<i class="fa fa-times"></i> Offline');
			$('#adm_message').html('<div class="alert alert-danger" role="alert"><strong>Error:</strong> could not query node stats.</div>');
		}
	});

	// LXC suspend
	$('#admin_lxctable').on('click', '[id^=lxcsuspend]', function(e) {
		e.preventDefault();
		$(this).prop('disabled', true);
		var details = {
			id: $(this).attr('role'),
			by: $('#user').val()
		};
		socket.emit('ADMLXCSuspendReq', details);
	});
	socket.on('ADMLXCSuspendRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}
	});

	// LXC unsuspend
	$('#admin_lxctable').on('click', '[id^=lxcunsuspend]', function(e) {
		e.preventDefault();
		$(this).prop('disabled', true);
		var details = {
			id: $(this).attr('role'),
			by: $('#user').val()
		};
		socket.emit('ADMLXCUnsuspendReq', details);
	});
	socket.on('ADMLXCUnsuspendRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}
	});

	// KVM suspend
	$('#admin_kvmtable').on('click', '[id^=kvmsuspend]', function(e) {
		e.preventDefault();
		$(this).prop('disabled', true);
		var details = {
			id: $(this).attr('role'),
			by: $('#user').val()
		};
		socket.emit('ADMKVMSuspendReq', details);
	});
	socket.on('ADMKVMSuspendRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}
	});

	// KVM unsuspend
	$('#admin_kvmtable').on('click', '[id^=kvmunsuspend]', function(e) {
		e.preventDefault();
		$(this).prop('disabled', true);
		var details = {
			id: $(this).attr('role'),
			by: $('#user').val()
		};
		socket.emit('ADMKVMUnsuspendReq', details);
	});
	socket.on('ADMKVMUnsuspendRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}
	});

	// Delete KVM - DB only
	$('#admin_kvmtable').on('click', '[id^=admin_kvmdelete]', function(e) {
		var r = confirm("== MANUAL DELETION ==\n\nRemoval from ProxCP only. Pools, users, and/or VMs may need to be manually removed from Proxmox too!");
		if(r == true) {
			e.preventDefault();
			$(this).prop("disabled", true);
			var data = {
				id: $(this).attr('role'),
				by: $('#user').val()
			};
			socket.emit('ADMKVMDeleteReq', data);
		}else{
			return false;
		}
	});
	socket.on('ADMKVMDeleteRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}else{
			$('#adm_message').html('<div class="alert alert-danger" role="alert"><strong>Error:</strong> could not delete KVM from database.</div>');
		}
	});

	// Delete LXC template - DB only
	$('#admin_lxctemptable').on('click', '[id^=admin_lxctempdelete]', function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var data = {
			id: $(this).attr('role'),
			by: $('#user').val()
		};
		socket.emit('ADMLXCTempDeleteReq', data);
	});
	socket.on('ADMLXCTempDeleteRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}else{
			$('#adm_message').html('<div class="alert alert-danger" role="alert"><strong>Error:</strong> could not delete LXC template from database.</div>');
		}
	});

	$('#admin_lxctemptable').on('click', '[id^=admin_apidelete]', function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var data = {
			id: $(this).attr('role'),
			by: $('#user').val()
		};
		socket.emit('ADMAPIDeleteReq', data);
	});
	socket.on('ADMAPIDeleteRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}else{
			$('#adm_message').html('<div class="alert alert-danger" role="alert"><strong>Error:</strong> could not delete API pair from database.</div>');
		}
	});

	$('#admin_lxctemptable').on('click', '[id^=admin_kvmtempdelete]', function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var data = {
			id: $(this).attr('role'),
			by: $('#user').val()
		};
		socket.emit('ADMKVMTempDeleteReq', data);
	});
	socket.on('ADMKVMTempDeleteRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}else{
			$('#adm_message').html('<div class="alert alert-danger" role="alert"><strong>Error:</strong> could not delete KVM template from database.</div>');
		}
	});

	// Delete KVM ISO - DB only
	$('#admin_kvmisotable').on('click', '[id^=admin_kvmisodelete]', function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var data = {
			id: $(this).attr('role'),
			by: $('#user').val()
		};
		socket.emit('ADMKVMISODeleteReq', data);
	});
	socket.on('ADMKVMISODeleteRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}else{
			$('#adm_message').html('<div class="alert alert-danger" role="alert"><strong>Error:</strong> could not delete KVM ISO from database.</div>');
		}
	});

	$('#admin_kvmisotable').on('click', '[id^=admin_kvmcustomisodelete]', function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var data = {
			id: $(this).attr('role'),
			by: $('#user').val()
		};
		socket.emit('ADMKVMCustomISODeleteReq', data);
	});
	socket.on('ADMKVMCustomISODeleteRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}else{
			$('#adm_message').html('<div class="alert alert-danger" role="alert"><strong>Error:</strong> could not delete KVM custom ISO.</div>');
		}
	});

	// Delete user ACL
	$('#admin_acltable').on('click', '[id^=admin_acldelete]', function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var data = {
			id: $(this).attr('role'),
			by: $('#user').val()
		};
		socket.emit('ADMACLDeleteReq', data);
	});
	socket.on('ADMACLDeleteRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}else{
			$('#adm_message').html('<div class="alert alert-danger" role="alert"><strong>Error:</strong> could not delete user ACL from database.</div>');
		}
	});

	// Edit cloud account form
	$('#getcloudhbid').change(function() {
		if($(this).val() != 'default') {
			socket.emit('ADMQueryCloudReq', {
				id: $(this).val(),
				by: $('#user').val()
			});
		}else{
			$('#getipv4').val('');
			$('#getipv4_avail').val('');
			$('#getcpucores').val('');
			$('#getcpucores_avail').val('');
			$('#getram').val('');
			$('#getram_avail').val('');
			$('#getstorage_size').val('');
			$('#getstorage_size_avail').val('');
		}
	});
	socket.on('ADMQueryCloudRes', function(res) {
		if(res.status == 'ok') {
			$('#getipv4').val(res.ipv4);
			$('#getipv4_avail').val(res.ipv4_avail);
			$('#getcpucores').val(res.cpucores);
			$('#getcpucores_avail').val(res.cpucores_avail);
			$('#getram').val(res.ram);
			$('#getram_avail').val(res.ram_avail);
			$('#getstorage_size').val(res.storage_size);
			$('#getstorage_size_avail').val(res.storage_size_avail);
		}else{
			$('#adm_message').html('<div class="alert alert-danger" role="alert"><strong>Error:</strong> could not query cloud account.</div>');
		}
	});
	$('#editcloudaccount').click(function(e) {
		var r = confirm("== MANUAL EDITING ==\n\nEditing the account in ProxCP only. Pools, users, and/or VMs may need to be manually changed within Proxmox too!");
		if(r == true) {
			e.preventDefault();
			$(this).prop("disabled", true);
			var data = {
				by: $('#user').val(),
				id: $('#getcloudhbid').val(),
				ipv4: $('#getipv4').val(),
				ipv4_avail: $('#getipv4_avail').val(),
				cpucores: $('#getcpucores').val(),
				cpucores_avail: $('#getcpucores_avail').val(),
				ram: $('#getram').val(),
				ram_avail: $('#getram_avail').val(),
				storage_size: $('#getstorage_size').val(),
				storage_size_avail: $('#getstorage_size_avail').val()
			};
			socket.emit('ADMEditCloudReq', data);
		}else{
			return false;
		}
	});
	socket.on('ADMEditCloudRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
			window.scrollTo(0, 0);
		}else{
			$('#adm_message').html('<div class="alert alert-danger" role="alert"><strong>Error:</strong> could not edit cloud account.</div>');
			window.scrollTo(0, 0);
		}
	});

	// KVM suspend cloud
	$('#admin_cloudtable').on('click', '[id^=cloudsuspend]', function(e) {
		e.preventDefault();
		$(this).prop('disabled', true);
		var details = {
			id: $(this).attr('role'),
			by: $('#user').val()
		};
		socket.emit('ADMCloudSuspendReq', details);
	});
	socket.on('ADMCloudSuspendRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}
	});

	// KVM unsuspend cloud
	$('#admin_cloudtable').on('click', '[id^=cloudunsuspend]', function(e) {
		e.preventDefault();
		$(this).prop('disabled', true);
		var details = {
			id: $(this).attr('role'),
			by: $('#user').val()
		};
		socket.emit('ADMCloudUnsuspendReq', details);
	});
	socket.on('ADMCloudUnsuspendRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}
	});

	// Delete cloud - DB only
	$('#admin_cloudtable').on('click', '[id^=admin_clouddelete]', function(e) {
		var r = confirm("== MANUAL DELETION ==\n\nRemoval from ProxCP only. Pools, users, and/or VMs may need to be manually removed from Proxmox too!");
		if(r == true) {
			e.preventDefault();
			$(this).prop("disabled", true);
			var data = {
				id: $(this).attr('role'),
				by: $('#user').val()
			};
			socket.emit('ADMCloudDeleteReq', data);
		}else{
			window.location.reload();
		}
	});
	socket.on('ADMCloudDeleteRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}else{
			$('#adm_message').html('<div class="alert alert-danger" role="alert"><strong>Error:</strong> could not delete cloud account from database.</div>');
		}
	});

	// edit vm props select
	$('#queryvmprops').change(function() {
		if($(this).val() != 'default') {
			socket.emit('ADMQueryPropsReq', {
				id: $(this).val(),
				by: $('#user').val()
			});
		}else{
			$('#userid').val('');
			$('#vmnode').val('');
			$('#vmos').val('');
			$('#vmip').val('');
			$('#vmip_old').val('');
			$('#vmip_gateway').val('');
			$('#vmip_netmask').val('');
			$('#vm_backups').val('');
			$('#vm_poolname').val('');
			$('#vm_poolpw').val('');
			$('#vm_backup_override').val('');
		}
	});
	socket.on('ADMQueryPropsRes', function(res) {
		if(res.status == 'ok') {
			$('#userid').val(res.userid);
			$('#vmnode').val(res.vmnode);
			$('#vmos').val(res.vmos);
			$('#vmip').val(res.vmip);
			$('#vmip_old').val(res.vmip);
			$('#vmip_gateway').val(res.vmip_gateway);
			$('#vmip_netmask').val(res.vmip_netmask);
			$('#vm_backups').val(res.backups);
			$('#vm_poolname').val(res.poolname);
			$('#vm_backup_override').val(res.override);
		}else{
			$('#adm_message').html('<div class="alert alert-danger" role="alert"><strong>Error:</strong> could not query current VM properties from database.</div>');
		}
	});

	// Delete dns record - DB only
	$('#admin_recordstable').on('click', '[id^=admin_recorddelete]', function(e) {
		var r = confirm("== MANUAL DELETION ==\n\nRemoval from ProxCP only. Further action may be required in WHM too!");
		if(r == true) {
			e.preventDefault();
			$(this).prop("disabled", true);
			var data = {
				id: $(this).attr('role'),
				by: $('#user').val()
			};
			socket.emit('ADMRecordDeleteReq', data);
		}else{
			return false;
		}
	});
	socket.on('ADMRecordDeleteRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}else{
			$('#adm_message').html('<div class="alert alert-danger" role="alert"><strong>Error:</strong> could not delete DNS record from database.</div>');
		}
	});

	// Delete domain - DB only
	$('#admin_domainstable').on('click', '[id^=admin_domaindelete]', function(e) {
		var r = confirm("== MANUAL DELETION ==\n\nRemoval from ProxCP only. Further action may be required in WHM too!");
		if(r == true) {
			e.preventDefault();
			$(this).prop("disabled", true);
			var data = {
				id: $(this).attr('role'),
				by: $('#user').val()
			};
			socket.emit('ADMDomainDeleteReq', data);
		}else{
			return false;
		}
	});
	socket.on('ADMDomainDeleteRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}else{
			$('#adm_message').html('<div class="alert alert-danger" role="alert"><strong>Error:</strong> could not delete domain from database.</div>');
		}
	});

	// Delete ptr - DB only
	$('#admin_ptrtable').on('click', '[id^=admin_ptrdelete]', function(e) {
		var r = confirm("== MANUAL DELETION ==\n\nRemoval from ProxCP only. Further action may be required in WHM too!");
		if(r == true) {
			e.preventDefault();
			$(this).prop("disabled", true);
			var data = {
				id: $(this).attr('role'),
				by: $('#user').val()
			};
			socket.emit('ADMPTRDeleteReq', data);
		}else{
			return false;
		}
	});
	socket.on('ADMPTRDeleteRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}else{
			$('#adm_message').html('<div class="alert alert-danger" role="alert"><strong>Error:</strong> could not delete PTR from database.</div>');
		}
	});

	// Delete ip2 - DB only
	$('#admin_ip2table').on('click', '[id^=admin_ip2delete]', function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var data = {
			id: $(this).attr('role'),
			by: $('#user').val()
		};
		socket.emit('ADMIP2DeleteReq', data);
	});
	socket.on('ADMIP2DeleteRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}else{
			$('#adm_message').html('<div class="alert alert-danger" role="alert"><strong>Error:</strong> could not delete secondary IP from database.</div>');
		}
	});

	// Clear private assignment
	$('#admin_privatetable').on('click', '[id^=admin_privatedelete]', function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var data = {
			id: $(this).attr('role'),
			by: $('#user').val()
		};
		socket.emit('ADMPrivateDeleteReq', data);
	});
	socket.on('ADMPrivateDeleteRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}else{
			$('#adm_message').html('<div class="alert alert-danger" role="alert"><strong>Error:</strong> could not clear private assignment from database.</div>');
		}
	});

	$('#admin_privatetable').on('click', '[id^=admin_publicdelete]', function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var data = {
			id: $(this).attr('role'),
			by: $('#user').val()
		};
		socket.emit('ADMPublicDeleteReq', data);
	});
	socket.on('ADMPublicDeleteRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}else{
			$('#adm_message').html('<div class="alert alert-danger" role="alert"><strong>Error:</strong> could not clear public assignment from database.</div>');
		}
	});

	$('#admin_privatetable').on('click', '[id^=admin_publicclr]', function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var data = {
			id: $(this).attr('role'),
			by: $('#user').val()
		};
		socket.emit('ADMPublicClrReq', data);
	});
	socket.on('ADMPublicClrRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}else{
			$('#adm_message').html('<div class="alert alert-danger" role="alert"><strong>Error:</strong> could not delete IP from database.</div>');
		}
	});

	$('#admin_privatetable').on('click', '[id^=admin_setip]', function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		$('#admin_ipseti' + $(this).attr('role')).prop("readonly", true);
		var data = {
			id: $(this).attr('role'),
			hbid: $('#admin_ipseti' + $(this).attr('role')).val(),
			by: $('#user').val()
		};
		socket.emit('ADMSetIPReq', data);
	});
	socket.on('ADMSetIPRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}else{
			$('#adm_message').html('<div class="alert alert-danger" role="alert"><strong>Error:</strong> could not assign IP in database.</div>');
		}
	});

	// Delete IPv6 assignment
	$('#admin_v6assigntable').on('click', '[id^=admin_v6assigndelete]', function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var data = {
			id: $(this).attr('role'),
			by: $('#user').val()
		};
		socket.emit('ADMIPv6AssignDeleteReq', data);
	});
	socket.on('ADMIPv6AssignDeleteRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}else{
			$('#adm_message').html('<div class="alert alert-danger" role="alert"><strong>Error:</strong> could not delete IPv6 assignment from database.</div>');
		}
	});

	// Delete IPv6 pool
	$('#admin_v6poolstable').on('click', '[id^=admin_v6pooldelete]', function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var data = {
			id: $(this).attr('role'),
			by: $('#user').val()
		};
		socket.emit('ADMIPv6PoolDeleteReq', data);
	});
	socket.on('ADMIPv6PoolDeleteRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}else{
			$('#adm_message').html('<div class="alert alert-danger" role="alert"><strong>Error:</strong> could not delete IPv6 pool from database.</div>');
		}
	});

	$('form#adm_createkvm_form').submit(function() {
		$(this).find(':input[type=submit]').prop("disabled", true).val('PLEASE WAIT...STAY ON THIS PAGE UNTIL COMPLETED');
	});

	$('form#adm_createlxc_form').submit(function() {
		$(this).find(':input[type=submit]').prop("disabled", true).val('PLEASE WAIT...STAY ON THIS PAGE UNTIL COMPLETED');
	});

	$('form#adm_natnode_form').submit(function() {
		$(this).find(':input[type=submit]').prop("disabled", true).val('PLEASE WAIT...STAY ON THIS PAGE UNTIL COMPLETED');
	});

	var restbl = null;
	$('#natnode_lvl1').on('show.bs.modal', function (event) {
	  var button = $(event.relatedTarget);
	  var node = button.data('node');
	  var modal = $(this);
	  modal.find('.modal-title').text('Detailed View - ' + node);
	  var data = {
			id: node,
			by: $('#user').val()
		};
		$('#lvl1_error').html('');
		restbl = null;
		$('#admin_nodelvl1table').DataTable().clear().draw();
		socket.emit('ADMQueryLvl1Req', data);
	});
	socket.on('ADMQueryLvl1Res', function(res) {
		if(res.status == 'ok') {
			restbl = res.tbl;
			$('#lvl1_error').html('');
			for(var i = 0; i < res.tbl.length; i++) {
				var tp = res.tbl[i].ports.split(";").length - 1;
				var td = res.tbl[i].domains.split(";").length - 1;
				$('#admin_nodelvl1table').DataTable().row.add([res.tbl[i].username, res.tbl[i].hb_account_id, tp + ' / ' + res.tbl[i].avail_ports, td + ' / ' + res.tbl[i].avail_domains]);
			}
			$('#admin_nodelvl1table').DataTable().draw();
			$('#admin_nodelvl1table tbody tr').css('cursor', 'pointer');
		}else{
			$('#admin_nodelvl1table').DataTable().clear().draw();
			restbl = null;
			$('#lvl1_error').html('<div class="alert alert-danger" role="alert"><strong>Error:</strong> could not query NAT stats - level 1.</div>');
		}
	});
	$('#admin_nodelvl1table tbody').on('click', 'tr', function() {
		var trd = $('#admin_nodelvl1table').DataTable().row(this).data();
		var trd_i = null;
		if(trd != undefined) {
			trd_i = trd[1];
		}
		var trd_ii = null;
		for(var i = 0; i < restbl.length; i++) {
			if(restbl[i].hb_account_id == trd_i) {
				trd_ii = restbl[i];
				break;
			}
		}
		if(trd_ii == null && trd_i != null) {
			$.confirm({
				title: '<span style="cursor:move;"><strong>Error</strong></span>',
				content: 'Could not find NAT information for the selected virtual machine.',
				draggable: true,
				buttons: {
					close: {
						text: 'Close',
						btnClass: 'btn btn-info',
						keys: [],
						isHidden: false,
						isDisabled: false,
						action: function(closeButton) {
							return true;
						}
					}
				},
				type: 'red',
				icon: 'fa fa-info-circle',
				containerFluid: true,
				columnClass: 'large'
			});
		}else if(trd_i == null) {
			$.confirm({
				title: '<span style="cursor:move;"><strong>Info</strong></span>',
				content: 'No information available.',
				draggable: true,
				buttons: {
					close: {
						text: 'Close',
						btnClass: 'btn btn-info',
						keys: [],
						isHidden: false,
						isDisabled: false,
						action: function(closeButton) {
							return true;
						}
					}
				},
				type: 'blue',
				icon: 'fa fa-info-circle',
				containerFluid: true,
				columnClass: 'large'
			});
		}else{
			var d = trd_ii.domains.split(";");
			var p = trd_ii.ports.split(";");
			var trd_d = '<ul>';
			for(var i = 0; i < d.length - 1; i++) {
				trd_d += '<li>'+d[i]+'</li>';
			}
			trd_d += '</ul>';
			var trd_p = '<ul>';
			for(var i = 0; i < p.length - 1; i++) {
				var t = p[i].split(":");
				trd_p += '<li>'+t[1]+' <i class="fa fa-arrow-right"></i> '+t[2]+' : '+t[3]+'</li>';
			}
			trd_p += '</ul>';
			$.confirm({
				title: '<span style="cursor:move;"><strong>Billing ID ' + trd_i + ': NAT Domains and Ports</strong></span>',
				content: '<div class="col-md-6"><div class="panel panel-default"><div class="panel-heading"><h3 class="panel-title">NAT Domains</h3></div><div class="panel-body">'+trd_d+'</div></div></div><div class="col-md-6"><div class="panel panel-default"><div class="panel-heading"><h3 class="panel-title">NAT Ports</h3></div><div class="panel-body">'+trd_p+'</div></div></div>',
				draggable: true,
				buttons: {
					close: {
						text: 'Close',
						btnClass: 'btn btn-info',
						keys: [],
						isHidden: false,
						isDisabled: false,
						action: function(closeButton) {
							return true;
						}
					}
				},
				type: 'blue',
				icon: 'fa fa-info-circle',
				containerFluid: true,
				columnClass: 'large'
			});
		}
	});
})(jQuery);
$(document).ready(function() {
	if(typeof(Storage) !== "undefined" && (typeof(localStorage.getItem("proxcp_last_node_selection")) == "string" && (atob(localStorage.getItem("proxcp_last_node_selection")) != "undefined" || atob(localStorage.getItem("proxcp_last_node_selection")) != "default"))) {
		$('#selectnodestats').val(atob(localStorage.getItem("proxcp_last_node_selection"))).change();
	}
});
