<?php
if(count(get_included_files()) == 1) die("You just broke everything.");
?>
<?php require_once('menu_main.php'); ?>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-body">
              <div class="clearfix"><a class="btn btn-info btn-sm pull-right" href="https://google.com" role="button" target="_blank"><i class="fa fa-book"></i> Related Documentation</a></div>
                <h2 align="center">Manage Cloud</h2><br />
                <?php
                if($cloudCreatedSuccess) {
                    echo '<div id="adm_message"><div class="alert alert-success" role="alert"><strong>Success:</strong> cloud created successfully!</div></div>';
                }else{
                    echo '<div id="adm_message"></div>';
                }
                ?>
                <?php
                $cloud_accounts = escape($db->get('vncp_settings', array('item', '=', 'cloud_accounts'))->first()->value);
                if($cloud_accounts == 'true') {
                ?>
                <div class="table-responsive">
                    <table class="table table-hover" id="admin_cloudtable">
                        <thead>
                            <tr>
                                <th>User ID</th>
                                <th>Billing ID</th>
                                <th>Pool ID</th>
                                <th>Node</th>
                                <th>IP List</th>
                                <th>Suspend</th>
                                <th>Delete</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $admin_datacloud = $db->get('vncp_kvm_cloud', array('user_id', '!=', 0));
                            $admin_datacloud = $admin_datacloud->all();
                            for($k = 0; $k < count($admin_datacloud); $k++) {
                                echo '<tr>';
                                    echo '<td>'.$admin_datacloud[$k]->user_id.'</td>';
                                    echo '<td>'.$admin_datacloud[$k]->hb_account_id.'</td>';
                                    echo '<td>'.$admin_datacloud[$k]->pool_id.'</td>';
                                    echo '<td>'.$admin_datacloud[$k]->nodes.'</td>';
                                    echo '<td>'.$admin_datacloud[$k]->ipv4.'</td>';
                                    if($admin_datacloud[$k]->suspended == 0)
                                        echo '<td><button class="btn btn-danger btn-sm" id="cloudsuspend'.$admin_datacloud[$k]->hb_account_id.'" role="'.$admin_datacloud[$k]->hb_account_id.'">Suspend</button></td>';
                                    else
                                        echo '<td><button class="btn btn-success btn-sm" id="cloudunsuspend'.$admin_datacloud[$k]->hb_account_id.'" role="'.$admin_datacloud[$k]->hb_account_id.'">Unsuspend</button></td>';
                                    echo '<td><button id="admin_clouddelete'.$admin_datacloud[$k]->hb_account_id.'" class="btn btn-sm btn-danger" role="'.$admin_datacloud[$k]->hb_account_id.'">Delete</button></td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <ul class="nav nav-tabs" role="tablist">
                  <li role="presentation" class="active"><a href="#createcloudacc" aria-controls="createcloudacc" role="tab" data-toggle="tab">Create Cloud Account</a></li>
                  <li role="presentation"><a href="#editcloudacc" aria-controls="editcloudacc" role="tab" data-toggle="tab">Edit Cloud Account</a></li>
                </ul>
                <div class="tab-content">
                  <div role="tabpanel" class="tab-pane fade in active" id="createcloudacc"><br />
                    <form role="form" action="" method="POST">
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
                            <select class="form-control" name="node">
                            	<option value="default">Select...</option>
                            	<?php
                            	$cloud_node = $db->get('vncp_nodes', array('id', '!=', 0));
                            	$cloud_node = $cloud_node->all();
                            	for($k = 0; $k < count($cloud_node); $k++) {
                            		echo '<option value="'.$cloud_node[$k]->name.'">'.$cloud_node[$k]->hostname.'</option>';
                            	}
                            	?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>IPv4 List</label>
                            <input class="form-control" type="text" name="ipv4" placeholder="1.1.1.4;1.1.1.5;1.1.1.6" />
                        </div>
                        <div class="form-group">
                            <label>CPU Cores</label>
                            <input class="form-control" type="text" name="cpucores" placeholder="1" />
                        </div>
                        <div class="form-group">
                            <label>CPU Type</label>
                            <select class="form-control" name="cputype">
                                <option value="default">Select...</option>
                                <option value="host">Host (passthrough)</option>
                                <option value="kvm64">kvm64</option>
                                <option value="qemu64">qemu64</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>RAM (MB)</label>
                            <input class="form-control" type="text" name="ram" placeholder="2048" />
                        </div>
                        <div class="form-group">
                            <label>Storage Size (GB)</label>
                            <input class="form-control" type="text" name="storage_size" placeholder="100" />
                        </div>
                        <input type="hidden" name="whatform" value="createcloud" />
                        <input type="hidden" name="token" value="<?php echo Token::generate(); ?>" />
                        <input type="submit" value="Submit" class="btn btn-success" />
                    </form>
                  </div>
                  <div role="tabpanel" class="tab-pane fade" id="editcloudacc"><br />
                    <form role="form">
                        <div class="form-group">
                            <label>Billing Account ID</label>
                            <select class="form-control" id="getcloudhbid">
                            	<option value="default">Select...</option>
                            	<?php
                            	$cloud_hbid = $db->get('vncp_kvm_cloud', array('user_id', '!=', 0));
                            	$cloud_hbid = $cloud_hbid->all();
                            	for($k = 0; $k < count($cloud_hbid); $k++) {
                            		echo '<option value="'.$cloud_hbid[$k]->hb_account_id.'">'.$cloud_hbid[$k]->hb_account_id.'</option>';
                            	}
                            	?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>All IPv4 List</label>
                            <input class="form-control" type="text" id="getipv4" placeholder="1.1.1.4;1.1.1.5;1.1.1.6" />
                        </div>
                        <div class="form-group">
                            <label>Available IPv4 List</label>
                            <input class="form-control" type="text" id="getipv4_avail" placeholder="1.1.1.4;1.1.1.6" />
                        </div>
                        <div class="form-group">
                            <label>All CPU Cores</label>
                            <input class="form-control" type="text" id="getcpucores" placeholder="3" />
                        </div>
                        <div class="form-group">
                            <label>Available CPU Cores</label>
                            <input class="form-control" type="text" id="getcpucores_avail" placeholder="1" />
                        </div>
                        <div class="form-group">
                            <label>All RAM (MB)</label>
                            <input class="form-control" type="text" id="getram" placeholder="2048" />
                        </div>
                        <div class="form-group">
                            <label>Available RAM (MB)</label>
                            <input class="form-control" type="text" id="getram_avail" placeholder="512" />
                        </div>
                        <div class="form-group">
                            <label>All Storage Size (GB)</label>
                            <input class="form-control" type="text" id="getstorage_size" placeholder="100" />
                        </div>
                        <div class="form-group">
                            <label>Available Storage Size (GB)</label>
                            <input class="form-control" type="text" id="getstorage_size_avail" placeholder="30" />
                        </div>
                        <input type="submit" value="Submit" class="btn btn-success" id="editcloudaccount" />
                    </form>
                  </div>
                </div>
                <?php
                }else{
                    echo '<p>Cloud accounts are not enabled. Go to '.$appname.' settings to enable it.</p>';
                }
                ?>
            </div>
        </div>
    </div>
</div>
