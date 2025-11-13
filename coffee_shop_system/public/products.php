<?php
// public/products.php
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Product.php';
require_once __DIR__ . '/../config/db.php'; // para operações diretas (delete)
Auth::requireAuth();

$errors = [];
$success = null;

// Handle create product POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_product') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = str_replace(',', '.', trim($_POST['price'] ?? '0'));
        $category = trim($_POST['category'] ?? '');
        $stock = trim($_POST['stock'] ?? '');

        if ($name === '') $errors[] = 'O nome do produto é obrigatório.';
        if (!is_numeric($price) || $price < 0) $errors[] = 'Preço inválido.';
        if ($stock !== '' && (!is_numeric($stock) || intval($stock) < 0)) $errors[] = 'Estoque inválido.';

        if (empty($errors)) {
            try {
                $ok = Product::create([
                    'name' => $name,
                    'description' => $description,
                    'price' => number_format((float)$price, 2, '.', ''),
                    'category' => $category !== '' ? $category : null,
                    'stock' => $stock !== '' ? intval($stock) : null
                ]);
                if ($ok) {
                    header('Location: products.php?created=1');
                    exit;
                } else {
                    $errors[] = 'Falha ao criar produto.';
                }
            } catch (Exception $e) {
                $errors[] = 'Erro no servidor: ' . htmlspecialchars($e->getMessage());
            }
        }
    }

    if ($action === 'delete_product') {
        $pid = intval($_POST['product_id'] ?? 0);
        if ($pid > 0) {
            try {
                $pdo = getPDO();
                $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
                $stmt->execute([$pid]);
                header('Location: products.php?deleted=1');
                exit;
            } catch (Exception $e) {
                $errors[] = 'Não foi possível excluir o produto: ' . htmlspecialchars($e->getMessage());
            }
        } else {
            $errors[] = 'Produto inválido para exclusão.';
        }
    }
}

// Flags after redirect
$created = isset($_GET['created']) && $_GET['created'] == '1';
$deleted = isset($_GET['deleted']) && $_GET['deleted'] == '1';

// Fetch products
$products = Product::all();

// Build categories list from products
$categories = [];
foreach ($products as $p) {
    $cat = $p['category'] ?? 'Sem categoria';
    if (!in_array($cat, $categories)) $categories[] = $cat;
}
sort($categories, SORT_STRING | SORT_FLAG_CASE);
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title>Produtos - Coffee Shop</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="style.css">
  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font for cozy feel -->
  <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@300;400;700&display=swap" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand py-3">
  <div class="container">
    <a class="navbar-brand brand-title" href="dashboard.php">Coffee Shop</a>
    <div class="ms-auto d-flex align-items-center gap-2">
      <a class="btn btn-sm btn-outline-light" href="dashboard.php">Voltar</a>
      <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">+ Novo Café</button>
    </div>
  </div>
</nav>

