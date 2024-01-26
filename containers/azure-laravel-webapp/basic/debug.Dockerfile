ARG BASIC_IMAGE

FROM ${BASIC_IMAGE}
# This image is for debugging applications that only use the base image. It only adds
# xdebug as an extra php extension.
ENV LARAVEL_WEBAPP_CONTAINER_VARIANT="${LARAVEL_WEBAPP_CONTAINER_VARIANT}-debug"

# ==== INSTALL XDEBUG ==== #
RUN install-php-extensions xdebug
