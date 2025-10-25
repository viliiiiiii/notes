<?php
// Placeholder MinIO integration. Uncomment and install dependencies to enable.
//
// require __DIR__ . '/../vendor/autoload.php';
//
// use Aws\S3\S3Client;
// use Aws\S3\Exception\S3Exception;
//
// function minio_client(array $config): S3Client
// {
//     return new S3Client([
//         'version' => 'latest',
//         'region' => $config['region'],
//         'endpoint' => $config['endpoint'],
//         'use_path_style_endpoint' => true,
//         'credentials' => [
//             'key' => $config['key'],
//             'secret' => $config['secret'],
//         ],
//     ]);
// }
//
// function upload_attachment_to_minio(array $file, int $noteId)
// {
//     $config = require __DIR__ . '/../config.php';
//     $minioConfig = $config['minio'];
//     $client = minio_client($minioConfig);
//
//     if ($file['error'] !== UPLOAD_ERR_OK) {
//         throw new RuntimeException('File upload failed.');
//     }
//
//     $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
//     $key = sprintf('notes/%d/%s.%s', $noteId, bin2hex(random_bytes(8)), $extension);
//
//     try {
//         $client->putObject([
//             'Bucket' => $minioConfig['bucket'],
//             'Key' => $key,
//             'SourceFile' => $file['tmp_name'],
//             'ACL' => 'private',
//         ]);
//     } catch (S3Exception $exception) {
//         throw new RuntimeException('Upload failed: ' . $exception->getMessage(), 0, $exception);
//     }
//
//     return $key;
// }
