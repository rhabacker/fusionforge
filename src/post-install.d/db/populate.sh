#!/bin/bash -e
# Create user and database, and import initial data

database_host=$(forge_get_config database_host)
database_port=$(forge_get_config database_port)
database_name=$(forge_get_config database_name)
database_user=$(forge_get_config database_user)
database_password=$(forge_get_config database_password)
source_path=$(forge_get_config source_path)

if [ -z "$database_name" ]; then
    echo "Cannot get database_name"
    exit 1
fi

# Create database
if ! su - postgres -c 'psql -At -l' | grep "^$database_name|" >/dev/null; then
    su - postgres -c "createdb --template template0 --encoding UNICODE $database_name"
    if ! su - postgres -c "createlang -l $database_name" | grep -q plpgsql; then
	su - postgres -c "createlang plpgsql $database_name"
    fi
fi

# Create DB user
if ! su - postgres -c 'psql -At -c \\du' | grep "^$database_user|" >/dev/null; then
    su - postgres -c "createuser -SDR $database_user"
fi
database_password_quoted=$(echo $database_password | sed -e "s/'/''/")
su - postgres -c psql <<EOF
ALTER ROLE $database_user WITH PASSWORD '$database_password_quoted';
GRANT CREATE ON DATABASE $database_name TO $database_user;  -- for wiki schemas
EOF
if ! su - postgres -c 'psql -At -c \\du' | grep "^${database_user}_nss|" >/dev/null; then
    su - postgres -c "createuser -SDR ${database_user}_nss"
fi

export PGPASSFILE=$(mktemp)
cat <<EOF > $PGPASSFILE
$database_host:$database_port:$database_name:$database_user:$database_password
EOF

# Database init
if ! su - postgres -c "psql $database_name -c 'SELECT COUNT(*) FROM users;'" > /dev/null;  then
    psql -h $database_host -p $database_port -U $database_user $database_name < $source_path/db/1-fusionforge-init.sql
fi

# Database upgrade
$source_path/post-install.d/db/upgrade.php

# Additional grants
psql -h $database_host -p $database_port -U $database_user $database_name <<EOF
GRANT SELECT ON nss_passwd TO ${database_user}_nss;
GRANT SELECT ON nss_groups TO ${database_user}_nss;
GRANT SELECT ON nss_usergroups TO ${database_user}_nss;
EOF

# Admin user
req="SELECT COUNT(*) FROM users WHERE user_name='admin'"
if [ "$(echo $req | su - postgres -c "psql -At $database_name")" != "1" ]; then
psql -h $database_host -p $database_port -U $database_user $database_name <<EOF
INSERT INTO users (user_name, realname, firstname, lastname, email,
    user_pw, unix_pw, status, theme_id)
  VALUES ('admin', 'Forge Admin', 'Forge', 'Admin', 'root@localhost.localdomain',
    'INVALID', 'INVALID', 'A', (SELECT theme_id FROM themes WHERE dirname='funky'));
EOF
forge_make_admin admin  # set permissions
fi

rm -f $PGPASSFILE
unset PGPASSFILE
# Note: no password defined yet
