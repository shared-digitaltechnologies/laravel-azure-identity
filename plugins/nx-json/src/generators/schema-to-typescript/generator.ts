import { formatFiles, Tree } from '@nx/devkit';
import * as path from 'path';
import { SchemaToTypescriptGeneratorSchema } from './schema';
import { compileFromFile } from 'json-schema-to-typescript';

export async function schemaToTypescriptGenerator(
    tree: Tree,
    options: SchemaToTypescriptGeneratorSchema
) {
    const filePath = path.join(tree.root, options.schema);

    const fileBaseName = path.basename(filePath, '.json');
    const tsFileName = fileBaseName + '.d.ts';
    const tsFilePath = path.join(path.dirname(options.schema), tsFileName);

    const tsTypes = await compileFromFile(filePath);

    tree.write(tsFilePath, tsTypes);
    await formatFiles(tree);
}

export default schemaToTypescriptGenerator;
