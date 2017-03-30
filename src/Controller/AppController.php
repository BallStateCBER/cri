<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link      http://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use App\Event\ActivityRecordsListener;
use App\Event\SurveysListener;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Event\EventManager;
use Cake\Network\Exception\ForbiddenException;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\Utility\Hash;

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @link http://book.cakephp.org/3.0/en/controllers.html#the-app-controller
 */
class AppController extends Controller
{

    public $helpers = [
        'Form' => [
            'templates' => 'bootstrap_form'
        ]
    ];

    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading components.
     *
     * @return void
     */
    public function initialize()
    {
        parent::initialize();
        $this->loadComponent('DataCenter.Flash');

        $this->loadComponent('Security', [
            'blackHoleCallback' => 'forceSSL'
        ]);

        $this->loadComponent('Cookie', [
            'encryption' => 'aes',
            'key' => Configure::read('cookie_key')
        ]);

        $this->loadComponent('Auth', [
            'loginAction' => [
                'prefix' => false,
                'controller' => 'Users',
                'action' => 'login'
            ],
            'logoutRedirect' => [
                'prefix' => false,
                'controller' => 'Pages',
                'action' => 'home'
            ],
            'authenticate' => [
                'Form' => [
                    'fields' => ['username' => 'email'],
                    'passwordHasher' => [
                        'className' => 'Fallback',
                        'hashers' => ['Default', 'Legacy']
                    ]
                ],
                'Xety/Cake3CookieAuth.Cookie'
            ],
            'authorize' => ['Controller']
        ]);
        $this->Auth->deny();
        $errorMessage = $this->Auth->user() ?
            'Sorry, you are not authorized to access that page.'
            : 'Please log in before accessing that page.';
        $this->Auth->config('authError', $errorMessage);

        // Prevents cookies from being accessible in Javascript
        $this->Cookie->httpOnly = true;

        // Set up listeners
        $activityRecordsListener = new ActivityRecordsListener();
        $activityRecordsListener->userId($this->Auth->user('id'));
        EventManager::instance()->on($activityRecordsListener);
        $surveysListener = new SurveysListener();
        EventManager::instance()->on($surveysListener);
    }

    /**
     * beforeFilter method
     *
     * @param \Cake\Event\Event $event Event
     * @return \Cake\Http\Response|null
     */
    public function beforeFilter(\Cake\Event\Event $event)
    {
        $this->Security->requireSecure();

        // Set accessible communities
        $usersTable = TableRegistry::get('Users');
        $this->set([
            'accessibleCommunities' => $usersTable->getAccessibleCommunities($this->Auth->user('id'))
        ]);

        // Automatically log in
        if (! $this->Auth->user() && $this->Cookie->read('CookieAuth')) {
            $rememberData = $this->request->data;
            $this->request->data = $this->Cookie->read('CookieAuth');
            $user = $this->Auth->identify();
            if ($user) {
                $this->Auth->setUser($user);
            } else {
                $this->Cookie->delete('CookieAuth');
            }
            if ($rememberData) {
                $this->request->data = $rememberData;
            }
        }

        if (Configure::read('maintenance_mode')) {
            $allowedActions = [
                'maintenance',
                'home',
                'faqCommunity',
                'enroll',
                'credits',
                'glossary',
                'sendTestEmail'
            ];
            if (! in_array($this->request->action, $allowedActions)) {
                return $this->redirect([
                    'prefix' => false,
                    'controller' => 'Pages',
                    'action' => 'maintenance'
                ]);
            }
        }
    }

    /**
     * beforeRender method
     *
     * @param \Cake\Event\Event $event Event
     * @return void
     */
    public function beforeRender(\Cake\Event\Event $event)
    {
        $this->setLayoutVariables();
        if ($this->Auth->user('role') == 'admin') {
            $this->prepareAdminHeader();
        }
    }

    /**
     * isAuthorized method
     *
     * @param array $user User
     * @return bool
     */
    public function isAuthorized($user)
    {
        if (! isset($user['role'])) {
            return false;
        }

        // Admin can access every action
        if ($user['role'] === 'admin') {
            return true;
        }

        // Clients and consultants can access the respective role-prefixed actions
        $prefix = isset($this->request->params['prefix']) ? $this->request->params['prefix'] : null;

        return $prefix === $user['role'];
    }

    /**
     * Redirects to SSL version of page
     *
     * @return \Cake\Http\Response|null
     */
    public function forceSSL()
    {
        return $this->redirect('https://' . env('SERVER_NAME') . $this->request->here());
    }

    /**
     * Redirects (returns a redirect Response object) to the page used by admins for choosing a client to impersonate
     *
     * @return \Cake\Http\Response
     * @throws ForbiddenException
     */
    protected function chooseClientToImpersonate()
    {
        if ($this->Auth->user('role') != 'admin') {
            throw new ForbiddenException('Error: Client ID not found for ' . $this->Auth->user('role') . ' account');
        }

        return $this->redirect([
            'prefix' => 'admin',
            'controller' => 'Users',
            'action' => 'chooseClient',
            'redirect' => urlencode(Router::url([]))
        ]);
    }

    /**
     * Return the logged-in client's ID, the previously-remembered client ID,
     * or null if neither is possible.
     * @return int|null
     */
    protected function getClientId()
    {
        if ($this->Auth->user('role') == 'client') {
            return $this->Auth->user('id');
        }

        // Admins can set the ID of the client they're impersonating
        $clientId = $this->Cookie->read('clientId');
        if ($clientId) {
            return $clientId;
        }

        return null;
    }

