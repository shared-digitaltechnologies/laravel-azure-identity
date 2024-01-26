import { createTreeWithEmptyWorkspace } from '@nx/devkit/testing';
import { Tree, readProjectConfiguration } from '@nx/devkit';

import { phpunitGenerator } from './phpunitGenerator';
import { PhpunitGeneratorSchema } from './schema';

describe('phpunit generator', () => {
    let tree: Tree;
    const options: PhpunitGeneratorSchema = { name: 'test' };

    beforeEach(() => {
        tree = createTreeWithEmptyWorkspace();
    });

    it('should run successfully', async () => {
        await phpunitGenerator(tree, options);
        const config = readProjectConfiguration(tree, 'test');
        expect(config).toBeDefined();
    });
});