<div class="page-container">
  <div class="panel">
    <div class="d-flex justify-content-between align-items-start mb-3">
      <div>
        <h3 class="mb-1">Cafés</h3>
        <small style="color: #f3e9dc;" class="text-muted">Cardápio - gerencie sabores, preços e estoque</small>
      </div>
      <div class="text-end">
        <div style="color: #f3e9dc;" class="text-muted small">Total de produtos: <?php echo count($products); ?></div>
      </div>
    </div>

    <?php if ($created): ?>
      <div class="alert alert-success">Produto criado com sucesso.</div>
    <?php endif; ?>
    <?php if ($deleted): ?>
      <div class="alert alert-success">Produto excluído com sucesso.</div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach($errors as $err): ?><li><?php echo $err; ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="controls mb-3">
      <input id="searchInput" class="form-control search-input" placeholder="Pesquisar cafés por nome ou descrição...">
      <select id="categoryFilter" class="form-select filter-select">
        <option value="">Todas categorias</option>
        <?php foreach($categories as $cat): ?>
          <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
        <?php endforeach; ?>
      </select>
      <select id="sortSelect" class="form-select filter-select" style="max-width:180px;">
        <option value="new">Mais recentes</option>
        <option value="price-asc">Preço ↑</option>
        <option value="price-desc">Preço ↓</option>
        <option value="name-asc">Nome A→Z</option>
      </select>
      <div class="ms-auto d-flex gap-2">
        <button id="gridViewBtn" class="btn btn-outline-light btn-sm">Grid</button>
        <button id="listViewBtn" class="btn btn-outline-light btn-sm">Lista</button>
      </div>
    </div>

    <!-- Products grid -->
    <div id="productsContainer" class="products-grid">
      <?php if (empty($products)): ?>
        <div class="empty-state">Nenhum produto cadastrado ainda. Use <strong>+ Novo Café</strong> para adicionar.</div>
      <?php else: ?>
        <?php foreach($products as $p): 
          // Pick a warm color based on ID for thumb
          $h = hexdec(substr(md5($p['id']), 0, 6));
          $bgcolor = sprintf("#%06X", $h & 0x7F7F7F); // subdued color
        ?>
        <div class="product-card" data-name="<?php echo strtolower($p['name']); ?>" data-desc="<?php echo strtolower($p['description'] ?? ''); ?>" data-cat="<?php echo strtolower($p['category'] ?? ''); ?>" data-price="<?php echo $p['price']; ?>">
          <div class="product-thumb" style="background: linear-gradient(135deg, <?php echo $bgcolor; ?>55, rgba(0,0,0,0.08));">
            <!-- Simple coffee cup SVG -->
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="filter: drop-shadow(0 6px 8px rgba(0,0,0,0.45));">
              <path d="M3 7h12v6a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V7z" fill="currentColor" opacity="0.12"/>
              <path d="M5 8.5c1.5-1 4.5-1 6 0" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
              <path d="M19 9a3 3 0 0 1-3 3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
              <path d="M20 6a2 2 0 0 0-2-2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
            </svg>
          </div>
          <div>
            <div class="d-flex justify-content-between align-items-start mb-1">
              <div>
                <div style="font-weight:700; font-size:16px; color:var(--ct-cream)"><?php echo htmlspecialchars($p['name']); ?></div>
                <div class="text-muted small" style="color:var(--ct-muted)"><?php echo htmlspecialchars(mb_strimwidth($p['description'] ?? '', 0, 60, '...')); ?></div>
              </div>
              <div class="text-end">
                <div class="price-badge">R$ <?php echo number_format($p['price'], 2, ',', '.'); ?></div>
                <div class="stock-small mt-1"><?php echo $p['stock'] === null ? '—' : ('Estoque: ' . intval($p['stock'])); ?></div>
              </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-2">
              <div class="category-chip"><?php echo htmlspecialchars($p['category'] ?? 'Sem categoria'); ?></div>
              <div class="d-flex gap-2">
                <!-- Actions: view / delete -->
                <button class="btn btn-sm btn-outline-light" onclick="viewProduct(<?php echo $p['id']; ?>)">Ver</button>
                <form method="post" style="display:inline;" onsubmit="return confirm('Excluir o produto <?php echo htmlspecialchars(addslashes($p['name'])); ?>?');">
                  <input type="hidden" name="action" value="delete_product">
                  <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                  <button class="btn btn-sm btn-danger" type="submit">Excluir</button>
                </form>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </div>
</div>

<!-- Modal: Add Product -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form method="post" class="modal-content needs-validation" novalidate>
      <input type="hidden" name="action" value="create_product">
      <div class="modal-header border-0">
        <h5 class="modal-title">Novo Café</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Nome <span class="text-danger">*</span></label>
            <input name="name" type="text" class="form-control" required maxlength="100" placeholder="Ex: Latte de Baunilha">
            <div class="invalid-feedback">Informe o nome do café.</div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Categoria</label>
            <input name="category" type="text" class="form-control" placeholder="Ex: Espresso, Gelado, Especialidade">
          </div>
          <div class="col-md-4">
            <label class="form-label">Preço (R$) <span class="text-danger">*</span></label>
            <input name="price" type="text" class="form-control" required placeholder="12,50">
            <div class="invalid-feedback">Preço inválido.</div>
          </div>
          <div class="col-md-4">
            <label class="form-label">Estoque (opcional)</label>
            <input name="stock" type="number" min="0" class="form-control" placeholder="10">
          </div>
          <div class="col-md-12">
            <label class="form-label">Descrição</label>
            <textarea name="description" rows="3" class="form-control" placeholder="Notas, sabor, origem, etc."></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Salvar Café</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: View Product -->
