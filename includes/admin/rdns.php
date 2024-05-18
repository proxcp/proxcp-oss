<?php
if(count(get_included_files()) == 1) die("You just broke everything.");
?>
<?php require_once('menu_main.php'); ?>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-body">
              <div class="clearfix"><a class="btn btn-info btn-sm pull-right" href="https://google.com" role="button" target="_blank"><i class="fa fa-book"></i> Related Documentation</a></div>
                <h2 align="center">Manage rDNS/PTR</h2><br />
                <div id="adm_message"></div>
                <?php
                $rdns = escape($db->get('vncp_settings', array('item', '=', 'enable_reverse_dns'))->first()->value);
                if($rdns == 'true') {
                ?>
                <div class="table-responsive">
                    <table class="table table-hover" id="admin_ptrtable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User ID</th>
                                <th>Type</th>
                                <th>IP Address</th>
                                <th>Hostname</th>
                                <th>Delete</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $admin_domains = $db->get('vncp_reverse_dns', array('id', '!=', 0));
                            $admin_domains = $admin_domains->all();
                            for($k = 0; $k < count($admin_domains); $k++) {
                                echo '<tr>';
                                    echo '<td>'.$admin_domains[$k]->id.'</td>';
                                    echo '<td>'.$admin_domains[$k]->client_id.'</td>';
                                    echo '<td>'.$admin_domains[$k]->type.'</td>';
                                    echo '<td>'.$admin_domains[$k]->ipaddress.'</td>';
                                    echo '<td>'.$admin_domains[$k]->hostname.'</td>';
                                    echo '<td><button id="admin_ptrdelete'.$admin_domains[$k]->id.'" class="btn btn-sm btn-danger" role="'.$admin_domains[$k]->id.'">Delete</button></td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <?php
                }else{
                    echo '<p>Reverse DNS is not enabled. Go to '.$appname.' settings to enable it.</p>';
                }
                ?>
            </div>
        </div>
    </div>
</div>
