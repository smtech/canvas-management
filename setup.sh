#!/usr/bin/env bash

# REQUIRED
echo "Preparing directory for install (your sudo password will be required)..."
sudo chmod -R 777 .
echo "Installing global Composer dependencies..."
composer global require "fxp/composer-asset-plugin:^1.1"
echo "Installing this package's Composer dependencies..."
composer install -o --prefer-dist

# OPTIONAL (but a good idea)
echo "Setting secure file permissions..."
sudo find . -type d -exec chmod 550 {} +
sudo find . -type f -exec chmod 440 {} +
sudo chmod 750 ./setup.sh
sudo find .git -type d -exec chmod 750 {} +
sudo find .git -type f -exec chmod 640 {} +

# REQUIRED
echo "Setting permissions to allow Smarty caching..."
sudo chmod -R 750 ./vendor/battis/bootstrapsmarty/templates_c
sudo chmod -R 750 ./vendor/battis/bootstrapsmarty/cache
SELINUX_ENABLED=$(sestatus | grep -oP "(?<=^Current mode:).*")
if [[ $SELINUX_ENABLED == "enabled" ]];	then
  sudo chcon -R -t httpd_sys_rw_content_t ./vendor/battis/bootstrapsmarty/templates_c
  sudo chcon -R -t httpd_sys_rw_content_t ./vendor/battis/bootstrapsmarty/cache
fi
