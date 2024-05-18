<?php
if(count(get_included_files()) == 1) die("You just broke everything.");
?>
<?php require_once('menu_main.php'); ?>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-body">
              <div class="clearfix"><a class="btn btn-info btn-sm pull-right" href="https://google.com" role="button" target="_blank"><i class="fa fa-book"></i> Related Documentation</a></div>
                <h2 align="center">System Logs</h2><br />
                <div>
                    <ul class="nav nav-tabs" role="tablist">
                        <li role="presentation" class="active"><a href="#general" aria-controls="general" role="tab" data-toggle="tab">General</a></li>
                        <li role="presentation"><a href="#admin" aria-controls="admin" role="tab" data-toggle="tab">Admin</a></li>
                        <li role="presentation"><a href="#error" aria-controls="error" role="tab" data-toggle="tab">Error</a></li>
                    </ul>
                    <div class="tab-content">
                        <div role="tabpanel" class="tab-pane fade in active" id="general">
                            <br />
                            <div class="table-responsive">
                                <table class="table table-hover" id="admin_general_log">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th>Severity</th>
                                            <th>Date</th>
                                            <th>Username</th>
                                            <th>IP Address</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $general_log = $log->get('general');
                                        for($i = 0; $i < count($general_log); $i++) {
                                            echo '<tr>';
                                            echo '<td>' . $general_log[$i]->msg . '</td>';
                                            echo '<td>' . severity($general_log[$i]->severity) . '</td>';
                                            echo '<td>' . $general_log[$i]->date . '</td>';
                                            echo '<td>' . $general_log[$i]->username . '</td>';
                                            echo '<td>' . $general_log[$i]->ipaddress . '</td>';
                                            echo '</tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div role="tabpanel" class="tab-pane fade in" id="admin">
                            <br />
                            <div class="table-responsive">
                                <table class="table table-hover" id="admin_admin_log">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th>Severity</th>
                                            <th>Date</th>
                                            <th>Username</th>
                                            <th>IP Address</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $admin_log = $log->get('admin');
                                        for($i = 0; $i < count($admin_log); $i++) {
                                            echo '<tr>';
                                            echo '<td>' . $admin_log[$i]->msg . '</td>';
                                            echo '<td>' . severity($admin_log[$i]->severity) . '</td>';
                                            echo '<td>' . $admin_log[$i]->date . '</td>';
                                            echo '<td>' . $admin_log[$i]->username . '</td>';
                                            echo '<td>' . $admin_log[$i]->ipaddress . '</td>';
                                            echo '</tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div role="tabpanel" class="tab-pane fade in" id="error">
                            <br />
                            <div class="table-responsive">
                                <table class="table table-hover" id="admin_error_log">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th>Severity</th>
                                            <th>Date</th>
                                            <th>Username</th>
                                            <th>IP Address</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $error_log = $log->get('error');
                                        for($i = 0; $i < count($error_log); $i++) {
                                            echo '<tr>';
                                            echo '<td>' . $error_log[$i]->msg . '</td>';
                                            echo '<td>' . severity($error_log[$i]->severity) . '</td>';
                                            echo '<td>' . $error_log[$i]->date . '</td>';
                                            echo '<td>' . $error_log[$i]->username . '</td>';
                                            echo '<td>' . $error_log[$i]->ipaddress . '</td>';
                                            echo '</tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <h2 align="center">Purge Old Logs</h2>
                <?php $today = getdate(); ?>
                <form role="form" action="" method="POST">
                    <div class="form-group">
                        <label>Log Type</label>
                        <select class="form-control" name="logtype">
                            <option value="default">Select...</option>
                            <option value="general">general</option>
                            <option value="admin">admin</option>
                            <option value="error">error</option>
                        </select>
                    </div>
                    <?php
                    if((int)$today['mday'] == 1) {
                      $longbois = [1,3,5,7,8,10,12];
                      $normalbois = [4,6,9,11];
                      $month = (int)$today['mon'];
                      if(in_array($month, $longbois)) {
                        $remove_day = 31;
                      }else if(in_array($month, $normalbois)) {
                        $remove_day = 30;
                      }else{
                        $remove_day = 28;
                      }
                    }else{
                      $remove_day = ((int)$today['mday']) - 1;
                    }
                    ?>
                    <div class="form-group">
                        <label>Remove Log Entries Before Date</label>
                        <input class="form-control" type="text" name="purgedate" value="<?php echo $today['year']; ?>-<?php echo $today['mon']; ?>-<?php echo $remove_day; ?>" />
                    </div>
                    <input type="hidden" name="token" value="<?php echo Token::generate(); ?>" />
                    <input type="submit" value="Submit" class="btn btn-success" />
                </form>
            </div>
        </div>
    </div>
</div>
