import { PhpunitExecutorSchema } from './schema';
import executor from './executor';

const options: PhpunitExecutorSchema = {};

describe('Phpunit Executor', () => {
    it('can run', async () => {
        const output = await executor(options);
        expect(output.success).toBe(true);
    });
});
