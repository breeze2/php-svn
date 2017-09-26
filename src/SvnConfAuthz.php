<?php
namespace BL\LibSvn;

use BL\LibSvn\Exceptions\SvnException;

class SvnConfAuthz
{
    const PERMISSION_NONE      = '';
    const PERMISSION_READ      = 'r';
    const PERMISSION_READWRITE = 'rw';

    private $SIGN_ALL_USERS = '*';
    private $GROUP_SIGN     = '@';
    private $GROUP_SECTION  = 'groups';
    private $ALIAS_SIGN     = '&';
    private $ALIAS_SECTION  = 'aliases';

    /**
     * Holds the SvnConfig object which is used to manage
     * all actions on SvnAuthFile (INI-format).
     *
     * @var SvnConfig
     */
    private $config = null;

    /**
     * Constructor
     *
     * @param string $path Path to the SvnAuthFile
     *
     * @throws SvnException
     */
    public function __construct($path = null)
    {
        if (!empty($path)) {
            $this->open($path);
        }
    }

    /**
     * Open the given SvnAuthFile, which contains permissions
     * of the svn users/groups.
     *
     * @param string $path Path to the SvnAuthFile
     *
     * @return bool
     *
     * @throws SvnException
     */
    public function open($path)
    {
        try {
            $this->config = new SvnConfig($path);
            return true;
        } catch (SvnException $e) {
            throw new SvnException("Can not read SvnAuthFile.", 0, $e);
        }
    }

    /**
     * Writes the changed SvnAuthFile to the given destination file.
     * If $path is 'null' then it will be written to the same file from
     * which the data has been read.
     *
     * @param string $path
     *
     * @return bool
     *
     * @throws SvnException
     */
    public function save($path = null)
    {
        try {
            return $this->config->save($path);
        } catch (SvnException $e) {
            throw new SvnException("Can not write SvnAuthFile.", 0, $e);
        }
        return false;
    }

    /**
     * Gets all existing aliases.
     *
     * @return array <string>
     */
    public function aliases()
    {
        return $this->config->getSectionKeys($this->ALIAS_SECTION);
    }

    /**
     * Gets all existing groups.
     *
     * @return array <string>
     */
    public function groups()
    {
        return $this->config->getSectionKeys($this->GROUP_SECTION);
    }

    /**
     * Gets all configured repositories + repository-path
     *
     * @return array<string>
     */
    public function repositoryPaths()
    {
        $arrSections = $this->config->getSections();
        $ret         = array();

        foreach ($arrSections as $section) {
            if ($section != $this->GROUP_SECTION && $section != $this->ALIAS_SECTION && !empty($section)) // empty = keys without section header.
            {
                $ret[] = $section;
            }
        }

        return $ret;
    }

    /**
     * Resolves the given alias to its real value.
     *
     * @param string $alias
     *
     * @return string
     */
    public function getAliasValue($alias)
    {
        $aliasKey = $alias;
        if (strpos($aliasKey, "&") !== 0) {
            $aliasKey = substr($aliasKey, 1);
        }
        return $this->config->getValue($this->ALIAS_SECTION, $aliasKey, $alias);
    }

    /**
     * Gets all users of the given group.
     *
     * @param string $group
     *
     * @return array<string>
     */
    public function usersOfGroup($group)
    {
        $users_str = $this->config->getValue($this->GROUP_SECTION, $group);

        if ($users_str != null) {
            $users_arr = explode(',', $users_str);
            $users_len = count($users_arr);

            for ($i = 0; $i < $users_len; ++$i) {
                $users_arr[$i] = trim($users_arr[$i]);
                if (!$users_arr[$i] || $users_arr[$i][0] === $this->GROUP_SIGN) {
                    unset($users_arr[$i]);
                }

            }

            $users_arr = array_values($users_arr);

            return $users_arr;
        }

        return array();
    }

    /**
     * Gets all subgroups of the given group.
     *
     * @param string $group
     *
     * @return array<string>
     */
    public function groupsOfGroup($group)
    {
        $groups_str = $this->config->getValue($this->GROUP_SECTION, $group);

        if ($groups_str != null) {
            $groups_arr = explode(',', $groups_str);
            $groups_len = count($groups_arr);

            for ($i = 0; $i < $groups_len; ++$i) {
                $groups_arr[$i] = trim($groups_arr[$i]);
                if ($groups_arr[$i] && $groups_arr[$i][0] === $this->GROUP_SIGN) {
                    $groups_arr[$i] = substr($groups_arr[$i], 1);
                } else {
                    unset($groups_arr[$i]);
                }

            }

            $groups_arr = array_values($groups_arr);

            return $groups_arr;
        }

        return array();
    }

