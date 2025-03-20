export PATH=/usr/bin:/bin

build:
	@echo "no build required"

test:
	php --version
	-phpcs --standard=conf/phpcs.xml .
	phpunit -c conf/phpunit.xml

citest:
	php --version
	type php
	type phpunit
	phpcs --standard=conf/phpcs.xml .
	phpunit -c conf/phpunit.xml

fmt:
	phpcbf --standard=conf/phpcs.xml .
