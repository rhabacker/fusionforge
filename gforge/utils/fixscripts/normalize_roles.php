#! /usr/bin/php5
<?php
/**
 * Copyright 2010 Roland Mas
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
 * You should have received a copy of the GNU General Public License
 * along with FusionForge; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  US
 */

require (dirname(__FILE__).'/../www/env.inc.php');
require $gfwww.'include/squal_pre.php';
			 
$err='';

// Plugins subsystem
require_once('common/include/Plugin.class.php') ;
require_once('common/include/PluginManager.class.php') ;

setup_plugin_manager () ;

$res = db_query_params ('SELECT group_id, group_name FROM groups',
			array ());

$rows=db_numrows($res);

for ($i=0; $i<$rows; $i++) {
	echo "Normalizing roles for group ".db_result($res,$i,'group_name')."\n" ;

	$group = group_get_object(db_result($res,$i,'group_id')) ;

	if ($group && !$group->isError()) {
		$group->normalizeAllRoles () ;
	}
}

?>
