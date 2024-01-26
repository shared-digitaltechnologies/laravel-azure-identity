#!/bin/sh
set -e

########################################################################################################################
# OPTIMIZE APPLICATION                                                                                                 #
########################################################################################################################

# Optimize application
if [ 'true' = "$STARTUP_OPTIMIZE_APP" ]; then
    echo "[OPTIMIZE] Optimizing app configuration..."
    (
        cd $PROJECT_ROOT
        php artisan optimize
        chown www-data storage
    )
else
    echo "[OPTIMIZE] Skipped optimizing app configuration..."
fi
