(function($) {
	// LXC firewall options
	$('#fwoptionssave').click(function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var data = {
			aid: $('#lxcinfo').val(),
			enable: $('#enableopts').val(),
			policy_in: $('#policyinopts').val(),
			policy_out: $('#policyoutopts').val(),
			log_level_in: $('#levelinopts').val(),
			log_level_out: $('#leveloutopts').val()
		};
		socket.emit('LXCFirewallOptionsReq', data);
	});
	socket.on('LXCFirewallOptionsRes', function(res) {
		if(res == 'ok') {
			$('#fwoptionssave').prop("disabled", false);
			$('#fwoptions').modal('hide');
			window.location.reload();
		}else{
			$('#fwoptionssave').html('An unexpected error has occurred.');
		}
	});

	// LXC firewall add rule
	$('#fwrulessave').click(function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var data = {
			aid: $('#lxcinfo').val(),
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
		socket.emit('LXCFirewallRuleReq', data);
	});
	socket.on('LXCFirewallRuleRes', function(res) {
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
		socket.emit('LXCFirewallEditReq', data);
	});
	socket.on('LXCFirewallEditRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}
	});

	// LXC firewall remove rule
	$('[id^=fwremove]').click(function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var data = {
			aid: $('#lxcinfo').val(),
			pos: $(this).attr('role')
		};
		socket.emit('LXCFirewallRemoveReq', data);
	});
	socket.on('LXCFirewallRemoveRes', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}
	});

	// LXC enable/disable fw interface net0
	$('#fwifacepub').click(function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var data = {
			aid: $('#lxcinfo').val(),
			action: $(this).attr('role')
		};
		socket.emit('LXCIfaceNet0Req', data);
	});
	socket.on('LXCIfaceNet0Res', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}
	});

	// LXC enable/disable fw interface net1
	$('#fwifacepriv').click(function(e) {
		e.preventDefault();
		$(this).prop("disabled", true);
		var data = {
			aid: $('#lxcinfo').val(),
			action: $(this).attr('role')
		};
		socket.emit('LXCIfaceNet1Req', data);
	});
	socket.on('LXCIfaceNet1Res', function(res) {
		if(res == 'ok') {
			window.location.reload();
		}
	});
})(jQuery);
