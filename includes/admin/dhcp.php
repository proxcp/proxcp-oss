<?php
if(count(get_included_files()) == 1) die("You just broke everything.");
?>
<?php require_once('menu_main.php'); ?>
<div class="row">
	<div class="col-md-12">
		<div class="panel panel-default">
			<div class="panel-body">
				<div class="clearfix"><a class="btn btn-info btn-sm pull-right" href="https://google.com" role="button" target="_blank"><i class="fa fa-book"></i> Related Documentation</a></div>
				<h2 align="center">Manage DHCP Servers</h2><br />
				<div id="adm_message"></div>
				<div class="table-responsive">
					<table class="table table-hover" id="admin_nodetable">
						<thead>
							<tr>
								<th>Name</th>
                <th>Network</th>
								<th>Delete</th>
							</tr>
						</thead>
						<tbody>
							<?php
							$admin_datanodes = $db->get('vncp_dhcp_servers', array('id', '!=', 0));
							$admin_nodes = $admin_datanodes->all();
							for($k = 0; $k < count($admin_nodes); $k++) {
								echo '<tr>';
					                echo '<td>'.$admin_nodes[$k]->hostname.'</td>';
					                echo '<td>'.$admin_nodes[$k]->dhcp_network.'</td>';
					                echo '<td><button id="admin_dhcpdelete'.$admin_nodes[$k]->id.'" class="btn btn-sm btn-danger" role="'.$admin_nodes[$k]->id.'">Delete</button></td>';
					            echo '</tr>';
							}
							?>
						</tbody>
					</table>
				</div>
        <div class="modal fade" id="dhcpHelp" tabindex="-1" role="dialog" aria-labelledby="dhcpHelpLabel">
          <div class="modal-dialog" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="dhcpHelpLabel">What is this for?</h4>
              </div>
              <div class="modal-body">
                <p>This feature works for DHCP servers you have running `isc-dhcp-server`. In order for your customers with KVM containers to be able to use DHCP for IP assignments, root SSH credentials are required to be stored to make the DHCP configuration changes. These credentials and SSH is used <strong>only</strong> to insert and remove DHCP static entries.</p>
                <p>This is exactly what is run when inserting/removing a DHCP static assignment:</p>
                <div class="well"><pre>printf 'ddns-update-style none;\n\n' > /root/dhcpd.test
printf 'option domain-name-servers 8.8.8.8, 8.8.4.4;\n\n' >> /root/dhcpd.test
printf 'default-lease-time 7200;\n' >> /root/dhcpd.test
printf 'max-lease-time 86400;\n\n' >> /root/dhcpd.test
printf 'log-facility local7;\n\n' >> /root/dhcpd.test
printf 'subnet {SUBNET} netmask {NETMASK} {}\n\n' >> /root/dhcpd.test
printf 'host {ID} {hardware ethernet {MACADDR};fixed-address {IP};option routers {GATEWAY};}\n' >> /root/dhcpd.test
mv /root/dhcpd.test /etc/dhcp/dhcpd.conf && rm /root/dhcpd.test
service isc-dhcp-server restart</pre></div>
                <p>As with the Proxmox node credentials, the root password will be stored as an AES-256-CBC encrypted value.</p>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
              </div>
            </div>
          </div>
        </div>
				<h2 align="center">Add New Server (<a data-toggle="modal" data-target="#dhcpHelp" style="cursor:pointer;">?</a>)</h2>
				<form role="form" action="" method="POST">
          <div class="form-group">
					    <label>DHCP Server Hostname</label>
					    <input class="form-control" type="text" name="dhcphostname" placeholder="dhcp.usa.domain.com" />
					</div>
					<div class="form-group">
					    <label>Root Password</label>
					    <input class="form-control" type="password" name="rpassword" />
					</div>
					<div class="form-group">
					    <label>SSH Port</label>
					    <input class="form-control" type="text" name="sshport" placeholder="22" />
					</div>
          <div class="form-group">
              <label>DHCP Network</label>
              <select class="form-control" name="dhcpnetwork">
                  <?php
                  $getnodes = $db->get_unique_network('vncp_dhcp', array('id', '!=', 0))->all();
                  for($i = 0; $i < count($getnodes); $i++) {
                      $unique = $db->get('vncp_dhcp_servers', array('dhcp_network', '=', $getnodes[$i]->network))->all();
                      if(count($unique) < 1) {
                        echo '<option value="'.$getnodes[$i]->network.'">'.$getnodes[$i]->network.'</option>';
                      }
                  }
                  ?>
              </select>
          </div>
					<input type="hidden" name="token" value="<?php echo Token::generate(); ?>" />
					<input type="submit" value="Submit" class="btn btn-success" />
				</form>
			</div>
		</div>
	</div>
</div>
