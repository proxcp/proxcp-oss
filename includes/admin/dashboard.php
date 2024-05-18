<?php
if(count(get_included_files()) == 1) die("You just broke everything.");
?>
<?php require_once('menu_main.php'); ?>
<div class="row">
	<div class="col-md-12">
		<div class="panel panel-default">
			<div class="panel-body">
				<div class="clearfix"><a class="btn btn-info btn-sm pull-right" href="https://google.com" role="button" target="_blank"><i class="fa fa-book"></i> Related Documentation</a></div><br />
				<div class="row">
					<?php
					$nodestats = $db->get('vncp_nodes', array('id', '!=', 0));
					$nodestats = $nodestats->all();
					$node_count = count($nodestats);
					if($node_count > 0) {
					?>
					<div class="col-md-12">
						<div class="panel panel-info">
							<div class="panel-heading">
								<h3 class="panel-title">Node Stats</h3>
							</div>
							<div class="panel-body">
								<div id="adm_message"></div>
							    <select class="form-control" id="selectnodestats">
							    	<option value="default">Select...</option>
							    	<?php
							    	for($k = 0; $k < $node_count; $k++) {
							    		echo '<option value="'.$nodestats[$k]->name.'">'.$nodestats[$k]->hostname.' ('.$nodestats[$k]->name.')</option>';
							    	}
							    	?>
							    </select>
								<h1 align="center" id="admin_nodestatus">Server Status: <span class="label" id="admin_nodestatus2"><img src="img/loader.GIF" id="loader" /></span></h1>
								<div class="col-md-2"><p><em>CPU Usage</em></p></div>
								<div class="progress">
									<div class="progress-bar progress-bar-info progress-bar-striped" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="min-width: 2em;width: 100%;" id="admin_cpu_1"><div id="admin_cpu_2"></div></div>
								</div>
								<div class="col-md-2"><p><em>RAM Usage</em></p></div>
								<div class="progress">
									<div class="progress-bar progress-bar-info progress-bar-striped" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="min-width: 2em;width: 100%;" id="admin_ram_1"><div id="admin_ram_2"></div></div>
								</div>
								<div class="col-md-2"><p><em>Disk Usage</em></p></div>
								<div class="progress">
									<div class="progress-bar progress-bar-info progress-bar-striped" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="min-width: 2em;width: 100%;" id="admin_disk_1"><div id="admin_disk_2"></div></div>
								</div>
								<div class="col-md-2"><p><em>Swap Usage</em></p></div>
								<div class="progress">
									<div class="progress-bar progress-bar-info progress-bar-striped" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="min-width: 2em;width: 100%;" id="admin_swap_1"><div id="admin_swap_2"></div></div>
								</div>
								<div class="table-responsive">
								    <table class="table table-striped">
								        <tr>
								            <td>Uptime</td>
								            <td id="node_uptime">null</td>
								        </tr>
								        <tr>
								            <td>Load Avg</td>
								            <td id="node_loadavg">null</td>
								        </tr>
								        <tr>
								            <td>Kernel Version</td>
								            <td id="node_kernel">null</td>
								        </tr>
								        <tr>
								            <td>PVE Version</td>
								            <td id="node_pve">null</td>
								        </tr>
								        <tr>
								        	<td>CPU Model</td>
								        	<td id="node_cpumod">null</td>
								        </tr>
								    </table>
								</div>
							</div>
						</div>
					</div>
					<?php
				}else{
					?>
					<div class="col-md-12">
						<div class="panel panel-warning">
							<div class="panel-heading">
								<h3 class="panel-title">Add A Node</h3>
							</div>
							<div class="panel-body">
								<p>Node status will appear here. No nodes found. <a href="<?php echo Config::get('admin/base'); ?>?action=nodes">Click here</a> to add a new node.</p>
							</div>
						</div>
					</div>
					<?php
					}
					?>
				</div>
				<div class="row">
					<div class="col-md-6">
						<div class="panel panel-info">
							<div class="panel-heading">
								<h3 class="panel-title">LXC VPS Stats</h3>
							</div>
							<div class="panel-body">
								<?php
								$lxccount = $db->get('vncp_lxc_ct', array('user_id', '!=', 0));
								$lxccount = $lxccount->all();
								$lxccount_s = $db->get('vncp_lxc_ct', array('suspended', '=', 1));
								$lxccount_s = $lxccount_s->all();
								?>
								<h2 align="center"><?php echo count($lxccount); ?> total</h2>
								<center><p>Suspended: <?php echo count($lxccount_s); ?></p></center>
							</div>
						</div>
					</div>
					<div class="col-md-6">
						<div class="panel panel-info">
							<div class="panel-heading">
								<h3 class="panel-title">KVM VPS Stats</h3>
							</div>
							<div class="panel-body">
								<?php
								$kvmcount = $db->get('vncp_kvm_ct', array('user_id', '!=', 0));
								$kvmcount = $kvmcount->all();
								$kvmcount_s = $db->get('vncp_kvm_ct', array('suspended', '=', 1));
								$kvmcount_s = $kvmcount_s->all();
								?>
								<h2 align="center"><?php echo count($kvmcount); ?> total</h2>
								<center><p>Suspended: <?php echo count($kvmcount_s); ?></p></center>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-6">
						<div class="panel panel-info">
							<div class="panel-heading">
								<h3 class="panel-title">Cloud Stats</h3>
							</div>
							<div class="panel-body">
								<?php
								$cloudcount = $db->get('vncp_kvm_cloud', array('user_id', '!=', 0));
								$cloudcount = $cloudcount->all();
								$cloudcount_s = $db->get('vncp_kvm_cloud', array('suspended', '=', 1));
								$cloudcount_s = $cloudcount_s->all();
								?>
								<h2 align="center"><?php echo count($cloudcount); ?> total</h2>
								<center><p>Suspended: <?php echo count($cloudcount_s); ?></p></center>
							</div>
						</div>
					</div>
					<div class="col-md-6">
						<div class="panel panel-info">
							<div class="panel-heading">
								<h3 class="panel-title">User Stats</h3>
							</div>
							<div class="panel-body">
								<?php
								$usercount = $db->get('vncp_users', array('id', '!=', 0));
								$usercount = $usercount->all();
								$usercount_s = $db->get('vncp_users', array('locked', '=', 1));
								$usercount_s = $usercount_s->all();
								$usercount_a = $db->get('vncp_users', array('`group`', '=', 2));
								$usercount_a = $usercount_a->all();
								?>
								<h2 align="center"><?php echo count($usercount); ?> total</h2>
								<center><p>Locked: <?php echo count($usercount_s); ?> / Admins <?php echo count($usercount_a); ?></p></center>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="panel panel-default">
	<div class="panel-heading">
		<h3 class="panel-title">System Information</h3>
	</div>
	<div class="panel-body">
		<div class="row">
			<div class="col-md-4">
				<div class="row">
					<div class="col-md-8">HTTPS Web</div>
					<div class="col-md-4"><?php if(gethttps()) echo 'True'; else echo 'False'; ?></div>
				</div>
				<div class="row">
					<div class="col-md-8">HTTPS Socket</div>
					<div class="col-md-4"><?php $f = fopen('js/io.js', 'r'); $value = fread($f, filesize('js/io.js')); $value = substr($value, 25, 5); if($value == 'https') echo 'True'; else echo 'False'; ?></div>
				</div>
				<div class="row">
					<div class="col-md-8">PHP Version</div>
					<div class="col-md-4"><?php echo phpversion(); ?></div>
				</div>
			</div>
			<div class="col-md-4">
				<div class="row">
					<div class="col-md-8">Removed install.php</div>
					<div class="col-md-4"><?php if(file_exists('install.php')) echo '<span style="color:red;">False</span>'; else echo 'True'; ?></div>
				</div>
				<div class="row">
					<div class="col-md-8">Permissions core/init.php</div>
					<div class="col-md-4"><?php echo substr(sprintf('%o', fileperms('core/init.php')), -4); ?></div>
				</div>
				<div class="row">
					<div class="col-md-8">MySQL Version</div>
					<div class="col-md-4"><?php echo mysqli_get_server_info($connection); ?></div>
				</div>
			</div>
			<div class="col-md-4">
				<div class="row">
					<div class="col-md-8">Removed sql/ directory</div>
					<div class="col-md-4"><?php if(file_exists('sql/')) echo '<span style="color:red;">False</span>'; else echo 'True'; ?></div>
				</div>
				<div class="row">
					<div class="col-md-8"><?php echo $appname; ?> Version</div>
					<div class="col-md-4"><?php echo getVersion()[0]; ?></div>
				</div>
			</div>
		</div>
	</div>
</div>
