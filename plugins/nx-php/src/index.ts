import {
    CreateDependencies,
    CreateNodes,
    DependencyType,
    RawProjectGraphDependency,
    readJsonFile,
} from '@nx/devkit';
import { Package } from './schemas/composer.schema';
import * as path from 'path';
import { projectConfigurationFromComposerPackage } from './lib/composer/composer-project';
import { existsSync } from 'fs';

export const createNodes: CreateNodes = [
    '**/composer.json',
    (projectConfigurationFile) => {
        const composerJson: Package = readJsonFile(projectConfigurationFile);

        console.log({ projectConfigurationFile });

        const root = path.dirname(projectConfigurationFile);

        const projectConf = projectConfigurationFromComposerPackage(
            composerJson,
            root
        );

        console.log({ [root]: projectConf });

        return {
            projects: {
                [root]: projectConf,
            },
        };
    },
];

type ComposerProject = {
    name: string;
    composerJson: Package;
    composerJsonPath: string;
};

export const createDependencies: CreateDependencies = (_opts, context) => {
    const composerPackageToProject = new Map<string, string>();

    const composerProjects: ComposerProject[] = [];

    for (const [name, project] of Object.entries(context.projects)) {
        const composerJsonPath = path.join(
            context.workspaceRoot,
            project.root,
            'composer.json'
        );

        if (existsSync(composerJsonPath)) {
            const composerJson = readJsonFile(composerJsonPath);

            composerProjects.push({
                name,
                composerJson,
                composerJsonPath: path.join(project.root, 'composer.json'),
            });

            if (composerJson.name) {
                composerPackageToProject.set(composerJson.name, name);
            }
        }
    }

    const dependencies: RawProjectGraphDependency[] = [];

    for (const { name, composerJson, composerJsonPath } of composerProjects) {
        [
            ...Object.keys(composerJson.require ?? {}),
            ...Object.keys(composerJson['require-dev'] ?? {}),
        ]
            .map((v) => composerPackageToProject.get(v))
            .filter(Boolean)
            .forEach((target) => {
                dependencies.push({
                    type: DependencyType.static,
                    source: name,
                    target: target as string,
                    sourceFile: composerJsonPath,
                });
            });
    }

    return dependencies;
};
