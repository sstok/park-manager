QA_DOCKER_IMAGE=parkmanager/phpqa:latest
QA_DOCKER_COMMAND=docker run -it --rm -v "$(shell pwd):/project" -w /project ${QA_DOCKER_IMAGE}

dist: install security-check cs-full phpstan test
ci: install security-check cs-full-check phpstan test
lint: security-check cs-full-check phpstan

install:
	docker-compose run --rm php make in-docker-install in-docker-clean-vendor

install-dev:
	docker-compose run --rm php make in-docker-install-dev in-docker-clean-vendor

test: docker-up
	docker-compose run --rm php make in-docker-test
	@$(MAKE) docker-down

test-coverage: docker-up
	mkdir -p build/logs build/cov
	docker-compose run --rm php make in-docker-test-coverage
	sh -c "${QA_DOCKER_COMMAND} phpdbg -qrr /usr/local/bin/phpcov merge --clover build/logs/clover.xml build/cov"
	@$(MAKE) docker-down

##
# Linting tools
##
security-check:
	sh -c "${QA_DOCKER_COMMAND} security-checker security:check ./composer.lock"

phpstan:
	docker-compose run --rm php make in-docker-phpstan

in-docker-phpstan:
	composer bin phpstan install --no-progress --no-interaction --no-suggest --optimize-autoloader --ansi
	php -d memory_limit=1G vendor/bin/phpstan analyse --configuration phpstan.neon --level max src public bin

cs:
	sh -c "${QA_DOCKER_COMMAND} php-cs-fixer fix -vvv --diff"

cs-full:
	sh -c "${QA_DOCKER_COMMAND} php-cs-fixer fix -vvv --using-cache=false --diff"

cs-full-check:
	sh -c "${QA_DOCKER_COMMAND} php-cs-fixer fix -vvv --using-cache=false --diff --dry-run"

##
# Special operations
##

docker-up:
	docker-compose up -d

docker-down:
	docker-compose down

##
# Private targets
##
in-docker-install:
	rm -f composer.lock
	composer.phar install --no-progress --no-interaction --no-suggest --optimize-autoloader --ansi

in-docker-install-dev:
	rm -f composer.lock
	cp composer.json _composer.json
	composer.phar config minimum-stability dev
	composer.phar update --no-progress --no-interaction --no-suggest --optimize-autoloader --ansi
	mv _composer.json composer.json

in-docker-install-fixtures:
	bin/console doctrine:schema:drop --force
	bin/console doctrine:schema:update --force
	bin/console doctrine:schema:validate || true
	psql -U root -h db -d park_manager -w -a -f ./etc/fixture.sql

in-docker-test: in-docker-install-fixtures
	vendor/bin/phpunit --exclude-group "" --verbose

in-docker-test-coverage: in-docker-install-fixtures
	phpdbg -qrr vendor/bin/phpunit --verbose --exclude-group "" --coverage-php build/cov/coverage-phpunit.cov

in-docker-clean-vendor:
	ls vendor/symfony/ | awk -F" " '{if ($$1) print "vendor/symfony/"$$1"/Tests" }' | xargs rm -rf
	ls vendor/symfony/ | awk -F" " '{if ($$1) print "vendor/symfony/"$$1"/LICENSE" }' | xargs rm -f
	ls vendor/symfony/ | awk -F" " '{if ($$1) print "vendor/symfony/"$$1"/README.md" }' | xargs rm -f
	ls vendor/symfony/ | awk -F" " '{if ($$1) print "vendor/symfony/"$$1"/.gitignore" }' | xargs rm -f
	ls vendor/symfony/ | awk -F" " '{if ($$1) print "vendor/symfony/"$$1"/phpunit.xml.dist" }' | xargs rm -f
	ls vendor/symfony/ | awk -F" " '{if ($$1) print "vendor/symfony/"$$1"/CHANGELOG.md" }' | xargs rm -f

.PHONY: install install-dev security-check phpstan cs cs-full cs-full-checks docker-up down-down
.PHONY: in-docker-install in-docker-install-dev in-docker-install-lowest in-docker-test in-docker-test-coverage in-docker-clean-vendor
