# Meta
.PHONY: build

# Include standard makefile
-include vendor/dav-m85/std-makefile/trusty-deploy.mk

# Variables
CD=$(shell pwd)

#
# Standard targets
#
help:
	@echo "Please visit ... for help"

build:
	composer install
	npm install
	node node_modules/gulp/bin/gulp.js build

clean:
	rm -rf vendor
	rm -rf node_modules

mrproper: clean

test: reports
	rm -rf reports/*
	php bin/behat -f progress,junit --out ,reports

#
# Sub targets
#
reports:
	mkdir reports
