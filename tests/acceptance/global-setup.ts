import { FullConfig } from '@playwright/test';
import { QueryMonitorGlobalUtils } from './utils/query-monitor-global-utils';

export default function globalSetup( config: FullConfig ): void {
	const globalUtils = new QueryMonitorGlobalUtils( {
		baseURL: config.projects[0].use.baseURL!,
		pluginSlug: 'query-monitor',
	} );

	globalUtils.installWordPressFromScratch();
	globalUtils.saveSnapshot();
}
