<?php

namespace App\Twig;

use App\Enum\CommentStatus;
use App\Enum\PostStatus;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use App\Repository\SubscriberRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Builds the gridview sidebar menu as a faithful replica of EasyAdmin's
 * {@see \App\Controller\Admin\DashboardController::configureMenuItems()} —
 * same sections, icons and badges — but with the entity links pointing to
 * /gridview/<entity> (no {_locale}; locale is handled separately here).
 *
 * Entity items auto-enable as soon as their gridview controller exists (the
 * `gridview_<slug>_index` route is registered); until then they render disabled.
 */
class GridviewMenuExtension extends AbstractExtension
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly RequestStack $requestStack,
        private readonly PostRepository $postRepository,
        private readonly CommentRepository $commentRepository,
        private readonly SubscriberRepository $subscriberRepository,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('gridview_menu', $this->buildMenu(...)),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function buildMenu(): array
    {
        $path = $this->requestStack->getCurrentRequest()?->getPathInfo() ?? '';
        $locale = $this->requestStack->getCurrentRequest()?->getLocale() ?? 'en';

        $draft = $this->safeCount(fn () => $this->postRepository->count(['status' => PostStatus::Draft]));
        $pending = $this->safeCount(fn () => $this->commentRepository->count(['status' => CommentStatus::Pending]));
        $unconfirmed = $this->safeCount(fn () => $this->subscriberRepository->count(['isConfirmed' => false]));

        return [
            $this->dashboard($path),

            $this->section('Content'),
            $this->entity('post', 'Blog Posts', 'fa fa-file-text-o', $path, $draft, 'text-bg-secondary'),
            $this->entity('category', 'Categories', 'fa fa-folder', $path),
            $this->entity('tag', 'Tags', 'fas fa-tags', $path),
            $this->entity('series', 'Series', 'fa fa-list-ol', $path),

            $this->section('Community'),
            $this->entity('comment', 'Comments', 'far fa-comments', $path, $pending, 'text-bg-danger'),
            $this->entity('subscriber', 'Subscribers', 'fa fa-envelope', $path, $unconfirmed, 'text-bg-info'),

            $this->section('Administration'),
            $this->entity('user', 'Users', 'fa fa-users', $path),

            $this->section('Resources'),
            $this->entity('form-field-reference', 'Form Field Reference', 'fa-solid fa-table-cells', $path),
            $this->route('Fixtures data', 'fa-solid fa-database', 'admin_regenerate_fixtures', $locale),

            $this->section('Links'),
            $this->url('Documentation', 'fas fa-book', 'https://symfony.com/doc/current/bundles/EasyAdminBundle/index.html'),
            $this->url('Demo', 'fas fa-magic', 'https://github.com/EasyCorp/easyadmin-demo'),
            $this->url('Sponsor', 'fa fa-euro-sign', 'https://github.com/sponsors/javiereguiluz'),
        ];
    }

    private function dashboard(string $path): array
    {
        $url = $this->router->generate('gridview_dashboard');

        return [
            'type' => 'link',
            'label' => 'Dashboard',
            'icon' => 'fa fa-home',
            'url' => $url,
            'enabled' => true,
            'active' => $path === $url,
            'target' => null,
            'badge' => null,
        ];
    }

    private function section(string $label): array
    {
        return ['type' => 'section', 'label' => $label];
    }

    /**
     * An entity page. Links to /gridview/<slug>; enabled only when that
     * controller's index route exists.
     */
    private function entity(string $slug, string $label, string $icon, string $path, int $badge = 0, string $badgeClass = 'text-bg-secondary'): array
    {
        $url = '/gridview/' . $slug;

        return [
            'type' => 'link',
            'label' => $label,
            'icon' => $icon,
            'url' => $url,
            'enabled' => $this->router->getRouteCollection()->get('gridview_' . str_replace('-', '_', $slug) . '_index') !== null,
            'active' => $path === $url || str_starts_with($path, $url . '/'),
            'target' => null,
            'badge' => $badge > 0 ? ['text' => (string) $badge, 'class' => $badgeClass] : null,
        ];
    }

    private function route(string $label, string $icon, string $route, string $locale): array
    {
        return [
            'type' => 'link',
            'label' => $label,
            'icon' => $icon,
            'url' => $this->router->generate($route, ['_locale' => $locale]),
            'enabled' => true,
            'active' => false,
            'target' => null,
            'badge' => null,
        ];
    }

    private function url(string $label, string $icon, string $url): array
    {
        return [
            'type' => 'link',
            'label' => $label,
            'icon' => $icon,
            'url' => $url,
            'enabled' => true,
            'active' => false,
            'target' => '_blank',
            'badge' => null,
        ];
    }

    private function safeCount(callable $fn): int
    {
        try {
            return (int) $fn();
        } catch (\Throwable) {
            return 0;
        }
    }
}
