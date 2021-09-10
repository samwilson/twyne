#!/bin/bash

## This script adapted from a similar one in wikimedia/toolforge-bundle

if [[ -z $1 || -z $2 ]]; then
    echo "USAGE: $0 [prod|dev] <app-dir>"
    exit 1
fi

## Get CLI parameters.
MODE=$1
APP_DIR=$(cd "$2" || exit; pwd)

## Update the repo.
cd "$APP_DIR" || exit
git fetch --quiet origin 2>&1

## Get some information about git.
HIGHEST_TAG=$(git tag --list "*.*.*" | sort --version-sort | tail --lines 1)
CURRENT_TAG=$(git describe --tags --always)
CURRENT_BRANCH=$(git symbolic-ref --short -q HEAD)
DIFF_TO_MAIN=$(git diff origin/main)

## Prod site: do nothing if we're already at the highest tag.
if [[ $MODE == 'prod' && "$CURRENT_TAG" == "$HIGHEST_TAG" ]]; then
    exit 0
fi

## Dev site: do nothing if not on main.
if [[ $MODE == "dev" && "$CURRENT_BRANCH" != "main" ]]; then
    ## Tell the maintainers, so they don't forget they're in
    ## the middle of testing something.
    echo "Dev site not on main branch. Not deploying."
    exit 0
fi

## Dev site: do nothing if there's no difference to main.
if [[ $MODE == "dev" && -z "$DIFF_TO_MAIN" ]]; then
    exit 0
fi

## Update the code.
if [[ $MODE == "prod" ]]; then
    git checkout "$HIGHEST_TAG"
fi
if [[ $MODE == "dev" ]]; then
    git pull origin main
fi

## Prod and dev sites: install the application.
composer install --no-dev --optimize-autoloader
./bin/console cache:clear
./bin/console doctrine:migrations:migrate --no-interaction
