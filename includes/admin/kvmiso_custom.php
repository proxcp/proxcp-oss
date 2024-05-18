<?php
if(count(get_included_files()) == 1) die("You just broke everything.");
?>
<?php require_once('menu_main.php'); ?>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-body">
              <div class="clearfix"><a class="btn btn-info btn-sm pull-right" href="https://google.com" role="button" target="_blank"><i class="fa fa-book"></i> Related Documentation</a></div>
                <h2 align="center">Manage Custom KVM ISOs</h2><br />
                <?php
                $custom_iso = escape($db->get('vncp_settings', array('item', '=', 'user_iso_upload'))->first()->value);
                if($custom_iso == 'true') {
                ?>
                <div id="adm_message"></div>
                <div class="table-responsive">
                    <table class="table table-hover" id="admin_kvmisotable">
                        <thead>
                            <tr>
                                <th>User ID</th>
                                <th>Friendly Name</th>
                                <th>Type</th>
                                <th>Real Name</th>
                                <th>Upload Date</th>
                                <th>Status</th>
                                <th>Delete</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $admin_datakvmiso = $db->get('vncp_kvm_isos_custom', array('id', '!=', 0));
                            $admin_datakvmiso = $admin_datakvmiso->all();
                            for($k = 0; $k < count($admin_datakvmiso); $k++) {
                                echo '<tr>';
                                    echo '<td>'.$admin_datakvmiso[$k]->user_id.'</td>';
                                    echo '<td>'.$admin_datakvmiso[$k]->fname.'</td>';
                                    echo '<td>'.$admin_datakvmiso[$k]->type.'</td>';
                                    echo '<td>'.$admin_datakvmiso[$k]->upload_key.'.iso</td>';
                                    echo '<td>'.$admin_datakvmiso[$k]->upload_date.'</td>';
                                    echo '<td>'.$admin_datakvmiso[$k]->status.'</td>';
                                    echo '<td><button id="admin_kvmcustomisodelete'.$admin_datakvmiso[$k]->id.'" class="btn btn-sm btn-danger" role="'.$admin_datakvmiso[$k]->id.'">Delete</button></td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <?php
                }else{
                    echo '<p>User ISO uploads are not enabled. Go to '.$appname.' settings to enable it.</p>';
                }
                ?>
            </div>
        </div>
    </div>
</div>
