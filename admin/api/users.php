<?php
/**
 * API de Gestión de Usuarios
 * Endpoint para operaciones CRUD de usuarios desde el panel de administración
 */

session_start();
require_once '../../config/database.php';
require_once '../inc/functions.php';

// Verificar autenticación y permisos de administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

// Configurar cabeceras JSON
header('Content-Type: application/json');

// Obtener método HTTP
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGet($pdo);
            break;
            
        case 'POST':
            handlePost($pdo);
            break;
            
        case 'PUT':
            handlePut($pdo);
            break;
            
        case 'DELETE':
            handleDelete($pdo);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}

/**
 * GET - Obtener usuarios o información de un usuario específico
 */
function handleGet($pdo) {
    if (isset($_GET['id'])) {
        // Obtener un usuario específico
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Eliminar datos sensibles
            unset($user['password']);
            echo json_encode(['success' => true, 'data' => $user]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        }
    } else {
        // Obtener lista de usuarios con paginación
        $page = max(1, intval($_GET['page'] ?? 1));
        $per_page = min(100, max(1, intval($_GET['per_page'] ?? 20)));
        $offset = ($page - 1) * $per_page;
        
        // Filtros opcionales
        $search = $_GET['search'] ?? '';
        $role = $_GET['role'] ?? '';
        $is_active = $_GET['is_active'] ?? '';
        
        $where_conditions = ['1=1'];
        $params = [];
        
        if ($search) {
            $where_conditions[] = "(email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
            $search_term = "%$search%";
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }
        
        if ($role) {
            $where_conditions[] = "role = ?";
            $params[] = $role;
        }
        
        if ($is_active !== '') {
            $where_conditions[] = "is_active = ?";
            $params[] = intval($is_active);
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        // Contar total
        $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE $where_clause");
        $count_stmt->execute($params);
        $total = $count_stmt->fetchColumn();
        
        // Obtener usuarios
        $stmt = $pdo->prepare("
            SELECT id, email, first_name, last_name, phone, birth_date, role, is_active, 
                   email_verified, created_at, last_login
            FROM users 
            WHERE $where_clause
            ORDER BY created_at DESC
            LIMIT $per_page OFFSET $offset
        ");
        $stmt->execute($params);
        $users = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $users,
            'pagination' => [
                'page' => $page,
                'per_page' => $per_page,
                'total' => $total,
                'total_pages' => ceil($total / $per_page)
            ]
        ]);
    }
}

/**
 * POST - Crear nuevo usuario
 */
