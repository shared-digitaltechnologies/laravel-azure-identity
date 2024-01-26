#!/usr/bin/env sh

# This script does the last steps that need to be done before the application can run.
# Afterwards, it gives a summary of the startup environment variables, so you can check
# if everything is configured as expected.

>&2 echo "[STARTUP-FINISH] Set home directory permissions..."
if [ -f "/home" ]; then chmod 777 /home; fi
if [ -f "/root" ]; then chmod 777 /root; fi

SHRD_LARAVEL_WEBAPP_CONTAINER_VARIANT
>&2 echo "[STARTUP-FINISH] Summary: 🤓"
>&2 echo "   - Laravel Container Variant:  ${SHRD_LARAVEL_WEBAPP_CONTAINER_VARIANT:-'<undefined>'}"
>&2 echo "   - Website Hostname:           ${WEBSITE_HOSTNAME:-'<undefined>'}"
>&2 echo "   - Server Name:                ${SERVER_NAME}"
>&2 echo "   - Workspace root:             $WORKSPACE_ROOT"
>&2 echo "   - Project (Laravel-app) root: $PROJECT_ROOT"
>&2 echo "   - PHP-FPM enabled:            $PHP_FPM_ENABLED"
>&2 echo "   - NGINX enabled:              $NGINX_ENABLED"
>&2 echo "   - Horizon enabled:            $HORIZON_ENABLED"
>&2 echo "   - PGBouncer enabled:          $PGBOUNCER_ENABLED"
>&2 echo "   - OTelCol enabled:            $OTELCOL_ENABLED"
>&2 echo "   - SSHD enabled:               $SSHD_ENABLED"
>&2 echo "   - App optimized:              ${STARTUP_OPTIMIZE_APP:-false}"

>&2 echo "[STARTUP-FINISH] Container Successfully Initialized! 😎"

echo "${STARTUP_COMPLETE_MESSAGE:-''}"
