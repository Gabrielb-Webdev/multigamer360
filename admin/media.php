<?php
require_once 'inc/auth.php';

$userManager = new UserManager($pdo);

// Directorio de uploads
$upload_dir = '../uploads/';
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$max_file_size = 5 * 1024 * 1024; // 5MB

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'upload') {
        if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['media_file'];
            $file_type = $file['type'];
            $file_size = $file['size'];
            
            // Validaciones
            if (!in_array($file_type, $allowed_types)) {
                $error_msg = "Tipo de archivo no permitido. Solo se permiten imágenes (JPG, PNG, GIF, WEBP).";
            } elseif ($file_size > $max_file_size) {
                $error_msg = "El archivo es demasiado grande. Tamaño máximo: 5MB.";
            } else {
                // Generar nombre único
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $new_filename = 'media_' . time() . '_' . uniqid() . '.' . $extension;
                $target_path = $upload_dir . $new_filename;
                
                // Mover archivo
                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                    // Guardar en base de datos
                    try {
                        $stmt = $pdo->prepare("
                            INSERT INTO media_files (filename, original_name, file_type, file_size, uploaded_by) 
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $new_filename,
                            $file['name'],
                            $file_type,
                            $file_size,
                            $_SESSION['user_id']
                        ]);
                        $success_msg = "Archivo subido exitosamente";
                    } catch (PDOException $e) {
                        $error_msg = "Error al guardar en base de datos: " . $e->getMessage();
                        unlink($target_path); // Eliminar archivo si falla BD
                    }
                } else {
                    $error_msg = "Error al subir el archivo";
                }
            }
        } else {
            $error_msg = "No se recibió ningún archivo o hubo un error en la subida";
        }
    } elseif ($action === 'delete') {
        $file_id = $_POST['file_id'];
        
        // Obtener nombre del archivo
        $stmt = $pdo->prepare("SELECT filename FROM media_files WHERE id = ?");
        $stmt->execute([$file_id]);
        $file = $stmt->fetch();
        
        if ($file) {
            // Eliminar archivo físico
            $file_path = $upload_dir . $file['filename'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            // Eliminar de BD
            $stmt = $pdo->prepare("DELETE FROM media_files WHERE id = ?");
            $stmt->execute([$file_id]);
            $success_msg = "Archivo eliminado exitosamente";
        } else {
            $error_msg = "Archivo no encontrado";
        }
    }
}

