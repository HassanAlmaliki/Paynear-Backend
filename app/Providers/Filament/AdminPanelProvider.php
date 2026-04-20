<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Navigation\NavigationGroup;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\HtmlString;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->authGuard('admin')
            ->brandLogo(asset('images/paynear-logo-white.png'))  
            ->darkModeBrandLogo(asset('images/paynear-logo-blue.png')) 
            ->brandLogoHeight('4rem')
            ->colors([
                'primary' => '#3CC7FD',
                'danger' => Color::Rose,
                'gray' => [
                    50  => '232, 240, 254',  // #E8F0FE
                    100 => '197, 218, 245',  // #C5DAF5
                    200 => '145, 178, 225',  // #91B2E1
                    300 => '93, 138, 200',   // #5D8AC8
                    400 => '55, 100, 165',   // #3764A5
                    500 => '30, 58, 95',     // #1E3A5F
                    600 => '23, 42, 69',     // #172A45
                    700 => '15, 30, 55',     // #0F1E37
                    800 => '12, 27, 50',     // #0C1B32
                    900 => '10, 25, 47',     // #0A192F
                    950 => '7, 17, 33',      // #071121
                ],
                'info' => '#2196F3',
                'success' => Color::Emerald,
                'warning' => Color::Orange,
            ])
            ->darkMode()
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => new HtmlString('
                    <style>
                        /* ─── PayNear Brand: Login Page Gradient ─── */
                        .fi-simple-layout {
                            background: linear-gradient(135deg, #0A192F 0%, #172A45 50%, #0A192F 100%) !important;
                            position: relative;
                        }
                        .fi-simple-layout::before {
                            content: "";
                            position: absolute;
                            top: 0; left: 0; right: 0; bottom: 0;
                            background:
                                radial-gradient(ellipse at 20% 50%, rgba(60, 199, 253, 0.08) 0%, transparent 50%),
                                radial-gradient(ellipse at 80% 20%, rgba(33, 150, 243, 0.06) 0%, transparent 50%);
                            pointer-events: none;
                            z-index: 0;
                        }
                        .fi-simple-layout > * {
                            position: relative;
                            z-index: 1;
                        }
                        /* ─── Login Card Styling ─── */
                        .fi-simple-main-ctn {
                            backdrop-filter: blur(12px);
                        }
                        .fi-simple-main {
                            background: rgba(23, 42, 69, 0.85) !important;
                            border: 1px solid rgba(60, 199, 253, 0.15) !important;
                            border-radius: 20px !important;
                            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3) !important;
                        }
                        /* ─── Sidebar & Topbar Navy ─── */
                        .dark .fi-sidebar {
                            background-color: rgb(10, 25, 47) !important;
                        }
                        .dark .fi-topbar {
                            background-color: rgba(10, 25, 47, 0.95) !important;
                            backdrop-filter: blur(12px);
                        }
                    </style>
                ')
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \Filament\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->navigationGroups([
                NavigationGroup::make('إدارة المستخدمين'),
                NavigationGroup::make('إدارة المالية'),
                NavigationGroup::make('الأجهزة'),
                NavigationGroup::make('طلبات التحقق'),
            ])
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications()
            ->middleware([
                \Illuminate\Cookie\Middleware\EncryptCookies::class,
                \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
                \Illuminate\Session\Middleware\StartSession::class,
                \Illuminate\Session\Middleware\AuthenticateSession::class,
                \Illuminate\View\Middleware\ShareErrorsFromSession::class,
                \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
                \Illuminate\Routing\Middleware\SubstituteBindings::class,
                \Filament\Http\Middleware\DisableBladeIconComponents::class,
                \Filament\Http\Middleware\DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                \Filament\Http\Middleware\Authenticate::class,
            ]);
    }
}
