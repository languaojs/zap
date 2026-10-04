<?php

namespace Zap\Core\Utils;

class Hasher
{

    /**
     * Use for generating password
     * @param string $string password to hash
     * @return string Hashed password
     */
    public function hash($string)
    {
        $option = ['cost' => 10];
        $password = password_hash($string, PASSWORD_BCRYPT, $option);
        return $password;
    }

    /**
     * Use for verifying password
     * @param string $hash hashed password
     * @param string $string password to verify
     * @return bool
     */
    public function verify($hash, $string)
    {
        if (!password_verify($string, $hash)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Use for generating an UUID version 4
     * @return string UUID version 4
     */
    public function generateUUIDv4()
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        $uuid = bin2hex(substr($data, 0, 4)) . '-' .
            bin2hex(substr($data, 4, 2)) . '-' .
            bin2hex(substr($data, 6, 2)) . '-' .
            bin2hex(substr($data, 8, 2)) . '-' .
            bin2hex(substr($data, 10, 6));

        return $uuid;
    }

    public function generateVerificationToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }
}
