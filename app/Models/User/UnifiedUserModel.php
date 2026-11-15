<?php

namespace App\Models\User;

use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Authentication\Authenticators\Session;

class UnifiedUserModel extends UserModel
{
    private const TABLE_STAFF   = 'users';
    private const TABLE_SISWA   = 'users_siswa';

    protected string $userTable = self::TABLE_STAFF;

    protected function initialize(): void
    {
        parent::initialize();
        $this->table = $this->userTable;
    }

    public function findById($id): ?User
    {
        foreach ([self::TABLE_STAFF, self::TABLE_SISWA] as $table) {
            $row = $this->db->table($table)
                ->where($table . '.id', $id)
                ->where($table . '.deleted_at', null)
                ->get()
                ->getRowArray();

            if ($row !== null) {
                $user = new User($row);
                $user->syncOriginal();
                $this->table = $table;
                return $user;
            }
        }

        return null;
    }

    public function findByCredentials(array $credentials): ?User
    {
        $role = isset($credentials['role']) && is_scalar($credentials['role'])
            ? strtolower(trim((string) $credentials['role']))
            : null;
        unset($credentials['role']);

        $tables = $this->tablesForRole($role);

        if ($tables === [] || (! isset($credentials['email']) && $credentials === [])) {
            return null;
        }

        $email = $credentials['email'] ?? null;

        if ($email !== null && ! is_scalar($email)) {
            return null;
        }

        unset($credentials['email']);

        foreach ($credentials as $key => $value) {
            if (! in_array($key, ['username', 'nisn'], true) || ! is_scalar($value)) {
                return null;
            }
        }

        foreach ($tables as $table) {
            $user = $this->findInTable($table, $credentials, $email === null ? null : (string) $email);

            if ($user !== null) {
                return $user;
            }
        }

        return null;
    }

    /**
     * Menentukan urutan tabel berdasarkan peran yang dipilih di halaman login.
     *
     * @return list<string>
     */
    private function tablesForRole(?string $role): array
    {
        return match ($role) {
            'admin', 'panitia', 'staff' => [self::TABLE_STAFF],
            'student'                  => [self::TABLE_SISWA],
            null                       => [self::TABLE_STAFF, self::TABLE_SISWA],
            default                    => [],
        };
    }

    /**
     * Mencari user pada satu tabel beserta password hash dari auth_identities.
     */
    private function findInTable(string $table, array $credentials, ?string $email): ?User
    {
        $builder = $this->db->table($table);

        foreach ($credentials as $key => $value) {
            $value = strtolower((string) $value);

            if ($key === 'username') {
                $builder->groupStart()
                    ->where('LOWER(username)', $value)
                    ->orWhere('LOWER(nisn)', $value)
                    ->groupEnd();
                continue;
            }

            $builder->where('LOWER(' . $key . ')', $value);
        }

        $builder->join('auth_identities', 'auth_identities.user_id = ' . $table . '.id')
            ->where('auth_identities.type', Session::ID_TYPE_EMAIL_PASSWORD)
            ->select($table . '.*, auth_identities.secret as email, auth_identities.secret2 as password_hash');

        if ($email !== null) {
            $builder->where('LOWER(auth_identities.secret)', strtolower($email));
        }

        $row = $builder->get()->getRowArray();

        if ($row === null) {
            return null;
        }

        $emailValue = $row['email'];
        $passwordHash = $row['password_hash'];

        unset($row['email'], $row['password_hash']);

        $user = new User($row);
        $user->email = $emailValue;
        $user->password_hash = $passwordHash;
        $user->syncOriginal();

        $this->table = $table;

        return $user;
    }

    public function save($row = null): bool
    {
        if (is_object($row) && isset($row->id)) {
            $id = $row->id;

            $exists = $this->db->table(self::TABLE_STAFF)
                ->where('id', $id)
                ->countAllResults();

            if ($exists > 0) {
                $this->table = self::TABLE_STAFF;
                return parent::save($row);
            }

            $exists = $this->db->table(self::TABLE_SISWA)
                ->where('id', $id)
                ->countAllResults();

            if ($exists > 0) {
                $this->table = self::TABLE_SISWA;
                return parent::save($row);
            }
        }

        $this->table = self::TABLE_STAFF;
        return parent::save($row);
    }

    public function updateActiveDate(User $user): void
    {
        $lastActive = $this->timeToDate($user->last_active);

        $this->db->table(self::TABLE_STAFF)
            ->set('last_active', $lastActive)
            ->where('id', $user->id)
            ->update();

        $this->db->table(self::TABLE_SISWA)
            ->set('last_active', $lastActive)
            ->where('id', $user->id)
            ->update();
    }
}
