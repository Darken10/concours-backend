deploy:
	ssh o2switch "cd ~/sites/concours.liptra.net && git pull && make install"

install: vendor/autoload.php .env public/storage public/build/manifest.json
	php artisan cache:clear
	php artisan migrate
	npm run build

.env:
	cp .env.example .env
	php artisan key:generate

vendor/autoload.php: composer.lock
	composer install
	touch vendor/autoload.php

public/storage:
	php artisan storage:link

public/build/manifest.json: package.json
	npm install

