<?php
if(!defined('constant-fw')) {
    header('Location: index');
}
$results = $db->get('vncp_kvm_ct', array('hb_account_id', '=', $_GET['id']));
$data = $results->first();
$noLogin = false;
if($data->from_template == 1) {
  die('Go to Dashboard first to complete setup.');
}
if($data->suspended == 0 && $data->user_id == $user->data()->id) {
    $nodename = $data->node;
    $node_results = $db->get('vncp_nodes', array('name', '=', $nodename));
    $node_data = $node_results->first();
    $pxAPI = new PVE2_API($node_data->hostname, $node_data->username, $node_data->realm, decryptValue($node_data->password));
    if(!$pxAPI->login()) $noLogin = true;
    if($noLogin == false) {
        $vminfo = $pxAPI->get('/pools/'.$data->pool_id);
    }
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
                <span>Options</span>
            </a></li>
        <!-- TAB END -->
        <!-- TAB -->
        <li class="btnLink smallBtnLink green_tab">
            <a class="tab-action" data-tab-cnt="tab2">
                <span>Rules</span>
            </a></li>
        <!-- TAB END -->
        <!-- TAB -->
        <li class="btnLink smallBtnLink green_tab">
            <a class="tab-action" data-tab-cnt="tab4">
                <span>Log</span>
            </a></li>
        <!-- TAB END -->
       </div>
       <br>
       <div class="clr"></div>
       <!-- TAB CONTENT -->
       <div id="tab1" class="tab-single tab-cnt active">
            <div class="datacenters">
                <div class="col-md-12">
                    <?php
                    if(count($vminfo['members']) == 1) {
                        $options = $pxAPI->get('/nodes/'.$data->node.'/qemu/'.$vminfo['members'][0]['vmid'].'/firewall/options');
                    }else{
                        for($j = 0; $j < count($vminfo['members']); $j++) {
                            if($vminfo['members'][$j]['name'] == $data->cloud_hostname) {
                                $options = $pxAPI->get('/nodes/'.$data->node.'/qemu/'.$vminfo['members'][$j]['vmid'].'/firewall/options');
                            }
                        }
                    }
                    ?>
                    <button type="button" class="btn btn-success btn-md pull-left" data-toggle="modal" data-target="#fwoptions">Edit Options</button>
                    <?php
                    if($data->has_net1 == 1) {
                        if($data->fw_enabled_net1 == 0) {
                            echo '<button type="button" class="btn btn-info btn-md pull-right" id="fwifacepriv" role="enable">Enable Firewall Interface (private)</button>';
                        }else{
                            echo '<button type="button" class="btn btn-info btn-md pull-right" id="fwifacepriv" role="disable">Disable Firewall Interface (private)</button>';
                        }
                    }
                    if($data->fw_enabled_net0 == 0) {
                        echo '<button type="button" class="btn btn-info btn-md pull-right" id="fwifacepub" role="enable" style="margin-right:10px;">Enable Firewall Interface (public)</button>';
                    }else{
                        echo '<button type="button" class="btn btn-info btn-md pull-right" id="fwifacepub" role="disable" style="margin-right:10px;">Disable Firewall Interface (public)</button>';
                    }
                    ?>
                    <div class="clearfix"></div>
                    <br />
                    <br />
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <tr>
                                <th>Option</th>
                                <th>Value</th>
                                <th>Description</th>
                            </tr>
                            <tr>
                                <td>Enable firewall</td>
                                <td>
                                    <?php
                                    if($options['enable'] == 1) echo '<div id="enable">Yes</div>';
                                    else echo '<div id="enable">No</div>';
                                    ?>
                                </td>
                                <td>Enable or disable the firewall for this VM</td>
                            </tr>
                            <tr>
                                <td>Global inbound policy</td>
                                <td>
                                    <?php
                                    if(isset($options['policy_in'])) echo '<div id="policyin">'.$options['policy_in'].'</div>';
                                    else echo '<div id="policyin">DROP</div>';
                                    ?>
                                </td>
                                <td>Accept, drop, or reject inbound traffic by default</td>
                            </tr>
                            <tr>
                                <td>Global outbound policy</td>
                                <td>
                                    <?php
                                    if(isset($options['policy_out'])) echo '<div id="policyout">'.$options['policy_out'].'</div>';
                                    else echo '<div id="policyout">ACCEPT</div>';
                                    ?>
                                </td>
                                <td>Accept, drop, or reject outbound traffic by default</td>
                            </tr>
                            <tr>
                                <td>Inbound log level</td>
                                <td>
                                    <?php
                                    if(isset($options['log_level_in'])) echo '<div id="levelin">'.$options['log_level_in'].'</div>';
                                    else echo '<div id="levelin">nolog</div>';
                                    ?>
                                </td>
                                <td>Set the logging level of inbound traffic</td>
                            </tr>
                            <tr>
                                <td>Outbound log level</td>
                                <td>
                                    <?php
                                    if(isset($options['log_level_out'])) echo '<div id="levelout">'.$options['log_level_out'].'</div>';
                                    else echo '<div id="levelout">nolog</div>';
                                    ?>
                                </td>
                                <td>Set the logging level of outbound traffic</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
       </div>
       <!-- TAB CONTENT END -->
       <!-- TAB CONTENT -->
       <div id="tab2" class="tab-single tab-cnt">
             <div class="datacenters">
                <div class="col-md-12">
                    <?php
                    if(count($vminfo['members']) == 1) {
                        $rules = $pxAPI->get('/nodes/'.$data->node.'/qemu/'.$vminfo['members'][0]['vmid'].'/firewall/rules');
                    }else{
                        for($j = 0; $j < count($vminfo['members']); $j++) {
                            if($vminfo['members'][$j]['name'] == $data->cloud_hostname) {
                                $rules = $pxAPI->get('/nodes/'.$data->node.'/qemu/'.$vminfo['members'][$j]['vmid'].'/firewall/rules');
                            }
                        }
                    }
                    ?>
                    <button type="button" class="btn btn-success btn-md" data-toggle="modal" data-target="#addfwrule">Add Rule</button>
                    <br />
                    <br />
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <tr>
                                <th>ID</th>
                                <th>Enable</th>
                                <th>Interface</th>
                                <th>Direction</th>
                                <th>Action</th>
                                <th>Source IP</th>
                                <th>Source port</th>
                                <th>Destination IP</th>
                                <th>Destination port</th>
                                <th>Protocol</th>
                                <th>Comment</th>
                                <th>Remove</th>
                            </tr>
                                <?php
                                for($i = 0; $i < count($rules); $i++) {
                                  $p = $rules[$i]['pos'];
                                    echo '<tr>';
                                    echo '<td>';
                                        echo '<a data-toggle="modal" data-target="#fwr' . (string)$p . '" style="cursor:pointer;">' . (string)((int)$p + 1) . '</a>';
                                    echo '</td>';
                                    echo '<td>';
                                        if($rules[$i]['enable'] == 0)
                                            echo '<div id="renable'.$i.'" role="'.$i.'">false</div>';
                                        else
                                            echo '<div id="renable'.$i.'" role="'.$i.'">true</div>';
                                    echo '</td>';
                                    echo '<td>';
                                        if($rules[$i]['iface'] == 'net0')
                                            echo '<div id="type'.$i.'" role="'.$i.'">net0 (public)</div>';
                                        else
                                            echo '<div id="type'.$i.'" role="'.$i.'">net1 (private)</div>';
                                    echo '</td>';
                                    echo '<td>';
                                        echo '<div id="type'.$i.'" role="'.$i.'">'.$rules[$i]['type'].'</div>';
                                    echo '</td>';
                                    echo '<td>';
                                        echo '<div id="action'.$i.'" role="'.$i.'">'.$rules[$i]['action'].'</div>';
                                    echo '</td>';
                                    echo '<td>';
                                        if(isset($rules[$i]['source']))
                                            echo $rules[$i]['source'];
                                        else
                                            echo '0.0.0.0';
                                    echo '</td>';
                                    echo '<td>';
                                        if(isset($rules[$i]['sport']))
                                            echo '<div id="sport'.$i.'" role="'.$i.'">'.$rules[$i]['sport'].'</div>';
                                        else
                                            echo '<div id="sport'.$i.'" role="'.$i.'"><em>null</em></div>';
                                    echo '</td>';
                                    echo '<td>';
                                        if(isset($rules[$i]['dest']))
                                            echo $rules[$i]['dest'];
                                        else
                                            echo '0.0.0.0';
                                    echo '</td>';
                                    echo '<td>';
                                        if(isset($rules[$i]['dport']))
                                            echo '<div id="dport'.$i.'" role="'.$i.'">'.$rules[$i]['dport'].'</div>';
                                        else
                                            echo '<div id="dport'.$i.'" role="'.$i.'"><em>null</em></div>';
                                    echo '</td>';
                                    echo '<td>';
                                        echo '<div id="proto'.$i.'" role="'.$i.'">'.$rules[$i]['proto'].'</div>';
                                    echo '</td>';
                                    echo '<td>';
                                        if(isset($rules[$i]['comment']))
                                            echo '<div id="comment'.$i.'" role="'.$i.'">'.$rules[$i]['comment'].'</div>';
                                        else
                                            echo '<div id="comment'.$i.'" role="'.$i.'">none</div>';
                                    echo '</td>';
                                    echo '<td>';
                                        echo '<button id="fwremove'.$i.'" class="btn btn-sm btn-danger" role="'.$i.'">Delete</button>';
                                    echo '</td>';
                                    echo '</tr>';
                                    echo '<div class="modal fade" id="fwr'.(string)$p.'" tabindex="-1" role="dialog"><div class="modal-dialog" role="document"><div class="modal-content"><div class="modal-header"><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button><h4 class="modal-title">Edit Firewall Rule</h4></div><div class="modal-body">
<form role="form">
    <div class="form-group">
        <label for="a' . (string)$p . '">Enable?</label>
        <select id="a' . (string)$p . '" class="form-control">';
        if($rules[$i]['enable'] == 0)
            echo '<option value="0">false</option><option value="1">true</option>';
        else
            echo '<option value="1">true</option><option value="0">false</option>';
        echo '</select>
    </div>
    <div class="form-group">
        <label for="iface' . (string)$p . '">Network Interface</label>
        <select id="iface' . (string)$p . '" class="form-control">';
        if($rules[$i]['iface'] == 'net0')
            echo '<option value="net0">net0 (public)</option><option value="net1">net1 (private)</option>';
        else
            echo '<option value="net1">net1 (private)</option><option value="net0">net0 (public)</option>';
        echo '</select>
    </div>
    <div class="form-group">
        <label for="b' . (string)$p . '">Direction</label>
        <select id="b' . (string)$p . '" class="form-control">';
        if($rules[$i]['type'] == 'in')
            echo '<option value="in">in</option><option value="out">out</option>';
        else
            echo '<option value="out">out</option><option value="in">in</option>';
        echo '</select>
    </div>
    <div class="form-group">
        <label for="c' . (string)$p . '">Action</label>
        <select id="c' . (string)$p . '" class="form-control">';
        if($rules[$i]['action'] == 'DROP')
            echo '<option value="DROP">DROP</option><option value="REJECT">REJECT</option><option value="ACCEPT">ACCEPT</option>';
        else if($rules[$i]['action'] == 'REJECT')
            echo '<option value="REJECT">REJECT</option><option value="DROP">DROP</option><option value="ACCEPT">ACCEPT</option>';
        else
            echo '<option value="ACCEPT">ACCEPT</option><option value="DROP">DROP</option><option value="REJECT">REJECT</option>';
        echo '</select>
    </div>
    <div class="form-group">
        <label for="d' . (string)$p . '">Source IP</label>
        <input id="d' . (string)$p . '" class="form-control" type="text" value="' . $rules[$i]['source'] . '" />
        <p class="help-block">Required. Must be IP address. Any IP = 0.0.0.0</p>
    </div>
    <div class="form-group">
        <label for="e' . (string)$p . '">Source port</label>
        <input id="e' . (string)$p . '" class="form-control" type="number" min="1" max="65535" value="' . ($rules[$i]['sport'] ?? '') . '" />
        <p class="help-block">Not required. Valid ports are between 1 - 65535.</p>
    </div>
    <div class="form-group">
        <label for="f' . (string)$p . '">Destination IP</label>
        <input id="f' . (string)$p . '" class="form-control" type="text" value="' . $rules[$i]['dest'] . '" />
        <p class="help-block">Required. Must be IP address or alias. Any IP = 0.0.0.0</p>
    </div>
    <div class="form-group">
        <label for="g' . (string)$p . '">Destination port</label>
        <input id="g' . (string)$p . '" class="form-control" type="number" min="1" max="65535" value="' . ($rules[$i]['dport'] ?? '') . '" />
        <p class="help-block">Not required. Valid ports are between 1 - 65535.</p>
    </div>
    <div class="form-group">
        <label for="h' . (string)$p . '">Protocol</label>
        <select id="h' . (string)$p . '" class="form-control">';
        if($rules[$i]['proto'] == 'tcp')
            echo '<option value="tcp">TCP</option><option value="udp">UDP</option><option value="icmp">ICMP</option><option value="ipv6">IPv6</option><option value="gre">GRE</option><option value="l2tp">L2TP</option>';
        else if($rules[$i]['proto'] == 'udp')
            echo '<option value="udp">UDP</option><option value="tcp">TCP</option><option value="icmp">ICMP</option><option value="ipv6">IPv6</option><option value="gre">GRE</option><option value="l2tp">L2TP</option>';
        else if($rules[$i]['proto'] == 'icmp')
            echo '<option value="icmp">ICMP</option><option value="tcp">TCP</option><option value="udp">UDP</option><option value="ipv6">IPv6</option><option value="gre">GRE</option><option value="l2tp">L2TP</option>';
        else if($rules[$i]['proto'] == 'ipv6')
            echo '<option value="ipv6">IPv6</option><option value="tcp">TCP</option><option value="udp">UDP</option><option value="icmp">ICMP</option><option value="gre">GRE</option><option value="l2tp">L2TP</option>';
        else if($rules[$i]['proto'] == 'gre')
            echo '<option value="gre">GRE</option><option value="tcp">TCP</option><option value="udp">UDP</option><option value="icmp">ICMP</option><option value="ipv6">IPv6</option><option value="l2tp">L2TP</option>';
        else
            echo '<option value="l2tp">L2TP</option><option value="tcp">TCP</option><option value="udp">UDP</option><option value="icmp">ICMP</option><option value="ipv6">IPv6</option><option value="gre">GRE</option>';
        echo '</select>
    </div>
    <div class="form-group">
        <label for="i' . (string)$p . '">Comment</label>
        <input id="i' . (string)$p . '" type="text" class="form-control" value="' . $rules[$i]['comment'] . '" />
        <p class="help-block">Not required.</p>
    </div>
    <input type="submit" value="Save" class="btn btn-md btn-success" id="fwredit' . (string)$p . '" role="' . (string)$p . '" />
</form>
                                    </div></div></div></div>';
                                }
                                ?>
                        </table>
                    </div>
                </div>
            </div>
       </div>
       <!-- TAB CONTENT END -->
       <!-- TAB CONTENT -->
       <div id="tab4" class="tab-single tab-cnt">
             <div class="datacenters">
                <div class="col-md-12">
                    <?php
                    if(count($vminfo['members']) == 1) {
                        $log = $pxAPI->get('/nodes/'.$data->node.'/qemu/'.$vminfo['members'][0]['vmid'].'/firewall/log');
                    }else{
                        for($j = 0; $j < count($vminfo['members']); $j++) {
                            if($vminfo['members'][$j]['name'] == $data->cloud_hostname) {
                                $log = $pxAPI->get('/nodes/'.$data->node.'/qemu/'.$vminfo['members'][$j]['vmid'].'/firewall/log');
                            }
                        }
                    }
                    ?>
                    <textarea class="form-control" rows="15" disabled>
<?php
for($i = 0; $i < count($log); $i++) {
    if($log[$i]['t'] == 'no content') {
        echo 'No log';
    }else{
        echo $log[$i]['t'];
    }
}
?>
                    </textarea><br /><br />
                </div>
            </div>
       </div>
       <!-- TAB CONTENT END -->
      </div>
     </div>
    </section>
    <!-- /Feature with tabs -->
    <div class="modal fade" id="fwoptions" tabindex="-1" role="dialog" aria-labelledby="fwoptionslabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="fwoptionslabel">Edit Firewall Options</h4>
                </div>
                <div class="modal-body">
                    <form role="form">
                        <div class="form-group">
                            <label>Enable firewall</label>
                            <select class="form-control" id="enableopts">
                                <?php
                                if($options['enable'] == 1) echo '<option value="1">Yes</option><option value="0">No</option>';
                                else echo '<option value="0">No</option><option value="1">Yes</option>';
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Global inbound policy</label>
                            <select class="form-control" id="policyinopts">
                                <?php
                                if(isset($options['policy_in'])) {
                                    if($options['policy_in'] == 'ACCEPT') {
                                        echo '<option value="ACCEPT">ACCEPT</option>';
                                        echo '<option value="DROP">DROP</option>';
                                        echo '<option value="REJECT">REJECT</option>';
                                    }else if($options['policy_in'] == 'DROP') {
                                        echo '<option value="DROP">DROP</option>';
                                        echo '<option value="ACCEPT">ACCEPT</option>';
                                        echo '<option value="REJECT">REJECT</option>';
                                    }else{
                                        echo '<option value="REJECT">REJECT</option>';
                                        echo '<option value="ACCEPT">ACCEPT</option>';
                                        echo '<option value="DROP">DROP</option>';
                                    }
                                }else{
                                    echo '<option value="DROP">DROP</option>';
                                    echo '<option value="ACCEPT">ACCEPT</option>';
                                    echo '<option value="REJECT">REJECT</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Global outbound policy</label>
                            <select class="form-control" id="policyoutopts">
                                <?php
                                if(isset($options['policy_out'])) {
                                    if($options['policy_out'] == 'ACCEPT') {
                                        echo '<option value="ACCEPT">ACCEPT</option>';
                                        echo '<option value="DROP">DROP</option>';
                                        echo '<option value="REJECT">REJECT</option>';
                                    }else if($options['policy_out'] == 'DROP') {
                                        echo '<option value="DROP">DROP</option>';
                                        echo '<option value="ACCEPT">ACCEPT</option>';
                                        echo '<option value="REJECT">REJECT</option>';
                                    }else{
                                        echo '<option value="REJECT">REJECT</option>';
                                        echo '<option value="ACCEPT">ACCEPT</option>';
                                        echo '<option value="DROP">DROP</option>';
                                    }
                                }else{
                                    echo '<option value="ACCEPT">ACCEPT</option>';
                                    echo '<option value="DROP">DROP</option>';
                                    echo '<option value="REJECT">REJECT</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Inbound log level</label>
                            <select class="form-control" id="levelinopts">
                                <?php
                                if(isset($options['log_level_in'])) {
                                    if($options['log_level_in'] == 'nolog') {
                                        echo '<option value="nolog">nolog</option>';
                                        echo '<option value="warning">warning</option>';
                                        echo '<option value="alert">alert</option>';
                                    }else if($options['log_level_in'] == 'warning') {
                                        echo '<option value="warning">warning</option>';
                                        echo '<option value="nolog">nolog</option>';
                                        echo '<option value="alert">alert</option>';
                                    }else{
                                        echo '<option value="alert">alert</option>';
                                        echo '<option value="nolog">nolog</option>';
                                        echo '<option value="warning">warning</option>';
                                    }
                                }else{
                                    echo '<option value="nolog">nolog</option>';
                                    echo '<option value="warning">warning</option>';
                                    echo '<option value="alert">alert</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Outbound log level</label>
                            <select class="form-control" id="leveloutopts">
                                <?php
                                if(isset($options['log_level_out'])) {
                                    if($options['log_level_out'] == 'nolog') {
                                        echo '<option value="nolog">nolog</option>';
                                        echo '<option value="warning">warning</option>';
                                        echo '<option value="alert">alert</option>';
                                    }else if($options['log_level_out'] == 'warning') {
                                        echo '<option value="warning">warning</option>';
                                        echo '<option value="nolog">nolog</option>';
                                        echo '<option value="alert">alert</option>';
                                    }else{
                                        echo '<option value="alert">alert</option>';
                                        echo '<option value="nolog">nolog</option>';
                                        echo '<option value="warning">warning</option>';
                                    }
                                }else{
                                    echo '<option value="nolog">nolog</option>';
                                    echo '<option value="warning">warning</option>';
                                    echo '<option value="alert">alert</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <input type="submit" value="Save" class="btn btn-md btn-success" id="fwoptionssave" />
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="addfwrule" tabindex="-1" role="dialog" aria-labelledby="addfwrulelabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="addfwrulelabel">Add New Firewall Rule</h4>
            </div>
            <div class="modal-body">
                <form role="form">
                    <div class="form-group">
                        <label for="a">Enable?</label>
                        <select id="a" class="form-control">
                            <option value="0">false</option>
                            <option value="1">true</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="iface">Network Interface</label>
                        <select id="iface" class="form-control">
                            <option value="net0">net0 (public)</option>
                            <option value="net1">net1 (private)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="b">Direction</label>
                        <select id="b" class="form-control">
                            <option value="in">in</option>
                            <option value="out">out</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="c">Action</label>
                        <select id="c" class="form-control">
                            <option value="DROP">DROP</option>
                            <option value="REJECT">REJECT</option>
                            <option value="ACCEPT">ACCEPT</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="d">Source IP</label>
                        <input id="d" class="form-control" type="text" />
                        <p class="help-block">Required. Must be IP address. Any IP = 0.0.0.0</p>
                    </div>
                    <div class="form-group">
                        <label for="e">Source port</label>
                        <input id="e" class="form-control" type="number" min="1" max="65535" />
                        <p class="help-block">Not required. Valid ports are between 1 - 65535.</p>
                    </div>
                    <div class="form-group">
                        <label for="f">Destination IP</label>
                        <input id="f" class="form-control" type="text" />
                        <p class="help-block">Required. Must be IP address or alias. Any IP = 0.0.0.0</p>
                    </div>
                    <div class="form-group">
                        <label for="g">Destination port</label>
                        <input id="g" class="form-control" type="number" min="1" max="65535" />
                        <p class="help-block">Not required. Valid ports are between 1 - 65535.</p>
                    </div>
                    <div class="form-group">
                        <label for="h">Protocol</label>
                        <select id="h" class="form-control">
                            <option value="tcp">TCP</option>
                            <option value="udp">UDP</option>
                            <option value="icmp">ICMP</option>
                            <option value="ipv6">IPv6</option>
                            <option value="gre">GRE</option>
                            <option value="l2tp">L2TP</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="i">Comment</label>
                        <input id="i" type="text" class="form-control" />
                        <p class="help-block">Not required.</p>
                    </div>
                    <input type="submit" value="Save" class="btn btn-md btn-success" id="fwrulessave" />
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>
</div>
<input type="hidden" value="<?php echo $data->hb_account_id; ?>" id="kvminfo" />
