(function($) {
	// Helper function
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

	// Cloud - create VM
	$('#clcreatevmsaved').click(function(e) {
		e.preventDefault();
		$('#clcreate_error').html('');
		$(this).prop('disabled', true);
		var clpoolid = $('#clpoolid').val();
		var clhostname = $('#clhostname').val();
		var clos = $('#clos').val();
		var ramslider = $('#ramslider').val();
		var cpuslider = $('#cpuslider').val();
		var diskslider = $('#diskslider').val();
		var clddevice = $('#clddevice').val();
		var clnetdevice = $('#clnetdevice').val();
		if(clos == 'default') {
			$('#clcreate_error').html('<div class="alert alert-danger" role="alert"><strong>Error:</strong> no operating system was chosen.</div>');
			$(this).prop('disabled', false);
		}else if(clpoolid == 'default') {
			$('#clcreate_error').html('<div class="alert alert-danger" role="alert"><strong>Error:</strong> no pool was chosen.</div>');
			$(this).prop('disabled', false);
		}else if(clddevice == 'default') {
			$('#clcreate_error').html('<div class="alert alert-danger" role="alert"><strong>Error:</strong> no disk device was chosen.</div>');
			$(this).prop('disabled', false);
		}else if(clnetdevice == 'default') {
			$('#clcreate_error').html('<div class="alert alert-danger" role="alert"><strong>Error:</strong> no network device was chosen.</div>');
			$(this).prop('disabled', false);
		}else{
			if(isEmpty(clhostname) || isBlank(clhostname) || clhostname.isEmpty() || !isDomain(clhostname) || !isAlphaNum(clhostname[clhostname.length - 1])) {
				$('#clcreate_error').html('<div class="alert alert-danger" role="alert"><strong>Error:</strong> invalid hostname.</div>');
				$(this).prop('disabled', false);
			}else{
				var newvm = {
					clpoolid: clpoolid,
					clhostname: clhostname,
					clos: clos,
					ram: ramslider,
					cpu: cpuslider,
					disk: diskslider,
					clddevice: clddevice,
					clnetdevice: clnetdevice
				};
				$('#clhostname').val('');
				$('#clcreate_error').html('');
				socket.emit('PubCloudCreateReq', newvm);
				$('#clcreate_error').html('<div class="alert alert-success" role="alert"><strong>Success:</strong> VM has been scheduled for creation.</div>');
			}
		}
	});
	socket.on('PubCloudCreateRes', function(res) {
		if(res == 'ok') {
			$('#clcreatevm').modal('hide');
			$('#clcreation').html('<div class="alert alert-success" role="alert">Success! Your new VM has been created.</div>');
			setTimeout(function() {
				window.location.reload();
			}, 2500);
		}else{
			$('#clcreatevm').modal('hide');
			$('#clcreation').html('<div class="alert alert-danger" role="alert">Oops! An unexpected error has occurred. Ensure your hostname is unique.</div>');
		}
	});

	// Cloud - create form
	$('#clpoolid').change(function() {
		if($(this).val() != 'default') {
			socket.emit('PubCloudQueryPoolReq', { clpoolid: $(this).val() });
		}else{
			$('#clcreatevmsaved').prop('disabled', true);
			$('#ramslider, #cpuslider, #diskslider').slider('disable');
		}
	});
	socket.on('PubCloudQueryPoolRes', function(res) {
		if(res.status == 'ok') {
			$('#ramslider').slider('setAttribute', 'max', res.retdata.avail_memory);
			$('#cpuslider').slider('setAttribute', 'max', res.retdata.avail_cpu_cores);
			$('#diskslider').slider('setAttribute', 'max', res.retdata.avail_disk_size);
			if(res.retdata.suspended == 1) {
				$('#clcreatevmsaved').prop('disabled', true);
				return;
			}
			if(res.retdata.avail_ip_limit > 0 && res.retdata.avail_memory >= 32 && res.retdata.avail_cpu_cores >= 1 && res.retdata.avail_disk_size >= 1) {
				$('#clcreatevmsaved').prop('disabled', false);
				$('#ramslider, #cpuslider, #diskslider').slider('enable');
			}
			if(res.retdata.templates.length > 0) {
				for(var i = 0; i < res.retdata.templates.length; i++) {
					$('#clos').append($('<option></option>').attr("value", res.retdata.templates[i].vmid).text(res.retdata.templates[i].friendly_name + ' (automatic template)'));
				}
			}
		}else{
			$('#clcreatevmsaved').val('Error retrieving pool details');
		}
	});
})(jQuery);
