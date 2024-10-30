<?php
namespace My\Topadm\Db;

use My\Topadm\Db\DbCipherInterface;
use My\Topadm\Db\DbManager;
use \PDO;
use \Exception;

class DbAesCipher implements DbCipherInterface
{
    private string $key;
    private DbManager $dbManager;
    private const RANDOM_BYTES = 16;  // IV 길이
    private const MYSQL_ENCRYPTION_MODE = 'aes-256-cbc';

    public function __construct(string $key, DbManager $dbManager)
    {
        $this->key = $key;
        $this->dbManager = $dbManager;
    }

    public function encrypt(string $column): string
    {
        try {
            return match($this->dbManager->db_type) {
                'mysql' => $this->encryptMySQL($column),
                'pgsql' => $this->encryptPostgreSQL($column),
                default => throw new Exception("Unsupported database type for encryption: {$this->dbManager->db_type}"),
            };
        } catch (Exception $e) {
            error_log("Encryption error: " . $e->getMessage());
            throw $e; // Re-throw the exception after logging
        }
    }

    public function decrypt(string $column): string
    {
        try {
            return match($this->dbManager->db_type) {
                'mysql' => $this->decryptMySQL($column),
                'pgsql' => $this->decryptPostgreSQL($column),
                default => throw new Exception("Unsupported database type for decryption: {$this->dbManager->db_type}"),
            }; 
        } catch (Exception $e) {
            error_log("Encryption error: " . $e->getMessage());
            throw $e; // Re-throw the exception after logging
        }
    }

    private function encryptMySQL(string $column): string
    {
        return "HEX(AES_ENCRYPT('{$column}', SHA2('{$this->key}', 512), RANDOM_BYTES(" . self::RANDOM_BYTES . ")))";
    }

    private function decryptMySQL(string $column): string
    {
        return "AES_DECRYPT(UNHEX($column), SHA2('{$this->key}', 512))";
    }

    // private function encryptPostgreSQL(string $column): string
    // {
    //     return sprintf(
    //         "encode(convert_to('%s', 'UTF8'), 'hex')", 
    //         $column
    //     );
    // }

    // private function decryptPostgreSQL(string $column): string
    // {
    //     return sprintf(
    //         "convert_from(decode(%s, 'hex'), 'UTF8')", 
    //         $column
    //     );
    // }
    private function encryptPostgreSQL(string $column): string
    {
        return sprintf(
            "encode(pgp_sym_encrypt(convert_to('%s', 'UTF8')::text, '%s'), 'hex')",
            $column,
            $this->key
        );
    }

    private function decryptPostgreSQL(string $column): string
    {
        return sprintf(
            "convert_from(pgp_sym_decrypt(decode('%s', 'hex'), '%s'), 'UTF8')",
            $column,
            $this->key
        );
    }

    public function set_mysql_encryption_mode(): void
    {
        if ($this->dbManager->db_type !== 'mysql') {
            throw new Exception("This method is only applicable for MySQL databases.");
        }

        $pdo = $this->dbManager->pdo;

        # 서버 버전
        $mysql_version = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
        if (version_compare($mysql_version, '5.7.0', '<')) {
            throw new Exception(sprintf("Setting(%s) is not possible for MySQL version lower than 5.7.0", self::MYSQL_ENCRYPTION_MODE));
        }

        # encryption_mode 확인
        $encryption_mode_qry = "SELECT @@session.block_encryption_mode as em";
        $stmt = $pdo->query($encryption_mode_qry);
        $encryption_row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (isset($encryption_row['em'])) {
            if ($encryption_row['em'] !== self::MYSQL_ENCRYPTION_MODE) {
                $set_encrypt_qry = sprintf("SET @@session.block_encryption_mode = '%s'", self::MYSQL_ENCRYPTION_MODE);
                $pdo->exec($set_encrypt_qry);
            }
        }
    }
}