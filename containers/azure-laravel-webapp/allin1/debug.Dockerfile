ARG ALLIN1_IMAGE

################################################################################################
# ALLIN1 DEBUG IMAGE                                                                           #
################################################################################################

FROM ${ALLIN1_IMAGE}
# The allin1 image, but with xdebug installed.
ENV LARAVEL_WEBAPP_CONTAINER_VARIANT="${LARAVEL_WEBAPP_CONTAINER_VARIANT}-debug"

# ==== INSTALL XDEBUG ==== #
RUN install-php-extensions xdebug

# ==== CONTAINER CONFIGURATION ==== #
# Expose http, ssh, supervisorctl and php-fpm statuses
EXPOSE 8080 2222 9000 9001
# Set the dev entrypoint as the default entrypoint.
ENTRYPOINT ["docker-php-entrypoint-dev"]
# Set start as the default command.
CMD ["start-supervisor"]
