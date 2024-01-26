import { spawnSync } from 'child_process';

export function composerUpdate() {
    spawnSync('composer', ['update'], {
        stdio: 'inherit',
    });
}
