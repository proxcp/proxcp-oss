<?php
if(count(get_included_files()) == 1) die("You just broke everything.");
?>
<?php require_once('menu_main.php'); ?>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-body">
              <div class="clearfix"><a class="btn btn-info btn-sm pull-right" href="https://google.com" role="button" target="_blank"><i class="fa fa-book"></i> Related Documentation</a></div>
                <h2 align="center">Manage API Credentials</h2>
                <h4 align="center">The <?php echo $appname; ?> API is currently only used for the WHMCS & Blesta billing modules.</h4><br />
                <div class="table-responsive">
                    <table class="table table-hover" id="admin_lxctemptable">
                        <thead>
                            <tr>
                                <th>API ID</th>
                                <th>API Key</th>
                                <th>API IP Address</th>
                                <th>Delete</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $admin_datalxctemp = $db->get('vncp_api', array('id', '!=', 0));
                            $admin_datalxctemp = $admin_datalxctemp->all();
                            for($k = 0; $k < count($admin_datalxctemp); $k++) {
                                echo '<tr>';
                                    echo '<td>'.$admin_datalxctemp[$k]->api_id.'</td>';
                                    echo '<td>'.$admin_datalxctemp[$k]->api_key.'</td>';
                                    echo '<td>'.$admin_datalxctemp[$k]->api_ip.'</td>';
                                    echo '<td><button id="admin_apidelete'.$admin_datalxctemp[$k]->id.'" class="btn btn-sm btn-danger" role="'.$admin_datalxctemp[$k]->id.'">Delete</button></td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <h2 align="center">Add New API ID</h2>
                <form role="form" action="" method="POST">
                    <div class="form-group">
                        <label>IP Restriction</label>
                        <input class="form-control" type="text" name="apiip" />
                    </div>
                    <input type="hidden" name="token" value="<?php echo Token::generate(); ?>" />
                    <input type="submit" value="Submit" class="btn btn-success" />
                </form>
            </div>
        </div>
    </div>
</div>