    /**
     * Gets all assigned members and groups which are directly assigned
     * to the given repository path.
     *
     * Groups are indicated with a leading '@' sign.
     *
     * @param string $repository_path
     *
     * @return array<string>
     */
    public function membersOfRepositoryPath($repository_path)
    {
        return $this->config->getSectionKeys($repository_path);
    }

    /**
     * Gets all users which have direct rights to this repository path.
     *
     * @param string $repository_path
     *
     * @return array<string>
     */
    public function usersOfRepositoryPath($repository_path)
    {
        $members = $this->membersOfRepositoryPath($repository_path);
        $users   = array();

        for ($i = 0; $i < count($members); ++$i) {
            if (strpos($members[$i], $this->GROUP_SIGN) === 0) {
                // Current members referes to a group.
                // Skip it.
                continue;
            } else {
                $users[] = $members[$i];
            }
        }

        return $users;
    }

    /**
     * Gets all groups which have direct rights to this repository path.
     *
     * @param string $repository_path
     *
     * @return array<string>
     */
    public function groupsOfRepositoryPath($repository_path)
    {
        $members = $this->membersOfRepositoryPath($repository_path);
        $groups  = array();

        for ($i = 0; $i < count($members); ++$i) {
            if (strpos($members[$i], $this->GROUP_SIGN) === 0) {
                // Remove the leading '@'-sign before adding group
                // to returning array.
                $groups[] = substr($members[$i], 1);
            } else {
                // Current member refers to a user.
                // Skip it.
                continue;
            }
        }

        return $groups;
    }

    /**
     * Gets all groups of which the user is a member.
     *
     * @param string $username
     *
     * @return array<string>
     */
    public function groupsOfUser($username)
    {
        $ret = array();

        $groups = $this->groups();
        foreach ($groups as $g) {
            $users = $this->usersOfGroup($g);
            if (in_array($username, $users)) {
                $ret[] = $g;
            }
        }

        return $ret;
    }

    /**
     * Gets all groups of which the group is a member.
     *
     * @param string $groupname
     *
     * @return array<string>
     */
    public function groupsOfSubgroup($groupname)
    {
        $ret = array();

        $groups = $this->groups();
        foreach ($groups as $g) {
            $subgroups = $this->groupsOfGroup($g);
            if (in_array($groupname, $subgroups)) {
                $ret[] = $g;
            }
        }

        return $ret;
    }

    /**
     * Gets all repository paths which got a specific group as member.
     *
     * @param string $groupname
     *
     * @return array<string>
     */
    public function repositoryPathsOfGroup($groupname)
    {
        $ret = array();

        $repositories = $this->repositoryPaths();
        foreach ($repositories as $repository_path) {
            $groups = $this->groupsOfRepositoryPath($repository_path);
            if (in_array($groupname, $groups)) {
                $ret[] = $repository_path;
            }
        }

        return $ret;
    }

    /**
     * Gets all repository paths which got a specific user as member.
     *
     * @param string $username
     *
     * @return array<string>
     */
    public function repositoryPathsOfUser($username)
    {
        $ret = array();

        $repositories = $this->repositoryPaths();
        foreach ($repositories as $repository_path) {
            $users = $this->usersOfRepositoryPath($repository_path);
            if (in_array($username, $users)) {
                $ret[] = $repository_path;
            }
        }

        return $ret;
    }

    /**
     * Checks whether the repository path already exists in the configuration.
     *
     * @param string $repository_path the repository path
     *
     * @return bool
     */
    public function repositoryPathExists($repository_path)
    {
        return $this->config->getSectionExists($repository_path);
    }

    /**
     * Adds a new repostory configuration path to the SvnAuthFile.
     *
     * @param string $repopath
     *
     * @return bool true=OK; false=Repository path already exists.
     *
     * @throws SvnException If an invalid repository path has been provided.
     */
    public function addRepositoryPath($repopath)
    {
        if ($this->repositoryPathExists($repopath)) {
            // Already exists.
            return false;
        }

        // Validate the $repopath string.
        $pattern = '/^[A-Za-z0-9\_\-.]+:\/.*$/i';
        if ($repopath != "/" && !preg_match($pattern, $repopath)) {
            throw new SvnException('Invalid repository name. (Pattern: ' . $pattern . ')');
        }

        // Create the repository configuration path.
        $this->config->setValue($repopath, null, null);
        return true;
    }

