#! /usr/bin/php
<?php
/**
 * Fusionforge Cron Job : ssh key administration
 *
 * The rest Copyright 2002-2005 (c) GForge Team
 * Copyright (C) 2009  Sylvain Beucler
 * Copyright 2012, Franck Villaume - TrivialDev
 * Copyright 2013, Xavier Le Boëc
 * http://fusionforge.org/
 *
 * This file is part of FusionForge.
 *
 * FusionForge is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * FusionForge is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

require dirname(__FILE__).'/../../env.inc.php';
require_once $gfcommon.'include/pre.php';
require $gfcommon.'include/cron_utils.php';

$err='';

$res2 = db_query_params('SELECT sshkey, user_name from sshkeys, users
						where sshkeys.userid = users.user_id
						and users.status = $1
						and users.unix_status = $2
						and sshkeys.deleted = $3
						and sshkeys.deploy = $4',
			array('A', 'A', 0, 0));
$keys = array();
while ($arr = db_fetch_array($res2)) {
	$username = $arr['user_name'];
	$key = $arr['sshkey'];
	if (!exists($keys[$username])) {
		$keys[$username] = array();
	}
	$keys[$username][] = $key;
}

function create_authkeys($params) {
	$ssh_dir = $params['ssh_dir'];
	$ssh_key = $params['ssh_key'];
	if (!is_dir($ssh_dir)) {
		mkdir ($ssh_dir, 0755);
	}
	$h8 = fopen("$ssh_dir/authorized_keys","w");
	fwrite($h8,'# This file is automatically generated from your account settings.'."\n");
	fwrite($h8,$ssh_key);
	fclose($h8);
	chmod ("$ssh_dir/authorized_keys", 0644);
}

foreach ($keys as $username => $v) {
	$ssh_key = join("\n", $v);

	$dir = forge_get_config('homedir_prefix').'/'.$username;
	if (util_is_root_dir($dir)) {
		$err .= _('Error: homedir_prefix/username points to root directory!');
		continue;
	}

	if(!is_dir($dir)){
		$err .=  sprintf(_('Error! homedirs.php hasn\'t created a home directory for user %s'), $username);
		continue;
	}

	$params = array();
	$params['ssh_key'] = str_replace('###',"\n",$ssh_key);
	$params['ssh_dir'] = forge_get_config('homedir_prefix')."/$username/.ssh";

	util_sudo_effective_user($username, "create_authkeys", $params);
}

cron_entry(15,$err);
