<?php
/**
 * product_config.php
 * -------------------------------------------------------------------------
 * GET ?id=PRODUCT_ID -> JSON config for the customize modal (customize-modal.inc.php):
 * { success, product:{id,name,price,category,description,image,stock},
 *   sizes:[{id,label,price,is_default}],
 *   groups:[{id,name,type,is_required,choices:[{id,label,price_delta,is_default}]}],
 *   addons:[{id,name,price}] }
 */
if (session_status() === PHP_SESSION_NONE) session_start();
include 'config.php';
include_once 'customize-lib.php';

header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);
$cfg = $id > 0 ? custProductConfig($conn, $id) : null;

if (!$cfg) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit();
}

echo json_encode([
    'success' => true,
    'product' => [
        'id'          => (int)$cfg['product']['id'],
        'name'        => (string)$cfg['product']['name'],
        'price'       => (float)$cfg['product']['price'],
        'category'    => (string)$cfg['product']['category'],
        'description' => (string)($cfg['product']['description'] ?? ''),
        'image'       => (string)($cfg['product']['image'] ?? ''),
        'stock'       => (int)$cfg['product']['stock'],
    ],
    'sizes'  => array_map(fn($s) => ['id' => (int)$s['id'], 'label' => $s['label'], 'price' => (float)$s['price'], 'is_default' => (bool)$s['is_default']], $cfg['sizes']),
    'groups' => array_map(fn($g) => [
        'id' => (int)$g['id'], 'name' => $g['name'], 'type' => $g['type'], 'is_required' => (bool)$g['is_required'],
        'choices' => array_map(fn($c) => ['id' => (int)$c['id'], 'label' => $c['label'], 'price_delta' => (float)$c['price_delta'], 'is_default' => (bool)$c['is_default']], $g['choices']),
    ], $cfg['groups']),
    'addons' => array_map(fn($a) => ['id' => (int)$a['id'], 'name' => $a['name'], 'price' => (float)$a['price']], $cfg['addons']),
]);