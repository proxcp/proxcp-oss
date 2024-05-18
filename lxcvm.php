<?php
if(!defined('constant')) {
  header('Location: index');
}
?>
<div class="col-md-12">
	<!-- Feature with tabs -->
    <section class="feature-tabs">
     <div class="first-sliding">
     </div>
     <div class="wrap">
      <div class="tabs">
       <div class="tabs_container">
       <!-- TAB -->
        <li class="btnLink smallBtnLink green_tab">
            <a class="tab-action active" data-tab-cnt="tab1">
                <span><i class="fa fa-wrench"></i> General</span>
            </a></li>
        <!-- TAB END -->
        <!-- TAB -->
        <li class="btnLink smallBtnLink green_tab">
            <a class="tab-action" data-tab-cnt="tab4">
                <span><i class="fa fa-desktop"></i> Device Manager</span>
            </a></li>
        <!-- TAB END -->
        <!-- TAB -->
        <li class="btnLink smallBtnLink green_tab">
            <a class="tab-action" data-tab-cnt="tab7">
                <span><i class="fa fa-bolt"></i> Network Manager</span>
            </a></li>
        <!-- TAB END -->
        <!-- TAB -->
        <li class="btnLink smallBtnLink green_tab">
            <a class="tab-action" data-tab-cnt="tab2">
                <span><i class="fa fa-area-chart"></i> Resource Graphs</span>
            </a></li>
        <!-- TAB END -->
        <!-- TAB -->
        <li class="btnLink smallBtnLink green_tab">
            <a class="tab-action" data-tab-cnt="tab3">
                <span><i class="fa fa-cubes"></i> Rebuild</span>
            </a></li>
        <!-- TAB END -->
        <!-- TAB -->
        <li class="btnLink smallBtnLink green_tab">
            <a class="tab-action" data-tab-cnt="tab5">
                <span><i class="fa fa-hdd-o"></i> Backups</span>
            </a></li>
        <!-- TAB END -->
        <!-- TAB -->
        <li class="btnLink smallBtnLink green_tab">
            <a class="tab-action" data-tab-cnt="tab6">
                <span><i class="fa fa-terminal"></i> Console</span>
            </a></li>
        <!-- TAB END -->
       </div>
       <br>
       <div class="clr"></div>
       <!-- TAB CONTENT -->
       <div id="tab1" class="tab-single tab-cnt active">
            <div class="datacenters">
                <div class="col-md-12">
		            <div id="func_error"></div>
		            <h1 align="center" id="status_1">Server Status: <span class="label" id="status_2"><img src="img/loader.GIF" id="loader" /></span></h1>
		            <?php
		            $results = $db->get('vncp_lxc_ct', array('hb_account_id', '=', escape($_GET['id'])));
		            $data = $results->first();
		            $node_results = $db->get('vncp_nodes', array('name', '=', $data->node));
		            $node_data = $node_results->first();
		            $pxAPI = new PVE2_API($node_data->hostname, $node_data->username, $node_data->realm, decryptValue($node_data->password));
		            $noLogin = false;
		            if(!$pxAPI->login()) $noLogin = true;
		            if($noLogin == false) {
		            	$vminfo = $pxAPI->get('/pools/'.$data->pool_id);
		            	$vmdetails = $pxAPI->get('/nodes/'.$data->node.'/lxc/'.$vminfo['members'][0]['vmid'].'/status/current');
		            }
		            ?>
		            <br />
		            <div class="row">
		            	<div class="col-md-12">
		            		<div class="panel panel-default">
		            			<div class="panel-body">
		            				<div class="col-md-2"><p><em>CPU Usage</em></p></div>
		            				<div class="progress">
		            					<div class="progress-bar progress-bar-info progress-bar-striped" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="min-width: 2em;width: 100%;" id="cpu_usage_1"><div id="cpu_usage_2"></div></div>
		            				</div>
		            				<div class="col-md-2"><p><em>RAM Usage</em></p></div>
		            				<div class="progress">
		            					<div class="progress-bar progress-bar-info progress-bar-striped" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="min-width: 2em;width: 100%;" id="ram_usage_1"><div id="ram_usage_2"></div></div>
		            				</div>
		            				<div class="col-md-2"><p><em>Disk Usage</em></p></div>
		            				<div class="progress">
		            					<div class="progress-bar progress-bar-info progress-bar-striped" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="min-width: 2em;width: 100%;" id="disk_usage_1"><div id="disk_usage_2"></div></div>
		            				</div>
		            				<div class="col-md-2"><p><em>Swap Usage</em></p></div>
		            				<div class="progress">
		            					<div class="progress-bar progress-bar-info progress-bar-striped" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="min-width: 2em;width: 100%;" id="swap_usage_1"><div id="swap_usage_2"></div></div>
		            				</div>
                        <?php
                        $bwstats = $db->get('vncp_bandwidth_monitor', array('hb_account_id', '=', escape($_GET['id'])))->first();
                        $bwperc = round(((float)$bwstats->current / (float)$bwstats->max) * 100, 2);
                        $bwperc_single = round($bwperc, 0);
                        ?>
                        <div class="col-md-2"><p><em>Bandwidth Usage</em></p></div>
		            				<div class="progress">
		            					<div class="progress-bar progress-bar-info progress-bar-striped" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="min-width: 2em;width: <?php echo $bwperc_single; ?>%;"><div><?php echo $bwperc; ?>%</div></div>
		            				</div>
                        <div class="col-md-2"><p><em>Total Storage</em></p></div>
                        <div>
                          <?php echo (ceil((int)$vmdetails['maxdisk'] / 1073741824)); ?>GB
                        </div>
		            			</div>
		            		</div>
		                </div>
		            </div>
		            <div class="row">
		            	<div class="col-md-3">
		            		<?php
		                    if($data->suspended == 0) {
		                        echo '<button class="btn btn-block btn-lg btn-success" disabled="disabled" id="start_server">
		                        <center>
		                            <i class="fa fa-play fa-4x"></i>
		                            <div>Start Server</div>
		                        </center>
		                    </button>';
		                    }else{
		                        echo '<button class="btn btn-block btn-lg btn-success" disabled="disabled">
		                        <center>
		                            <i class="fa fa-play fa-4x"></i>
		                            <div>Start Server</div>
		                        </center>
		                    </button>';
		                    }
		                    ?>
		            	</div>
		            	<div class="col-md-3">
		            		<?php
		                    if($data->suspended == 0) {
		                        echo '<button class="btn btn-block btn-lg btn-danger" disabled="disabled" id="shutdown_server">
		                        <center>
		                            <i class="fa fa-stop fa-4x"></i>
		                            <div>Shutdown Server</div>
		                        </center>
		                    </button>';
		                    }else{
		                        echo '<button class="btn btn-block btn-lg btn-danger" disabled="disabled">
		                        <center>
		                            <i class="fa fa-stop fa-4x"></i>
		                            <div>Shutdown Server</div>
		                        </center>
		                    </button>';
		                    }
		                    ?>
		            	</div>
		            		<div class="col-md-3">
		            			<?php
		            	        if($data->suspended == 0) {
		            	            echo '<button class="btn btn-block btn-lg btn-warning" disabled="disabled" id="restart_server">
		            	            <center>
		            	                <i class="fa fa-refresh fa-4x"></i>
		            	                <div>Restart Server</div>
		            	            </center>
		            	        </button>';
		            	        }else{
		            	            echo '<button class="btn btn-block btn-lg btn-warning" disabled="disabled">
		            	            <center>
		            	                <i class="fa fa-refresh fa-4x"></i>
		            	                <div>Restart Server</div>
		            	            </center>
		            	        </button>';
		            	        }
		            	        ?>
		            		</div>
		            	<div class="col-md-3">
		            		<?php
		                    if($data->suspended == 0) {
		                        echo '<button class="btn btn-block btn-lg btn-info" disabled="disabled" id="kill_server">
		                        <center>
		                            <i class="fa fa-times fa-4x"></i>
		                            <div>Kill Server</div>
		                        </center>
		                    </button>';
		                    }else{
		                        echo '<button class="btn btn-block btn-lg btn-info" disabled="disabled">
		                        <center>
		                            <i class="fa fa-times fa-4x"></i>
		                            <div>Kill Server</div>
		                        </center>
		                    </button>';
		                    }
		                    ?>
		            	</div>
		            </div>
		            <br />
                <?php
                $LXCNAT = count($db->get('vncp_natforwarding', array('hb_account_id', '=', $data->hb_account_id))->all());
                ?>
		            <div class="row">
		            	<div class="col-md-6 col-md-offset-3">
		                    <div class="table-responsive">
		                        <table class="table table-striped">
		                            <tr>
		                                <td>Uptime</td>
		                                <td id="uptime">0 seconds</td>
		                            </tr>
		                            <tr>
		                                <td>Hostname</td>
		                                <td><?php echo $vmdetails['name']; ?> ( LXC )</td>
		                            </tr>
		                            <tr>
		                                <td>Primary IP</td>
		                                <td><?php echo $data->ip; ?></td>
		                            </tr>
                                <?php if($LXCNAT == 1) { ?>
                                <tr>
                                  <td>Public IP</td>
                                  <td><?php
                                  $natnode = $db->get('vncp_nat', array('node', '=', $data->node))->all();
                                  if(count($natnode) == 1) {
                                    echo $natnode[0]->publicip;
                                  }else{
                                    echo 'Error';
                                  }
                                  ?></td>
                                </tr>
                                <?php } ?>
		                            <tr>
		                                <td>Operating System</td>
		                                <td><?php echo $data->os; ?></td>
		                            </tr>
		                            <tr>
		                            	<td>VPS Node</td>
		                            	<td><?php echo $data->node; ?></td>
		                            </tr>
		                        </table>
		                    </div>
		                </div>
		            </div>
                </div>
            </div>
       </div>
       <!-- TAB CONTENT END -->
        <!-- TAB CONTENT -->
        <div id="tab4" class="tab-single tab-cnt">
              <div class="datacenters">
                 <div class="col-md-12">
                    <div id="lxc_pwsuccess"></div>
                     <div class="table-responsive">
                       <table class="table table-striped">
                         <tr>
                           <th>Option</th>
                           <th>Description</th>
                           <th>Manage</th>
                         </tr>
                         <tr>
                           <td>TUN/TAP</td>
                           <td>Enable or disable the tun/tap device on your VPS. Useful for some VPN software.</td>
                           <?php
                           if($data->tuntap == 0 && $data->suspended == 0) {
                            echo '<td><button class="btn btn-success btn-sm" id="enabletap">Enable</button></td>';
                           }else if($data->tuntap == 1 && $data->suspended == 0) {
                            echo '<td><button class="btn btn-danger btn-sm" id="disabletap">Disable</button></td>';
                           }else{
                            echo '<td><button class="btn btn-warning btn-sm" disabled>Service suspended</button></td>';
                           }
                           ?>
                         </tr>
                         <tr>
                           <td>Start at boot</td>
                           <td>Enable or disable starting your VPS at node boot.</td>
                           <?php
                           if($data->onboot == 0 && $data->suspended == 0) {
                            echo '<td><button class="btn btn-success btn-sm" id="enableonboot">Enable</button></td>';
                           }else if($data->onboot == 1 && $data->suspended == 0) {
                            echo '<td><button class="btn btn-danger btn-sm" id="disableonboot">Disable</button></td>';
                           }else{
                            echo '<td><button class="btn btn-warning btn-sm" disabled>Service suspended</button></td>';
                           }
                           ?>
                         </tr>
                         <tr>
                           <td>Quotas</td>
                           <td>Enable or disable disk quotas on your VPS.</td>
                           <?php
                           if($data->quotas == 0 && $data->suspended == 0) {
                            echo '<td><button class="btn btn-success btn-sm" id="enablequotas">Enable</button></td>';
                           }else if($data->quotas == 1 && $data->suspended == 0) {
                            echo '<td><button class="btn btn-danger btn-sm" id="disablequotas">Disable</button></td>';
                           }else{
                            echo '<td><button class="btn btn-warning btn-sm" disabled>Service suspended</button></td>';
                           }
                           ?>
                         </tr>
                         <tr>
                           <td>VPS Task Log</td>
                           <td><textarea class="form-control" id="vmlog" rows="10" style="resize:none;font-family: Menlo,Monaco,Consolas,monospace;font-size:13px;" disabled>null</textarea></td>
                           <td><button class="btn btn-info btn-sm" id="getvmlog">Fetch Log</button><br /><br /><button class="btn btn-info btn-sm" id="clearvmlog">Clear Log</button></td>
                         </tr>
                         <tr>
                           <td>Change Root Password</td>
                           <td>Forgot your root password? Change it here.</td>
                           <?php
                           if($data->suspended == 0) {
                             echo '<td><button class="btn btn-success btn-sm" id="chgrootpw_lxc">Change Password</button></td>';
                           }else{
                             echo '<td><button class="btn btn-warning btn-sm" disabled>Service suspended</button></td>';
                           }
                           ?>
                         </tr>
                       </table>
                     </div>
                 </div>
             </div>
        </div>
        <!-- TAB CONTENT END -->
        <!-- TAB CONTENT -->
        <?php
        $portforwards = $db->get('vncp_natforwarding', array('hb_account_id', '=', $data->hb_account_id))->all();
        if(count($portforwards) == 1) {
          $portlimit = $portforwards[0]->avail_ports;
          $domainlimit = $portforwards[0]->avail_domains;
          $ports = (string)(count(explode(";", $portforwards[0]->ports)) - 1);
          $domains = (string)(count(explode(";", $portforwards[0]->domains)) - 1);
        }
        ?>
        <div id="tab7" class="tab-single tab-cnt">
              <div class="datacenters">
                <div class="col-md-3">
                	<div class="tabpanel">
	                    <ul class="nav nav-pills nav-stacked" role="tablist">
	                    	<li role="presentation" class="active"><a href="#ipam" aria-controls="ipam" role="tab" data-toggle="pill"><i class="fa fa-location-arrow"></i> IPAM</a></li>
                        <?php if($LXCNAT == 1) { ?>
                        <li role="presentation"><a href="#natports" aria-controls="natports" role="tab" data-toggle="pill"><i class="fa fa-arrows-h"></i> NAT Ports (<?php echo $ports; ?> / <?php echo $portlimit; ?>)</a></li>
                        <li role="presentation"><a href="#natdomains" aria-controls="natdomains" role="tab" data-toggle="pill"><i class="fa fa-globe"></i> NAT Domains (<?php echo $domains; ?> / <?php echo $domainlimit; ?>)</a></li>
                        <?php } ?>
	                    </ul>
	                </div>
	               </div>
                 <div class="col-md-9">
                   <div class="tabpanel">
                     <div class="tab-content">
                       <div role="tabpanel" class="tab-pane active" id="ipam">
                         <?php
                         $ip_values = $db->get('vncp_dhcp', array('ip', '=', $data->ip))->first();
                         ?>
                           <h2 align="center">Public Network</h2>
                           <div class="table-responsive">
                            <table class="table table-striped">
                              <tr>
                                <th>IPv4 Address</th>
                                <th>Gateway</th>
                                <th>Netmask</th>
                                <th>Assignment</th>
                              </tr>
                              <tr>
                                <td><?php echo $data->ip; ?></td>
                                <td><?php echo $ip_values->gateway; ?></td>
                                <td><?php echo $ip_values->netmask; ?></td>
                                <td><?php if($ip_values->type == 0) echo 'primary'; else echo 'secondary'; ?></td>
                              </tr>
                            </table>
                           </div>
                           <br />
                           <?php
                           $vm_ipv6 = escape($db->get('vncp_settings', array('item', '=', 'vm_ipv6'))->first()->value);
                           $v6mode = escape($db->get('vncp_settings', array('item', '=', 'ipv6_mode'))->first()->value);
                           $private_networking = escape($db->get('vncp_settings', array('item', '=', 'private_networking'))->first()->value);
                           if($vm_ipv6 == 'true') {
                           ?>
                           <div class="table-responsive">
                            <table class="table table-striped">
                              <tr>
                                <th>IPv6 Address</th>
                                <th>Gateway</th>
                                <th>Netmask</th>
                              </tr>
                              <?php
                              $v6data = $db->get('vncp_ipv6_assignment', array('hb_account_id', '=', escape($_GET['id'])));
                              $v6results = $db->all();
                              if(count($v6results) > 0) {
                                for($i = 0; $i < count($v6results); $i++) {
                                  $v6pool = $db->get('vncp_ipv6_pool', array('id', '=', $v6results[$i]->ipv6_pool_id))->first();
                                  echo '<tr>';
                                    echo '<td>' . $v6results[$i]->address . '</td>';
                                    echo '<td>' . explode('/', $v6pool->subnet)[0] . '1</td>';
                                    echo '<td>' . explode('/', $v6pool->subnet)[1] . '</td>';
                                  echo '</tr>';
                                }
                              }
                              ?>
                            </table>
                           </div>
                           <?php if(count($v6results) < 1) { ?><button type="button" class="btn btn-success btn-block" id="assignipv6">Assign IPv6 Address (<?php echo count($v6results); ?> of 1)</button><?php }} ?>
                           <?php if($private_networking == 'true') { ?>
                           <h2 align="center">Private Network</h2>
                           <?php if($data->has_net1 == 1) { ?>
                           <div class="table-responsive">
                            <table class="table table-striped">
                              <tr>
                                <th>IPv4 Address</th>
                                <th>Netmask</th>
                                <th></th>
                              </tr>
                              <tr>
                                <?php
                                $getprivateip = $db->get('vncp_private_pool', array('hb_account_id', '=', escape($_GET['id'])));
                                $ipresults = $getprivateip->first();
                                echo '<td>' . $ipresults->address . '</td>';
                                ?>
                                <td><?php echo $ipresults->netmask; ?></td>
                                <td><button type="button" class="btn btn-danger btn-md" id="disableprivatenet">Disable Private Networking</button></td>
                              </tr>
                            </table>
                           </div>
                           <?php }else{ ?>
                           <button type="button" class="btn btn-success btn-block" id="enableprivatenet">Enable Private Networking</button><br /><br />
                         <?php }} ?>
                       </div>
                       <?php if($LXCNAT == 1) { ?>
                       <div role="tabpanel" class="tab-pane" id="natports">
                         <form role="form">
 		                        <div class="form-group">
 		                            <label>NAT Port</label>
 		                            <input class="form-control" type="number" id="chosennatport" autocomplete="off" min="1" max="65535" />
 		                            <p class="help-block" style="color:red;" id="natport_error"></p>
 		                        </div>
 		                        <div class="form-group">
 		                            <label>Description <small>max 20 characters</small></label>
 		                            <input class="form-control" type="text" id="natportdesc" autocomplete="off" />
 		                            <p class="help-block" style="color:red;" id="natdesc_error"></p>
 		                        </div>
 		                        <input type="hidden" id="aid" value="<?php echo escape($data->hb_account_id); ?>" />
 		                        <?php
 		                        if($data->suspended == 0) {
 		                            echo '<input type="submit" id="natport_btn" value="Create Port Forward" class="btn btn-info btn-lg" />';
 		                        }else{
 		                            echo '<div class="btn btn-info btn-lg" disabled>Create Port Forward</div>';
 		                        }
 		                        ?>
 		                    </form><br /><br />
                         <div class="table-responsive">
                           <table class="table table-striped table-condensed" id="user_porttable">
                             <tr>
                               <th>Public Side</th>
                               <th></th>
                               <th>NAT Side</th>
                               <th>Description</th>
                               <th></th>
                             </tr>
                             <?php
                             $allpdata = explode(";", $portforwards[0]->ports);
                             for($i = 0; $i < $ports; $i++) {
                               $pdata = explode(":", $allpdata[$i]);
                               echo '<tr>';
                                echo '<td>' . $natnode[0]->publicip . ':' . $pdata[1] . '</td>';
                                echo '<td><i class="fa fa-arrow-right"></i></td>';
                                echo '<td>' . $data->ip . ':' . $pdata[2] . '</td>';
                                echo '<td>' . $pdata[3] . '</td>';
                                echo '<td><button id="user_natportdelete' . $pdata[0] . '" class="btn btn-sm btn-danger" role="' . $pdata[0] . '">Delete</button></td>';
                               echo '</tr>';
                             }
                             ?>
                           </table>
                         </div>
                       </div>
                       <div role="tabpanel" class="tab-pane" id="natdomains">
                         <form role="form">
 		                        <div class="form-group">
 		                            <label>Domain</label>
 		                            <input class="form-control" type="text" id="chosendomain" autocomplete="off" placeholder="domain.com" />
                                <p class="help-block">Wildcard forwarding. All requests to domain.com and *.domain.com will be forwarded to this virtual machine.</p>
 		                            <p class="help-block" style="color:red;" id="natdomain_error"></p>
 		                        </div>
                            <p>NAT domains will forward HTTP traffic by default. To enable HTTPS support, paste your SSL certificate details below.</p>
                            <div class="form-group">
 		                            <label>SSL Certificate</label>
                                <textarea class="form-control" rows="5" id="nat_sslcert" placeholder="*Optional*: paste SSL certificate in PEM format"></textarea>
 		                        </div>
                            <div class="form-group">
 		                            <label>SSL Private Key</label>
                                <textarea class="form-control" rows="5" id="nat_sslkey" placeholder="*Optional*: paste SSL private key in PEM format"></textarea>
 		                        </div>
 		                        <input type="hidden" id="aid" value="<?php echo escape($data->hb_account_id); ?>" />
 		                        <?php
 		                        if($data->suspended == 0) {
 		                            echo '<input type="submit" id="natdomain_btn" value="Create Domain Forward" class="btn btn-info btn-lg" />';
 		                        }else{
 		                            echo '<div class="btn btn-info btn-lg" disabled>Create Domain Forward</div>';
 		                        }
 		                        ?>
 		                    </form><br /><br />
                        <div class="table-responsive">
                          <table class="table table-striped table-condensed" id="user_domaintable">
                            <tr>
                              <th>Domain</th>
                              <th>Forwarding</th>
                              <th></th>
                            </tr>
                            <?php
                            $allddata = explode(";", $portforwards[0]->domains);
                            for($i = 0; $i < $domains; $i++) {
                              echo '<tr>';
                               echo '<td>' . $allddata[$i] . '</td>';
                               echo '<td>' . $allddata[$i] . ' <i class="fa fa-arrow-right"></i> ' . $natnode[0]->publicip . ' <i class="fa fa-arrow-right"></i> ' . $data->ip . '</td>';
                               echo '<td><button id="user_natdomaindelete' . $allddata[$i] . '" class="btn btn-sm btn-danger" role="' . $allddata[$i] . '">Delete</button></td>';
                              echo '</tr>';
                            }
                            ?>
                          </table>
                        </div>
                       </div>
                      <?php } ?>
                     </div>
                   </div>
                 </div>
                 <br /><br />
             </div>
        </div>
        <!-- TAB CONTENT END -->
      <!-- TAB CONTENT -->
      <div id="tab2" class="tab-single tab-cnt">
            <div class="datacenters">
               <div class="row">
	                <div class="col-md-4 col-md-offset-2">
	                	<p>Scale:</p>
	                	<select class="form-control" id="graphtime">
	                		<option value="hour">Hour</option>
	                		<option value="day">Day</option>
	                		<option value="week">Week</option>
	                		<option value="month">Month</option>
	                		<option value="year">Year</option>
	                	</select>
	                </div>
               </div>
               <div id="hour">
               		<div class="row">
               			<div class="col-md-12 col-md-offset-2">
               				<h4>CPU Usage</h4>
               				<?php
                            $rrd = $pxAPI->get('/nodes/' . $data->node . '/lxc/' . $vminfo['members'][0]['vmid'] . '/rrd?ds=cpu&timeframe=hour&cf=AVERAGE');
                            echo '<img src="data:image/png;base64,' . base64_encode(utf8_decode($rrd['image'])) . '" />';
                            ?>
               			</div>
               		</div>
               		<div class="row">
               			<div class="col-md-12 col-md-offset-2">
               				<h4>RAM Usage</h4>
               				<?php
                            $rrd = $pxAPI->get('/nodes/' . $data->node . '/lxc/' . $vminfo['members'][0]['vmid'] . '/rrd?ds=mem,maxmem&timeframe=hour&cf=AVERAGE');
                            echo '<img src="data:image/png;base64,' . base64_encode(utf8_decode($rrd['image'])) . '" />';
                            ?>
               			</div>
               		</div>
               		<div class="row">
               			<div class="col-md-12 col-md-offset-2">
               				<h4>Network Usage</h4>
               				<?php
                            $rrd = $pxAPI->get('/nodes/' . $data->node . '/lxc/' . $vminfo['members'][0]['vmid'] . '/rrd?ds=netin,netout&timeframe=hour&cf=AVERAGE');
                            echo '<img src="data:image/png;base64,' . base64_encode(utf8_decode($rrd['image'])) . '" />';
                            ?>
               			</div>
               		</div>
               		<div class="row">
               			<div class="col-md-12 col-md-offset-2">
               				<h4>Disk I/O</h4>
               				<?php
                            $rrd = $pxAPI->get('/nodes/' . $data->node . '/lxc/' . $vminfo['members'][0]['vmid'] . '/rrd?ds=diskread,diskwrite&timeframe=hour&cf=AVERAGE');
                            echo '<img src="data:image/png;base64,' . base64_encode(utf8_decode($rrd['image'])) . '" />';
                            ?>
               			</div>
               		</div>
               </div>
               <div id="day">
               		<div class="row">
               			<div class="col-md-12 col-md-offset-2">
               				<h4>CPU Usage</h4>
               				<?php
                            $rrd = $pxAPI->get('/nodes/' . $data->node . '/lxc/' . $vminfo['members'][0]['vmid'] . '/rrd?ds=cpu&timeframe=day&cf=AVERAGE');
                            echo '<img src="data:image/png;base64,' . base64_encode(utf8_decode($rrd['image'])) . '" />';
                            ?>
               			</div>
               		</div>
               		<div class="row">
               			<div class="col-md-12 col-md-offset-2">
               				<h4>RAM Usage</h4>
               				<?php
                            $rrd = $pxAPI->get('/nodes/' . $data->node . '/lxc/' . $vminfo['members'][0]['vmid'] . '/rrd?ds=mem,maxmem&timeframe=day&cf=AVERAGE');
                            echo '<img src="data:image/png;base64,' . base64_encode(utf8_decode($rrd['image'])) . '" />';
                            ?>
               			</div>
               		</div>
               		<div class="row">
               			<div class="col-md-12 col-md-offset-2">
               				<h4>Network Usage</h4>
               				<?php
                            $rrd = $pxAPI->get('/nodes/' . $data->node . '/lxc/' . $vminfo['members'][0]['vmid'] . '/rrd?ds=netin,netout&timeframe=day&cf=AVERAGE');
                            echo '<img src="data:image/png;base64,' . base64_encode(utf8_decode($rrd['image'])) . '" />';
                            ?>
               			</div>
               		</div>
               		<div class="row">
               			<div class="col-md-12 col-md-offset-2">
               				<h4>Disk I/O</h4>
               				<?php
                            $rrd = $pxAPI->get('/nodes/' . $data->node . '/lxc/' . $vminfo['members'][0]['vmid'] . '/rrd?ds=diskread,diskwrite&timeframe=day&cf=AVERAGE');
                            echo '<img src="data:image/png;base64,' . base64_encode(utf8_decode($rrd['image'])) . '" />';
                            ?>
               			</div>
               		</div>
               </div>
               <div id="week">
               		<div class="row">
               			<div class="col-md-12 col-md-offset-2">
               				<h4>CPU Usage</h4>
               				<?php
                            $rrd = $pxAPI->get('/nodes/' . $data->node . '/lxc/' . $vminfo['members'][0]['vmid'] . '/rrd?ds=cpu&timeframe=week&cf=AVERAGE');
                            echo '<img src="data:image/png;base64,' . base64_encode(utf8_decode($rrd['image'])) . '" />';
                            ?>
               			</div>
               		</div>
               		<div class="row">
               			<div class="col-md-12 col-md-offset-2">
               				<h4>RAM Usage</h4>
               				<?php
                            $rrd = $pxAPI->get('/nodes/' . $data->node . '/lxc/' . $vminfo['members'][0]['vmid'] . '/rrd?ds=mem,maxmem&timeframe=week&cf=AVERAGE');
                            echo '<img src="data:image/png;base64,' . base64_encode(utf8_decode($rrd['image'])) . '" />';
                            ?>
               			</div>
               		</div>
               		<div class="row">
               			<div class="col-md-12 col-md-offset-2">
               				<h4>Network Usage</h4>
               				<?php
                            $rrd = $pxAPI->get('/nodes/' . $data->node . '/lxc/' . $vminfo['members'][0]['vmid'] . '/rrd?ds=netin,netout&timeframe=week&cf=AVERAGE');
                            echo '<img src="data:image/png;base64,' . base64_encode(utf8_decode($rrd['image'])) . '" />';
                            ?>
               			</div>
               		</div>
               		<div class="row">
               			<div class="col-md-12 col-md-offset-2">
               				<h4>Disk I/O</h4>
               				<?php
                            $rrd = $pxAPI->get('/nodes/' . $data->node . '/lxc/' . $vminfo['members'][0]['vmid'] . '/rrd?ds=diskread,diskwrite&timeframe=week&cf=AVERAGE');
                            echo '<img src="data:image/png;base64,' . base64_encode(utf8_decode($rrd['image'])) . '" />';
                            ?>
               			</div>
               		</div>
               </div>
               <div id="month">
               		<div class="row">
               			<div class="col-md-12 col-md-offset-2">
               				<h4>CPU Usage</h4>
               				<?php
                            $rrd = $pxAPI->get('/nodes/' . $data->node . '/lxc/' . $vminfo['members'][0]['vmid'] . '/rrd?ds=cpu&timeframe=month&cf=AVERAGE');
                            echo '<img src="data:image/png;base64,' . base64_encode(utf8_decode($rrd['image'])) . '" />';
                            ?>
               			</div>
               		</div>
               		<div class="row">
               			<div class="col-md-12 col-md-offset-2">
               				<h4>RAM Usage</h4>
               				<?php
                            $rrd = $pxAPI->get('/nodes/' . $data->node . '/lxc/' . $vminfo['members'][0]['vmid'] . '/rrd?ds=mem,maxmem&timeframe=month&cf=AVERAGE');
                            echo '<img src="data:image/png;base64,' . base64_encode(utf8_decode($rrd['image'])) . '" />';
                            ?>
               			</div>
               		</div>
               		<div class="row">
               			<div class="col-md-12 col-md-offset-2">
               				<h4>Network Usage</h4>
               				<?php
                            $rrd = $pxAPI->get('/nodes/' . $data->node . '/lxc/' . $vminfo['members'][0]['vmid'] . '/rrd?ds=netin,netout&timeframe=month&cf=AVERAGE');
                            echo '<img src="data:image/png;base64,' . base64_encode(utf8_decode($rrd['image'])) . '" />';
                            ?>
               			</div>
               		</div>
               		<div class="row">
               			<div class="col-md-12 col-md-offset-2">
               				<h4>Disk I/O</h4>
               				<?php
                            $rrd = $pxAPI->get('/nodes/' . $data->node . '/lxc/' . $vminfo['members'][0]['vmid'] . '/rrd?ds=diskread,diskwrite&timeframe=month&cf=AVERAGE');
                            echo '<img src="data:image/png;base64,' . base64_encode(utf8_decode($rrd['image'])) . '" />';
                            ?>
               			</div>
               		</div>
               </div>
               <div id="year">
               		<div class="row">
               			<div class="col-md-12 col-md-offset-2">
               				<h4>CPU Usage</h4>
               				<?php
                            $rrd = $pxAPI->get('/nodes/' . $data->node . '/lxc/' . $vminfo['members'][0]['vmid'] . '/rrd?ds=cpu&timeframe=year&cf=AVERAGE');
                            echo '<img src="data:image/png;base64,' . base64_encode(utf8_decode($rrd['image'])) . '" />';
                            ?>
               			</div>
               		</div>
               		<div class="row">
               			<div class="col-md-12 col-md-offset-2">
               				<h4>RAM Usage</h4>
               				<?php
                            $rrd = $pxAPI->get('/nodes/' . $data->node . '/lxc/' . $vminfo['members'][0]['vmid'] . '/rrd?ds=mem,maxmem&timeframe=year&cf=AVERAGE');
                            echo '<img src="data:image/png;base64,' . base64_encode(utf8_decode($rrd['image'])) . '" />';
                            ?>
               			</div>
               		</div>
               		<div class="row">
               			<div class="col-md-12 col-md-offset-2">
               				<h4>Network Usage</h4>
               				<?php
                            $rrd = $pxAPI->get('/nodes/' . $data->node . '/lxc/' . $vminfo['members'][0]['vmid'] . '/rrd?ds=netin,netout&timeframe=year&cf=AVERAGE');
                            echo '<img src="data:image/png;base64,' . base64_encode(utf8_decode($rrd['image'])) . '" />';
                            ?>
               			</div>
               		</div>
               		<div class="row">
               			<div class="col-md-12 col-md-offset-2">
               				<h4>Disk I/O</h4>
               				<?php
                            $rrd = $pxAPI->get('/nodes/' . $data->node . '/lxc/' . $vminfo['members'][0]['vmid'] . '/rrd?ds=diskread,diskwrite&timeframe=year&cf=AVERAGE');
                            echo '<img src="data:image/png;base64,' . base64_encode(utf8_decode($rrd['image'])) . '" />';
                            ?>
               			</div>
               		</div>
               </div>
           </div>
      </div>
      <!-- TAB CONTENT END -->
       <!-- TAB CONTENT -->
       <div id="tab3" class="tab-single tab-cnt">
             <div class="datacenters">
                <div class="col-md-12">
                    <div class="row">
		                <div class="col-md-6">
		                    <div id="info_box"></div>
		                    <form role="form">
		                        <div class="form-group">
		                            <label>Operating System Template</label>
		                            <select class="form-control" id="os">
		                            	<option value="default">Select...</option>
		                                <?php
		                                $content = $db->get('vncp_lxc_templates', array('content', '=', 'vztmpl'));
		                                $contentr = $content->all();
		                                for($i = 0; $i < count($contentr); $i++) {
		                                	echo '<option value="'.$contentr[$i]->volid.'">'.$contentr[$i]->friendly_name.'</option>';
		                                }
		                                ?>
		                            </select>
		                            <p class="help-block" style="color:red;" id="os_error"></p>
		                        </div>
		                        <div class="form-group">
		                            <label>Hostname</label>
		                            <input class="form-control" type="text" id="hostname" autocomplete="off" />
		                            <p class="help-block" style="color:red;" id="hostname_error"></p>
		                        </div>
		                        <div class="form-group">
		                            <label>Root Password</label>
		                            <input class="form-control" type="password" id="password" autocomplete="off" />
		                            <p class="help-block" style="color:red;" id="password_error"></p>
		                            <p class="help-block">This password is not stored in our database. Remember it!</p>
		                        </div>
		                        <input type="hidden" id="aid" value="<?php echo escape($data->hb_account_id); ?>" />
		                        <?php
		                        if($data->suspended == 0) {
		                            echo '<input type="submit" id="rebuild_btn" value="Rebuild" class="btn btn-info btn-lg" />';
		                        }else{
		                            echo '<div class="btn btn-info btn-lg" disabled>Rebuild</div>';
		                        }
		                        ?>
		                    </form>
		                </div>
		                <div class="col-md-6">
		                    <?php
		                    $result_blog = $db->limit_get_desc('vncp_users_rebuild_log', array('client_id', '=', $user->data()->id), '1');
		                    $data_blog = $result_blog->first();
		                    ?>
		                    <div class="alert alert-info"><strong>Last recorded rebuild: </strong><?php if(!empty($data_blog) && $data_blog->vmid == $vminfo['members'][0]['vmid']) echo escape($data_blog->date); else echo 'none'; ?><br /><br /></div>
		                </div>
		            </div>
                </div>
            </div>
            <div class="modal fade" id="rebuild_modal" tabindex="-1" role="dialog" aria-labelledby="rebuild_modalLabel" aria-hidden="true">
            	<div class="modal-dialog modal-lg">
            		<div class="modal-content">
            			<div class="modal-header">
            				<h4 class="modal-title" id="rebuild_modalLabel">Rebuild in progress...</h4>
            			</div>
            			<div class="modal-body">
            				<div class="progress">
            					<div class="progress-bar progress-bar-info progress-bar-striped active rebuild_progress" role="progressbar" style="width:0%"></div>
            					<br />
            					<br />
            					<div id="rebuild_output"></div>
            				</div>
            			</div>
            		</div>
            	</div>
            </div>
       </div>
       <!-- TAB CONTENT END -->
       <!-- TAB CONTENT -->
       <div id="tab5" class="tab-single tab-cnt">
            <div class="datacenters">
                <div class="col-md-12">
                    <div class="row">
		                <div class="col-md-12">
                      <?php
                      $enable_backups = escape($db->get('vncp_settings', array('item', '=', 'enable_backups'))->first()->value);
                      if($enable_backups == 'true') {
                        $maxbackups = (int)escape($db->get('vncp_settings', array('item', '=', 'backup_limit'))->first()->value);
                        $override = (int)escape($db->get('vncp_ct_backups', array('hb_account_id', '=', $data->hb_account_id))->first()->backuplimit);
                        if($override >= 0) {
                          $maxbackups = $override;
                        }
                      if($data->allow_backups == 1) {
                      ?>
		                    <div id="backup_info"></div>
		                    <?php
		                    $backups = $pxAPI->get('/nodes/' . $data->node . '/storage/'.$node_data->backup_store.'/content');
                        $scheduled = $pxAPI->get('/cluster/backup');
		                    $backupcount = 0;
		                    for($i = 0; $i < count($backups); $i++) {
                            $volid = explode('-', $backups[$i]['volid']);
		                        if($volid[1] == 'lxc' && $volid[2] == (string)$vminfo['members'][0]['vmid'] && $backups[$i]['content'] == 'backup') {
		                            $backupcount++;
		                        }
		                    }
                        $schedule = null;
                        for($i = 0; $i < count($scheduled); $i++) {
                          if($scheduled[$i]['vmid'] == $vminfo['members'][0]['vmid']) {
                            $schedule = $scheduled[$i];
                            break;
                          }
                        }
		                    ?>
		                    <h4>Backup Usage: <span id="currentbackupcount"><?php echo $backupcount . ' used'; ?></span> of <span id="maxbackupcount"><?php echo $maxbackups . ' available'; ?></span></h4>
                        <br />
		                    <div class="table-responsive">
		                        <table class="table table-hover" id="backuplist">
                                <tbody>
		                            <tr>
		                                <th>Name</th>
		                                <th>Size</th>
                                    <th></th>
		                                <th></th>
		                                <th></th>
		                            </tr>
		                            <?php
		                            for($i = 0; $i < count($backups); $i++) {
                                  $volid = explode('-', $backups[$i]['volid']);
      		                        if($volid[1] == 'lxc' && $volid[2] == (string)$vminfo['members'][0]['vmid'] && $backups[$i]['content'] == 'backup') {
		                                	$bname = explode("/", $backups[$i]['volid']);
		                                    echo '<tr>';
		                                        echo '<td>' . $bname[1] . '</td>';
		                                        echo '<td>' . read_bytes_size($backups[$i]['size']) . '</td>';
                                            echo '<td><button id="get_backup_config_' . ($i+1) . '" class="btn btn-sm btn-default" content="'.$backups[$i]['volid'].'" data-toggle="modal" data-target="#config_modal">View Config</button></td>';
		                                        echo '<td><button id="restore_backup_' . ($i+1) . '" class="btn btn-sm btn-info" content="'.$backups[$i]['volid'].'">Restore</button></td>';
		                                        echo '<td><button id="remove_backup_' . ($i+1) . '" class="btn btn-sm btn-danger" content="'.$backups[$i]['volid'].'">Remove</button></td>';
		                                    echo '</tr>';
		                                }
		                            }
		                            ?>
                              </tbody>
		                        </table>
		                    </div>
                        <span id="countsection">
		                    <?php
		                    if($backupcount < $maxbackups && $data->suspended == 0) {
		                        echo '<button type="button" class="btn btn-md btn-success btn-block" data-toggle="modal" data-target="#backup_modal">Create backup</button><br /><br />';
		                    }else{
                            echo '<button type="button" class="btn btn-md btn-warning btn-block" disabled="disabled">Create backup</button>&nbsp;&nbsp;&nbsp;&nbsp;<small><em>Backup limit reached. Remove some old backups to create more.</em></small><br /><br />';
                        }
		                    ?>
                        </span>
                        <br />
                        <?php
                        $schtime = '';
                        if($schedule != null) {
                          $schtime = $schedule['dow'] . ' @ ' . $schedule['starttime'];
                        }
                        ?>
                        <h4>Scheduled Backup: <?php if($schtime != '') echo $schtime; else echo 'None'; ?></h4>
                        <br />
                        <?php if($schedule == null) { ?>
                        <form class="form-inline" id="scheduled_form">
                          <div class="form-group">
                            <label>Day(s) of week: </label>
                            <select multiple class="form-control" id="scheduled_dow">
                              <option value="mon">Monday</option>
                              <option value="tue">Tuesday</option>
                              <option value="wed">Wednesday</option>
                              <option value="thu">Thursday</option>
                              <option value="fri">Friday</option>
                              <option value="sat">Saturday</option>
                              <option value="sun">Sunday</option>
                            </select>
                          </div>
                          <div class="form-group">
                            <label>Time: </label>
                            <input type="time" class="form-control" id="scheduled_time" />
                          </div>
                          <button type="submit" class="btn btn-md btn-success" id="scheduled_submit">Create scheduled backup</button><br /><br />
                        </form>
                      <?php }else{ ?>
                        <form class="form-inline" id="schdelete_form">
                          <input type="hidden" value="<?php echo $schedule['id']; ?>" id="schid" />
                          <button type="submit" class="btn btn-md btn-danger" id="schdelete_submit">Delete scheduled backup</button><br /><br />
                        </form>
                      <?php } ?>
                        <div class="modal fade" id="backup_modal" tabindex="-1" role="dialog" aria-labelledby="backup_modalLabel" aria-hidden="true">
                        	<div class="modal-dialog">
                        		<div class="modal-content">
                        			<div class="modal-header">
                        				<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
                        				<h4 class="modal-title" id="backup_modalLabel">Backup Confirmation</h4>
                        			</div>
                        			<div class="modal-body">
                        				<p>Taking a backup on your LXC VPS will put your VPS in a suspended state and compress all VPS data into a backup file. This is a fast process that will cause minimal downtime, but <strong>your VPS will not be accessible while suspended.</strong> Once the backup finishes, your VPS will be available again.</p>
                        				<br />
                        				<form role="form">
                        				    <div class="form-group">
                        				        <label>Email Notification</label>
                        				        <select class="form-control" id="notification">
                        				        	<option value="no">No</option>
                        				        	<option value="yes">Yes</option>
                        				        </select>
                        				        <p class="help-block">Do you want to receive an email notification when the backup job finishes? The email will be sent to the email registered with your <a href="profile"><?php echo $appname; ?> account</a>.</p>
                        				    </div>
                        				    <input type="hidden" id="backup_aid" value="<?php echo escape($data->hb_account_id); ?>" />
                        				</form>
                        				<div id="backup_message" style="color: green;"></div>
                        			</div>
                        			<div class="modal-footer">
                        				<button type="button" class="btn btn-default" data-dismiss="modal" id="cancel_backup">Cancel</button>
                				        <button type="button" class="btn btn-primary" id="create_backup">Confirm Backup</button>
                        			</div>
                        		</div>
                        	</div>
                        </div>
                        <div class="modal fade" id="config_modal" tabindex="-1" role="dialog" aria-labelledby="config_modalLabel" aria-hidden="true">
                        	<div class="modal-dialog">
                        		<div class="modal-content">
                        			<div class="modal-header">
                        				<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
                        				<h4 class="modal-title" id="config_modalLabel">Stored Configuration</h4>
                        			</div>
                        			<div class="modal-body">
                                <h5 id="confheader"></h5>
                        				<div class="well well-sm" id="confwell">
                                  <i class="fa fa-cog fa-spin"></i> Pulling configuration, please wait...
                                </div>
                        			</div>
                        			<div class="modal-footer">
                        				<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                              </div>
                        		</div>
                        	</div>
                        </div>
                        <div class="modal fade" id="restore_modal" tabindex="-1" role="dialog" aria-labelledby="restore_modalLabel" aria-hidden="true">
                        	<div class="modal-dialog modal-lg">
                        		<div class="modal-content">
                        			<div class="modal-header">
                        				<h4 class="modal-title" id="restore_modalLabel">Restore in progress...</h4>
                        			</div>
                        			<div class="modal-body">
                        				<div class="progress">
                        					<div class="progress-bar progress-bar-info progress-bar-striped active restore_progress" role="progressbar" style="width:0%"></div>
                        					<br />
                        					<br />
                        					<div id="restore_output"></div>
                        				</div>
                        			</div>
                        		</div>
                        	</div>
                        </div>
                        <?php
                      }else{
                        echo 'Backups are not enabled on this VPS.';
                      }}else { echo 'Backups are not enabled here.'; }
                        ?>
		                </div>
		            </div>
                </div>
            </div>
       </div>
       <!-- TAB CONTENT END -->
      <!-- TAB CONTENT -->
      <div id="tab6" class="tab-single tab-cnt">
            <div class="datacenters">
            	<div class="row">
	               <div class="col-md-12">
	                   <h4>Click the button below to open a new console session for this VM. This console should only be used if you are unable to access your VM from the Internet. This console is not designed to be used for regular usage.</h4>
	                   <br />
		               	<?php
			            if($data->suspended == 0) {
			                echo '<button class="btn btn-info btn-lg" id="lxcconsole" role="'.escape($_GET['id']).'">Open VNC console</button>';
			            }else{
			                echo '<button class="btn btn-info btn-lg" disabled>Open VNC console</button>';
			            }
			            ?>
	               </div>
               </div>
           </div>
      </div>
      <!-- TAB CONTENT END -->
      </div>
     </div>
    </section>
    <!-- /Feature with tabs -->
</div>
<input type="hidden" value="<?php echo $data->hb_account_id; ?>" id="lxcinfo" />
