<?php
if(count(get_included_files()) == 1) die("You just broke everything.");
?>
<?php require_once('menu_main.php'); ?>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-body">
              <div class="clearfix"><a class="btn btn-info btn-sm pull-right" href="https://google.com" role="button" target="_blank"><i class="fa fa-book"></i> Related Documentation</a></div>
                <h2 align="center">Manage IPv6</h2><br />
                <?php
                if($ipv6AddedSuccess) {
                    echo '<div id="adm_message"><div class="alert alert-success" role="alert"><strong>Success:</strong> IPv6 pool added successfully!</div></div>';
                }else{
                    echo '<div id="adm_message"></div>';
                }
                ?>
                <?php
                $vm_ipv6 = escape($db->get('vncp_settings', array('item', '=', 'vm_ipv6'))->first()->value);
                if($vm_ipv6 == 'true') {
                ?>
                <?php
                $poolexists = $db->get('vncp_ipv6_pool', array('id', '!=', 0))->all();
                if(count($poolexists) > 0) {
                ?>
                <h2 align="center">IPv6 Pools</h2>
                <div class="table-responsive">
                    <table class="table table-hover" id="admin_v6poolstable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Subnet</th>
                                <th>Nodes</th>
                                <th>Delete</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $admin_v6pools = $db->get('vncp_ipv6_pool', array('id', '!=', 0));
                            $admin_v6pools = $admin_v6pools->all();
                            for($k = 0; $k < count($admin_v6pools); $k++) {
                                echo '<tr>';
                                    echo '<td>'.$admin_v6pools[$k]->id.'</td>';
                                    echo '<td>'.$admin_v6pools[$k]->subnet.'</td>';
                                    echo '<td>'.$admin_v6pools[$k]->nodes.'</td>';
                                    echo '<td><button id="admin_v6pooldelete'.$admin_v6pools[$k]->id.'" class="btn btn-sm btn-danger" role="'.$admin_v6pools[$k]->id.'">Delete</button></td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <h2 align="center">IPv6 Assignments</h2>
                <div class="table-responsive">
                    <table class="table table-hover" id="admin_v6assigntable">
                        <thead>
                            <tr>
                                <th>User ID</th>
                                <th>Billing Account ID</th>
                                <th>Address</th>
                                <th>Delete</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $admin_v6assign = $db->get('vncp_ipv6_assignment', array('id', '!=', 0));
                            $admin_v6assign = $admin_v6assign->all();
                            for($k = 0; $k < count($admin_v6assign); $k++) {
                                echo '<tr>';
                                    echo '<td>'.$admin_v6assign[$k]->user_id.'</td>';
                                    echo '<td>'.$admin_v6assign[$k]->hb_account_id.'</td>';
                                    echo '<td>'.$admin_v6assign[$k]->address.'</td>';
                                    echo '<td><button id="admin_v6assigndelete'.$admin_v6assign[$k]->id.'" class="btn btn-sm btn-danger" role="'.$admin_v6assign[$k]->id.'">Delete</button></td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <h2 align="center">Add IPv6 Pool</h2>
                <form role="form" action="" method="POST">
                    <div class="form-group">
                        <label>IPv6 CIDR</label>
                        <input class="form-control" type="text" name="v6cidr" placeholder="fe80:4500:0:2ec::/64" />
                    </div>
                    <div class="form-group">
                        <label>Nodes</label>
                        <select multiple class="form-control" name="v6nodes[]">
                            <?php
                            $getnodes = $db->get('vncp_nodes', array('id', '!=', 0))->all();
                            for($i = 0; $i < count($getnodes); $i++) {
                                echo '<option value="'.$getnodes[$i]->name.'">'.$getnodes[$i]->hostname.'</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <input type="hidden" name="token" value="<?php echo Token::generate(); ?>" />
                    <input type="submit" value="Submit" class="btn btn-success" />
                </form>
                <?php
                }else{
                ?>
                <h2 align="center">Add IPv6 Pool</h2>
                <form role="form" action="" method="POST">
                    <div class="form-group">
                        <label>IPv6 CIDR</label>
                        <input class="form-control" type="text" name="v6cidr" placeholder="fe80:4500:0:2ec::/64" />
                    </div>
                    <div class="form-group">
                        <label>Nodes</label>
                        <select multiple class="form-control" name="v6nodes[]">
                            <?php
                            $getnodes = $db->get('vncp_nodes', array('id', '!=', 0))->all();
                            for($i = 0; $i < count($getnodes); $i++) {
                                echo '<option value="'.$getnodes[$i]->name.'">'.$getnodes[$i]->hostname.'</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <input type="hidden" name="token" value="<?php echo Token::generate(); ?>" />
                    <input type="submit" value="Submit" class="btn btn-success" />
                </form>
                <?php
                }
                ?>
                <?php
                }else{
                    echo '<p>IPv6 networking is not enabled. Go to '.$appname.' settings to enable it.</p>';
                }
                ?>
            </div>
        </div>
    </div>
</div>
