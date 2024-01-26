################################################################################################
# BASIC MAIN IMAGE                                                                             #
################################################################################################

FROM php:8.3-fpm-alpine3.17
# This is the base container containing the minimal extensions needed to run a laravel
# application in Azure. It only installs the `install-php-extensions` script and the
# php extensions commonly used in the Shared-stack.
ENV LARAVEL_WEBAPP_CONTAINER_VARIANT='basic'

# ==== ENVIRONMENT SETUP ==== #
# The workspace root is the main path from which all commands will be executed.
ENV WORKSPACE_ROOT=/var/www
# The project root is the root of the laravel application.
ENV PROJECT_ROOT=${WORKSPACE_ROOT}
# The next environment variabeles make it easier to run composer binaries.
ENV PATH="/composer/vendor/bin:$PROJECT_ROOT:$WORKSPACE_ROOT/vendor/bin:$PATH" \
    COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_VENDOR_DIR="$WORKSPACE_ROOT/vendor" \
    COMPOSER_HOME=/composer
# We will set current working directory to the default workspace root.
WORKDIR /var/www

# ==== INSTALL PHP EXTENSIONS ==== #
# First, we install the `install-php-extensions` script, which makes it much easier to
# install php extensions in the container.
ADD --chmod=775 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

# Then, we use this script to install the php extensions that we need for most projects
# in the Shared Procode stack. You may not need every extension for each project, but
# it is just convenient to have them ready to use.
#
# Note that we do not install xdebug here, as this package is quite big and unsafe for
# production. We do this in a later stage for the Development-only container.
RUN install-php-extensions \
        opcache \
        ds \
        gd \
        pcntl \
        bcmath \
        intl \
        zip \
        exif \
        msgpack \
        mcrypt \
        igbinary \
        pdo_pgsql \
        pgsql \
        redis \
        lz4 \
        ffi \
        protobuf \
        grpc \
        opentelemetry \
        parle \
        xlswriter \
        csv \
        @composer

# ==== ADD STARTUP SCRIPTS ==== #
# The script to derive environment variables
COPY --from=common startup/derive-env-variables.sh /usr/local/startup/derive-env-variables.sh

# The script to generate the php.ini and php-fpm.ini configuration files from the environment variables.
COPY --from=common startup/generate-php-configuration.sh /usr/local/startup/generate-php-configuration.sh
# Add this startup script to the `generate-configuration.sh` file so that it will be called by the
# entrypoints.
RUN echo "/bin/sh /usr/local/startup/generate-php-configuration.sh" >> /usr/local/startup/generate-configuration.sh

# The script to show the startup summary.
COPY --from=common startup/finish-startup.sh /usr/local/startup/finish-startup.sh
# The script to run startup runtime optimisations (like caching the config and routes).
COPY --from=common startup/run-startup-optimisations.sh /usr/local/startup/run-startup-optimisations.sh

# ==== ADD ENTRYPOINTS ==== #
# Add the entrypoint for normal execution.
COPY --from=common --chmod=775 bin/docker-php-entrypoint /usr/local/bin/docker-php-entrypoint
# Add the entrypoint for local development.
COPY --from=common --chmod=775 bin/docker-php-entrypoint-dev /usr/local/bin/docker-php-entrypoint-dev

# ==== ADD COMMANDS ==== #
# Command for testing the application in the container.
COPY --from=common --chmod=775 bin/test-app      /usr/local/bin/test-app
# Command for migrating the database.
COPY --from=common --chmod=775 bin/migrate       /usr/local/bin/migrate
# Command for starting horizon.
COPY --from=common --chmod=775 bin/horizon       /usr/local/bin/horizon
# Command for starting php-fpm.
COPY --from=common --chmod=775 bin/start-php-fpm /usr/local/bin/start-php-fpm

# ==== CONVENIENCES AND NICITIES ==== #
# Add a nice welcome message.
COPY --from=common welcome.txt /etc/motd

# ==== CONTAINER CONFIGURATION ==== #
# Use the production entrypoint as the default entrypoint.
ENTRYPOINT ["docker-php-entrypoint"]
# Set start-php-fpm as the default command.
CMD ["start-php-fpm"]
