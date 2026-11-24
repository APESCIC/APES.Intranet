<?php

namespace App\Core;

use App\Core\Access\Console\RemindAccessReview;
use App\Core\Access\Policies\MatrixPolicies;
use App\Core\Access\Policies\RolePolicy;
use App\Core\Access\RoleName;
use App\Core\AdminDashboard\AdminDashboard;
use App\Core\AdminDashboard\AdminDashboardLayouts;
use App\Core\AdminDashboard\AdminSectionCards;
use App\Core\AdminDashboard\CoreAdminCards;
use App\Core\AdminDashboard\DirectorGate;
use App\Core\Analytics\RecordDailyAnalytics;
use App\Core\Appearance\AppearancePreferences;
use App\Core\Audit\Console\PruneAuditLog;
use App\Core\Auth\Cloudron\CloudronIdentityProvider;
use App\Core\Auth\Cloudron\LdapDirectoryGroups;
use App\Core\Auth\Contracts\DirectoryGroups;
use App\Core\Auth\Contracts\IdentityProvider;
use App\Core\Changelog\Console\SyncChangelog;
use App\Core\Changelog\Console\ValidateChangelog;
use App\Core\Changelog\FooterVersion;
use App\Core\Changelog\Release;
use App\Core\Changelog\ReleasePolicy;
use App\Core\Communications\Contracts\PublishIntranetUpdate;
use App\Core\Communications\NullPublishIntranetUpdate;
use App\Core\Helpdesk\Contracts\RaiseItTicket;
use App\Core\Helpdesk\NullRaiseItTicket;
use App\Core\Hr\Contracts\ApprovedLeaveLookup;
use App\Core\Hr\NullApprovedLeaveLookup;
use App\Core\Legal\FooterLinkPresenter;
use App\Core\Legal\FooterLinks;
use App\Core\Legal\Navigation\LegalMenuContributor;
use App\Core\Legal\TermsAcceptance;
use App\Core\Modules\ModuleRegistry;
use App\Core\Navigation\Menu;
use App\Core\Navigation\MenuMode;
use App\Core\Qa\QaLogin;
use App\Core\Search\SearchProviders;
use App\Core\System\Checks\CloudronApiCheck;
use App\Core\System\Checks\LdapCheck;
use App\Core\System\Checks\MysqlCheck;
use App\Core\System\Checks\OidcCheck;
use App\Core\System\Checks\RedisCheck;
use App\Core\System\Checks\SmtpCheck;
use App\Core\System\Console\RunSystemChecks;
use App\Core\System\SystemStatus;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;
use Spatie\Permission\Models\Role;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(IdentityProvider::class, CloudronIdentityProvider::class);
        $this->app->bind(DirectoryGroups::class, LdapDirectoryGroups::class);
        $this->app->bind(ApprovedLeaveLookup::class, NullApprovedLeaveLookup::class);
        $this->app->bind(RaiseItTicket::class, NullRaiseItTicket::class);
        $this->app->bind(PublishIntranetUpdate::class, NullPublishIntranetUpdate::class);
        $this->app->singleton(ModuleRegistry::class);
        $this->app->singleton(SearchProviders::class);
        $this->app->singleton(AdminDashboard::class);
        $this->app->singleton(AdminDashboardLayouts::class);
        $this->app->singleton(DirectorGate::class);
        $this->app->singleton(AdminSectionCards::class);
        $this->app->singleton(CoreAdminCards::class);
        $this->app->tag([AdminSectionCards::class, CoreAdminCards::class], 'intranet.admin-dashboard');
        $this->app->singleton(FooterLinks::class);
        $this->app->singleton(FooterLinkPresenter::class);
        $this->app->singleton(LegalMenuContributor::class);
        $this->app->tag([LegalMenuContributor::class], 'intranet.menu');
        $this->app->bind(SystemStatus::class, fn ($app) => new SystemStatus([
            $app->make(MysqlCheck::class),
            $app->make(RedisCheck::class),
            $app->make(SmtpCheck::class),
            $app->make(LdapCheck::class),
            $app->make(OidcCheck::class),
            $app->make(CloudronApiCheck::class),
        ]));
    }

    public function boot(): void
    {
        $this->commands([
            PruneAuditLog::class,
            RecordDailyAnalytics::class,
            RunSystemChecks::class,
            RemindAccessReview::class,
            ValidateChangelog::class,
            SyncChangelog::class,
        ]);

        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Release::class, ReleasePolicy::class);
        MatrixPolicies::register();

        $shareAppearance = function (ViewInstance $view): void {
            $user = auth()->user();
            $user = $user instanceof User ? $user : null;

            $view->with([
                'appearanceServer' => $user ? AppearancePreferences::forUser($user) : null,
            ]);
        };

        View::composer(['components.layouts.app', 'components.layouts.error'], $shareAppearance);

        View::composer('components.layouts.app', function (ViewInstance $view) {
            $user = auth()->user();
            $user = $user instanceof User ? $user : null;

            $mode = $user ? Menu::resolveMode($user) : MenuMode::Staff;
            $menu = Menu::for($user, $mode);
            $adminSection = $mode === MenuMode::Admin ? Menu::activeGroup($menu) : null;
            $qaProfile = QaLogin::enabled() ? QaLogin::profileFor($user) : null;
            $view->with([
                'menu' => $menu,
                'adminSection' => $adminSection,
                'adminPage' => $adminSection !== null ? Menu::activeItem([$adminSection]) : null,
                'shellPanel' => $user?->hasRole(RoleName::Admin->value) ? $mode : null,
                'shellUser' => $user,
                'qaProfile' => $qaProfile,
                'qaPicker' => $qaProfile !== null ? QaLogin::picker() : null,
                'qaReturnTo' => $qaProfile !== null ? request()->getRequestUri() : null,
                'qaConfirm' => $qaProfile !== null && session(QaLogin::CONFIRM) === true,
                'qaNotice' => $qaProfile !== null ? session('qa.notice') : null,
                'footerVersion' => app(FooterVersion::class)->for($user),
                'unreadNotifications' => $user?->unreadNotifications()->count() ?? 0,
                'recentNotifications' => $user
                    ? $user->notifications()->latest()->limit(8)->get()
                    : collect(),
                'legalUpdatedNotices' => $user ? app(TermsAcceptance::class)->pendingUpdatedNotices($user) : [],
            ]);
        });
    }
}
