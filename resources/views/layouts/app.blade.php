<!DOCTYPE html>
<html lang="id" data-layout="vertical" data-layout-style="default" data-layout-position="fixed" data-topbar="dark"
    data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="none" data-preloader="disable"
    data-layout-mode="light" data-bs-theme="light" data-body-image="none" data-theme="default"
    data-theme-colors="default">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Job Application Automator & Document Generator" />
    <meta name="mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="default" />

    <title>@yield('title', 'Auto Lamaran') | Auto Lamaran</title>

    <!-- ============================================================== -->
    <!-- TEMA INITIALIZATION - SEBELUM SEMUA CSS UNTUK MENGHINDARI FOUC -->
    <!-- ============================================================== -->
    <script>
        (function () {
            // Baca preferensi tema dari localStorage
            var savedTheme = localStorage.getItem('autolamaran-theme') || 'light';
            var isDark = savedTheme === 'dark';
            var themeMode = isDark ? 'dark' : 'light';

            // Set atribut HTML untuk CSS Velzone
            document.documentElement.setAttribute('data-layout-mode', themeMode);
            document.documentElement.setAttribute('data-bs-theme', themeMode);
            document.documentElement.setAttribute('data-topbar', 'dark');
            document.documentElement.setAttribute('data-sidebar', 'dark');

            // Set sidebar size
            var savedSidebarSize = localStorage.getItem('velzone-sidebar-size') || 'lg';
            document.documentElement.setAttribute('data-sidebar-size', savedSidebarSize);

            // Ambil SEMUA atribut <html> SAAT INI untuk sessionStorage
            var currentAttrs = {};
            var htmlAttrs = document.documentElement.attributes;
            for (var i = 0; i < htmlAttrs.length; i++) {
                var attr = htmlAttrs[i];
                currentAttrs[attr.name] = attr.value;
            }

            // Kunci yang disimpan oleh app.js
            var keysToSave = [
                'data-layout', 'data-layout-style', 'data-layout-position',
                'data-layout-width', 'data-sidebar', 'data-sidebar-size',
                'data-sidebar-image', 'data-topbar', 'data-preloader',
                'data-body-image', 'data-theme', 'data-theme-colors',
                'data-bs-theme', 'data-layout-mode', 'data-sidebar-visibility'
            ];

            // Simpan setiap kunci ke sessionStorage
            for (var j = 0; j < keysToSave.length; j++) {
                var key = keysToSave[j];
                var value = currentAttrs[key];
                if (value !== undefined) {
                    sessionStorage.setItem(key, value);
                }
            }

            // Set defaultAttribute - MENCEGAH app.js masuk branch "first visit"
            // Yang menyebabkan: membuka theme settings panel & auto-reload
            sessionStorage.setItem('defaultAttribute', JSON.stringify(currentAttrs));

            // Sinkronkan tema ke sessionStorage
            sessionStorage.setItem('data-layout-mode', themeMode);
            sessionStorage.setItem('data-bs-theme', themeMode);
        })();
    </script>

    <!-- App Favicon -->
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.ico') }}">

    <!-- Velzone Bootstrap & Layout CSS -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/app.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/custom.min.css') }}" rel="stylesheet" type="text/css" />

    <!-- DataTables Bootstrap 5 Styling CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">

    @stack('styles')
</head>

