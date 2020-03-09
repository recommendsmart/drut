<?php

namespace Drupal\contacts\EventSubscriber;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Redirects users when access is denied.
 *
 * Anonymous users are taken to the login page when attempting to access the
 * user profile pages. Authenticated users are redirected from the login form to
 * their profile page and from the user registration form to their profile edit
 * form.
 */
class AccessDeniedSubscriber implements EventSubscriberInterface {

  use UrlGeneratorTrait;
  use RedirectDestinationTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Constructs a new redirect subscriber.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The URL generator.
   */
  public function __construct(AccountInterface $account, UrlGeneratorInterface $url_generator) {
    $this->account = $account;
    $this->setUrlGenerator($url_generator);
  }

  /**
   * Redirects users when access is denied.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function onException(GetResponseForExceptionEvent $event) {
    $exception = $event->getException();
    if ($exception instanceof AccessDeniedHttpException) {
      // @todo: Switch over to using the dashboard helper.
      $route = RouteMatch::createFromRequest($event->getRequest());
      if ($route->getRouteName() === 'contacts.ajax_subpage') {
        $response = new AjaxResponse();
        $url = Url::fromRoute('contacts.contact', [
          'user' => $route->getParameter('user')->id(),
          'subpage' => $route->getParameter('subpage'),
        ]);
        $response->addCommand(new RedirectCommand($url->toString()));
        $response->headers->set('X-Status-Code', 200);
        $event->setResponse($response);
      }
    }
  }

  /**
   * Redirects users to login page when access denied.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function redirectToLoginPage(GetResponseForExceptionEvent $event) {
    $exception = $event->getException();
    if ($exception instanceof AccessDeniedHttpException) {
      if (!$this->account->isAuthenticated() && !$event->getRequest()->isXmlHttpRequest() && in_array('text/html', $event->getRequest()->getAcceptableContentTypes())) {
        $event->setResponse($this->redirect('user.login', [], ['query' => $this->getRedirectDestination()->getAsArray()]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::EXCEPTION][] = ['onException'];

    // Last to make sure other subscribers can handle first.
    $events[KernelEvents::EXCEPTION][] = ['redirectToLoginPage', -20];
    return $events;
  }

}
