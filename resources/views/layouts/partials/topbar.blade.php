<header id="page-topbar">
    <div class="layout-width">
        <div class="navbar-header">
            <div class="d-flex">
                <!-- LOGO -->
                <div class="navbar-brand-box horizontal-logo">
                    <a href="{{ route('dashboard') }}" class="logo logo-dark">
                        <span class="logo-sm">
                            <img src="{{ asset('assets/images/logo-sm.png') }}" alt="" height="22">
                        </span>
                        <span class="logo-lg">
                            <img src="{{ asset('assets/images/logo-dark.png') }}" alt="" height="17">
                        </span>
                    </a>

                    <a href="{{ route('dashboard') }}" class="logo logo-light">
                        <span class="logo-sm">
                            <img src="{{ asset('assets/images/logo-sm.png') }}" alt="" height="22">
                        </span>
                        <span class="logo-lg">
                            <img src="{{ asset('assets/images/logo-light.png') }}" alt="" height="17">
                        </span>
                    </a>
                </div>

                <!-- ============================================================== -->
                <!-- TOMBOL BURGER MENU MOBILE (ID: btn-toggle-sidebar) -->
                <!-- ============================================================== -->
                <button type="button" class="btn btn-sm px-3 fs-16 header-item vertical-menu-btn"
                    id="btn-toggle-sidebar">
                    <i class="ri-menu-2-line fs-22"></i>
                </button>
            </div>

            <div class="d-flex align-items-center">
                <!-- ============================================================== -->
                <!-- TOMBOL DARK MODE (ID: btn-toggle-darkmode) -->
                <!-- ============================================================== -->
                <div class="ms-1 header-item">
                    <button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle"
                        id="btn-toggle-darkmode">
                        <i class="ri-moon-line fs-22" id="darkmode-icon"></i>
                    </button>
                </div>

                <!-- User Dropdown -->
                <div class="dropdown ms-sm-3 header-item topbar-user">
                    <button type="button" class="btn material-shadow-none" id="page-header-user-dropdown"
                        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="d-flex align-items-center">
                            <img class="rounded-circle" src="{{ asset('assets/images/users/user-dummy-img.jpg') }}"
                                alt="Header Avatar" width="32" height="32" style="object-fit: cover;">
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <!-- User Info Header -->
                        <div class="p-3 border-bottom border-bottom-dashed">
                            <h6 class="mb-1 fw-semibold">{{ auth()->user()->name }}</h6>
                            <p class="text-muted mb-0 fs-12 text-capitalize">
                                {{ auth()->user()->role === 'admin' ? 'Administrator' : 'Pengguna' }}
                            </p>
                        </div>
                        <a class="dropdown-item mt-2" href="{{ route('profile.index') }}">
                            <i class="ri-user-settings-line text-muted fs-16 align-middle me-1"></i>
                            <span class="align-middle">Profile</span>
                        </a>
                        <a class="dropdown-item" href="{{ route('email-settings.edit') }}">
                            <i class="mdi mdi-cog-outline text-muted fs-16 align-middle me-1"></i>
                            <span class="align-middle">Pengaturan SMTP</span>
                        </a>
                        <div class="dropdown-divider"></div>
                        <form action="{{ route('logout') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="mdi mdi-logout text-danger fs-16 align-middle me-1"></i>
                                <span class="align-middle">Logout</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>