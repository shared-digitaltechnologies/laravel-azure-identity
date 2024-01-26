#!/bin/sh

>&2 echo "[STARTUP-NGINX] Configure NGINX"

# shellcheck disable=SC2016
DefaultNginxLogFormat='[NGINX] $remote_addr - $remote_user [$time_local]  $status "$request" $body_bytes_sent "$http_referer" "$http_user_agent" "$http_x_forwarded_for"'

# Helper function to convert an input boolean variable to 'on'/'off'.
echoSwitch() {
  Var=${2:-none}
  if [ "$Var" = "on" ] || [ "$Var" = "true" ] || [ "$Var" = "1" ]; then
    echo "$1 on;"
  elif [ "$Var" = "off" ] || [ "$Var" = "false" ] || [ "$Var" = "0" ]; then
    echo "$1 off;"
  fi
}

######################################################################################
# ENTRYPOINT CONFIGURATION                                                           #
######################################################################################

# Generate the main entrypoint configuration for nginx.
(
  echo "user www-data;"
  echo "worker_processes auto;"

  echo "error_log /var/log/nginx/error.log ${NGINX_LOG_LEVEL:-notice};"

  echo "pid /var/run/nginx/pid;"

  echo 'include /etc/nginx/conf.d/*.conf;'

  eventsConf=$(
    printf 'worker_connections %d;' "${NGINX_WORKER_CONNECTIONS:-1204}"
  )

  printf '\nevents {\n%s\n}\n\n' "${eventsConf}"

  httpConf=$(
    echo "include /etc/nginx/mime.types;"
    echo "include /etc/nginx/proxy.conf;"
    echo "default_type application/octet-stream;"

    echo "log_format main '${NGINX_ACCESS_LOG_FORMAT:-$DefaultNginxLogFormat}';"
    echo "access_log /var/log/nginx/access.log main;"

    echoSwitch "sendfile" "${NGINX_HTTP_SENDFILE:-on}";
    echoSwitch "tcp_nopush" "${NGINX_HTTP_TCP_NOPUSH:-on}";
    echoSwitch "gzip" "${NGINX_HTTP_GZIP}";

    printf 'keepalive_timeout %d;\n' "${NGINX_HTTP_KEEPALIVE_TIMEOUT:-65}"

    echo 'include /etc/nginx/http.d/*.conf;'
  )

  printf '\nhttp {\n%s\n}\n\n' "${httpConf}"

) > /etc/nginx/nginx.conf

>&2 echo "[STARTUP-NGINX] ✔ Wrote main configuration to '/etc/nginx/nginx.conf'"

######################################################################################
# NGINX PROXY CONFIGURATION                                                          #
######################################################################################

# Generate the proxy settings
(
  echo 'proxy_set_header Host $host;'

  echo "client_max_body_size ${NGINX_CLIENT_MAX_BODY_SIZE:-1m};"

  if [ -n "$NGINX_CLIENT_BODY_BUFFER_SIZE" ]; then
    echo "client_body_buffer_size ${NGINX_CLIENT_BODY_BUFFER_SIZE};"
  fi

  echoSwitch "client_body_in_file_only" "${NGINX_CLIENT_BODY_IN_FILE_ONLY:-off}"
  echoSwitch "client_body_in_single_buffer" "${NGINX_CLIENT_BODY_IN_SINGLE_BUFFER:-off}"
  echo "client_body_timeout ${NGINX_CLIENT_BODY_TIMEOUT:-60s};"
  echo "client_header_buffer_size ${NGINX_CLIENT_HEADER_BUFFER_SIZE:-1k};"
  echo "client_header_timeout ${NGINX_CLIENT_HEADER_TIMEOUT:-60s};"

) > /etc/nginx/proxy.conf

>&2 echo "[STARTUP-NGINX] ✔ Wrote proxy configuration to '/etc/nginx/proxy.conf'"

######################################################################################
# DEFAULT SERVER CONFIGURATION                                                       #
######################################################################################

# Substitute the needed environment variables in the nginx configuration of the default server.
envsubst '$PROJECT_ROOT,$SERVER_NAME' < /etc/nginx/http.d/default.conf.template > /etc/nginx/http.d/default.conf
>&2 echo "[STARTUP-NGINX] ✔ Wrote default server configuration to '/etc/nginx/http.d/default.conf'"
