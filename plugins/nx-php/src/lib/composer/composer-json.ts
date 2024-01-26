import {
    GeneratorCallback,
    Tree,
    readJson,
    updateJson,
    writeJson,
} from '@nx/devkit';
import {
    Package as ComposerJson,
    Authors as ComposerJsonAuthors,
} from '../../schemas/composer.schema';
import { PackageJson as BasePackageJson } from 'nx/src/utils/package-json';
import * as path from 'path';
import * as _ from 'lodash';
import { spawnSync } from 'child_process';
import { Autoload } from '../../schemas/composer.schema';

type PackageJsonAuthor =
    | { name: string; email?: string; url?: string }
    | string;

type PackageJson = BasePackageJson & {
    description?: string;
    keywords?: string[];
    homepage?: string;
    author?: PackageJsonAuthor;
    repository?: string;
};

/**
 * Creates the baic contents of a `composer.json` file.
 */
export function createComposerJson(packageName: string): ComposerJson {
    return {
        name: packageName,
        version: '0.1.0',
        description: 'Generated Composer Package',
        license: 'UNLICENCED',
        keywords: [],
        readme: 'README.md',
        require: {},
        'require-dev': {},
        config: {
            'preferred-install': 'dist',
            'sort-packages': true,
            'allow-plugins': {
                'pestphp/pest-plugin': true,
                'php-http/discovery': true,
                'shrd/*': true,
                'meeva/composer-monorepo-builder-path-plugin': true,
            },
            lock: false,
        },
        'minimum-stability': 'dev',
        'prefer-stable': true,
    };
}

/**
 * Returns the composer json at the root of the workspace.
 */
export function getRootComposerJson(tree: Tree): ComposerJson {
    return readJson(tree, 'composer.json');
}

/**
 * Converts the package.json author a value for authors in a composer.json file.
 */
export function packageJsonAuthorToComposerJsonAuthors(
    author: PackageJsonAuthor | null | undefined
): ComposerJsonAuthors {
    if (!author) return [];

    if (typeof author === 'string') {
        return [{ name: author }];
    }

    return [
        {
            name: author.name,
            email: author.email,
            homepage: author.url,
        },
    ];
}

/**
 * Creates a new composer.json file based on a package.json file.
 */
export function composerJsonFromPackageJson(
    packageJson: PackageJson
): ComposerJson {
    let name = packageJson.name;
    if (name.startsWith('@')) {
        name = name.substring(1);
    }
    const result = createComposerJson(name);

    result.version = packageJson.version ?? result.version;
    result.description = packageJson.description ?? result.description;
    result.license = packageJson.license ?? result.license;
    result.authors = packageJsonAuthorToComposerJsonAuthors(packageJson.author);
    result.keywords = packageJson.keywords ?? [];
    result.homepage = packageJson.homepage;
    result.support = {
        source: packageJson.repository,
    };

    return result;
}

/**
 * Gets the package.json file that is next to the composer.json file.
 */
function packageJsonPathFromComposerJsonPath(composerJsonPath: string): string {
    const dir = path.dirname(composerJsonPath);
    if (dir === '.') return 'package.json';
    return path.join(dir, 'package.json');
}

export function ensureComposerJsonExists(
    tree: Tree,
    composerJsonPath?: string,
    override?: Partial<ComposerJson>
): void {
    composerJsonPath ??= 'composer.json';

    if (!tree.exists(composerJsonPath)) {
        const packageJsonPath =
            packageJsonPathFromComposerJsonPath(composerJsonPath);

        let result: ComposerJson;

        if (tree.exists(packageJsonPath)) {
            const packageJson = readJson(tree, packageJsonPath);
            result = composerJsonFromPackageJson(packageJson);
        } else {
            result = createComposerJson(
                'shrd/' + path.basename(path.dirname(composerJsonPath))
            );
        }

        if (override) {
            result = _.merge(result, override);
        }

        writeJson(tree, composerJsonPath, result);
    }
}

/**
 * Adds a `replace` item to a composer.json file
 */
export function addReplaceToComposerJson(
    tree: Tree,
    packageName: string,
    version = 'self.version',
    composerJsonPath: string = 'composer.json'
): void {
    ensureComposerJsonExists(tree, composerJsonPath);

    updateJson(tree, composerJsonPath, (original: ComposerJson) => {
        original.replace = {
            ...original.replace,
            [packageName]: version,
        };

        return original;
    });
}

/**
 * Adds `require` and `require-dev` to a composer.json file.
 */
export function addDependenciesToComposerJson(
    tree: Tree,
    require: Record<string, string>,
    requireDev: Record<string, string>,
    composerJsonPath = 'composer.json',
    useRootComposerJsonVersions = false
): GeneratorCallback {
    ensureComposerJsonExists(tree, composerJsonPath);

    if (useRootComposerJsonVersions) {
        require = getDefaultComposerPackageVersions(tree, require);
        requireDev = getDefaultComposerPackageVersions(tree, requireDev);
    }

    updateJson(tree, composerJsonPath, (original: ComposerJson) => {
        original.require = {
            ...(original.require ?? {}),
            ...require,
        };

        original['require-dev'] = {
            ...(original['require-dev'] ?? {}),
            ...requireDev,
        };

        return original;
    });

    const cwd = path.dirname(path.resolve(tree.root, composerJsonPath));

    return () => {
        spawnSync('composer', ['install', '--ansi', '--no-interaction'], {
            cwd,
            stdio: 'inherit',
            env: process.env,
        });
    };
}

