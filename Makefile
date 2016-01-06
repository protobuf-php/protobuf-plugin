# vim: ts=4:sw=4:noexpandtab!:

BASEDIR        := $(shell pwd)
COMPOSER       := $(shell which composer)
PROTOC_VERSION := $(shell protoc --version | grep -oEi '([0-9]).*' | cut -d '.' -f 1)

help:
	@echo "---------------------------------------------"
	@echo "List of available targets:"
	@echo "  composer-install         - Installs composer dependencies."
	@echo "  phpcs                    - Runs PHP Code Sniffer."
	@echo "  phpunit                  - Runs tests."
	@echo "  phpunit-coverage-clover  - Runs tests to genereate coverage clover."
	@echo "  phpunit-coverage-html    - Runs tests to genereate coverage html."
	@echo "  help                     - Shows this dialog."
	@exit 0

all: install phpunit

install: composer-install proto-generate

test: proto-generate phpcs phpunit

composer-install:
ifdef COMPOSER
	php $(COMPOSER) install --prefer-source --no-interaction;
else
	@echo "Composer not found !!"
	@echo
	@echo "curl -sS https://getcomposer.org/installer | php"
	@echo "mv composer.phar /usr/local/bin/composer"
endif

proto-clean:
	rm -rf $(BASEDIR)/tests/Protos/*;

proto-generate: proto-clean
ifeq ($(PROTOC_VERSION), 3)
	php $(BASEDIR)/bin/protobuf --include-descriptors \
		--psr4 ProtobufCompilerTest\\Protos \
		-o $(BASEDIR)/tests/Protos \
		-i $(BASEDIR)/tests/Resources \
		$(BASEDIR)/tests/Resources/proto3/*.proto
endif
	php $(BASEDIR)/bin/protobuf --include-descriptors \
		--psr4 ProtobufCompilerTest\\Protos \
		-o $(BASEDIR)/tests/Protos \
		-i $(BASEDIR)/tests/Resources \
		$(BASEDIR)/tests/Resources/proto2/*.proto

phpunit:
	php $(BASEDIR)/vendor/bin/phpunit -v;

phpunit-coverage-clover:
	php $(BASEDIR)/vendor/bin/phpunit -v --coverage-clover ./build/logs/clover.xml;

phpunit-coverage-html:
	php $(BASEDIR)/vendor/bin/phpunit -v --coverage-html ./build/coverage;

phpcs:
	php $(BASEDIR)/vendor/bin/phpcs -p --extensions=php  --standard=ruleset.xml src;

.PHONY: composer-install phpunit phpcs help