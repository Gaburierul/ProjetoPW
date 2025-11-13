<?php
// public/customers.php (Coffee Talk themed)
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Customer.php';
Auth::requireAuth();

// Handle create customer POST (must be before output)
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_customer') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if ($name === '') $errors[] = 'O campo <strong>Nome</strong> é obrigatório.';
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email inválido.';
    if (mb_strlen($name) > 100) $errors[] = 'Nome muito longo (máx. 100 caracteres).';

    if (empty($errors)) {
        try {
            $ok = Customer::create([
                'name' => $name,
                'phone' => $phone !== '' ? $phone : null,
                'email' => $email !== '' ? $email : null,
                'notes' => $notes !== '' ? $notes : null
            ]);
            if ($ok) {
                header('Location: customers.php?created=1');
                exit;
            } else {
                $errors[] = 'Erro ao salvar o cliente.';
            }
        } catch (Exception $e) {
            $errors[] = 'Erro no servidor: ' . htmlspecialchars($e->getMessage());
        }
    }
}

$success = isset($_GET['created']) && $_GET['created'] == '1';
$customers = Customer::all();
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title>Clientes - Coffee Shop</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="style.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@300;400;700&display=swap" rel="stylesheet">
</head>
<body>
  <nav class="navbar navbar-expand py-3">
    <div class="container">
      <a class="navbar-brand brand-title" href="dashboard.php">Coffee Shop</a>
      <div class="ms-auto d-flex align-items-center gap-3">
        <a class="btn btn-sm btn-outline-light" href="dashboard.php">Voltar</a>
      </div>
    </div>
  </nav>

  <div class="container-page">
    <div class="panel">
      <div class="heading mb-3">
        <div>
          <h4 style="margin:0">Clientes</h4>
          <div class="small-muted">Gerencie as preferências e notas dos clientes</div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <input id="searchInput" class="form-control search-input" placeholder="Pesquisar por nome, telefone ou email">
          <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCustomerModal">+ Adicionar Cliente</button>
        </div>
      </div>

      <?php if ($success): ?>
        <div class="alert alert-success">Cliente criado com sucesso.</div>
      <?php endif; ?>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
          <ul class="mb-0"><?php foreach ($errors as $err): ?><li><?php echo $err; ?></li><?php endforeach; ?></ul>
        </div>
      <?php endif; ?>

      <div class="table-fixed">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th style="width:6%;">ID</th>
              <th>Nome</th>
              <th style="width:18%;">Telefone</th>
              <th style="width:24%;">Email</th>
              <th style="width:12%;">Ações</th>
            </tr>
          </thead>
          <tbody id="customersTableBody">
            <?php if (empty($customers)): ?>
              <tr><td colspan="5"><div class="empty-state">Nenhum cliente registrado.</div></td></tr>
            <?php else: ?>
              <?php foreach($customers as $c): ?>
                <tr>
                  <td><?php echo $c['id']; ?></td>
                  <td><?php echo htmlspecialchars($c['name']); ?></td>
                  <td><?php echo htmlspecialchars($c['phone'] ?? '-'); ?></td>
                  <td><?php echo htmlspecialchars($c['email'] ?? '-'); ?></td>
                  <td>
                    <button class="notes-btn" type="button" onclick="showNotes(<?php echo $c['id']; ?>, <?php echo json_encode($c['notes'] ?? ''); ?>)">Notas</button>
                    <!-- Aqui dá para adicionar editar/excluir futuramente -->
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Modal: Add Customer -->
  <div class="modal fade" id="addCustomerModal" tabindex="-1" aria-labelledby="addCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <form method="post" class="modal-content needs-validation" novalidate>
        <input type="hidden" name="action" value="create_customer">
        <div class="modal-header border-0">
          <h5 class="modal-title" id="addCustomerModalLabel">Adicionar Cliente</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Nome <span class="text-danger">*</span></label>
            <input name="name" type="text" class="form-control" required maxlength="100" placeholder="Nome completo">
            <div class="invalid-feedback">Por favor, informe o nome.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Telefone</label>
            <input name="phone" type="text" class="form-control" placeholder="(00) 9 9999-9999">
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input name="email" type="email" class="form-control" placeholder="exemplo@cliente.com">
            <div class="invalid-feedback">Email inválido.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Notas</label>
            <textarea name="notes" rows="3" class="form-control" placeholder="Preferências, alergias, bebidas favoritas..."></textarea>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Salvar Cliente</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal: View Notes -->
  <div class="modal fade" id="notesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
      <div class="modal-content">
        <div class="modal-header">
          <h6 class="modal-title">Notas do Cliente</h6>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>
        <div class="modal-body" id="notesModalBody"></div>
      </div>
    </div>
  </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const searchInput = document.getElementById('searchInput');
  const tableBody = document.getElementById('customersTableBody');

  searchInput.addEventListener('input', function() {
    const q = this.value.trim().toLowerCase();
    const rows = tableBody.querySelectorAll('tr');
    rows.forEach(row => {
      const text = row.innerText.toLowerCase();
      row.style.display = text.indexOf(q) !== -1 ? '' : 'none';
    });
  });

  function showNotes(id, notes) {
    const modalBody = document.getElementById('notesModalBody');
    notes = notes || 'Sem notas.';
    modalBody.innerHTML = '<div style="white-space:pre-wrap; color:var(--ct-cream)">' + escapeHtml(notes) + '</div>';
    const notesModal = new bootstrap.Modal(document.getElementById('notesModal'));
    notesModal.show();
  }

  function escapeHtml(s) {
    if (!s) return '';
    return s.replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'", '&#039;');
  }

  // Bootstrap validation for modal form
  (function () {
    'use strict'
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(function (form) {
      form.addEventListener('submit', function (event) {
        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false);
    });
  })();

  <?php if (!empty($errors) && $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
    const addModal = new bootstrap.Modal(document.getElementById('addCustomerModal'));
    addModal.show();
  <?php endif; ?>
</script>
</body>
</html>