<?php
/**
  *
  * Project Statistics Page
  *
  * SourceForge: Breaking Down the Barriers to Open Source Development
  * Copyright 1999-2001 (c) VA Linux Systems
  * http://sourceforge.net
  *
  * @version   $Id$
  *
  */

   // week_to_dates
function week_to_dates( $week, $year = 0 ) {

	if ( $year == 0 ) {
		$year = gmstrftime("%Y", time() );
	} 

	   // One second into the New Year!
	$beginning = gmmktime(0,0,0,1,1,$year);
	while ( gmstrftime("%U", $beginning) < 1 ) {
		   // 86,400 seconds? That's almost exactly one day!
		$beginning += 86400;
	}
	$beginning += (86400 * 7 * ($week - 1));
	$end = $beginning + (86400 * 6);

	return array( $beginning, $end );
}


   // stats_project_daily
function stats_project_daily( $group_id, $span = 7 ) {
	global $HTML;
	global $Language;

	//
	//	We now only have 30 & 7-day views
	//
	if ( $span != 30 && $span != 7) { 
		$span = 7;
	}

	$sql="SELECT * FROM stats_project_vw
		WHERE group_id='$group_id' ORDER BY month DESC, day DESC";

	if ($span == 30) {
		$res = db_query($sql, 30, 0, SYS_DB_STATS);
	} else {
		$res = db_query($sql,  7, 0, SYS_DB_STATS);
	}

	echo db_error();

   // if there are any days, we have valid data.
	if ( ($valid_days = db_numrows( $res )) > 0 ) {
		?>
		<p><strong><?php echo $Language->getText('project_stats_utils','stats_for_days',array($valid_days )) ?></strong></p>

		<p><table width="100%" cellpadding="0" cellspacing="1" border="0">
			<tr valign="top">
			<td><strong><?php echo $Language->getText('project_stats_utils','date') ?></strong></td>
			<td><strong><?php echo $Language->getText('project_stats_utils','rank') ?></strong></td>
			<td align="right"><strong><?php echo $Language->getText('project_stats_utils','page_views') ?> </strong></td>
			<td align="right"><strong><?php echo $Language->getText('project_stats_utils','dl') ?></strong></td>
			<td align="right"><strong><?php echo $Language->getText('project_stats_utils','bugs') ?></strong></td>
			<td align="right"><strong><?php echo $Language->getText('project_stats_utils','support') ?></strong></td>
			<td align="right"><strong><?php echo $Language->getText('project_stats_utils','patches') ?></strong></td>
			<td align="right"><strong><?php echo $Language->getText('project_stats_utils','all_tracker') ?></strong></td>
			<td align="right"><strong><?php echo $Language->getText('project_stats_utils','tasks') ?></strong></td>
			<td align="right"><strong><?php echo $Language->getText('project_stats_utils','CVS') ?></strong></td>
			</tr>

		<?php
		
		while ( $row = db_fetch_array($res) ) {
			$i++;
			print	'<tr ' . $HTML->boxGetAltRowStyle($i) . '>'
				. '<td>' . gmstrftime("%e %b %Y", gmmktime(0,0,0,substr($row["month"],4,2),$row["day"],substr($row["month"],0,4)) ) . '</td>'
				//. '<td>' . $row["month"] . " " . $row["day"] . '</td>'
				. '<td>' . sprintf("%d", $row["group_ranking"]) . " ( " . sprintf("%0.2f", $row["group_metric"]) . ' ) </td>'
				. '<td align="right">' . number_format( $row["subdomain_views"] + $row['site_views'],0 ) . '</td>'
				. '<td align="right">' . number_format( $row["downloads"],0 ) . '</td>'
				. '<td align="right">&nbsp;&nbsp;' . number_format($row["bugs_opened"],0) . " ( " . number_format($row["bugs_closed"],0) . ' )</td>'
				. '<td align="right">&nbsp;&nbsp;' . number_format($row["support_opened"],0) . " ( " . number_format($row["support_closed"],0) . ' )</td>'
				. '<td align="right">&nbsp;&nbsp;' . number_format($row["patches_opened"],0) . " ( " . number_format($row["patches_closed"],0) . ' )</td>'
				. '<td align="right">&nbsp;&nbsp;' . number_format($row["artifacts_opened"],0) . " ( " . number_format($row["artifacts_closed"],0) . ' )</td>'
				. '<td align="right">&nbsp;&nbsp;' . number_format($row["tasks_opened"],0) . " ( " . number_format($row["tasks_closed"],0) . ' )</td>'
				. '<td align="right">&nbsp;&nbsp;' . number_format($row["cvs_commits"],0) . '</td>'
				. '</tr>' . "\n";
		}

		?>
		</table></p>
		<?php

	} else {
		echo $Language->getText('project_stats_utils','project_did_not_exits');
		echo db_error() .'</p>';
	}

}

   // stats_project_monthly
