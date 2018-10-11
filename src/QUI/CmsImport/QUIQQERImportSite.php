<?php

namespace QUI\CmsImport;

use QUI;
use QUI\Projects\Site\Edit;

class QUIQQERImportSite extends Edit
{
    /**
     * Create a new Child
     *
     * @param array $params
     * @param array $childPermissions - [optional] permissions for the child
     * @param boolean|QUI\Users\User|QUI\Users\SystemUser $User - [optional] the user which create the site, optional
     *
     * @return Int
     * @throws QUI\Exception
     */
    public function createChild(
        $params = [],
        $childPermissions = [],
        $User = false
    ) {
        if ($User == false) {
            $User = QUI::getUserBySession();
        }

        $this->checkPermission('quiqqer.projects.site.new', $User);


        //$newid    = $Project->getNewId();
        $new_name = 'Neue Seite';   // @todo multilingual
        $old      = $new_name;

        // Namen vergeben falls existiert
        $i = 1;

        if (!isset($params['name']) || empty($params['name'])) {
            while ($this->existNameInChildren($new_name)) {
                $new_name = $old.' ('.$i.')';
                $i++;
            }
        } else {
            $new_name = $params['name'];
        }

        if ($this->existNameInChildren($new_name)) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.site.same.name',
                    [
                        'name' => $new_name
                    ]
                )
            );
        }

        // can we use this name?
        QUI\Projects\Site\Utils::checkName($new_name);


        $childCount = $this->hasChildren(true);

        $_params = [
            'name'        => $new_name,
            'title'       => $new_name,
            'c_date'      => date('Y-m-d H:i:s'),
            'e_user'      => $User->getId(),
            'c_user'      => $User->getId(),
            'c_user_ip'   => QUI\Utils\System::getClientIP(),
            'order_field' => $childCount + 1
        ];

        if (isset($params['title'])) {
            $_params['title'] = $params['title'];
        }

        if (isset($params['short'])) {
            $_params['short'] = $params['short'];
        }

        if (isset($params['content'])) {
            $_params['content'] = $params['content'];
        }

        $DataBase = QUI::getDataBase();
        $PDO      = $DataBase->getPDO();

        // temporarily remove AUTO_INCREMENT from id column in `sites` to allow custom site IDs
        if (!empty($params['id'])) {
            $_params['id'] = (int)$params['id'];

            // disable AUTO_INCREMENT
            $Statement = $PDO->prepare("ALTER TABLE {$this->TABLE} MODIFY `id` INT(11) NOT NULL");
            $Statement->execute();

            $DataBase->insert($this->TABLE, $_params);

            // re-enable AUTO_INCREMENT
            $Statement = $PDO->prepare("ALTER TABLE {$this->TABLE} MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT");
            $Statement->execute();

            $newId = $_params['id'];
        } else {
            $DataBase->insert($this->TABLE, $_params);
            $newId = $DataBase->getPDO()->lastInsertId();
        }

        // something is wrong
        if ($newId == 0) {
            $max = $DataBase->fetch([
                'select' => 'MAX(id)',
                'from'   => $this->TABLE
            ]);

            $newId = (int)reset($max[0]) + 1;

            $DataBase->update(
                $this->TABLE,
                ['id' => $newId],
                ['id' => 0]
            );
        }

        $DataBase->insert($this->RELTABLE, [
            'parent' => $this->getId(),
            'child'  => $newId
        ]);

        // copy permissions to the child
        $PermManager    = QUI::getPermissionManager();
        $permissions    = $PermManager->getSitePermissions($this);
        $newPermissions = [];

        // parent permissions
        foreach ($permissions as $permission => $value) {
            if (empty($value)) {
                continue;
            }

            $newPermissions[$permission] = $value;
        }

        // optional new permission
        foreach ($childPermissions as $permission => $value) {
            if (empty($value)) {
                continue;
            }

            $newPermissions[$permission] = $value;
        }

        if (!empty($newPermissions)) {
            $Child = new Edit($this->getProject(), $newId);

            $PermManager->setSitePermissions(
                $Child,
                $newPermissions,
                QUI::getUsers()->getSystemUser()
            );
        }

        // Aufruf der createChild Methode im TempSite - für den Adminbereich
        $this->Events->fireEvent('createChild', [$newId, $this]);
        QUI::getEvents()->fireEvent('siteCreateChild', [$newId, $this]);


        return $newId;
    }
}
