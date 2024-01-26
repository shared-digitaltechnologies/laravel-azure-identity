#!/usr/bin/env sh

>&2 echo "[STARTUP] Deriving environment variables..."

##########################################################################################################
# SET ENV-VARIABLE DEFAULTS                                                                              #
##########################################################################################################

# We need the following environment variables in the startup-script. The will only change in very uncommon
# debug cases, so we will set there default values here, so we may omit them when running the container.
#
# The strange script below does the following:
#  1. Sets DEPLOYMENT_ENVIRONMENT and APP_ENV to each others value if one of them is missing.
#  2. Defaults DEPLOYMENT_ENVIRONMENT to 'local' and APP_ENV to 'production' of both are missing.
DEPLOYMENT_ENVIRONMENT=${DEPLOYMENT_ENVIRONMENT:-${APP_ENV:-}}
APP_ENV=${APP_ENV:-${DEPLOYMENT_ENVIRONMENT:-production}}
DEPLOYMENT_ENVIRONMENT=${DEPLOYMENT_ENVIRONMENT:-local}
export DEPLOYMENT_ENVIRONMENT
export APP_ENV


# Optimize the app by default.
STARTUP_OPTIMIZE_APP=${STARTUP_OPTIMIZE_APP:-'true'}
export STARTUP_OPTIMIZE_APP

# Enable PHP_FPM by default.
PHP_FPM_ENABLED=${PHP_FPM_ENABLED:-'true'}
export PHP_FPM_ENABLED

##########################################################################################################
# LAST RESORT FALLBACKS                                                                                  #
##########################################################################################################

# The APP_KEY should always be set to some static value! However, it is not always practical to do this
# (like in CI). Therefore, we generate a random APP_KEY here and give a clear warning the the app key
# was missing.
# If the HORIZON_ENABLED environment variable was not found, we assume that you want to use horizon
# if the QUEUE_CONNECTION is equal to 'redis'.
if command -v openssh &> /dev/null; then
    if [ -z "${APP_KEY}" ]; then
        echo "---------------------------------------------------------------------"
        echo "|!!!!!!!!!!!!  WARNING: APP_KEY env-variable missing!  !!!!!!!!!!!!!|"
        echo "---------------------------------------------------------------------"
        echo "|                                                                   |"
        echo "| You are running this Laravel Application Container without the    |"
        echo "|  'APP_KEY' env-variable set!                                      |"
        echo "|                                                                   |"
        echo "| A random APP_KEY will be generated, however:                      |"
        echo "|                                                                   |"
        echo "|                                                                   |"
        echo "|        THIS COULD CAUSE STRANGE BEHAVIOUR AFTER RESTARTS!         |"
        echo "|                                                                   |"
        echo "|                                                                   |"
        echo "| ( If you are running this container from CI or for running tests, |"
        echo "|   you can ignore this message...)                                 |"
        echo "|                                                                   |"
        echo "---------------------------------------------------------------------"

        export APP_KEY="${APP_KEY:-base64:$(openssl rand -base64 32)}"
    fi
fi

##########################################################################################################
# USED VALUES FROM WEBAPP                                                                                #
##########################################################################################################

# Here, we ensure that some values are set. Normally, they are automatically provided by the Azure WebApp.
# This ensures that we can still use these environment variables in containers that run in CI or
# Development environments.

WEBSITE_SITE_NAME=${WEBSITE_SITE_NAME:-"azure-laravel-webapp-${APP_ENV:-production}"}
export WEBSITE_SITE_NAME

export WEBSITE_HOSTNAME="${WEBSITE_HOSTNAME:-localhost}"
export WEBSITE_RESOURCE_GROUP="${WEBSITE_RESOURCE_GROUP:-dev_local}"
if command -v openssh &> /dev/null; then
    export WEBSITE_INSTANCE_ID="${WEBSITE_INSTANCE_ID:-$(openssl rand -hex 32)}"
fi

export DIAGNOSTIC_LOGS_MOUNT_PATH="${DIAGNOSTIC_LOGS_MOUNT_PATH:-/var/log/diagnosticLogs}"

export REGION_NAME="${REGION_NAME:-westeurope}"

export SERVICE_NAME="${SERVICE_NAME:-${WEBSITE_SITE_NAME}}"
export SERVICE_NAMESPACE="${SERVICE_NAMESPACE:-${WEBSITE_RESOURCE_GROUP}}"
export INSTANCE_ID="${INSTANCE_ID:-${WEBSITE_INSTANCE_ID}}"

