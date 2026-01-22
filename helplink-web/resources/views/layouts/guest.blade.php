<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Admin Area')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

</head>
<body class="bg-light">
    <body style="background: linear-gradient(to right, #dbeafe, #f0f9ff); min-height: 100vh;">

    <div class="container mt-5">
        @yield('content')
    </div>
</body>
</html>
