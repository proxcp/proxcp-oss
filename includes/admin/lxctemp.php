<?php
if(count(get_included_files()) == 1) die("You just broke everything.");
?>
<?php require_once('menu_main.php'); ?>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-body">
              <div class="clearfix"><a class="btn btn-info btn-sm pull-right" href="https://google.com" role="button" target="_blank"><i class="fa fa-book"></i> Related Documentation</a></div>
                <h2 align="center">Manage LXC Templates</h2><br />
                <?php
                if($lxcTempSuccess) {
                    echo '<div id="adm_message"><div class="alert alert-success" role="alert"><strong>Success:</strong> template added to database! Make sure the template is in proxmox too.</div></div>';
                }else if($lxcImportSuccess && $lxcImportCount > 0) {
                  echo '<div id="adm_message"><div class="alert alert-success" role="alert"><strong>Success:</strong> imported ' . ((string)$lxcImportCount) . ' LXC templates!</div></div>';
                }else{
                    echo '<div id="adm_message"></div>';
                }
                ?>
                <?php
                $node_results = $db->get('vncp_nodes', array('id', '!=', 0));
                if(count($node_results->all()) > 0) {
                  $node_results = $node_results->first();
                  $pxAPI = new PVE2_API($node_results->hostname, $node_results->username, $node_results->realm, decryptValue($node_results->password));
                  $noLogin = false;
                  if(!$pxAPI->login()) $noLogin = true;
                  if($noLogin == false) {
                    $currentIsosDB = $db->get('vncp_lxc_templates', array('id', '!=', 0))->all();
                    $currentIsos = [];
                    for($i = 0; $i < count($currentIsosDB); $i++) {
                      array_push($currentIsos, $currentIsosDB[$i]->volid);
                    }
                    $activeStorages = $pxAPI->get('/nodes/' . $node_results->name . '/storage', array('enabled', 1));
                    $isoStorages = array();
                    for($i = 0; $i < count($activeStorages); $i++) {
                      if(strpos($activeStorages[$i]['content'], "vztmpl") !== false) {
                        $isoStorages[] = $activeStorages[$i];
                      }
                    }
                    $missingContent = array();
                    $isos = array();
                    for($i = 0; $i < count($isoStorages); $i++) {
                      $allIsos = $pxAPI->get('/nodes/' . $node_results->name . '/storage/' . $isoStorages[$i]['storage'] . '/content');
                      for($j = 0; $j < count($allIsos); $j++) {
                        if($allIsos[$j]['content'] == "vztmpl") {
                          $isos[] = $allIsos[$j];
                        }
                      }
                    }
                    for($i = 0; $i < count($isos); $i++) {
                      if(!in_array($isos[$i]['volid'], $currentIsos)) {
                        $missingContent[] = $isos[$i]['volid'];
                      }
                    }
                    if(count($missingContent) > 0) {
                      ?>
                      <div class="alert alert-info" role="alert">Found new template files. Would you like to import them?</div>
                      <form role="form" action="" method="POST">
                        <?php
                        for($i = 0; $i < count($missingContent); $i++) {
                          echo '<div class="input-group">
                            <span class="input-group-addon">Friendly Name</span>
                            <input type="text" class="form-control" name="field['.((string)$i).'][fname]" value="' . explode('/', $missingContent[$i])[1] . '" />
                            <span class="input-group-addon">Volume ID</span>
                            <input type="text" class="form-control" name="field['.((string)$i).'][volid]" value="' . $missingContent[$i] . '" readonly="readonly" />
                          </div><br />';
                        }
                        ?>
                        <input type="hidden" name="form_name" value="import_lxc_template" />
                        <input type="submit" value="Import" class="btn btn-success btn-lg btn-block" />
                      </form><br /><br />
                      <?php
                    }
                  }else{
                    echo '<div class="alert alert-danger" role="alert"><strong>Error:</strong> Could not connect to Proxmox node to check for new templates.</div>';
                  }
                }
                ?>
                <div class="table-responsive">
                    <table class="table table-hover" id="admin_lxctemptable">
                        <thead>
                            <tr>
                                <th>Friendly Name</th>
                                <th>Volume ID</th>
                                <th>Delete</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $admin_datalxctemp = $db->get('vncp_lxc_templates', array('id', '!=', 0));
                            $admin_datalxctemp = $admin_datalxctemp->all();
                            for($k = 0; $k < count($admin_datalxctemp); $k++) {
                                echo '<tr>';
                                    echo '<td>'.$admin_datalxctemp[$k]->friendly_name.'</td>';
                                    echo '<td>'.$admin_datalxctemp[$k]->volid.'</td>';
                                    echo '<td><button id="admin_lxctempdelete'.$admin_datalxctemp[$k]->id.'" class="btn btn-sm btn-danger" role="'.$admin_datalxctemp[$k]->id.'">Delete</button></td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <h2 align="center">Manually Add New LXC Template</h2>
                <form role="form" action="" method="POST">
                    <div class="form-group">
                        <label>Friendly Name</label>
                        <input class="form-control" type="text" name="fname" placeholder="CentOS 7" />
                    </div>
                    <div class="form-group">
                        <label>Volume ID</label>
                        <input class="form-control" type="text" name="volid" placeholder="store:vztmpl/file.tar.gz" />
                    </div>
                    <input type="hidden" name="token" value="<?php echo Token::generate(); ?>" />
                    <input type="hidden" name="form_name" value="new_lxc_template" />
                    <input type="submit" value="Submit" class="btn btn-success" />
                </form>
            </div>
        </div>
    </div>
</div>
