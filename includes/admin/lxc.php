<?php
if(count(get_included_files()) == 1) die("You just broke everything.");
?>
<?php require_once('menu_main.php'); ?>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-body">
              <div class="clearfix"><a class="btn btn-info btn-sm pull-right" href="https://google.com" role="button" target="_blank"><i class="fa fa-book"></i> Related Documentation</a></div>
                <h2 align="center">Manage LXC</h2><br />
                <?php
                if($lxcCreatedSuccess) {
                    echo '<div id="adm_message"><div class="alert alert-success" role="alert"><strong>New LXC Root Password:</strong> <input type="text" class="form-control" value="'.$plaintext_password.'" /></div></div>';
                }else{
                    echo '<div id="adm_message"></div>';
                }
                ?>
                <div class="table-responsive">
                    <table class="table table-hover" id="admin_lxctable">
                        <thead>
                            <tr>
                                <th>User ID</th>
                                <th>Billing ID</th>
                                <th>Node</th>
                                <th>OS</th>
                                <th>IP</th>
                                <th>Suspend</th>
                                <th>Delete</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $admin_datalxc = $db->get('vncp_lxc_ct', array('user_id', '!=', 0));
                            $admin_lxc = $admin_datalxc->all();
                            for($k = 0; $k < count($admin_lxc); $k++) {
                                echo '<tr>';
                                    echo '<td>'.$admin_lxc[$k]->user_id.'</td>';
                                    echo '<td>'.$admin_lxc[$k]->hb_account_id.'</td>';
                                    echo '<td>'.$admin_lxc[$k]->node.'</td>';
                                    echo '<td>'.$admin_lxc[$k]->os.'</td>';
                                    echo '<td>'.$admin_lxc[$k]->ip.'</td>';
                                    if($admin_lxc[$k]->suspended == 0)
                                        echo '<td><button class="btn btn-danger btn-sm" id="lxcsuspend'.$admin_lxc[$k]->hb_account_id.'" role="'.$admin_lxc[$k]->hb_account_id.'">Suspend</button></td>';
                                    else
                                        echo '<td><button class="btn btn-success btn-sm" id="lxcunsuspend'.$admin_lxc[$k]->hb_account_id.'" role="'.$admin_lxc[$k]->hb_account_id.'">Unsuspend</button></td>';
                                    echo '<td><button id="admin_lxcdelete'.$admin_lxc[$k]->hb_account_id.'" class="btn btn-sm btn-danger" role="'.$admin_lxc[$k]->hb_account_id.'">Delete</button></td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <h2 align="center">Create LXC</h2>
                <form role="form" action="" method="POST" id="adm_createlxc_form">
                    <div class="row">
                      <div class="col-md-6">
                        <div class="form-group">
                            <label>User ID</label><br />
                            <select class="form-control selectpicker" data-live-search="true" name="userid">
                              <option value="default">Select...</option>
                              <?php
                              $userdata = $db->get('vncp_users', array('id', '!=', 0))->all();
                              for($i = 0; $i < count($userdata); $i++) {
                                echo '<option value="' . $userdata[$i]->id . '">' . $userdata[$i]->email . ' (ID: ' . $userdata[$i]->id . ')</option>';
                              }
                              ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Billing Account ID</label>
                            <input class="form-control" type="text" name="hb_account_id" placeholder="100" />
                        </div>
                        <div class="form-group">
                            <label>Pool ID</label>
                            <input class="form-control" type="text" name="poolid" placeholder="client_id_1" />
                        </div>
                        <div class="form-group">
                            <label>Node</label>
                            <select class="form-control" name="node" id="node">
                            	<option value="default">Select...</option>
                            	<?php
                            	$lxc_node = $db->get('vncp_nodes', array('id', '!=', 0));
                            	$lxc_node = $lxc_node->all();
                            	for($k = 0; $k < count($lxc_node); $k++) {
                                $isNATNode = $db->get('vncp_nat', array('node', '=', $lxc_node[$k]->name))->all();
                                if(count($isNATNode) == 1) {
                                  echo '<option value="'.$lxc_node[$k]->name.'">'.$lxc_node[$k]->hostname.' (NAT: ' . $isNATNode[0]->natcidr . ')</option>';
                                }else{
                            		    echo '<option value="'.$lxc_node[$k]->name.'">'.$lxc_node[$k]->hostname.'</option>';
                                }
                            	}
                            	?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>LXC Hostname</label>
                            <input class="form-control" type="text" name="hostname" placeholder="server.name" />
                        </div>
                        <div class="form-group">
                            <label>Operating System</label>
                            <select class="form-control" name="osfriendly">
                            	<option value="default">Select...</option>
                            	<?php
                            	$lxc_templates = $db->get('vncp_lxc_templates', array('id', '!=', 0));
                            	$lxc_templates = $lxc_templates->all();
                            	for($k = 0; $k < count($lxc_templates); $k++) {
                            		echo '<option value="'.$lxc_templates[$k]->volid.'">'.$lxc_templates[$k]->friendly_name.'</option>';
                            	}
                            	?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Operating System Type <small>determines LXC setup scripts used for OS</small></label>
                            <select class="form-control" name="ostype">
                            	<option value="default">Select...</option>
                            	<option value="debian">debian</option>
                            	<option value="ubuntu">ubuntu</option>
                            	<option value="centos">centos</option>
                            	<option value="fedora">fedora</option>
                            	<option value="opensuse">opensuse</option>
                            	<option value="archlinux">archlinux</option>
                            	<option value="alpine">alpine</option>
                            	<option value="gentoo">gentoo</option>
                            	<option value="unmanaged">unmanaged</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>LXC NAT Enabled?</label>
                            <select class="form-control" name="lxcisnat" id="lxcisnat">
                            	<option value="default">Select...</option>
                            	<option value="false">False</option>
                            	<option value="true">True</option>
                            </select>
                        </div>
                        <div id="lxcnatfields" style="display:none;"><div class="form-group">
                            <label>NAT Public Ports <small>how many public ports should this LXC get?</small></label>
                            <input class="form-control" type="number" name="natpublicports" placeholder="20" />
                        </div>
                        <div class="form-group">
                            <label>NAT Domain Forwarding <small>how many proxied domains should this LXC get?</small></label>
                            <input class="form-control" type="number" name="natdomainproxy" placeholder="5" />
                        </div></div>
                        <div class="form-group">
                            <label>IPv4 <small>must include subnet (CIDR notation)</small></label>
                            <input class="form-control" type="text" name="ipv4" id="ipv4" placeholder="1.1.1.5/CIDR" />
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group">
                            <label>IPv4 Gateway</label>
                            <input class="form-control" type="text" name="ipv4gw" placeholder="1.1.1.1" />
                        </div>
                        <div class="form-group">
                            <label>IPv4 Netmask</label>
                            <input class="form-control" type="text" name="ipv4_netmask" value="255.255.255.0" />
                        </div>
                        <div class="form-group">
                            <label>LXC CPU Cores</label>
                            <input class="form-control" type="text" name="cpucores" placeholder="1" />
                        </div>
                        <div class="form-group">
                            <label>LXC RAM (MB)</label>
                            <input class="form-control" type="text" name="ram" placeholder="512" />
                        </div>
                        <div class="form-group">
                            <label>LXC Storage Location</label>
                            <select class="form-control" name="storage_location" id="storage_location">
                            	<option value="default">Select...</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>LXC Storage Size (GB)</label>
                            <input class="form-control" type="text" name="storage_size" placeholder="20" />
                        </div>
                        <div class="form-group">
                            <label>LXC Bandwidth Limit (GB)</label>
                            <input class="form-control" type="text" name="bandwidth_limit" placeholder="500" />
                        </div>
                        <div class="form-group">
                            <label>Network Speed Limit (MB/s) <small>0 = unlimited, 1 MB/s = 0.125 Mb/s</small></label>
                            <input class="form-control" type="number" name="portspeed" value="0" min="0" max="10000" />
                        </div>
                        <div class="form-group">
                            <label>MAC Address <small>optional, 00:00:00:00:00:00 format</small></label>
                            <input class="form-control" type="text" name="setmacaddress" placeholder="00:00:00:00:00:00" />
                        </div>
                        <div class="form-group">
                            <label>VLAN Tag <small>optional</small></label>
                            <input class="form-control" type="number" name="setvlantag" placeholder="no VLAN" min="0" max="4094" value="0" />
                        </div>
                      </div>
                    </div>
                    <input type="hidden" name="token" value="<?php echo Token::generate(); ?>" />
                    <input type="submit" value="Submit" class="btn btn-success btn-block" />
                </form>
            </div>
        </div>
    </div>
</div>
