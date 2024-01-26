import { ArchiveExecutorSchema } from './schema';
import executor from './executor';

const options: ArchiveExecutorSchema = {};

describe('Archive Executor', () => {
    it('can run', async () => {
        const output = await executor(options);
        expect(output.success).toBe(true);
    });
});
