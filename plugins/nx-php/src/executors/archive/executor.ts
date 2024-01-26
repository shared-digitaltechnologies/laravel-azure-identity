import { spawnSync } from 'child_process';
import { ArchiveExecutorSchema } from './schema';
import { ExecutorContext, logger } from '@nx/devkit';
import * as path from 'path';

export default async function runExecutor(
    options: ArchiveExecutorSchema,
    context: ExecutorContext
) {
    const cmd = 'composer';

    const args: string[] = ['archive', '--ansi', '--no-interaction'];

    const projectName = context.projectName;
    if (!projectName) throw new Error('No project name found');

    const projectRoot =
        context.projectsConfigurations?.projects[projectName].root;

    if (!projectRoot) throw new Error('No project root found');
    if (options.outputFile) {
        const format = formatFromOutputFile(options.outputFile);
        const dir = path.relative(
            projectRoot,
            path.dirname(options.outputFile)
        );
        const file = path.basename(options.outputFile, `.${format}`);

        args.push('--format', format, '--dir', dir, '--file', file);
    }

    if (options.ignoreFilters) args.push('--ignore-filters');
    if (options.noPlugins) args.push('--no-plugins');
    if (options.noScripts) args.push('--no-scripts');
    if (options.noCache) args.push('--no-cache');
    if (context.isVerbose) args.push('-vv');
    if (options.package) {
        args.push('--', options.package);
        if (options.version) {
            args.push(options.version);
        }
    }

    if (context.isVerbose) {
        logger.log(`> ${cmd} ${args.map((arg) => `'${arg}'`).join(' ')}`);
    }

    const result = spawnSync(cmd, args, {
        cwd: projectRoot,
        stdio: 'inherit',
        env: process.env,
    });

    return {
        success: result.status === 0,
    };
}

function formatFromOutputFile(
    outputFile: string
): 'tar' | 'tar.gz' | 'tar.bz2' | 'zip' {
    const ext = path.extname(outputFile);
    switch (ext) {
        case '.tar':
            return 'tar';
        case '.gz':
            return 'tar.gz';
        case '.bz2':
            return 'tar.bz2';
        case '.zip':
            return 'zip';
        default:
            throw Error(
                `Unknown archive format '${ext}' for output file '${outputFile}'.`
            );
    }
}
