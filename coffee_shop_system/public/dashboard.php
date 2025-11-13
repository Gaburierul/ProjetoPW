<?php
// public/dashboard.php
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Product.php';
require_once __DIR__ . '/../app/Customer.php';
require_once __DIR__ . '/../app/Order.php';
Auth::requireAuth();
$user = Auth::user();

// Small aggregations for cards
$products = Product::all();
$customers = Customer::all();
$orders = Order::all();

$countProducts = count($products);
$countCustomers = count($customers);
$countOrders = count($orders);
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title>Dashboard - Coffee Shop</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="style.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@300;400;700&display=swap" rel="stylesheet">
</head>
<body>
  <nav class="navbar navbar-expand py-3">
    <div class="container">
      <a class="navbar-brand brand-title" href="#">Coffee Shop</a>
      <div class="ms-auto d-flex align-items-center gap-3">
        <div class="small-muted">Olá, <?php echo htmlspecialchars($user['username']); ?></div>
        <a class="btn btn-sm btn-outline-light" href="logout.php">Sair</a>
      </div>
    </div>
  </nav>

  <div class="page">
    <div class="panel">
      <div class="d-flex justify-content-between align-items-start">
        <div class="welcome">
          <div class="avatar"><?php echo strtoupper(substr(htmlspecialchars($user['username']),0,1)); ?></div>
          <div>
            <h4 class="mb-1" style="margin:0">Bem-vindo, <?php echo htmlspecialchars($user['username']); ?></h4>
          </div>
        </div>
        <div class="text-end small-muted">
          <div><?php echo date('d/m/Y'); ?></div>
          <div class="small-muted">Sistema</div>
        </div>
      </div>

      <div class="cards">
        <div class="stat-card">
          <div class="stat-icon">
            <!-- cup icon -->
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none"><path d="M3 7h12v6a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V7z" fill="currentColor" opacity="0.14"/></svg>
          </div>
          <div>
            <div class="stat-title">Cafés cadastrados</div>
            <div class="stat-value"><?php echo $countProducts; ?></div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon" style="background:linear-gradient(135deg,var(--ct-tan),#b88458); color:#2b1f1a;">
            <!-- client icon -->
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8z" fill="currentColor" opacity="0.14"/></svg>
          </div>
          <div>
            <div class="stat-title">Clientes</div>
            <div class="stat-value"><?php echo $countCustomers; ?></div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon" style="background:linear-gradient(135deg,#9b8576,#7a5f4e);">
            <!-- orders icon -->
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none"><path d="M3 6h18M8 6v12" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
          </div>
          <div>
            <div class="stat-title">Pedidos</div>
            <div class="stat-value"><?php echo $countOrders; ?></div>
          </div>
        </div>
      </div>

      <div class="quick-links mt-3">
        <a class="card-link quick-card" href="products.php">
          <div>
            <div style="font-weight:700">Gerenciar Cafés</div>
            <div class="small-muted">Adicionar, editar e organizar cardápio</div>
          </div>
          <div>
            <button class="btn btn-ghost">Abrir</button>
          </div>
        </a>

        <a class="card-link quick-card" href="customers.php">
          <div>
            <div style="font-weight:700">Clientes</div>
            <div class="small-muted">Ver preferências e notas</div>
          </div>
          <div><button class="btn btn-ghost">Abrir</button></div>
        </a>

        <a class="card-link quick-card" href="orders.php">
          <div>
            <div style="font-weight:700">Pedidos</div>
            <div class="small-muted">Acompanhar e finalizar</div>
          </div>
          <div><button class="btn btn-ghost">Abrir</button></div>
        </a>
      </div>
    </div>
  </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>