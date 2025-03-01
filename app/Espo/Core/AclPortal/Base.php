<?php
/*
 * This file is part of EspoCRM and/or AtroCore.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * AtroCore is EspoCRM-based Open Source application.
 * Copyright (C) 2020 AtroCore UG (haftungsbeschränkt).
 *
 * AtroCore as well as EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * AtroCore as well as EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word
 * and "AtroCore" word.
 */

namespace Espo\Core\AclPortal;

use \Espo\Entities\User;
use \Espo\ORM\Entity;

class Base extends \Espo\Core\Acl\Base
{
    public function checkScope(User $user, $data, $action = null, Entity $entity = null, $entityAccessData = array())
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (is_null($data)) {
            return false;
        }
        if ($data === false) {
            return false;
        }
        if ($data === true) {
            return true;
        }
        if (is_string($data)) {
            return true;
        }

        $isOwner = null;
        if (isset($entityAccessData['isOwner'])) {
            $isOwner = $entityAccessData['isOwner'];
        }
        $inAccount = null;
        if (isset($entityAccessData['inAccount'])) {
            $inAccount = $entityAccessData['inAccount'];
        }

        if (is_null($action)) {
            return true;
        }

        if (!isset($data->$action)) {
            return false;
        }

        $value = $data->$action;

        if ($value === 'all' || $value === 'yes' || $value === true) {
            return true;
        }

        if (!$value || $value === 'no') {
            return false;
        }

        if (is_null($isOwner)) {
            if ($entity) {
                $isOwner = $this->checkIsOwner($user, $entity);
            } else {
                return true;
            }
        }

        if ($isOwner) {
            if ($value === 'own' || $value === 'account') {
                return true;
            }
        }

        if ($value === 'account') {
            if (is_null($inAccount) && $entity) {
                $inAccount = $this->checkInAccount($user, $entity);
            }
            if ($inAccount) {
                return true;
            }
        }

        return false;

    }

    public function checkReadOnlyAccount(User $user, $data)
    {
        if (empty($data) || !is_object($data) || !isset($data->read)) {
            return false;
        }
        return $data->read === 'account';
    }

    public function checkInAccount(User $user, Entity $entity)
    {
        if (!empty($accountId = $user->get('accountId'))) {
            if ($entity->hasAttribute('accountId')) {
                if ($entity->get('accountId') === $accountId) {
                    return true;
                }
            }

            if ($entity->hasRelation('assignedAccounts')) {
                $repository = $this->getEntityManager()->getRepository($entity->getEntityType());
                if ($repository->isRelated($entity, 'assignedAccounts', $accountId)) {
                    return true;
                }
            } elseif ($entity->hasRelation('accounts')) {
                $repository = $this->getEntityManager()->getRepository($entity->getEntityType());
                if ($repository->isRelated($entity, 'accounts', $accountId)) {
                    return true;
                }
            }

            if ($entity->hasAttribute('parentId') && $entity->hasRelation('parent')) {
                if ($entity->get('parentType') === 'Account') {
                    if ($entity->get('parentId') === $accountId) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
