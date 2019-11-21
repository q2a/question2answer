#!/usr/bin/env bash

set -o errexit
set -o nounset

# Environment variables explanation:

# ROOT: This is the root directory where the application files (not only the
# q2a ones) will be stored. This directory should be shared with NGINX or any
# software used to serve the site files.

# Q2A_DIR_NAME: This is the name that will be used for the q2a application
# folder located at $ROOT. The source files will be copied here in order to
# be served.

# Q2A_DIR: This is the absolute path to the folder were q2a will be served.

ROOT=${ROOT:-/var/www/html/}
Q2A_DIR_NAME=${Q2A_DIR_NAME:-q2a}
export Q2A_DIR=${ROOT}${Q2A_DIR_NAME}

# The SRC_DIR variable is set at the Dockerfile, and contains the location
# where the source code was downloaded to the image at build time. This source
# code will be copied to the Q2A_DIR which is the folder from where the
# application will be served.
# The copy is required to support a shared folder with the site data between
# this container and another application (i.e. NGINX) that serves the files.
# The SRC_DIR is not used for sharing purposes because some container platforms
# (as kubernetes), don't keep the files of a directory when a shared volume is
# mount at the same path.
SRC_DIR=${SRC_DIR:-"/opt/q2a/src"}
echo "Populating Q2A directory"
mkdir -p ${Q2A_DIR}
cp -r ${SRC_DIR}/* ${Q2A_DIR}

# Set the config file if not present
Q2A_CONFIG=qa-config.php
if [ ! -f "$Q2A_CONFIG" ]; then
    echo "Configuring Q2A"
    pushd ${Q2A_DIR}
    mv qa-config-example.php ${Q2A_CONFIG}
    sed -i -e "s/127.0.0.1/${QA_DB_HOSTNAME}/g" ${Q2A_CONFIG}
    sed -i -e "s/your-mysql-username/${QA_DB_USER}/g" ${Q2A_CONFIG}
    sed -i -e "s/your-mysql-password/${QA_DB_PASS}/g" ${Q2A_CONFIG}
    sed -i -e "s/your-mysql-db-name/${QA_DB_NAME}/g"  ${Q2A_CONFIG}
    popd
fi

# Run initialization scripts
echo "Running initialization scripts"
for f in /docker-entrypoint-init.d/*; do
    # Check if executable
    if [ ! -x "$f" ]; then
        continue
    fi
    case "$f" in
        *.sh) echo "$0: running $f"; . "$f" ;;
        *)    echo "$0: ignoring $f" ;;
    esac
    echo
done

# Run php-fpm
php-fpm &
pid=$!
echo "php-fpm PID: ${pid}."

# Cleaning
unset SRC_DIR
unset QA_DB_HOSTNAME
unset QA_DB_USER
unset QA_DB_PASS
unset QA_DB_NAME
unset ROOT
unset Q2A_DIR_NAME

history -c
history -w

# Run any user command

if [ ! -z ${@+x} ]; then
    echo "Running user command : $@"
    exec "$@"
else
    # Wait the service
    echo 'Waiting until php-fpm termination.'
    wait $pid
fi
