#!/usr/bin/perl
#/**
#  *
#  * db_cvs_history.pl - NIGHTLY SCRIPT
#  *
#  * Pulls the parsed CVS datafile (generated by cvs_history_parse.pl )
#  * from the cvs server, and parses it into the database.
#  *
#  * SourceForge: Breaking Down the Barriers to Open Source Development
#  * Copyright 1999-2001 (c) VA Linux Systems
#  * http://sourceforge.net
#  *
#  * @version   $Id$
#  * @author  Matthew Snelham <matthew@valinux.com>
#  *
#  */

#use strict; ## annoying include requirements
use DBI;
use Time::Local;
use POSIX qw( strftime );
require("/usr/lib/gforge/lib/include.pl");  # Include all the predefined functions
&db_connect;

my ($logfile, $sql, $res, $temp, %groups, $group_id, $errors );
my $verbose = 1;

##
## Set begin and end times (in epoch seconds) of day to be run
## Either specified on the command line, or auto-calculated
## to run yesterday's data.
##
if ( $ARGV[0] && $ARGV[1] && $ARGV[2] ) {

	$day_begin = timegm( 0, 0, 0, $ARGV[2], $ARGV[1] - 1, $ARGV[0] - 1900 );
	$day_end = timegm( 0, 0, 0, (gmtime( $day_begin + 86400 ))[3,4,5] );

} else {

	   ## Start at midnight last night.
	#$day_end = timegm( 0, 0, 0, (gmtime( time() ))[3,4,5] );
	$day_end = timelocal( 0, 0, 0, (localtime( time() ))[3,4,5] );
	   ## go until midnight yesterday.
	#$day_begin = timegm( 0, 0, 0, (gmtime( time() - 86400 ))[3,4,5] );
	$day_begin = timelocal( 0, 0, 0, (localtime( time() - 86400 ))[3,4,5] );

}

   ## Preformat the important date strings.
$year   = strftime("%Y", gmtime( $day_begin ) );
$mon    = strftime("%m", gmtime( $day_begin ) );
$week   = strftime("%U", gmtime( $day_begin ) );    ## GNU ext.
$day    = strftime("%d", gmtime( $day_begin ) );
print "Running week $week, day $day month $mon year $year \n" if $verbose;


   ## We'll pull down the parsed CVS log from the CVS server via http?! <sigh>
$logfile = "/var/log/gforge/cvs/$year/$mon/cvs_traffic_$year$mon$day.log";
print "Using $logfile\n";

   ## Now, we will pull all of the project ID's and names into a *massive*
   ## hash, because it will save us some real time in the log processing.
print "Caching group information from groups table.\n" if $verbose;
$sql = "SELECT group_id,unix_group_name FROM groups";
$res = $dbh->prepare($sql);
$res->execute();
while ( $temp = $res->fetchrow_arrayref() ) {
	$groups{${$temp}[1]} = ${$temp}[0];
}


# TODO
#
#   Need to do the same thing for users
#


$dbh->{AutoCommit} = 0;

$dbh->do("DELETE FROM stats_cvs_group WHERE month='$year$mon' AND day='$day'");

## begin parsing the log file line by line.
print "Parsing the information into the database..." if $verbose;
open( LOGFILE, $logfile ) or die "Cannot open $logfile";
while(<LOGFILE>) {

	if ( $_ =~ /^G::/ ) {
		chomp($_);

		   ## (G|U|E)::proj_name::user_name::checkouts::commits::adds
		my ($type, $group, $user, $checkouts, $commits, $adds) = split( /::/, $_, 6 );

		$group_id = $groups{$group};

		if ( $group_id == 0 ) {
			print STDERR "db_cvs_history.pl: bad unix_group_name \'$name\' ($_)\n";
			next;
		}
			
		$sql = "INSERT INTO stats_cvs_group
			(month,day,group_id,checkouts,commits,adds)
			VALUES ('$year$mon','$day','$group_id','$checkouts','$commits','$adds')";

		$dbh->do( $sql );

	} elsif ( $_ =~ /^U::/ ) {
#
#   TODO - process user cvs stats
#
#   table: stats_cvs_user
#
	} elsif ( $_ =~ /^E::/ ) {
		$errors++;
	}

}
close( LOGFILE );

$dbh->commit;

print " done.\n" if $verbose;

##
## EOF
##
