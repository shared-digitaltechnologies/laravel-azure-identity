import { BlobUploadDirectoryExecutorSchema } from './schema';
import { getContainerClient } from '../../storage/blob';
import * as fg from 'fast-glob';
import * as path from 'node:path';

export default async function runExecutor(
    options: BlobUploadDirectoryExecutorSchema
) {
    const directory = path.resolve(options.directory);

    const files = getFiles(options, directory);

    console.log('Uploading files ', files);

    const containerClient = await getContainerClient({
        blobContainerName: options.containerName,
        blobStorageAccount: options.storageAccount,
        blobStorageConnectionString: options.blobStorageAccountConnectionString,
    });

    const success = true;

    const getContentType = contentTypeResolver(options.contentTypes);

    for (const file of files) {
        console.log(`Uploading file '${file}'`);
        const blobClient = containerClient.getBlockBlobClient(file);
        await blobClient.uploadFile(path.resolve(directory, file), {
            blobHTTPHeaders: {
                blobContentType: getContentType(file),
            },
        });
        console.log(`Uploaded file '${file}'`);
    }

    return {
        success,
    };
}

function contentTypeResolver(contentTypeAssociations: Record<string, string>) {
    const assocs = {
        '.html': 'text/html',
        '.htm': 'text/html',
        '.shtml': 'text/html',
        '.css': 'text/css',
        '.xml': 'text/xml',
        '.gif': 'image/gif',
        '.jpeg': 'image/jpeg',
        '.jpg': 'image/jpeg',
        '.js': 'text/javascript',
        '.atom': 'application/atom+xml',
        '.rss': 'application/rss+xml',
        '.mml': 'text/mathml',
        '.jad': 'text/vnd.sun.j2me.app-descriptor',
        '.wml': 'text/vnd.wap.wml',
        '.htc': 'text/x-component',
        '.txt': 'text/plain',
        '.avif': 'image/avif',
        '.png': 'image/png',
        '.svg': 'image/svg+xml',
        '.svgz': 'image/svg+xml',
        '.tif': 'image/tiff',
        '.tiff': 'image/tiff',
        '.wbmp': 'image/vnd.wap.wbmp',
        '.webp': 'image/webp',
        '.ico': 'image/x-icon',
        '.jng': 'image/x-jng',
        '.bmp': 'image/x-ms-bmp',
        '.woff': 'font/woff',
        '.woff2': 'font/woff2',
        '.jar': 'application/java-archive',
        '.war': 'application/java-archive',
        '.ear': 'application/java-archive',
        '.json': 'application/json',
        '.pdf': 'application/pdf',
        '.ps': 'application/postscript',
        '.esp': 'application/postscript',
        '.ai': 'application/postscript',
        '.rtf': 'application/rtf',
        '.zip': 'application/zip',
        '.wasm': 'application/wasm',
        '.7z': 'application/x-7z-compressed',
        '.xhtml': 'application/xhtml+xml',
        '.xlsx':
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        '.docx':
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        '.pptx':
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        '.mid': 'audio/midi',
        '.midi': 'audio/midi',
        '.kar': 'audio/midi',
        '.mp3': 'audio/mpeg',
        '.ogg': 'audio/ogg',
        ...contentTypeAssociations,
    };

    return (file: string): string => {
        const extname = path.extname(file);

        if (extname in assocs) return assocs[extname];

        console.warn(`No content type for extension ${extname}.`);
        return 'application/octet-stream';
    };
}

function getFiles(
    options: BlobUploadDirectoryExecutorSchema,
    directory: string
) {
    return fg.sync(options.include, {
        cwd: directory,
        ignore: options.exclude,
        onlyFiles: true,
        braceExpansion: options.braceExpansion,
        dot: options.includeDotFiles,
    });
}