##########################################################################################################
# DERIVE DIRECTORY LAYOUT VARIABLES                                                                      #
##########################################################################################################

# This container is designed to work nicely with projects with an nx-like folder structure. We need two
# environment variables to accomplish this, namely $WORKSPACE_ROOT (root of the repo) and $PROJECT_ROOT
# (root of the laravel project). If these variables are not set, we assume you are using a normal
# Laravel-project and set these environment variables accordingly.

# If the workspace root is not set, we just use the current working directory as the workspace root.
if [ -z "${WORKSPACE_ROOT}" ]; then
    WORKSPACE_ROOT="$(pwd)"
    export WORKSPACE_ROOT
fi

# If the project root is not set, we use the workspace root as the project root.
PROJECT_ROOT=${PROJECT_ROOT:-$WORKSPACE_ROOT}
export PROJECT_ROOT

##########################################################################################################
# DERIVE COMPOSER ENVIRONMENT VARIABLES                                                                  #
##########################################################################################################

# The composer vendor dir is in the workspace root in the case of an nx-monorepo.
COMPOSER_VENDOR_DIR=${COMPOSER_VENDOR_DIR:-"$WORKSPACE_ROOT/vendor"}
export COMPOSER_VENDOR_DIR

COMPOSER_HOME=${COMPOSER_HOME:-'/composer'}
export COMPOSER_HOME

##########################################################################################################
# DERIVE NGINX ENVIRONMENT VARIABLES                                                                     #
##########################################################################################################

# Enable SSH by default if it is available. Disable it otherwise.
if command -v /usr/sbin/nginx &> /dev/null; then
    export NGINX_ENABLED="${NGINX_ENABLED:-true}"
else
    export NGINX_ENABLED='false'
fi

##########################################################################################################
# DERIVE SSH ENVIRONMENT VARIABLES                                                                       #
##########################################################################################################

# Enable SSH by default if it is available. Disable it otherwise.
if command -v /usr/sbin/sshd &> /dev/null; then
    export SSHD_ENABLED="${SSHD_ENABLED:-true}"
else
    export SSHD_ENABLED='false'
fi

##########################################################################################################
# DERIVE OTELCOL CONFIG                                                                                  #
##########################################################################################################

# Enable Open Telemetry Collector by default if it is available. Disable it otherwise.
if command -v /usr/local/src/otelcol/otelcol &> /dev/null; then
    export OTELCOL_ENABLED=${OTELCOL_ENABLED:-'true'}
else
    export OTELCOL_ENABLED='false'
fi

# Use the default otelcol config.
export OTELCOL_CONFIG=${OTELCOL_CONFIG:-'file:/etc/otelcol-contrib/config.yaml'}

##########################################################################################################
# DERIVE HORIZON ENVIRONMENT VARIABLES                                                                   #
##########################################################################################################

# If the HORIZON_ENABLED environment variable was not found, we assume that you want to use horizon
# if the QUEUE_CONNECTION is equal to 'redis'.
if [ -z "${HORIZON_ENABLED}" ]; then
    if [ 'redis' = "${QUEUE_CONNECTION}" ]; then
        export HORIZON_ENABLED='true'
    else
        export HORIZON_ENABLED='false'
    fi
fi

##########################################################################################################
# DERIVE PGBOUNCER ENVIRONMENT VARIABLES                                                                 #
##########################################################################################################

# Determine if PGBouncer should be enabled
# We assume that you want to use PGBOUNCER if you set DB_CONNECTION to 'pgbouncer'.
if [ -z "${PGBOUNCER_ENABLED}" ]; then
    if [ 'pgbouncer' = "${DB_CONNECTION}" ]; then
        export PGBOUNCER_ENABLED='true'
    else
        export PGBOUNCER_ENABLED='false'
    fi
fi

##########################################################################################################
# DERIVE SERVER NAME                                                                                     #
##########################################################################################################

# If the servername was not set, we will try to set it to the value of WEBSITE_HOSTNAME, which is an
# environment variable that azure provides to all WebApps.
#
# If the WEBSITE_HOSTNAME was not set (for instance, when the container is not running as an Azure
# WebApp, but as a local development environment), we will just use '$hostname', which is the default
# value for the server name in Nginx.

