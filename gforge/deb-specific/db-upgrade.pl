#!/usr/bin/perl -w
#
# $Id$
#
# Debian-specific script to upgrade the database between releases
# Roland Mas <lolando@debian.org>

use strict ;
use diagnostics ;

use DBI ;
use MIME::Base64 ;
use HTML::Entities ;

use vars qw/$dbh @reqlist/ ;
use vars qw/$sys_default_domain $sys_cvs_host $sys_download_host
    $sys_shell_host $sys_users_host $sys_docs_host $sys_lists_host
    $sys_dns1_host $sys_dns2_host $FTPINCOMING_DIR $FTPFILES_DIR
    $sys_urlroot $sf_cache_dir $sys_name $sys_themeroot
    $sys_news_group $sys_dbhost $sys_dbname $sys_dbuser $sys_dbpasswd
    $sys_ldap_base_dn $sys_ldap_host $admin_login $admin_password
    $server_admin $domain_name $newsadmin_groupid $statsadmin_groupid
    $skill_list/ ;

sub is_lesser ( $$ ) ;
sub is_greater ( $$ ) ;
sub debug ( $ ) ;
sub parse_sql_file ( $ ) ;

require ("/usr/lib/sourceforge/lib/include.pl") ; # Include a few predefined functions 
require ("/usr/lib/sourceforge/lib/sqlparser.pm") ; # Our magic SQL parser

debug "You'll see some debugging info during this installation." ;
debug "Do not worry unless told otherwise." ;

&db_connect ;

# debug "Connected to the database OK." ;

