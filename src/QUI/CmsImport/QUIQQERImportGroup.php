<?php

namespace QUI\CmsImport;

use QUI;
use QUI\Groups\Group;
use QUI\Groups\Manager;

/**
 * Class QUIQQERImportGroup
 *
 * Extension of the default QUIQQER group class to allow custom IDs on create
 */
class QUIQQERImportGroup extends Group
{
    /**
     * Create a subgroup
     *
     * @internal This is an almost exact copy of \QUI\Groups\Group::createChild
     * with the only difference that it allows a custom ID for new groups
     *
     * @param string $name - name of the subgroup
     * @param QUI\Interfaces\Users\User $ParentUser - (optional), Parent User, which create the user
     * @param int $customId (optional)
     *
     * @return QUI\Groups\Group
     * @throws QUI\Exception
     */
    public function createChild($name, $ParentUser = null, $customId = null)
    {
        if (!empty($customId)) {
            $newId = (int)$customId;
        } else {
            $create = true;
            $newId  = false;

            while ($create) {
                mt_srand(microtime(true) * 1000000);
                $newId = mt_rand(10, 1000000000);

                $result = QUI::getDataBase()->fetch([
                    'select' => 'id',
                    'from'   => Manager::table(),
                    'where'  => [
                        'id' => $newId
                    ]
                ]);

                if (!isset($result[0]) || !$result[0]['id']) {
                    $create = false;
                }
            }

            if (!$newId) {
                throw new QUI\Exception(
                    QUI::getLocale()->get(
                        'quiqqer/quiqqer',
                        'exception.group.create.id.creation.error'
                    )
                );
            }
        }

        QUI::getDataBase()->insert(Manager::table(), [
            'id'     => $newId,
            'name'   => $name,
            'parent' => $this->getId(),
            'active' => 0
        ]);

        $Group = QUI::getGroups()->get($newId);

        // set standard permissions
        QUI::getPermissionManager()->importPermissionsForGroup($Group, $ParentUser);

        QUI::getEvents()->fireEvent('groupCreate', [$Group]);

        return $Group;
    }
}
