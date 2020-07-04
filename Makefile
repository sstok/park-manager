QA_DOCKER_IMAGE=parkmanager/phpqa:latest
QA_DOCKER_COMMAND=docker run --init -t --rm --env "COMPOSER_HOME=/composer" --user "$(shell id -u):$(shell id -g)" --volume /tmp/tmp-phpqa-$(shell id -u):/tmp:delegated --volume "$(shell pwd):/project:delegated" --volume "${HOME}/.composer:/composer:delegated" --workdir /project ${QA_DOCKER_IMAGE}

install: composer-install
ci: install check test
check: composer-validate lint-xml lint-yaml lint-twig cs-check phpstan psalm
test: phpunit
test-coverage: infection

clean:
	rm -rf var/

composer-validate: ensure
	@echo "Validating local composer files"
	@sh -c "${QA_DOCKER_COMMAND} composer validate"
	@sh -c "${QA_DOCKER_COMMAND} composer-normalize --dry-run"

encore:
	docker-compose run --rm encore make in-docker-encore

lint-xml:
	@echo "Validating XML files"

ifeq (, $(shell which xmllint))
	@echo "[SKIPPED] No xmllint in $(PATH), consider installing it"
else
	@find . \( -name '*.xml' -or -name '*.xliff' -or -name '*.xlf' \) \
			-not -path './vendor/*' \
			-not -path './.*' \
			-not -path './var/*' \
			-type f \
			-exec xmllint --format --encode UTF-8 --noout '{}' \;
endif

lint-yaml:
	@echo "Validating YAML files"
	docker-compose run --rm php php bin/console lint:yaml -vv config/

lint-twig:
	@echo "Validating Twig files"
	docker-compose run --rm php php bin/console lint:twig -vv templates/
	sh -c "${QA_DOCKER_COMMAND} twigcs --severity=error templates/"

composer-install: clean
	docker-compose run --rm php composer install

cs: ensure
	sh -c "${QA_DOCKER_COMMAND} php-cs-fixer fix -vvv --using-cache=false --diff"

cs-check: ensure
	sh -c "${QA_DOCKER_COMMAND} php-cs-fixer fix -vvv --diff --dry-run"
	sh -c "docker-compose run --rm php vendor/bin/phpcs"

phpstan: ensure
	docker-compose run --user "$(shell id -u):$(shell id -g)" --rm php bin/console cache:clear --env=dev
	sh -c "${QA_DOCKER_COMMAND} phpstan analyse"

rector: ensure
	sh -c "${QA_DOCKER_COMMAND} rector process /project --config /project/rector.yaml --dry-run"

psalm: ensure
	sh -c "${QA_DOCKER_COMMAND} vendor/bin/psalm --show-info=false"

phpunit: encore
	docker-compose run --rm php make in-docker-phpunit

infection: clean
	docker-compose run --rm php make in-docker-infection

##
# Private targets
##

db-fixtures:
	bin/console doctrine:database:drop --force || true
	bin/console doctrine:database:create
	bin/console doctrine:schema:validate || true
	bin/console doctrine:schema:update --force
	bin/console doctrine:fixtures:load --no-interaction

in-docker-phpunit:
	bin/console cache:clear --env=test
	APP_ENV=test make db-fixtures
	APP_ENV=test vendor/bin/phpunit --verbose --configuration phpunit.xml.dist --exclude-group ""

in-docker-infection:
	bin/console cache:clear --env=test
	APP_ENV=test make db-fixtures
	phpdbg -qrr vendor/bin/phpunit --verbose --configuration phpunit.xml.dist --exclude-group "" --coverage-text --log-junit=var/junit.xml --coverage-xml var/coverage-xml/
	phpdbg -qrr /usr/local/bin/infection run --verbose --show-mutations --no-interaction --only-covered --coverage var/ --min-msi=84 --min-covered-msi=84

in-docker-encore:
	yarn install
	yarn encore dev

fetch:
	docker pull "${QA_DOCKER_IMAGE}"

ensure:
	mkdir -p ${HOME}/.composer /tmp/tmp-phpqa-$(shell id -u) var/

.PHONY: clean composer-validate lint-xml lint-yaml lint-twig
.PHONY: composer-install cs cs-check phpstan psalm phpunit infection
.PHONY: db-fixtures in-docker-phpunit in-docker-infection fetch ensure

