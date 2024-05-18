<?php
if(count(get_included_files()) == 1) die("You just broke everything.");
?>
<?php require_once('menu_main.php'); ?>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-body">
              <div class="clearfix"><a class="btn btn-info btn-sm pull-right" href="https://google.com" role="button" target="_blank"><i class="fa fa-book"></i> Related Documentation</a></div>
                <h2 align="center">Manage Private Pool</h2><br />
                <?php
                if($privAddedSuccess) {
                    echo '<div id="adm_message"><div class="alert alert-success" role="alert"><strong>Success:</strong> private pool added successfully!</div></div>';
                }else{
                    echo '<div id="adm_message"></div>';
                }
                ?>
                <?php
                $private_networking = escape($db->get('vncp_settings', array('item', '=', 'private_networking'))->first()->value);
                if($private_networking == 'true') {
                ?>
                <?php
                $poolexists = $db->get('vncp_private_pool', array('id', '!=', 0))->all();
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
                                $total = $db->get('vncp_private_pool', array('id', '!=', 0))->all();
                                $total = count($total);
                                $avail = $db->get('vncp_private_pool', array('available', '=', 1))->all();
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
                                <th>User ID</th>
                                <th>Billing Account ID</th>
                                <th>IP Address</th>
                                <th>Clear</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $admin_private = $db->get('vncp_private_pool', array('available', '=', 0));
                            $admin_private = $admin_private->all();
                            for($k = 0; $k < count($admin_private); $k++) {
                                echo '<tr>';
                                    echo '<td>'.$admin_private[$k]->user_id.'</td>';
                                    echo '<td>'.$admin_private[$k]->hb_account_id.'</td>';
                                    echo '<td>'.$admin_private[$k]->address.'</td>';
                                    echo '<td><button id="admin_privatedelete'.$admin_private[$k]->id.'" class="btn btn-sm btn-danger" role="'.$admin_private[$k]->id.'">Clear</button></td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <h2 align="center">Add Private IP Pool</h2>
                <form role="form" action="" method="POST">
                    <div class="form-group">
                        <label>IP CIDR</label>
                        <input class="form-control" type="text" name="cidr" placeholder="1.1.1.5/24" />
                    </div>
                    <div class="form-group">
                        <label>Nodes</label>
                        <select multiple class="form-control" name="privnodes[]">
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
                <h2 align="center">Add Private IP Pool</h2>
                <form role="form" action="" method="POST">
                    <div class="form-group">
                        <label>IP CIDR</label>
                        <input class="form-control" type="text" name="cidr" placeholder="1.1.1.5/24" />
                    </div>
                    <div class="form-group">
                        <label>Nodes</label>
                        <select multiple class="form-control" name="privnodes[]">
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
                    echo '<p>Private networking is not enabled. Go to '.$appname.' settings to enable it.</p>';
                }
                ?>
            </div>
        </div>
    </div>
</div>