    /**
     * Removes the access path from the configuration.
     *
     * @param string $repopath
     *
     * @return bool
     */
    public function removeRepositoryPath($repopath)
    {
        if (!$this->repositoryPathExists($repopath)) {
            return false;
        }

        return $this->config->removeValue($repopath, null);
    }

    /**
     * Checks whether the group "$groupname" already exists.
     *
     * @param string $groupname
     *
     * @return bool
     */
    public function groupExists($groupname)
    {
        return $this->config->getValueExists($this->GROUP_SECTION, $groupname);
    }

    /**
     * Creates the new group "$groupname", if it does not exist.
     *
     * @param string $groupname
     *
     * @return bool TRUE/FALSE
     *
     * @throws SvnException If an invalid group name has been provided.
     */
    public function createGroup($groupname)
    {
        // Validate the groupname.
        $pattern = '/^[A-Za-z0-9\-\_]+$/i';
        if (!preg_match($pattern, $groupname)) {
            throw new SvnException('Invalid group name "' . $groupname .
                '". Allowed signs are: A-Z, a-z, Underscore, Dash, (no spaces!) ');
        }

        if ($this->groupExists($groupname)) {
            // The group already exists.
            return false;
        }

        $this->config->setValue($this->GROUP_SECTION, $groupname, "");
        return true;
    }

    /**
     * Deletes the given group by name.
     *
     * @param $groupname
     *
     * @return bool
     */
    public function deleteGroup($groupname)
    {
        if (!$this->groupExists($groupname)) {
            return false;
        }
        return $this->config->removeValue($this->GROUP_SECTION, $groupname);
    }

    /**
     * Adds the user to group.
     *
     * @param string $groupname
     * @param string $username
     *
     * @return bool
     */
    public function addUserToGroup($groupname, $username)
    {
        if (!$this->groupExists($groupname)) {
            return false;
        }

        // Get current users and groups.
        $users  = $this->usersOfGroup($groupname);
        $groups = $this->groupsOfGroup($groupname);

        if (!is_array($users) || !is_array($groups)) {
            return false;
        }

        // NOTE: Its no longer an error when the user is already in group!!!
        // Check whether the user is already in group.
        if (in_array($username, $users)) {
            return true;
        }

        // Add user to $users array.
        $users[] = $username;

        // Set changes to config.
        $userString = $this->convertGroupsUsersToString($groups, $users);
        $this->config->setValue($this->GROUP_SECTION, $groupname, $userString);
        return true;
    }

    /**
     * Adds the subgroup to group.
     *
     * @param string $groupname
     * @param string $subgroupname
     *
     * @return bool
     */
    public function addSubgroupToGroup($groupname, $subgroupname)
    {
        if (!$this->groupExists($groupname) || !$this->groupExists($subgroupname)) {
            return false;
        }

        // Get current users and groups.
        $users  = $this->usersOfGroup($groupname);
        $groups = $this->groupsOfGroup($groupname);

        if (!is_array($users) || !is_array($groups)) {
            return false;
        }

        // NOTE: Its no longer an error when the subgroup is already in group!!!
        // Check whether the subgroup is already in group.
        if (in_array($subgroupname, $groups)) {
            return true;
        }

        // Add subgroup to groups array.
        $groups[] = $subgroupname;

        // Set changes to config.
        $userString = $this->convertGroupsUsersToString($groups, $users);
        $this->config->setValue($this->GROUP_SECTION, $groupname, $userString);
        return true;
    }

    /**
     * Checks whether the user is in the given group.
     *
     * @param string $groupname
     * @param string $username
     *
     * @return bool
     */
    public function isUserInGroup($groupname, $username)
    {
        $users = $this->usersOfGroup($groupname);

        if (in_array($username, $users)) {
            return true;
        }
        return false;
    }

    /**
     * Checks whether the subgroups is in the given group.
     *
     * @param string $groupname
     * @param string $subgroupname
     *
     * @return bool
     */
    public function isSubgroupInGroup($groupname, $subgroupname)
    {
        $groups = $this->groupsOfGroup($groupname);

        if (in_array($subgroupname, $groups)) {
            return true;
        }
        return false;
    }

    /**
     * Removes the given user from group.
     *
     * @param string $groupname
     * @param string $username
     *
     * @return bool
     */
    public function removeUserFromGroup($groupname, $username)
    {
        $groupUsers = $this->usersOfGroup($groupname);

        // Search the user in array.
        $pos = array_search($username, $groupUsers);

        if ($pos !== false) {
            // Remove the user from array.
            unset($groupUsers[$pos]);

            $groups = $this->groupsOfGroup($groupname);

            $userString = $this->convertGroupsUsersToString($groups, $groupUsers);
            $this->config->setValue($this->GROUP_SECTION, $groupname, $userString);
        } else {
            // User is not in group.
            return true;
        }
        return true;
    }

