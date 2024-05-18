(function($) {
	// KVM firewall options
	$('#fwoptionssave').click(function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var data = {
			aid: $('#kvminfo').val(),
			enable: $('#enableopts').val(),
			policy_in: $('#policyinopts').val(),
			policy_out: $('#policyoutopts').val(),
			log_level_in: $('#levelinopts').val(),
			log_level_out: $('#leveloutopts').val()
		};
		socket.emit('KVMFirewallOptionsReq', data);
	});
	socket.on('KVMFirewallOptionsRes', function(res) {
		if(res == 'ok') {
			$('#fwoptionssave').prop("disabled", false);
			$('#fwoptions').modal('hide');
			window.location.reload();
		}else{
			$('#fwoptionssave').html('An unexpected error has occurred.');
		}
	});

	// KVM firewall add rule
	$('#fwrulessave').click(function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var data = {
			aid: $('#kvminfo').val(),
			enable: $('#a').val(),
			iface: $('#iface').val(),
			type: $('#b').val(),
			action: $('#c').val(),
			source: $('#d').val(),
			sport: $('#e').val(),
			dest: $('#f').val(),
			dport: $('#g').val(),
			proto: $('#h').val(),
			comment: $('#i').val()
		};
		socket.emit('KVMFirewallRuleReq', data);
	});
	socket.on('KVMFirewallRuleRes', function(res) {
		if(res == 'ok') {
			$('#fwrulessave').prop("disabled", true);
			$('#addfwrule').modal('hide');
			window.location.reload();
		}else{
			$('#fwrulessave').html('An unexpected error has occurred.');
		}
	});

	$('[id^=fwredit]').click(function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var p = $(this).attr('role');
		var data = {
			aid: $('#kvminfo').val(),
			pos: p,
			enable: $('#a' + p).val(),
			iface: $('#iface' + p).val(),
			type: $('#b' + p).val(),
			action: $('#c' + p).val(),
			source: $('#d' + p).val(),
			sport: $('#e' + p).val(),
			dest: $('#f' + p).val(),
			dport: $('#g' + p).val(),
			proto: $('#h' + p).val(),
			comment: $('#i' + p).val()
		};
		socket.emit('KVMFirewallEditReq', data);
	});
	socket.on('KVMFirewallEditRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}
	});

	// KVM firewall remove rule
	$('[id^=fwremove]').click(function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var data = {
			aid: $('#kvminfo').val(),
			pos: $(this).attr('role')
		};
		socket.emit('KVMFirewallRemoveReq', data);
	});
	socket.on('KVMFirewallRemoveRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}
	});

	// KVM enable/disable fw interface net0
	$('#fwifacepub').click(function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var data = {
			aid: $('#kvminfo').val(),
			action: $(this).attr('role')
		};
		socket.emit('KVMIfaceNet0Req', data);
	});
	socket.on('KVMIfaceNet0Res', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}
	});

	// KVM enable/disable fw interface net1
	$('#fwifacepriv').click(function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var data = {
			aid: $('#kvminfo').val(),
			action: $(this).attr('role')
		};
		socket.emit('KVMIfaceNet1Req', data);
	});
	socket.on('KVMIfaceNet1Res', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}
	});
})(jQuery);