$dbh->{AutoCommit} = 0;
$dbh->{RaiseError} = 1;
eval {
    my ($query, $sth, @array, $version, $action, $path, $target) ;

    # Do we have at least the basic schema?

    $query = "SELECT count(*) from pg_class where relname = 'groups' and relkind = 'r'";
    # debug $query ;
    $sth = $dbh->prepare ($query) ;
    $sth->execute () ;
    @array = $sth->fetchrow_array () ;
    $sth->finish () ;

    # Create Sourceforge database

    if ($array [0] == 0) {	# No 'groups' table
	# Installing SF 2.6 from scratch
	$action = "installation" ;
	debug "Creating initial Sourceforge database from files." ;

	&create_metadata_table ("2.5.9999") ;

	debug "Updating debian_meta_data table." ;
	$query = "INSERT INTO debian_meta_data (key, value) VALUES ('current-path', 'scratch-to-2.6')" ;
	# debug $query ;
	$sth = $dbh->prepare ($query) ;
	$sth->execute () ;
	$sth->finish () ;
	debug "Committing." ;
	$dbh->commit () ;

    } else {			# A 'groups' table exists
	$action = "upgrade" ;
	
	$query = "SELECT count(*) from pg_class where relname = 'debian_meta_data' and relkind = 'r'";
	# debug $query ;
	$sth = $dbh->prepare ($query) ;
	$sth->execute () ;
	@array = $sth->fetchrow_array () ;
	$sth->finish () ;

	if ($array[0] == 0) {	# No 'debian_meta_data' table
	    # If we're here, we're upgrading from 2.5-7 or earlier
	    # We therefore need to create the table
	    &create_metadata_table ("2.5-7+just+before+8") ;
	}
	
	$version = &get_db_version ;
	if (is_lesser $version, "2.5.9999") {
	    debug "Found an old (2.5) database, will upgrade to 2.6" ;
	    
	    $query = "SELECT count(*) from debian_meta_data where key = 'current-path'";
	    # debug $query ;
	    $sth = $dbh->prepare ($query) ;
	    $sth->execute () ;
	    @array = $sth->fetchrow_array () ;
	    $sth->finish () ;

	    if ($array[0] == 0) {
		# debug "Updating debian_meta_data table." ;
		$query = "INSERT INTO debian_meta_data (key, value) VALUES ('current-path', '2.5-to-2.6')" ;
		# debug $query ;
		$sth = $dbh->prepare ($query) ;
		$sth->execute () ;
		$sth->finish () ;
		debug "Committing." ;
		$dbh->commit () ;
	    }
	}
    }
    
    $query = "SELECT count(*) from debian_meta_data where key = 'current-path'";
    # debug $query ;
    $sth = $dbh->prepare ($query) ;
    $sth->execute () ;
    @array = $sth->fetchrow_array () ;
    $sth->finish () ;

    if ($array[0] == 0) {
	$path = "" ;
    } else {
	$query = "SELECT value from debian_meta_data where key = 'current-path'";
	# debug $query ;
	$sth = $dbh->prepare ($query) ;
	$sth->execute () ;
	@array = $sth->fetchrow_array () ;
	$sth->finish () ;
	
	$path = $array[0] ;
    }

  PATH_SWITCH: {
      ($path eq 'scratch-to-2.6') && do {
	  $version = &get_db_version ;
	  $target = "2.5.9999.1+global+data+done" ;
	  if (is_lesser $version, $target) {
	      my @filelist = qw{ /usr/lib/sourceforge/db/sf-2.6-complete.sql } ;
	      # TODO: user_rating.sql

	      foreach my $file (@filelist) {
		  debug "Processing $file" ;
		  @reqlist = @{ &parse_sql_file ($file) } ;
		  
		  foreach my $s (@reqlist) {
		      $query = $s ;
		      # debug $query ;
		      $sth = $dbh->prepare ($query) ;
		      $sth->execute () ;
		      $sth->finish () ;
		  }
	      }
	      @reqlist = () ;
	      
	      &update_db_version ($target) ;
	      debug "Committing." ;
	      $dbh->commit () ;
	  }

	  $version = &get_db_version ;
	  $target = "2.5.9999.2+local+data+done" ;
	  if (is_lesser $version, $target) {
	      debug "Adding local data." ;
	      
	      do "/etc/sourceforge/local.pl" or die "Cannot read /etc/sourceforge/local.pl" ;
	      
	      my ($login, $pwd, $md5pwd, $email, $noreplymail, $date) ;
	      
	      $login = $admin_login ;
	      $pwd = $admin_password ;
	      $md5pwd=qx/echo -n $pwd | md5sum/ ;
	      chomp $md5pwd ;
	      $email = $server_admin ;
	      $noreplymail="noreply\@$domain_name" ;
	      $date = time () ;
	      
	      @reqlist = (
			  "UPDATE groups SET homepage = '$domain_name/admin/' where group_id = 1",
			  "UPDATE groups SET homepage = '$domain_name/news/' where group_id = 2",
			  "UPDATE groups SET homepage = '$domain_name/stats/' where group_id = 3",
			  "UPDATE groups SET homepage = '$domain_name/peerrating/' where group_id = 4",
			  "UPDATE users SET email = '$noreplymail' where user_id = 100",
			  "INSERT INTO users VALUES (101,'$login','$email','$md5pwd','Sourceforge admin','A','/bin/bash','','N',2000,'shell',$date,'',1,0,NULL,NULL,0,'','GMT', 1, 0)", 
			  "SELECT setval ('\"users_pk_seq\"', 102, 'f')",
			  "INSERT INTO user_group (user_id, group_id, admin_flags) VALUES (101, 1, 'A')",
			  "INSERT INTO user_group (user_id, group_id, admin_flags) VALUES (101, 2, 'A')",
			  "INSERT INTO user_group (user_id, group_id, admin_flags) VALUES (101, 3, 'A')",
			  "INSERT INTO user_group (user_id, group_id, admin_flags) VALUES (101, 4, 'A')"
			  ) ;
	      
	      foreach my $s (@reqlist) {
		  $query = $s ;
		  # debug $query ;
		  $sth = $dbh->prepare ($query) ;
		  $sth->execute () ;
		  $sth->finish () ;
	      }
	      @reqlist = () ;
	      
	      &update_db_version ($target) ;
	      debug "Committing." ;
	      $dbh->commit () ;
	  }
	  
	  $version = &get_db_version ;
	  $target = "2.5.9999.3+skills+done" ;
	  if (is_lesser $version, $target) {
	      debug "Inserting skills." ;
	      
	      foreach my $skill (split /;/, $skill_list) {
		  push @reqlist, "INSERT INTO people_skill (name) VALUES ('$skill')" ;
	      }
	      
	      foreach my $s (@reqlist) {
		  $query = $s ;
		  # debug $query ;
		  $sth = $dbh->prepare ($query) ;
		  $sth->execute () ;
		  $sth->finish () ;
	      }
	      @reqlist = () ;
	      
	      &update_db_version ($target) ;
	      debug "Committing." ;
	      $dbh->commit () ;
	  }

	  $version = &get_db_version ;
	  $target = "2.6-0" ;
	  if (is_lesser $version, $target) {
	      debug "Updating debian_meta_data table." ;
	      $query = "DELETE FROM debian_meta_data WHERE key = 'current-path'" ;
	      # debug $query ;
	      $sth = $dbh->prepare ($query) ;
	      $sth->execute () ;
	      $sth->finish () ;
	      
	      &update_db_version ($target) ;
	      debug "Committing." ;
	      $dbh->commit () ;
	  }

	  last PATH_SWITCH ;
      } ;

      ($path eq '2.5-to-2.6') && do {
	  
	  $version = &get_db_version ;
	  $target = "2.5-8" ;
	  if (is_lesser $version, $target) {
	      debug "Adding row to people_job_category." ;
	      $query = "INSERT INTO people_job_category VALUES (100, 'Undefined', 0)" ;
	      $sth = $dbh->prepare ($query) ;
	      $sth->execute () ;
	      $sth->finish () ;
	      
	      &update_db_version ($target) ;
	      debug "Committing." ;
	      $dbh->commit () ;
	  }

	  $version = &get_db_version ;
	  $target = "2.5-25" ;
	  if (is_lesser $version, $target) {
	      debug "Adding row to supported_languages." ;
	      $query = "INSERT INTO supported_languages VALUES (15, 'Korean', 'Korean.class', 'Korean', 'kr')" ;
	      $sth = $dbh->prepare ($query) ;
	      $sth->execute () ;
	      $sth->finish () ;

	      &update_db_version ($target) ;
	      debug "Committing." ;
	      $dbh->commit () ;
	  }

	  $version = &get_db_version ;
	  $target = "2.5-27" ;
	  if (is_lesser $version, $target) {
	      debug "Fixing unix_box entries." ;
	      
	      $query = "update groups set unix_box = 'shell'" ;
	      $sth = $dbh->prepare ($query) ;
	      $sth->execute () ;
	      $sth->finish () ;
	      
	      $query = "update users set unix_box = 'shell'" ;
	      $sth = $dbh->prepare ($query) ;
	      $sth->execute () ;
	      $sth->finish () ;
	      
	      debug "Also fixing a few sequences." ;

	      &bump_sequence_to ("bug_pk_seq", 100) ;
	      &bump_sequence_to ("project_task_pk_seq", 100) ;

	      &update_db_version ($target) ;
	      debug "Committing." ;
	      $dbh->commit () ;
	  }

	  $version = &get_db_version ;
	  $target = "2.5.9999.1+temp+data+dropped" ;
	  if (is_lesser $version, $target) {
	      debug "Preparing to upgrade your database - dropping temporary tables" ;

	      my @tables = qw/ user_metric_tmp1_1 user_metric_tmp1_2
		  user_metric_tmp1_3 user_metric_tmp1_4
		  user_metric_tmp1_5 user_metric_tmp1_6
		  user_metric_tmp1_7 user_metric_tmp1_8 user_metric1
		  user_metric2 user_metric3 user_metric4 user_metric5
		  user_metric6 user_metric7 user_metric8
		  project_counts_tmp project_metric_tmp
		  project_metric_tmp1 project_counts_weekly_tmp
		  project_metric_weekly_tmp project_metric_weekly_tmp1
		  / ;

	      my @sequences = qw/ user_metric1_ranking_seq
		  user_metric2_ranking_seq user_metric3_ranking_seq
		  user_metric4_ranking_seq user_metric5_ranking_seq
		  user_metric6_ranking_seq user_metric7_ranking_seq
		  user_metric8_ranking_seq project_metric_weekly_seq
		  trove_treesum_trove_treesum_seq
		  project_metric_tmp1_pk_seq / ;

	      my @indexes = qw/ idx_project_metric_group
		  idx_project_metric_weekly_group
		  user_metric_history_date_userid / ;

	      foreach my $table (@tables) {
		  &drop_table_if_exists ($table) ;
	      }

	      foreach my $sequence (@sequences) {
		  &drop_sequence_if_exists ($sequence) ;
	      }

	      foreach my $index (@indexes) {
		  &drop_index_if_exists ($index) ;
	      }

	      &update_db_version ($target) ;
	      debug "Committing." ;
	      $dbh->commit () ;
	  }

	  $version = &get_db_version ;
	  $target = "2.5.9999.2+data+upgraded" ;
	  if (is_lesser $version, $target) {
	      debug "Upgrading your database scheme from 2.5" ;

	      @reqlist = @{ &parse_sql_file ("/usr/lib/sourceforge/db/sf2.5-to-sf2.6.sql") } ;
	      foreach my $s (@reqlist) {
		  $query = $s ;
		  debug $query ;
		  $sth = $dbh->prepare ($query) ;
		  $sth->execute () ;
		  $sth->finish () ;
	      }
	      @reqlist = () ;

	      &update_db_version ($target) ;
	      debug "Committing." ;
	      $dbh->commit () ;
	  }

	  $version = &get_db_version ;
	  $target = "2.5.9999.3+artifact+transcoded" ;
	  if (is_lesser $version, $target) {
	      debug "Transcoding the artifact data fields" ;

	      $query = "SELECT id,bin_data FROM artifact_file ORDER BY id ASC" ;
	      # debug $query ;
	      $sth = $dbh->prepare ($query) ;
	      $sth->execute () ;
	      while (@array = $sth->fetchrow_array) {
		  my $query2 = "UPDATE artifact_file SET bin_data='" ;
		  $query2 .= encode_base64 (decode_entities ($array [1])) ;
		  $query2 .= "' WHERE id=" ;
		  $query2 .= $array [0] ;
		  $query2 .= "" ;
		  # debug $query2 ;
		  my $sth2 =$dbh->prepare ($query2) ;
		  $sth2->execute () ;
		  $sth2->finish () ;
	      }
	      $sth->finish () ;

	      @reqlist = () ;
	      &update_db_version ($target) ;
	      debug "Committing." ;
	      $dbh->commit () ;
	  }

	  $version = &get_db_version ;
	  $target = "2.5.9999.4+groups+inserted" ;
	  if (is_lesser $version, $target) {
	      debug "Inserting missing groups" ;

	      @reqlist = (
			  "INSERT INTO groups (group_name, homepage,
                           is_public, status, unix_group_name,
                           unix_box, http_domain, short_description,
                           cvs_box, license, register_purpose,
                           license_other, register_time, rand_hash,
                           use_mail, use_survey, use_forum, use_pm,
                           use_cvs, use_news, type, use_docman,
                           new_task_address, send_all_tasks,
                           use_pm_depend_box)
       	                   VALUES ('Stats', '$domain_name/top/', 1,
       	    	           'A', 'stats', 'shell', NULL, NULL, 'cvs',
       	    	           'website', NULL, NULL, 0, NULL, 1, 0, 0, 0, 0,
       	    	           1, 1, 1, '', 0, 0)",
			  "INSERT INTO groups (group_name, homepage,
                           is_public, status, unix_group_name,
                           unix_box, http_domain, short_description,
                           cvs_box, license, register_purpose,
                           license_other, register_time, rand_hash,
                           use_mail, use_survey, use_forum, use_pm,
                           use_cvs, use_news, type, use_docman,
                           new_task_address, send_all_tasks,
                           use_pm_depend_box)
                           VALUES ('Peer Ratings', '$domain_name/people/', 0,
                           'A', 'peerrating', 'shell', NULL, NULL, 'cvs1',
                           'website', NULL, NULL, 0, NULL, 1, 0, 0, 0, 0,
                           1, 1, 0, '', 0, 0)"
			  ) ;
	      
	      foreach my $s (@reqlist) {
		  $query = $s ;
		  debug $query ;
		  $sth = $dbh->prepare ($query) ;
		  $sth->execute () ;
		  $sth->finish () ;
	      }
	      @reqlist = () ;
	      &update_db_version ($target) ;
	      debug "Committing." ;
	      $dbh->commit () ;
	  }

	  $version = &get_db_version ;
	  $target = "2.6-0" ;
	  if (is_lesser $version, $target) {
	      debug "Database has successfully been converted." ;
	      $query = "DELETE FROM debian_meta_data WHERE key = 'current-path'" ;
	      # debug $query ;
	      $sth = $dbh->prepare ($query) ;
	      $sth->execute () ;
	      $sth->finish () ;
	      
	      &update_db_version ($target) ;
	      debug "Committing." ;
	      $dbh->commit () ;
	  }
	  
	  last PATH_SWITCH ;
      } ;
  }
    
    debug "It seems your database $action went well and smoothly.  That's cool." ;
    debug "Please enjoy using Debian Sourceforge." ;
    
    # There should be a commit at the end of every block above.
    # If there is not, then it might be symptomatic of a problem.
    # For safety, we roll back.
    $dbh->rollback ();
};

