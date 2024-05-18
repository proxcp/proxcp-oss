<?php
if(!defined('constant-fw')) {
  header('Location: index');
}
?>
<div class="col-md-12">
      <h2 align="center"><?php echo escape($L['firewall']['vm_select']); ?></h2>
      <div class="table-responsive">
            <table class="table table-hover">
                  <tr>
                        <th><?php echo escape($L['firewall']['status']); ?></th>
                        <th><?php echo escape($L['firewall']['hostname']); ?></th>
                        <th><?php echo escape($L['firewall']['type']); ?></th>
                        <th><?php echo escape($L['firewall']['main_ip']); ?></th>
                        <th><?php echo escape($L['firewall']['os']); ?></th>
                        <th><?php echo escape($L['firewall']['ram']); ?></th>
                        <th><?php echo escape($L['firewall']['storage']); ?></th>
                        <th></th>
                  </tr>
                  <?php
            $noLogin = false;
                  $results = $db->get('vncp_lxc_ct', array('user_id', '=', $user->data()->id));
                  $data = $results->all();
            if(count($data) > 0) {
                $firstNode = $data[0]->node;
                $node_results = $db->get('vncp_nodes', array('name', '=', $firstNode));
                $node_data = $node_results->first();
                $pxAPI = new PVE2_API($node_data->hostname, $node_data->username, $node_data->realm, decryptValue($node_data->password));
                if(!$pxAPI->login()) $noLogin = true;
            }
                  for($i = 0; $i < count($data); $i++) {
                $noLogin = false;
                if($data[$i]->node != $firstNode) {
                    $firstNode = $data[$i]->node;
                    $node_results = $db->get('vncp_nodes', array('name', '=', $firstNode));
                    $node_data = $node_results->first();
                    $pxAPI = new PVE2_API($node_data->hostname, $node_data->username, $node_data->realm, decryptValue($node_data->password));
                    if(!$pxAPI->login()) $noLogin = true;
                }
                if($noLogin == true) {
                    echo '<tr>';
                        echo '<td>Uh oh! We can\'t reach your node.</td>';
                        echo '<td></td>';
                        echo '<td></td>';
                        echo '<td></td>';
                        echo '<td></td>';
                        echo '<td></td>';
                        echo '<td></td>';
                        echo '<td></td>';
                    echo '</tr>';
                }else if($data[$i]->suspended == 0 && $noLogin == false) {
                    $vminfo = $pxAPI->get('/pools/'.$data[$i]->pool_id);
                    $info = $pxAPI->get('/nodes/'.$data[$i]->node.'/lxc/'.$vminfo['members'][0]['vmid'].'/status/current');
                    echo '<tr>';
                        if($info['status'] == 'running') echo '<td><img src="img/online.png" /></td>';
                        else echo '<td><img src="img/offline.png" /></td>';
                        echo '<td>'.$info['name'].'</td>';
                        echo '<td><img src="img/lxc.png" style="padding-right:5px;" />LXC</td>';
                                    echo '<td>'. escape($data[$i]->ip) . '</td>';
                                    echo '<td>'. escape($data[$i]->os) .'</td>';
                                    echo '<td>'. read_bytes_size($info['maxmem'], 0) . '</td>';
                                    echo '<td>'. read_bytes_size($info['maxdisk'], 0) . '</td>';
                                    echo '<td><a href="firewall?id='.escape($data[$i]->hb_account_id).'&virt=lxc" class="btn btn-sm btn-info">Manage</a></td>';
                              echo '</tr>';
                }else if($data[$i]->suspended == 1){
                    $vminfo = $pxAPI->get('/pools/'.$data[$i]->pool_id);
                    $info = $pxAPI->get('/nodes/'.$data[$i]->node.'/lxc/'.$vminfo['members'][0]['vmid'].'/status/current');
                    echo '<tr>';
                        if($info['status'] == 'running') echo '<td><img src="img/online.png" /></td>';
                        else echo '<td><img src="img/offline.png" /></td>';
                        echo '<td>'. $info['name'] . '</td>';
                        echo '<td><img src="img/lxc.png" style="padding-right:5px;" />LXC</td>';
                        echo '<td>'. escape($data[$i]->ip) . '</td>';
                        echo '<td>'. escape($data[$i]->os) .'</td>';
                        echo '<td>'. read_bytes_size($info['maxmem'], 0) . '</td>';
                        echo '<td>'. read_bytes_size($info['maxdisk'], 0) . '</td>';
                        echo '<td><div class="tooltip-wrapper disabled" data-title="Suspended" data-placement="right"><button class="btn btn-sm btn-info" disabled>Manage</button></td>';
                    echo '</tr>';
                }
                  }
                  ?>
                  <?php
                  $results = $db->get('vncp_kvm_ct', array('user_id', '=', $user->data()->id));
                  $data = $results->all();
            if(count($data) > 0) {
                $firstNode = $data[0]->node;
                $node_results = $db->get('vncp_nodes', array('name', '=', $firstNode));
                $node_data = $node_results->first();
                $pxAPI = new PVE2_API($node_data->hostname, $node_data->username, $node_data->realm, decryptValue($node_data->password));
                if(!$pxAPI->login()) $noLogin = true;
            }
                  for($i = 0; $i < count($data); $i++) {
                if($data[$i]->node != $firstNode) {
                    $firstNode = $data[$i]->node;
                    $node_results = $db->get('vncp_nodes', array('name', '=', $firstNode));
                    $node_data = $node_results->first();
                    $pxAPI = new PVE2_API($node_data->hostname, $node_data->username, $node_data->realm, decryptValue($node_data->password));
                    if(!$pxAPI->login()) $noLogin = true;
                }
                if($noLogin == true) {
                    echo '<tr>';
                        echo '<td>Uh oh! We can\'t reach your node.</td>';
                        echo '<td></td>';
                        echo '<td></td>';
                        echo '<td></td>';
                        echo '<td></td>';
                        echo '<td></td>';
                        echo '<td></td>';
                        echo '<td></td>';
                    echo '</tr>';
                }else if($data[$i]->suspended == 0 && $noLogin == false) {
                              $vminfo = $pxAPI->get('/pools/'.$data[$i]->pool_id);
                              if(count($vminfo['members']) == 1) {
                                  $info = $pxAPI->get('/nodes/'.$data[$i]->node.'/qemu/'.$vminfo['members'][0]['vmid'].'/status/current');
                                  echo '<tr>';
                                        if($info['status'] == 'running') echo '<td><img src="img/online.png" /></td>';
                                        else echo '<td><img src="img/offline.png" /></td>';
                                        echo '<td>'. $info['name'] . '</td>';
                                        echo '<td><img src="img/kvm.png" style="padding-right:5px;" />KVM</td>';
                                        echo '<td>'. escape($data[$i]->ip) . '</td>';
                                        echo '<td>' . escape($data[$i]->os) . '</td>';
                                        echo '<td>'. read_bytes_size($info['maxmem'], 0) . '</td>';
                                        echo '<td>'. read_bytes_size($info['maxdisk'], 0) . '</td>';
                                        if($data[$i]->from_template == 1) {
            															echo '<td>Go to Dashboard first to complete setup.</td>';
            														}else{
            															echo '<td><a href="firewall?id='.escape($data[$i]->hb_account_id).'&virt=kvm" class="btn btn-sm btn-info">Manage</a></td>';
            														}
                                  echo '</tr>';
                              }else{
                                  for($j = 0; $j < count($vminfo['members']); $j++) {
                                      if($vminfo['members'][$j]['name'] == $data[$i]->cloud_hostname) {
                                          $info = $pxAPI->get('/nodes/'.$data[$i]->node.'/qemu/'.$vminfo['members'][$j]['vmid'].'/status/current');
                                  echo '<tr>';
                                        if($info['status'] == 'running') echo '<td><img src="img/online.png" /></td>';
                                        else echo '<td><img src="img/offline.png" /></td>';
                                        echo '<td>'. $info['name'] . '</td>';
                                        echo '<td><img src="img/kvm.png" style="padding-right:5px;" />KVM</td>';
                                        echo '<td>'. escape($data[$i]->ip) . '</td>';
                                        echo '<td>' . escape($data[$i]->os) . '</td>';
                                        echo '<td>'. read_bytes_size($info['maxmem'], 0) . '</td>';
                                        echo '<td>'. read_bytes_size($info['maxdisk'], 0) . '</td>';
                                        if($data[$i]->from_template == 1) {
            															echo '<td>Go to Dashboard first to complete setup.</td>';
            														}else{
            															echo '<td><a href="firewall?id='.escape($data[$i]->hb_account_id).'&virt=kvm" class="btn btn-sm btn-info">Manage</a></td>';
            														}
                                  echo '</tr>';
                                      }
                                  }
                              }
                }else if($data[$i]->suspended == 1){
                    $vminfo = $pxAPI->get('/pools/'.$data[$i]->pool_id);
                    $info = $pxAPI->get('/nodes/'.$data[$i]->node.'/qemu/'.$vminfo['members'][0]['vmid'].'/status/current');
                    echo '<tr>';
                        if($info['status'] == 'running') echo '<td><img src="img/online.png" /></td>';
                        else echo '<td><img src="img/offline.png" /></td>';
                        echo '<td>'. $info['name'] . '</td>';
                        echo '<td><img src="img/kvm.png" style="padding-right:5px;" />KVM</td>';
                        echo '<td>'. escape($data[$i]->ip) . '</td>';
                        echo '<td>' . escape($data[$i]->os) . '</td>';
                        echo '<td>'. read_bytes_size($info['maxmem'], 0) . '</td>';
                        echo '<td>'. read_bytes_size($info['maxdisk'], 0) . '</td>';
                        echo '<td><div class="tooltip-wrapper disabled" data-title="Suspended" data-placement="right"><button class="btn btn-sm btn-info" disabled>Manage</button></td>';
                    echo '</tr>';
                }
                  }
                  ?>
            </table>
      </div>
</div>
