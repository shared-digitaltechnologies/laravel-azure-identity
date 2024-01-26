export interface LibraryGeneratorSchema {
    name: string;
    directory: string;
    packageNamespace: string;
    namespace: string;
    testing: 'phpunit' | 'pest' | 'none';
    typechecker: 'psalm' | 'phpstan' | 'none';
}
