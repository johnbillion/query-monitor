import { GlobalUtils, GlobalUtilsOptions } from '@johnbillion/plugin-infrastructure/acceptance';
import { execFileSync } from 'child_process';
import * as fs from 'fs';

const snapshot = '/tmp/query-monitor-snapshot.sql';
const dropin = 'vendor/wordpress/wordpress/wp-content/db.php';

const runInDatabaseContainer = ( script: string ): void => {
	execFileSync( 'docker', [ 'compose', 'exec', '-T', 'database', 'sh', '-c', script ], { stdio: 'pipe' } );
};

export class QueryMonitorGlobalUtils extends GlobalUtils {
	constructor( options: GlobalUtilsOptions ) {
		super( options );
	}

	/**
	 * Installs WordPress with WP-CLI.
	 */
	installWordPressFromScratch(): void {
		super.installWordPress();
	}

	/**
	 * Saves a dump of the database inside the database container.
	 */
	saveSnapshot(): void {
		runInDatabaseContainer( `mariadb-dump -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" > ${snapshot}` );
	}

	/**
	 * Restores the database saved by `saveSnapshot()`, which is much quicker than reinstalling WordPress.
	 */
	installWordPress(): void {
		runInDatabaseContainer( `{
			echo "DROP DATABASE \\\`$MYSQL_DATABASE\\\`; CREATE DATABASE \\\`$MYSQL_DATABASE\\\`; USE \\\`$MYSQL_DATABASE\\\`;";
			cat ${snapshot};
		} | mariadb -u"$MYSQL_USER" -p"$MYSQL_PASSWORD"` );

		// Restoring the database skips plugin activation, which is what creates the drop-in. The link is relative
		// because WordPress is mounted at a different path in the wpcli and php containers.
		fs.rmSync( dropin, { force: true } );
		fs.symlinkSync( 'plugins/query-monitor/wp-content/db.php', dropin );
	}
}
