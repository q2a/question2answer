Provisioning scripts
====================

These scripts are mounted to `/docker-entrypoint-init.d` and are run by the
Q2A entrypoint. These files must be:

- scripts with the .sh extension
- executables

Use the scripts to install plugins, themes or configure your system. For example:

.. code-block:: bash

    #!/usr/bin/env bash

    set -o errexit
    set -o nounset

    pushd /tmp

    ###################
    # Install plugins #
    ###################

    echo "Installing plugins..."

    # Markdown Editor
    # ---------------

    PLUGIN_NAME=q2a-markdown-editor
    PLUGIN_VERSION=2.6.0
    PLUGIN_FILE_NAME=${PLUGIN_NAME}-${PLUGIN_VERSION}

    curl -L "https://github.com/svivian/q2a-markdown-editor/archive/v${PLUGIN_VERSION}.zip" --output ${PLUGIN_FILE_NAME}.zip
    unzip ${PLUGIN_FILE_NAME}.zip
    rm ${PLUGIN_FILE_NAME}.zip
    cp -r ${PLUGIN_FILE_NAME} ${Q2A_DIR}/qa-plugin/${PLUGIN_NAME}
    rm -rf ${PLUGIN_FILE_NAME}

    echo "Done."

    ##################
    # Install themes #
    ##################

    echo "Installing themes..."

    # Donut
    DONUT_TAG=2.0.2
    DONUT_FILE_NAME=Donut-${DONUT_TAG}
    curl -L "https://github.com/amiyasahu/Donut/archive/2.0.2.zip" --output ${DONUT_FILE_NAME}.zip
    unzip ${DONUT_FILE_NAME}.zip
    rm ${DONUT_FILE_NAME}.zip

    cp -r ${DONUT_FILE_NAME}/qa-plugin/Donut-admin ${Q2A_DIR}/qa-plugin/Donut
    cp -r ${DONUT_FILE_NAME}/qa-theme/Donut-theme ${Q2A_DIR}/qa-theme/Donut
    rm -rf ${DONUT_FILE_NAME}

    echo "Done."

    popd

Environment variables
---------------------
Basically any environment variable declared for the container is available to
be used by the scripts. Look at the `Environment variables` section of the
deployment `README.md <../../README.md>`_ for more information.
