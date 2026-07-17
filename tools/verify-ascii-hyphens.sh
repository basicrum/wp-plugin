#!/bin/sh

set -eu

repository_root=$( git rev-parse --show-toplevel 2>/dev/null ) || {
	printf '%s\n' 'ASCII hyphen check must run inside a Git worktree.' >&2
	exit 1
}

cd "$repository_root"

# UTF-8 byte sequences keep the forbidden characters out of this source file.
en_dash=$( printf '\342\200\223' )
em_dash=$( printf '\342\200\224' )

set +e
matches=$( LC_ALL=C git grep -nIF -e "$en_dash" -e "$em_dash" -- . )
status=$?
set -e

case "$status" in
	0)
		printf '%s\n' 'ASCII hyphen check failed: replace en dash and em dash characters with ASCII hyphens.' >&2
		printf '%s\n' "$matches" >&2
		exit 1
		;;
	1)
		printf '%s\n' 'ASCII hyphen check passed.'
		;;
	*)
		printf '%s\n' 'ASCII hyphen check could not search tracked text files.' >&2
		exit "$status"
		;;
esac
