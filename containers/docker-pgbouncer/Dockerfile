ARG BASE_IMAGE

FROM ${BASE_IMAGE}
ARG PGBOUNCER_VERSION=1.21.0

COPY --chmod=775 ./bin/install-pgbouncer /usr/bin/install-pgbouncer

RUN install-pgbouncer $PGBOUNCER_VERSION

COPY --from=common ./generate-pgbouncer-configuration.sh /usr/local/startup/generate-pgbouncer-configuration.sh
COPY --from=common --chmod=775 ./entrypoint /entrypoint

USER postgres

EXPOSE 5432
ENTRYPOINT ["/entrypoint"]
CMD ["/usr/bin/pgbouncer", "/etc/pgbouncer/pgbouncer.ini"]
