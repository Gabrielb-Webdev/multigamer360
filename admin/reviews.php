<?php
require_once 'inc/auth.php';

$userManager = new UserManager($pdo);

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'approve') {
        $review_id = $_POST['review_id'];
        $stmt = $pdo->prepare("UPDATE reviews SET is_approved = 1 WHERE id = ?");
        $stmt->execute([$review_id]);
        
        // Actualizar estadísticas del producto
        updateProductStats($pdo, $_POST['product_id']);
        $success_msg = "Reseña aprobada exitosamente";
    } 
    elseif ($action === 'reject') {
        $review_id = $_POST['review_id'];
        $stmt = $pdo->prepare("UPDATE reviews SET is_approved = 0 WHERE id = ?");
        $stmt->execute([$review_id]);
        
        // Actualizar estadísticas del producto
        updateProductStats($pdo, $_POST['product_id']);
        $success_msg = "Reseña rechazada";
    }
    elseif ($action === 'delete') {
        $review_id = $_POST['review_id'];
        $product_id = $_POST['product_id'];
        
        $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
        $stmt->execute([$review_id]);
        
        // Actualizar estadísticas del producto
        updateProductStats($pdo, $product_id);
        $success_msg = "Reseña eliminada exitosamente";
    }
    elseif ($action === 'respond') {
        $review_id = $_POST['review_id'];
        $response = trim($_POST['admin_response']);
        
        if (!empty($response)) {
            $stmt = $pdo->prepare("
                UPDATE reviews 
                SET admin_response = ?, admin_response_by = ?, admin_response_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$response, $_SESSION['user_id'], $review_id]);
            $success_msg = "Respuesta agregada exitosamente";
        } else {
            $error_msg = "La respuesta no puede estar vacía";
        }
    }
}

