import { SchemaToTypescriptExecutorSchema } from './schema';
import { compile, compileFromFile } from 'json-schema-to-typescript';

export default async function runExecutor(
    options: SchemaToTypescriptExecutorSchema
) {
    const tsFile = await compileFromFile(options.schemaFile);


    return {
        success: true,
    };
}
