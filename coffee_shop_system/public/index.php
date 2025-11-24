<?php
// public/login.php
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/User.php';
User::ensureAdminExists();

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $u = $_POST['username'] ?? '';
    $p = $_POST['password'] ?? '';
    if (Auth::login($u,$p)){
        header('Location: dashboard.php');
        exit;
    } else {
        $err = 'Credenciais inválidas';
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title>Login - Coffee Shop</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@300;400;700&display=swap" rel="stylesheet">
  <style>
    :root{
      --ct-bg: #1e1512;
      --ct-surface: #2b221f;
      --ct-cream: #f3e9dc;
      --ct-tan: #c9a57a;
      --ct-teal: #6fb3ad;
      --ct-muted: #9b8576;
      --card-radius: 14px;
    }

    /* Page background */
    body{
      margin:0;
      min-height:100vh;
      font-family: 'Merriweather', serif;
      background: radial-gradient(circle at 10% 10%, rgba(255,255,255,0.02), transparent 10%),
                  linear-gradient(180deg, #241815 0%, var(--ct-bg) 100%);
      color: var(--ct-cream);
      display:flex;
      align-items:center;
      justify-content:center;
      padding: 36px 12px;
    }

    /* Center card */
    .login-wrap {
      width:100%;
      max-width:440px;
    }

    .card-auth {
      background: linear-gradient(180deg, rgba(43,34,31,0.7), rgba(20,14,12,0.6));
      border-radius: 16px;
      padding: 22px;
      box-shadow: 0 12px 36px rgba(0,0,0,0.6);
      border: 1px solid rgba(255,255,255,0.03);
      color: var(--ct-cream);
    }

    .brand {
      display:flex;
      gap:12px;
      align-items:center;
      margin-bottom:8px;
    }
    .brand .logo {
      width:48px;height:48px;border-radius:10px;
      background: linear-gradient(135deg,var(--ct-tan), #b88458);
      display:flex;align-items:center;justify-content:center;font-weight:700;color:#2b1f1a;font-size:20px;
      box-shadow: 0 6px 18px rgba(0,0,0,0.45);
    }
    .brand .title { font-size:20px; font-weight:700; margin:0; color:var(--ct-cream); }
    .brand .subtitle { font-size:13px; color:var(--ct-muted); margin:0; }

    /* Form */
    .form-control, .form-select {
      background: rgba(255,255,255,0.02) !important;
      color: var(--ct-cream) !important;
      border: 1px solid rgba(255,255,255,0.04) !important;
      box-shadow: none !important;
    }
    input::placeholder, textarea::placeholder {
      color: var(--ct-muted) !important;
      opacity: 1 !important;
    }
    .btn-primary {
      background: linear-gradient(90deg,var(--ct-teal), #5aa79f) !important;
      border: none !important;
      color: #fff !important;
      font-weight:700;
    }

    /* Small note */
    .small-note { color: var(--ct-muted); font-size:13px; margin-top:12px; }

    /* Error box */
    .alert-danger {
      background: rgba(160,60,60,0.12);
      color: #f3d2d2;
      border: 1px solid rgba(255,255,255,0.03);
    }

    /* Autofill */
    input:-webkit-autofill,
    input:-webkit-autofill:focus,
    textarea:-webkit-autofill {
      -webkit-text-fill-color: var(--ct-cream) !important;
      -webkit-box-shadow: 0 0 0px 1000px rgba(0,0,0,0.08) inset !important;
      box-shadow: 0 0 0px 1000px rgba(0,0,0,0.08) inset !important;
    }

    /* Footer small */
    .helper { text-align:center; margin-top:14px; color:var(--ct-muted); font-size:13px; }

    @media (max-width:480px){
      .card-auth { padding:16px; }
      .brand .title { font-size:18px; }
    }
  </style>
</head>
<body>
  <div class="login-wrap">
    <div class="card-auth">
      <div class="brand">
        <div class="logo">C</div>
        <div>
          <p class="title">Coffee Shop</p>
          <p class="subtitle">Entrar na Cafeteria — painel administrativo</p>
        </div>
      </div>

      <?php if($err): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($err); ?></div>
      <?php endif; ?>

      <form method="post" novalidate>
        <div class="mb-3">
          <label class="form-label">Usuário</label>
          <input name="username" class="form-control" placeholder="Usuário" required autofocus>
        </div>
        <div class="mb-3">
          <label class="form-label">Senha</label>
          <input name="password" type="password" class="form-control" placeholder="Senha" required>
        </div>
        <div class="d-grid">
          <button class="btn btn-primary btn-lg" type="submit">Entrar</button>
        </div>
        <div class="small-note">Usuário admin inicial: <strong>admin</strong> / <strong>admin123</strong></div>
      </form>

    </div>
  </div>

<script>
  // client validation: prevent empty submit (nicety)
  (function(){
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e){
      const u = form.querySelector('[name="username"]').value.trim();
      const p = form.querySelector('[name="password"]').value.trim();
      if (!u || !p) {
        e.preventDefault();
        alert('Preencha usuário e senha.');
      }
    });
  })();
</script>
</body>
</html>
