<?php
declare(strict_types=1);

use Cake\Routing\RouteBuilder;

/** @var \Cake\Routing\RouteBuilder $routes */
$routes->plugin('Passbolt/Attachments', ['path' => '/attachments'], function (RouteBuilder $routes): void {
    $routes->setExtensions(['json']);

    // NOTE: no 'prefix' here. The controllers live in
    // Passbolt\Attachments\Controller\ (not a prefixed sub-namespace), so a
    // routing prefix would make dispatch look for Controller\Attachments\* and
    // 404 with MissingController. `cake routes` lists routes without resolving
    // controllers, so the prefix mistake only surfaces on a real HTTP request.

    // List attachment metadata for a resource.
    $routes->connect('/{resourceId}', ['controller' => 'AttachmentsIndex', 'action' => 'index'])
        ->setPass(['resourceId'])
        ->setMethods(['GET']);

    // Upload a ciphertext blob for a resource (multipart form field `data`).
    $routes->connect('/{resourceId}', ['controller' => 'AttachmentsCreate', 'action' => 'create'])
        ->setPass(['resourceId'])
        ->setMethods(['POST']);

    // Download a ciphertext blob (raw bytes, not the JSON envelope).
    $routes->connect('/{resourceId}/{attachmentId}', ['controller' => 'AttachmentsDownload', 'action' => 'download'])
        ->setPass(['resourceId', 'attachmentId'])
        ->setMethods(['GET']);

    // Delete a ciphertext blob.
    $routes->connect('/{resourceId}/{attachmentId}', ['controller' => 'AttachmentsDelete', 'action' => 'delete'])
        ->setPass(['resourceId', 'attachmentId'])
        ->setMethods(['DELETE']);
});
