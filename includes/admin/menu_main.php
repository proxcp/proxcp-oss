<?php
if(count(get_included_files()) == 1) die("You just broke everything.");
?>
<nav class="navbar navbar-default" id="admin_navigation">
    <div class="container">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-2">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            </button>
        </div>
            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-2">
                <ul class="nav navbar-nav nav-elem">
                    <li><a href="<?php echo Config::get('admin/base'); ?>">Admin Dashboard</a></li>
										<li class="dropdown">
												<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">Users <span class="caret"></span></a>
												<ul class="dropdown-menu subNav" role="menu">
															<li class="admin_dd_li"><a class="admin_dd" href="<?php echo Config::get('admin/base') . '?action=users'; ?>">Manage Users</a></li>
															<li class="admin_dd_li"><a class="admin_dd" href="<?php echo Config::get('admin/base') . '?action=acl'; ?>">Manage User ACL</a></li>
												</ul>
										</li>
										<li class="dropdown">
												<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">LXC <span class="caret"></span></a>
												<ul class="dropdown-menu subNav" role="menu">
															<li class="admin_dd_li"><a class="admin_dd" href="<?php echo Config::get('admin/base') . '?action=lxc'; ?>">Manage LXC</a></li>
															<li class="admin_dd_li"><a class="admin_dd" href="<?php echo Config::get('admin/base') . '?action=lxctemp'; ?>">Manage LXC Templates</a></li>
															<li class="admin_dd_li"><a class="admin_dd" href="<?php echo Config::get('admin/base') . '?action=tuntap'; ?>">Manage LXC TUN/TAP</a></li>
															<li class="admin_dd_li"><a class="admin_dd" href="<?php echo Config::get('admin/base') . '?action=lxckvmprops'; ?>">Manage LXC/KVM Properties</a></li>
												</ul>
										</li>
										<li class="dropdown">
												<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">KVM <span class="caret"></span></a>
												<ul class="dropdown-menu subNav" role="menu">
															<li class="admin_dd_li"><a class="admin_dd" href="<?php echo Config::get('admin/base') . '?action=kvm'; ?>">Manage KVM</a></li>
															<li class="admin_dd_li"><a class="admin_dd" href="<?php echo Config::get('admin/base') . '?action=kvmiso'; ?>">Manage KVM ISOs</a></li>
                              <li class="admin_dd_li"><a class="admin_dd" href="<?php echo Config::get('admin/base') . '?action=kvmiso_custom'; ?>">Manage Custom KVM ISOs</a></li>
															<li class="admin_dd_li"><a class="admin_dd" href="<?php echo Config::get('admin/base') . '?action=kvmtemp'; ?>">Manage KVM Templates</a></li>
															<li class="admin_dd_li"><a class="admin_dd" href="<?php echo Config::get('admin/base') . '?action=lxckvmprops'; ?>">Manage LXC/KVM Properties</a></li>
												</ul>
										</li>
										<li class="dropdown">
												<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">Cloud <span class="caret"></span></a>
												<ul class="dropdown-menu subNav" role="menu">
															<li class="admin_dd_li"><a class="admin_dd" href="<?php echo Config::get('admin/base') . '?action=cloud'; ?>">Manage Cloud</a></li>
												</ul>
										</li>
										<li class="dropdown">
												<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">DNS <span class="caret"></span></a>
												<ul class="dropdown-menu subNav" role="menu">
															<li class="admin_dd_li"><a class="admin_dd" href="<?php echo Config::get('admin/base') . '?action=fdns'; ?>">Manage Forward DNS</a></li>
															<li class="admin_dd_li"><a class="admin_dd" href="<?php echo Config::get('admin/base') . '?action=rdns'; ?>">Manage Reverse DNS</a></li>
												</ul>
										</li>
										<li class="dropdown">
												<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">Settings <span class="caret"></span></a>
												<ul class="dropdown-menu subNav" role="menu">
															<li class="admin_dd_li"><a class="admin_dd" href="<?php echo Config::get('admin/base') . '?action=settings'; ?>"><?php echo $appname; ?> Settings</a></li>
															<li class="admin_dd_li"><a class="admin_dd" href="<?php echo Config::get('admin/base') . '?action=nodes'; ?>">Manage Nodes</a></li>
                              <li class="admin_dd_li"><a class="admin_dd" href="<?php echo Config::get('admin/base') . '?action=tuntap'; ?>">Manage Node SSH</a></li>
                              <li class="admin_dd_li"><a class="admin_dd" href="<?php echo Config::get('admin/base') . '?action=natnodes'; ?>">Manage NAT Nodes</a></li>
															<li class="admin_dd_li"><a class="admin_dd" href="<?php echo Config::get('admin/base') . '?action=bandwidth'; ?>">Manage Bandwidth</a></li>
															<li class="admin_dd_li"><a class="admin_dd" href="<?php echo Config::get('admin/base') . '?action=dhcp'; ?>">Manage DHCP</a></li>
															<li class="admin_dd_li"><a class="admin_dd" href="<?php echo Config::get('admin/base') . '?action=ipv6'; ?>">Manage IPv6</a></li>
															<li class="admin_dd_li"><a class="admin_dd" href="<?php echo Config::get('admin/base') . '?action=private'; ?>">Manage Private IPs</a></li>
															<li class="admin_dd_li"><a class="admin_dd" href="<?php echo Config::get('admin/base') . '?action=ip2'; ?>">Manage Secondary IPs</a></li>
															<li class="admin_dd_li"><a class="admin_dd" href="<?php echo Config::get('admin/base') . '?action=log'; ?>">System Logs</a></li>
												</ul>
										</li>
										<li class="dropdown">
												<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">Integrations <span class="caret"></span></a>
												<ul class="dropdown-menu subNav" role="menu">
													<li class="admin_dd_li"><a class="admin_dd" href="<?php echo Config::get('admin/base') . '?action=ipv4'; ?>">Manage IPv4 Pool</a></li>
													<li class="admin_dd_li"><a class="admin_dd" href="<?php echo Config::get('admin/base') . '?action=api'; ?>">Manage API</a></li>
												</ul>
										</li>
                </ul>
            </div>
    </div>
</nav>
