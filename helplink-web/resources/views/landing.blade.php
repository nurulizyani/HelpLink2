<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>HelpLink | Welcome</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body {
      background-color: #f4f6f8;
    }

    .cover-container {
      background: url('{{ asset('images/volunter.jpg') }}') center center/cover no-repeat;
      color: white;
      text-align: center;
      padding: 6rem 1rem;
      position: relative;
    }

    .cover-container::after {
      content: '';
      background: rgba(44, 62, 80, 0.7);
      position: absolute;
      top: 0; left: 0;
      width: 100%; height: 100%;
      z-index: 1;
    }

    .cover-container > * {
      position: relative;
      z-index: 2;
    }

    .btn-custom {
      margin: 0.5rem;
    }

    .feature-box {
      border-bottom: 3px solid transparent;
      transition: all 0.3s ease;
    }

    .feature-box:hover {
      border-color: #007bff;
      transform: translateY(-5px);
    }

    .highlight-banner {
      background-color: #dc3545;
      color: white;
      padding: 2rem;
      text-align: center;
    }

    .highlight-banner h4 {
      margin-bottom: 0.5rem;
    }
  </style>
</head>
<body>

  <!-- Hero Section -->
  <div class="cover-container">
    <h1 class="display-4 fw-bold">Together, we support our community.</h1>
    <p class="lead">HelpLink empowers everyday people to give or receive help, one request at a time.</p>
    <a href="{{ route('login') }}" class="btn btn-outline-light btn-custom">Login</a>
    <a href="{{ route('register') }}" class="btn btn-warning btn-custom">Register</a>
  </div>

  <!-- 3 Column Info -->
  <div class="container my-5">
    <div class="row text-center g-4">
      <div class="col-md-4">
        <div class="p-4 bg-white shadow-sm feature-box h-100">
          <div class="text-danger mb-3">
            <i class="fas fa-people-group fa-2x"></i>
          </div>
          <h5>We connect people who care with those in need</h5>
          <p class="mb-2">HelpLink is a platform where individuals in your neighborhood offer or request support — safely and directly.</p>
          <a href="{{ route('requests.create') }}">Click here</a> to view current needs.
        </div>
      </div>
      <div class="col-md-4">
        <div class="p-4 bg-white shadow-sm feature-box h-100">
          <div class="text-primary mb-3">
            <i class="fas fa-box-open fa-2x"></i>
          </div>
          <h5>Donate items you no longer need</h5>
          <p class="mb-2">Give useful things a second life. Your item might be just what someone nearby is looking for.</p>
          <a href="{{ route('offer.create') }}" class="text-primary text-decoration-none">Click here</a> to start donating.
        </div>
      </div>
      <div class="col-md-4">
        <div class="p-4 bg-white shadow-sm feature-box h-100">
          <div class="text-success mb-3">
            <i class="fas fa-hand-holding-heart fa-2x"></i>
          </div>
          <h5>Real help, real people</h5>
          <p class="mb-2">Every request on HelpLink comes from real individuals. No middlemen, just clear and honest community support.</p>
          <a href="{{ route('claims.request.available') }}" class="text-success text-decoration-none">Click here</a> to view recent requests.
        </div>
      </div>
    </div>
  </div>

  <!-- How It Works -->
  <div class="container my-5 text-center">
    <h4>How HelpLink Works</h4>
    <p class="text-muted">Create an account, share a request or offer, and connect with people in your community — it's simple and meaningful.</p>
  </div>

  <!-- Red Banner -->
  <div class="highlight-banner">
    <h4>Be part of a caring community.</h4>
    <p>Join HelpLink to lend a hand or ask for support — exactly when it’s needed most.</p>
    <a href="{{ route('register') }}" class="btn btn-light btn-sm">Join Now</a>
  </div>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

<!-- Footer -->
<footer class="text-center py-4 bg-light text-muted">
  <small>© 2025 HelpLink. Built for communities, by communities.</small>
</footer>
</html>
