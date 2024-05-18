<?php
if(count(get_included_files()) == 1) die("You just broke everything.");
?>
<?php require_once('menu_main.php'); ?>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-body">
              <div class="clearfix"><a class="btn btn-info btn-sm pull-right" href="https://google.com" role="button" target="_blank"><i class="fa fa-book"></i> Related Documentation</a></div>
                <h2 align="center">Manage LXC/KVM Properties</h2><br />
                <h4 align="center">This form makes property changes in ProxCP only. Additional changes may need to be made in Proxmox too.</h4><br />
                <?php
                if($editedSuccess) {
                    echo '<div id="adm_message"><div class="alert alert-success" role="alert"><strong>Success:</strong> properties changed successfully!</div></div>';
                }else{
                    echo '<div id="adm_message"></div>';
                }
                ?>
                <form role="form" action="" method="POST">
                    <div class="form-group">
                        <label>Billing Account ID</label>
                        <select class="form-control" name="hbaccountid" id="queryvmprops">
                            <option value="default">Select...</option>
                            <?php
                            $hbaccountid = $db->get('vncp_lxc_ct', array('hb_account_id', '!=', 0));
                            $hbaccountid = $hbaccountid->all();
                            for($k = 0; $k < count($hbaccountid); $k++) {
                                echo '<option value="'.$hbaccountid[$k]->hb_account_id.'">ID: '.$hbaccountid[$k]->hb_account_id.' - LXC - '.$hbaccountid[$k]->ip.'</option>';
                            }
                            ?>
                            <?php
                            $hbaccountid = $db->get('vncp_kvm_ct', array('hb_account_id', '!=', 0));
                            $hbaccountid = $hbaccountid->all();
                            for($k = 0; $k < count($hbaccountid); $k++) {
                                echo '<option value="'.$hbaccountid[$k]->hb_account_id.'">ID: '.$hbaccountid[$k]->hb_account_id.' - KVM - '.$hbaccountid[$k]->ip.'</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>User ID</label>
                        <input class="form-control" type="text" name="userid" id="userid" />
                    </div>
                    <div class="form-group">
                        <label>Node</label>
                        <input class="form-control" type="text" name="vmnode" id="vmnode" />
                    </div>
                    <div class="form-group">
                        <label>Operating System</label>
                        <input class="form-control" type="text" name="vmos" id="vmos" />
                    </div>
                    <div class="form-group">
                        <label>IPv4 Address</label>
                        <input class="form-control" type="text" name="vmip" id="vmip" />
                    </div>
                    <div class="form-group">
                        <label>IPv4 Gateway</label>
                        <input class="form-control" type="text" name="vmip_gateway" id="vmip_gateway" />
                    </div>
                    <div class="form-group">
                        <label>IPv4 Netmask</label>
                        <input class="form-control" type="text" name="vmip_netmask" id="vmip_netmask" />
                    </div>
                    <div class="form-group">
                        <label>Allow Backups (1 or 0)</label>
                        <input class="form-control" type="text" name="vm_backups" id="vm_backups" />
                    </div>
                    <div class="form-group">
                        <label>Backup Limit Override</label>
                        <input class="form-control" type="text" name="vm_backup_override" id="vm_backup_override" />
                        <p class="help-block">Values: -1 = use global limit; Any other number will override global backup limit for this VM.</p>
                    </div>
                    <div class="form-group">
                      <label>Proxmox Pool Name</label>
                      <input class="form-control" type="text" name="vm_poolname" id="vm_poolname" readonly />
                    </div>
                    <div class="form-group">
                      <label>Proxmox Pool Password <small>modify only</small></label>
                      <input class="form-control" type="password" name="vm_poolpw" id="vm_poolpw" />
                    </div>
                    <input type="hidden" name="token" value="<?php echo Token::generate(); ?>" />
                    <input type="hidden" name="vmip_old" id="vmip_old" value="" />
                    <input type="submit" value="Submit" class="btn btn-success" />
                </form>
            </div>
        </div>
    </div>
</div>
