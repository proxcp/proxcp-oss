<?php
if(count(get_included_files()) == 1) die("You just broke everything.");
?>
<?php require_once('menu_main.php'); ?>
<div class="row">
  <div class="col-md-2">
    <ul class="nav nav-pills nav-stacked">
      <li role="presentation"><a href="<?php echo Config::get('admin/base') . '?action=nodes'; ?>">Manage Nodes</a></li>
      <li role="presentation"><a href="<?php echo Config::get('admin/base') . '?action=tuntap'; ?>">Manage Node SSH</a></li>
      <li role="presentation" class="active"><a href="<?php echo Config::get('admin/base') . '?action=natnodes'; ?>">Manage NAT Nodes</a></li>
    </ul>
  </div>
	<div class="col-md-10">
		<div class="panel panel-default">
			<div class="panel-body">
        <div class="clearfix"><a class="btn btn-info btn-sm pull-right" href="https://google.com" role="button" target="_blank"><i class="fa fa-book"></i> Related Documentation</a></div>
				<h2 align="center">Manage NAT Nodes</h2><br />
        <?php
        if($natCreatedSuccess) {
            echo '<div id="adm_message"><div class="alert alert-success" role="alert"><strong>Success:</strong> new NAT-enabled node created.</div></div>';
        }else{
            echo '<div id="adm_message"></div>';
        }
        ?>
				<div class="table-responsive">
					<table class="table table-hover" id="admin_natnodetable">
						<thead>
							<tr>
                <th>Hostname</th>
								<th>Name</th>
								<th>Public IP</th>
								<th>NAT Range</th>
                <th>NAT VM Limit</th>
							</tr>
						</thead>
						<tbody>
							<?php
							$admin_datanodes = $db->get('vncp_nat', array('id', '!=', 0));
							$admin_nodes = $admin_datanodes->all();
							for($k = 0; $k < count($admin_nodes); $k++) {
								echo '<tr>';
                          $hostname = $db->get('vncp_nodes', array('name', '=', $admin_nodes[$k]->node))->all()[0]->hostname;
					                echo '<td>'.$hostname.'</td>';
					                echo '<td>'.$admin_nodes[$k]->node.'</td>';
					                echo '<td>'.$admin_nodes[$k]->publicip.'</td>';
					                echo '<td>'.$admin_nodes[$k]->natcidr.'</td>';
                          $natcount = $db->get('vncp_natforwarding', array('node', '=', $admin_nodes[$k]->node))->all();
                          echo '<td><a data-toggle="modal" href="#" data-target="#natnode_lvl1" data-node="'.$admin_nodes[$k]->node.'" style="text-decoration:underline;">' . (string)count($natcount) . ' / ' . $admin_nodes[$k]->vmlimit . '</a></td>';
					            echo '</tr>';
							}
							?>
						</tbody>
					</table>
				</div>
				<h2 align="center">Enable New NAT Node</h2>
				<form role="form" action="" method="POST" id="adm_natnode_form">
          <div class="form-group">
              <label>Node <small>requires <a href="<?php echo Config::get('admin/base') . '?action=tuntap'; ?>">Node SSH</a> credentials to be added</small></label>
              <select class="form-control" name="natnode" id="natnode">
                  <option value="default">Select...</option>
                  <?php
                  $getnodes = $db->get('vncp_nodes', array('id', '!=', 0))->all();
                  for($i = 0; $i < count($getnodes); $i++) {
                      $hasSSH = $db->get('vncp_tuntap', array('node', '=', $getnodes[$i]->name))->all();
                      $hasNAT = $db->get('vncp_nat', array('node', '=', $getnodes[$i]->name))->all();
                      if(count($hasSSH) == 1 && count($hasNAT) == 0) {
                        echo '<option value="'.$getnodes[$i]->name.'">'.$getnodes[$i]->hostname.'</option>';
                      }
                  }
                  ?>
              </select>
          </div>
          <div class="form-group">
            <label>Node Public IP</label>
            <input class="form-control" type="text" readonly name="natnodeip" id="natnodeip" value="" />
          </div>
          <div class="form-group">
            <label>NAT IP Range (CIDR Format) <small>must be within 10.0.0.0/8, 172.16.0.0/12, or 192.168.0.0/16</small></label>
            <input class="form-control" type="text" name="natiprange" placeholder="10.10.10.0/24" />
          </div>
          <div class="checkbox">
            <label>
              <input type="checkbox" name="natunderstand" /> Check this box to confirm enabling this Proxmox node for NAT. This action cannot be undone - you cannot disable NAT afterwards. Once enabled, ProxCP will start managing port and domain forwarding for NAT virtual machines created on this node via <em>iptables</em> and <em>nginx</em>.<br /><br />NAT-enabled Proxmox nodes can have NAT virtual machines and non-NAT virtual machines on them.
            </label>
          </div>
					<input type="hidden" name="token" value="<?php echo Token::generate(); ?>" />
					<input type="submit" value="Submit" class="btn btn-success" />
				</form>
			</div>
		</div>
	</div>
  <link rel="stylesheet" href="css/jquery-confirm.min.css" />
  <div class="modal fade" id="natnode_lvl1" tabindex="-1" role="dialog" aria-labelledby="natnode_lvl1Label">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="natnode_lvl1Label">Detailed View - </h4>
      </div>
      <div class="modal-body">
        <div id="lvl1_error"></div>
        <div class="table-responsive">
					<table class="table table-hover" id="admin_nodelvl1table">
						<thead>
							<tr>
                <th>User</th>
								<th>Billing ID</th>
								<th>NAT Ports</th>
								<th>NAT Domains</th>
							</tr>
						</thead>
						<tbody></tbody>
					</table>
				</div>
      </div>
    </div>
  </div>
</div>
</div>
