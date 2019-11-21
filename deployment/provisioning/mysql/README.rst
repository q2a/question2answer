Provisioning scripts
====================

These scripts are mounted to /docker-entrypoint-initdb.d and are run by the
MySQL entrypoint as described in:

    https://hub.docker.com/_/mysql section "Initializing a fresh instance"

And implemented here:

    https://github.com/mysql/mysql-docker/blob/mysql-server/5.7/docker-entrypoint.sh#L149

Use the .sh script to run commands that use environment variables and .sql to
run MySQL commnads.
