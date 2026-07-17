#!/bin/sh

set -eu

SCRIPT_DIR=$( CDPATH= cd -- "$(dirname -- "$0")" && pwd )
REPOSITORY_ROOT=$( CDPATH= cd -- "$SCRIPT_DIR/.." && pwd )
COMPOSE_FILE="$REPOSITORY_ROOT/docker/woocommerce-e2e.yml"
COMPOSE_DEV_FILE="$REPOSITORY_ROOT/docker/woocommerce-e2e.dev.yml"

if ! command -v docker >/dev/null 2>&1; then
	printf '%s\n' 'Docker is required to stop the WooCommerce development stack.' >&2
	exit 1
fi

COMPOSE_PROJECT_NAME=basicrum_woocommerce_dev
export COMPOSE_PROJECT_NAME

docker compose -f "$COMPOSE_FILE" -f "$COMPOSE_DEV_FILE" down --volumes --remove-orphans
