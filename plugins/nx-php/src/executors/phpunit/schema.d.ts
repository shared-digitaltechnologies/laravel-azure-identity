/* eslint-disable */
/**
 * This file was automatically generated by json-schema-to-typescript.
 * DO NOT MODIFY IT BY HAND. Instead, modify the source JSONSchema file,
 * and run json-schema-to-typescript to regenerate this file.
 */

export interface PhpunitExecutorSchema {
  /**
   * Location of the phpunit binary (relative to the project root.)
   */
  cmd?: string;
  /**
   * The current working directory of the phpunit process. (defaults to the workspace root.)
   */
  cwd?: string;
  /**
   * The test files or directories to test.
   */
  files?: string[];
  /**
   * A PHP script that is included before the tests run
   */
  bootstrap?: string;
  /**
   * Read configuration from XML file.
   */
  configurationFile?: string;
  /**
   * Ignore default configuration file (phpunit.xml)
   */
  noConfiguration?: boolean;
  /**
   * Do not load PHPUnit extensions
   */
  noExtensions?: boolean;
  /**
   * Prepend PHP's include_path with the given paths.
   */
  includePath?: string[];
  /**
   * Set php.ini values for this test run.
   */
  phpConfig?: {
    [k: string]: string;
  };
  /**
   * Specify cache directory
   */
  cacheDirectory?: string;
  /**
   * Use baseline to ignore issues
   */
  useBaseline?: string;
  /**
   * Do not use baseline to ignore issues
   */
  ignoreBaseline?: boolean;
  /**
   * Only run tests from the specified test suites.
   */
  testsuites?: string[];
  /**
   * Exclude tests from the specified test suites.
   */
  excludeTestsuites?: string[];
  /**
   * Only run tests from the specified groups.
   */
  groups?: string[];
  /**
   * Exclude tests form the specified groups.
   */
  excludeGroups?: string[];
  /**
   * Only run tests that intend to cover the provided name.
   */
  covers?: string;
  /**
   * Only run tests that intend to use the provided name.
   */
  uses?: string;
  /**
   * Filter which tests to run
   */
  filter?: string;
  /**
   * Only search for test in files with the specified suffixes
   */
  testSuffix?: string[];
  /**
   * Run each test in a seperate PHP process
   */
  processIsolation?: boolean;
  /**
   * Backup and restore $GLOBALS for each test
   */
  globalsBackup?: boolean;
  /**
   * Backup and restore static properties for each test
   */
  staticBackup?: boolean;
  /**
   * Be strict about code coverage metadata
   */
  strictCoverage?: boolean;
  /**
   * Be strict about changes to global state
   */
  strictGlobalState?: boolean;
  /**
   * Be strict about output during tests
   */
  disallowTestOutput?: boolean;
  /**
   * Enforce time limit based on test size
   */
  enforceTimeLimit?: boolean;
  /**
   * Timeout in seconds for tests that have no declared size
   */
  defaultTimeLimit?: number;
  /**
   * Do not report tests that do not test anything
   */
  dontReportUselessTests?: boolean;
  /**
   * Stops after first ...
   */
  stopOn?: {
    /**
     * Stop after first error, failure, waring or risky test
     */
    defect?: boolean;
    /**
     * Stop after first error
     */
    error?: boolean;
    /**
     * Stop after first failure
     */
    failure?: boolean;
    /**
     * Stop after first warning
     */
    warning?: boolean;
    /**
     * Stop after first risky test
     */
    risky?: boolean;
    /**
     * Stop after first test that triggered a deprecation
     */
    deprecation?: boolean;
    /**
     * Stop after first test that triggered a notice
     */
    notice?: boolean;
    /**
     * Stop after first skipped test
     */
    skipped?: boolean;
    /**
     * Stop after first incomplete test
     */
    incomplete?: boolean;
  };
  /**
   * Signal failure using shell exit code when ...
   */
  failOn?: {
    /**
     * Signal failure using shell exit code when a warning was triggered
     */
    warning?: boolean;
    /**
     * Signal failure using shell exit code when a test was considered risky
     */
    risky?: boolean;
    /**
     * Signal failure using shell exit code when a notice was triggered
     */
    deprecation?: boolean;
    /**
     * Signal failure using shell exit code when a test was skipped
     */
    skipped?: boolean;
    /**
     * Signal failure using shell exit code when a test was marked incomplete
     */
    incomplete?: boolean;
  };
  /**
   * Write test results to cache file
   */
  cacheResult?: boolean;
  /**
   * Do not write test results to cache file
   */
  doNotCacheResult?: boolean;
  /**
   * Run tests in order
   */
  orderBy?: "default" | "defects" | "depends" | "duration" | "no-depends" | "random" | "reverse" | "size";
  /**
   * Use the specified random seed when running tests in random order
   */
  randomOrderSeed?: number;
  /**
   * Use colors in output
   */
  colors?: "never" | "auto" | "always";
  /**
   * Number of columns to use for progress output
   */
  columns?: number;
  /**
   * Write to STDERR instead of STDOUT
   */
  stderr?: boolean;
  /**
   * Disable output for test execution progress
   */
  noProgress?: boolean;
  /**
   * Disable output for test results
   */
  noResults?: boolean;
  /**
   * Disable all output
   */
  noOutput?: boolean;
  /**
   * Display details for certain tests
   */
  display?: {
    /**
     * Display details for incomplete tests
     */
    incomplete?: boolean;
    /**
     * Display details for skipped tests
     */
    skipped?: boolean;
    /**
     * Display details for deprecations triggered by tests
     */
    deprecations?: boolean;
    /**
     * Display details for errors triggered by tests
     */
    errors?: boolean;
    /**
     * Display details for notices triggerd by tests
     */
    notices?: boolean;
    /**
     * Display details for warings triggerd by tests
     */
    warnings?: boolean;
    /**
     * Print defects in reverse order
     */
    reverse?: boolean;
  };
  /**
   * Replace default progress and result output with TeamCity format
   */
  teamCity?: boolean;
  /**
   * Replace default result output with TestDox format
   */
  testDox?: boolean;
  /**
   * Replace default progress and result output with debugging information
   */
  debug?: boolean;
  /**
   * Write test results to files
   */
  log?: {
    /**
     * Write test results in JUnit XML format to file
     */
    junit?: string;
    /**
     * Write test results in TeamCity format to file
     */
    teamcity?: string;
    /**
     * Write test results in TestDox format (HTML) to file
     */
    testdoxHtml?: string;
    /**
     * Write test results in TestDox format (plain text) to file
     */
    testdoxText?: string;
    /**
     * Stream events as plain text to file
     */
    eventsText?: string;
    /**
     * Stream events as plain text with extended information to file
     */
    eventsVerboseText?: string;
  };
  /**
   * Ignore logging configured in the XML configuration file
   */
  noLogging?: boolean;
  /**
   * Write code coverage report to files
   */
  coverage?: {
    /**
     * Write code coverage report in Clover XML format to file
     */
    clover?: string;
    /**
     * Write code coverage report in Cobertura XML format to file
     */
    cobertura?: string;
    /**
     * Write code coverage report in Crap4J XML format to file
     */
    crap4j?: string;
    /**
     * Write code coverage report in HTML format to directory
     */
    html?: string;
    /**
     * Write serialized code coverage data to file
     */
    php?: string;
    /**
     * Write code coverage report in text format to file
     */
    text?: string;
    /**
     * Write code coverage report in XML format to directory
     */
    xml?: string;
  };
  /**
   * Warm static analysis cache
   */
  warmCoverageCache?: string;
  /**
   * Include directory in code coverage reporting
   */
  coverageFilter?: string;
  /**
   * Report path coverage in addition to line coverage
   */
  pathCoverage?: boolean;
  /**
   * Disable metadata for ignoring code coverage
   */
  disableCoverageIgnore?: boolean;
  /**
   * Ignore code coverage reporting configured in the XML configuration file
   */
  noCoverage?: boolean;
  [k: string]: unknown;
}