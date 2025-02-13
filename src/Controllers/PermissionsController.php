<?php

/**
 * This file is part of the Vökuró.
 *
 * (c) Phalcon Team <team@phalcon.io>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Vokuro\Controllers;

use Vokuro\Models\Permissions;
use Vokuro\Models\Profiles;

use const PHP_EOL;

/**
 * View and define permissions for the various profile levels.
 */
class PermissionsController extends ControllerBase
{
    /**
     * View the permissions for a profile level, and change them if we have a
     * POST.
     */
    public function indexAction(): void
    {
        $this->view->setTemplateBefore('private');

        //default select option value
        $selectedOption = 0;
        if ($this->request->isPost()) {
            $selectedOption = $this->request->getPost('profileId');
            $profile = Profiles::findFirstById($selectedOption);
            if ($profile) {
                if ($this->request->hasPost('permissions') && $this->request->hasPost('submit')) {
                    // Deletes the current permissions
                    $profile->getPermissions()
                            ->delete()
                    ;

                    // Save the new permissions
                    foreach ($this->request->getPost('permissions') as $permission) {
                        $parts = explode('.', $permission);

                        $permission             = new Permissions();
                        $permission->profilesId = $profile->id;
                        $permission->resource   = $parts[0];
                        $permission->action     = $parts[1];

                        $permission->save();
                    }

                    $this->flash->success('Permissions were updated with success');
                }

                // Rebuild the ACL with
                $this->acl->rebuild();

                // Pass the current permissions to the view
                $this->view->setVar('permissions', $this->acl->getPermissions($profile));
            }

            $this->view->setVar('profile', $profile);
        }

        $profiles = Profiles::find([
            'active = :active:',
            'bind' => [
                'active' => 'Y',
            ],
        ]);

        $options = [
            'id' => 'profileId',
            'name' => 'profileId',
            'class' => 'form-control mr-sm-2'
        ];

        $select  = $this
            ->tag
            ->inputSelect('    ', PHP_EOL, $options)
            ->addPlaceholder('...', '', [], true)
            ->selected((string) $selectedOption)
        ;

        foreach ($profiles as $profile) {
            $select->add($profile->name, (string) $profile->id);
        }

        $this->view->setVar('profilesSelect', $select);
    }
}
