<?php
if(count(get_included_files()) == 1) die("You just broke everything.");
?>
<?php require_once('menu_main.php'); ?>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-body">
                <div class="clearfix"><a class="btn btn-info btn-sm pull-right" href="https://google.com" role="button" target="_blank"><i class="fa fa-book"></i> Related Documentation</a></div>
                <h2 align="center">Manage Users</h2><br />
                <?php
                if($userCreatedSuccess) {
                    echo '<div id="adm_message"><div class="alert alert-success" role="alert"><strong>New User Password:</strong> <input type="text" class="form-control" value="'.$plaintext_user_password.'" /></div></div>';
                }else{
                    echo '<div id="adm_message"></div>';
                }
                ?>
                <div class="table-responsive">
                    <table class="table table-hover" id="admin_usertable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Email / Username</th>
                                <th>Group</th>
                                <th>Change Password</th>
                                <th>Access</th>
                                <th>Account Lock</th>
                                <th>Delete</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $admin_datausers = $db->get('vncp_users', array('id', '!=', 0));
                            $admin_users = $admin_datausers->all();
                            for($k = 0; $k < count($admin_users); $k++) {
                                echo '<tr>';
                                    echo '<td>'.$admin_users[$k]->id.'</td>';
                                    echo '<td>'.$admin_users[$k]->email.'</td>';
                                    if($admin_users[$k]->group == 2)
                                        echo '<td>Administrator</td>';
                                    else
                                        echo '<td>Standard user</td>';
                                    echo '<td><button class="btn btn-default btn-sm" id="acctpw'.$admin_users[$k]->id.'" role="'.$admin_users[$k]->id.'">Change</button></td>';
                                    if($admin_users[$k]->group == 1)
                                      echo '<td><a class="btn btn-default btn-sm" href="admin?action=admaccess&id='.$admin_users[$k]->id.'">Access</a></td>';
                                    else
                                      echo '<td>N/A</td>';
                                    if($admin_users[$k]->locked == 0)
                                        echo '<td><button class="btn btn-danger btn-sm" id="acctlock'.$admin_users[$k]->id.'" role="'.$admin_users[$k]->id.'">Lock</button></td>';
                                    else
                                        echo '<td><button class="btn btn-success btn-sm" id="acctunlock'.$admin_users[$k]->id.'" role="'.$admin_users[$k]->id.'">Unlock</button></td>';
                                    echo '<td><button id="admin_userdelete'.$admin_users[$k]->id.'" class="btn btn-sm btn-danger" role="'.$admin_users[$k]->id.'">Delete</button></td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <ul class="nav nav-tabs" role="tablist">
                  <li role="presentation" class="active"><a href="#newuser" aria-controls="newuser" role="tab" data-toggle="tab">Add New User</a></li>
                  <li role="presentation"><a href="#changeusername" aria-controls="changeusername" role="tab" data-toggle="tab">Change Username</a></li>
                </ul>
                <div class="tab-content">
                  <div role="tabpanel" class="tab-pane fade in active" id="newuser"><br />
                    <form role="form" action="" method="POST">
                        <div class="form-group">
                            <label>Email / Username</label>
                            <input class="form-control" type="email" name="email" placeholder="user@domain.com" />
                        </div>
                        <div class="form-group">
                            <label>Group</label>
                            <select class="form-control" name="group">
                              <option value="default">Select...</option>
                              <option value="1">User</option>
                              <option value="2">Admin</option>
                            </select>
                        </div>
                        <input type="hidden" name="form_name" value="new_user_form" />
                        <input type="submit" value="Submit" class="btn btn-success" />
                    </form>
                  </div>
                  <div role="tabpanel" class="tab-pane fade" id="changeusername"><br />
                    <form role="form" action="" method="POST">
                        <div class="form-group">
                            <label>Select User</label>
                            <select class="form-control" name="which_user">
                              <option value="default">Select...</option>
                              <?php
                              $usersDB = $db->get_users_asc('vncp_users', array('id', '!=', 0));
                              $usersDB = $usersDB->all();
                              for($i = 0; $i < count($usersDB); $i++) {
                                echo '<option value="' . $usersDB[$i]->username . '">' . $usersDB[$i]->username . '</option>';
                              }
          							    	?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>New Username</label>
                            <input class="form-control" type="text" name="username" />
                        </div>
                        <input type="hidden" name="form_name" value="change_username_form" />
                        <input type="submit" value="Submit" class="btn btn-success" />
                    </form>
                  </div>
                </div>
            </div>
        </div>
    </div>
</div>