    /**
     * Accepts an array of stringy variables and returns a comma-delimited list with an optional conjunction before the last element
     *
     * @param array $array Array to turn into a string
     * @param string $conjunction Conjunction, defaults to 'and'
     * @return string
     */
    protected function arrayToList($array, $conjunction = 'and')
    {
        $count = count($array);
        if ($count === 0) {
            return '';
        }

        if ($count === 1) {
            return $array[0];
        }

        if ($conjunction) {
            $lastElement = array_pop($array);
            array_push($array, $conjunction . ' ' . $lastElement);
        }

        if ($count == 2) {
            return implode(' ', $array);
        }

        return implode(', ', $array);
    }

    /**
     * Uses cookie to remember current sorting and apply remembered sorting when none is currently specified
     *
     * @param string $cookieParentKey Cookie parent key
     * @return void
     */
    public function cookieSort($cookieParentKey)
    {
        // Remember selected sort, but only remember direction if sort is specified
        $param = 'sort';
        if ($this->request->query($param)) {
            $value = $this->request->query($param);
            $key = "$cookieParentKey.$param";
            $this->Cookie->write($key, $value);

            $param = 'direction';
            if ($this->request->query($param)) {
                $value = $this->request->query($param);
                $key = "$cookieParentKey.$param";
                $this->Cookie->write($key, $value);

            // Forget direction
            } elseif ($this->Cookie->check($key)) {
                $this->Cookie->delete($key);
            }

        // If no sort specified, apply remembered sort
        } else {
            $param = 'sort';
            $key = "$cookieParentKey.$param";
            if ($this->Cookie->check($key)) {
                $this->request->query[$param] = $this->Cookie->read($key);

                // And direction, if remembered
                $param = 'direction';
                $key = "$cookieParentKey.$param";
                if ($this->Cookie->check($key)) {
                    $this->request->query[$param] = $this->Cookie->read($key);
                }
            }
        }
    }

    /**
     * Sets variables used in the default layout
     *
     * @return void
     */
    public function setLayoutVariables()
    {
        // Set up variables for sidebar
        if ($this->viewBuilder()->layout() == 'default' && $this->Auth->user('role') == 'admin') {
            $communitiesTable = TableRegistry::get('Communities');
            $this->set([
                'sidebar' => [
                    'communities' => $communitiesTable->getClientCommunityList(),
                    'communityId' => $this->Cookie->read('communityId'),
                    'clientId' => $this->Cookie->read('clientId')
                ]
            ]);
        }

        $this->set([
            'authUser' => $this->Auth->user(),
            'flashMessages' => $this->Flash->messages
        ]);
        $this->request->session()->delete('FlashMessage');
    }

    /**
     * Sets the $adminHeader variable
     *
     * @return void
     */
    public function prepareAdminHeader()
    {
        $this->loadModel('Communities');
        $communities = $this->Communities->find('list')
            ->order(['name' => 'ASC']);

        $route = ['prefix' => 'admin', 'controller' => 'Communities'];
        $communityPages = [
            'Edit' => Router::url($route + ['action' => 'edit']) . '/{community-id}',
            'Progress' => Router::url($route + ['action' => 'progress']) . '/{community-id}',
            'Clients' => Router::url($route + ['action' => 'clients']) . '/{community-id}',
            'Client Home' => Router::url($route + ['action' => 'clienthome']) . '/{community-id}',
            'Presentations' => Router::url($route + ['action' => 'presentations']) . '/{community-id}',
            'Notes' => Router::url($route + ['action' => 'notes']) . '/{community-id}',
            'Purchases' => Router::url([
                'prefix' => 'admin',
                'controller' => 'Purchases',
                'action' => 'view'
            ]) . '/{community-id}',
            'Activity' => Router::url([
                'prefix' => 'admin',
                'controller' => 'ActivityRecords',
                'action' => 'community'
            ]) . '/{community-id}'
        ];

        $surveyPages = [
            'Overview' => Router::url(['prefix' => 'admin', 'controller' => 'Surveys', 'action' => 'view']) . '/{community-id}/{survey-type}',
            'Link' => Router::url(['prefix' => 'admin', 'controller' => 'Surveys', 'action' => 'link']) . '/{community-id}/{survey-type}',
            'Activate' => Router::url(['prefix' => 'admin', 'controller' => 'Surveys', 'action' => 'activate']) . '/{survey-id}',
            'Invitations' => Router::url(['prefix' => 'admin', 'controller' => 'Surveys', 'action' => 'invite']) . '/{survey-id}',
            'Reminders' => Router::url(['prefix' => 'admin', 'controller' => 'Surveys', 'action' => 'remind']) . '/{survey-id}',
            'Respondents' => Router::url(['prefix' => 'admin', 'controller' => 'Respondents', 'action' => 'view']) . '/{survey-id}',
            'Alignment' => Router::url(['prefix' => 'admin', 'controller' => 'Responses', 'action' => 'view']) . '/{survey-id}'
        ];

        $this->loadModel('Surveys');
        $results = $this->Surveys->find('all')
            ->select(['id', 'type', 'community_id'])
            ->toArray();
        $surveyIds = Hash::combine($results, '{n}.type', '{n}.id', '{n}.community_id');

        $this->set([
            'adminHeader' => [
                'communities' => $communities,
                'communityPages' => $communityPages,
                'currentUrl' => '/' . $this->request->url,
                'surveyIds' => $surveyIds,
                'surveyPages' => $surveyPages
            ]
        ]);
    }
}
