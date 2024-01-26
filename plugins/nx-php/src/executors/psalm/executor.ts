import { ExecutorContext, logger } from '@nx/devkit';
import { PsalmExecutorSchema } from './schema';
import { spawnSync } from 'child_process';

export default async function runExecutor(
    options: PsalmExecutorSchema,
    context: ExecutorContext
) {
    const cmd = options.cmd ?? './vendor/bin/psalm.phar';
    const cwd = options.cwd ?? context.root;

    const args: string[] = [];

    if (options.config) args.push(`--config=${options.config}`);
    if (options.useIniDefaults) args.push('--use-ini-defaults');
    if (options.memoryLimit) args.push(`--memory-limit=${options.memoryLimit}`);
    if (options.disableExtensions) {
        for (const disableExtension of options.disableExtensions) {
            args.push(`--disable-extension=${disableExtension}`);
        }
    }
    if (options.threads) args.push(`--threads=${options.threads}`);
    if (options.noDiff) args.push('--no-diff');
    if (options.phpVersion) args.push(`--php-version=${options.phpVersion}`);
    if (options.errorLevel) args.push(`--error-level=${options.errorLevel}`);
    if (options.showInfo !== undefined)
        args.push(`--show-info=${options.showInfo}`);
    if (options.showSnippet !== undefined)
        args.push(`--show-snippet=${options.showSnippet}`);
    if (options.findDeadCode)
        args.push(`--find-dead-code=${options.findDeadCode}`);
    if (options.findUnusedCode)
        args.push(`--find-unused-code=${options.findUnusedCode}`);
    if (options.findUnusedPsalmSuppress)
        args.push('--find-unused-psalm-suppress');
    if (options.findReferencesTo)
        args.push(`--find-references-to=${options.findReferencesTo}`);
    if (options.noSuggestions) args.push('--no-suggestions');
    if (options.taintAnalysis) args.push('--taint-analysis');
    if (options.dumpTaintGraph)
        args.push(`--dump-taint-graph=${options.dumpTaintGraph}`);
    if (options.setBaseline) args.push(`--set-baseline=${options.setBaseline}`);
    if (options.useBaseline) args.push(`--use-baseline=${options.useBaseline}`);
    if (options.updateBaseline)
        args.push(`--update-baseline=${options.updateBaseline}`);
    if (options.plugin) args.push(`--plugin=${options.plugin}`);
    if (options.monochrome) args.push('--monochrome');
    if (options.outputFormat)
        args.push(`--output-format=${options.outputFormat}`);
    if (options.noProgress) args.push(`--no-progress`);
    if (options.longProgress) args.push(`--long-progress`);
    if (options.stats) args.push('--stags');
    if (options.report) args.push(`--report=${options.report}`);
    if (options.reportShowInfo)
        args.push(`--report-show-info=${options.reportShowInfo}`);
    if (options.clearCache) args.push(`--clear-cache`);
    if (options.clearGlobalCache) args.push('--clear-global-cache');
    if (options.noReflectionCache) args.push('--no-reflection-cache');
    if (options.noFileCache) args.push('--no-file-cache');
    if (options.debug) args.push('--debug');
    if (options.debugByLine) args.push('--debug-by-line');
    if (options.debugEmittedIssues) args.push('--debug-emitted-issues');
    if (options.root) args.push('--root');
    if (options.generateJsonMap)
        args.push(`--generate-json-map=${options.generateJsonMap}`);
    if (options.generateStubs)
        args.push(`--generate-stubs=${options.generateStubs}`);
    if (options.shepherd) args.push(`--shepherd=${options.shepherd}`);
    if (options.files) args.push(...options.files);

    if (context.isVerbose) {
        logger.log(`> ${cmd} ${args.map((arg) => `'${arg}'`).join(' ')}`);
    }

    const result = spawnSync(cmd, args, {
        cwd,
        stdio: 'inherit',
        env: process.env,
    });

    return {
        success: result.status === 0,
    };
}
