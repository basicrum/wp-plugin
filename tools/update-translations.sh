#!/bin/sh

set -eu

plugin_dir="${1:-plugins/basicrum}"
languages_dir="${plugin_dir}/languages"

wp i18n make-pot \
	"${plugin_dir}" \
	"${languages_dir}/basicrum.pot" \
	--domain=basicrum \
	--exclude=tests,vendor \
	--headers='{"POT-Creation-Date":""}' \
	--allow-root

wp i18n update-po \
	"${languages_dir}/basicrum.pot" \
	"${languages_dir}/basicrum-bg_BG.po" \
	--allow-root

wp i18n make-mo \
	"${languages_dir}/basicrum-bg_BG.po" \
	"${languages_dir}/basicrum-bg_BG.mo" \
	--allow-root
