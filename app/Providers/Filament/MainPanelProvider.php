<?php

namespace App\Providers\Filament;

use App\Filament\Resources\PersonalAccessTokenResource;
use App\Filament\Widgets\ActiveAnomalies;
use App\Filament\Widgets\AnomaliesPerMonitor;
use App\Filament\Widgets\RecentTestRuns;
use App\Filament\Widgets\ResponseTime;
use App\Filament\Widgets\StatusWidget;
use App\Models\SocialiteUser;
use DutchCodingCompany\FilamentSocialite\FilamentSocialitePlugin;
use DutchCodingCompany\FilamentSocialite\Provider as SocialiteProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class MainPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('main')
            ->path('')
            ->brandLogo(fn () => asset('logo.svg'))
            ->brandLogoHeight('2rem')
            ->favicon(fn () => asset('favicon.png'))
            ->login()
            ->colors([
                'primary' => Color::Red,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
            ])
            ->navigationGroups([
                NavigationGroup::make('Monitoring')
                    ->icon('heroicon-o-heart'),
                NavigationGroup::make('Status Pages')
                    ->icon('heroicon-o-eye'),
            ])
            ->darkMode(false)
            ->registration()
            ->profile()
            ->passwordReset()
            ->emailVerification()
            ->widgets([
                StatusWidget::class,
                AccountWidget::class,
                ResponseTime::class,
                AnomaliesPerMonitor::class,
                ActiveAnomalies::class,
                RecentTestRuns::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->topbar()
            ->breadcrumbs(false)
            ->font('Manrope')
            ->authMiddleware([
                Authenticate::class,
            ])
            ->renderHook(
                PanelsRenderHook::CONTENT_START,
                fn () => view('blob')
            )
            ->renderHook(
                PanelsRenderHook::SIMPLE_PAGE_START,
                fn () => view('auth-banner'),
                scopes: [\Filament\Pages\Auth\Login::class, \Filament\Pages\Auth\Register::class, \Filament\Pages\Auth\EmailVerification\EmailVerificationPrompt::class, \Filament\Pages\Auth\PasswordReset\RequestPasswordReset::class]
            )
            ->renderHook(
                PanelsRenderHook::FOOTER,
                fn () => view('footer')
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_NAV_END,
                fn () => view('sidebar-user')
            )
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn () => view('head-start')
            )
            ->viteTheme('resources/css/filament/main/theme.css')
            ->userMenuItems([
                MenuItem::make()
                    ->label('Connections')
                    ->url(fn (): string => PersonalAccessTokenResource::getUrl())
                    ->icon('heroicon-o-device-phone-mobile'),
            ])->plugin(
                FilamentSocialitePlugin::make()
                    ->providers([
                        SocialiteProvider::make('github')
                            ->label('GitHub')
                            ->icon('fab-github')
                            ->color(Color::hex('#24292e'))
                            ->outlined(true)
                            ->stateless(false),
                        SocialiteProvider::make('gitlab')
                            ->label('GitLab')
                            ->icon('fab-gitlab')
                            ->color(Color::hex('#FCA326'))
                            ->outlined(true)
                            ->stateless(false),

                    ])
                    ->registration(true)
                    ->showDivider(false)
                    ->socialiteUserModelClass(SocialiteUser::class)
            );
    }
}
