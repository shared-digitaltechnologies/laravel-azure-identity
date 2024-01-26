import { ExecutorContext } from '@nx/devkit';
import { LintExecutorSchema } from './schema';
import { spawn } from 'promisify-child-process';

export default async function runExecutor(
    options: LintExecutorSchema,
    context: ExecutorContext
) {
    const cmd = options.azPath ?? 'az';

    const args: string[] = ['bicep', 'build', '--file', options.file];

    if (options.noRestore) args.push('--no-restore');
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