<body>
    <!-- Begin page -->
    <div id="layout-wrapper">

        <!-- ============================================================== -->
        <!-- Topbar -->
        <!-- ============================================================== -->
        @include('layouts.partials.topbar')

        <!-- ============================================================== -->
        <!-- Sidebar -->
        <!-- ============================================================== -->
        @include('layouts.partials.sidebar')

        <!-- Vertical Overlay untuk backdrop mobile -->
        <div class="vertical-overlay" id="mobile-overlay"></div>

        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    @yield('content')
                </div>
            </div>

            <!-- Footer -->
            <footer class="footer">
                <div class="container">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="text-center">
                                <p class="mb-0 text-muted">
                                    &copy;
                                    <script>document.write(new Date().getFullYear())</script>
                                    <strong>ApplyGo</strong> - Job Application Automator
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    <!-- END layout-wrapper -->

    <!-- ============================================================== -->
    <!-- JAVASCRIPT WAJIB - Urutan harus benar! -->
    <!-- ============================================================== -->
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap Bundle -->
    <script src="{{ asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <!-- SimpleBar (Sidebar scroll) -->
    <script src="{{ asset('assets/libs/simplebar/simplebar.min.js') }}"></script>
    <!-- Waves Effect -->
    <script src="{{ asset('assets/libs/node-waves/waves.min.js') }}"></script>
    <!-- Feather Icons -->
    <script src="{{ asset('assets/libs/feather-icons/feather.min.js') }}"></script>
    <!-- Lord Icon -->
    <script src="{{ asset('assets/js/pages/plugins/lord-icon-2.1.0.js') }}"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

    <!-- ============================================================== -->
    <!-- CUSTOM HANDLER - Event listeners & non-theme initialization -->
    <!-- ============================================================== -->
    <script>
            (function () {
                'use strict';

                // ============================================================== -->
                // 1. TOGGLE DARK/LIGHT MODE - SATU-SATUNYA SUMBER KEBENARAN -->
                // ==============================================================
                function applyTheme(isDark) {
                    var iconEl = document.getElementById('darkmode-icon');
                    var theme = isDark ? 'dark' : 'light';

                    // Set SEMUA atribut tema SECARA SERENTAK
                    document.documentElement.setAttribute('data-layout-mode', theme);
                    document.documentElement.setAttribute('data-bs-theme', theme);
                    document.documentElement.setAttribute('data-topbar', 'dark');
                    document.documentElement.setAttribute('data-sidebar', 'dark');

                    // Simpan ke localStorage (persistent) dan sessionStorage (app.js kompatibilitas)
                    localStorage.setItem('autolamaran-theme', theme);
                    sessionStorage.setItem('data-layout-mode', theme);
                    sessionStorage.setItem('data-bs-theme', theme);

                    // Update ikon tombol toggle
                    if (iconEl) {
                        if (isDark) {
                            iconEl.className = 'ri-sun-line fs-22';
                        } else {
                            iconEl.className = 'ri-moon-line fs-22';
                        }
                    }
                }

                // Inisialisasi icon berdasarkan tema saat ini (dari localStorage)
                var savedTheme = localStorage.getItem('autolamaran-theme') || 'light';
                var iconEl = document.getElementById('darkmode-icon');
                if (iconEl) {
                    if (savedTheme === 'dark') {
                        iconEl.className = 'ri-sun-line fs-22';
                    } else {
                        iconEl.className = 'ri-moon-line fs-22';
                    }
                }

                // Event listener untuk tombol toggle tema
                var darkmodeBtn = document.getElementById('btn-toggle-darkmode');
                if (darkmodeBtn) {
                    darkmodeBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        var currentMode = document.documentElement.getAttribute('data-layout-mode') || 'light';
                        applyTheme(currentMode !== 'dark');
                    });
                }

                // ============================================================== -->
                // 2. TOGGLE BURGER MENU MOBILE & DESKTOP (JQUERY) -->
                // ==============================================================
                $(document).on('click', '#topnav-hamburger-icon, #btn-toggle-sidebar', function (e) {
                    e.preventDefault();
                    const windowWidth = $(window).width();

                    if (windowWidth <= 767.98) {
                        // --- LAYAR MOBILE ---
                        $('body').toggleClass('vertical-sidebar-enable');
                        $('.hamburger-icon').toggleClass('open');
                    } else {
                        // --- LAYAR DESKTOP ---
                        const htmlTag = document.documentElement;
                        const currentSidebarSize = htmlTag.getAttribute('data-sidebar-size');

                        if (currentSidebarSize === 'sm') {
                            htmlTag.setAttribute('data-sidebar-size', 'lg');
                            localStorage.setItem('velzone-sidebar-size', 'lg');
                        } else {
                            htmlTag.setAttribute('data-sidebar-size', 'sm');
                            localStorage.setItem('velzone-sidebar-size', 'sm');
                        }
                    }
                });

                // Klik backdrop overlay di mobile untuk menutup menu
                $(document).on('click', '.vertical-overlay, #mobile-overlay', function () {
                    $('body').removeClass('vertical-sidebar-enable');
                    $('.hamburger-icon').removeClass('open');
                });

                // ============================================================== -->
                // 4. INITIALIZE FEATHER ICONS -->
                // ==============================================================
                if (typeof feather !== 'undefined') {
                    feather.replace();
                }
                window.addEventListener('load', function () {
                    if (typeof feather !== 'undefined') {
                        feather.replace();
                    }
                });

                // ============================================================== -->
                // 5. INITIALIZE WAVES EFFECT -->
                // ==============================================================
                if (typeof Waves !== 'undefined') {
                    Waves.init();
                }

                // ============================================================== -->
                // 6. ACTIVE MENU HIGHLIGHTING -->
                // ==============================================================
                var currentPath = window.location.pathname;
                var navLinks = document.querySelectorAll('#navbar-nav a');
                navLinks.forEach(function (link) {
                    var href = link.getAttribute('href');
                    if (href === currentPath) {
                        link.classList.add('active');
                    }
                });

                // ============================================================== -->
                // 7. SIMPLEBAR INITIALIZATION -->
                // ==============================================================
                var scrollbar = document.getElementById('scrollbar');
                if (scrollbar && typeof SimpleBar !== 'undefined') {
                    try {
                        new SimpleBar(scrollbar);
                    } catch (e) { }
                }

            })();
    </script>

    <!-- App JS Velzone -->
    <script src="{{ asset('assets/js/app.js') }}"></script>

    <!-- Custom Init (minimal, tanpa bentrok) -->
    <script src="{{ asset('assets/js/custom-init.js') }}"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Script DataTables per Halaman -->
    @stack('scripts')

    <!-- ============================================================== -->
    <!-- FLASH MESSAGE & GLOBAL EVENT HANDLER (SWEETALERT2) -->
    <!-- ============================================================== -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // 1. Notifikasi Flash Message Sukses
            @if(session('success'))
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: "{{ session('success') }}",
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true
                });
            @endif

            // 2. Notifikasi Flash Message Error
            @if(session('error'))
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: "{{ session('error') }}",
                    confirmButtonText: 'Tutup',
                    customClass: {
                        confirmButton: 'btn btn-primary w-xs mt-2'
                    },
                    buttonsStyling: false
                });
            @endif

            // 3. Notifikasi Error Validasi Form
            @if($errors->any())
                Swal.fire({
                    icon: 'warning',
                    title: 'Periksa Kembali Form!',
                    html: '<ul class="text-start" style="list-style-type: none; padding-left: 0;">' +
                        '@foreach($errors->all() as $error)' +
                            '<li><i class="ri-error-warning-line text-danger me-1"></i> {{ $error }}</li>' +
                        '@endforeach' +
                        '</ul>',
                    confirmButtonText: 'Mengerti',
                    customClass: {
                        confirmButton: 'btn btn-primary w-xs mt-2'
                    },
                    buttonsStyling: false
                });
            @endif

            // 4. Global Handler untuk Konfirmasi Hapus Data
            $(document).on('click', '.btn-delete', function (e) {
                e.preventDefault();
                const form = $(this).closest('form');
                const itemName = $(this).data('name') || 'data ini';

                Swal.fire({
                    title: 'Apakah Anda Yakin?',
                    text: `Data "${itemName}" yang dihapus tidak dapat dikembalikan!`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal',
                    customClass: {
                        confirmButton: 'btn btn-danger w-xs me-2',
                        cancelButton: 'btn btn-light w-xs'
                    },
                    buttonsStyling: false,
                    showCloseButton: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    </script>
</body>

</html>