    /**
     * Removes the given group from group.
     *
     * @param string $subgroupname
     * @param string $groupname
     *
     * @return bool
     */
    public function removeSubgroupFromGroup($subgroupname, $groupname)
    {
        $groupGroups = $this->groupsOfGroup($groupname);

        // Search the user in array.
        $pos = array_search($subgroupname, $groupGroups);
        if ($pos !== false) {
            // Remove the group from array.
            unset($groupGroups[$pos]);

            $users = $this->usersOfGroup($groupname);

            $userString = $this->convertGroupsUsersToString($groupGroups, $users);
            $this->config->setValue($this->GROUP_SECTION, $groupname, $userString);
        } else {
            // Group is not in group.
            return true;
        }
        return true;
    }

    /**
     * Removes the given $groupname from $repository_path.
     *
     * @param string $groupname
     * @param string $repository_path
     *
     * @return bool
     */
    public function removeGroupFromRepositoryPath($repository_path, $groupname)
    {
        // Does the repo config exists?
        if (!$this->repositoryPathExists($repository_path)) {
            return false;
        }

        $groupname = '@' . $groupname;
        return $this->config->removeValue($repository_path, $groupname);
    }

    /**
     * Removes the given $groupname from $repository_path.
     *
     * @param string $username
     * @param string $repository_path
     *
     * @return bool
     */
    public function removeUserFromRepositoryPath($repository_path, $username)
    {
        if (!$this->repositoryPathExists($repository_path)) {
            return false;
        }
        return $this->config->removeValue($repository_path, $username);
    }

    /**
     * Gets to know whether the user is assigned to a specified
     * repository path (optional: with specific permission.)
     *
     * @param string $username
     * @param string $repository_path
     * @param string $permission
     *
     * @return bool
     */
    public function isUserAssignedToRepositoryPath($username, $repository_path, $permission = null)
    {
        if (!$this->repositoryPathExists($repository_path)) {
            return false;
        }

        if ($this->config->getValueExists($repository_path, $username)) {
            if ($permission == null) {
                return true;
            } else {
                // Provide for specific permission.
                if ($this->config->getValue($repository_path, $username) == $permission) {
                    return true;
                }
                return false;
            }
        }

        return false;
    }

    /**
     * Gets to know whether the user is assigned to a specified
     * repository path (optional: with specific permission.)
     *
     * @param string $username
     * @param string $repository_path
     * @param string $permission
     *
     * @return bool
     */
    public function isGroupAssignedToRepositoryPath($groupname, $repository_path, $permission = null)
    {
        if (!$this->repositoryPathExists($repository_path)) {
            return false;
        }

        $groupname = $this->GROUP_SIGN . $groupname;

        if ($this->config->getValueExists($repository_path, $groupname)) {
            if ($permission == null) {
                return true;
            } else {
                // Provide for specific permission.
                if ($this->config->getValue($repository_path, $groupname) == $permission) {
                    return true;
                }
                return false;
            }
        }

        return false;
    }

    /**
     * Assigns a user directly to a repository with permissions.
     *
     * @param string $repository_path
     * @param string $username
     * @param string $permission
     *
     * @return bool
     */
    public function addUserToRepositoryPath($repository_path, $username, $permission)
    {
        if (!$this->repositoryPathExists($repository_path)) {
            return false;
        }

        $this->config->setValue($repository_path, $username, $permission);
        return true;
    }

    /**
     * Assigns a group directly to a repository with permissions.
     *
     * @param string $repository_path
     * @param string $groupname
     * @param string $permission
     *
     * @return bool
     */
    public function addGroupToRepositoryPath($repository_path, $groupname, $permission)
    {
        if (!$this->repositoryPathExists($repository_path)) {
            return false;
        }

        $groupname = $this->GROUP_SIGN . $groupname;
        $this->config->setValue($repository_path, $groupname, $permission);
        return true;
    }

