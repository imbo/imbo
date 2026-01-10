#!/usr/bin/env bash
# Add to git hooks as a commit-msg hook to enforce conventional commit messages.
#
# From the repository root, run:
# ln -s ../../scripts/conventional-commit-msg.sh .git/hooks/commit-msg
set -euo pipefail

export LC_ALL=C

function validate_title {
  local msg="$1"

  echo "$msg" | grep -Eq \
    '^(build|ci|docs|feat|fix|refactor|revert|style|test)(\([a-z0-9 _\-]+\))?: .+$'
}

function explain_scheme {
  cat >&2 <<EOF
Your commit message title did not follow conventional commit message format:

>  $1

Format: <type>[(<scope>)]: <subject>

Where <type> is one of the following:

Type     | Description
---------+---------------------------------------------------------------------
build    | Build system or external dependencies (i.e. composer.json, scripts)
ci       | CI configuration files and scripts (i.e. .github/**)
docs     | Documentation only changes (i.e. README, code comments)
feat     | Introduces a new feature
fix      | Patches a bug
refactor | Neither bugfix nor adds a feature (i.e. rename classes, move code)
revert   | Reverts a previous commit
style    | Changes to output formatting / colors / coding style
test     | Adding / correcting tests

An optional scope may be provided in parentheses after type. A scope can
contain the following characters: a-z, 0-9, space, underscore (_), hyphen (-).

Examples:
- feat(matcher): add support for new fancy matcher
- build(deps): bump dependencies
- docs: fix typo in README

Please see
- https://www.conventionalcommits.org/en/v1.0.0/#summary
EOF
}

if [ -z "$1" ]; then
  echo "Missing argument (commit message). Did you try to run this script manually?"
  exit 1
fi

commit_title="$(head "$1" -n1)"
if ! validate_title "$commit_title"; then
  explain_scheme "$commit_title"
  exit 1
fi
