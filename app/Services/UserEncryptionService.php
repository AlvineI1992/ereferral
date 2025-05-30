<?php

namespace App\Services;

use ParagonIE\CipherSweet\CipherSweet;
use ParagonIE\CipherSweet\EncryptedRow;
use ParagonIE\CipherSweet\BlindIndex;

class UserEncryptionService
{
    protected $cipherSweet;
    protected $encryptedRow;

    public function __construct(CipherSweet $cipherSweet)
    {
        $this->cipherSweet = $cipherSweet;

        $this->encryptedRow = (new EncryptedRow($cipherSweet, 'users'))
            ->addTextField('email')
            ->addBlindIndex('email', new BlindIndex('email_index', [], 32, true));
    }

    public function encrypt(array $data): array
    {
        return $this->encryptedRow->encryptRow($data);
    }

    public function decrypt(array $row): array
    {
        return $this->encryptedRow->decryptRow($row);
    }

    public function getBlindIndex(string $email): string
    {
        return $this->encryptedRow->getBlindIndex('email', $email);
    }
}
