/* eslint-disable */

export interface PsalmExecutorSchema {
    /**
     * The path to the psalm executable.
     */
    cmd?: string;
    /**
     * The cwd for the psalm process.
     */
    cwd?: string;
    /**
     * The files to typecheck
     */
    files?: string[];
    /**
     * Path to a psalm configuration file.
     */
    config?: string;
    /**
     * Use PHP-provided ini defaults for memory and error display.
     */
    useIniDefaults?: boolean;
    /**
     * Use a specific memory limit. Cannot be combined with useIniDefaults.
     */
    memoryLimit?: string;
    /**
     * Use to disable certain extensions while Psalm is running.
     */
    disableExtensions?: string[];
    /**
     * If greater than one, Psalm will run analysis on multiple threats, speeding things up.
     */
    threads?: number;
    /**
     * Turns off Psalm's diff mode, checks all files regardless of whether they've changed.
     */
    noDiff?: boolean;
    /**
     * Explicitly set PHP version to analyse code against.
     */
    phpVersion?: string;
    /**
     * Set the error reporting level.
     */
    errorLevel?: number;
    /**
     * Show non-exception parser findings (defaults to false).
     */
    showInfo?: boolean;
    /**
     * Show code snippets with errors
     */
    showSnippet?: boolean;
    /**
     * Look for unused code. Options are 'auto' or 'always'. If no value is specified, default is 'auto'.
     */
    findDeadCode?: 'auto' | 'always';
    /**
     * Look for unused code. Options are 'auto' or 'always'. If no value is specified, default is 'auto'.
     */
    findUnusedCode?: 'auto' | 'always';
    /**
     * find all @psalm-suppress annotations that aren't used.
     */
    findUnusedPsalmSuppress?: boolean;
    /**
     * Searches the codebase for references to the given fully-qualified class or method, where method is in the format class::methodName
     */
    findReferencesTo?: string;
    /**
     * Hide suggestions
     */
    noSuggestions?: boolean;
    /**
     * Run Psalm in taint analysis mode - see https://psalm.dev/docs/security_analysis for more info
     */
    taintAnalysis?: boolean;
    /**
     * Output the taint graph using the DOT language – requires taintAnalysis=true.
     */
    dumpTaintGraph?: string;
    /**
     * Save all current error level issues to a file, to mark them as info in subsequent runs. Add --include-php-versions to also include a list of PHP extension versions
     */
    setBaseline?: string;
    /**
     * Allows you to use a baseline other than the default baseline provided in your config
     */
    useBaseline?: string;
    /**
     * Ignore the error baseline
     */
    ignoreBaseline?: boolean;
    /**
     * Update the baseline by removing fixed issues. This will not add new issues to the baseline.  Add --include-php-versions to also include a list of PHP extension versions.
     */
    updateBaseline?: boolean;
    /**
     * Executes a plugin, an alternative to using the Psalm config
     */
    plugin?: string;
    /**
     * Enable monochrome output
     */
    monochrome?: boolean;
    /**
     * Changes the output format.
     */
    outputFormat?:
        | 'compact'
        | 'console'
        | 'text'
        | 'emacs'
        | 'json'
        | 'pylint'
        | 'xml'
        | 'checkstyle'
        | 'junit'
        | 'sonarqube'
        | 'github'
        | 'phpstorm'
        | 'codeclimate'
        | 'by-issue-level';
    /**
     * Disable the progress indicator
     */
    noProgress?: boolean;
    /**
     * Use a progress indicator suitable for Continuous Integration logs
     */
    longProgress?: boolean;
    /**
     * Shows a breakdown of Psalm’s ability to infer types in the codebase
     */
    stats?: boolean;
    /**
     * The path where to output report file. The output format is based on the file extension. (Currently supported formats: ".json", ".xml", ".txt", ".emacs", ".pylint", ".console", ".sarif", "checkstyle.xml", "sonarqube.json", "codeclimate.json", "summary.json", "junit.xml")
     */
    report?: string;
    /**
     * Whether the report should include non-errors in its output.
     */
    reportShowInfo?: boolean;
    /**
     * Clears all cache files that Psalm uses for this specific project
     */
    clearCache?: boolean;
    /**
     * Clears all cache files that Psalm uses for all projects
     */
    clearGlobalCache?: boolean;
    /**
     * Runs Psalm without using cache
     */
    noCache?: boolean;
    /**
     * Runs Psalm without using cached representations of unchanged classes and files. Useful if you want the afterClassLikeVisit plugin hook to run every time you visit a file.
     */
    noReflectionCache?: boolean;
    /**
     * Runs Psalm without using caching every single file for later diffing. This reduces the space Psalm uses on disk and file I/O.
     */
    noFileCache?: boolean;
    /**
     * Debug information
     */
    debug?: boolean;
    /**
     * Debug information on a line-by-line level
     */
    debugByLine?: boolean;
    /**
     * Print a php backtrace to stderr when emitting issues.
     */
    debugEmittedIssues?: boolean;
    /**
     * If running Psalm globally you’ll need to specify a project root. Defaults to cwd
     */
    root?: boolean;
    /**
     * Generate a map of node references and types in JSON format, saved to the given path.
     */
    generateJsonMap?: string;
    /**
     * Generate stubs for the project and dump the file in the given path
     */
    generateStubs?: string;
    shepherd?: string;
}
