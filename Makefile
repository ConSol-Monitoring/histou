export PATH=/usr/bin:/bin

build:
	@echo "no build required"

test:
	php --version
	-phpcs --standard=conf/phpcs.xml .
	phpunit -c conf/phpunit.xml

# run only specific tests, ex.: make testf testParseArgs
testf:
	phpunit -c conf/phpunit.xml --filter $(filter-out $@,$(MAKECMDGOALS))

citest:
	php --version
	type php
	type phpunit
	phpcs --standard=conf/phpcs.xml .
	phpunit -c conf/phpunit.xml

fmt:
	phpcbf --standard=conf/phpcs.xml .

# just skip unknown make targets
.DEFAULT:
	if [[ "$(MAKECMDGOALS)" =~ ^testf ]]; then \
		; \
	else \
		echo "unknown make target(s): $(MAKECMDGOALS)"; \
		exit 1; \
	fi
