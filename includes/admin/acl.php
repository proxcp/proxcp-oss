<?php
if(count(get_included_files()) == 1) die("You just broke everything.");
?>
<?php require_once('menu_main.php'); ?>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-body">
              <div class="clearfix"><a class="btn btn-info btn-sm pull-right" href="https://google.com" role="button" target="_blank"><i class="fa fa-book"></i> Related Documentation</a></div>
                <h2 align="center">Manage User ACL</h2><br />
                <?php
                $user_acl = escape($db->get('vncp_settings', array('item', '=', 'user_acl'))->first()->value);
                if($user_acl == 'true') {
                ?>
                <div class="table-responsive">
                    <table class="table table-hover" id="admin_acltable">
                        <thead>
                            <tr>
                                <th>User ID</th>
                                <th>IP Address</th>
                                <th>Delete</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $admin_dataacl = $db->get('vncp_acl', array('id', '!=', 0));
                            $admin_dataacl = $admin_dataacl->all();
                            for($k = 0; $k < count($admin_dataacl); $k++) {
                                echo '<tr>';
                                    echo '<td>'.$admin_dataacl[$k]->user_id.'</td>';
                                    echo '<td>'.$admin_dataacl[$k]->ipaddress.'</td>';
                                    echo '<td><button id="admin_acldelete'.$admin_dataacl[$k]->id.'" class="btn btn-sm btn-danger" role="'.$admin_dataacl[$k]->id.'">Delete</button></td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <?php
                }else{
                    echo '<p>User ACL is not enabled. Go to '.$appname.' settings to enable it.</p>';
                }
                ?>
            </div>
        </div>
    </div>
</div>
