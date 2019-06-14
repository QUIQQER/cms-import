<?php

namespace QUI\CmsImport;

use QUI;
use QUI\Users\Manager;

class QUIQQERImportUserManager extends Manager
{
    /**
     * Create a new User
     *
     * @internal This is an (almost) exact copy of \QUI\Users\Manager::createChild()
     * with the only difference that you can set a custom ID for a user
     *
     * @param string|boolean $username - (optional), new username
     * @param QUI\Interfaces\Users\User $ParentUser - (optional), Parent User, which create the user
     * @param int $customId (optional) - Set custom ID for the user
     *
     * @return QUI\Users\User
     * @throws QUI\Users\Exception
     * @throws QUI\Exception
     */
    public function createChild($username = false, $ParentUser = null, $customId = null)
    {
        if ($username) {
            if ($this->usernameExists($username)) {
                throw new QUI\Users\Exception(
                    QUI::getLocale()->get(
                        'quiqqer/system',
                        'exception.lib.user.exist'
                    )
                );
            }

            $newName = $username;
        } else {
            $newUserLocale = QUI::getLocale()->get('quiqqer/quiqqer', 'user.create.new.username');
            $newName       = $newUserLocale;
            $i             = 0;

            while ($this->usernameExists($newName)) {
                $newName = $newUserLocale.' ('.$i.')';
                $i++;
            }
        }

        self::checkUsernameSigns($username);

        try {
            $uuid = QUI\Utils\Uuid::get();
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            throw new QUI\Users\Exception('Could not create User. Please try again later.');
        }

        $data = [
            'uuid'     => $uuid,
            'username' => $newName,
            'regdate'  => time(),
            'lang'     => QUI::getLocale()->getCurrent()
        ];

        if (!empty($customId)) {
//            $PDO        = QUI::getDataBase()->getPDO();
            $table      = self::table();
            $data['id'] = (int)$customId;

            /**
             * Disabling and re-enabling fo AUTO_INCREMENT for the `id` column is disabled here
             */

            // disable AUTO_INCREMENT
//            $Statement = $PDO->prepare("ALTER TABLE {$table} MODIFY `id` INT(11) NOT NULL");
//            $Statement->execute();

            QUI::getDataBase()->insert($table, $data);

            // re-enable AUTO_INCREMENT
//            $Statement = $PDO->prepare("ALTER TABLE {$table} MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT");
//            $Statement->execute();

            $newId = $data['id'];
        } else {
            QUI::getDataBase()->insert(self::table(), $data);
            $newId = QUI::getDataBase()->getPDO()->lastInsertId();
        }

        $User = $this->get($newId);

        // workspace
        $twoColumn   = QUI\Workspace\Manager::getTwoColumnDefault();
        $threeColumn = QUI\Workspace\Manager::getThreeColumnDefault();

        $newWorkspaceId = QUI\Workspace\Manager::addWorkspace(
            $User,
            QUI::getLocale()->get('quiqqer/quiqqer', 'workspaces.2.columns'),
            $twoColumn,
            500,
            700
        );

        QUI\Workspace\Manager::addWorkspace(
            $User,
            QUI::getLocale()->get('quiqqer/quiqqer', 'workspaces.3.columns'),
            $threeColumn,
            500,
            700
        );

        QUI\Workspace\Manager::setStandardWorkspace($User, $newWorkspaceId);

        $Everyone = new QUI\Groups\Everyone();

        $User->setAttribute('toolbar', $Everyone->getAttribute('toolbar'));

        if (!$User->getAttribute('toolbar')) {
            $available = QUI\Editor\Manager::getToolbars();

            if (!empty($available)) {
                $User->setAttribute('toolbar', $available[0]);
            }
        }

        $User->addToGroup($Everyone->getId());
        $User->save($ParentUser);

        QUI::getEvents()->fireEvent('userCreate', [$User]);

        return $User;
    }
}
