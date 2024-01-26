#!/bin/sh
set -e

# Write the PHP-FPM configuration.
echo >&2 "[STARTUP-PHP] Generate PHP-FPM configuration"
(
    echo 'listen = /var/run/php-fpm.sock;'
    echo 'listen.owner = www-data;'
    echo 'listen.group = www-data;'
    echo "pm.max_children = ${PHP_FPM_PM_MAX_CHILDREN:-20};"
    echo "pm.start_servers = ${PHP_FPM_PM_START_SERVERS:-2};"
    echo "pm.min_spare_servers = ${PHP_FPM_PM_MIN_SPARE_SERVERS:-1};"
    echo "pm.max_spare_servers = ${PHP_FPM_PM_MAX_SPARE_SERVERS:-3};"
    echo "pm.status_path = /status;"
    echo "pm.status_listen = /var/run/php-fpm-status.sock;"
    echo "clear_env = no;"

    echo "env[OTEL_PHP_AUTOLOAD_ENABLED] = \"true\";"
    echo "env[OTEL_SERVICE_NAME] = \"${SERVICE_NAME}\";"
    echo "env[OTEL_SERVICE_NAMESPACE] = \"${SERVICE_NAMESPACE}\";"
    echo "env[OTEL_INSTANCE_ID] = \"${INSTANCE_ID}\";"
    echo "env[OTEL_PROPAGATORS] = \"${OTEL_PROPAGATORS:-baggage,tracecontext}\""
    if [ "true" = "${OTELCOL_ENABLED}" ]; then
        echo "env[OTEL_SDK_DISABLED] = \"false\";"
        echo "env[OTEL_TRACES_EXPORTER] = \"otlp\";"
        echo "env[OTEL_LOGS_EXPORTER] = \"otlp\";"
        echo "env[OTEL_METRICS_EXPORTER] = \"otlp\";"
        echo "env[OTEL_TRACES_SAMPLER] = \"${OTEL_TRACES_SAMPLER:-parentbased_always_on}\";"
        echo "env[OTEL_EXPORTER_OTLP_PROTOCOL] = \"grpc\""
        echo "env[OTEL_EXPORTER_OTLP_ENDPOINT] = \"http://localhost:4317\";"
    else
        echo "env[OTEL_SDK_DISABLED] = \"true\";"
        echo "env[OTEL_TRACES_EXPORTER] = \"none\";"
        echo "env[OTEL_LOGS_EXPORTER] = \"none\";"
        echo "env[OTEL_METRICS_EXPORTER] = \"none\";"
        echo "env[OTEL_TRACES_SAMPLER] = \"always_off\";"
    fi
) >/usr/local/etc/php-fpm.d/zzz-app.conf
echo >&2 "[STARTUP-PHP] ✔ Wrote PHP-configuration to '/usr/local/etc/php-fpm.d/zzz-app.conf'"



# Write the memory-limit php configuration
echo >&2 "[STARTUP-PHP] Generate PHP configuration"
(
    echo "memory_limit=${PHP_MEMORY_LIMIT:-128M}"
    echo "max_execution_time=${REQUEST_MAX_EXECUTION_TIME:-30}"

    echo "xdebug.mode=${XDEBUG_MODE:-off}"
    echo "xdebug.cli_color=${XDEBUG_CLI_COLOR:-0}"
    echo "xdebug.client_discovery_header=\"${XDEBUG_CLIENT_DISCOVERY_HEADER:-HTTP_X_FORWARDED_FOR,REMOTE_ADDR}\""
    echo "xdebug.client_host=${XDEBUG_CLIENT_HOST:-host.docker.internal}"
    echo "xdebug.client_port=${XDEBUG_CLIENT_PORT:-9003}"
    echo "${XDEBUG_CLOUD_ID:+"xdebug.cloud_id=${XDEBUG_CLOUD_ID}"}"
    echo "${XDEBUG_IDEKEY:+"xdebug.idekey=${XDEBUG_IDEKEY}"}"
    echo "xdebug.discover_client_host=${XDEBUG_DISCOVER_CLIENT_HOST:-false}"
    echo "xdebug.file_link_format='${XDEBUG_FILE_LINK_FORMAT:-javascript: var r = new XMLHttpRequest; r.open("get", "http://localhost:63342/api/file/%f:%l");r.send())}'"
    echo "xdebug.log=${XDEBUG_LOG}"
    echo "xdebug.log_level=${XDEBUG_LOG_LEVEL:-7}"
    echo "xdebug.output_dir=${XDEBUG_OUTPUT_DIR:-/tmp}"
    echo "xdebug.profiler_output_name=${XDEBUG_PROFILER_OUTPUT_NAME:-cachegrind.out.%p}"
    echo "xdebug.start_with_request=${XDEBUG_START_WITH_REQUEST:-default}"
    echo "xdebug.trace_format=${XDEBUG_TRACE_FORMAT:-0}"
    echo "xdebug.trace_output_name=${XDEBUG_TRACE_OUTPUT_NAME:-trace.%c}"
    echo "xdebug.trigger_value=${XDEBUG_TRIGGER_VALUE}"
    echo "xdebug.use_compression=${XDEBUG_USE_COMPRESSION:-true}"
) >/usr/local/etc/php/conf.d/docker-php-resolved.ini

echo >&2 "[STARTUP-PHP] ✔ Wrote generated PHP-configuration to '/usr/local/etc/docker-php-resolved.ini'"
