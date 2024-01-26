ARG READY2GO_IMAGE

################################################################################################
# READY2GO DEBUG IMAGE                                                                         #
################################################################################################

FROM ${READY2GO_IMAGE}
# Adds xdebug to the ready2go image.
ENV LARAVEL_WEBAPP_CONTAINER_VARIANT='${LARAVEL_WEBAPP_CONTAINER_VARIANT}-debug'

# ==== INSTALL XDEBUG ==== #
RUN install-php-extensions xdebug

# ==== CONTAINER CONFIGURATION ==== #
# Expose http, ssh, php-fpm statuses and supervisorctl.
EXPOSE 8080 2222 9000 9001
# Set the dev entrypoint as the default entrypoint.
ENTRYPOINT ["docker-php-entrypoint-dev"]
# Set start-supervisor as the default command.
CMD ["start-supervisor"]