if [ -z "${SERVER_NAME}" ]; then
    if [ -n "${WEBSITE_HOSTNAME}" ]; then
        export SERVER_NAME="$WEBSITE_HOSTNAME"
    else
        # Note that the single quotes here is no typo! We really want the string '$hostname', not
        # the value of the variable hostname.
        export SERVER_NAME='$hostname'
    fi
fi

##########################################################################################################
# RESOLVE SUPERVISOR CONFIGURATION                                                                       #
##########################################################################################################

# These environment variables configure the logging behaviour of the supervised processes.

export SUPERVISORD_LOGFILE="${SUPERVISORD_LOGFILE:-/dev/stdout}"
export SUPERVISORD_LOGFILE_MAXBYTES="${SUPERVISORD_LOGFILE_MAXBYTES:-0}"
export SUPERVISORD_LOGLEVEL="${SUPERVISORD_LOGLEVEL:-INFO}"

export PHP_FPM_STDOUT_LOGFILE="${PHP_FPM_STDOUT_LOGFILE:-/dev/stdout}"
export PHP_FPM_STDOUT_LOGFILE_MAXBYTES="${PHP_FPM_STDOUT_LOGFILE_MAXBYTES:-0}"
export PHP_FPM_STDERR_LOGFILE="${PHP_FPM_STDERR_LOGFILE:-/dev/stderr}"
export PHP_FPM_STDERR_LOGFILE_MAXBYTES="${PHP_FPM_STDERR_LOGFILE_MAXBYTES:-0}"

export NGINX_STDOUT_LOGFILE="${NGINX_STDOUT_LOGFILE:-/dev/stdout}"
export NGINX_STDOUT_LOGFILE_MAXBYTES="${NGINX_STDOUT_LOGFILE_MAXBYTES:-0}"
export NGINX_STDERR_LOGFILE="${NGINX_STDERR_LOGFILE:-/dev/stderr}"
export NGINX_STDERR_LOGFILE_MAXBYTES="${NGINX_STDERR_LOGFILE_MAXBYTES:-0}"

export SSHD_STDOUT_LOGFILE="${SSHD_STDOUT_LOGFILE:-/dev/stdout}"
export SSHD_STDOUT_LOGFILE_MAXBYTES="${SSHD_STDOUT_LOGFILE_MAXBYTES:-0}"
export SSHD_STDERR_LOGFILE="${SSHD_STDERR_LOGFILE:-/dev/stderr}"
export SSHD_STDERR_LOGFILE_MAXBYTES="${SSHD_STDERR_LOGFILE_MAXBYTES:-0}"

export HORIZON_STDOUT_LOGFILE="${HORIZON_STDOUT_LOGFILE:-/dev/stdout}"
export HORIZON_STDOUT_LOGFILE_MAXBYTES="${HORIZON_STDOUT_LOGFILE_MAXBYTES:-0}"
export HORIZON_STDERR_LOGFILE="${HORIZON_STDERR_LOGFILE:-/dev/stderr}"
export HORIZON_STDERR_LOGFILE_MAXBYTES="${HORIZON_STDERR_LOGFILE_MAXBYTES:-0}"

export PGBOUNCER_STDOUT_LOGFILE="${PGBOUNCER_STDOUT_LOGFILE:-/dev/stdout}"
export PGBOUNCER_STDOUT_LOGFILE_MAXBYTES="${PGBOUNCER_STDOUT_LOGFILE_MAXBYTES:-0}"
export PGBOUNCER_STDERR_LOGFILE="${PGBOUNCER_STDERR_LOGFILE:-/dev/stderr}"
export PGBOUNCER_STDERR_LOGFILE_MAXBYTES="${PGBOUNCER_STDERR_LOGFILE_MAXBYTES:-0}"

export OTELCOL_STDOUT_LOGFILE="${OTELCOL_STDOUT_LOGFILE:-/dev/stdout}"
export OTELCOL_STDOUT_LOGFILE_MAXBYTES="${OTELCOL_STDOUT_LOGFILE_MAXBYTES:-0}"
export OTELCOL_STDERR_LOGFILE="${OTELCOL_STDERR_LOGFILE:-/dev/stderr}"
export OTELCOL_STDERR_LOGFILE_MAXBYTES="${OTELCOL_STDERR_LOGFILE_MAXBYTES:-0}"

##########################################################################################################
# RESOLVE SHARED SETTINGS                                                                                #
##########################################################################################################

# These environment variables are shared between multiple configuration files.

export REQUEST_MAX_EXECUTION_TIME="${REQUEST_MAX_EXECUTION_TIME:-30}"

