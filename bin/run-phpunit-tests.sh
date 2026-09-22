#!/usr/bin/env bash
x(){ echo "No script dir" >&2;return 1 2>/dev/null||exit 1;};if [ -n "${BASH_VERSION:-}" ];then s="${BASH_SOURCE[0]}";elif [ -n "${ZSH_VERSION:-}" ];then eval 's="${(%):-%x}"';else x;fi;[ -n "$s" ]||x;while [ -h "$s" ];do d="$(cd -P "$(dirname "$s")"&&pwd)"||x;s="$(readlink "$s")"||x;[[ $s != /* ]]&&s="$d/$s";done;__DIR__="$(cd -P "$(dirname "$s")"&&pwd)"||x;unset s d;unset -f x

# ========= Begin Configuration =========
PHP="$(command -v php)"
#PHP=/opt/homebrew/opt/php@8.5/bin/php

# Paths should be relative to THIS file:
INSTALL_PATH="../tests_phpunit/"
CONFIG="../tests_phpunit/phpunit.xml"
VENDOR_PATH="../vendor/"
# ========= End Configuration =========

# ========= Validation =========
[[ -z "$INSTALL_PATH" ]] && echo "❌️ \$INSTALL_PATH cannot be empty" && exit 3
INSTALL_PATH="$(cd "$__DIR__/$INSTALL_PATH" && pwd)"
[[ -z "$INSTALL_PATH" ]] && echo "❌️ \$INSTALL_PATH does not exist; check the \$INSTALL_PATH variable in $0" && exit 3
CONFIG="$__DIR__/$CONFIG"
VENDOR_PATH="$(cd "$__DIR__/$VENDOR_PATH" && pwd)"
[[ -z "$VENDOR_PATH" ]] && echo "❌️ \$VENDOR_PATH cannot be empty" && exit 4
[[ ! -d  "$VENDOR_PATH" ]] && echo "❌️ \"$VENDOR_PATH\" does not exist; check the \$VENDOR_PATH variable in $0" && exit 5
[[ ! -f $VENDOR_PATH/bin/phpunit ]] && echo "❌️ missing dependencies; try \`composer install\`" && echo && exit 6

# ========= Internal config =========
# shellcheck disable=SC2034
coverage_reports="$INSTALL_PATH/reports"

# DynamicConfig points BROWSERTEST_OUTPUT_DIRECTORY here but doesn't create it,
# and the directory is gitignored, so a fresh worktree has none and every run
# warns "not a writable directory" before the PHPUnit banner.
mkdir -p "$INSTALL_PATH/test_output"

export INSTALL_PATH

# ========= Execute PHPUnit =========
"$PHP" "$VENDOR_PATH/bin/phpunit" -c "$CONFIG" "$@"
#"$PHP" "$VENDOR_PATH/bin/phpunit" -c "$CONFIG" --testdox "$@"
#export XDEBUG_MODE=$XDEBUG_MODE,coverage;"$PHP" "$VENDOR_PATH/bin/phpunit" -c "$CONFIG" --coverage-html="$coverage_reports" "$@"
#echo "$coverage_reports/index.html"
