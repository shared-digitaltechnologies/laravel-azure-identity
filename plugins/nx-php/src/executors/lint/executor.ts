import { ExecutorContext, logger } from '@nx/devkit';
import { LintExecutorSchema } from './schema';
import * as fg from 'fast-glob';
import * as async from 'async';
import { spawn } from 'child_process';
import { cpus } from 'os';

export default async function runExecutor(
    options: LintExecutorSchema,
    context: ExecutorContext
) {
    const cwd = options.cwd ?? context.root;

    let fileCount: number = 0;
    let successCount: number = 0;
    let errorCount: number = 0;

    function lintFile(file: string, callback: (error?: unknown) => void) {
        fileCount++;

        spawn('php', ['-l', file], {
            cwd,
            stdio: [null, context.isVerbose ? 'inherit' : null, 'inherit'],
            env: process.env,
        }).on('close', (code) => {
            if (code === 0) {
                successCount++;
            } else {
                errorCount++;
            }
            callback();
        });
    }

    const items = fg.stream(options.patterns, {
        absolute: false,
        cwd,
        dot: options.dot ?? false,
        followSymbolicLinks: options.followSymbolicLinks ?? false,
        deep: options.deep,
        ignore: options.ignore,
        onlyFiles: true,
        unique: true,
        globstar: true,
    });

    if (options.bail) {
        await async.everyLimit(
            items as AsyncIterable<string>,
            cpus().length,
            lintFile
        );
    } else {
        await async.eachLimit(
            items as AsyncIterable<string>,
            cpus().length,
            lintFile
        );
    }

    logger.log(`

RESULTS
 - Successes: ${successCount}/${fileCount}
 - Failures:  ${errorCount}/${fileCount}
`);

    return {
        success: errorCount === 0,
    };
}