function handlePost($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validar campos requeridos
    if (empty($data['email']) || empty($data['password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email y contraseña son requeridos']);
        return;
    }
    
    // Verificar si el email ya existe
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$data['email']]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'El email ya está registrado']);
        return;
    }
    
    // Validar rol
    $valid_roles = ['administrador', 'colaborador', 'moderador', 'cliente'];
    $role = $data['role'] ?? 'cliente';
    if (!in_array($role, $valid_roles)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Rol inválido']);
        return;
    }
    
    // Hash de la contraseña
    $password_hash = password_hash($data['password'], PASSWORD_BCRYPT);
    
    // Insertar usuario
    $stmt = $pdo->prepare("
        INSERT INTO users (email, password, first_name, last_name, phone, birth_date, role, is_active, email_verified, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $result = $stmt->execute([
        $data['email'],
        $password_hash,
        $data['first_name'] ?? '',
        $data['last_name'] ?? '',
        $data['phone'] ?? null,
        $data['birth_date'] ?? null,
        $role,
        isset($data['is_active']) ? intval($data['is_active']) : 1,
        isset($data['email_verified']) ? intval($data['email_verified']) : 0
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Usuario creado correctamente',
            'data' => ['id' => $pdo->lastInsertId()]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al crear usuario']);
    }
}

/**
 * PUT - Actualizar usuario
 */
function handlePut($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Actualización masiva (múltiples usuarios)
    if (isset($data['ids']) && is_array($data['ids'])) {
        handleBulkUpdate($pdo, $data);
        return;
    }
    
    // Actualización individual
    if (empty($data['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de usuario requerido']);
        return;
    }
    
    $user_id = intval($data['id']);
    
    // No permitir que el admin se quite sus propios permisos
    if ($user_id === $_SESSION['user_id'] && isset($data['role']) && $data['role'] !== 'administrador') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No puedes cambiar tu propio rol de administrador']);
        return;
    }
    
    // Construir UPDATE dinámico
    $update_fields = [];
    $params = [];
    
    // Campos actualizables
    $allowed_fields = ['first_name', 'last_name', 'email', 'phone', 'birth_date', 'is_active', 'email_verified', 'role'];
    
    foreach ($allowed_fields as $field) {
        if (isset($data[$field])) {
            // Validar rol si se está actualizando
            if ($field === 'role') {
                $valid_roles = ['administrador', 'colaborador', 'moderador', 'cliente'];
                if (!in_array($data[$field], $valid_roles)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Rol inválido. Usar: ' . implode(', ', $valid_roles)]);
                    return;
                }
            }
            
            $update_fields[] = "$field = ?";
            $params[] = $data[$field];
        }
    }
    
    // Actualizar contraseña si se proporciona
    if (!empty($data['password'])) {
        $update_fields[] = "password = ?";
        $params[] = password_hash($data['password'], PASSWORD_BCRYPT);
    }
    
    if (empty($update_fields)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No hay campos para actualizar']);
        return;
    }
    
    $params[] = $user_id;
    
    $stmt = $pdo->prepare("
        UPDATE users 
        SET " . implode(', ', $update_fields) . ", updated_at = NOW()
        WHERE id = ?
    ");
    
    if ($stmt->execute($params)) {
        echo json_encode(['success' => true, 'message' => 'Usuario actualizado correctamente']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al actualizar usuario']);
    }
}

/**
 * Actualización masiva de usuarios
 */
function handleBulkUpdate($pdo, $data) {
    $ids = array_map('intval', $data['ids']);
    
    // No permitir modificar el propio usuario
    $ids = array_filter($ids, function($id) {
        return $id !== $_SESSION['user_id'];
    });
    
    if (empty($ids)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No hay usuarios válidos para actualizar']);
        return;
    }
    
    $update_fields = [];
    $params = [];
    
    if (isset($data['is_active'])) {
        $update_fields[] = "is_active = ?";
        $params[] = intval($data['is_active']);
    }
    
    if (isset($data['role'])) {
        $valid_roles = ['administrador', 'colaborador', 'moderador', 'cliente'];
        if (!in_array($data['role'], $valid_roles)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Rol inválido']);
            return;
        }
        $update_fields[] = "role = ?";
        $params[] = $data['role'];
    }
    
    if (empty($update_fields)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No hay campos para actualizar']);
        return;
    }
    
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $params = array_merge($params, $ids);
    
    $stmt = $pdo->prepare("
        UPDATE users 
        SET " . implode(', ', $update_fields) . ", updated_at = NOW()
        WHERE id IN ($placeholders)
    ");
    
    if ($stmt->execute($params)) {
        echo json_encode([
            'success' => true,
            'message' => count($ids) . ' usuario(s) actualizado(s) correctamente'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al actualizar usuarios']);
    }
}

/**
 * DELETE - Eliminar usuario(s)
 */
function handleDelete($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['ids']) || !is_array($data['ids'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'IDs de usuarios requeridos']);
        return;
    }
    
    $ids = array_map('intval', $data['ids']);
    
    // No permitir eliminar el propio usuario
    $ids = array_filter($ids, function($id) {
        return $id !== $_SESSION['user_id'];
    });
    
    if (empty($ids)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No hay usuarios válidos para eliminar']);
        return;
    }
    
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    $stmt = $pdo->prepare("DELETE FROM users WHERE id IN ($placeholders)");
    
    if ($stmt->execute($ids)) {
        echo json_encode([
            'success' => true,
            'message' => $stmt->rowCount() . ' usuario(s) eliminado(s) correctamente'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al eliminar usuarios']);
    }
}
