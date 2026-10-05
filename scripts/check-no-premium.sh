#!/usr/bin/env bash
#
# check-no-premium.sh — fail the build if AWT Premium *implementation* code has
# leaked into this free theme repo.
#
# What this DOES flag:
#   1. A Premium block/feature implementation whose slug is on the denylist below.
#   2. Any `premium-staging/` archive living inside the repo.
#
# What this does NOT flag (shared base that ships in free): the design-system
# class contract that keeps a Premium slug live (e.g. Carbon's `header-search`
# classes) so the awt-premium add-on routes through it instead of forking,
# and Premium upsell copy / URLs.
#
# See awt-stage-1-spec.md §6 "Build sequencing".
set -uo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
FAIL=0

# Slugs reserved for AWT Premium — never ship as an implementation here.
PREMIUM_BLOCKS=("header-search" "chart" "timeline" "timeline-item")

# The theme has no src/build; scan the whole tree minus deps for a block dir.
for slug in "${PREMIUM_BLOCKS[@]}"; do
  while IFS= read -r hit; do
    [ -n "$hit" ] || continue
    # Only a real implementation carries block.json or render.php.
    if [ -f "$hit/block.json" ] || [ -f "$hit/render.php" ]; then
      echo "❌ Premium implementation found: ${hit#"$ROOT"/}"
      FAIL=1
    fi
  done < <(find "$ROOT" -name node_modules -prune -o -type d -name "$slug" -print 2>/dev/null)
done

while IFS= read -r hit; do
  [ -n "$hit" ] || continue
  echo "❌ premium-staging present in free repo: ${hit#"$ROOT"/}"
  FAIL=1
done < <(find "$ROOT" -name node_modules -prune -o -type d -name "premium-staging" -print 2>/dev/null)

if [ "$FAIL" -ne 0 ]; then
  echo ""
  echo "Premium code belongs in the awt-premium repo, not this free repo."
  echo "See awt-stage-1-spec.md §6 'Build sequencing'."
  exit 1
fi

echo "✓ No AWT Premium implementation code in $(basename "$ROOT")."
