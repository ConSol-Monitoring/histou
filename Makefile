
build:
	@echo "no build required"

test:
	php --version
	-phpcs --standard=conf/phpcs.xml .
	phpunit -c conf/phpunit.xml

fmt:
	phpcbf --standard=conf/phpcs.xml .
