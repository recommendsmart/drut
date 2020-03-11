#!/usr/bin/env bash

echo "# Starting services"
php -S localhost:8888 > /dev/null 2>&1 &
chromedriver > /dev/null 2>&1 &
service mysql start
mysql -u $MYSQL_USER -p$MYSQL_PASSWORD -e "CREATE DATABASE $MYSQL_DATABASE"

echo "# Running tests"
../vendor/bin/phpunit -c $CI_PROJECT_DIR/phpunit.xml.dist --bootstrap core/tests/bootstrap.php
RESULT="$?"

echo "# Moving artifacts into place"
rm $DRUPAL_BUILD_ROOT/web/sites/simpletest/browser_output/.htaccess 2>/dev/null || true
rm $DRUPAL_BUILD_ROOT/web/sites/simpletest/browser_output/*.counter 2>/dev/null || true
mv $DRUPAL_BUILD_ROOT/web/sites/simpletest/browser_output $CI_PROJECT_DIR/test-output 2>/dev/null || true

exit $RESULT
