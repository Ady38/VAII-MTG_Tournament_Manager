<?php

namespace App\Models;

use Framework\Core\Model;
use Framework\Core\IIdentity;

class User extends Model implements IIdentity
{
    public ?int $user_id = null;
    public string $username = "";
    public string $password_hash = "";
    public string $email = "";

    /**
     * Authenticate user by username and password
     * @param string $username
     * @param string $password
     * @return User|null
     */
    public static function authenticate(string $username, string $password): ?User
    {
        // Find user by username
        $user = self::findOne(["username" => $username]);
        if ($user && password_verify($password, $user->password_hash)) {
            return $user;
        }
        return null;
    }

    /**
     * Find one user by criteria
     * @param array $criteria
     * @return User|null
     */
    public static function findOne(array $criteria): ?User
    {
        $where = [];
        $params = [];
        foreach ($criteria as $key => $value) {
            $where[] = "$key = :$key";
            $params[":$key"] = $value;
        }
        $sql = "SELECT * FROM app_user WHERE " . implode(" AND ", $where) . " LIMIT 1";
        $results = self::executeRawSQL($sql, $params);
        if ($results && count($results) > 0) {
            $result = $results[0];
            $user = new self();
            foreach ($result as $key => $value) {
                if (property_exists($user, $key)) {
                    $user->$key = $value;
                }
            }
            return $user;
        }
        return null;
    }

    /**
     * Get the name of the user
     * @return string
     */
    public function getName(): string
    {
        return $this->username;
    }
}