if ($@) {
    warn "Transaction aborted because $@" ;
    debug "Transaction aborted because $@" ;
    $dbh->rollback ;
    debug "Please report this bug on the Debian bug-tracking system." ;
    debug "Please include the previous messages as well to help debugging." ;
    debug "You should not worry too much about this," ;
    debug "your DB is still in a consistent state and should be usable." ;
    exit 1 ;
}

$dbh->rollback ;
$dbh->disconnect ;

sub is_lesser ( $$ ) {
    my $v1 = shift || 0 ;
    my $v2 = shift || 0 ;

    my $rc = system "dpkg --compare-versions $v1 lt $v2" ;
    
    return (! $rc) ;
}

sub is_greater ( $$ ) {
    my $v1 = shift || 0 ;
    my $v2 = shift || 0 ;

    my $rc = system "dpkg --compare-versions $v1 gt $v2" ;
    
    return (! $rc) ;
}

sub debug ( $ ) {
    my $v = shift ;
    chomp $v ;
    print STDERR "$v\n" ;
}

sub create_metadata_table ( $ ) {
    my $v = shift || "2.5-7+just+before+8" ;
    # Do we have the metadata table?

    my $query = "SELECT count(*) FROM pg_class WHERE relname = 'debian_meta_data' and relkind = 'r'";
    # debug $query ;
    my $sth = $dbh->prepare ($query) ;
    $sth->execute () ;
    my @array = $sth->fetchrow_array () ;
    $sth->finish () ;

    # Let's create this table if we have it not

    if ($array [0] == 0) {
	debug "Creating debian_meta_data table." ;
	$query = "CREATE TABLE debian_meta_data (key varchar primary key, value text not null)" ;
	# debug $query ;
	$sth = $dbh->prepare ($query) ;
	$sth->execute () ;
	$sth->finish () ;
    }
    
    $query = "SELECT count(*) FROM debian_meta_data WHERE key = 'db-version'";
    # debug $query ;
    $sth = $dbh->prepare ($query) ;
    $sth->execute () ;
    @array = $sth->fetchrow_array () ;
    $sth->finish () ;

    # Empty table?  We'll have to fill it up a bit

    if ($array [0] == 0) {
	debug "Inserting first data into debian_meta_data table." ;
	$query = "INSERT INTO debian_meta_data (key, value) VALUES ('db-version', '$v')" ;
	# debug $query ;
	$sth = $dbh->prepare ($query) ;
	$sth->execute () ;
	$sth->finish () ;
    }
}

