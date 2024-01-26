import {
    formatFiles,
    generateFiles,
    getProjects,
    Tree,
    updateProjectConfiguration,
} from '@nx/devkit';
import * as path from 'path';
import { PsalmGeneratorSchema } from './schema';
import {
    addDependenciesToComposerJson,
    vendorDirFromComposerJson,
} from '../../lib/composer/composer-json';

export async function psalmGenerator(
    tree: Tree,
    options: PsalmGeneratorSchema
) {
    const projectName = options.project;
    const project = getProjects(tree).get(projectName);
    if (!project)
        throw new Error(
            `Could not find project '${projectName}' in workspace tree.`
        );

    const composerJsonPath =
        options.composerJson ?? path.join(project.root, 'composer.json');

    const vendorDir =
        options.vendorDir ?? vendorDirFromComposerJson(tree, composerJsonPath);

    const sourceDir =
        options.sourceDir ??
        project.sourceRoot ??
        path.join(project.root, 'src');

    const cacheDir = path.join('.psalm/cache', project.root);

    addDependenciesToComposerJson(
        tree,
        {},
        { 'psalm/phar': '^5.20' },
        composerJsonPath,
        true
    );

    addDependenciesToComposerJson(
        tree,
        {},
        { 'psalm/phar': '^5.20' },
        'composer.json',
        true
    );

    updateProjectConfiguration(tree, projectName, {
        ...project,
        targets: {
            ...(project.targets ?? {}),
            typecheck: {
                executor: '@shrd/nx-php:psalm',
                cache: true,
                options: {
                    cmd: './vendor/bin/psalm.phar',
                    config: path.join(project.root, 'psalm.xml'),
                },
                configurations: {
                    default: {
                        showInfo: true,
                        ignoreBaseline: true,
                    },
                    ci: {
                        longProgress: true,
                    },
                },
                defaultConfiguration: 'default',
                inputs: ['default', '^production'],
                outputs: [`{workspaceRoot}/.psalm/cache/{projectRoot}`],
                dependsOn: [],
            },
        },
    });

    generateFiles(tree, path.join(__dirname, 'files'), project.root, {
        relativeVendorDir: path.relative(project.root, vendorDir),
        relativeSourceRoot: path.relative(project.root, sourceDir),
        relativeCacheDir: path.relative(project.root, cacheDir),
    });

    await formatFiles(tree);
}

export default psalmGenerator;
