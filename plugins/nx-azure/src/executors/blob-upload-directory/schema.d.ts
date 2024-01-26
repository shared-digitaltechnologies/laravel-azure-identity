export interface BlobUploadDirectoryExecutorSchema {
    directory: string;
    include: string[];
    exclude: string[];
    storageAccount: string;
    containerName: string;
    braceExpansion: boolean;
    includeDotFiles: boolean;
    contentTypes: Record<string, string>;
    blobStorageAccountConnectionString: string;
}
