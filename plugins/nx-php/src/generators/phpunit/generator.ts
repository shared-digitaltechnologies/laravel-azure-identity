import {
    formatFiles,
    generateFiles,
    getProjects,
    Tree,
    updateProjectConfiguration,
} from '@nx/devkit';
import * as path from 'path';
import {
    addDependenciesToComposerJson,
    addAutoloadToComposerJson,
    vendorDirFromComposerJson,
    deriveSourceNamespaceFromComposerJson,
} from '../../lib/composer/composer-json';
import { PhpunitGeneratorSchema } from './schema';

export async function phpunitGenerator(
    tree: Tree,
    options: PhpunitGeneratorSchema
) {
    const projectName = options.project;
    const project = getProjects(tree).get(projectName);
    if (!project) throw new Error(`Project ${projectName} not found!`);

    const composerJsonFile = path.join(project.root, 'composer.json');

    let testNamespace = options.testNamespace;
    if (!testNamespace) {
        const srcNamespace = deriveSourceNamespaceFromComposerJson(
            tree,
            composerJsonFile
        );

        testNamespace = srcNamespace + 'Tests\\';
    }

    const vendorDir =
        options.vendorDir ?? vendorDirFromComposerJson(tree, composerJsonFile);

    const sourceDir =
        options.sourceDir ??
        project.sourceRoot ??
        path.join(project.root, 'src');

    const coverageDir = path.join('coverage', project.root);
    const cacheDir = path.join('.phpunit.cache', project.root);

    // Update composer json
    addDependenciesToComposerJson(
        tree,
        {},
        {
            'phpunit/phpunit': '^10.5',
        },
        composerJsonFile,
        true
    );

    addAutoloadToComposerJson(
        tree,
        {},
        {
            'psr-4': {
                [testNamespace + '\\']: 'tests/',
            },
        },
        composerJsonFile
    );

    // Update root composer json
    addDependenciesToComposerJson(
        tree,
        {},
        {
            'phpunit/phpunit': '^10.5',
        },
        'composer.json',
        true
    );

    addAutoloadToComposerJson(
        tree,
        {},
        {
            'psr-4': {
                [testNamespace + '\\']: path.join(project.root, 'tests/'),
            },
        }
    );

    // Update project configuration
    updateProjectConfiguration(tree, projectName, {
        ...project,
        targets: {
            ...(project.targets ?? {}),
            test: {
                executor: '@shrd/nx-php:phpunit',
                options: {
                    configurationFile: path.join(project.root, 'phpunit.xml'),
                    bootstrap: path.join(vendorDir, 'autoload.php'),
                    testsuites: ['Unit'],
                    colors: 'always',
                },
                configurations: {
                    default: {
                        display: {
                            deprecations: true,
                            notices: true,
                            warnings: true,
                            errors: true,
                        },
                        cacheResult: true,
                        orderBy: 'defects',
                    },
                    ci: {
                        noProgress: true,
                        doNotCacheResult: true,
                        orderBy: 'random',
                    },
                },
                defaultConfiguration: 'default',
                dependsOn: [],
                inputs: ['default', '^production'],
                outputs: ['{workspaceRoot}/coverage/{projectRoot}'],
            },
        },
    });

    generateFiles(tree, path.join(__dirname, 'files'), project.root, {
        relativeVendorDir: path.relative(project.root, vendorDir),
        relativeSourceDir: path.relative(project.root, sourceDir),
        relativeCacheDir: path.relative(project.root, cacheDir),
        relativeCoverageDir: path.relative(project.root, coverageDir),
        testNamespace,
    });
    await formatFiles(tree);
}

export default phpunitGenerator;
