<?php

namespace Kodiak\Security\Model\Authentication;

use Kodiak\Security\Model\User\AuthenticatedUserInterface;

abstract class AuthenticationInterface
{
    const HASH_ALGORITHM    = "sha256";
    const HASH_COST         = 10000;
    const HASH_SALT_LENGTH  = 32; // in bytes
    const HASH_KEY_LENGTH   = 32; // in bytes

    /**
     * @var array
     */
    private $configuration;

    /**
     * AuthenticationInterface constructor.
     * @param array $configuration
     */
    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @return array
     */
    protected function getConfiguration(): array
    {
        return $this->configuration;
    }

    /**
     * @param array $credentials
     * @return AuthenticationTaskResult
     */
    abstract public function login(array $credentials): AuthenticationTaskResult;

    /**
     * @param array $credentials
     * @return AuthenticationTaskResult
     */
    abstract public function register(array $credentials): AuthenticationTaskResult;

    /**
     * @param array $credentials
     * @return AuthenticationTaskResult
     */
    abstract public function deRegister(array $credentials): AuthenticationTaskResult;

    /**
     * @param array $credentials
     * @return AuthenticationTaskResult
     */
    abstract public function resetPassword(array $credentials): AuthenticationTaskResult;

    /**
     * @param array $credentials
     * @return AuthenticationTaskResult
     */
    abstract public function changePassword(array $credentials): AuthenticationTaskResult;

    /**
     * Hash password.
     *
     * @param string $pw
     * @param string|bool $salt
     * @return \stdClass
     */
    public static function hashPassword($pw, $salt=false) {
        // Salt generálás
        if (!$salt) {
            $salt = bin2hex(openssl_random_pseudo_bytes(self::HASH_SALT_LENGTH));
        }
        $hash = self::generatePbkdf2(self::HASH_ALGORITHM, $pw, $salt, self::HASH_COST, 32);
        $r = new \stdClass();
        $r->output = $salt.$hash;
        $r->salt = $salt;
        $r->hash = $hash;
        return $r;
    }

    /**
     * Checks password correctness based on PBKDF2
     *
     * @param string $algorithm Hashing algorithm
     * @param string $password The raw password
     * @param string $salt Salt
     * @param int $count Number of hash
     * @param int $key_length Key length
     * @param bool $raw_output
     * @return bool|mixed|string
     */
    protected static function generatePbkdf2($algorithm, $password, $salt, $count, $key_length, $raw_output = false)
    {
        $algorithm = strtolower($algorithm);
        if(!in_array($algorithm, hash_algos(), true))
            trigger_error('PBKDF2 ERROR: Invalid hash algorithm.', E_USER_ERROR);
        if($count <= 0 || $key_length <= 0)
            trigger_error('PBKDF2 ERROR: Invalid parameters.', E_USER_ERROR);

        if (function_exists("hash_pbkdf2")) {
            // The output length is in NIBBLES (4-bits) if $raw_output is false!
            if (!$raw_output) {
                $key_length = $key_length * 2;
            }
            return hash_pbkdf2($algorithm, $password, $salt, $count, $key_length, $raw_output);
        }

        $hash_length = strlen(hash($algorithm, "", true));
        $block_count = ceil($key_length / $hash_length);

        $output = "";
        for($i = 1; $i <= $block_count; $i++) {
            // $i encoded as 4 bytes, big endian.
            $last = $salt . pack("N", $i);
            // first iteration
            $last = $xorsum = hash_hmac($algorithm, $last, $password, true);
            // perform the other $count - 1 iterations
            for ($j = 1; $j < $count; $j++) {
                $xorsum ^= ($last = hash_hmac($algorithm, $last, $password, true));
            }
            $output .= $xorsum;
        }

        if($raw_output)
            return substr($output, 0, $key_length);
        else
            return bin2hex(substr($output, 0, $key_length));
    }

    /**
     * @param AuthenticatedUserInterface $userCandidate
     * @param string $passwordCandidate
     * @return bool
     */
    protected function checkPbkdf2(AuthenticatedUserInterface $userCandidate, string $passwordCandidate) {
        $salt = substr($userCandidate->getHashedPassword(), 0, self::HASH_SALT_LENGTH*2);
        $hash = $this->hashPassword($passwordCandidate, $salt);
        if ($hash->output == $userCandidate->getHashedPassword()) return true;
        else return false;
    }

    protected function checkPbkdf2ByPassword(string $hashed_password, string $passwordCandidate) {
        $salt = substr($hashed_password, 0, self::HASH_SALT_LENGTH*2);
        $hash = $this->hashPassword($passwordCandidate, $salt);
        if ($hash->output == $hashed_password) return true;
        else return false;
    }
}