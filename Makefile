ifndef BUILD_ENV
BUILD_ENV=php7.3
endif

QA_DOCKER_IMAGE=parkmanager/phpqa:latest
QA_DOCKER_COMMAND=docker run --init --interactive --tty --rm --env "COMPOSER_HOME=/composer" --user "$(shell id -u):$(shell id -g)" --volume /tmp/tmp-phpqa-$(shell id -u):/tmp --volume "$(shell pwd):/project" --volume "${HOME}/.composer:/composer" --workdir /project ${QA_DOCKER_IMAGE}

install: composer-install
dist: composer-validate cs phpstan psalm test
ci: check test
check: composer-validate cs-check phpstan psalm
test: phpunit-coverage infection

clean:
	rm -rf var/

composer-validate: ensure
	sh -c "${QA_DOCKER_COMMAND} composer validate"
#	sh -c "${QA_DOCKER_COMMAND} composer normalize"

	@for direc in $$(gfind src -mindepth 2 -type f -name composer.json -printf '%h\n'); \
	do \
		sh -c "${QA_DOCKER_COMMAND} composer validate --working-dir=$${direc}"; \
	done;

#	@for direc in $$(gfind src -mindepth 2 -type f -name composer.json -printf '%h\n'); \
#	do \
#		sh -c "${QA_DOCKER_COMMAND} composer validate --working-dir=$${direc}"; \
#		sh -c "${QA_DOCKER_COMMAND} composer normalize --working-dir=$${direc}"; \
#	done;

composer-install: fetch ensure clean
	sh -c "${QA_DOCKER_COMMAND} composer upgrade"

composer-install-lowest: fetch ensure clean
	sh -c "${QA_DOCKER_COMMAND} composer upgrade --prefer-lowest"

composer-install-dev: fetch ensure clean
	rm -f composer.lock
	cp composer.json _composer.json
	sh -c "${QA_DOCKER_COMMAND} composer config minimum-stability dev"
	sh -c "${QA_DOCKER_COMMAND} composer upgrade --no-progress --no-interaction --no-suggest --optimize-autoloader --ansi"
	mv _composer.json composer.json

cs:
	sh -c "docker-compose run --rm php vendor/bin/phpcbf"

cs-check:
	sh -c "docker-compose run --rm php vendor/bin/phpcs"

phpstan: ensure
	sh -c "${QA_DOCKER_COMMAND} phpstan analyse"

psalm: ensure
	sh -c "${QA_DOCKER_COMMAND} psalm --show-info=false"

infection: phpunit-coverage
	sh -c "${QA_DOCKER_COMMAND} phpdbg -qrr /tools/infection run --verbose --show-mutations --no-interaction --only-covered --coverage var/ --min-msi=84 --min-covered-msi=100"

phpunit-coverage: ensure
	sh -c "${QA_DOCKER_COMMAND} phpdbg -qrr vendor/bin/phpunit --verbose --exclude-group """" --coverage-text --log-junit=var/phpunit.junit.xml --coverage-xml var/coverage-xml/"

db-fixtures:
	bin/console doctrine:database:drop --force || true
	bin/console doctrine:database:create
	bin/console doctrine:schema:validate || true
	bin/console doctrine:schema:update --force
	psql -U root -h db -d park_manager -w -a -f ./etc/fixture.sql

phpunit:
	docker-compose run --rm php make db-fixtures
	sh -c "${QA_DOCKER_COMMAND} phpunit --verbose --exclude-group """" "

ensure:
	mkdir -p ${HOME}/.composer /tmp/tmp-phpqa-$(shell id -u)

fetch:
	docker pull "${QA_DOCKER_IMAGE}"