sub update_db_version ( $ ) {
    my $v = shift or die "Not enough arguments" ;

    debug "Updating debian_meta_data table." ;
    my $query = "UPDATE debian_meta_data SET value = '$v' WHERE key = 'db-version'" ;
    # debug $query ;
    my $sth = $dbh->prepare ($query) ;
    $sth->execute () ;
    $sth->finish () ;
}

sub get_db_version () {
    my $query = "SELECT value FROM debian_meta_data WHERE key = 'db-version'" ;
    # debug $query ;
    my $sth = $dbh->prepare ($query) ;
    $sth->execute () ;
    my @array = $sth->fetchrow_array () ;
    $sth->finish () ;
    
    my $version = $array [0] ;

    return $version ;
}

sub drop_table_if_exists ( $ ) {
    my $tname = shift or die  "Not enough arguments" ;
    my $query = "SELECT count(*) FROM pg_class WHERE relname='$tname' AND relkind='r'" ;
    my $sth = $dbh->prepare ($query) ;
    $sth->execute () ;
    my @array = $sth->fetchrow_array () ;
    $sth->finish () ;
    
    if ($array [0] != 0) {
	# debug "Dropping table $tname" ;
	$query = "DROP TABLE $tname" ;
	debug $query ;
	$sth = $dbh->prepare ($query) ;
	$sth->execute () ;
	$sth->finish () ;
    }
}

