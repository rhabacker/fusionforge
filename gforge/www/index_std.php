<!-- whole page table -->
<TABLE width=100% cellpadding=5 cellspacing=0 border=0>
<TR><TD width="65%" VALIGN="TOP">
<P>
<H2>Welcome to the GForge 3.0 Project!</H2>
<P>
GForge is a fork of the 2.61 SourceForge code, which was only available via anonymous 
CVS from VA (Research|Linux|Software).
<P>
GForge.org is <B>not</B> a project hosting platform, it is merely an implementation of
the GForge code, which is available for public download on the right hand side of this
page.
<P>
<A HREF="/tracker/?atid=101&group_id=5&func=browse"><IMG SRC="/images/gforge.jpg" WIDTH=450 HEIGHT=268 BORDER=0></A>
<P>
We believe that the GForge functionality is important not only to the Open Source 
community, but to the wider business community. Since VA has not released the 
source in over one year, despite their promises to the contrary,
a fork was necessary to ensure a viable open source version of the codebase.
<P>
The GForge project was formed and is maintained by Tim Perdue,
the original author of much of the original SourceForge web code.
<P>
Major changes are present in the current GForge 3.0 codebase
<P>
<UL>
<LI><B>Jabber Support!</B> System events, such as bug submissions, are optionally sent 
via jabber and email
<LI><B>Radically easier to install!</B> By removing SF.net-specific code, like caching 
and image servers, many install dependencies could be eliminated.
<LI><B>New interface.</B> The interface should make it easier to navigate as well as know 
your present location.
<LI>Code cleanup. Since GForge does not need to scale to 500,000+ users, many 
hacks and optimizations can be removed.
<LI>Foundries and related nonsense have been removed.
</UL>
<P>

<?php
echo $HTML->boxTop($Language->getText('group','long_news'));
echo news_show_latest($sys_news_group,5,true,false,false,5);
echo $HTML->boxBottom();
?>

</TD>

<TD width="35%" VALIGN="TOP">

<?php
echo $HTML->boxTop('Getting GForge');
?>
<B>Download:</B><BR>
<A HREF="/project/showfiles.php?group_id=1">GForge3.0pre5</A><BR>
<A HREF="http://postgresql.org/">PostgreSQL</A><BR>
<A HREF="http://www.php.net/">PHP 4.x</A><BR>
<A HREF="http://www.apache.org/">Apache</A><BR>
<A HREF="http://www.gnu.org/software/mailman/">MailMan *</A><BR>
<A HREF="http://www.python.org/">Python *</A><BR>
<A HREF="http://jabberd.jabberstudio.org/">Jabber Server *</A><BR>
* optional
<P>
<A HREF="/forum/forum.php?forum_id=6"><B>Help Board</B></A><BR>
<A HREF="/forum/forum.php?forum_id=5"><B>Developer Board</B></A><BR>
<A HREF="/tracker/?atid=105&group_id=1&func=browse"><B>Bug Tracker</B></A><BR>
<A HREF="/tracker/?func=browse&group_id=1&atid=106"><B>Patch Submissions</B></A>
<P>
<A HREF="http://www.debian.org/"><B>Debian Users</B></A> can simply add 
"http://people.debian.org/~bayle/" to /etc/apt/sources.list and type 
"apt-get install sourceforge" to 
install a working SF2.6.1 system, thanks to the Debian-SF project. Debian-SF
is working to incorporate the GForge 3.0 changes into their package.
<P>
<?php
echo $HTML->boxBottom();
echo show_features_boxes();
?>

</TD></TR></TABLE>

