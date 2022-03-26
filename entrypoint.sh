#!/bin/sh
set -e

echo "Waiting for db to be ready..."
ATTEMPTS_LEFT_TO_REACH_DATABASE=60
until [ $ATTEMPTS_LEFT_TO_REACH_DATABASE -eq 0 ] || DATABASE_ERROR=$(php bin/console dbal:run-sql "SELECT 1" 2>&1); do
    if [ $? -eq 255 ]; then
        # If the Doctrine command exits with 255, an unrecoverable error occurred
        ATTEMPTS_LEFT_TO_REACH_DATABASE=0
        break
    fi
    sleep 1
    ATTEMPTS_LEFT_TO_REACH_DATABASE=$((ATTEMPTS_LEFT_TO_REACH_DATABASE - 1))
    echo "Still waiting for db to be ready... Or maybe the db is not reachable. $ATTEMPTS_LEFT_TO_REACH_DATABASE attempts left"
done

if [ $ATTEMPTS_LEFT_TO_REACH_DATABASE -eq 0 ]; then
    echo "The database is not up or not reachable:"
    echo "$DATABASE_ERROR"
    exit 1
else
    echo "The db is now ready and reachable"
fi

if ls -A migrations/*.php >/dev/null 2>&1; then
    php bin/console doctrine:migrations:migrate --no-interaction
fi

yarn encore production
#for dev env you have to use : yarn watch
#but can't find how to.
symfony server:start