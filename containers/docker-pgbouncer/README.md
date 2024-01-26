# Dockerized Instance of PgBouncer

A minimal PgBouncer image. 

Heavily inspired by [edoburu/docker-pgbouncer](https://github.com/edoburu/docker-pgbouncer).
The main things that changed for this container are:

 - A seperate install script so that it can easily be reused to install PgBouncer in
   existing container.
 - A separate `generate-pgbouncer-configuration.sh` script that can be reused.
 - Prefixes the configuration environment variables with `PGBOUNCER_` so that they
   won't clash with environment variables in another container.
   
## TODO
 - Also design a debian-based image.
