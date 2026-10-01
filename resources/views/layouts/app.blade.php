<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <!-- Navigation Bar -->
    <nav class="navbar navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand fw-semibold" href="{{ auth()->check() && auth()->user()->role === 'student' ? route('student.dashboard') : route('students.index') }}">Student Portal</a>
            
            <div class="d-flex flex-wrap align-items-center gap-3">
            @auth
                @if(auth()->user()->role === 'admin')
                    <a class="link-light text-decoration-none" href="{{ route('students.index') }}">Students</a>
                    <a class="link-light text-decoration-none" href="{{ route('admin.enrollment-applications.index') }}">Applications</a>
                    <a class="link-light text-decoration-none" href="{{ route('admin.enrollment-applications.history') }}">History</a>
                    <a class="link-light text-decoration-none" href="{{ route('admin.enrollments.index') }}">Enrollments</a>
                    <a class="link-light text-decoration-none" href="{{ route('departments.index') }}">Departments</a>
                    <a class="link-light text-decoration-none" href="{{ route('programs.index') }}">Programs</a>
                    <a class="link-light text-decoration-none" href="{{ route('courses.index') }}">Subjects</a>
                @endif
                <form action="{{ route('portal.logout') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-light btn-sm">
                        Logout
                    </button>
                </form>
            @endauth
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="container">
        <!-- Flash Success Notification -->
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

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

    <!-- Bootstrap 5 JavaScript Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>