<?php
if(count(get_included_files()) == 1) die("You just broke everything.");
?>
<?php require_once('menu_main.php'); ?>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-body">
              <div class="clearfix"><a class="btn btn-info btn-sm pull-right" href="https://google.com" role="button" target="_blank"><i class="fa fa-book"></i> Related Documentation</a></div>
                <h2 align="center">Manage Bandwidth</h2><br />
                <div class="table-responsive">
                    <table class="table table-hover" id="admin_usertable">
                        <thead>
                            <tr>
                                <th>Billing Account ID</th>
                                <th>VM Type</th>
                                <th>Usage Percentage</th>
                                <th>Bandwidth Limit</th>
                                <th>Reset Date</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $admin_datausers = $db->get('vncp_bandwidth_monitor', array('id', '!=', 0));
                            $admin_users = $admin_datausers->all();
                            for($k = 0; $k < count($admin_users); $k++) {
                                echo '<tr>';
                                    echo '<td>'.$admin_users[$k]->hb_account_id.'</td>';
                                    if($admin_users[$k]->ct_type == 'qemu')
                                      echo '<td>KVM</td>';
                                    else
                                      echo '<td>LXC</td>';
                                    $usage = round(((float)$admin_users[$k]->current / (float)$admin_users[$k]->max) * 100, 2);
                                    echo '<td>'.$usage.'%</td>';
                                    echo '<td>'.read_bytes_size($admin_users[$k]->max).'</td>';
                                    echo '<td>'.$admin_users[$k]->reset_date.'</td>';
                                    echo '<td><button class="btn btn-info btn-sm" id="resetbw'.$admin_users[$k]->id.'" role="'.$admin_users[$k]->id.'">Reset Now</button></td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
