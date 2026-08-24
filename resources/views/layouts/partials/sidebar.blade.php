<!-- ============================================================== -->
<!-- Sidebar Navigation -->
<!-- ============================================================== -->
<div class="app-menu navbar-menu">

    <!-- LOGO -->
    <div class="navbar-brand-box">
        <a href="{{ route('dashboard') }}" class="logo logo-dark">
            <span class="logo-sm">
                <img src="{{ asset('assets/images/logo-sm.png') }}" alt="" height="22">
            </span>
            <span class="logo-lg">
                <img src="{{ asset('assets/images/logo-dark.png') }}" alt="" height="90">
            </span>
        </a>

        <a href="{{ route('dashboard') }}" class="logo logo-light">
            <span class="logo-sm">
                <img src="{{ asset('assets/images/logo-sm.png') }}" alt="" height="22">
            </span>
            <span class="logo-lg">
                <img src="{{ asset('assets/images/logo-light.png') }}" alt="" height="90">
            </span>
        </a>

        <button type="button" class="btn btn-sm p-0 fs-20 header-item float-end btn-vertical-sm-hover"
            id="vertical-hover">
            <i class="ri-record-circle-line"></i>
        </button>
    </div>

    <!-- Scrollbar Container -->
    <div id="scrollbar">
        <div class="container-fluid">
            <div id="two-column-menu">
                <ul class="navbar-nav" id="navbar-nav">

                    <!-- Dashboard -->
                    <li class="nav-item {{ request()->routeIs('dashboard*') ? 'active' : '' }}">
                        <a class="nav-link menu-link {{ request()->routeIs('dashboard*') ? 'active' : '' }}"
                            href="{{ route('dashboard') }}">
                            <i class="ri-dashboard-line"></i>
                            <span data-key="t-dashboard">Dashboard</span>
                        </a>
                    </li>

                    <!-- Menu Admin (Hanya untuk admin) -->
                    @if(auth()->user()->isAdmin())
                        <li class="menu-title"><span data-key="t-admin">Administrasi</span></li>

                        <li class="nav-item {{ request()->routeIs('admin.users*') ? 'active' : '' }}">
                            <a class="nav-link menu-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}"
                                href="{{ route('admin.users.index') }}">
                                <i class="ri-user-settings-line"></i>
                                <span data-key="t-users">Manajemen Pengguna</span>
                            </a>
                        </li>
                    @endif

                    <!-- Menu Utama -->
                    <li class="menu-title"><span data-key="t-menu">Data Master Email</span></li>

                    <!-- Pengaturan SMTP -->
                    <li class="nav-item {{ request()->routeIs('email-settings*') ? 'active' : '' }}">
                        <a class="nav-link menu-link {{ request()->routeIs('email-settings*') ? 'active' : '' }}"
                            href="{{ route('email-settings.edit') }}">
                            <i class="ri-mail-settings-line"></i>
                            <span data-key="t-email-settings">Pengaturan SMTP</span>
                        </a>
                    </li>

                    <!-- Template Email -->
                    <li class="nav-item {{ request()->routeIs('email-templates*') ? 'active' : '' }}">
                        <a class="nav-link menu-link {{ request()->routeIs('email-templates*') ? 'active' : '' }}"
                            href="{{ route('email-templates.index') }}">
                            <i class="ri-file-text-line"></i>
                            <span data-key="t-email-templates">Template Email</span>
                        </a>
                    </li>

                    <!-- Dokumen Lampiran -->
                    <li class="nav-item {{ request()->routeIs('attachment-documents*') ? 'active' : '' }}">
                        <a class="nav-link menu-link {{ request()->routeIs('attachment-documents*') ? 'active' : '' }}"
                            href="{{ route('attachment-documents.index') }}">
                            <i class="ri-file-paper-2-line"></i>
                            <span data-key="t-attachment-documents">Dokumen Lampiran</span>
                        </a>
                    </li>

                    <li class="menu-title"><span data-key="t-menu">Data Master Dokumen</span></li>

                    <!-- Template Dokumen -->
                    <li class="nav-item {{ request()->routeIs('document-templates*') ? 'active' : '' }}">
                        <a class="nav-link menu-link {{ request()->routeIs('document-templates*') ? 'active' : '' }}"
                            href="{{ route('document-templates.index') }}">
                            <i class="ri-file-word-2-line"></i>
                            <span data-key="t-document-templates">Template Dokumen</span>
                        </a>
                    </li>

                    <li class="menu-title"><span data-key="t-menu">Kirim Email</span></li>

                    <!-- Kirim Lamaran -->
                    <li class="nav-item {{ request()->routeIs('job-applications.create*') ? 'active' : '' }}">
                        <a class="nav-link menu-link {{ request()->routeIs('job-applications.create*') ? 'active' : '' }}"
                            href="{{ route('job-applications.create') }}">
                            <i class="ri-send-plane-line"></i>
                            <span data-key="t-job-applications">Kirim Lamaran</span>
                        </a>
                    </li>

                    <!-- Riwayat Lamaran -->
                    <li class="nav-item {{ request()->routeIs('job-applications.index*') ? 'active' : '' }}">
                        <a class="nav-link menu-link {{ request()->routeIs('job-applications.index*') ? 'active' : '' }}"
                            href="{{ route('job-applications.index') }}">
                            <i class="ri-history-line"></i>
                            <span data-key="t-job-history">Riwayat Lamaran</span>
                        </a>
                    </li>

                    <li class="menu-title"><span data-key="t-menu">Generator Dokumen</span></li>
                    <!-- Generator Dokumen -->
                    <li class="nav-item {{ request()->routeIs('cover-letter*') ? 'active' : '' }}">
                        <a class="nav-link menu-link {{ request()->routeIs('cover-letter*') ? 'active' : '' }}"
                            href="{{ route('cover-letter.form') }}">
                            <i class="ri-file-list-3-line"></i>
                            <span data-key="t-cover-letter">Generator Dokumen</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="sidebar-background"></div>
</div>