// Función para actualizar estadísticas del producto
function updateProductStats($pdo, $product_id) {
    $stmt = $pdo->prepare("
        UPDATE products p SET 
            average_rating = (
                SELECT COALESCE(AVG(rating), 0) 
                FROM reviews r 
                WHERE r.product_id = p.id AND r.is_approved = 1
            ),
            review_count = (
                SELECT COUNT(*) 
                FROM reviews r 
                WHERE r.product_id = p.id AND r.is_approved = 1
            )
        WHERE p.id = ?
    ");
    $stmt->execute([$product_id]);
}

// Obtener filtros
$status = $_GET['status'] ?? '';
$rating = $_GET['rating'] ?? '';
$search = $_GET['search'] ?? '';
$product_id = $_GET['product_id'] ?? '';

// Construir consulta
$sql = "
    SELECT r.*, 
           u.email as user_email,
           u.first_name,
           u.last_name,
           p.name as product_name,
           p.image_url as product_image,
           admin_user.email as admin_email
    FROM reviews r
    LEFT JOIN users u ON r.user_id = u.id
    LEFT JOIN products p ON r.product_id = p.id
    LEFT JOIN users admin_user ON r.admin_response_by = admin_user.id
    WHERE 1=1
";

$params = [];

if ($status === 'pending') {
    $sql .= " AND r.is_approved = 0";
} elseif ($status === 'approved') {
    $sql .= " AND r.is_approved = 1";
}

if ($rating) {
    $sql .= " AND r.rating = ?";
    $params[] = $rating;
}

if ($search) {
    $sql .= " AND (r.title LIKE ? OR r.comment LIKE ? OR u.email LIKE ? OR p.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($product_id) {
    $sql .= " AND r.product_id = ?";
    $params[] = $product_id;
}

$sql .= " ORDER BY r.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reviews = $stmt->fetchAll();

// Obtener estadísticas
$stats_stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_reviews,
        COUNT(CASE WHEN is_approved = 1 THEN 1 END) as approved_reviews,
        COUNT(CASE WHEN is_approved = 0 THEN 1 END) as pending_reviews,
        AVG(CASE WHEN is_approved = 1 THEN rating END) as avg_rating
    FROM reviews
");
$stats = $stats_stmt->fetch();

// Obtener productos para filtro
$products_stmt = $pdo->query("
    SELECT p.id, p.name, COUNT(r.id) as review_count
    FROM products p
    LEFT JOIN reviews r ON p.id = r.product_id
    GROUP BY p.id, p.name
    HAVING review_count > 0
    ORDER BY p.name
");
$products = $products_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Reseñas - Admin MultiGamer360</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <?php include 'inc/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'inc/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-star me-2"></i>Gestión de Reseñas</h1>
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
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-comments fa-2x me-3"></i>
                                    <div>
                                        <div class="fs-4 fw-bold"><?php echo $stats['total_reviews']; ?></div>
                                        <div>Total Reseñas</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-check-circle fa-2x me-3"></i>
                                    <div>
                                        <div class="fs-4 fw-bold"><?php echo $stats['approved_reviews']; ?></div>
                                        <div>Aprobadas</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-clock fa-2x me-3"></i>
                                    <div>
                                        <div class="fs-4 fw-bold"><?php echo $stats['pending_reviews']; ?></div>
                                        <div>Pendientes</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-star fa-2x me-3"></i>
                                    <div>
                                        <div class="fs-4 fw-bold"><?php echo number_format($stats['avg_rating'], 1); ?></div>
                                        <div>Rating Promedio</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Buscar</label>
                                <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Título, comentario, usuario...">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Estado</label>
                                <select class="form-select" name="status">
                                    <option value="">Todos</option>
                                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pendientes</option>
                                    <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Aprobadas</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Rating</label>
                                <select class="form-select" name="rating">
                                    <option value="">Todos</option>
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <option value="<?php echo $i; ?>" <?php echo $rating == $i ? 'selected' : ''; ?>>
                                            <?php echo $i; ?> estrella<?php echo $i > 1 ? 's' : ''; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Producto</label>
                                <select class="form-select" name="product_id">
                                    <option value="">Todos los productos</option>
                                    <?php foreach ($products as $product): ?>
                                        <option value="<?php echo $product['id']; ?>" <?php echo $product_id == $product['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($product['name']); ?> (<?php echo $product['review_count']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-search me-1"></i>Filtrar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Lista de reseñas -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Reseñas (<?php echo count($reviews); ?>)</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php foreach ($reviews as $review): ?>
                            <div class="border-bottom p-4">
                                <div class="row">
                                    <div class="col-md-2">
                                        <img src="<?php echo $review['product_image'] ?: '../assets/images/no-image.jpg'; ?>" 
                                             alt="<?php echo htmlspecialchars($review['product_name']); ?>" 
                                             class="img-fluid rounded">
                                    </div>
                                    <div class="col-md-10">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <h6 class="mb-1">
                                                    <a href="../product-details.php?id=<?php echo $review['product_id']; ?>" target="_blank">
                                                        <?php echo htmlspecialchars($review['product_name']); ?>
                                                    </a>
                                                </h6>
                                                <div class="text-muted small">
                                                    Por: <?php echo htmlspecialchars($review['user_email']); ?>
                                                    <?php if ($review['is_verified_purchase']): ?>
                                                        <span class="badge bg-success ms-1">Compra verificada</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <div class="mb-1">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="fas fa-star text-<?php echo $i <= $review['rating'] ? 'warning' : 'muted'; ?>"></i>
                                                    <?php endfor; ?>
                                                </div>
                                                <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($review['created_at'])); ?></small>
                                            </div>
                                        </div>
                                        
                                        <h6><?php echo htmlspecialchars($review['title']); ?></h6>
                                        <p class="mb-3"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                        
                                        <?php if ($review['admin_response']): ?>
                                            <div class="bg-light p-3 rounded mb-3">
                                                <h6 class="mb-1"><i class="fas fa-reply me-1"></i>Respuesta del administrador:</h6>
                                                <p class="mb-1"><?php echo nl2br(htmlspecialchars($review['admin_response'])); ?></p>
                                                <small class="text-muted">
                                                    Por: <?php echo htmlspecialchars($review['admin_email']); ?> - 
                                                    <?php echo date('d/m/Y H:i', strtotime($review['admin_response_at'])); ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="badge bg-<?php echo $review['is_approved'] ? 'success' : 'warning'; ?>">
                                                    <?php echo $review['is_approved'] ? 'Aprobada' : 'Pendiente'; ?>
                                                </span>
                                                <?php if ($review['helpful_count'] > 0): ?>
                                                    <span class="badge bg-info ms-1">
                                                        <i class="fas fa-thumbs-up me-1"></i><?php echo $review['helpful_count']; ?> útiles
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($review['reported_count'] > 0): ?>
                                                    <span class="badge bg-danger ms-1">
                                                        <i class="fas fa-flag me-1"></i><?php echo $review['reported_count']; ?> reportes
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="btn-group">
                                                <?php if (!$review['is_approved']): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="approve">
                                                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                                        <input type="hidden" name="product_id" value="<?php echo $review['product_id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-success" title="Aprobar">
                                                            <i class="fas fa-check"></i> Aprobar
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="reject">
                                                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                                        <input type="hidden" name="product_id" value="<?php echo $review['product_id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-warning" title="Rechazar">
                                                            <i class="fas fa-times"></i> Rechazar
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="showResponseModal(<?php echo $review['id']; ?>, '<?php echo htmlspecialchars($review['admin_response']); ?>')" 
                                                        title="Responder">
                                                    <i class="fas fa-reply"></i> Responder
                                                </button>
                                                
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        onclick="deleteReview(<?php echo $review['id']; ?>, <?php echo $review['product_id']; ?>)" 
                                                        title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if (empty($reviews)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-star fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No se encontraron reseñas</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal para responder -->
    <div class="modal fade" id="responseModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Responder a la Reseña</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="respond">
                        <input type="hidden" name="review_id" id="responseReviewId">
                        
                        <div class="mb-3">
                            <label class="form-label">Respuesta del Administrador</label>
                            <textarea class="form-control" name="admin_response" id="adminResponse" rows="4" required 
                                      placeholder="Escribe tu respuesta a esta reseña..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Enviar Respuesta</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showResponseModal(reviewId, currentResponse) {
            document.getElementById('responseReviewId').value = reviewId;
            document.getElementById('adminResponse').value = currentResponse || '';
            new bootstrap.Modal(document.getElementById('responseModal')).show();
        }
        
        function deleteReview(reviewId, productId) {
            if (confirm('¿Estás seguro de que quieres eliminar esta reseña? Esta acción no se puede deshacer.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="review_id" value="${reviewId}">
                    <input type="hidden" name="product_id" value="${productId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>