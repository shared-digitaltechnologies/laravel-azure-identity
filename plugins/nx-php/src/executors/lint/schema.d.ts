export interface LintExecutorSchema {
    patterns: string[];
    ignore?: string[];
    cwd?: string;
    dot?: boolean;
    deep?: number;
    followSymbolicLinks?: boolean;
    bail?: boolean;
}
