<?php
declare(strict_types=1);

use Cake\Routing\RouteBuilder;

/** @var \Cake\Routing\RouteBuilder $routes */
$routes->plugin('Passbolt/Attachments', ['path' => '/attachments'], function (RouteBuilder $routes): void {
    $routes->setExtensions(['json']);

    // List attachment metadata for a resource.
    $routes->connect('/{resourceId}', ['prefix' => 'Attachments', 'controller' => 'AttachmentsIndex', 'action' => 'index'])
        ->setPass(['resourceId'])
        ->setMethods(['GET']);

    // Upload a ciphertext blob for a resource (multipart form field `data`).
    $routes->connect('/{resourceId}', ['prefix' => 'Attachments', 'controller' => 'AttachmentsCreate', 'action' => 'create'])
        ->setPass(['resourceId'])
        ->setMethods(['POST']);

    // Download a ciphertext blob (raw bytes, not the JSON envelope).
    $routes->connect('/{resourceId}/{attachmentId}', ['prefix' => 'Attachments', 'controller' => 'AttachmentsDownload', 'action' => 'download'])
        ->setPass(['resourceId', 'attachmentId'])
        ->setMethods(['GET']);

    // Delete a ciphertext blob.
    $routes->connect('/{resourceId}/{attachmentId}', ['prefix' => 'Attachments', 'controller' => 'AttachmentsDelete', 'action' => 'delete'])
        ->setPass(['resourceId', 'attachmentId'])
        ->setMethods(['DELETE']);
});
