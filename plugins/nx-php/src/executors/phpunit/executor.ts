import { spawnSync } from 'child_process';
import { PhpunitExecutorSchema } from './schema';
import { ExecutorContext, logger } from '@nx/devkit';

export default async function runExecutor(
    options: PhpunitExecutorSchema,
    context: ExecutorContext
) {
    const cmd = options.cmd ?? './vendor/bin/phpunit';
    const cwd = options.cwd ?? context.root;

    const args: string[] = [];

    if (options.bootstrap) args.push('--bootstrap', options.bootstrap);
    if (options.configurationFile)
        args.push('--configuration', options.configurationFile);
    if (options.noConfiguration) args.push('--no-configuration');
    if (options.noExtensions) args.push('--no-extensions');
    if (options.includePath) {
        for (const includePath of options.includePath) {
            args.push('--include-path', includePath);
        }
    }
    if (options.phpConfig) {
        for (const [key, value] of Object.entries(options.phpConfig)) {
            args.push('-d', `${key}=${value}`);
        }
    }
    if (options.cacheDirectory)
        args.push('--cache-directory', options.cacheDirectory);
    if (options.useBaseline) args.push('--use-baseline', options.useBaseline);
    if (options.ignoreBaseline) args.push('--ignore-baseline');
    if (options.testsuites) {
        for (const testsuite of options.testsuites) {
            args.push('--testsuite', testsuite);
        }
    }

    if (options.excludeTestsuites) {
        for (const excludeTestsuite of options.excludeTestsuites) {
            args.push('--exclude-testsuite', excludeTestsuite);
        }
    }

    if (options.groups) {
        for (const group of options.groups) {
            args.push('--group', group);
        }
    }

    if (options.excludeGroups) {
        for (const excludeGroup of options.excludeGroups) {
            args.push('--exclude-group', excludeGroup);
        }
    }

    if (options.covers) args.push('--covers', options.covers);
    if (options.uses) args.push('--uses', options.uses);
    if (options.filter) args.push('--filter', options.filter);
    if (options.testSuffix)
        args.push('--test-suffix', options.testSuffix.join(','));

    if (options.processIsolation) args.push('--process-isolation');
    if (options.globalsBackup) args.push('--globals-backup');
    if (options.staticBackup) args.push('--static-backup');
    if (options.strictCoverage) args.push('--strict-coverage');
    if (options.strictGlobalState) args.push('--strict-global-state');
    if (options.disallowTestOutput) args.push('--disallow-test-output');
    if (options.enforceTimeLimit) args.push('--enforce-time-limit');
    if (options.defaultTimeLimit)
        args.push('--default-time-limit', String(options.defaultTimeLimit));
    if (options.dontReportUselessTests)
        args.push('--dont-report-useless-tests');

    if (options.stopOn) {
        const stopOn = options.stopOn;
        if (stopOn.defect) args.push('--stop-on-defect');
        if (stopOn.error) args.push('--stop-on-error');
        if (stopOn.failure) args.push('--stop-on-failure');
        if (stopOn.warning) args.push('--stop-on-warning');
        if (stopOn.risky) args.push('--stop-on-risky');
        if (stopOn.deprecation) args.push('--stop-on-deprecation');
        if (stopOn.notice) args.push('--stop-on-notice');
        if (stopOn.skipped) args.push('--stop-on-skipped');
        if (stopOn.incomplete) args.push('--stop-on-incomplete');
    }

    if (options.cacheResult) args.push('--cache-result');
    if (options.doNotCacheResult) args.push('--do-not-cache-result');
    if (options.orderBy) args.push('--order-by', options.orderBy);
    if (options.randomOrderSeed)
        args.push('--random-order-seed', String(options.randomOrderSeed));
    if (options.colors) args.push(`--colors=${options.colors}`);
    if (options.columns) args.push('--columns', String(options.columns));
    if (options.stderr) args.push('--stderr');
    if (options.noProgress) args.push('--no-progress');
    if (options.noResults) args.push('--no-results');
    if (options.noOutput) args.push('--no-output');
    if (options.display) {
        const display = options.display;
        if (display.incomplete) args.push('--display-incomplete');
        if (display.skipped) args.push('--display-skipped');
        if (display.deprecations) args.push('--display-deprecations');
        if (display.errors) args.push('--display-errors');
        if (display.notices) args.push('--display-notices');
        if (display.warnings) args.push('--display-warnings');
        if (display.reverse) args.push('--reverse-list');
    }
    if (options.teamCity) args.push('--teamcity');
    if (options.testDox) args.push('--testdox');
    if (options.debug) args.push('--debug');
    if (options.log) {
        const log = options.log;
        if (log.junit) args.push('--log-junit', log.junit);
        if (log.teamcity) args.push('--log-teamcity', log.teamcity);
        if (log.eventsText) args.push('--log-events-text', log.eventsText);
        if (log.eventsVerboseText)
            args.push('--log-events-verbose-text', log.eventsVerboseText);
    }
    if (options.noLogging) {
        args.push('--no-logging');
    }

    if (options.coverage) {
        const coverage = options.coverage;
        if (coverage.clover) args.push('--coverage-clover', coverage.clover);
        if (coverage.cobertura)
            args.push('--coverage-cobertura', coverage.cobertura);
        if (coverage.crap4j) args.push('--coverage-crap4j', coverage.crap4j);
        if (coverage.html) args.push('--coverage-html', coverage.html);
        if (coverage.php) args.push('--coverage-php', coverage.php);
        if (coverage.text) args.push(`--coverage-text=${coverage.text}`);
        if (coverage.xml) args.push('--coverage-xml ', coverage.xml);
    }
    if (options.warmCoverageCache) args.push('--warm-coverage-cache');
    if (options.coverageFilter) args.push('--coverage-filter');
    if (options.pathCoverage) args.push('--path-coverage');
    if (options.disableCoverageIgnore) args.push('--disable-coverage-ignore');
    if (options.noCoverage) args.push('--no-coverage');

    if (context.isVerbose) {
        logger.log(`> ${cmd} ${args.map((arg) => `'${arg}'`).join(' ')}`);
    }

    const result = spawnSync(cmd, args, {
        stdio: 'inherit',
        cwd,
        env: { ...process.env, XDEBUG_MODE: 'coverage' },
    });

    return {
        success: result.status === 0,
    };
}