    /**
     * Gets an array which holds all permissions of a specific user.<br>
     * Returning array example:
     *
     * array(
     *         array(    0 => "repo_path:/",        // Access-Path
     *                 1 => "rw",                // Permission
     *                 2 => "group1"            // Derived group, '*' or empty.
     *         ),
     *         array(    0 => ....
     *         )
     * )
     *
     * @param string $username
     * @param bool $resolveGroups (default=true) Indicates whether groups and *-user should be resolved, too.
     * @param string $filterRepository (default=null) Restricts the returning array to the given repository.
     *
     * @return array See method description for details.
     */
    public function permissionsOfUser($username, $resolveGroups = true, $filterRepository = null)
    {
        $ret = array();

        // Iterate all repository paths.
        $repositories = $this->repositoryPaths();
        foreach ($repositories as $repository_path) {
            // If !null than only prove the $filterRepository.
            if ($filterRepository != null && $filterRepository != $repository_path) {
                continue;
            }

            // Get the permission of the user.
            $permission = $this->config->getValue($repository_path, $username);
            if ($permission !== null) {
                $ret[] = array($repository_path, $permission, '');
            }

            if ($resolveGroups) {
                // Iterate all groups which are directly assigned to the repository
                // and check whether the '$username' is a member.
                $groups = $this->groupsOfRepositoryPath($repository_path);
                foreach ($groups as $g) {
                    if ($this->isUserInGroup($g, $username)) {
                        $g2         = $this->GROUP_SIGN . $g;
                        $permission = $this->config->getValue($repository_path, $g2);
                        if ($permission !== null) {
                            $ret[] = array($repository_path, $permission, $g);
                        }
                    }
                }

                // Get the all-user permissions.
                $permission = $this->config->getValue($repository_path, $this->SIGN_ALL_USERS);
                if ($permission !== null) {
                    $ret[] = array($repository_path, $permission, $this->SIGN_ALL_USERS);
                }
            }

        } // foreach ($repositories)

        return $ret;
    }

    /**
     * Gets an array which holds all permissions of a specific group.<br>
     * Returning array example:
     *
     * array(
     *         array(    0 => "repo_path:/",        // Access-Path
     *                 1 => "rw",                // Permission
     *                 2 => "group1"            // Derived group or empty.
     *         ),
     *         array(    0 => ....
     *         )
     * )
     *
     * @param string $groupname
     * @param bool $resolveGroups (default=true) Indicates whether groups should be resolved, too.
     * @param string $filterRepository (default=null) Restricts the returning array to the given repository.
     *
     * @return array See method description for details.
     */
    public function permissionsOfGroup($groupname, $resolveGroups = true, $filterRepository = null)
    {
        $ret                = array();
        $groupname_internal = $this->GROUP_SIGN . $groupname;

        // Iterate all repository paths.
        $repositories = $this->repositoryPaths();
        foreach ($repositories as $repository_path) {
            // If !null than only prove the $filterRepository.
            if ($filterRepository != null && $filterRepository != $repository_path) {
                continue;
            }

            // Get the direct permission of the group.
            $permission = $this->config->getValue($repository_path, $groupname_internal);
            if ($permission !== null) {
                $ret[] = array($repository_path, $permission, '');
            }

            if ($resolveGroups) {
                // TODO: Iterate all groups, and check whether the current
                // group is a member of one of these.
            }

        } // foreach ($repositories)

        return $ret;
    }

    public function permissionOfUserInRepositoryPath($username, $repository_path, $resolve_groups = true)
    {
        $ret = array();
        // Get the permission of the user.
        $permission = $this->config->getValue($repository_path, $username);
        if ($permission !== null) {
            $ret[] = array($username=>$permission);
        }

        if ($resolve_groups) {
            // Iterate all groups which are directly assigned to the repository
            // and check whether the '$username' is a member.
            $groups = $this->groupsOfRepositoryPath($repository_path);
            foreach ($groups as $g) {
                if ($this->isUserInGroup($g, $username)) {
                    $g2         = $this->GROUP_SIGN . $g;
                    $permission = $this->config->getValue($repository_path, $g2);
                    if ($permission !== null) {
                        $ret[] = array($g=>$permission);
                    }
                }
            }

            // Get the all-user permissions.
            $permission = $this->config->getValue($repository_path, $this->SIGN_ALL_USERS);
            if ($permission !== null) {
                $ret[] = array($this->SIGN_ALL_USERS=>$permission);
            }
        }
        return $ret;
    }

    /**
     * Convert list of groups and users to string to associate a group.
     *
     * @param array $groups
     * @param array $users
     * @return string
     * @see http://svnbook.red-bean.com/en/1.7/svn.serverconfig.pathbasedauthz.html
     */
    private static function convertGroupsUsersToString(array $groups, array $users)
    {
        if (!$groups && !$users) {
            return '';
        }

        // anonymous functions works on PHP 5.3 or higher
        array_walk($groups, function (&$item) {
            $item = '@' . $item;
        });

        $string = join(',', array_merge($groups, $users));
        return $string;
    }
}
