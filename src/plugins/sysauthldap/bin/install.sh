#! /bin/sh

p=$(forge_get_config plugins_path)/sysauthldap

if [ -x /usr/sbin/slapd ] && [ -x /usr/bin/ldapadd ] ; then
    if ! slapcat -b cn=schema,cn=config 2> /dev/null | egrep -q ^cn:.\{[[:digit:]]+\}gforge$ ; then
	$p/bin/schema2ldif.pl < $p/gforge.schema | ldapadd -H ldapi:/// -Y EXTERNAL -Q
    fi
fi

c=$(forge_get_config config_path)/config.ini.d/sysauthldap-secrets.ini
system_user=$(forge_get_config system_user)
if ! [ -e "$c" ] ; then
    touch $c
    chmod 600 $c
    chown $system_user $c
    echo [sysauthldap] >> $c
    echo ldap_password = CHANGEME >> $c
fi
