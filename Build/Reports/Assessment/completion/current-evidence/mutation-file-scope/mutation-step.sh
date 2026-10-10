set -euo pipefail
mutation_scope=()
if [[ "$MUTATION_EVENT" == pull_request ]]; then
  [[ "$MUTATION_DIFF_BASE" =~ ^[0-9a-f]{40}$ ]] || { printf 'Invalid mutation comparison commit.\n' >&2; exit 2; }
  git cat-file -e "$MUTATION_DIFF_BASE^{commit}"
  changed_source_list=$(mktemp)
  trap 'rm -f -- "$changed_source_list"' EXIT
  git diff --name-only -z --diff-filter=AMR "$MUTATION_DIFF_BASE" HEAD -- ':(glob)Classes/**/*.php' > "$changed_source_list"
  mapfile -d '' -t mutation_scope < "$changed_source_list"
  if (( ${#mutation_scope[@]} == 0 )); then
    printf 'No production PHP added, modified or renamed; Unit and native behavior passed, mutation measurement is skipped.\n'
    exit 0
  fi
  for changed_source in "${mutation_scope[@]}"; do
    [[ "$changed_source" == Classes/*.php && -f "$changed_source" && ! -L "$changed_source" ]] || { printf 'Invalid mutation source file.\n' >&2; exit 2; }
  done
fi
.Build/vendor/bin/infection --configuration infection.native.json5 --threads=1 \
  --with-uncovered --with-timeouts --only-covering-test-cases \
  --no-progress --show-mutations=0 -- "${mutation_scope[@]}"

