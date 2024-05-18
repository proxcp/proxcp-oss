<?php
if(count(get_included_files()) == 1) die("You just broke everything.");
?>
<?php require_once('menu_main.php'); ?>
<div class="row">
	<div class="col-md-2">
    <ul class="nav nav-pills nav-stacked">
      <li role="presentation" class="active"><a href="<?php echo Config::get('admin/base') . '?action=nodes'; ?>">Manage Nodes</a></li>
      <li role="presentation"><a href="<?php echo Config::get('admin/base') . '?action=tuntap'; ?>">Manage Node SSH</a></li>
      <li role="presentation"><a href="<?php echo Config::get('admin/base') . '?action=natnodes'; ?>">Manage NAT Nodes</a></li>
    </ul>
  </div>
	<div class="col-md-10">
		<div class="panel panel-default">
			<div class="panel-body">
				<div class="clearfix"><a class="btn btn-info btn-sm pull-right" href="https://google.com" role="button" target="_blank"><i class="fa fa-book"></i> Related Documentation</a></div>
				<h2 align="center">Manage Nodes</h2><br />
				<?php
				$nodelimit = -1;
				if($GLOBALS['node_limit'] == '100000000') {
					$nodelimit = "unlimited";
				}else{
					$nodelimit = $GLOBALS['node_limit'];
				}
				?>
				<h4 align="center">Licensed node limit: <?php echo $nodelimit; ?></h4><br />
				<div id="adm_message"></div>
				<div class="table-responsive">
					<table class="table table-hover" id="admin_nodetable">
						<thead>
							<tr>
								<th>Hostname</th>
								<th>Name</th>
								<th>Location</th>
								<th>CPU</th>
								<th>SSH</th>
								<th>NAT</th>
								<th>Delete</th>
							</tr>
						</thead>
						<tbody>
							<?php
							$admin_datanodes = $db->get('vncp_nodes', array('id', '!=', 0));
							$admin_nodes = $admin_datanodes->all();
							for($k = 0; $k < count($admin_nodes); $k++) {
								echo '<tr>';
					                echo '<td><a href="' . Config::get('instance/base') . '/admin?action=edit_node&id=' . escape($admin_nodes[$k]->id) . '">'.$admin_nodes[$k]->hostname.'</a></td>';
					                echo '<td>'.$admin_nodes[$k]->name.'</td>';
					                echo '<td>'.$admin_nodes[$k]->location.'</td>';
					                echo '<td>'.$admin_nodes[$k]->cpu.'</td>';
													$hasSSH = $db->get('vncp_tuntap', array('node', '=', $admin_nodes[$k]->name))->all();
													$hasNAT = $db->get('vncp_nat', array('node', '=', $admin_nodes[$k]->name))->all();
													if(count($hasSSH) == 1) {
														echo '<td><i class="fa fa-check"></i></td>';
													}else{
														echo '<td><i class="fa fa-times"></i> <a href="' . Config::get('instance/base') . '/admin?action=tuntap" style="text-decoration:underline;">(?)</a></td>';
													}
													if(count($hasNAT) == 1) {
														echo '<td><i class="fa fa-check"></i></td>';
													}else{
														echo '<td><i class="fa fa-times"></i> <a href="' . Config::get('instance/base') . '/admin?action=natnodes" style="text-decoration:underline;">(?)</a></td>';
													}
					                echo '<td><button id="admin_nodedelete'.$admin_nodes[$k]->id.'" class="btn btn-sm btn-danger" role="'.$admin_nodes[$k]->id.'">Delete</button></td>';
					            echo '</tr>';
							}
							?>
						</tbody>
					</table>
				</div>
				<h2 align="center">Add New Node</h2>
				<form role="form" action="" method="POST">
					<div class="form-group">
					    <label>Hostname</label>
					    <input class="form-control" type="text" name="hostname" placeholder="server.domain.com" />
					</div>
					<div class="form-group">
					    <label>Name</label>
					    <input class="form-control" type="text" name="name" placeholder="server" />
					</div>
					<div class="form-group">
					    <label>API Username</label>
					    <input class="form-control" type="text" name="username" placeholder="name" />
					</div>
					<div class="form-group">
					    <label>API Password</label>
					    <input class="form-control" type="password" name="password" />
					</div>
					<div class="form-group">
					    <label>Realm</label>
					    <input class="form-control" type="text" name="realm" value="pve" />
					</div>
					<div class="form-group">
					    <label>Port</label>
					    <input class="form-control" type="text" name="port" value="8006" />
					</div>
					<div class="form-group">
					    <label>Location</label>
					    <input class="form-control" type="text" name="location" placeholder="City, State, Country" />
					</div>
					<div class="form-group">
					    <label>CPU</label>
					    <input class="form-control" type="text" name="cpu" placeholder="Intel Xeon" />
					</div>
					<div class="form-group">
					    <label>Backup Store</label>
					    <input class="form-control" type="text" name="backup" value="backup" />
					</div>
					<input type="hidden" name="token" value="<?php echo Token::generate(); ?>" />
					<input type="submit" value="Submit" class="btn btn-success" />
				</form>
			</div>
		</div>
	</div>
</div>
