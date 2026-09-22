#!/usr/bin/env bash
x(){ echo "No script dir" >&2;return 1 2>/dev/null||exit 1;};if [ -n "${BASH_VERSION:-}" ];then s="${BASH_SOURCE[0]}";elif [ -n "${ZSH_VERSION:-}" ];then eval 's="${(%):-%x}"';else x;fi;[ -n "$s" ]||x;while [ -h "$s" ];do d="$(cd -P "$(dirname "$s")"&&pwd)"||x;s="$(readlink "$s")"||x;[[ $s != /* ]]&&s="$d/$s";done;__DIR__="$(cd -P "$(dirname "$s")"&&pwd)"||x;unset s d;unset -f x

# ========= Begin Configuration =========

# The directory path to the phpswap package, it must contain phpswap_execute.php
phpswap_directory=$(cd "$__DIR__/../vendor/aklump/phpswap/" 2>/dev/null && pwd)

# The PHP versions to run the test suite against, in order.
PHP_VERSIONS=(7.3 7.4 8.0 8.1 8.2 8.3 8.4 8.5)

# The command to execute under each PHP version, relative to CWD.
PHPUNIT='./vendor/bin/phpunit -c tests_phpunit/phpunit.xml'
# ========= End Configuration =========

function failed() {
  local message="$1"

  NO_FORMAT="\033[0m"
  F_BOLD="\033[1m"
  C_YELLOW="\033[48;5;226m"
 echo -e "${F_BOLD}${C_YELLOW}$message${NO_FORMAT}"
}

# ========= Validation =========
error=false
message="                                                                        "
if [[ -z "$phpswap_directory" ]]; then
  error=true
  message="$message\n     You seem to be missing this: https://github.com/aklump/phpswap     "
  message="$message\n     Try running: composer require --dev aklump/phpswap                 "
fi
if ! [ -e ./vendor/bin/phpunit ]; then
  error=true
  message="$message\n     You seem to be missing this: PHPUnit                               "
  message="$message\n     Try running: composer require --dev phpunit/phpunit                "
fi
if [[ "$error" == true ]]; then
    message="$message\n                                                                        "
    failed "$message"
    exit 1
fi

# ========= Force All PHP Errors To Be Displayed =========
# Many CLI builds (MAMP, some Homebrew formulae) ship display_errors=Off, so a
# fatal error produces a non-zero exit code and no output whatsoever -- the
# failure has to be hunted for instead of read.  A leading ':' makes PHP append
# this directory to its default scan path rather than replace it, so no php.ini
# is touched and the override applies to every PHP version phpswap swaps in.
# The variable is exported, so phpswap and the command it runs both inherit it.
php_ini_overrides="$(mktemp -d)"
trap 'rm -rf "$php_ini_overrides"' EXIT
cat > "$php_ini_overrides/zz-display-errors.ini" <<'PHP_INI'
display_errors = On
display_startup_errors = On
error_reporting = E_ALL
PHP_INI
export PHP_INI_SCAN_DIR="${PHP_INI_SCAN_DIR:-}:$php_ini_overrides"

# ========= Execute PHPUnit =========
verbose=''
if [[ "${*}" == *'-v'* ]]; then
  verbose='-v'
fi
# Note: each check is a full if-block rather than a `! cmd && ... && exit 1`
# one-liner.  In that idiom the compound command evaluates to false whenever the
# command SUCCEEDS, which becomes the loop's -- and therefore the script's --
# exit status, so a fully passing run would report failure to CI.
for version in "${PHP_VERSIONS[@]}"; do
  if ! "$phpswap_directory/phpswap_execute.php" supports "$version"; then
    failed "     PHP $version is not available in this environment.     "
    continue
  fi
  if ! "$phpswap_directory/phpswap_execute.php" using "$version" $verbose "$PHPUNIT"; then
    failed "     PHP $version tests failed.     "
    exit 1
  fi
done

exit 0
