<?php
if(count(get_included_files()) == 1) die("You just broke everything.");
?>
<?php require_once('menu_main.php'); ?>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-body">
              <div class="clearfix"><a class="btn btn-info btn-sm pull-right" href="https://google.com" role="button" target="_blank"><i class="fa fa-book"></i> Related Documentation</a></div>
                <h2 align="center">Manage KVM Templates</h2><br />
                <?php
                if($kvmTempSuccess) {
                    echo '<div id="adm_message"><div class="alert alert-success" role="alert"><strong>Success:</strong> template added to database! Make sure the template is in proxmox too.</div></div>';
                }else{
                    echo '<div id="adm_message"></div>';
                }
                ?>
                <div class="table-responsive">
                    <table class="table table-hover" id="admin_lxctemptable">
                        <thead>
                            <tr>
                                <th>Friendly Name</th>
                                <th>VMID@Node</th>
                                <th>Type</th>
                                <th>Delete</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $admin_datalxctemp = $db->get('vncp_kvm_templates', array('id', '!=', 0));
                            $admin_datalxctemp = $admin_datalxctemp->all();
                            for($k = 0; $k < count($admin_datalxctemp); $k++) {
                                echo '<tr>';
                                    echo '<td>'.$admin_datalxctemp[$k]->friendly_name.'</td>';
                                    echo '<td>'.$admin_datalxctemp[$k]->vmid.'@'.$admin_datalxctemp[$k]->node.'</td>';
                                    echo '<td>'.$admin_datalxctemp[$k]->type.'</td>';
                                    echo '<td><button id="admin_kvmtempdelete'.$admin_datalxctemp[$k]->id.'" class="btn btn-sm btn-danger" role="'.$admin_datalxctemp[$k]->id.'">Delete</button></td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <h2 align="center">Add New KVM Template</h2>
                <form role="form" action="" method="POST">
                    <div class="form-group">
                        <label>Friendly Name</label>
                        <input class="form-control" type="text" name="fname" placeholder="CentOS 7" />
                    </div>
                    <div class="form-group">
                        <label>VMID</label>
                        <input class="form-control" type="number" min="100" name="template_vmid" placeholder="100" />
                    </div>
                    <div class="form-group">
                      <label>Template Type</label>
                      <select class="form-control" name="template_type">
                        <option value="default">Select...</option>
                        <option value="windows">Windows</option>
                        <option value="linux">Linux</option>
                      </select>
                    </div>
                    <div class="form-group">
                        <label>Node</label>
                        <select class="form-control" name="template_node">
                            <option value="default">Select...</option>
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
            </div>
        </div>
    </div>
</div>
