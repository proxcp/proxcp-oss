<?php
if(count(get_included_files()) == 1) die("You just broke everything.");
?>
<?php require_once('menu_main.php'); ?>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-body">
              <div class="clearfix"><a class="btn btn-info btn-sm pull-right" href="https://google.com" role="button" target="_blank"><i class="fa fa-book"></i> Related Documentation</a></div>
                <h2 align="center">Manage Domains</h2><br />
                <div id="adm_message"></div>
                <?php
                $fdns = escape($db->get('vncp_settings', array('item', '=', 'enable_forward_dns'))->first()->value);
                if($fdns == 'true') {
                ?>
                <div class="table-responsive">
                    <table class="table table-hover" id="admin_domainstable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User ID</th>
                                <th>Domain</th>
                                <th>Delete</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $admin_domains = $db->get('vncp_forward_dns_domain', array('id', '!=', 0));
                            $admin_domains = $admin_domains->all();
                            for($k = 0; $k < count($admin_domains); $k++) {
                                echo '<tr>';
                                    echo '<td>'.$admin_domains[$k]->id.'</td>';
                                    echo '<td>'.$admin_domains[$k]->client_id.'</td>';
                                    echo '<td>'.$admin_domains[$k]->domain.'</td>';
                                    echo '<td><button id="admin_domaindelete'.$admin_domains[$k]->id.'" class="btn btn-sm btn-danger" role="'.$admin_domains[$k]->id.'">Delete</button></td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <h2 align="center">Manage Records</h2>
                <div class="table-responsive">
                    <table class="table table-hover" id="admin_recordstable">
                        <thead>
                            <tr>
                                <th>Domain</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Address</th>
                                <th>CNAME</th>
                                <th>Exchange</th>
                                <th>TXT</th>
                                <th>Delete</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $admin_records = $db->get('vncp_forward_dns_record', array('id', '!=', 0));
                            $admin_records = $admin_records->all();
                            for($k = 0; $k < count($admin_records); $k++) {
                                echo '<tr>';
                                    echo '<td>'.$admin_records[$k]->domain.'</td>';
                                    echo '<td>'.$admin_records[$k]->name.'</td>';
                                    echo '<td>'.$admin_records[$k]->type.'</td>';
                                    echo '<td>'.$admin_records[$k]->address.'</td>';
                                    echo '<td>'.$admin_records[$k]->cname.'</td>';
                                    echo '<td>'.$admin_records[$k]->exchange.'</td>';
                                    echo '<td>'.$admin_records[$k]->txtdata.'</td>';
                                    echo '<td><button id="admin_recorddelete'.$admin_records[$k]->id.'" class="btn btn-sm btn-danger" role="'.$admin_records[$k]->id.'">Delete</button></td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <?php
                }else{
                    echo '<p>Forward DNS is not enabled. Go to '.$appname.' settings to enable it.</p>';
                }
                ?>
            </div>
        </div>
    </div>
</div>
