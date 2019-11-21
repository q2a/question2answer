# Deployment

## The Docker image
Inside the `docker` folder you'll find a Dockerfile based on php-fpm that
will download and store the question2answer source code. The q2a version can
be modified by passing the `Q2A_VERSION` argument at build time:

    cd docker/
    docker build . --build-arg Q2A_VERSION=1.7.5 --tag q2a


### Environment variables
There are several environment variables you need to set in order to deploy the
Docker image. Also there are others you can use in your scripts that are
declared on runtime or inherited from buildtime.

#### Required variables
You must declare the following variables to run the image:
- **QA_DB_HOSTNAME**: MySQL hostname.
- **QA_DB_USER**: MySQL username for q2a.
- **QA_DB_PASS**: MySQL q2a user password.
- **QA_DB_NAME**: MySQL database for q2a.

#### Optional variables
- **ROOT**: This is the root directory where the application files (not only
  the q2a ones) will be stored. This directory should be shared with NGINX or
  any software used to serve the site files.
- **Q2A_DIR_NAME**: This is the name that will be used for the q2a application
  folder located at `$ROOT`. The source files will be copied here in order to be
  served.

#### Runtime variables
- **Q2A_DIR**: This is the absolute path to the q2a source code. It is defined
  as `Q2A_DIR=${ROOT}/${Q2A_DIR_NAME}`.


### Initializing a fresh instance
This container support initialization scripts which may be mounted as `.sh`
files with execution permissions at:

    /docker-entrypoint-init.d/

these scripts could be used to install plugins, themes or configure the running
instance. Look at the provisioning [README.rst](provisioning/q2a/README.rst)
for an example of one script.


## The "provisioning" directories
At the root of the `deployment` folder you will find a `provisioning` directory
that is shared by symbolink links between the docker and helm directories. The
files in this folder will be mount in the containers initialization path to
provision them when the containers start. For more information look at the
`README.rst` files inside the provisioning folders.


## Docker stack
The `docker` folder contains a compose file which enables the possibility to
deploy an instance of question2answers using docker stack.

### Setting up a stack
Modify the `stack.yaml` with your own values. Set the q2a and mysql environment
variables with your own username, password and database name. Look for the
`__change_me__` hints to find easily what must be replaced. You can also tweak
any other attribute if you want, as the image versions or network ports.

To run an instance `docker stack` must be configured in your computer.

Deploy the stack using:

    cd docker/
    docker stack deploy -c stack.yaml q2a

this will run a q2a, mysql, and nginx containers that serve q2a on the url:

    http://localhost:8000

If you have any trouble accessing it, you can also look to the stack status and
services logs in order to figure out what is going on:

    docker stack ps q2a  # use the --no-trunc option for more information
    docker services q2a_<service> logs  # change the service for q2a, mysql or nginx


## Helm

There is a helm chart to deploy question2answers in kubernetes. For this, a
public image of the Dockerfile is needed but for now, there is none published;
so you may have to publish your own until an official one is released.

The chart provides a way to deploy a q2a instance, among with a MySQL database
and a NGINX reverse proxy to serve the site files.

Before deploying your instance of q2a, edit the `values.yaml` file at the helm
directory for your needs, and set your own values for the database name, user
and passwords. Look for the `__change_me__` hints to find easily what must be
replaced. There is also an optional ingress that can be activated for
http or https.

To deploy an instance of the q2a chart, run:

    cd helm
    helm install . -f values.yaml -n <release_name> --namespace <namespace>
