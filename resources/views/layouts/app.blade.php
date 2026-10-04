<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            color-scheme: light;
            --portal-ink: #18233d;
            --portal-blue: #2457d6;
            --portal-violet: #6e48c9;
        }

        body {
            min-height: 100vh;
            color: var(--portal-ink);
            background: radial-gradient(ellipse at top left, #e5edff 0, #f4f6fb 42rem, #f8f9fc 100%);
        }

        .portal-navbar {
            background: linear-gradient(110deg, #172d70, #344fb5 58%, #6041a8);
            box-shadow: 0 .4rem 1.5rem rgb(28 48 103 / 18%);
        }

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

        .card {
            border-radius: 1rem;
        }

        .dashboard-stat-card {
            border: 1px solid rgb(40 67 130 / 8%);
            box-shadow: 0 .35rem 1.25rem rgb(30 50 100 / 7%);
            transition: transform .16s ease, box-shadow .16s ease;
        }

        .dashboard-stat-card:hover {
            box-shadow: 0 .7rem 1.6rem rgb(30 50 100 / 14%);
            transform: translateY(-2px);
        }

        .auth-card {
            border: 1px solid rgb(255 255 255 / 70%);
            box-shadow: 0 1rem 3rem rgb(30 50 100 / 12%);
        }

        .btn-primary {
            border-color: var(--portal-blue);
            background: linear-gradient(105deg, var(--portal-blue), var(--portal-violet));
        }

        .btn-primary:hover,
        .btn-primary:focus-visible {
            border-color: #1b43ac;
            background: linear-gradient(105deg, #1b43ac, #5636ac);
        }

        :focus-visible {
            outline: 3px solid #7ca4ff;
            outline-offset: 2px;
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                scroll-behavior: auto !important;
                transition-duration: .01ms !important;
            }
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark portal-navbar mb-4">
        <div class="container">
            <a class="navbar-brand fw-semibold" href="{{ auth()->user()?->role === 'student' ? route('student.dashboard') : (auth()->user()?->role === 'admin' ? route('dashboard') : (request()->routeIs('admin.login') ? route('admin.login') : (request()->routeIs('student.login') ? route('student.login') : route('catalog.courses.index')))) }}">Student Portal</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#portal-navigation" aria-controls="portal-navigation" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse" id="portal-navigation">
                <div class="navbar-nav me-auto gap-lg-3">
                    @guest
                        @unless(request()->routeIs('student.login', 'admin.login'))
                            <a href="{{ route('enrollment.create') }}" class="nav-link portal-nav-link">Apply for Enrollment</a>
                            <a href="{{ route('enrollment.status') }}" class="nav-link portal-nav-link">Check Application Status</a>
                        @endunless
                    @endguest
                    @auth
                        <a
                            href="{{ auth()->user()?->role === 'student' ? route('student.dashboard') : route('dashboard') }}"
                            @class(['nav-link', 'portal-nav-link', 'active' => request()->routeIs('dashboard', 'student.dashboard')])
                            @if (request()->routeIs('dashboard', 'student.dashboard')) aria-current="page" @endif
                        >Dashboard</a>
                    @endauth
                    @unless(auth()->user()?->role === 'student' || request()->routeIs('student.login', 'admin.login'))
                        <a
                            href="{{ auth()->user()?->role === 'admin' ? route('courses.index') : route('catalog.courses.index') }}"
                            @class(['nav-link', 'portal-nav-link', 'active' => request()->routeIs('courses.*', 'catalog.courses.index')])
                            @if (request()->routeIs('courses.*', 'catalog.courses.index')) aria-current="page" @endif
                        >Courses</a>
                    @endunless
                @if(auth()->user()?->role === 'admin')
                    <a
                        href="{{ route('admin.enrollment-applications.index') }}"
                        @class(['nav-link', 'portal-nav-link', 'active' => request()->routeIs('admin.enrollment-applications.*')])
                        @if (request()->routeIs('admin.enrollment-applications.*')) aria-current="page" @endif
                    >Enrollment Applications</a>
                    <a
                        href="{{ route('students.index') }}"
                        @class(['nav-link', 'portal-nav-link', 'active' => request()->routeIs('students.*')])
                        @if (request()->routeIs('students.*')) aria-current="page" @endif
                    >Students</a>
                @endif
                </div>
            @auth
                <form action="{{ route('portal.logout') }}" method="POST" class="d-inline ms-auto">
                    @csrf
                    <button type="submit" class="btn btn-outline-light btn-sm">Logout</button>
                </form>
            @endauth
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="container">
        <!-- Dynamic Page Content -->
        @yield('content')
    </div>

    @php
        $flashMessages = collect([
            ['id' => 'success-toast', 'type' => 'success', 'message' => session('success'), 'delay' => 2000],
            ['id' => 'warning-toast', 'type' => 'warning', 'message' => session('warning'), 'delay' => 6000],
            ['id' => 'error-toast', 'type' => 'danger', 'message' => session('error'), 'delay' => 8000],
            ['id' => 'status-toast', 'type' => 'info', 'message' => session('status'), 'delay' => 6000],
            ['id' => 'message-toast', 'type' => 'info', 'message' => session('message'), 'delay' => 6000],
        ])->filter(fn (array $flashMessage): bool => filled($flashMessage['message']));

        $validationMessages = $errors->all();

        if ($validationMessages !== []) {
            $flashMessages->push([
                'id' => 'validation-errors-toast',
                'type' => 'danger',
                'messages' => $validationMessages,
                'delay' => 8000,
            ]);
        }
    @endphp

    @if ($flashMessages->isNotEmpty())
        <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090; max-width: 100vw;">
            @foreach ($flashMessages as $flashMessage)
                <div
                    id="{{ $flashMessage['id'] }}"
                    class="toast portal-flash-toast text-bg-{{ $flashMessage['type'] }} border-0"
                    role="{{ $flashMessage['type'] === 'danger' ? 'alert' : 'status' }}"
                    aria-live="{{ $flashMessage['type'] === 'danger' ? 'assertive' : 'polite' }}"
                    aria-atomic="true"
                    data-bs-autohide="true"
                    data-bs-delay="{{ $flashMessage['delay'] }}"
                >
                    <div class="d-flex">
                        <div class="toast-body">
                            @if (isset($flashMessage['messages']))
                                <strong>Please review the following:</strong>
                                <ul class="mb-0 mt-1">
                                    @foreach ($flashMessage['messages'] as $validationMessage)
                                        <li>{{ $validationMessage }}</li>
                                    @endforeach
                                </ul>
                            @else
                                {{ $flashMessage['message'] }}
                            @endif
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Bootstrap 5 JavaScript Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @if ($flashMessages->isNotEmpty())
        <script>
            document.querySelectorAll('.portal-flash-toast').forEach((toast) => {
                bootstrap.Toast.getOrCreateInstance(toast).show();
            });
        </script>
    @endif
</body>
</html>
