<?php
namespace App\Controller\Admin;

use App\Controller\AppController;

class CommunitiesController extends AppController
{
    /**
     * Alters $this->paginate settings according to $_GET and Cookie data,
     * and remembers $_GET data with a cookie.
     */
    private function adminIndexFilter()
    {
        $cookieParentKey = 'AdminCommunityIndex';

        // Remember selected filters
        $this->filters = $this->request->query('filters');
        foreach ($this->filters as $group => $filter) {
            $this->Cookie->write("$cookieParentKey.filters.$group", $filter);
        }

        // Use remembered filters when no filters manually specified
        foreach (['progress', 'track'] as $group) {
            if (! isset($this->filters[$group])) {
                $key = "$cookieParentKey.filters.$group";
                if ($this->Cookie->check($key)) {
                    $this->filters[$group] = $this->Cookie->read($key);
                }
            }
        }

        // Default filters if completely unspecified
        if (! isset($this->filters['progress'])) {
            $this->filters['progress'] = 'ongoing';
        }

        // Apply filters
        foreach ($this->filters as $filter) {
            switch ($filter) {
                case 'ongoing':
                    $this->paginate['conditions']['Community.score <'] = '5';
                    break;
                case 'completed':
                    $this->paginate['conditions']['Community.score'] = '5';
                    break;
                case 'fast_track':
                    $this->paginate['conditions']['Community.fast_track'] = true;
                    break;
                case 'normal_track':
                    $this->paginate['conditions']['Community.fast_track'] = false;
                    break;
                case 'all':
                default:
                    // No action
                    break;
            }
        }
    }

    private function adminIndexSetupPagination()
    {
        $this->paginate['contain'] = [
            'Client' => [
                'fields' => [
                    'Client.email',
                    'Client.name'
                ]
            ],
            'OfficialSurvey' => [
                'fields' => [
                    'OfficialSurvey.id',
                    'OfficialSurvey.sm_id',
                    'OfficialSurvey.alignment',
                    'OfficialSurvey.alignment_passed',
                    'OfficialSurvey.respondents_last_modified_date'
                ]
            ],
            'OrganizationSurvey' => [
                'fields' => [
                    'OrganizationSurvey.id',
                    'OrganizationSurvey.sm_id',
                    'OrganizationSurvey.alignment',
                    'OrganizationSurvey.alignment_passed',
                    'OrganizationSurvey.respondents_last_modified_date'
                ]
            ],
            'Area' => [
                'fields' => [
                    'Area.name'
                ]
            ]
        ];
        $this->paginate['group'] = 'Community.id';
        $this->paginate['fields'] = [
            'Community.id',
            'Community.name',
            'Community.fast_track',
            'Community.score',
            'Community.created'
        ];
    }

    private function adminIndexSetupFilterButtons()
    {
        $allFilters = [
            'progress' => [
                'all' => 'All',
                'completed' => 'Completed',
                'ongoing' => 'Ongoing'
            ],
            'track' => [
                'all' => 'All',
                'fast_track' => 'Fast Track',
                'normal_track' => 'Normal Track'
            ]
        ];
        foreach ($this->filters as $group => $filter) {
            if ($filter == 'all') {
                unset($this->filters[$group]);
            }
        }
        $buttons = [];
        foreach ($allFilters as $group => $filters) {
            $groupLabel = ucwords($group);
            $selectedFilterKey = isset($this->filters[$group]) ?
                $this->filters[$group]
                : null;
            if ($selectedFilterKey != 'all') {
                $selectedFilterLabel = isset($filters[$selectedFilterKey]) ?
                    $filters[$selectedFilterKey]
                    : null;
                if ($selectedFilterLabel) {
                    $groupLabel .= ': <strong>'.$selectedFilterLabel.'</strong>';
                }
            }

            $links = array();
            foreach ($filters as $filter => $label) {
                // Only show 'all' link if filter is active
                if ($filter == 'all' && ! isset($this->filters[$group])) {
                    continue;
                }

                // Don't show links to active filters
                if (isset($this->filters[$group]) && $this->filters[$group] == $filter) {
                    continue;
                }

                $linkFilters = [$group => $filter];
                $linkFilters = array_merge($this->filters, $linkFilters);
                $links[$label] = $linkFilters;
            }

            $buttons[$groupLabel] = $links;
        }
        $this->set('buttons', $buttons);
    }

    public function index()
    {
        if (isset($_GET['search'])) {
            $this->paginate['conditions']['Community.name LIKE'] = '%'.$_GET['search'].'%';
        } else {
            $this->adminIndexFilter();
        }
        $this->cookieSort('AdminCommunityIndex');
        $this->adminIndexSetupPagination();
        $this->adminIndexSetupFilterButtons();
        $this->set(array(
            'communities' => $this->paginate(),
            'title_for_layout' => 'Indiana Communities'
        ));
    }
}
