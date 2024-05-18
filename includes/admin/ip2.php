<?php
if(count(get_included_files()) == 1) die("You just broke everything.");
?>
<?php require_once('menu_main.php'); ?>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-body">
              <div class="clearfix"><a class="btn btn-info btn-sm pull-right" href="https://google.com" role="button" target="_blank"><i class="fa fa-book"></i> Related Documentation</a></div>
                <h2 align="center">Manage Secondary IPs</h2><br />
                <?php
                if($ipAddedSuccess) {
                    echo '<div id="adm_message"><div class="alert alert-success" role="alert"><strong>Success:</strong> secondary IP added successfully!</div></div>';
                }else{
                    echo '<div id="adm_message"></div>';
                }
                ?>
                <?php
                $ip2 = escape($db->get('vncp_settings', array('item', '=', 'secondary_ips'))->first()->value);
                if($ip2 == 'true') {
                ?>
                <div class="table-responsive">
                    <table class="table table-hover" id="admin_ip2table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User ID</th>
                                <th>Billing Account ID</th>
                                <th>IP Address</th>
                                <th>Delete</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $admin_ip2 = $db->get('vncp_secondary_ips', array('id', '!=', 0));
                            $admin_ip2 = $admin_ip2->all();
                            for($k = 0; $k < count($admin_ip2); $k++) {
                                echo '<tr>';
                                    echo '<td>'.$admin_ip2[$k]->id.'</td>';
                                    echo '<td>'.$admin_ip2[$k]->user_id.'</td>';
                                    echo '<td>'.$admin_ip2[$k]->hb_account_id.'</td>';
                                    echo '<td>'.$admin_ip2[$k]->address.'</td>';
                                    echo '<td><button id="admin_ip2delete'.$admin_ip2[$k]->id.'" class="btn btn-sm btn-danger" role="'.$admin_ip2[$k]->id.'">Delete</button></td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <h2 align="center">Add Secondary IP</h2>
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
                        <input class="form-control" type="text" name="hbaccountid" />
                    </div>
                    <div class="form-group">
                        <label>IP Address</label>
                        <input class="form-control" type="text" name="ipaddr" />
                    </div>
                    <input type="hidden" name="token" value="<?php echo Token::generate(); ?>" />
                    <input type="submit" value="Submit" class="btn btn-success" />
                </form>
                <?php
                }else{
                    echo '<p>Secondary IPs are not enabled. Go to '.$appname.' settings to enable it.</p>';
                }
                ?>
            </div>
        </div>
    </div>
</div>
