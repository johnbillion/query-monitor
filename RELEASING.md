# Releasing a New Version

These are the steps to take to release a new version of Query Monitor (for contributors who have push access to the GitHub repo).

## Prior to Release

1. Check [the milestone on GitHub](https://github.com/johnbillion/query-monitor/milestones) for open issues or PRs. Fix or reassign as necessary.
1. If this is a non-patch release, reassign issues and PRs assigned to the patch or minor milestones that will get skipped.
1. Ensure you're on the `develop` branch and all the changes for this release have been merged in.
1. Ensure `README.md` and `readme.txt` contain up to date "Tested up to" versions, descriptions, FAQs, and screenshots.
1. Ensure `.gitattributes` is up to date with all files that shouldn't be part of the build.
   - To do this, run `git archive --output=qm.zip HEAD` then check the contents for files that shouldn't be part of the package.
1. Run `composer test` and ensure everything passes.
1. Run `git push origin develop` and ensure CI is passing.
1. Prepare a changelog for the release.

## For Release

1. Bump the plugin version number:
   - `npm run bump:patch` for a patch release (1.2.3 => 1.2.4)
   - `npm run bump:minor` for a minor release (1.2.3 => 1.3.0)
   - `npm run bump:major` for a major release (1.2.3 => 2.0.0)
1. `git push origin develop:release`
1. `git push origin develop`
1. Wait for [the Build action](https://github.com/johnbillion/query-monitor/actions/workflows/build.yml) to complete
1. Enter the changelog into [the release on GitHub](https://github.com/johnbillion/query-monitor/releases) and publish it
1. Approve the release on [the WordPress.org release management dashboard](https://wordpress.org/plugins/developers/releases/)
1. `git push origin develop:trunk`
