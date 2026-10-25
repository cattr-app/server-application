#!/usr/bin/env python3
"""Self-contained line coverage reporting for the Tests workflow (advisory).

Subcommands:
  summarize COBERTURA [--json FILE] [--sha SHA] [--title TITLE]
      Read total line coverage from a PHPUnit Cobertura report, write it to
      $GITHUB_OUTPUT (lines_valid, lines_covered, percent) and the job
      summary, and optionally to a baseline JSON file.
  read-baseline JSON --sha SHA
      Validate a baseline JSON file recorded by `summarize --json` for
      commit SHA and write found=true and its counts to $GITHUB_OUTPUT.
  compare
      Print "main <sha7>: X% -> this PR: Y% (+Z pp)" into the job summary,
      or "no baseline: <reason>". Inputs come from the environment (see
      compare()). Never fails.

Percentages are computed from line counts and rounded to 2 decimals.
"""

import argparse
import json
import os
import re
import sys
import xml.etree.ElementTree as ET

SHA_RE = re.compile(r'[0-9a-f]{40}')


def percent(covered, valid):
    return round(100 * covered / valid, 2) if valid else 0.0


def counts(valid, covered):
    """Validate line counts given as strings or numbers."""
    valid, covered = int(valid), int(covered)
    if valid < 0 or covered < 0 or covered > valid:
        raise ValueError(f'invalid line counts {covered}/{valid}')
    return valid, covered


def append(env_name, text):
    path = os.environ.get(env_name)
    if path:
        with open(path, 'a', encoding='utf-8') as f:
            f.write(text)


def set_outputs(**values):
    append('GITHUB_OUTPUT', ''.join(f'{k}={v}\n' for k, v in values.items()))


def summarize(args):
    root = ET.parse(args.cobertura).getroot()
    valid, covered = counts(root.get('lines-valid'), root.get('lines-covered'))
    pct = percent(covered, valid)
    set_outputs(lines_valid=valid, lines_covered=covered, percent=f'{pct:.2f}')
    text = f'Lines: {pct:.2f}% ({covered}/{valid})'
    print(text)
    append('GITHUB_STEP_SUMMARY', f'### {args.title}\n\n{text}\n\n')
    if args.json:
        if not SHA_RE.fullmatch(args.sha or ''):
            raise SystemExit('--json requires --sha with a full commit SHA')
        with open(args.json, 'w', encoding='utf-8') as f:
            json.dump({
                'sha': args.sha,
                'lines_valid': valid,
                'lines_covered': covered,
                'percent': pct,
            }, f)
            f.write('\n')


def read_baseline(args):
    with open(args.json, encoding='utf-8') as f:
        data = json.load(f)
    if data.get('sha') != args.sha:
        raise SystemExit(f'baseline is for {data.get("sha")!r}, expected {args.sha}')
    valid, covered = counts(data['lines_valid'], data['lines_covered'])
    set_outputs(found='true', lines_valid=valid, lines_covered=covered)
    print(f'Baseline for {args.sha}: {percent(covered, valid):.2f}% ({covered}/{valid})')


def compare(_args):
    """Inputs (environment):
    BASE_SHA                     PR base commit
    PR_VALID, PR_COVERED         line counts of the PR (PHPUnit job)
    FOUND, FOUND_VALID, FOUND_COVERED
                                 baseline artifact from main for BASE_SHA
                                 (FOUND is "true" only if it was valid)
    COMPUTED_VALID, COMPUTED_COVERED, COMPUTED_REASON
                                 baseline computed from the base commit
    """
    env = os.environ.get
    base_sha = env('BASE_SHA', '')
    lines = ['### Coverage vs main', '']
    try:
        try:
            pr_valid, pr_covered = counts(env('PR_VALID'), env('PR_COVERED'))
        except (TypeError, ValueError):
            raise LookupError('no coverage for this PR (PHPUnit job failed?)')
        try:
            if env('FOUND') == 'true':
                source = 'artifact from main'
                base_valid, base_covered = counts(env('FOUND_VALID'), env('FOUND_COVERED'))
            else:
                source = 'computed from the base commit'
                base_valid, base_covered = counts(env('COMPUTED_VALID'), env('COMPUTED_COVERED'))
        except (TypeError, ValueError):
            raise LookupError(f'no baseline: {env("COMPUTED_REASON") or "baseline job did not run"}')
        base_pct = percent(base_covered, base_valid)
        pr_pct = percent(pr_covered, pr_valid)
        lines.append(
            f'main {base_sha[:7]}: {base_pct:.2f}% → this PR: {pr_pct:.2f}% '
            f'({pr_pct - base_pct:+.2f} pp)'
        )
        lines.append('')
        lines.append(f'Lines: {base_covered}/{base_valid} → {pr_covered}/{pr_valid}; baseline {source}.')
    except LookupError as e:
        lines.append(str(e))
    except Exception as e:  # advisory: never fail the job
        lines.append(f'no baseline: unexpected error ({type(e).__name__})')
    text = '\n'.join(lines) + '\n'
    print(text)
    append('GITHUB_STEP_SUMMARY', text + '\n')


def main():
    parser = argparse.ArgumentParser(description=__doc__.split('\n')[0])
    sub = parser.add_subparsers(dest='command', required=True)

    p = sub.add_parser('summarize')
    p.add_argument('cobertura')
    p.add_argument('--json')
    p.add_argument('--sha')
    p.add_argument('--title', default='PHPUnit coverage')
    p.set_defaults(func=summarize)

    p = sub.add_parser('read-baseline')
    p.add_argument('json')
    p.add_argument('--sha', required=True)
    p.set_defaults(func=read_baseline)

    p = sub.add_parser('compare')
    p.set_defaults(func=compare)

    args = parser.parse_args()
    args.func(args)


if __name__ == '__main__':
    sys.exit(main())