<div class="modal fade" id="viewProductModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title" id="viewProductTitle">Café</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body" id="viewProductBody">
        <!-- preenchido via JS -->
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Client-side filtering, sorting and view toggle
  const products = Array.from(document.querySelectorAll('.product-card'));
  const searchInput = document.getElementById('searchInput');
  const categoryFilter = document.getElementById('categoryFilter');
  const sortSelect = document.getElementById('sortSelect');
  const productsContainer = document.getElementById('productsContainer');
  const gridBtn = document.getElementById('gridViewBtn');
  const listBtn = document.getElementById('listViewBtn');

  function applyFilters() {
    const q = searchInput.value.trim().toLowerCase();
    const cat = categoryFilter.value.toLowerCase();
    const sort = sortSelect.value;

    let filtered = products.filter(card => {
      const name = card.dataset.name || '';
      const desc = card.dataset.desc || '';
      const c = card.dataset.cat || '';
      const matchQ = !q || name.includes(q) || desc.includes(q);
      const matchCat = !cat || c === cat;
      return matchQ && matchCat;
    });

    // Sorting
    if (sort === 'price-asc' || sort === 'price-desc' || sort === 'name-asc') {
      filtered.sort((a,b) => {
        if (sort === 'name-asc') {
          return a.dataset.name.localeCompare(b.dataset.name);
        } else {
          const pa = parseFloat(a.dataset.price) || 0;
          const pb = parseFloat(b.dataset.price) || 0;
          return sort === 'price-asc' ? pa - pb : pb - pa;
        }
      });
    } else if (sort === 'new') {
      // default order is DOM order (assume newest first already)
    }

    // Clear container and re-append
    productsContainer.innerHTML = '';
    filtered.forEach(c => productsContainer.appendChild(c));
    if (filtered.length === 0) {
      productsContainer.innerHTML = '<div class="empty-state">Nenhum café encontrado para os filtros selecionados.</div>';
    }
  }

  searchInput.addEventListener('input', applyFilters);
  categoryFilter.addEventListener('change', applyFilters);
  sortSelect.addEventListener('change', applyFilters);

  // View toggle
  gridBtn.addEventListener('click', () => {
    productsContainer.style.gridTemplateColumns = 'repeat(auto-fill, minmax(240px, 1fr))';
  });
  listBtn.addEventListener('click', () => {
    productsContainer.style.gridTemplateColumns = '1fr';
  });

  // View product modal
  function viewProduct(id) {
    // find product card
    const card = products.find(c => c.querySelector('form input[name="product_id"]') && c.querySelector('form input[name="product_id"]').value == id)
                || products.find(c => c.querySelector('.btn') && c.innerText.includes('Ver') && (c.innerHTML.indexOf('product_id') === -1 || true));
    // Build detail from card dataset
    const title = card ? card.querySelector('div[style] + div > div > div').innerText : 'Café';
    const desc = card ? card.dataset.desc : '';
    const cat = card ? (card.dataset.cat || '—') : '—';
    const price = card ? (parseFloat(card.dataset.price || 0).toFixed(2).replace('.',',')) : '0,00';
    const stock = card ? (card.querySelector('.stock-small') ? card.querySelector('.stock-small').innerText : '—') : '—';

    const body = document.getElementById('viewProductBody');
    body.innerHTML = `
      <div style="display:flex;gap:12px;align-items:center;">
        <div style="width:96px;height:96px;border-radius:10px;background:linear-gradient(135deg,#00000022,#ffffff08);display:flex;align-items:center;justify-content:center">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none"><path d="M3 7h12v6a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V7z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <div style="flex:1">
          <h5 style="margin:0 0 6px 0">${escapeHtml(title)}</h5>
          <div style="color:var(--ct-muted);margin-bottom:8px">${escapeHtml(desc || '—')}</div>
          <div style="display:flex;gap:8px;align-items:center">
            <div class="category-chip">${escapeHtml(cat)}</div>
            <div class="price-badge">R$ ${escapeHtml(price)}</div>
            <div class="stock-small">${escapeHtml(stock)}</div>
          </div>
        </div>
      </div>
    `;
    const modal = new bootstrap.Modal(document.getElementById('viewProductModal'));
    modal.show();
  }

  // Escape utility
  function escapeHtml(s) {
    if (!s) return '';
    return s.replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'", '&#039;');
  }

  // If validation errors occurred server-side, open the modal to show them
  <?php if (!empty($errors) && $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
    const addModal = new bootstrap.Modal(document.getElementById('addProductModal'));
    addModal.show();
  <?php endif; ?>

  // Initialize filters (ensure initial order)
  applyFilters();
</script>
</body>
</html>
