<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .portal-nav-link {
            border-bottom: 2px solid transparent;
            color: rgba(255, 255, 255, .75);
            padding: .5rem .25rem;
            text-decoration: none;
        }

        .portal-nav-link:hover,
        .portal-nav-link.active {
            border-bottom-color: #fff;
            color: #fff;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation Bar -->
    <nav class="navbar navbar-dark bg-dark mb-4">
        <div class="container">
            <div class="d-flex align-items-center gap-4">
                <a class="navbar-brand fw-semibold mb-0" href="{{ auth()->user()?->role === 'student' ? route('student.dashboard') : (session('portal_access') ? route('dashboard') : (request()->routeIs('admin.login') ? route('admin.login') : (request()->routeIs('student.login') ? route('student.login') : route('catalog.courses.index')))) }}">Student Portal</a>
                <div class="d-flex align-items-center gap-3">
                    @if(session('portal_access'))
                        <a
                            href="{{ auth()->user()?->role === 'student' ? route('student.dashboard') : route('dashboard') }}"
                            @class(['portal-nav-link', 'active' => request()->routeIs('dashboard', 'student.dashboard')])
                            @if (request()->routeIs('dashboard', 'student.dashboard')) aria-current="page" @endif
                        >Dashboard</a>
                    @endif
                    @unless(request()->routeIs('student.login', 'admin.login'))
                        <a
                            href="{{ auth()->user()?->role === 'admin' ? route('courses.index') : route('catalog.courses.index') }}"
                            @class(['portal-nav-link', 'active' => request()->routeIs('courses.*', 'catalog.courses.index')])
                            @if (request()->routeIs('courses.*', 'catalog.courses.index')) aria-current="page" @endif
                        >Courses</a>
                    @endunless
                @if(session('portal_access') && auth()->user()?->role === 'admin')
                    <a
                        href="{{ route('admin.enrollment-applications.index') }}"
                        @class(['portal-nav-link', 'active' => request()->routeIs('admin.enrollment-applications.*')])
                        @if (request()->routeIs('admin.enrollment-applications.*')) aria-current="page" @endif
                    >Enrollment Applications</a>
                    <a
                        href="{{ route('students.index') }}"
                        @class(['portal-nav-link', 'active' => request()->routeIs('students.*')])
                        @if (request()->routeIs('students.*')) aria-current="page" @endif
                    >Students</a>
                @endif
                </div>
            </div>
            @if(session('portal_access'))
                <form action="{{ route('portal.logout') }}" method="POST" class="d-inline ms-auto">
                    @csrf
                    <button type="submit" class="btn btn-outline-light btn-sm">Logout</button>
                </form>
            @endif
        </div>
    </nav>

    <!-- Main Container -->
    <div class="container">
        <!-- Flash Warning Notification -->
        @if (session('warning'))
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                {{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Dynamic Page Content -->
        @yield('content')
    </div>

    @if (session('success'))
        <div class="toast-container position-fixed bottom-0 end-0 p-3">
            <div
                id="success-toast"
                class="toast text-bg-success border-0"
                role="status"
                aria-live="polite"
                aria-atomic="true"
                data-bs-autohide="true"
                data-bs-delay="2000"
            >
                <div class="d-flex">
                    <div class="toast-body">{{ session('success') }}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        </div>
    @endif

    <!-- Bootstrap 5 JavaScript Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @if (session('success'))
        <script>
            bootstrap.Toast.getOrCreateInstance(document.getElementById('success-toast')).show();
        </script>
    @endif
</body>
</html>