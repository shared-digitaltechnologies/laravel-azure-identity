export const SPECIAL_COMPOSER_COMMANDS = [
    'pre-install-cmd',
    'post-install-cmd',
    'pre-update-cmd',
    'post-update-cmd',
    'pre-status-cmd',
    'post-status-cmd',
    'pre-archive-cmd',
    'post-archive-cmd',
    'pre-autoload-dump',
    'post-autoload-dump',
    'post-root-package-install',
    'post-create-project-command',
    'pre-operations-exec',
    'pre-package-install',
    'post-package-install',
    'pre-package-update',
    'post-package-update',
    'pre-package-uninstall',
    'post-package-uninstall',
    'init',
] as const;

export function isSpecialComposerCommand(scriptName: string): boolean {
    return (SPECIAL_COMPOSER_COMMANDS as readonly string[]).includes(
        scriptName
    );
}
