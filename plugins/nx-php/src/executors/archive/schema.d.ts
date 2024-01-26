export interface ArchiveExecutorSchema {
    outputFile?: string;
    package?: string;
    version?: string;
    ignoreFilters?: boolean;
    noPlugins?: boolean;
    noScripts?: boolean;
    noCache?: boolean;
}
