import { createTreeWithEmptyWorkspace } from '@nx/devkit/testing';
import { Tree, readProjectConfiguration } from '@nx/devkit';

import { schemaToTypescriptGenerator } from './generator';
import { SchemaToTypescriptGeneratorSchema } from './schema';

describe('schema-to-typescript generator', () => {
    let tree: Tree;
    const options: SchemaToTypescriptGeneratorSchema = { name: 'test' };

    beforeEach(() => {
        tree = createTreeWithEmptyWorkspace();
    });

    it('should run successfully', async () => {
        await schemaToTypescriptGenerator(tree, options);
        const config = readProjectConfiguration(tree, 'test');
        expect(config).toBeDefined();
    });
});
