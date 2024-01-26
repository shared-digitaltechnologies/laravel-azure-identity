import { DefaultAzureCredential } from '@azure/identity';
import {
    BlobServiceClient,
    ContainerClient,
    type BlockBlobClient,
} from '@azure/storage-blob';
import { AbortController } from '@azure/abort-controller';
import archiver from 'archiver';
import { logger } from '@nx/devkit';

export type GetBlobServiceClientOptions = {
    blobStorageConnectionString?: string | null;
    storageConnectionString?: string | null;
    blobStorageAccount?: string | null;
    storageAccount?: string | null;
};

export function getBlobServiceClient(options: GetBlobServiceClientOptions) {
    const connectionString =
        options.blobStorageConnectionString ?? options.storageConnectionString;
    if (connectionString) {
        return BlobServiceClient.fromConnectionString(connectionString);
    }

    const storageAccount = options.blobStorageAccount ?? options.storageAccount;
    if (!storageAccount) {
        let connectionString = 'UseDevelopmentStorage=true';
        const proxy = process.env.AZURITE_PROXY ?? null;
        if (proxy) {
            connectionString += `;DevelopmentStorageProxyUri=${proxy}`;
        }
        return BlobServiceClient.fromConnectionString(connectionString);
    }

    let storageAccountUrl = storageAccount;
    if (
        !storageAccountUrl.startsWith('http://') &&
        !storageAccountUrl.startsWith('https://')
    ) {
        storageAccountUrl = `https://${storageAccount}.blob.core.windows.net`;
    }

    const credential = new DefaultAzureCredential();

    return new BlobServiceClient(storageAccountUrl, credential);
}

export type GetBlobContainerClientOptions = GetBlobServiceClientOptions & {
    blobContainerName: string;
};

export async function getContainerClient(
    options: GetBlobContainerClientOptions
): Promise<ContainerClient> {
    const blobServiceClient = getBlobServiceClient(options);
    const containerName = options.blobContainerName;
    const containerClient = blobServiceClient.getContainerClient(containerName);
    if (!(await containerClient.exists())) {
        await containerClient.create();
    }
    return containerClient;
}

export type ZipDirectoryOptions = {
    include?: string;
    ignore?: string[];
    zlibLevel?: number;
    tags?: Record<string, string>;
    metadata?: Record<string, string>;
    archive?: archiver.ArchiverOptions;
};

export async function zipAndStoreBlob(
    blob: BlockBlobClient,
    directory: string,
    options: ZipDirectoryOptions
) {
    const abort = new AbortController();

    const archive = archiver('zip');

    const result = blob.uploadStream(
        archive,
        archive.readableHighWaterMark,
        5,
        {
            abortSignal: abort.signal,
            tags: options.tags,
            metadata: options.metadata,
            blobHTTPHeaders: {
                blobContentType: 'application/zip',
            },
        }
    );

    archive.on('warning', function (err) {
        if (err.code === 'ENOENT') {
            logger.warn(err);
        } else {
            logger.warn(err);
            abort.abort();
            throw err;
        }
    });

    archive.on('error', function (err) {
        logger.error(err);
        abort.abort();
        throw err;
    });

    if (options.include) {
        archive.glob(options.include, {
            ignore: options.ignore ?? [],
            cwd: directory,
        });
    } else {
        archive.directory(directory, false);
    }

    try {
        await archive.finalize();
    } catch (e) {
        abort.abort();
        throw e;
    }

    return result;
}
