<?php
// public/orders.php
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Order.php';
require_once __DIR__ . '/../app/Product.php';
require_once __DIR__ . '/../app/Customer.php';
require_once __DIR__ . '/../config/db.php';
Auth::requireAuth();

$errors = [];
$success = null;

// Handle POST actions: create order or update status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_order') {
        // Collect order data
        $customer_id = !empty($_POST['customer_id']) ? intval($_POST['customer_id']) : null;
        $table_number = trim($_POST['table_number'] ?? '');
        $raw_products = $_POST['product_id'] ?? [];
        $raw_qty = $_POST['quantity'] ?? [];

        $items = [];
        for ($i = 0; $i < count($raw_products); $i++) {
            $pid = intval($raw_products[$i]);
            $qty = intval($raw_qty[$i]);
            if ($pid > 0 && $qty > 0) {
                $items[] = ['product_id' => $pid, 'quantity' => $qty];
            }
        }

        if (empty($items)) {
            $errors[] = 'Adicione ao menos um item com quantidade maior que zero.';
        }

        if (empty($errors)) {
            try {
                $orderData = [
                    'customer_id' => $customer_id ?: null,
                    'table_number' => $table_number !== '' ? $table_number : null,
                    'status' => 'pending',
                    'items' => $items
                ];
                $order_id = Order::create($orderData);
                // Order::create returns order id (we return it), if not, try to detect
                if ($order_id) {
                    header('Location: orders.php?created=1');
                    exit;
                } else {
                    // If create returned true/false, redirect with created=1 on true
                    header('Location: orders.php?created=1');
                    exit;
                }
            } catch (Exception $e) {
                $errors[] = 'Erro ao criar pedido: ' . htmlspecialchars($e->getMessage());
            }
        }
    }

    if ($action === 'update_status') {
        $order_id = intval($_POST['order_id'] ?? 0);
        $new_status = $_POST['status'] ?? '';
        $allowed = ['pending','preparing','served','canceled'];
        if ($order_id <= 0 || !in_array($new_status, $allowed)) {
            $errors[] = 'Dados inválidos para atualização de status.';
        } else {
            try {
                $pdo = getPDO();
                $u = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
                $u->execute([$new_status, $order_id]);
                header('Location: orders.php?status_updated=1');
                exit;
            } catch (Exception $e) {
                $errors[] = 'Erro ao atualizar status: ' . htmlspecialchars($e->getMessage());
            }
        }
    }
}

// Flags
$created = isset($_GET['created']) && $_GET['created'] == '1';
$status_updated = isset($_GET['status_updated']) && $_GET['status_updated'] == '1';

