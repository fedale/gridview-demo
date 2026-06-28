<?php

namespace App\EventListener;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Locale handling for the gridview pages, which (unlike the EasyAdmin routes)
 * have no {_locale} in their URL. The shell's language switcher links to
 * ?_locale=xx; this listener honours that, stores the choice in the session and
 * restores it on later gridview requests — so the locale is handled "differently"
 * from the route-based admin, without adding a route parameter.
 *
 * Runs after Symfony's router/locale listener so route-defined locales (the
 * admin pages) keep winning.
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 12)]
final class GridviewLocaleListener
{
    private const SESSION_KEY = '_gridview_locale';

    /**
     * @param list<string> $enabledLocales
     */
    public function __construct(
        #[Autowire('%kernel.enabled_locales%')] private readonly array $enabledLocales,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Only manage locale for the gridview pages.
        if (!str_starts_with($request->getPathInfo(), '/gridview')) {
            return;
        }

        $queryLocale = $request->query->get('_locale');
        if (\is_string($queryLocale) && \in_array($queryLocale, $this->enabledLocales, true)) {
            if ($request->hasSession()) {
                $request->getSession()->set(self::SESSION_KEY, $queryLocale);
            }
            $request->setLocale($queryLocale);

            return;
        }

        if ($request->hasSession()) {
            $stored = $request->getSession()->get(self::SESSION_KEY);
            if (\is_string($stored) && \in_array($stored, $this->enabledLocales, true)) {
                $request->setLocale($stored);
            }
        }
    }
}
