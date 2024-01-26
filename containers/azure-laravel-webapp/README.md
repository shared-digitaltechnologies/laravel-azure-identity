# Azure Laravel WebApp Container

This package contains the source code for the base images to host Laravel WebApps in Azure.

## Variants

### Basic (`basic`)

The basic container builds apon the official `php:8.3-fpm-alpine3.17` container. It contains
the minimal setup to run a laravel application in Azure and installs the php extensions
that are commonly used in the Shared ProCode stack. It also adds some helper scripts to
make it easier to work with the container.

#### The `install-php-extensions` script.

Firstly, it installs the [install-php-extensions](https://github.com/mlocati/docker-php-extension-installer)
script, which makes it much easier to install php extensions in the docker container without
bloating the container with c/c++ compilers and build tools.

You can use this script in any of your docker containers to install additional php extensions 
by simply adding the follow command to the docker-file:

``` dockerfile
RUN install-php-extensions {extensionA} {extensionB} ...
```

#### Pre-installed php extensions.

Using the `install-php-extensions`in the Shared Procode stack. You may not need every extension for each project, but
it is just convenient to have them ready to use.

The packages we install enable us to:
 - Run php efficiently (`opcache`, `ds`)
 - Run laravel applications. (`gd`, `pcntl`, `bcmath`, `intl`, `zip`, `exif`, `msgpack`, `mcrypt`, `igbinary`)
 - Connect to PostgreSQL Databases (`do_pgsql`, `pgsql`)
 - Connect to Redis Databases (`redis`)
 - Use OpenTelemetry to collect telemetry data. (`lz4`, `ffi`, `protobuf`, `grpc`, `opentelemetry`)
 - Handle/parse files from within php (`parle`, `xlswriter`, `csv`)
 - Install dependencies with `composer`. (`@composer`)

Note that we do not install xdebug here, as this package is quite big and unsafe for
production. We do this in a later stage for the Development-only container.

### Basic Debug (`base-debug`)

This image extends `basic` by also pre-installing the `xdebug` extension.

### Ready To Go (`ready2go`)

These images extends `basic` so that it is ready to run in
[Azure WebApps](https://azure.microsoft.com/nl-nl/products/app-service/web) without any
other supporting containers. It does this by installing the folling services:

 - `nginx`: For passing http requests to `php-fpm`.
 - `sshd`: To make it a bit more convenient to troubleshoot production-only problems.
 - `bash`: So that the terminal works a bit more intuitive when logging in at the container.
 - `supervisor`: To ensure that `php-fpm`, `nginx` and `sshd` keep running together.

Because `supervisor` is available, you can choose to run additional Laravel background 
processes (like `worker`, `horizon` or `schedule`) and let them be managed by `supervisor`.

Note that this image is still minimal in the sense that it does not provide any services
for telemetry or efficient database access. Use this image if you don't need these
features or if you want to provide these features using external containers.

### All In One (`allin1`)

This image extends `ready2go` so that it does not need any additional containers to
fully integrate with the webapps infastructure. It makes the following services available
from inside the container:

 - `pgbouncer`: To manage a connection pool to the postgres database.
 - `otelcol-collector`: To collect telemetry data from the application and send it to
    application insights.

## Additional/custom Scripts

### Entrypoints

### Startup scripts

### Commands

These images define the following commands.

- `start-php-fpm`: Calls the `run-startup-optimisations.sh` script and starts `php-fpm` only.
- `start-supervisor`: Calls the `run-startup-optimisations.sh` script and starts `supervisor`.
- `horizon`: Starts running horizon.
- `migrate`: Runs the database migrations (using `php artisan migrate`).
- `test-app`: Runs the tests (using `php artisan test`).

## Configuration

### Startup scripts

You can put custom startup scripts in the `/etc/startup.d/` or `/etc/startup-dev.d` directory.
These scripts must have the `*.sh*` extension. The container will then source those scripts
in its default entry points:

 - Both `docker-php-entrypoint` and `docker-php-entrypoint-dev` will source all scripts in
   `/etc/startup.d`. These scripts are executed after it derived the evironment variables, but
   before it generates the configuration files. Therefore, you can overwrite those environment
   variables in these startup script.
   
 - Only `docker-php-entrypoint-dev` will source all scripts in the `/etc/startup-dev.d` after
   it installed its composer dependencies.

### Environment variables

 - `PROJECT_ROOT`: The root of the laravel application to execute.
   
   **Default value:** `/var/www`

 - `SUPERVISOR_CONFIG_FILE`: Path to the `supervisord.conf` file used at the startup of
   the `ready2go` and `allin1` images. 
   
   **Default value:** `/etc/supervisord.conf`