// Fetch data for display
$ordersRaw = Order::all();
// Build detailed orders (including items) for modal usage
$detailedOrders = [];
foreach ($ordersRaw as $o) {
    $detailedOrders[] = Order::find($o['id']);
}
$products = Product::all();
$customers = Customer::all();
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title>Pedidos - Coffee Shop</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@300;400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <nav class="navbar navbar-expand py-3">
    <div class="container">
      <a class="navbar-brand brand-title" href="dashboard.php">Coffee Shop</a>
      <div class="ms-auto d-flex align-items-center gap-3">
        <a class="btn btn-sm btn-outline-light" href="dashboard.php">Voltar</a>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createOrderModal">+ Novo Pedido</button>
      </div>
    </div>
  </nav>

  <div class="page">
    <div class="panel">
      <div class="heading">
        <div>
          <h4 style="margin:0">Pedidos</h4>
          <div class="small-muted">Acompanhe, visualize e atualize os pedidos em andamento</div>
        </div>
        <div class="small-muted">Total: <?php echo count($detailedOrders); ?></div>
      </div>

      <?php if ($created): ?>
        <div class="alert alert-success">Pedido criado com sucesso.</div>
      <?php endif; ?>
      <?php if ($status_updated): ?>
        <div class="alert alert-success">Status atualizado.</div>
      <?php endif; ?>
      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $e) echo '<li>'.$e.'</li>'; ?></ul></div>
      <?php endif; ?>

      <div class="controls">
        <input id="searchInput" class="form-control search-input" placeholder="Pesquisar por cliente, mesa ou ID...">
        <select id="statusFilter" class="form-select" style="max-width:180px;">
          <option value="">Todos status</option>
          <option value="pending">Pendente</option>
          <option value="preparing">Preparando</option>
          <option value="served">Servido</option>
          <option value="canceled">Cancelado</option>
        </select>
        <div class="ms-auto small-muted">Clique em <strong>Ver</strong> para detalhes</div>
      </div>

      <div class="table-fixed">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th style="width:6%">ID</th>
              <th>Cliente</th>
              <th style="width:12%">Mesa</th>
              <th style="width:14%">Total</th>
              <th style="width:16%">Status</th>
              <th style="width:18%">Ações</th>
            </tr>
          </thead>
          <tbody id="ordersBody">
            <?php if (empty($detailedOrders)): ?>
              <tr><td colspan="6"><div class="empty-state">Nenhum pedido registrado.</div></td></tr>
            <?php else: ?>
              <?php foreach($detailedOrders as $o): 
                $json = htmlspecialchars(json_encode($o), ENT_QUOTES);
                ?>
                <tr data-id="<?php echo $o['id']; ?>" data-json="<?php echo $json; ?>">
                  <td><?php echo $o['id']; ?></td>
                  <td><?php echo htmlspecialchars($o['customer_name'] ?? '—'); ?></td>
                  <td><?php echo htmlspecialchars($o['table_number'] ?? '—'); ?></td>
                  <td>R$ <?php echo number_format($o['total'],2,',','.'); ?></td>
                  <td>
                    <?php
                      $st = $o['status'];
                      $cls = 'st-pending';
                      if ($st === 'preparing') $cls = 'st-preparing';
                      if ($st === 'served') $cls = 'st-served';
                      if ($st === 'canceled') $cls = 'st-canceled';
                    ?>
                    <span class="badge-status <?php echo $cls; ?>"><?php echo htmlspecialchars(ucfirst($st)); ?></span>
                  </td>
                  <td>
                    <div class="d-flex gap-2">
                      <button class="btn btn-sm btn-outline" onclick="openViewModal(<?php echo $o['id']; ?>)">Ver</button>

                      <form method="post" style="display:inline;">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                        <select name="status" class="form-select form-select-sm" style="max-width:140px;display:inline-block;">
                          <option value="pending" <?php if($o['status']=='pending') echo 'selected'; ?>>Pendente</option>
                          <option value="preparing" <?php if($o['status']=='preparing') echo 'selected'; ?>>Preparando</option>
                          <option value="served" <?php if($o['status']=='served') echo 'selected'; ?>>Servido</option>
                          <option value="canceled" <?php if($o['status']=='canceled') echo 'selected'; ?>>Cancelado</option>
                        </select>
                        <button class="btn btn-sm btn-primary" type="submit">Atualizar</button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>

  <!-- Modal: View Order -->
  <div class="modal fade" id="viewOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md">
      <div class="modal-content">
        <div class="modal-header border-0">
          <h5 class="modal-title" id="viewOrderTitle">Pedido</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>
        <div class="modal-body" id="viewOrderBody">
          <!-- preenchido via JS -->
        </div>
        <div class="modal-footer border-0">
          <button class="btn btn-outline" data-bs-dismiss="modal">Fechar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal: Create Order -->
  <div class="modal fade" id="createOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <form method="post" id="createOrderForm" class="modal-content needs-validation" novalidate>
        <input type="hidden" name="action" value="create_order">
        <div class="modal-header border-0">
          <h5 class="modal-title">Novo Pedido</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Cliente (opcional)</label>
              <select name="customer_id" class="form-select">
                <option value="">- Cliente não selecionado -</option>
                <?php foreach($customers as $c): ?>
                  <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Mesa (opcional)</label>
              <input name="table_number" type="text" class="form-control" placeholder="Ex: 12 / Takeaway">
            </div>

            <div class="col-12">
              <label class="form-label">Itens</label>
              <div id="itemsContainer" class="mb-2"></div>
              <div class="d-flex gap-2 align-items-center">
                <select id="productSelect" class="form-select" style="min-width:240px;">
                  <option value="">Selecione um café...</option>
                  <?php foreach($products as $p): ?>
                    <option value="<?php echo $p['id']; ?>" data-price="<?php echo $p['price']; ?>"><?php echo htmlspecialchars($p['name'] . ' — R$ ' . number_format($p['price'],2,',','.')); ?></option>
                  <?php endforeach; ?>
                </select>
                <input id="productQty" type="number" min="1" value="1" class="form-control" style="max-width:100px;">
                <button id="addItemBtn" type="button" class="btn btn-primary">Adicionar</button>
              </div>
            </div>

            <div class="col-12">
              <div class="d-flex justify-content-between align-items-center">
                <div class="small-muted">Total estimado</div>
                <div style="font-weight:700; font-size:18px;">R$ <span id="orderTotal">0,00</span></div>
              </div>
            </div>

          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Salvar Pedido</button>
        </div>
      </form>
    </div>
  </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Convert PHP orders data into JS map
  const orderRows = Array.from(document.querySelectorAll('#ordersBody tr[data-json]'));
  const ordersMap = {};
  orderRows.forEach(r => {
    try {
      ordersMap[r.dataset.id] = JSON.parse(r.dataset.json);
    } catch (e) { /* ignore */ }
  });

  // View order
  function openViewModal(id) {
    const order = ordersMap[id];
    const title = 'Pedido #' + id;
    const body = document.getElementById('viewOrderBody');
    if (!order) {
      body.innerHTML = '<div class="empty-state">Detalhes indisponíveis.</div>';
    } else {
      let html = '';
      html += '<div style="margin-bottom:10px;"><strong>Cliente:</strong> ' + (order.customer_name || '—') + '</div>';
      html += '<div style="margin-bottom:10px;"><strong>Mesa:</strong> ' + (order.table_number || '—') + '</div>';
      html += '<hr>';
      html += '<div><strong>Itens</strong></div>';
      html += '<ul class="list-unstyled" style="margin-left:0;padding-left:0;">';
      (order.items || []).forEach(it => {
        html += '<li style="padding:8px 0;border-bottom:1px dashed rgba(255,255,255,0.03)">';
        html += '<div style="display:flex;justify-content:space-between;align-items:center">';
        html += '<div><strong>' + escapeHtml(it.product_name) + '</strong><div class="small-muted">' + (it.quantity) + ' × R$ ' + (parseFloat(it.unit_price).toFixed(2).replace('.',',')) + '</div></div>';
        html += '<div style="font-weight:700">R$ ' + (parseFloat(it.subtotal).toFixed(2).replace('.',',')) + '</div>';
        html += '</div></li>';
      });
      html += '</ul>';
      html += '<hr>';
      html += '<div style="display:flex;justify-content:space-between"><div class="small-muted">Total:</div><div style="font-weight:700">R$ ' + parseFloat(order.total).toFixed(2).replace('.',',') + '</div></div>';
      body.innerHTML = html;
    }
    document.getElementById('viewOrderTitle').innerText = title;
    const modal = new bootstrap.Modal(document.getElementById('viewOrderModal'));
    modal.show();
  }

  // Search & filter
  const searchInput = document.getElementById('searchInput');
  const statusFilter = document.getElementById('statusFilter');
  const ordersBody = document.getElementById('ordersBody');

  function applyFilters() {
    const q = searchInput.value.trim().toLowerCase();
    const st = statusFilter.value;
    const rows = ordersBody.querySelectorAll('tr[data-id]');
    rows.forEach(row => {
      const id = row.dataset.id;
      const o = ordersMap[id];
      const text = (row.innerText || '').toLowerCase();
      const matchQ = !q || text.indexOf(q) !== -1;
      const matchSt = !st || (o && o.status === st);
      row.style.display = (matchQ && matchSt) ? '' : 'none';
    });
  }
  searchInput.addEventListener('input', applyFilters);
  statusFilter.addEventListener('change', applyFilters);

  // Create order - items handling
  const itemsContainer = document.getElementById('itemsContainer');
  const productSelect = document.getElementById('productSelect');
  const productQty = document.getElementById('productQty');
  const addItemBtn = document.getElementById('addItemBtn');
  const orderTotalEl = document.getElementById('orderTotal');

  const items = []; // { product_id, name, price, quantity, subtotal }

  function renderItems() {
    itemsContainer.innerHTML = '';
    let total = 0;
    items.forEach((it, idx) => {
      total += it.subtotal;
      const row = document.createElement('div');
      row.className = 'd-flex justify-content-between align-items-center mb-2';
      row.innerHTML = `<div>
          <strong>${escapeHtml(it.name)}</strong>
          <div class="small-muted">${it.quantity} × R$ ${it.price.toFixed(2).replace('.',',')}</div>
        </div>
        <div style="display:flex;gap:8px;align-items:center">
          <div style="font-weight:700">R$ ${it.subtotal.toFixed(2).replace('.',',')}</div>
          <button type="button" class="btn btn-sm btn-outline" onclick="removeItem(${idx})">Remover</button>
        </div>`;
      itemsContainer.appendChild(row);

      // Hidden inputs for the form
      const hidPid = document.createElement('input');
      hidPid.type = 'hidden';
      hidPid.name = 'product_id[]';
      hidPid.value = it.product_id;
      itemsContainer.appendChild(hidPid);
      const hidQty = document.createElement('input');
      hidQty.type = 'hidden';
      hidQty.name = 'quantity[]';
      hidQty.value = it.quantity;
      itemsContainer.appendChild(hidQty);
    });
    orderTotalEl.innerText = total.toFixed(2).replace('.',',');
  }

  function removeItem(i) {
    items.splice(i,1);
    renderItems();
  }

  addItemBtn.addEventListener('click', () => {
    const pid = parseInt(productSelect.value || 0);
    const qty = parseInt(productQty.value || 0);
    if (!pid || qty <= 0) return alert('Selecione um café e informe quantidade válida.');
    const opt = productSelect.options[productSelect.selectedIndex];
    const name = opt.text;
    const price = parseFloat(opt.dataset.price || 0);
    const subtotal = price * qty;
    items.push({ product_id: pid, name, price, quantity: qty, subtotal });
    renderItems();
  });

  function escapeHtml(s) {
    if (!s) return '';
    return s.replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'", '&#039;');
  }

  // Form validation
  (function () {
    'use strict'
    const form = document.getElementById('createOrderForm');
    form.addEventListener('submit', function (event) {
      if (items.length === 0) {
        event.preventDefault(); event.stopPropagation();
        alert('Adicione ao menos um item ao pedido.');
        return;
      }
      if (!form.checkValidity()) {
        event.preventDefault(); event.stopPropagation();
        form.classList.add('was-validated');
      }
    }, false);
  })();

  // If there were server validation errors when submitting, keep modal open
  <?php if (!empty($errors) && $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
    const createModal = new bootstrap.Modal(document.getElementById('createOrderModal'));
    createModal.show();
  <?php endif; ?>
</script>
</body>
</html>
