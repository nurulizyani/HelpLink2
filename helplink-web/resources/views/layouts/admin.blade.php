<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'HelpLink Admin')</title>

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            margin: 0;
            background-color: #f4f6f8;
            font-family: 'Segoe UI', system-ui, sans-serif;
            overflow-x: hidden;
        }

        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* ================= SIDEBAR ================= */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100vh;
            background: linear-gradient(180deg, #1f2937, #111827);
            color: #e5e7eb;
            padding: 24px 16px;
            z-index: 1000;
            transition: width .25s ease;
            display: flex;
            flex-direction: column;
        }

        body.sidebar-collapsed .sidebar {
            width: 80px;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 24px;
            color: #fff;
        }

        .sidebar-brand-icon {
            display: none;
            font-size: 1.4rem;
        }

        body.sidebar-collapsed .sidebar-brand-text {
            display: none;
        }

        body.sidebar-collapsed .sidebar-brand-icon {
            display: block;
        }

        .nav-link {
            color: #d1d5db;
            padding: 10px 12px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 6px;
            font-size: .95rem;
        }

        .nav-link.active {
            background-color: #2563eb;
            color: #fff;
            font-weight: 600;
        }

        body.sidebar-collapsed .menu-text {
            display: none;
        }

        body.sidebar-collapsed .nav-link {
            justify-content: center;
        }

        .sidebar-menu {
            flex: 1;
        }

        /* ================= CONTENT ================= */
        .content-wrapper {
            margin-left: 250px;
            width: calc(100% - 250px);
            transition: all .25s ease;
        }

        body.sidebar-collapsed .content-wrapper {
            margin-left: 80px;
            width: calc(100% - 80px);
        }

        .topbar {
            height: 72px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .main-content {
            padding: 24px;
        }

        /* ================= NOTIFICATION ================= */
        .notification-item {
            font-size: 0.85rem;
            white-space: normal;
        }

        .notification-unread {
            background: #eef4ff;
        }

        @media (max-width: 991px) {
            .sidebar { display: none; }
            .content-wrapper {
                margin-left: 0;
                width: 100%;
            }
        }
    </style>
</head>

<body>

<div class="admin-wrapper">

<!-- ================= SIDEBAR ================= -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <i class="fas fa-hand-holding-heart sidebar-brand-icon"></i>
        <span class="sidebar-brand-text">HelpLink Admin</span>
    </div>

    <div class="sidebar-menu">
        <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <i class="fas fa-chart-line"></i><span class="menu-text">Dashboard</span>
        </a>

        <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
            <i class="fas fa-users"></i><span class="menu-text">Manage Users</span>
        </a>

        <a href="{{ route('admin.requests.index') }}" class="nav-link {{ request()->routeIs('admin.requests.*') ? 'active' : '' }}">
            <i class="fas fa-box-open"></i><span class="menu-text">Requests</span>
        </a>

        <a href="{{ route('admin.offers.index') }}" class="nav-link {{ request()->routeIs('admin.offers.*') ? 'active' : '' }}">
            <i class="fas fa-gift"></i><span class="menu-text">Offers</span>
        </a>

        <a href="{{ route('admin.notifications.index') }}" class="nav-link {{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}">
            <i class="fas fa-bell"></i><span class="menu-text">Notifications</span>
        </a>
    </div>

    <form method="POST" action="{{ route('admin.logout') }}">
        @csrf
        <button class="btn btn-danger w-100">
            <i class="fas fa-sign-out-alt"></i>
            <span class="menu-text ms-2">Logout</span>
        </button>
    </form>
</aside>

<!-- ================= CONTENT ================= -->
<div class="content-wrapper">

<header class="topbar">
    <button id="sidebarToggle" class="btn btn-light">
        <i class="fas fa-bars"></i>
    </button>

    <!--REAL NOTIFICATION -->
    <div class="dropdown">
        <button class="btn btn-light position-relative"
                data-bs-toggle="dropdown">
            <i class="fas fa-bell"></i>
            <span id="notif-count"
                  class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none">
            </span>
        </button>

        <ul class="dropdown-menu dropdown-menu-end shadow"
            id="notif-list"
            style="width:320px">
            <li class="dropdown-header fw-bold">Notifications</li>
            <li class="text-center small text-muted py-2">Loading...</li>
        </ul>
    </div>
</header>

<main class="main-content">
    @yield('content')
</main>

</div>
</div>

<!-- ================= SCRIPTS ================= -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.getElementById('sidebarToggle')
    .addEventListener('click', () => {
        document.body.classList.toggle('sidebar-collapsed');
    });

async function loadNotifications() {
    const res = await fetch("{{ route('admin.notifications.unread') }}");
    const data = await res.json();

    const list = document.getElementById('notif-list');
    const badge = document.getElementById('notif-count');

    list.innerHTML = '<li class="dropdown-header fw-bold">Notifications</li>';

    if (data.count > 0) {
        badge.classList.remove('d-none');
        badge.innerText = data.count;
    } else {
        badge.classList.add('d-none');
    }

    if (data.notifications.length === 0) {
        list.innerHTML += '<li class="text-center small text-muted py-2">No new notifications</li>';
        return;
    }

    data.notifications.forEach(n => {
        list.innerHTML += `
            <li>
                <a href="#" class="dropdown-item notification-item notification-unread"
                   onclick="markAsRead('${n.id}')">
                    ${n.message}
                </a>
            </li>`;
    });

    list.innerHTML += `
        <li><hr class="dropdown-divider"></li>
        <li>
            <a href="{{ route('admin.notifications.index') }}"
               class="dropdown-item text-center small text-primary">
               View all notifications
            </a>
        </li>`;
}

        async function markAsRead(id) {
            await fetch(`/admin/notifications/read/${id}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
            loadNotifications();
        }

        loadNotifications();
        setInterval(loadNotifications, 15000);
        </script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    })
</script>

</body>
</html>
