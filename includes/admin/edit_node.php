<?php
if(count(get_included_files()) == 1) die("You just broke everything.");
?>
<?php require_once('menu_main.php'); ?>
<?php
$edit_node = $db->get('vncp_nodes', array('id', '=', escape($_GET['id'])))->first();
?>
<div class="row">
	<div class="col-md-12">
		<div class="panel panel-default">
			<div class="panel-body">
        <?php if(count($edit_node) == 1) { ?>
				<h2 align="center">Edit Node - <?php echo escape($edit_node->name); ?></h2><br />
				<div id="adm_message"></div>
				<form role="form" action="" method="POST">
					<div class="form-group">
					    <label>API Username</label>
					    <input class="form-control" type="text" name="username" value="<?php echo escape($edit_node->username); ?>" />
					</div>
					<div class="form-group">
					    <label>API Password</label>
					    <input class="form-control" type="password" name="password" placeholder="Type here to change the password, leave this blank otherwise." />
					</div>
					<div class="form-group">
					    <label>Realm</label>
					    <input class="form-control" type="text" name="realm" value="<?php echo escape($edit_node->realm); ?>" />
					</div>
					<div class="form-group">
					    <label>Port</label>
					    <input class="form-control" type="text" name="port" value="<?php echo escape($edit_node->port); ?>" />
					</div>
					<div class="form-group">
					    <label>Location</label>
					    <input class="form-control" type="text" name="location" value="<?php echo escape($edit_node->location); ?>" />
					</div>
					<div class="form-group">
					    <label>CPU</label>
					    <input class="form-control" type="text" name="cpu" value="<?php echo escape($edit_node->cpu); ?>" />
					</div>
					<div class="form-group">
					    <label>Backup Store</label>
					    <input class="form-control" type="text" name="backup" value="<?php echo escape($edit_node->backup_store); ?>" />
					</div>
					<input type="hidden" name="nid" value="<?php echo escape($_GET['id']); ?>" />
					<input type="hidden" name="token" value="<?php echo Token::generate(); ?>" />
					<input type="submit" value="Submit" class="btn btn-success" />
				</form>
      <?php }else{
        echo 'Node not found. <a href="' . Config::get('instance/base') . '/admin?action=nodes">Go back to Manage Nodes</a>';
      } ?>
			</div>
		</div>
	</div>
</div>
