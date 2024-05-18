<?php
if(count(get_included_files()) == 1) die("You just broke everything.");
?>
<?php require_once('menu_main.php'); ?>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-body">
              <div class="clearfix"><a class="btn btn-info btn-sm pull-right" href="https://google.com" role="button" target="_blank"><i class="fa fa-book"></i> Related Documentation</a></div>
                <h2 align="center">Manage IPv4 Pool</h2>
                <h4 align="center">The <?php echo $appname; ?> IPv4  Pool is used for the billing modules and manual tracking.</h4><br />
                <?php
                if($pubAddedSuccess) {
                    echo '<div id="adm_message"><div class="alert alert-success" role="alert"><strong>Success:</strong> IPv4 pool added successfully!</div></div>';
                }else{
                    echo '<div id="adm_message"></div>';
                }
                ?>
                <?php
                $poolexists = $db->get('vncp_ipv4_pool', array('id', '!=', 0))->all();
                if(count($poolexists) > 0) {
                ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-info">
                            <div class="panel-heading">
                                <h3 class="panel-title">Pool Usage</h3>
                            </div>
                            <div class="panel-body">
                                <?php
                                $total = $db->get('vncp_ipv4_pool', array('id', '!=', 0))->all();
                                $total = count($total);
                                $avail = $db->get('vncp_ipv4_pool', array('available', '=', 1))->all();
                                $avail = count($avail);
                                ?>
                                <h2 align="center"><?php echo $avail; ?> free / <?php echo $total; ?> total</h2>
                            </div>
                        </div>
                    </div>
                </div>
                <h2 align="center">IP Assignments</h2>
                <div class="table-responsive">
                    <table class="table table-hover" id="admin_privatetable">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Billing Account ID</th>
                                <th>IP Address</th>
                                <th>Node</th>
                                <th>Available</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $admin_private = $db->get('vncp_ipv4_pool', array('id', '!=', 0));
                            $admin_private = $admin_private->all();
                            for($k = 0; $k < count($admin_private); $k++) {
                                $uid = 'None';
                                if($admin_private[$k]->user_id != 0) {
                                  $u = $db->get('vncp_users', array('id', '=', $admin_private[$k]->user_id))->first();
                                  $uid = $u->username;
                                }
                                echo '<tr>';
                                    echo '<td>'.$uid.'</td>';
                                    echo '<td>'.$admin_private[$k]->hb_account_id.'</td>';
                                    echo '<td>'.$admin_private[$k]->address.'</td>';
                                    echo '<td>'.$admin_private[$k]->nodes.'</td>';
                                    echo '<td>'.(($admin_private[$k]->available == 0) ? 'No' : 'Yes').'</td>';
                                    if($admin_private[$k]->available == 0) {
                                      echo '<td><button id="admin_publicdelete'.$admin_private[$k]->id.'" class="btn btn-sm btn-danger" role="'.$admin_private[$k]->id.'">Clear Assignment</button></td>';
                                    }else{
                                      echo '<td><div class="input-group input-group-sm"><input type="text" class="form-control" placeholder="Billing ID (must exist)" id="admin_ipseti'.$admin_private[$k]->id.'" /><span class="input-group-btn"><button class="btn btn-success btn-sm" type="button" id="admin_setip'.$admin_private[$k]->id.'" role="'.$admin_private[$k]->id.'">Assign</button></span></div><br /><button id="admin_publicclr'.$admin_private[$k]->id.'" class="btn btn-sm btn-danger" role="'.$admin_private[$k]->id.'">Delete From Pool</button></td>';
                                    }
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <h2 align="center">Add IPv4 Pool (CIDR)</h2>
                <form role="form" action="" method="POST">
                    <div class="form-group">
                        <label>IP CIDR</label>
                        <input class="form-control" type="text" name="cidr" placeholder="1.1.1.0/24" />
                    </div>
                    <div class="form-group">
                        <label>Nodes</label>
                        <select multiple class="form-control" name="pubnodes[]">
                            <?php
                            $getnodes = $db->get('vncp_nodes', array('id', '!=', 0))->all();
                            for($i = 0; $i < count($getnodes); $i++) {
                                echo '<option value="'.$getnodes[$i]->name.'">'.$getnodes[$i]->hostname.'</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <input type="hidden" name="token" value="<?php echo Token::generate(); ?>" />
                    <input type="hidden" name="form_name" value="add_cidr" />
                    <input type="submit" value="Submit" class="btn btn-success" />
                </form>
                <h2 align="center">Add IPv4 Pool (Single)</h2>
                <form role="form" action="" method="POST">
                    <div class="form-group">
                        <label>IPv4 Address</label>
                        <input class="form-control" type="text" name="ipaddress" placeholder="1.1.1.2" />
                    </div>
                    <div class="form-group">
                        <label>IPv4 Gateway</label>
                        <input class="form-control" type="text" name="ipgateway" placeholder="1.1.1.1" />
                    </div>
                    <div class="form-group">
                        <label>IPv4 Netmask</label>
                        <input class="form-control" type="text" name="ipnetmask" placeholder="255.255.255.0" />
                    </div>
                    <div class="form-group">
                        <label>Nodes</label>
                        <select multiple class="form-control" name="pubnodes[]">
                            <?php
                            $getnodes = $db->get('vncp_nodes', array('id', '!=', 0))->all();
                            for($i = 0; $i < count($getnodes); $i++) {
                                echo '<option value="'.$getnodes[$i]->name.'">'.$getnodes[$i]->hostname.'</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <input type="hidden" name="form_name" value="add_single" />
                    <input type="submit" value="Submit" class="btn btn-success" />
                </form>
                <?php
                }else{
                ?>
                <h2 align="center">Add IPv4 Pool</h2>
                <form role="form" action="" method="POST">
                    <div class="form-group">
                        <label>IP CIDR</label>
                        <input class="form-control" type="text" name="cidr" placeholder="1.1.1.0/24" />
                    </div>
                    <div class="form-group">
                        <label>Nodes</label>
                        <select multiple class="form-control" name="pubnodes[]">
                            <?php
                            $getnodes = $db->get('vncp_nodes', array('id', '!=', 0))->all();
                            for($i = 0; $i < count($getnodes); $i++) {
                                echo '<option value="'.$getnodes[$i]->name.'">'.$getnodes[$i]->hostname.'</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <input type="hidden" name="token" value="<?php echo Token::generate(); ?>" />
                    <input type="hidden" name="form_name" value="add_cidr" />
                    <input type="submit" value="Submit" class="btn btn-success" />
                </form>
                <h2 align="center">Add IPv4 Pool (Single)</h2>
                <form role="form" action="" method="POST">
                    <div class="form-group">
                        <label>IPv4 Address</label>
                        <input class="form-control" type="text" name="ipaddress" placeholder="1.1.1.2" />
                    </div>
                    <div class="form-group">
                        <label>IPv4 Gateway</label>
                        <input class="form-control" type="text" name="ipgateway" placeholder="1.1.1.1" />
                    </div>
                    <div class="form-group">
                        <label>IPv4 Netmask</label>
                        <input class="form-control" type="text" name="ipnetmask" placeholder="255.255.255.0" />
                    </div>
                    <div class="form-group">
                        <label>Nodes</label>
                        <select multiple class="form-control" name="pubnodes[]">
                            <?php
                            $getnodes = $db->get('vncp_nodes', array('id', '!=', 0))->all();
                            for($i = 0; $i < count($getnodes); $i++) {
                                echo '<option value="'.$getnodes[$i]->name.'">'.$getnodes[$i]->hostname.'</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <input type="hidden" name="form_name" value="add_single" />
                    <input type="submit" value="Submit" class="btn btn-success" />
                </form>
                <?php
                }
                ?>
            </div>
        </div>
    </div>
</div>
