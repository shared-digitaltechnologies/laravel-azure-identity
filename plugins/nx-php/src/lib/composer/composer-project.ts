import {
    ProjectConfiguration,
    ProjectType,
    TargetConfiguration,
} from '@nx/devkit';
import { Autoload, Package } from '../../schemas/composer.schema';
import { isSpecialComposerCommand } from './constants';
import * as path from 'path';

function extractExtraNx(conf: Package): Partial<ProjectConfiguration> {
    const extra = conf.extra;
    if (!extra || !('nx' in extra)) return {};

    const nx = extra.nx;

    if (!nx || typeof nx !== 'object' || Array.isArray(nx)) return {};

    return nx as Partial<ProjectConfiguration>;
}

export function projectNameFromComposerPackage(
    conf: Package
): string | undefined {
    const nx = extractExtraNx(conf);

    return nx.name ?? conf.name;
}

export function projectTagsFromComposerPackage(conf: Package): string[] {
    const nx = extractExtraNx(conf);

    const keywordTags = (conf.keywords ?? []).map((v) => `composer:${v}`);

    return ['composer', ...(nx.tags ?? []), ...keywordTags];
}

export function projectTypeFromComposerPackage(
    conf: Package
): ProjectType | undefined {
    const nx = extractExtraNx(conf);

    if (nx.projectType) return nx.projectType;

    const type = conf.type;
    if (!type) return undefined;

    if (['project', 'app', 'application'].includes(type)) return 'application';

    return 'library';
}

function prependProjectRootToAutoloadPath(autoloadPath: string): string {
    if (autoloadPath[0] !== '/') autoloadPath = `/${autoloadPath}`;
    return `{projectRoot}${autoloadPath}`;
}

export function inputDefinitionsFromComposerAutoload(
    autoload?: Autoload
): string[] {
    if (!autoload) return [];

    const include = [
        ...Object.values(autoload['psr-0'] ?? {}),
        ...Object.values(autoload['psr-4'] ?? {}),
        ...(autoload.classmap ?? []),
        ...(autoload.files ?? []),
    ] as string[];

    const exclude = (autoload['exclude-from-classmap'] ?? []) as string[];

    return [
        ...include.map(prependProjectRootToAutoloadPath),
        ...exclude
            .map(prependProjectRootToAutoloadPath)
            .map((path) => `!${path}`),
    ];
}

export function archiveInputsFromComposerPackage(conf: Package): string[] {
    const archiveExcludes: string[] = (
        (conf.archive?.exclude ?? []) as string[]
    ).map((rule) => {
        const negative = rule.startsWith('!');

        if (negative) rule = rule.substring(1);

        if (!rule.startsWith('/')) rule = `/**/${rule}`;
        if (rule.endsWith('/')) rule = `${rule}/**/*`;

        if (negative) return `{projectRoot}${rule}`;
        return `!{projectRoot}/${rule}`;
    });

    return ['{projectRoot}/**/*', ...archiveExcludes];
}

export function namedInputsFromComposerPackage(
    conf: Package
): ProjectConfiguration['namedInputs'] {
    const nx = extractExtraNx(conf);

    return {
        ...(nx.namedInputs ?? {}),
        archive: archiveInputsFromComposerPackage(conf),
        autoload: inputDefinitionsFromComposerAutoload(conf.autoload),
        'autoload-dev': [
            'autoload',
            ...inputDefinitionsFromComposerAutoload(conf['autoload-dev']),
        ],
    };
}

export function archiveBaseNameFromComposerPackage(
    conf: Package
): string | undefined {
    const fromArchiveOption = conf.archive?.name;
    if (fromArchiveOption) return fromArchiveOption;

    if (!conf.name) return undefined;

    const [, packageName] = conf.name.split('/', 2);

    if (!conf.version) return packageName;

    return `${packageName}-${conf.version}`;
}

export function archiveOutputFileNameFromComposerPackage(
    conf: Package
): string | undefined {
    const baseName = archiveBaseNameFromComposerPackage(conf);

    const extension = conf.config?.['archive-format'] ?? 'tar';

    const dir = conf.config?.['archive-dir'] ?? '.';

    return path.join(dir, `${baseName}.${extension}`);
}

export function archiveTargetFromComposerPackage(
    conf: Package,
    projectRoot: string
): TargetConfiguration | undefined {
    const outputFilename = archiveOutputFileNameFromComposerPackage(conf);
    if (!outputFilename) return undefined;

    const outputFile = path.join(projectRoot, outputFilename);

    return {
        executor: '@shrd/nx-php:archive',
        cache: true,
        options: {
            outputFile,
        },
        inputs: ['archive'],
        outputs: [path.join('{workspaceRoot}', outputFile)],
    };
}

export function sourceRootFromComposerPackage(
    conf: Package,
    projectRoot: string
): string | undefined {
    const nx = extractExtraNx(conf);

    if (nx.sourceRoot) return nx.sourceRoot;

    if (nx.root) projectRoot = nx.root;

    const candidate = Object.values(conf.autoload?.['psr-4'] ?? {}).flatMap(
        (dirs) => (Array.isArray(dirs) ? dirs : [dirs])
    )[0];

    if (!candidate) return undefined;

    return path.join(projectRoot, candidate);
}

export function targetsFromComposerPackage(
    conf: Package,
    projectRoot: string
): ProjectConfiguration['targets'] {
    const nx = extractExtraNx(conf);

    if (nx.root) projectRoot = nx.root;

    const scripts: string[] = [
        ...Object.keys(conf.scripts ?? {}).filter(
            (value) => !isSpecialComposerCommand(value)
        ),
        ...Object.keys(conf['scripts-aliases'] ?? {}),
    ];

    const scriptTargets: Record<string, TargetConfiguration> =
        Object.fromEntries(
            scripts.map((script) => [
                script,
                {
                    executor: 'nx:run-commands',
                    options: {
                        cwd: projectRoot,
                        commands: [`composer run-script --ansi -- ${script}`],
                        color: true,
                        forwardAllArgs: true,
                    },
                },
            ])
        );

    const archive = archiveTargetFromComposerPackage(conf, projectRoot);

    return {
        ...(archive ? { archive } : {}),
        ...scriptTargets,
        ...(nx.targets ?? {}),
    };
}

export function projectConfigurationFromComposerPackage(
    conf: Package,
    projectRoot: string
): ProjectConfiguration {
    return {
        root: projectRoot,
        ...extractExtraNx(conf),
        name: projectNameFromComposerPackage(conf),
        sourceRoot: sourceRootFromComposerPackage(conf, projectRoot),
        targets: targetsFromComposerPackage(conf, projectRoot),
        projectType: projectTypeFromComposerPackage(conf),
        namedInputs: namedInputsFromComposerPackage(conf),
        tags: projectTagsFromComposerPackage(conf),
    };
}
