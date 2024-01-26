ARG READY2GO_IMAGE
ARG AZURE_OTELCOL_AGENT_IMAGE
ARG DOCKER_PGBOUNCER_IMAGE

FROM ${AZURE_OTELCOL_AGENT_IMAGE} AS otelcol
FROM ${DOCKER_PGBOUNCER_IMAGE} AS pgbouncer

################################################################################################
# ALLIN1 IMAGE                                                                                 #
################################################################################################

FROM ${READY2GO_IMAGE}
# This image adds every service needed to nicely integrate Laravel with Azure WebServices.
# It's cool for small applications, but can be a bit opinionated for more complex applications
# that need to be fast.
ENV LARAVEL_WEBAPP_CONTAINER_VARIANT='allin1'

ARG PGBOUNCER_VERSION=1.21.0

# ==== INSTALL PGBOUNCER ==== #
# Sadly, we have to rerun the install-pgbouncer script as pgbouncer is a dynamically linked
# application.

COPY --from=pgbouncer /usr/bin/install-pgbouncer /usr/bin/install-pgbouncer
COPY --from=pgbouncer /usr/local/startup/generate-pgbouncer-configuration.sh /usr/local/startup/generate-pgbouncer-configuration.sh

RUN install-pgbouncer $PGBOUNCER_VERSION

RUN echo "/bin/sh /usr/local/startup/generate-pgbouncer-configuration.sh" >> /usr/local/startup/generate-configuration.sh

# ==== INSTALL OPEN TELEMETRY COLLECTOR ==== #
# Copy the otelcol binary form the otelcol_builder stage.
COPY --from=otelcol /usr/local/src/otelcol/ /usr/local/src/otelcol/
# Copy the default otelcol configuration.
COPY --from=common etc/otelcol-contrib/ /etc/otelcol-contrib/
