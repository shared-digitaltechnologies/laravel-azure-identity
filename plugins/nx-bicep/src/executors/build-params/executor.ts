import { BuildParamsExecutorSchema } from './schema';
import { ExecutorContext } from '@nx/devkit';
import * as fs from 'fs';
import * as path from 'node:path';
import { spawn } from 'promisify-child-process';

export default async function runExecutor(
    options: BuildParamsExecutorSchema,
    context: ExecutorContext
) {
    const cmd = options.azPath ?? 'az';

    const args: string[] = ['bicep', 'build-params', '--file', options.file];

    if (options.noRestore) args.push('--no-restore');
    if (options.outputPath) {
        if (!fs.existsSync(options.outputPath)) {
            fs.mkdirSync(options.outputPath, {
                recursive: true,
            });
        }

        args.push('--outdir', options.outputPath);
    }
    if (options.outputFile) {
        const rootDir = path.dirname(options.outputFile);
        if (!fs.existsSync(rootDir)) {
            fs.mkdirSync(rootDir, {
                recursive: true,
            });
        }

        args.push('--outfile', options.outputFile);
    }
    if (context.isVerbose) args.push('--verbose');

    const result = await spawn(cmd, args, {
        cwd: process.cwd(),
        env: process.env,
        stdio: 'inherit',
    });

    return {
        success: result.code === 0,
    };
}
