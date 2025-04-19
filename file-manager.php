<?php

session_start();
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'var') {
    http_response_code(403);
    exit("Access denied.");
}
function isValidPath($path)
{
    $baseDir = realpath(__DIR__ . '/');
    return strpos(realpath($path), $baseDir) === 0;
}

function handleDelete()
{
    $baseDir = realpath(__DIR__ . '/');
    $fileToDelete = $_POST['file'] ?? '';
    $filePath = realpath($baseDir . '/' . $fileToDelete);

    if (isValidPath($filePath) && file_exists($filePath)) {
        if (is_dir($filePath)) {
            rmdir($filePath);
            echo json_encode(['status' => 'success', 'message' => '✅ Folder deleted.']);
        } else {
            unlink($filePath);
            echo json_encode(['status' => 'success', 'message' => '✅ File deleted.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => '❌ File or folder not found.']);
    }
}

function handleCreate()
{
    $type = $_POST['type'] ?? '';
    $name = $_POST['name'] ?? '';
    $parent = $_POST['parent'] ?? '';
    $baseDir = __DIR__ . '/';

    // Prevent directory traversal
    if (preg_match('/[\/\\\\]/', $name)) {
        echo json_encode(['status' => 'error', 'message' => '❌ Invalid name.']);
        exit;
    }

    $path = rtrim($baseDir . '/' . $parent, '/') . '/' . $name;

    if ($type === 'file') {
        if (file_exists($path)) {
            echo json_encode(['status' => 'error', 'message' => '❌ File already exists.']);
        } else {
            file_put_contents($path, '');
            echo json_encode(['status' => 'success', 'message' => '✅ File created.', 'path' => normalizePath($path)]);
        }
    } elseif ($type === 'folder') {
        if (file_exists($path)) {
            echo json_encode(['status' => 'error', 'message' => '❌ Folder already exists.']);
        } else {
            mkdir($path, 0775, true);
            echo json_encode(['status' => 'success', 'message' => '✅ Folder created.', 'path' => normalizePath($path)]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => '❌ Unknown type.']);
    }
}

function handleRename()
{
    $baseDir = realpath(__DIR__ . '/');
    $oldRel = $_POST['old'] ?? '';
    $newName = $_POST['new'] ?? '';

    $oldPath = realpath($baseDir . '/' . $oldRel);
    $newPath = realpath($baseDir . '/' . dirname($oldRel) . '/' . basename($newName));

    if (isValidPath($oldPath) && file_exists($oldPath)) {
        if (isValidPath($newPath) && !file_exists($newPath)) {
            if (rename($oldPath, $newPath)) {
                echo json_encode(['status' => 'success', 'message' => '✅ File renamed!']);
            } else {
                echo json_encode(['status' => 'error', 'message' => '❌ Rename failed.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => '❌ Invalid new name or file already exists.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => '❌ Invalid path or file not found.']);
    }
}

function normalizePath($path)
{
    return strtolower(str_replace('\\', '/', realpath($path)));
}



$baseDir = __DIR__ . '/';
if (!is_dir($baseDir)) {
    die("Directory not found.");
}

$action = $_GET['action'] ?? $_POST['action'] ?? null;

switch ($action) {
    case 'delete':
        handleDelete();
        break;

    case 'create':
        handleCreate();
        break;

    case 'rename':
        handleRename();
        break;

    case 'list':
        header('Content-Type: application/json');
        echo json_encode(listFiles($baseDir));
        break;

    case 'open':
        if (isset($_GET['file'])) {
            $relativePath = $_GET['file'];
            $filepath = realpath($baseDir . '/' . $relativePath);
            if (isValidPath($filepath) && file_exists($filepath)) {
                ob_clean();
                header('Content-Type: text/html; charset=UTF-8');
                echo file_get_contents($filepath);
            } else {
                http_response_code(404);
                echo "File not found or invalid path.";
            }
        }
        break;

    default:
        http_response_code(400);
        echo "Invalid action.";
}

function listFiles($dir)
{
    $protectedFiles = ['index.php', 'save.php', 'file-manager.php', 'assets', 'editor-manager'];
    $result = [];

    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..')
            continue;

        if (in_array($item, $protectedFiles) && realpath($dir) === realpath(__DIR__)) {
            continue;
        }

        $path = $dir . DIRECTORY_SEPARATOR . $item;

        if (is_dir($path)) {
            $result[] = [
                'type' => 'folder',
                'name' => $item,
                'children' => listFiles($path)
            ];
        } else {
            $result[] = [
                'type' => 'file',
                'name' => $item
            ];
        }
    }

    return $result;
}
?>