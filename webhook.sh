composer update
yarn install
yarn encore production
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console c:c --env=prod
#php bin/console opti:covers
