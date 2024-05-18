<?php
if(count(get_included_files()) == 1) die("You just broke everything.");
?>
<?php require_once('menu_main.php'); ?>
<div class="row">
	<div class="col-md-2">
    <ul class="nav nav-pills nav-stacked">
      <li role="presentation"><a href="<?php echo Config::get('admin/base') . '?action=nodes'; ?>">Manage Nodes</a></li>
      <li role="presentation" class="active"><a href="<?php echo Config::get('admin/base') . '?action=tuntap'; ?>">Manage Node SSH</a></li>
      <li role="presentation"><a href="<?php echo Config::get('admin/base') . '?action=natnodes'; ?>">Manage NAT Nodes</a></li>
    </ul>
  </div>
	<div class="col-md-10">
		<div class="panel panel-default">
			<div class="panel-body">
				<div class="clearfix"><a class="btn btn-info btn-sm pull-right" href="https://google.com" role="button" target="_blank"><i class="fa fa-book"></i> Related Documentation</a></div>
				<h2 align="center">Manage SSH Super Credentials</h2><br />
				<div id="adm_message"></div>
				<div class="table-responsive">
					<table class="table table-hover" id="admin_nodetable">
						<thead>
							<tr>
								<th>Name</th>
								<th>Delete</th>
							</tr>
						</thead>
						<tbody>
							<?php
							$admin_datanodes = $db->get('vncp_tuntap', array('id', '!=', 0));
							$admin_nodes = $admin_datanodes->all();
							for($k = 0; $k < count($admin_nodes); $k++) {
								echo '<tr>';
					                echo '<td>'.$admin_nodes[$k]->node.'</td>';
					                echo '<td><button id="admin_tuntapdelete'.$admin_nodes[$k]->id.'" class="btn btn-sm btn-danger" role="'.$admin_nodes[$k]->id.'">Delete</button></td>';
					            echo '</tr>';
							}
							?>
						</tbody>
					</table>
				</div>
        <div class="modal fade" id="tuntapHelp" tabindex="-1" role="dialog" aria-labelledby="tuntapHelpLabel">
          <div class="modal-dialog" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="tuntapHelpLabel">What is this for?</h4>
              </div>
              <div class="modal-body">
                <p>Some ProxCP features require root access to your Proxmox nodes. These credentials and SSH are used <strong>only</strong> to: enable/disable the TUN/TAP interface (LXC), change root passwords (LXC), move custom ISO uploads to Proxmox nodes (KVM), enable/disable VirtIO RNG (KVM), or manage NAT.</p>
                <p>This is exactly what is run when enabling TUN/TAP:</p>
                <div class="well"><pre>printf \'lxc.cgroup.devices.allow: c 10:200 rwm\nlxc.hook.autodev = sh -c "modprobe tun; cd ${LXC_ROOTFS_MOUNT}/dev; mkdir net; mknod net/tun c 10 200; chmod 0666 net/tun"\' >> /etc/pve/lxc/{VMID}.conf</pre></div>
                <p>This is exactly what is run when disabling TUN/TAP:</p>
                <div class="well"><pre>grep -v "lxc.cgroup.devices.allow: c 10:200 rwm" /etc/pve/lxc/{VMID}.conf > {VMID}.temp && mv {VMID}.temp /etc/pve/lxc/{VMID}.conf && grep -v \'lxc.hook.autodev = sh -c "modprobe tun; cd ${LXC_ROOTFS_MOUNT}/dev; mkdir net; mknod net/tun c 10 200; chmod 0666 net/tun"\' /etc/pve/lxc/{VMID}.conf > {VMID}.temp && mv {VMID}.temp /etc/pve/lxc/{VMID}.conf</pre></div>
								<p>This is exactly what is run when moving custom ISO uploads:</p>
								<div class="well"><pre>wget -bqc -O {NAME}.iso https://url.com/files/{KEY}/get</pre></div>
								<p>This is exactly what is run when enabling/disabling RNG:</p>
								<div class="well"><pre>pvesh set /nodes/{NODE}/qemu/{VMID}/config --rng0 source=/dev/urandom,max_bytes=1024,period=1000 || pvesh set /nodes/{NODE}/qemu/{VMID}/config --delete rng0</pre></div>
                <p>As with the Proxmox node credentials, the root password will be stored as an AES-256-CBC encrypted value and used only when necessary.</p>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
              </div>
            </div>
          </div>
        </div>
				<h2 align="center">Add New Credentials (<a data-toggle="modal" data-target="#tuntapHelp" style="cursor:pointer;">More info</a>)</h2>
				<form role="form" action="" method="POST">
          <div class="form-group">
              <label>Node</label>
              <select class="form-control" name="tuntapnode">
									<option value="default">Select...</option>
                  <?php
                  $getnodes = $db->get('vncp_nodes', array('id', '!=', 0))->all();
                  for($i = 0; $i < count($getnodes); $i++) {
											$hasSSH = $db->get('vncp_tuntap', array('node', '=', $getnodes[$i]->name))->all();
											if(count($hasSSH) == 0) {
                      	echo '<option value="'.$getnodes[$i]->name.'">'.$getnodes[$i]->hostname.'</option>';
											}
                  }
                  ?>
              </select>
          </div>
					<div class="form-group">
					    <label>Root Password</label>
					    <input class="form-control" type="password" name="rpassword" />
					</div>
					<div class="form-group">
					    <label>SSH Port</label>
					    <input class="form-control" type="text" name="sshport" placeholder="22" />
					</div>
					<input type="hidden" name="token" value="<?php echo Token::generate(); ?>" />
					<input type="submit" value="Submit" class="btn btn-success" />
				</form>
			</div>
		</div>
	</div>
</div>
