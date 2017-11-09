QA_DOCKER_IMAGE=parkmanager/phpqa:latest
QA_DOCKER_COMMAND=docker run -it --rm -v "$(shell pwd):/project" -w /project ${QA_DOCKER_IMAGE}

dist: install cs-full phpstan test-full
lint: install security-check cs-full phpstan
check: docker-compose.yml cs-full-check phpstan test-isolated

# Development set-up
install:
	composer.phar install --no-progress --no-interaction --no-suggest --optimize-autoloader --prefer-dist --ansi

# Don't run unless you know what your doing.
fixtures:
	bin/console doctrine:schema:drop --force
	bin/console doctrine:schema:update --force
	bin/console doctrine:schema:validate || true
	psql -U root -h db -d park_manager -w -a -f ./etc/fixture.sql

test:
	vendor/bin/phpunit --verbose --configuration phpunit.xml.dist --exclude-group functional,performance

test-full:
	vendor/bin/phpunit --verbose --configuration phpunit.xml.dist --exclude-group ""

docker-compose.yml:
	docker-compose up --build -d
	docker-compose run --rm php make install fixtures

test-isolated: docker-compose.yml
	docker-compose run --rm php make test-full

# Linting tools
security-check:
	sh -c "${QA_DOCKER_COMMAND} security-checker security:check ./composer.lock"

phpstan:
	sh -c "${QA_DOCKER_COMMAND} phpstan analyse --configuration phpstan.neon --level max src public bin"

cs:
	sh -c "${QA_DOCKER_COMMAND} php-cs-fixer fix -vvv --diff"

cs-full:
	sh -c "${QA_DOCKER_COMMAND} php-cs-fixer fix -vvv --using-cache=false --diff"

cs-full-check:
	sh -c "${QA_DOCKER_COMMAND} php-cs-fixer fix -vvv --using-cache=false --diff --dry-run"

.PHONY: install test test-full test-isolated security-check phpstan cs cs-full cs-full-check
