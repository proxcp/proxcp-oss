<?php
if(count(get_included_files()) == 1) die("You just broke everything.");
?>
<?php require_once('menu_main.php'); ?>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-body">
              <div class="clearfix"><a class="btn btn-info btn-sm pull-right" href="https://google.com" role="button" target="_blank"><i class="fa fa-book"></i> Related Documentation</a></div>
                <h2 align="center">Manage KVM</h2><br />
                <?php
                if($kvmCreatedSuccess) {
                  if(!$cipassword) {
                    echo '<div id="adm_message"><div class="alert alert-success" role="alert"><strong>Success:</strong> KVM created successfully!</div></div>';
                  }else{
                    echo '<div id="adm_message"><div class="alert alert-success" role="alert"><strong>Success:</strong> KVM created successfully!<br />Password: '.$cipassword.'</div></div>';
                  }
                }else{
                    echo '<div id="adm_message"></div>';
                }
                ?>
                <div class="table-responsive">
                    <table class="table table-hover" id="admin_kvmtable">
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
                            $admin_datakvm = $db->get('vncp_kvm_ct', array('user_id', '!=', 0));
                            $admin_kvm = $admin_datakvm->all();
                            for($k = 0; $k < count($admin_kvm); $k++) {
                                echo '<tr>';
                                    echo '<td>'.$admin_kvm[$k]->user_id.'</td>';
                                    echo '<td>'.$admin_kvm[$k]->hb_account_id.'</td>';
                                    echo '<td>'.$admin_kvm[$k]->node.'</td>';
                                    echo '<td>'.$admin_kvm[$k]->os.'</td>';
                                    echo '<td>'.$admin_kvm[$k]->ip.'</td>';
                                    if($admin_kvm[$k]->suspended == 0)
                                        echo '<td><button class="btn btn-danger btn-sm" id="kvmsuspend'.$admin_kvm[$k]->hb_account_id.'" role="'.$admin_kvm[$k]->hb_account_id.'">Suspend</button></td>';
                                    else
                                        echo '<td><button class="btn btn-success btn-sm" id="kvmunsuspend'.$admin_kvm[$k]->hb_account_id.'" role="'.$admin_kvm[$k]->hb_account_id.'">Unsuspend</button></td>';
                                    echo '<td><button id="admin_kvmdelete'.$admin_kvm[$k]->hb_account_id.'" class="btn btn-sm btn-danger" role="'.$admin_kvm[$k]->hb_account_id.'">Delete</button></td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <h2 align="center">Create KVM</h2>
                <form role="form" action="" method="POST" id="adm_createkvm_form">
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
                        	$kvm_node = $db->get('vncp_nodes', array('id', '!=', 0));
                        	$kvm_node = $kvm_node->all();
                        	for($k = 0; $k < count($kvm_node); $k++) {
                            $isNATNode = $db->get('vncp_nat', array('node', '=', $kvm_node[$k]->name))->all();
                            if(count($isNATNode) == 1) {
                              echo '<option value="'.$kvm_node[$k]->name.'">'.$kvm_node[$k]->hostname.' (NAT: ' . $isNATNode[0]->natcidr . ')</option>';
                            }else{
                        		  echo '<option value="'.$kvm_node[$k]->name.'">'.$kvm_node[$k]->hostname.'</option>';
                            }
                        	}
                        	?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>KVM Hostname</label>
                        <input class="form-control" type="text" name="hostname" placeholder="server.name" />
                    </div>
                    <div class="form-group">
                        <label>KVM NAT Enabled?</label>
                        <select class="form-control" name="kvmisnat" id="kvmisnat">
                          <option value="default">Select...</option>
                          <option value="false">False</option>
                          <option value="true">True</option>
                        </select>
                    </div>
                    <div id="kvmnatfields" style="display:none;"><div class="form-group">
                        <label>NAT Public Ports <small>how many public ports should this KVM get?</small></label>
                        <input class="form-control" type="number" name="natpublicports" placeholder="20" />
                    </div>
                    <div class="form-group">
                        <label>NAT Domain Forwarding <small>how many proxied domains should this KVM get?</small></label>
                        <input class="form-control" type="number" name="natdomainproxy" placeholder="5" />
                    </div></div>
                    <div class="form-group">
                        <label>Operating System Installation Type</label>
                        <select class="form-control" name="os_installation_type" id="os_installation_type">
                            <option value="default">Select...</option>
                            <option value="iso">ISO (manual installation)</option>
                            <option value="template">Template (automatic installation)</option>
                        </select>
                    </div>
                    <div id="admin_createkvm_template" style="display:none;"><div class="form-group">
                        <label>Operating System</label>
                        <select class="form-control" name="ostemplate">
                        	<option value="default">Select...</option>
                        	<?php
                        	$kvm_templates = $db->get('vncp_kvm_templates', array('id', '!=', 0));
                        	$kvm_templates = $kvm_templates->all();
                        	for($k = 0; $k < count($kvm_templates); $k++) {
                        		echo '<option value="'.$kvm_templates[$k]->id.'">'.$kvm_templates[$k]->friendly_name.' (Node: '.$kvm_templates[$k]->node.')</option>';
                        	}
                        	?>
                        </select>
                    </div></div>
                    <div id="admin_createkvm_iso" style="display:none;"><div class="form-group">
                        <label>Operating System</label>
                        <select class="form-control" name="osfriendly">
                        	<option value="default">Select...</option>
                        	<?php
                        	$kvm_templates = $db->get('vncp_kvm_isos', array('id', '!=', 0));
                        	$kvm_templates = $kvm_templates->all();
                        	for($k = 0; $k < count($kvm_templates); $k++) {
                        		echo '<option value="'.$kvm_templates[$k]->volid.'">'.$kvm_templates[$k]->friendly_name.'</option>';
                        	}
                        	?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Operating System Type <small>determines KVM optimizations/features</small></label>
                        <select class="form-control" name="ostype">
                            <option value="default">Select...</option>
                            <option value="other">Other</option>
                            <option value="wxp">Windows XP</option>
                            <option value="w2k">Windows 2000</option>
                            <option value="w2k3">Windows 2003</option>
                            <option value="w2k8">Windows 2008</option>
                            <option value="wvista">Windows Vista</option>
                            <option value="win7">Windows 7</option>
                            <option value="win8">Windows 8/2012</option>
                            <option value="win10">Windows 10/2016</option>
                            <option value="l24">Linux 2.4 Kernel</option>
                            <option value="l26">Linux 2.6+ Kernel</option>
                            <option value="solaris">Solaris</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>KVM NIC Driver</label>
                        <select class="form-control" name="nicdriver">
                            <option value="default">Select...</option>
                            <option value="e1000">Intel E1000 (Windows)</option>
                            <option value="virtio">VirtIO (Linux)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>KVM Storage Driver</label>
                        <select class="form-control" name="storage_driver">
                            <option value="default">Select...</option>
                            <option value="ide">IDE (Windows)</option>
                            <option value="virtio">VirtIO (Linux)</option>
                        </select>
                    </div></div>
                    <div id="admin_createkvm_next" style="display:none;">
                      <div class="form-group">
                          <label>IPv4</label>
                          <input class="form-control" type="text" name="ipv4" id="ipv4" placeholder="1.1.1.5" />
                      </div>
                      <div class="form-group">
                          <label>IPv4 Gateway</label>
                          <input class="form-control" type="text" name="ipv4_gateway" placeholder="1.1.1.1" />
                      </div>
                      <div class="form-group">
                          <label>IPv4 Netmask</label>
                          <input class="form-control" type="text" name="ipv4_netmask" value="255.255.255.0" />
                      </div>
                      <div class="form-group">
                          <label>KVM CPU Cores</label>
                          <input class="form-control" type="text" name="cpucores" placeholder="1" />
                      </div>
                      <div class="form-group">
                          <label>KVM CPU Type</label>
                          <select class="form-control" name="cputype">
                              <option value="default">Select...</option>
                              <option value="host">Host (passthrough)</option>
                              <option value="kvm64">kvm64</option>
                              <option value="qemu64">qemu64</option>
                          </select>
                      </div>
                      <div class="form-group">
                          <label>KVM RAM (MB)</label>
                          <input class="form-control" type="text" name="ram" placeholder="512" />
                      </div>
                      <div class="form-group">
                          <label>KVM Storage Location</label>
                          <select class="form-control" name="storage_location" id="storage_location">
                          	<option value="default">Select...</option>
                          </select>
                      </div>
                      <div class="form-group">
                          <label>KVM Storage Size (GB)</label>
                          <input class="form-control" type="text" name="storage_size" placeholder="20" />
                      </div>
                      <div class="form-group">
                          <label>KVM Bandwidth Limit (GB)</label>
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
                          <input class="form-control" type="number" name="setvlantag" placeholder="no VLAN" max="4094" min="0" value="0" />
                      </div>
                    </div>
                    <input type="hidden" name="token" value="<?php echo Token::generate(); ?>" />
                    <input type="submit" value="Submit" class="btn btn-success" />
                </form>
            </div>
        </div>
    </div>
</div>
