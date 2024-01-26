import { formatFiles, readNxJson, Tree, updateNxJson } from '@nx/devkit';
import { addDependenciesToComposerJson } from '../../lib/composer/composer-json';
import { InitGeneratorSchema } from './schema';
import { logger } from '@nx/devkit';
import { spawnSync } from 'child_process';

function initMonorepoBuilderPhp(tree: Tree) {
    if (tree.exists('monorepo-builder.php')) {
        logger.log('SKIP monorepo-builder.php (already exists)');
        return;
    }

    tree.write(
        'monorepo-builder.php',
        `<?php

declare(strict_types=1);

use Symplify\\MonorepoBuilder\\Config\\MBConfig;

return static function (MBConfig $mbConfig): void {
    $mbConfig->packageDirectories([
        __DIR__ . '/libs'
    ]);
};
`
    );
}

export async function initGenerator(tree: Tree, options: InitGeneratorSchema) {
    addDependenciesToComposerJson(
        tree,
        {
            php: options.phpVersion ?? '^8.2',
        },
        {
            'phpunit/phpunit': '^10.5',
            'symplify/monorepo-builder': '^11.2',
            'meeva/composer-monorepo-builder-path-plugin': '^2.0',
        }
    );
    // 2. Generate/update .gitignore
    initMonorepoBuilderPhp(tree);
    // 4. Generate/update root phpunit.xml
    // 5. Generate/update root psalm.xml

    const nxJson = readNxJson(tree);
    if (nxJson) {
        nxJson.plugins = [
            { plugin: '@shrd/nx-php', options: {} },
            ...(nxJson?.plugins ?? []),
        ];

        updateNxJson(tree, nxJson);
    }

    await formatFiles(tree);

    return () => {
        spawnSync('composer', ['install'], {
            cwd: tree.root,
            stdio: 'inherit',
            env: process.env,
        });

        spawnSync('./vendor/bin/monorepo-builder', ['merge'], {
            cwd: tree.root,
            stdio: 'inherit',
            env: process.env,
        });

        spawnSync('composer', ['update'], {
            cwd: tree.root,
            stdio: 'inherit',
            env: process.env,
        });
    };
}

export default initGenerator;
