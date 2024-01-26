################################################################################################
# READY2GO IMAGE                                                                               #
################################################################################################
ARG BASIC_IMAGE

FROM ${BASIC_IMAGE}
# This is an image that can be deployed immediately to Azure WebApps.
ENV LARAVEL_WEBAPP_CONTAINER_VARIANT='ready2go'

# ==== INSTALL SYSTEM SERVICES ==== #
RUN apk update && apk add --no-cache --upgrade busybox && apk add --no-cache \
    dos2unix \
    supervisor \
    nginx \
    bash \
    gettext \
    openssh \
    curl

# ==== SSH SETUP ==== #
# Copy the ssh configuration
COPY --from=common sshd_config /etc/ssh/sshd_config
# Set the ssh password and generate the ssh keys.
RUN echo "root:Docker!" | chpasswd \
    && cd /etc/ssh/ \
    && ssh-keygen -A

# ==== NGINX SETUP ==== #
# This ensures that NGINX outputs to the log files.
RUN ln -sf /dev/stdout /var/log/nginx/access.log \
    && ln -sf /dev/stderr /var/log/nginx/error.log

# Ensure that nginx can write to temporary files in case of an buffer overflow.
RUN chmod +x /var/lib/nginx/ -R

# Copy nginx configuration
COPY --from=common etc/nginx/default.conf /etc/nginx/http.d/default.conf.template
COPY --from=common etc/nginx/php-fpm.conf /etc/nginx/http.d/php-fpm.conf


# ==== SUPERVISOR SETUP ==== #
# Copy supervisor configuration
COPY --from=common etc/supervisord.conf /etc/supervisord.conf

# ==== ADD STARTUP SCRIPTS ==== #
# The script to generate the nginx configuration from environment variables
COPY --from=common startup/generate-nginx-configuration.sh /usr/local/startup/generate-nginx-configuration.sh
# Add this startup script to the `generate-configuration.sh` file so that it will be called by the
# entrypoints.
RUN echo "/bin/sh /usr/local/startup/generate-nginx-configuration.sh" >> /usr/local/startup/generate-configuration.sh

# ==== ADD COMMANDS ==== #
# Add command for starting supervisor
COPY --from=common --chmod=775 bin/start-php-fpm /usr/local/bin/start-supervisor

# ==== CONVENIENCES AND NICITIES ==== #
# Copy convenience configuration for the terminal.
COPY --from=common etc/profile.d/ /etc/profile.d/

# ==== CONTAINER CONFIGURATION ==== #
# Expose http and ssh
EXPOSE 8080 2222
# Set the default entrypoint
ENTRYPOINT ["docker-php-entrypoint"]
# Set start as the default command.
CMD ["start-supervisor"]