/**
 * Adds `autoload` and `autoload-dev` to a composer.json file.
 */
export function addAutoloadToComposerJson(
    tree: Tree,
    autoload: Autoload,
    autoloadDev: ComposerJson['autoload-dev'],
    composerJsonPath = 'composer.json'
): GeneratorCallback {
    ensureComposerJsonExists(tree, composerJsonPath);

    updateJson(tree, composerJsonPath, (original: ComposerJson) => {
        original.autoload = mergeAutoload(original.autoload, autoload);
        original['autoload-dev'] = mergeAutoload(
            original['autoload-dev'],
            autoloadDev
        );
        return original;
    });

    const cwd = path.dirname(path.resolve(tree.root, composerJsonPath));

    return () => {
        spawnSync('composer', ['dump-autoload'], {
            cwd,
            stdio: 'inherit',
            env: process.env,
        });
    };
}

/**
 * Returns the version of a dependency in a composer.json file.
 */
export function getComposerPackageVersion(
    composerJson: ComposerJson,
    packageName: string
): string | undefined {
    if (composerJson.require && packageName in composerJson.require) {
        return composerJson.require[packageName];
    }

    if (
        composerJson['require-dev'] &&
        packageName in composerJson['require-dev']
    ) {
        return composerJson['require-dev'][packageName];
    }

    return undefined;
}

export function useComposerJsonPackageVersionsOrDefault(
    composerJson: ComposerJson,
    packages: Record<string, string>
): Record<string, string> {
    return Object.fromEntries(
        Object.entries(packages).map(([k, v]) => [
            k,
            getComposerPackageVersion(composerJson, k) ?? v,
        ])
    );
}

export function getDefaultComposerPackageVersions(
    tree: Tree,
    packages: Record<string, string>
): Record<string, string> {
    return tree.exists('composer.json')
        ? useComposerJsonPackageVersionsOrDefault(
              readJson(tree, 'composer.json'),
              packages
          )
        : packages;
}

function simplifyAutoloadEntry(
    entry: string[] | string | undefined
): string[] | string | undefined {
    if (Array.isArray(entry)) {
        if (entry.length === 0) return undefined;
        if (entry.length === 1) return entry[0];
        return _.uniq(entry);
    } else {
        return entry;
    }
}

function mergeAutoloadPsrEntry(
    a: string[] | string | undefined,
    b: string[] | string | undefined
): string[] | string | undefined {
    if (!a) return b;
    if (!b) return a;

    return simplifyAutoloadEntry([
        ...(Array.isArray(a) ? a : [a]),
        ...(Array.isArray(b) ? b : [b]),
    ]);
}

function mergeAutoloadPsrEntries(
    a: Autoload['psr-0' | 'psr-4'] | undefined,
    b: Autoload['psr-0' | 'psr-4'] | undefined
): Autoload['psr-0' | 'psr-4'] | undefined {
    if (!a) return b;
    if (!b) return a;

    return Object.fromEntries(
        _.uniq([...Object.keys(a), ...Object.keys(b)])
            .sort()
            .map((namespace) => [
                namespace,
                mergeAutoloadPsrEntry(a[namespace], b[namespace]),
            ])
            .filter(([, v]) => !!v)
    );
}

function mergeArrayUnique<T>(
    a: T[] | undefined,
    b: T[] | undefined
): T[] | undefined {
    if (!a) return a;
    if (!b) return b;

    return _.uniq([...a, ...b]);
}

export function mergeAutoload(
    a: Autoload | undefined,
    b: Autoload | undefined
): Autoload | undefined {
    if (!a) return b;
    if (!b) return a;

    return {
        ...a,
        ...b,
        'psr-0': mergeAutoloadPsrEntries(a['psr-0'], b['psr-0']),
        'psr-4': mergeAutoloadPsrEntries(a['psr-4'], b['psr-4']),
        files: mergeArrayUnique(a['files'], b['files']),
        classmap: mergeArrayUnique(a['classmap'], b['classmap']),
        'exclude-from-classmap': mergeArrayUnique(
            a['exclude-from-classmap'],
            b['exclude-from-classmap']
        ),
    };
}

export function getRootPhpVersion(tree: Tree): string {
    return readJson(tree, 'composer.json')?.require?.php ?? '^8.2';
}

export function vendorDirFromComposerJson(
    tree: Tree,
    composerJsonPath = 'composer.json'
): string {
    if (tree.exists(composerJsonPath)) {
        const composerJson: ComposerJson = readJson(tree, composerJsonPath);

        const vendorDir = composerJson.config?.['vendor-dir'];
        if (vendorDir)
            return path.resolve(path.dirname(composerJsonPath), vendorDir);
    }

    return 'vendor';
}

export function deriveSourceNamespaceFromComposerJson(
    tree: Tree,
    composerJsonPath: string
): string {
    if (!tree.exists(composerJsonPath))
        throw new Error(
            `Could not derive source namespace. '${composerJsonPath}' does not exist.`
        );

    const composerJson: ComposerJson = readJson(tree, composerJsonPath);

    const psr4 = composerJson.autoload?.['psr-4'];
    if (!psr4)
        throw new Error(
            `Could not derive source namespace. '${composerJsonPath}' has no 'autoload.psr-4'.`
        );

    const entries = Object.entries(psr4);
    if (entries.length === 0)
        throw new Error(
            `Could not derive source namespace. '${composerJsonPath}' has no 'autoload.psr-4' entries.`
        );

    let namespace = entries[0][0];
    if (!namespace.endsWith('\\')) {
        namespace += '\\';
    }

    return namespace;
}