// Obtener archivos
$search = $_GET['search'] ?? '';
$type_filter = $_GET['type'] ?? '';
$page = $_GET['page'] ?? 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$sql = "SELECT m.*, u.username as uploaded_by_name 
        FROM media_files m 
        LEFT JOIN users u ON m.uploaded_by = u.id 
        WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (m.original_name LIKE ? OR m.filename LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($type_filter) {
    $sql .= " AND m.file_type LIKE ?";
    $params[] = "$type_filter%";
}

$sql .= " ORDER BY m.uploaded_at DESC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$media_files = $stmt->fetchAll();

// Contar total
$count_sql = "SELECT COUNT(*) FROM media_files m WHERE 1=1";
$count_params = [];

if ($search) {
    $count_sql .= " AND (m.original_name LIKE ? OR m.filename LIKE ?)";
    $count_params[] = "%$search%";
    $count_params[] = "%$search%";
}

if ($type_filter) {
    $count_sql .= " AND m.file_type LIKE ?";
    $count_params[] = "$type_filter%";
}

$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($count_params);
$total_files = $count_stmt->fetchColumn();
$total_pages = ceil($total_files / $per_page);

// Obtener estadísticas
$stats_stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_files,
        SUM(file_size) as total_size,
        COUNT(CASE WHEN file_type LIKE 'image/%' THEN 1 END) as image_count
    FROM media_files
");
$stats = $stats_stmt->fetch();

$page_title = "Gestión de Medios";
require_once 'inc/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'inc/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-photo-video me-2"></i>Gestión de Medios</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                    <i class="fas fa-upload me-1"></i>Subir Archivo
                </button>
            </div>

            <?php if (isset($success_msg)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error_msg)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Estadísticas -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">Total de Archivos</h6>
                            <h3><?php echo number_format($stats['total_files']); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">Espacio Utilizado</h6>
                            <h3><?php echo number_format($stats['total_size'] / 1024 / 1024, 2); ?> MB</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">Imágenes</h6>
                            <h3><?php echo number_format($stats['image_count']); ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control" placeholder="Buscar archivos..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-3">
                            <select name="type" class="form-select">
                                <option value="">Todos los tipos</option>
                                <option value="image/jpeg" <?php echo $type_filter === 'image/jpeg' ? 'selected' : ''; ?>>JPEG</option>
                                <option value="image/png" <?php echo $type_filter === 'image/png' ? 'selected' : ''; ?>>PNG</option>
                                <option value="image/gif" <?php echo $type_filter === 'image/gif' ? 'selected' : ''; ?>>GIF</option>
                                <option value="image/webp" <?php echo $type_filter === 'image/webp' ? 'selected' : ''; ?>>WEBP</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                        </div>
                        <div class="col-md-2">
                            <a href="media.php" class="btn btn-secondary w-100">
                                <i class="fas fa-times"></i> Limpiar
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Galería de medios -->
            <div class="row g-3 mb-4">
                <?php if (count($media_files) > 0): ?>
                    <?php foreach ($media_files as $file): ?>
                        <div class="col-md-3">
                            <div class="card">
                                <div class="position-relative">
                                    <?php if (strpos($file['file_type'], 'image/') === 0): ?>
                                        <img src="<?php echo $upload_dir . $file['filename']; ?>" 
                                             class="card-img-top" 
                                             alt="<?php echo htmlspecialchars($file['original_name']); ?>"
                                             style="height: 200px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center" 
                                             style="height: 200px;">
                                            <i class="fas fa-file fa-3x text-white"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="position-absolute top-0 end-0 p-2">
                                        <button class="btn btn-sm btn-danger" 
                                                onclick="deleteFile(<?php echo $file['id']; ?>, '<?php echo htmlspecialchars($file['original_name']); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <h6 class="card-title text-truncate" title="<?php echo htmlspecialchars($file['original_name']); ?>">
                                        <?php echo htmlspecialchars($file['original_name']); ?>
                                    </h6>
                                    <p class="card-text small text-muted mb-1">
                                        <i class="fas fa-file-alt"></i> <?php echo number_format($file['file_size'] / 1024, 2); ?> KB
                                    </p>
                                    <p class="card-text small text-muted mb-1">
                                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($file['uploaded_by_name'] ?? 'Sistema'); ?>
                                    </p>
                                    <p class="card-text small text-muted">
                                        <i class="fas fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($file['uploaded_at'])); ?>
                                    </p>
                                    <button class="btn btn-sm btn-outline-primary w-100" 
                                            onclick="copyUrl('<?php echo $upload_dir . $file['filename']; ?>')">
                                        <i class="fas fa-copy"></i> Copiar URL
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No se encontraron archivos multimedia.
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Paginación -->
            <?php if ($total_pages > 1): ?>
                <nav>
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($type_filter); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>

        </main>
    </div>
</div>

<!-- Modal para subir archivo -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Subir Archivo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="upload">
                    
                    <div class="mb-3">
                        <label class="form-label">Seleccionar Archivo</label>
                        <input type="file" class="form-control" name="media_file" accept="image/*" required>
                        <div class="form-text">
                            Formatos permitidos: JPG, PNG, GIF, WEBP. Tamaño máximo: 5MB
                        </div>
                    </div>

                    <div id="preview" class="mb-3" style="display:none;">
                        <img id="preview-image" src="" alt="Preview" class="img-fluid rounded">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Subir Archivo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Preview de imagen antes de subir
document.querySelector('input[name="media_file"]').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview-image').src = e.target.result;
            document.getElementById('preview').style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
});

function deleteFile(id, filename) {
    if (confirm(`¿Estás seguro de que quieres eliminar "${filename}"?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="file_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function copyUrl(url) {
    // Crear URL completo
    const fullUrl = window.location.origin + '/' + url;
    
    // Copiar al portapapeles
    navigator.clipboard.writeText(fullUrl).then(function() {
        alert('URL copiada al portapapeles: ' + fullUrl);
    }, function() {
        // Fallback para navegadores antiguos
        const input = document.createElement('input');
        input.value = fullUrl;
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        document.body.removeChild(input);
        alert('URL copiada al portapapeles: ' + fullUrl);
    });
}
</script>

<?php require_once 'inc/footer.php'; ?>
