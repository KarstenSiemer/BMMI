#!/usr/bin/env bash

failure=0

php_lint(){
  echo "Running php -l tests:"
  find ./app -type f -name "*.php" -print0 | xargs -0 -n1 -P8 docker run --rm -w "$(pwd)" -v "$(pwd)":"$(pwd)" webdevops/php-apache:8.0 php -l || failure=1
}

php_cs(){
  echo "Running squizlabs/php_codesniffer:"
  # Find errors and let commit fail
  docker run --rm -w "$(pwd)" -v "$(pwd)":"$(pwd)" webdevops/php-apache:8.0 bash -c 'composer require "squizlabs/php_codesniffer=*" --sort-packages --dev && ./vendor/bin/phpcs "$0"' ./app || failure=1

  # Fix fixable to add to staging area
  docker run --rm -w "$(pwd)" -v "$(pwd)":"$(pwd)" webdevops/php-apache:8.0 bash -c 'composer require "squizlabs/php_codesniffer=*" --sort-packages --dev && ./vendor/bin/phpcbf "$0"' ./app
}

php_stan(){
  echo "Running phpstan:"
  docker run --rm -w "$(pwd)" -v "$(pwd)":"$(pwd)" webdevops/php-apache:8.0 bash -c 'composer require "phpstan/phpstan" --sort-packages --dev && ./vendor/bin/phpstan analyse --level max "$0"' ./app || failure=1
}

php_lint
php_cs
php_stan
exit ${failure}