function stats_project_monthly( $group_id ) {
	global $HTML;
	global $Language;
	$res = db_query("
		SELECT * FROM stats_project_months 
		WHERE group_id='$group_id'
		ORDER BY group_id DESC, month DESC
	", -1, 0, SYS_DB_STATS);

	   // if there are any weeks, we have valid data.
	if ( ($valid_months = db_numrows( $res )) > 1 ) {

		?>
		<p><strong><?php echo $Language->getText('project_stats_utils','stats_for_months',array($valid_months )) ?></strong></p>

		<p><table width="100%" cellpadding="0" cellspacing="1" border="0">
			<tr valign="top">
			<td><strong><?php echo $Language->getText('project_stats_utils','month') ?></strong></td>
			<td><strong><?php echo $Language->getText('project_stats_utils','rank') ?></strong></td>
			<td align="right"><strong><?php echo $Language->getText('project_stats_utils','page_views') ?></strong></td>
			<td align="right"><strong><?php echo $Language->getText('project_stats_utils','dl') ?></strong></td>
			<td align="right"><strong><?php echo $Language->getText('project_stats_utils','bugs') ?></strong></td>
			<td align="right"><strong><?php echo $Language->getText('project_stats_utils','support') ?></strong></td>
			<td align="right"><strong><?php echo $Language->getText('project_stats_utils','patches') ?></strong></td>
			<td align="right"><strong><?php echo $Language->getText('project_stats_utils','all_tracker') ?></strong></td>
			<td align="right"><strong><?php echo $Language->getText('project_stats_utils','tasks') ?></strong></td>
			<td align="right"><strong><?php echo $Language->getText('project_stats_utils','CVS') ?></strong></td>
			</tr>

		<?php

		while ( $row = db_fetch_array($res) ) {
			$i++;

			print	'<tr ' . $HTML->boxGetAltRowStyle($i) . '>'
				. '<td>' . gmstrftime("%B %Y", mktime(0,0,1,substr($row["month"],4,2),1,substr($row["month"],0,4)) ) . '</td>'
				. '<td>' . sprintf("%d", $row["group_ranking"]) . " ( " . sprintf("%0.2f", $row["group_metric"]) . ' ) </td>'
				. '<td align="right">' . number_format( $row["subdomain_views"] + $row['site_views'],0 ) . '</td>'
				. '<td align="right">' . number_format( $row["downloads"],0 ) . '</td>'
				. '<td align="right">&nbsp;&nbsp;' . number_format($row["bugs_opened"],0) . " ( " . number_format($row["bugs_closed"],0) . ' )</td>'
				. '<td align="right">&nbsp;&nbsp;' . number_format($row["support_opened"],0) . " ( " . number_format($row["support_closed"],0) . ' )</td>'
				. '<td align="right">&nbsp;&nbsp;' . number_format($row["patches_opened"],0) . " ( " . number_format($row["patches_closed"],0) . ' )</td>'
				. '<td align="right">&nbsp;&nbsp;' . number_format($row["artifacts_opened"],0) . " ( " . number_format($row["artifacts_closed"],0) . ' )</td>'
				. '<td align="right">&nbsp;&nbsp;' . number_format($row["tasks_opened"],0) . " ( " . number_format($row["tasks_closed"],0) . ' )</td>'
				. '<td align="right">&nbsp;&nbsp;' . number_format($row["cvs_commits"],0) . '</td>'
				. '</tr>' . "\n";
		}

		?>
		</table></p>
		<?php

	} else {
		echo $Language->getText('project_stats_utils','project_did_not_exits')."<p>";
		echo db_error();
	}
}

function stats_project_all( $group_id ) {
	global $HTML;
	global $Language;
	$res = db_query("
		SELECT *
		FROM stats_project_all_vw
		WHERE group_id='$group_id'
	", -1, 0, SYS_DB_STATS);
	$row = db_fetch_array($res);
//	echo db_error();

	?>
	<p><strong><?php echo $Language->getText('project_stats_utils','stats_all_time') ?></strong></p>

	<p><table width="100%" cellpadding="0" cellspacing="1" border="0">
		<tr valign="top">
			<td><strong><?php echo $Language->getText('project_stats_utils','month') ?></strong></td>
			<td><strong><?php echo $Language->getText('project_stats_utils','rank') ?></strong></td>
			<td align="right"><strong><?php echo $Language->getText('project_stats_utils','page_views') ?></strong></td>
			<td align="right"><strong><?php echo $Language->getText('project_stats_utils','dl') ?></strong></td>
			<td align="right"><strong><?php echo $Language->getText('project_stats_utils','bugs') ?></strong></td>
			<td align="right"><strong><?php echo $Language->getText('project_stats_utils','support') ?></strong></td>
			<td align="right"><strong><?php echo $Language->getText('project_stats_utils','patches') ?></strong></td>
			<td align="right"><strong><?php echo $Language->getText('project_stats_utils','all_tracker') ?> </strong></td>
			<td align="right"><strong><?php echo $Language->getText('project_stats_utils','tasks') ?></strong></td>
			<td align="right"><strong><?php echo $Language->getText('project_stats_utils','CVS') ?></strong></td>
		</tr>

	<tr <?php echo $HTML->boxGetAltRowStyle(1); ?>>
		<td><?php echo $row["day"]; ?> <?php echo $Language->getText('project_stats_utils','days') ?> </td>
		<td><?php echo sprintf("%d", $row["group_ranking"]) . " ( " . sprintf("%0.2f", $row["group_metric"]); ?> ) </td>
		<td align="right"><?php echo number_format( $row["subdomain_views"] + $row['site_views'],0); ?></td>
		<td align="right"><?php echo number_format( $row["downloads"],0); ?></td>
		<td align="right"><?php echo number_format($row["bugs_opened"],0) . " ( " . number_format($row["bugs_closed"],0); ?> )</td>
		<td align="right"><?php echo number_format($row["support_opened"],0) . " ( " . number_format($row["support_closed"],0); ?> )</td>
		<td align="right"><?php echo number_format($row["patches_opened"],0) . " ( " . number_format($row["patches_closed"],0); ?> )</td>
		<td align="right"><?php echo number_format($row["artifacts_opened"],0) . " ( " . number_format($row["artifacts_closed"],0); ?> )</td>
		<td align="right"><?php echo number_format($row["tasks_opened"],0) . " ( " . number_format($row["tasks_closed"],0); ?> )</td>
		<td align="right"><?php echo number_format($row["cvs_commits"],0); ?></td>
		</tr>

	</table></p>

	<?php

}


function period2seconds($period_name,$span) {
	if (!$period_name || $period_name=="lifespan") {
		return "";
	}

	if (!$span) $span=1;

	if ($period_name=="day") {
		return 60*60*24*$span;
	} else if ($period_name=="week") {
		return 60*60*24*7*$span;
	} else if ($period_name=="month") {
		return 60*60*24*30*$span;
	} else if ($period_name=="year") {
		return 60*60*24*365*$span;
	}
}

function period2sql($period_name,$span,$field_name) {
	$time_now=time();
	$seconds=period2seconds($period_name,$span);

	if (!$seconds) return "";

	return "AND $field_name>=" . (string)($time_now-$seconds) ." \n";
}

?>