sub drop_sequence_if_exists ( $ ) {
    my $sname = shift or die  "Not enough arguments" ;
    my $query = "SELECT count(*) FROM pg_class WHERE relname='$sname' AND relkind='S'" ;
    my $sth = $dbh->prepare ($query) ;
    $sth->execute () ;
    my @array = $sth->fetchrow_array () ;
    $sth->finish () ;
    
    if ($array [0] != 0) {
	# debug "Dropping sequence $sname" ;
	$query = "DROP SEQUENCE $sname" ;
	debug $query ;
	$sth = $dbh->prepare ($query) ;
	$sth->execute () ;
	$sth->finish () ;
    }
}

sub drop_index_if_exists ( $ ) {
    my $iname = shift or die  "Not enough arguments" ;
    my $query = "SELECT count(*) FROM pg_class WHERE relname='$iname' AND relkind='i'" ;
    my $sth = $dbh->prepare ($query) ;
    $sth->execute () ;
    my @array = $sth->fetchrow_array () ;
    $sth->finish () ;
    
    if ($array [0] != 0) {
	# debug "Dropping index $iname" ;
	$query = "DROP INDEX $iname" ;
	debug $query ;
	$sth = $dbh->prepare ($query) ;
	$sth->execute () ;
	$sth->finish () ;
    }
}

sub bump_sequence_to ( $$ ) {
    my ($query, $sth, @array, $seqname, $targetvalue) ;

    $seqname = shift ;
    $targetvalue = shift ;

    do {
	$query = "select nextval ('$seqname')" ;
	$sth = $dbh->prepare ($query) ;
	$sth->execute () ;
	@array = $sth->fetchrow_array () ;
	$sth->finish () ;
    } until $array[0] >= $targetvalue ;
}
