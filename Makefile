install:
	docker run --rm --interactive --tty \
	--volume $$PWD:/app \
	composer install
update:
	docker run --rm --interactive --tty \
	--volume $$PWD:/app \
	composer update
run:
	docker run --rm --interactive --tty \
	--volume $$PWD:/usr/src/kahlan \
	--workdir /usr/src/kahlan \
	php:7.2-cli bin/kahlan
phpcs:
	docker run --rm --interactive --tty \
	--volume $$PWD:/usr/src/kahlan \
	--workdir /usr/src/kahlan \
	php:7.2-cli vendor/bin/phpcs
