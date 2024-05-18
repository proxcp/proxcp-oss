<?php
if(count(get_included_files()) == 1) die("You just broke everything.");
?>
<?php
require_once('menu_main.php');
$t = Token::generate();
?>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-body">
              <div class="clearfix"><a class="btn btn-info btn-sm pull-right" href="https://google.com" role="button" target="_blank"><i class="fa fa-book"></i> Related Documentation</a></div>
                <h2 align="center"><?php echo $appname; ?> Settings</h2>
                <?php
                if($adminSettingsUpdated) {
                    echo '<div id="adm_message"><div class="alert alert-success" role="alert"><strong>Success:</strong> application settings updated!</div></div>';
                }else{
                    echo '<div id="adm_message"></div>';
                }
                ?>
                <?php
                $fetch = $db->get('vncp_settings', array('id', '!=', 0));
                $fetch = $fetch->all();
                $current = array();
                for($i = 0; $i < count($fetch); $i++) {
                	$current[$fetch[$i]->item] = $fetch[$i]->value;
                }
                ?>
                <div>
                  <ul class="nav nav-tabs" role="tablist">
                    <li role="presentation" class="active"><a href="#general" aria-controls="general" role="tab" data-toggle="tab">General</a></li>
                    <li role="presentation"><a href="#mail" aria-controls="mail" role="tab" data-toggle="tab">Mail</a></li>
                  </ul>
                  <div class="tab-content">
                    <div role="tabpanel" class="tab-pane fade in active" id="general"><br />
                      <form role="form" action="" method="POST">
                      	<div class="form-group">
                      	    <label>App Name</label>
                      	    <input class="form-control" type="text" name="app_name" value="<?php echo escape($current['app_name']); ?>" />
                      	</div>
                        <div class="form-group">
                      	    <label>Default Language</label>
                            <select class="form-control" name="default_language">
                              <option value="<?php echo escape($current['default_language']).'.json'; ?>"><?php echo escape($current['default_language']).'.json'; ?></option>
                              <?php
                              foreach(glob("lang/*.json") as $file) {
                                $file = explode('/', $file)[1];
                                if($file != $current['default_language'].'.json') {
                                  echo '<option value="'.$file.'">'.$file.'</option>';
                                }
                              }
                              ?>
                            </select>
                      	</div>
                        <div class="form-group">
                      	    <label>Enable WHMCS Invoice/Support Integration</label>
                            <select class="form-control" name="enable_whmcs">
                              <?php
                              if($current['enable_whmcs'] == 'false')
                                echo '<option value="false">False</option><option value="true">True</option>';
                              else
                                echo '<option value="true">True</option><option value="false">False</option>';
                              ?>
                            </select>
                      	</div>
                        <?php
                        if($current['enable_whmcs'] == 'true') {
                        ?>
                        <div class="form-group">
                            <label>WHMCS API URL</label>
                            <input class="form-control" type="text" name="whmcs_url" value="<?php echo $current['whmcs_url']; ?>" />
                        </div>
                        <div class="form-group">
                            <label>WHMCS API ID</label>
                            <input class="form-control" type="text" name="whmcs_id" value="<?php echo $current['whmcs_id']; ?>" />
                        </div>
                        <div class="form-group">
                            <label>WHMCS API Key</label>
                            <input class="form-control" type="text" name="whmcs_key" value="<?php echo $current['whmcs_key']; ?>" />
                        </div>
                        <?php
                        }
                        ?>
                          <div class="form-group">
                              <label>Enable Firewall</label>
                              <select class="form-control" name="enable_firewall">
                              	<?php
                              	if($current['enable_firewall'] == 'false')
                              		echo '<option value="false">False</option><option value="true">True</option>';
                              	else
                              		echo '<option value="true">True</option><option value="false">False</option>';
                              	?>
                              </select>
                          </div>
                          <div class="form-group">
                              <label>Enable Forward DNS</label>
                              <select class="form-control" name="enable_forward_dns">
                              	<?php
                              	if($current['enable_forward_dns'] == 'false')
                              		echo '<option value="false">False</option><option value="true">True</option>';
                              	else
                              		echo '<option value="true">True</option><option value="false">False</option>';
                              	?>
                              </select>
                          </div>
                          <div class="form-group">
                              <label>Enable Reverse DNS</label>
                              <select class="form-control" name="enable_reverse_dns">
                              	<?php
                              	if($current['enable_reverse_dns'] == 'false')
                              		echo '<option value="false">False</option><option value="true">True</option>';
                              	else
                              		echo '<option value="true">True</option><option value="false">False</option>';
                              	?>
                              </select>
                          </div>
                          <?php
                          if($current['enable_forward_dns'] == 'true' || $current['enable_reverse_dns'] == 'true') {
                          ?>
                          <div class="form-group">
                              <label>WHM URL</label>
                              <input class="form-control" type="text" name="whmurl" value="<?php echo $current['whm_url']; ?>" />
                          </div>
                          <div class="form-group">
                              <label>WHM Username</label>
                              <input class="form-control" type="text" name="whmusername" value="<?php echo $current['whm_username']; ?>" />
                          </div>
                          <div class="form-group">
                              <label>WHM API Token</label>
                              <input class="form-control" type="text" name="whmapitoken" value="<?php echo $current['whm_api_token']; ?>" />
                          </div>
                          <?php
                          }
                          if($current['enable_forward_dns'] == 'true') {
                          ?>
                          <div class="form-group">
                              <label>Forward DNS Domain Limit (per user)</label>
                              <input class="form-control" type="text" name="fdnslimit" value="<?php echo $current['forward_dns_domain_limit']; ?>" />
                          </div>
                          <div class="form-group">
                              <label>Forward DNS Domain Blacklist (; separated)</label>
                              <input class="form-control" type="text" name="fdnsblacklist" value="<?php echo $current['forward_dns_blacklist']; ?>" />
                          </div>
                          <div class="form-group">
                              <label>Forward DNS Nameservers (; separated)</label>
                              <input class="form-control" type="text" name="fdnsnameservers" value="<?php echo $current['forward_dns_nameservers']; ?>" />
                          </div>
                          <?php
                          }
                          ?>
                          <div class="form-group">
                              <label>Enable Notepad</label>
                              <select class="form-control" name="enable_notepad">
                              	<?php
                              	if($current['enable_notepad'] == 'false')
                              		echo '<option value="false">False</option><option value="true">True</option>';
                              	else
                              		echo '<option value="true">True</option><option value="false">False</option>';
                              	?>
                              </select>
                          </div>
                          <div class="form-group">
                              <label>Enable Status</label>
                              <select class="form-control" name="enable_status">
                              	<?php
                              	if($current['enable_status'] == 'false')
                              		echo '<option value="false">False</option><option value="true">True</option>';
                              	else
                              		echo '<option value="true">True</option><option value="false">False</option>';
                              	?>
                              </select>
                          </div>
                          <div class="form-group">
                              <label>Enable App News</label>
                              <select class="form-control" name="enable_panel_news">
                              	<?php
                              	if($current['enable_panel_news'] == 'false')
                              		echo '<option value="false">False</option><option value="true">True</option>';
                              	else
                              		echo '<option value="true">True</option><option value="false">False</option>';
                              	?>
                              </select>
                          </div>
                          <?php
                          if($current['enable_panel_news'] == 'true') {
                          ?>
                          <div class="form-group">
                              <label>App News</label>
                              <textarea class="form-control" name="panel_news" rows="5" style="resize:none;"><?php echo $current['panel_news']; ?></textarea>
                          </div>
                          <?php
                          }
                          ?>
                          <div class="form-group">
                              <label>Support Ticket URL</label>
                              <input class="form-control" type="text" name="support_ticket_url" value="<?php echo escape($current['support_ticket_url']); ?>" />
                          </div>
                          <div class="form-group">
                              <label>Enable User ACL</label>
                              <select class="form-control" name="user_acl">
                              	<?php
                              	if($current['user_acl'] == 'false')
                              		echo '<option value="false">False</option><option value="true">True</option>';
                              	else
                              		echo '<option value="true">True</option><option value="false">False</option>';
                              	?>
                              </select>
                          </div>
                          <div class="form-group">
                              <label>Enable Cloud Accounts</label>
                              <select class="form-control" name="cloud_accounts">
                              	<?php
                              	if($current['cloud_accounts'] == 'false')
                              		echo '<option value="false">False</option><option value="true">True</option>';
                              	else
                              		echo '<option value="true">True</option><option value="false">False</option>';
                              	?>
                              </select>
                          </div>
                          <div class="form-group">
                              <label>Enable VM IPv6</label>
                              <select class="form-control" name="vm_ipv6">
                              	<?php
                              	if($current['vm_ipv6'] == 'false')
                              		echo '<option value="false">False</option><option value="true">True</option>';
                              	else
                              		echo '<option value="true">True</option><option value="false">False</option>';
                              	?>
                              </select>
                          </div>
                          <?php
                          if($current['vm_ipv6'] == 'true') {
                          ?>
                          <div class="form-group">
                              <label>IPv6 Assignment Mode</label>
                              <select class="form-control" name="ipv6mode">
                              	<?php
                              	if($current['ipv6_mode'] == 'single')
                              		echo '<option value="single">Single</option><option value="subnet">Subnet</option>';
                              	else
                              		echo '<option value="subnet">Subnet</option><option value="single">Single</option>';
                              	?>
                              </select>
                          </div>
                          <?php
                          }
                          ?>
                          <?php if($current['vm_ipv6'] == 'true' && $current['ipv6_mode'] == 'single') { ?>
                          <div class="form-group">
                              <label>IPv6 Limit (per VM)</label>
                              <input class="form-control" type="text" name="ipv6lim" value="<?php echo $current['ipv6_limit']; ?>" />
                          </div>
                        <?php }else if($current['vm_ipv6'] == 'true' && $current['ipv6_mode'] == 'subnet') { ?>
                          <div class="form-group">
                              <label>IPv6 /64 Subnets (per VM)</label>
                              <input class="form-control" type="text" name="ipv6limsubnet" value="<?php echo $current['ipv6_limit_subnet']; ?>" />
                          </div>
                        <?php } ?>
                          <div class="form-group">
                              <label>Enable Private Networking</label>
                              <select class="form-control" name="private_networking">
                              	<?php
                              	if($current['private_networking'] == 'false')
                              		echo '<option value="false">False</option><option value="true">True</option>';
                              	else
                              		echo '<option value="true">True</option><option value="false">False</option>';
                              	?>
                              </select>
                          </div>
                          <div class="form-group">
                              <label>Auto Suspend Bandwidth Overage <small>runs once per day</small></label>
                              <select class="form-control" name="bw_auto_suspend">
                              	<?php
                              	if($current['bw_auto_suspend'] == 'false')
                              		echo '<option value="false">False</option><option value="true">True</option>';
                              	else
                              		echo '<option value="true">True</option><option value="false">False</option>';
                              	?>
                              </select>
                          </div>
                          <div class="form-group">
                              <label>Enable Secondary IPs</label>
                              <select class="form-control" name="secondary_ips">
                              	<?php
                              	if($current['secondary_ips'] == 'false')
                              		echo '<option value="false">False</option><option value="true">True</option>';
                              	else
                              		echo '<option value="true">True</option><option value="false">False</option>';
                              	?>
                              </select>
                          </div>
                          <div class="form-group">
                              <label>Enable VM Backups</label>
                              <select class="form-control" name="vmbackups">
                                  <?php
                                  if($current['enable_backups'] == 'false')
                                      echo '<option value="false">False</option><option value="true">True</option>';
                                  else
                                      echo '<option value="true">True</option><option value="false">False</option>';
                                  ?>
                              </select>
                          </div>
                          <?php
                          if($current['enable_backups'] == 'true') {
                          ?>
                          <div class="form-group">
                              <label>Backup Limit (per VM)</label>
                              <input class="form-control" type="text" name="backuplim" value="<?php echo $current['backup_limit']; ?>" />
                          </div>
                          <?php
                          }
                          ?>
                          <div class="form-group">
                              <label>Enable User ISO Uploads</label>
                              <select class="form-control" name="user_iso_upload">
                              	<?php
                              	if($current['user_iso_upload'] == 'false')
                              		echo '<option value="false">False</option><option value="true">True</option>';
                              	else
                              		echo '<option value="true">True</option><option value="false">False</option>';
                              	?>
                              </select>
                          </div>
                          <input type="hidden" name="token" value="<?php echo $t; ?>" />
                          <input type="hidden" name="whatform" value="general_settings" />
                          <input type="submit" value="Submit" class="btn btn-success" />
                      </form>
                    </div>
                    <div role="tabpanel" class="tab-pane fade" id="mail"><br />
                      <form role="form" action="" method="POST">
                          <div class="form-group">
                            <label>Mail Type</label>
                            <select class="form-control" name="mail_type">
                              <?php
                              if($current['mail_type'] == 'sysmail')
                                echo '<option value="sysmail">PHP mail()</option><option value="smtp">SMTP</option>';
                              else
                                echo '<option value="smtp">SMTP</option><option value="sysmail">PHP mail()</option>';
                              ?>
                            </select>
                          </div>
                          <hr />
                          <div class="form-group">
                            <label>From Name</label>
                            <input class="form-control" type="text" name="from_email_addr_name" value="<?php echo escape($current['from_email_name']); ?>" />
                          </div>
                          <div class="form-group">
                              <label>From Email Address</label>
                              <input class="form-control" type="text" name="from_email_addr" value="<?php echo escape($current['from_email']); ?>" />
                          </div>
                          <hr />
                          <div class="form-group">
                              <label>SMTP Host</label>
                              <input class="form-control" type="text" name="smtp_host" value="<?php echo escape($current['smtp_host']); ?>" />
                          </div>
                          <div class="form-group">
                              <label>SMTP Port</label>
                              <input class="form-control" type="number" name="smtp_port" value="<?php echo escape($current['smtp_port']); ?>" min="1" max="65535" />
                          </div>
                          <div class="form-group">
                              <label>SMTP Username</label>
                              <input class="form-control" type="text" name="smtp_username" value="<?php echo escape($current['smtp_username']); ?>" />
                          </div>
                          <div class="form-group">
                              <label>SMTP Password</label>
                              <input class="form-control" type="password" name="smtp_password" value="<?php echo escape($current['smtp_password']); ?>" />
                          </div>
                          <div class="form-group">
                            <label>SMTP Security</label>
                            <select class="form-control" name="smtp_type">
                              <?php
                              if($current['smtp_type'] == 'none')
                                echo '<option value="none">None</option><option value="ssltls">SSL/TLS</option><option value="starttls">STARTTLS</option>';
                              else if($current['smtp_type'] == 'ssltls')
                                echo '<option value="ssltls">SSL/TLS</option><option value="none">None</option><option value="starttls">STARTTLS</option>';
                              else
                                echo '<option value="starttls">STARTTLS</option><option value="none">None</option><option value="ssltls">SSL/TLS</option>';
                              ?>
                            </select>
                          </div>
                          <input type="hidden" name="token" value="<?php echo $t; ?>" />
                          <input type="hidden" name="whatform" value="mail_settings" />
                          <input type="submit" value="Submit" class="btn btn-success" />
                      </form>
                    </div>
                  </div>
                </div>
            </div>
        </div>
    </div>
</div>
